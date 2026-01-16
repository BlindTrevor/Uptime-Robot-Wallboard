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

// Check for config.env file in both locations
$configPaths = [
    __DIR__ . '/../config.env',  // Outside webroot (recommended)
    __DIR__ . '/config.env',     // Current directory (fallback)
];

$configVersion = 0;
$configFound = false;

foreach ($configPaths as $configPath) {
    if (file_exists($configPath)) {
        $configFound = true;
        // Use file modification time as version indicator
        $mtime = @filemtime($configPath);
        if ($mtime !== false) {
            $configVersion = $mtime;
        }
        break;
    }
}

// Return the version (mtime) so client can detect changes
echo json_encode([
    'ok' => true,
    'version' => $configVersion,
    'found' => $configFound,
]);
