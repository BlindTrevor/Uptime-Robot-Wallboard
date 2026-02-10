<?php

// Event Logger - Handles writing events to NDJSON file
// API Documentation: Custom event logging for wallboard

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
    'eventLoggingMode' => 'circular',
    'eventLoggingMaxEvents' => 1000,
];

if ($configPath !== null) {
    $parsed = parseEnvFile($configPath, [
        'EVENT_LOGGING_MODE',
        'EVENT_LOGGING_MAX_EVENTS'
    ]);
    
    if (isset($parsed['EVENT_LOGGING_MODE'])) {
        $loggingMode = strtolower($parsed['EVENT_LOGGING_MODE']);
        if (in_array($loggingMode, ['circular', 'forever'], true)) {
            $CONFIG['eventLoggingMode'] = $loggingMode;
        }
    }
    
    if (isset($parsed['EVENT_LOGGING_MAX_EVENTS']) && is_numeric($parsed['EVENT_LOGGING_MAX_EVENTS'])) {
        $CONFIG['eventLoggingMaxEvents'] = max(10, (int)$parsed['EVENT_LOGGING_MAX_EVENTS']);
    }
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

// Get JSON payload
$input = file_get_contents('php://input');
$event = json_decode($input, true);

if (!is_array($event)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON payload']);
    exit;
}

// Validate required fields
$requiredFields = ['monitorId', 'monitorName', 'eventType', 'timestamp'];
foreach ($requiredFields as $field) {
    if (!isset($event[$field])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => "Missing required field: $field"]);
        exit;
    }
}

// Validate event type
$validEventTypes = ['up', 'down', 'paused', 'error', 'transient'];
if (!in_array($event['eventType'], $validEventTypes, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid eventType. Must be one of: ' . implode(', ', $validEventTypes)]);
    exit;
}

// Add server timestamp
$event['recordedAt'] = date('c');

// Path to NDJSON file
$eventsFile = __DIR__ . '/events.ndjson';

// Append event to file
$eventJson = json_encode($event, JSON_UNESCAPED_SLASHES);
if ($eventJson === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to encode event']);
    exit;
}

// Atomic write with lock
$fp = fopen($eventsFile, 'a');
if (!$fp) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to open events file']);
    exit;
}

if (flock($fp, LOCK_EX)) {
    fwrite($fp, $eventJson . "\n");
    fflush($fp);
    flock($fp, LOCK_UN);
} else {
    fclose($fp);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Failed to acquire file lock']);
    exit;
}

fclose($fp);

// Handle circular logging: prune old events if needed
if ($CONFIG['eventLoggingMode'] === 'circular') {
    pruneOldEvents($eventsFile, $CONFIG['eventLoggingMaxEvents']);
}

echo json_encode(['ok' => true, 'message' => 'Event logged successfully']);

/**
 * Prune old events to keep only the most recent N events
 */
function pruneOldEvents(string $filePath, int $maxEvents): void {
    if (!file_exists($filePath)) {
        return;
    }
    
    // Read all events
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false || count($lines) <= $maxEvents) {
        return; // No pruning needed
    }
    
    // Keep only the last N events
    $linesToKeep = array_slice($lines, -$maxEvents);
    
    // Write back to file atomically
    $fp = fopen($filePath, 'w');
    if (!$fp) {
        error_log('[Event Logger] Failed to open events file for pruning');
        return;
    }
    
    if (flock($fp, LOCK_EX)) {
        foreach ($linesToKeep as $line) {
            fwrite($fp, $line . "\n");
        }
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    
    fclose($fp);
}
