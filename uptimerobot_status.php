<?php

// API Documentation: https://uptimerobot.com/api/v3/

declare(strict_types=1);

// Block direct browsing - only allow access from application
// Check if request has a valid Referer header (indicates it's from our application)
$referer = $_SERVER['HTTP_REFERER'] ?? '';

// Use SERVER_NAME (not HTTP_HOST) to prevent header injection attacks
// SERVER_NAME comes from server config and cannot be manipulated by client
// Note: SERVER_NAME should match the hostname users access the site with
// For sites with both example.com and www.example.com, configure SERVER_NAME appropriately
$serverName = $_SERVER['SERVER_NAME'] ?? '';

// Parse referer URL and validate hostname matches server name
$refererValid = false;
if (!empty($referer) && !empty($serverName)) {
    $refererParts = parse_url($referer);
    // parse_url can return false, null, or array - only proceed if we got an array
    if (is_array($refererParts) && isset($refererParts['host'])) {
        // Compare hostnames exactly (case-insensitive) to prevent subdomain bypasses
        $refererValid = strcasecmp($refererParts['host'], $serverName) === 0;
    }
}

// If referer validation fails, return 403 Forbidden with no output
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

// --- CONFIG ---
// Load shared configuration utilities
require_once __DIR__ . '/config-utils.php';

// Load all configuration from unified config.env file
// Searches parent directories until config is found
$configPath = findConfigPath();

$TOKEN = '';
$WALLBOARD_CONFIG = [
    'title' => '',
    'logo' => '',
];

// Default configuration values
$CONFIG = [
    'showProblemsOnly' => false,
    'showPausedDevices' => false,
    'refreshRate' => 20,
    'configCheckRate' => 5,
    'allowQueryOverride' => true,
    'theme' => 'dark',
    'autoFullscreen' => false,
    'showTags' => true,
    'rateLimitWarningThreshold' => 3,
    'eventViewerDefault' => 'hidden',
    'eventLoggingMode' => 'circular',
    'eventLoggingMaxEvents' => 1000,
    'eventViewerItemsPerPage' => 50,
    'recentEventWindowMinutes' => 60,
];

