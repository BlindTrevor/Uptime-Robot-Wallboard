<?php

// API Documentation: https://uptimerobot.com/api/v3/

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/uptime_errors.log');

// --- CONFIG ---
// Helper function to parse environment files
function parseEnvFile($filePath, $keys = []) {
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

// Try loading environment variables from api_token.env file
// Checks outside webroot first (most secure), then fallback to current directory
$envPaths = [
    __DIR__ . '/../api_token.env',  // Outside webroot (recommended)
    __DIR__ . '/api_token.env',     // Current directory (fallback)
];

$TOKEN = '';
foreach ($envPaths as $envPath) {
    $parsed = parseEnvFile($envPath, ['UPTIMEROBOT_API_TOKEN']);
    if (isset($parsed['UPTIMEROBOT_API_TOKEN'])) {
        $TOKEN = $parsed['UPTIMEROBOT_API_TOKEN'];
        break;
    }
}

// Load wallboard configuration from config.env file
$configPaths = [
    __DIR__ . '/../config.env',  // Outside webroot (recommended)
    __DIR__ . '/config.env',     // Current directory (fallback)
];

$WALLBOARD_CONFIG = [
    'title' => '',
    'logo' => '',
];
foreach ($configPaths as $configPath) {
    $parsed = parseEnvFile($configPath, ['WALLBOARD_TITLE', 'WALLBOARD_LOGO']);
    if (!empty($parsed)) {
        if (isset($parsed['WALLBOARD_TITLE'])) {
            // Sanitize title to prevent XSS
            $WALLBOARD_CONFIG['title'] = htmlspecialchars($parsed['WALLBOARD_TITLE'], ENT_QUOTES, 'UTF-8');
        }
        if (isset($parsed['WALLBOARD_LOGO']) && !empty($parsed['WALLBOARD_LOGO'])) {
            $logo = $parsed['WALLBOARD_LOGO'];
            // Validate logo path/URL
            // Allow: relative paths ending in image extensions, absolute HTTP(S) URLs, data URIs
            // Disallow: file:// URIs, javascript: URIs, path traversal with ../
            $isValidUrl = preg_match('#^https?://.+\.(png|jpg|jpeg|gif|svg|webp)$#i', $logo);
            $isValidPath = preg_match('#^[a-zA-Z0-9_/.-]+\.(png|jpg|jpeg|gif|svg|webp)$#i', $logo) && strpos($logo, '..') === false;
            $isDataUri = preg_match('#^data:image/(png|jpg|jpeg|gif|svg\+xml|webp);base64,#i', $logo);
            
            if ($isValidUrl || $isValidPath || $isDataUri) {
                $WALLBOARD_CONFIG['logo'] = htmlspecialchars($logo, ENT_QUOTES, 'UTF-8');
            }
            // If validation fails, logo remains empty (safe default)
        }
        break;
    }
}

$onlyProblems = isset($_GET['only_problems']) && $_GET['only_problems'] === '1';

if (!$TOKEN) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Missing UPTIMEROBOT_API_TOKEN. Please create api_token.env file with UPTIMEROBOT_API_TOKEN=your-key']);
    exit;
}

$API_BASE = 'https://api.uptimerobot.com/v3';
$url = $API_BASE . '/monitors?page_size=100';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Authorization: Bearer ' . $TOKEN,
    ],
    CURLOPT_TIMEOUT => 15,
]);
$response = curl_exec($ch);
$curlErr  = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlErr) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'cURL error: ' . $curlErr]);
    exit;
}
if ($httpCode < 200 || $httpCode >= 300) {
    http_response_code($httpCode);
    echo json_encode(['ok' => false, 'error' => 'HTTP ' . $httpCode, 'raw' => $response]);
    exit;
}

$data = json_decode($response, true);
if (!is_array($data)) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON from UptimeRobot v3', 'raw' => $response]);
    exit;
}

$monitors = $data['data'] ?? [];
if ($onlyProblems) {
    $monitors = array_values(array_filter($monitors, function ($m) {
        return strtolower((string)($m['status'] ?? 'unknown')) !== 'up';
    }));
}

