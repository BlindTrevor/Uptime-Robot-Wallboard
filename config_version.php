<?php

// Endpoint to check if config.env has changed
// Returns a hash of the config file modification time
// Used by the front-end to detect config changes and auto-refresh

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/uptime_errors.log');

// Load shared configuration utilities
require_once __DIR__ . '/config-utils.php';

// Find config.env by searching parent directories
$configPath = findConfigPath();
$configFound = ($configPath !== null);
$configVersion = 0;

if ($configFound) {
    // Use file modification time as version indicator
    $mtime = @filemtime($configPath);
    if ($mtime !== false) {
        $configVersion = $mtime;
    }
}

// Return the version (mtime) so client can detect changes
echo json_encode([
    'ok' => true,
    'version' => $configVersion,
    'found' => $configFound,
]);
