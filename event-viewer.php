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

$totalEvents = count($events);

// Handle pagination
if ($perPage === 'all') {
    $paginatedEvents = $events;
    $totalPages = 1;
} else {
    $totalPages = (int)ceil($totalEvents / $perPage);
    $page = min($page, max(1, $totalPages)); // Clamp page to valid range
    $offset = ($page - 1) * $perPage;
    $paginatedEvents = array_slice($events, $offset, $perPage);
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