if ($configPath !== null) {
    $parsed = parseEnvFile($configPath, [
        'UPTIMEROBOT_API_TOKEN', 
        'WALLBOARD_TITLE', 
        'WALLBOARD_LOGO',
        'SHOW_PROBLEMS_ONLY',
        'SHOW_PAUSED_DEVICES',
        'REFRESH_RATE',
        'CONFIG_CHECK_RATE',
        'ALLOW_QUERY_OVERRIDE',
        'THEME',
        'AUTO_FULLSCREEN',
        'SHOW_TAGS',
        'TAG_COLORS',
        'RATE_LIMIT_WARNING_THRESHOLD',
        'EVENT_VIEWER_DEFAULT',
        'EVENT_LOGGING_MODE',
        'EVENT_LOGGING_MAX_EVENTS',
        'EVENT_VIEWER_ITEMS_PER_PAGE',
        'RECENT_EVENT_WINDOW_MINUTES',
        'EVENT_TYPE_FILTER_ENABLED',
        'EVENT_TYPE_FILTER_DEFAULT_DOWN',
        'EVENT_TYPE_FILTER_DEFAULT_UP',
        'EVENT_TYPE_FILTER_DEFAULT_PAUSED',
        'EVENT_TYPE_FILTER_DEFAULT_ERROR'
    ]);
    
    // Load API token
    if (isset($parsed['UPTIMEROBOT_API_TOKEN'])) {
        $TOKEN = $parsed['UPTIMEROBOT_API_TOKEN'];
    }
    
    // Load wallboard title
    if (isset($parsed['WALLBOARD_TITLE'])) {
        // Sanitize title to prevent XSS
        $WALLBOARD_CONFIG['title'] = htmlspecialchars($parsed['WALLBOARD_TITLE'], ENT_QUOTES, 'UTF-8');
    }
    
    // Load wallboard logo
    if (isset($parsed['WALLBOARD_LOGO']) && !empty($parsed['WALLBOARD_LOGO'])) {
        $logo = $parsed['WALLBOARD_LOGO'];
        // Validate logo path/URL
        // Allow: relative paths ending in image extensions, absolute HTTP(S) URLs, data URIs
        // Disallow: file:// URIs, javascript: URIs, path traversal with ../
        $isValidUrl = preg_match('#^https?://.+\.(png|jpg|jpeg|gif|svg|webp)$#i', $logo);
        $isValidPath = preg_match('#^[a-zA-Z0-9_/.-]+\.(png|jpg|jpeg|gif|svg|webp)$#i', $logo) && strpos($logo, '..') === false;
        // Data URIs are limited to 100KB to prevent abuse
        $isDataUri = preg_match('#^data:image/(png|jpg|jpeg|gif|svg\+xml|webp);base64,#i', $logo) && strlen($logo) < 102400;
        
        if ($isValidUrl || $isValidPath || $isDataUri) {
            $WALLBOARD_CONFIG['logo'] = htmlspecialchars($logo, ENT_QUOTES, 'UTF-8');
        }
        // If validation fails, logo remains empty (safe default)
    }
    
    // Load display options
    if (isset($parsed['SHOW_PROBLEMS_ONLY'])) {
        $CONFIG['showProblemsOnly'] = filter_var($parsed['SHOW_PROBLEMS_ONLY'], FILTER_VALIDATE_BOOLEAN);
    }
    
    // Load show paused devices option
    if (isset($parsed['SHOW_PAUSED_DEVICES'])) {
        $CONFIG['showPausedDevices'] = filter_var($parsed['SHOW_PAUSED_DEVICES'], FILTER_VALIDATE_BOOLEAN);
    }
    
    // Load refresh rate (minimum 10 seconds to prevent API abuse)
    if (isset($parsed['REFRESH_RATE']) && is_numeric($parsed['REFRESH_RATE'])) {
        $CONFIG['refreshRate'] = max(10, (int)$parsed['REFRESH_RATE']);
    }
    
    // Load config check rate (minimum 1 second)
    if (isset($parsed['CONFIG_CHECK_RATE']) && is_numeric($parsed['CONFIG_CHECK_RATE'])) {
        $CONFIG['configCheckRate'] = max(1, (int)$parsed['CONFIG_CHECK_RATE']);
    }
    
    // Load query override setting
    if (isset($parsed['ALLOW_QUERY_OVERRIDE'])) {
        $CONFIG['allowQueryOverride'] = filter_var($parsed['ALLOW_QUERY_OVERRIDE'], FILTER_VALIDATE_BOOLEAN);
    }
    
    // Load theme setting
    if (isset($parsed['THEME'])) {
        $theme = strtolower($parsed['THEME']);
        if (in_array($theme, ['dark', 'light', 'auto'], true)) {
            $CONFIG['theme'] = $theme;
        }
    }
    
    // Load auto fullscreen setting
    if (isset($parsed['AUTO_FULLSCREEN'])) {
        $CONFIG['autoFullscreen'] = filter_var($parsed['AUTO_FULLSCREEN'], FILTER_VALIDATE_BOOLEAN);
    }
    
    // Load show tags setting
    if (isset($parsed['SHOW_TAGS'])) {
        $CONFIG['showTags'] = filter_var($parsed['SHOW_TAGS'], FILTER_VALIDATE_BOOLEAN);
    }
    
    // Load tag colors configuration
    if (isset($parsed['TAG_COLORS']) && !empty($parsed['TAG_COLORS'])) {
        $tagColorsJson = $parsed['TAG_COLORS'];
        // Attempt to decode JSON
        $tagColorsData = @json_decode($tagColorsJson, true);
        
        // Validate the decoded data structure
        if (is_array($tagColorsData)) {
            $validTagColors = [];
            
            // Color validation regex: allows hex codes, CSS color names, and rgb/hsl formats
            $colorValidationPattern = '/^(#[0-9a-fA-F]{3,8}|[a-zA-Z]+|rgba?\(.*\)|hsla?\(.*\))$/';
            
            // Validate acceptable colors array
            if (isset($tagColorsData['acceptable']) && is_array($tagColorsData['acceptable'])) {
                $acceptableColors = [];
                foreach ($tagColorsData['acceptable'] as $color) {
                    if (is_string($color) && !empty($color)) {
                        // Sanitize color value - allow hex codes, color names, and rgb/hsl
                        $sanitizedColor = trim($color);
                        // Basic validation: must start with # (hex) or be alphanumeric/rgb/hsl
                        if (preg_match($colorValidationPattern, $sanitizedColor)) {
                            $acceptableColors[] = $sanitizedColor;
                        }
                    }
                }
                if (!empty($acceptableColors)) {
                    $validTagColors['acceptable'] = $acceptableColors;
                }
            }
            
            // Validate tag-specific colors mapping
            if (isset($tagColorsData['tags']) && is_array($tagColorsData['tags'])) {
                $tagMapping = [];
                foreach ($tagColorsData['tags'] as $tagName => $color) {
                    if (is_string($tagName) && is_string($color) && !empty($tagName) && !empty($color)) {
                        $sanitizedTagName = trim($tagName);
                        $sanitizedColor = trim($color);
                        // Basic validation for color
                        if (preg_match($colorValidationPattern, $sanitizedColor)) {
                            $tagMapping[$sanitizedTagName] = $sanitizedColor;
                        }
                    }
                }
                if (!empty($tagMapping)) {
                    $validTagColors['tags'] = $tagMapping;
                }
            }
            
            // Only set if we have valid configuration
            if (!empty($validTagColors)) {
                $CONFIG['tagColors'] = $validTagColors;
            }
        }
    }
    
    // Load rate limit warning threshold (minimum 1)
    if (isset($parsed['RATE_LIMIT_WARNING_THRESHOLD']) && is_numeric($parsed['RATE_LIMIT_WARNING_THRESHOLD'])) {
        $CONFIG['rateLimitWarningThreshold'] = max(1, (int)$parsed['RATE_LIMIT_WARNING_THRESHOLD']);
    }
    
    // Load event viewer default state
    if (isset($parsed['EVENT_VIEWER_DEFAULT'])) {
        $viewerDefault = strtolower($parsed['EVENT_VIEWER_DEFAULT']);
        if (in_array($viewerDefault, ['visible', 'hidden', 'disabled'], true)) {
            $CONFIG['eventViewerDefault'] = $viewerDefault;
        }
    }
    
    // Load event logging mode
    if (isset($parsed['EVENT_LOGGING_MODE'])) {
        $loggingMode = strtolower($parsed['EVENT_LOGGING_MODE']);
        if (in_array($loggingMode, ['circular', 'forever'], true)) {
            $CONFIG['eventLoggingMode'] = $loggingMode;
        }
    }
    
    // Load event logging max events (minimum 10)
    if (isset($parsed['EVENT_LOGGING_MAX_EVENTS']) && is_numeric($parsed['EVENT_LOGGING_MAX_EVENTS'])) {
        $CONFIG['eventLoggingMaxEvents'] = max(10, (int)$parsed['EVENT_LOGGING_MAX_EVENTS']);
    }
    
    // Load event viewer items per page
    if (isset($parsed['EVENT_VIEWER_ITEMS_PER_PAGE'])) {
        $itemsPerPage = strtolower(trim($parsed['EVENT_VIEWER_ITEMS_PER_PAGE']));
        if ($itemsPerPage === 'all') {
            $CONFIG['eventViewerItemsPerPage'] = 'all';
        } elseif (is_numeric($itemsPerPage)) {
            $CONFIG['eventViewerItemsPerPage'] = max(10, (int)$itemsPerPage);
        }
    }
    
    // Load recent event window minutes
    if (isset($parsed['RECENT_EVENT_WINDOW_MINUTES']) && is_numeric($parsed['RECENT_EVENT_WINDOW_MINUTES'])) {
        $value = (int)$parsed['RECENT_EVENT_WINDOW_MINUTES'];
        $CONFIG['recentEventWindowMinutes'] = max(1, $value);
    }
    
    // Load event type filter enabled
    if (isset($parsed['EVENT_TYPE_FILTER_ENABLED'])) {
        $value = strtolower(trim($parsed['EVENT_TYPE_FILTER_ENABLED']));
        $CONFIG['eventTypeFilterEnabled'] = ($value === 'true' || $value === '1');
    }
    
    // Load event type filter default states
    if (isset($parsed['EVENT_TYPE_FILTER_DEFAULT_DOWN'])) {
        $value = strtolower(trim($parsed['EVENT_TYPE_FILTER_DEFAULT_DOWN']));
        $CONFIG['eventTypeFilterDefaultDown'] = ($value === 'true' || $value === '1');
    }
    if (isset($parsed['EVENT_TYPE_FILTER_DEFAULT_UP'])) {
        $value = strtolower(trim($parsed['EVENT_TYPE_FILTER_DEFAULT_UP']));
        $CONFIG['eventTypeFilterDefaultUp'] = ($value === 'true' || $value === '1');
    }
    if (isset($parsed['EVENT_TYPE_FILTER_DEFAULT_PAUSED'])) {
        $value = strtolower(trim($parsed['EVENT_TYPE_FILTER_DEFAULT_PAUSED']));
        $CONFIG['eventTypeFilterDefaultPaused'] = ($value === 'true' || $value === '1');
    }
    if (isset($parsed['EVENT_TYPE_FILTER_DEFAULT_ERROR'])) {
        $value = strtolower(trim($parsed['EVENT_TYPE_FILTER_DEFAULT_ERROR']));
        $CONFIG['eventTypeFilterDefaultError'] = ($value === 'true' || $value === '1');
    }
}

