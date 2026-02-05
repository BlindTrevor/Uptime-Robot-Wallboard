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

// Function to find config.env by traversing up parent directories
function findConfigPath() {
    $currentDir = __DIR__;
    $maxLevels = 10; // Safety limit to prevent infinite loops
    
    // Start with current directory
    $testPaths = [
        $currentDir . '/config.env'
    ];
    
    // Add parent directories
    $testPath = $currentDir;
    for ($i = 0; $i < $maxLevels; $i++) {
        $parentPath = dirname($testPath);
        
        // Stop if we've reached root or can't go further
        if ($parentPath === $testPath || $parentPath === '/') {
            break;
        }
        
        $testPaths[] = $parentPath . '/config.env';
        $testPath = $parentPath;
    }
    
    // Check each path and return the first one that exists
    foreach ($testPaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return null;
}

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