// Normalize fields for your wallboard
$nowUtc = (new DateTime('now', new DateTimeZone('UTC')))->format(DateTime::ATOM);
$transformed = array_map(function ($m) {
    $status = strtolower((string)($m['status'] ?? 'unknown'));

    // API v3 doesn't provide explicit last_check/next_check timestamps
    // Best approximation: use currentStateDuration to calculate when state changed
    $lastCheck = null;
    if (isset($m['currentStateDuration']) && is_numeric($m['currentStateDuration'])) {
        $lastCheck = time() - (int)$m['currentStateDuration'];
    }
    // Note: We don't fallback to createDateTime as it's misleading for "last check"
    
    // Calculate next check based on interval
    // Since API v3 doesn't provide actual check times, estimate next check
    $nextCheck = null;
    if (!empty($m['interval']) && is_numeric($m['interval'])) {
        // Simple estimate: current time + interval
        $nextCheck = time() + (int)$m['interval'];
    }
    
    // Calculate uptime from available data
    // Strategy: Use multiple data sources for best approximation
    $lastDayUptimeRatio = null;
    
    // Method 1: If monitor is UP and last incident was resolved, calculate uptime from incident resolution
    if ($status === 'up' && !empty($m['lastIncident'])) {
        $incident = $m['lastIncident'];
        $incidentStatus = strtolower((string)($incident['status'] ?? ''));
        
        if ($incidentStatus === 'resolved' && !empty($incident['startedAt']) && !empty($incident['duration'])) {
            // Parse startedAt timestamp
            $incidentStart = 0;
            if (is_numeric($incident['startedAt'])) {
                $incidentStart = (int)$incident['startedAt'];
            } else {
                $parsed = strtotime($incident['startedAt']);
                if ($parsed !== false) {
                    $incidentStart = $parsed;
                }
            }
            
            if ($incidentStart > 0 && is_numeric($incident['duration'])) {
                // Calculate when incident was resolved
                $incidentResolved = $incidentStart + (int)$incident['duration'];
                
                // Calculate time since incident was resolved (uptime period)
                $uptimeDuration = time() - $incidentResolved;
                
                // Calculate total time period (from incident start to now)
                $totalDuration = time() - $incidentStart;
                
                // Validate: both durations must be positive (prevent clock skew issues)
                if ($totalDuration > 0 && $uptimeDuration >= 0) {
                    // Uptime ratio = time since resolved / total time
                    $lastDayUptimeRatio = ($uptimeDuration / $totalDuration) * 100;
                    // Ensure ratio is within valid range [0, 100]
                    $lastDayUptimeRatio = max(0, min(round($lastDayUptimeRatio, 2), 100));
                }
            }
        }
    }
    
    // Method 2: Fallback to lastDayUptimes histogram if incident method didn't work
    if ($lastDayUptimeRatio === null && !empty($m['lastDayUptimes']['histogram']) && is_array($m['lastDayUptimes']['histogram'])) {
        $uptimes = array_column($m['lastDayUptimes']['histogram'], 'uptime');
        // Validate that we have numeric uptime values
        $uptimes = array_filter($uptimes, 'is_numeric');
        if (!empty($uptimes)) {
            // Average the uptime samples to get approximate daily uptime
            $lastDayUptimeRatio = round(array_sum($uptimes) / count($uptimes), 2);
            // Ensure ratio is within valid range [0, 100]
            $lastDayUptimeRatio = max(0, min($lastDayUptimeRatio, 100));
        }
    }

    return [
        'id' => $m['id'] ?? null,
        'friendly_name' => $m['friendlyName'] ?? '',
        'url' => $m['url'] ?? '',
        'type' => $m['type'] ?? null,
        'interval' => isset($m['interval']) ? (int)$m['interval'] : null,
        'status_code' => null,
        'status' => $status,
        // Note: API v3 doesn't provide all-time uptime, using last day uptime instead
        'all_time_uptime_ratio' => $lastDayUptimeRatio,
        'custom_uptime_ratios' => null,
        'last_check' => $lastCheck,
        'next_check' => $nextCheck,
        'recent_incident' => $m['lastIncident'] ?? null,
        'logs' => null,
        'alert_contacts' => $m['assignedAlertContacts'] ?? null,
        // Tags are passed as-is; formatTags() in index.html handles object-to-name conversion
        'tags' => $m['tags'] ?? [],
    ];
}, $monitors);

echo json_encode([
    'ok' => true,
    'fetched_at_utc' => $nowUtc,
    'count' => count($transformed),
    'monitors' => $transformed,
    'meta' => $data['meta'] ?? new stdClass(),
    'config' => [
        'title' => $WALLBOARD_CONFIG['title'],
        'logo' => $WALLBOARD_CONFIG['logo'],
    ],
], JSON_UNESCAPED_SLASHES);