// Backend filter: only_problems query parameter
// This is sent by the frontend when user toggles "Show Only Problems" button
// It filters data at the backend to reduce data transfer
// This is different from the showProblemsOnly config which sets the initial state
$onlyProblems = isset($_GET['only_problems']) && $_GET['only_problems'] === '1';

// Allow query parameter to override showPausedDevices config
// This enables runtime control via URL like ?showPausedDevices=true
if (isset($_GET['showPausedDevices'])) {
    $queryShowPaused = $_GET['showPausedDevices'];
    if ($queryShowPaused === 'true' || $queryShowPaused === '1') {
        $CONFIG['showPausedDevices'] = true;
    } elseif ($queryShowPaused === 'false' || $queryShowPaused === '0') {
        $CONFIG['showPausedDevices'] = false;
    }
}

if (!$TOKEN) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Missing UPTIMEROBOT_API_TOKEN. Please create config.env file with UPTIMEROBOT_API_TOKEN=your-key']);
    exit;
}

$API_BASE = 'https://api.uptimerobot.com/v3';
$url = $API_BASE . '/monitors?page_size=100';

// Variable to capture response headers
$responseHeaders = [];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => false, // Don't include headers in the response body
    CURLOPT_HEADERFUNCTION => function($curl, $headerLine) use (&$responseHeaders) {
        $len = strlen($headerLine);
        $headerParts = explode(':', $headerLine, 2);
        if (count($headerParts) < 2) { // ignore invalid headers
            return $len;
        }
        $responseHeaders[strtolower(trim($headerParts[0]))] = trim($headerParts[1]);
        return $len;
    },
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

