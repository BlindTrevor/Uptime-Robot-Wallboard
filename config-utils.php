<?php

/**
 * Shared configuration utilities
 * This file contains common functions used across the application
 * for configuration file discovery and parsing
 */

declare(strict_types=1);

/**
 * Find config.env by traversing up parent directories
 * 
 * @return string|null Path to config.env if found, null otherwise
 */
function findConfigPath(): ?string {
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
    // Use @ to suppress warnings from open_basedir restrictions
    foreach ($testPaths as $path) {
        if (@file_exists($path)) {
            return $path;
        }
    }
    
    return null;
}

/**
 * Parse environment file into key-value pairs
 * 
 * @param string $filePath Path to environment file
 * @param array $keys Optional array of specific keys to extract
 * @return array Associative array of configuration values
 */
function parseEnvFile(string $filePath, array $keys = []): array {
    $result = [];
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return $result;
    }
    
    // Use @ to suppress errors and prevent information disclosure
    $content = @file_get_contents($filePath);
    if ($content === false) {
        return $result;
    }
    
    $lines = explode("\n", $content);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments and empty lines
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            $parts = array_map('trim', explode('=', $line, 2));
            if (count($parts) === 2) {
                list($key, $value) = $parts;
                // If specific keys requested, only return those
                if (empty($keys) || in_array($key, $keys, true)) {
                    // Remove surrounding quotes if present
                    $result[$key] = trim($value, '"\'');
                }
            }
        }
    }
    return $result;
}

/**
 * Ensure a directory exists and is writable, creating it if necessary.
 *
 * @param string $dir      Absolute path to the directory
 * @param int    $mode     Directory permissions (default 0700)
 * @return bool  True if the directory exists and is writable, false otherwise
 */
function ensureCacheDir(string $dir, int $mode = 0700): bool {
    if (is_dir($dir)) {
        return is_writable($dir);
    }
    // If mkdir() fails because another process created the directory concurrently
    // (TOCTOU race), the trailing is_dir() check ensures we still return true
    // rather than propagating a spurious failure.
    return mkdir($dir, $mode, true) || is_dir($dir);
}
