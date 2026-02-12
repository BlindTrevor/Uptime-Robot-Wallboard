<?php

// Event Viewer API - Handles reading events from NDJSON file
// Returns paginated events in JSON format

declare(strict_types=1);

// Block direct browsing - only allow access from application
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$serverName = $_SERVER['SERVER_NAME'] ?? '';

$refererValid = false;
if (!empty($referer) && !empty($serverName)) {
    $refererParts = parse_url($referer);
    if (is_array($refererParts) && isset($refererParts['host'])) {
        $refererValid = strcasecmp($refererParts['host'], $serverName) === 0;
    }
}

if (!$refererValid) {
    http_response_code(403);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/uptime_errors.log');

// Load shared configuration utilities
require_once __DIR__ . '/config-utils.php';

// Load configuration
$configPath = findConfigPath();

$CONFIG = [
    'eventViewerItemsPerPage' => 50,
];

if ($configPath !== null) {
    $parsed = parseEnvFile($configPath, [
        'EVENT_VIEWER_ITEMS_PER_PAGE'
    ]);
    
    if (isset($parsed['EVENT_VIEWER_ITEMS_PER_PAGE'])) {
        $itemsPerPage = strtolower(trim($parsed['EVENT_VIEWER_ITEMS_PER_PAGE']));
        if ($itemsPerPage === 'all') {
            $CONFIG['eventViewerItemsPerPage'] = 'all';
        } elseif (is_numeric($itemsPerPage)) {
            $CONFIG['eventViewerItemsPerPage'] = max(10, (int)$itemsPerPage);
        }
    }
}

// Get pagination parameters
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = $CONFIG['eventViewerItemsPerPage'];

// Override perPage if provided in query string
if (isset($_GET['perPage'])) {
    $queryPerPage = strtolower(trim($_GET['perPage']));
    if ($queryPerPage === 'all') {
        $perPage = 'all';
    } elseif (is_numeric($queryPerPage)) {
        $perPage = max(10, (int)$queryPerPage);
    }
}

// Get event type filters (default: show all)
$eventTypeFilters = [
    'down' => true,
    'up' => true,
    'paused' => true,
    'error' => true,
];

// Apply filters from query parameter (with validation)
if (isset($_GET['filters'])) {
    $filterParam = $_GET['filters'];
    if (is_string($filterParam) && strlen($filterParam) < 1000) { // Sanity check length
        $filterDecoded = json_decode($filterParam, true);
        // Only accept array with expected keys and boolean values
        if (is_array($filterDecoded) && json_last_error() === JSON_ERROR_NONE) {
            // Whitelist: only process known event type keys
            foreach ($eventTypeFilters as $type => $default) {
                if (array_key_exists($type, $filterDecoded) && is_bool($filterDecoded[$type])) {
                    $eventTypeFilters[$type] = $filterDecoded[$type];
                }
            }
        }
    }
}

// Path to NDJSON file
$eventsFile = __DIR__ . '/events.ndjson';

// Check if file exists
if (!file_exists($eventsFile)) {
    echo json_encode([
        'ok' => true,
        'events' => [],
        'pagination' => [
            'page' => 1,
            'perPage' => $perPage,
            'totalEvents' => 0,
            'totalPages' => 0,
        ],
    ]);
    exit;
}

// Read events from file
$fp = fopen($eventsFile, 'r');
if (!$fp) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to open events file']);
    exit;
}

// Read all events with shared lock
$events = [];
if (flock($fp, LOCK_SH)) {
    while (($line = fgets($fp)) !== false) {
        $line = trim($line);
        if (empty($line)) {
            continue;
        }
        
        $event = json_decode($line, true);
        if (is_array($event)) {
            $events[] = $event;
        }
    }
    flock($fp, LOCK_UN);
}

fclose($fp);

// Reverse to show most recent first
$events = array_reverse($events);

// Apply event type filters BEFORE pagination
$filteredEvents = array_filter($events, function($event) use ($eventTypeFilters) {
    if (!isset($event['eventType'])) {
        return true;
    }
    
    // Normalize event type - handle 'transient' as 'error'
    $eventType = $event['eventType'] === 'transient' ? 'error' : $event['eventType'];
    
    // Check if this event type should be shown
    return isset($eventTypeFilters[$eventType]) && $eventTypeFilters[$eventType] === true;
});

// Re-index array after filtering
$filteredEvents = array_values($filteredEvents);

$totalEvents = count($filteredEvents);

// Handle pagination on filtered events
if ($perPage === 'all') {
    $paginatedEvents = $filteredEvents;
    $totalPages = 1;
} else {
    $totalPages = $totalEvents > 0 ? (int)ceil($totalEvents / $perPage) : 1;
    $page = min($page, max(1, $totalPages)); // Clamp page to valid range
    $offset = ($page - 1) * $perPage;
    $paginatedEvents = array_slice($filteredEvents, $offset, $perPage);
}

echo json_encode([
    'ok' => true,
    'events' => $paginatedEvents,
    'pagination' => [
        'page' => $page,
        'perPage' => $perPage,
        'totalEvents' => $totalEvents,
        'totalPages' => $totalPages,
    ],
], JSON_UNESCAPED_SLASHES);