// Parse rate limit headers
$rateLimit = [
    'limit' => null,
    'remaining' => null,
    'reset' => null,
];

// Check for standard rate limit headers (case-insensitive)
if (isset($responseHeaders['x-ratelimit-limit'])) {
    $rateLimit['limit'] = (int)$responseHeaders['x-ratelimit-limit'];
}
if (isset($responseHeaders['x-ratelimit-remaining'])) {
    $rateLimit['remaining'] = (int)$responseHeaders['x-ratelimit-remaining'];
}
if (isset($responseHeaders['x-ratelimit-reset'])) {
    $rateLimit['reset'] = (int)$responseHeaders['x-ratelimit-reset'];
}

// Log rate limit information if quota is low or if 429 error occurred
$shouldLogRateLimit = false;
$rateLimitLogMessage = '';

if ($httpCode === 429) {
    $shouldLogRateLimit = true;
    $rateLimitLogMessage = 'HTTP 429 Rate Limit Exceeded';
} elseif ($rateLimit['remaining'] !== null && $rateLimit['remaining'] <= $CONFIG['rateLimitWarningThreshold']) {
    $shouldLogRateLimit = true;
    $rateLimitLogMessage = 'Rate Limit Warning - Low Quota';
}

if ($shouldLogRateLimit) {
    $logEntry = sprintf(
        "[%s] %s - Limit: %s, Remaining: %s, Reset: %s (HTTP %d)\n",
        date('Y-m-d H:i:s'),
        $rateLimitLogMessage,
        $rateLimit['limit'] !== null ? $rateLimit['limit'] : 'unknown',
        $rateLimit['remaining'] !== null ? $rateLimit['remaining'] : 'unknown',
        $rateLimit['reset'] !== null ? date('Y-m-d H:i:s', $rateLimit['reset']) : 'unknown',
        $httpCode
    );
    error_log($logEntry, 3, __DIR__ . '/uptime_errors.log');
}

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

// Keep original unfiltered list for event detection
$allMonitors = $monitors;

// Count paused devices before any filtering (for display when hidden)
$pausedCount = count(array_filter($monitors, function ($m) {
    return strtolower((string)($m['status'] ?? 'unknown')) === 'paused';
}));

if ($onlyProblems) {
    $monitors = array_values(array_filter($monitors, function ($m) {
        return strtolower((string)($m['status'] ?? 'unknown')) !== 'up';
    }));
}

// Filter out paused devices if showPausedDevices is false
if (!$CONFIG['showPausedDevices']) {
    $monitors = array_values(array_filter($monitors, function ($m) {
        return strtolower((string)($m['status'] ?? 'unknown')) !== 'paused';
    }));
}

// Normalize fields for your wallboard
$nowUtc = (new DateTime('now', new DateTimeZone('UTC')))->format(DateTime::ATOM);

// Transformation function to normalize monitor data
$transformMonitor = function ($m) {
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
};

$transformed = array_map($transformMonitor, $monitors);
$allMonitorsTransformed = array_map($transformMonitor, $allMonitors);

echo json_encode([
    'ok' => true,
    'fetched_at_utc' => $nowUtc,
    'count' => count($transformed),
    'paused_count' => $pausedCount,
    'monitors' => $transformed,
    'all_monitors' => $allMonitorsTransformed,
    'meta' => $data['meta'] ?? new stdClass(),
    'rateLimit' => $rateLimit,
    'config' => [
        'title' => $WALLBOARD_CONFIG['title'],
        'logo' => $WALLBOARD_CONFIG['logo'],
        'showProblemsOnly' => $CONFIG['showProblemsOnly'],
        'showPausedDevices' => $CONFIG['showPausedDevices'],
        'refreshRate' => $CONFIG['refreshRate'],
        'configCheckRate' => $CONFIG['configCheckRate'],
        'allowQueryOverride' => $CONFIG['allowQueryOverride'],
        'theme' => $CONFIG['theme'],
        'showTags' => $CONFIG['showTags'],
        'tagColors' => $CONFIG['tagColors'] ?? null,
        'rateLimitWarningThreshold' => $CONFIG['rateLimitWarningThreshold'],
        'eventViewerDefault' => $CONFIG['eventViewerDefault'],
        'eventLoggingMode' => $CONFIG['eventLoggingMode'],
        'eventLoggingMaxEvents' => $CONFIG['eventLoggingMaxEvents'],
        'eventViewerItemsPerPage' => $CONFIG['eventViewerItemsPerPage'],
        'recentEventWindowMinutes' => $CONFIG['recentEventWindowMinutes'] ?? 60,
        'eventTypeFilterEnabled' => $CONFIG['eventTypeFilterEnabled'] ?? true,
        'eventTypeFilterDefaultDown' => $CONFIG['eventTypeFilterDefaultDown'] ?? true,
        'eventTypeFilterDefaultUp' => $CONFIG['eventTypeFilterDefaultUp'] ?? true,
        'eventTypeFilterDefaultPaused' => $CONFIG['eventTypeFilterDefaultPaused'] ?? true,
        'eventTypeFilterDefaultError' => $CONFIG['eventTypeFilterDefaultError'] ?? true,
    ],
], JSON_UNESCAPED_SLASHES);
