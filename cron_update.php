#!/usr/bin/env php
<?php

/**
 * Wallboard Cache Update Script
 *
 * Fetches wallboard monitor status from the UptimeRobot API and stores the
 * result in a flat cache file. The cache file is named after the SHA512 hash
 * of the API key, so no sensitive credentials are ever written to disk.
 *
 * All displays using the same API key will read from this shared cache file,
 * ensuring they always show identical, up-to-date data without racing each
 * other or hitting the API independently.
 *
 * Usage:
 *   php cron_update.php
 *
 * Recommended crontab entry (runs every minute):
 *   * * * * * /usr/bin/php /var/www/html/status/cron_update.php >> /var/log/wallboard_cron.log 2>&1
 *
 * To match a custom REFRESH_RATE you can still use every-minute cron, because
 * the wallboard frontend reads from the cache file rather than the cron schedule.
 *
 * Exit codes:
 *   0  Success
 *   1  Fatal error (missing config, API failure, write failure)
 */

declare(strict_types=1);

// Only allow execution from the command line
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo json_encode(['error' => 'This script must be run from the command line.']);
    exit(1);
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/uptime_errors.log');

// Load shared configuration utilities
require_once __DIR__ . '/config-utils.php';

// --- CONFIG LOADING ---
$configPath = findConfigPath();

$TOKEN = '';
$CONFIG = [
    'showPausedDevices'            => false,
    'refreshRate'                  => 20,
    'rateLimitWarningThreshold'    => 3,
];

if ($configPath !== null) {
    $parsed = parseEnvFile($configPath, [
        'UPTIMEROBOT_API_TOKEN',
        'SHOW_PAUSED_DEVICES',
        'REFRESH_RATE',
        'RATE_LIMIT_WARNING_THRESHOLD',
    ]);

    if (isset($parsed['UPTIMEROBOT_API_TOKEN'])) {
        $TOKEN = $parsed['UPTIMEROBOT_API_TOKEN'];
    }
    if (isset($parsed['SHOW_PAUSED_DEVICES'])) {
        $CONFIG['showPausedDevices'] = filter_var($parsed['SHOW_PAUSED_DEVICES'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($parsed['REFRESH_RATE']) && is_numeric($parsed['REFRESH_RATE'])) {
        $CONFIG['refreshRate'] = max(10, (int)$parsed['REFRESH_RATE']);
    }
    if (isset($parsed['RATE_LIMIT_WARNING_THRESHOLD']) && is_numeric($parsed['RATE_LIMIT_WARNING_THRESHOLD'])) {
        $CONFIG['rateLimitWarningThreshold'] = max(1, (int)$parsed['RATE_LIMIT_WARNING_THRESHOLD']);
    }
}

if (!$TOKEN) {
    $configFile = $configPath ?? 'config.env (not found in any parent directory)';
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . "] ERROR: Missing UPTIMEROBOT_API_TOKEN in $configFile\n");
    exit(1);
}

// --- CACHE SETUP ---
// The cache filename is the SHA512 hash of the API token.
// No plain API key is ever written to disk.
$cacheHash = hash('sha512', $TOKEN);
$cacheDir  = __DIR__ . '/cache/wallboard';
$cacheFile = $cacheDir . '/' . $cacheHash . '.json';

if (!ensureCacheDir($cacheDir)) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . "] ERROR: Failed to create or access cache directory: $cacheDir\n");
    exit(1);
}

// --- API FETCH ---
// API Documentation: https://uptimerobot.com/api/v3/
$API_BASE = 'https://api.uptimerobot.com/v3';
$url = $API_BASE . '/monitors?page_size=100';

$responseHeaders = [];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => false,
    CURLOPT_HEADERFUNCTION => function ($curl, $headerLine) use (&$responseHeaders) {
        $len = strlen($headerLine);
        $headerParts = explode(':', $headerLine, 2);
        if (count($headerParts) < 2) {
            return $len;
        }
        $responseHeaders[strtolower(trim($headerParts[0]))] = trim($headerParts[1]);
        return $len;
    },
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        'Authorization: Bearer ' . $TOKEN,
    ],
    CURLOPT_TIMEOUT        => 15,
]);

$response = curl_exec($ch);
$curlErr  = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Parse rate-limit headers
$rateLimit = ['limit' => null, 'remaining' => null, 'reset' => null];
if (isset($responseHeaders['x-ratelimit-limit'])) {
    $rateLimit['limit'] = (int)$responseHeaders['x-ratelimit-limit'];
}
if (isset($responseHeaders['x-ratelimit-remaining'])) {
    $rateLimit['remaining'] = (int)$responseHeaders['x-ratelimit-remaining'];
}
if (isset($responseHeaders['x-ratelimit-reset'])) {
    $rateLimit['reset'] = (int)$responseHeaders['x-ratelimit-reset'];
}

// Log rate-limit warnings
if ($httpCode === 429) {
    $logEntry = sprintf(
        "[%s] HTTP 429 Rate Limit Exceeded - Limit: %s, Remaining: %s, Reset: %s\n",
        date('Y-m-d H:i:s'),
        $rateLimit['limit'] !== null ? $rateLimit['limit'] : 'unknown',
        $rateLimit['remaining'] !== null ? $rateLimit['remaining'] : 'unknown',
        $rateLimit['reset'] !== null ? date('Y-m-d H:i:s', $rateLimit['reset']) : 'unknown'
    );
    error_log($logEntry, 3, __DIR__ . '/uptime_errors.log');
} elseif ($rateLimit['remaining'] !== null && $rateLimit['remaining'] <= $CONFIG['rateLimitWarningThreshold']) {
    $logEntry = sprintf(
        "[%s] Rate Limit Warning - Remaining: %s/%s, Reset: %s\n",
        date('Y-m-d H:i:s'),
        $rateLimit['remaining'],
        $rateLimit['limit'] !== null ? $rateLimit['limit'] : 'unknown',
        $rateLimit['reset'] !== null ? date('Y-m-d H:i:s', $rateLimit['reset']) : 'unknown'
    );
    error_log($logEntry, 3, __DIR__ . '/uptime_errors.log');
}

if ($curlErr) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . "] ERROR: cURL error: $curlErr\n");
    exit(1);
}

if ($httpCode < 200 || $httpCode >= 300) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . "] ERROR: HTTP $httpCode from UptimeRobot API\n");
    if ($httpCode === 429) {
        fwrite(STDERR, '[' . date('Y-m-d H:i:s') . "] WARN: Rate limited. Consider reducing cron frequency or increasing REFRESH_RATE.\n");
    }
    exit(1);
}

$data = json_decode($response, true);
if (!is_array($data)) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . "] ERROR: Invalid JSON response from UptimeRobot API\n");
    exit(1);
}

$monitors = $data['data'] ?? [];

$nowUtc = (new DateTime('now', new DateTimeZone('UTC')))->format(DateTime::ATOM);

// Count paused monitors (before any filtering)
$pausedCount = count(array_filter($monitors, function ($m) {
    return strtolower((string)($m['status'] ?? 'unknown')) === 'paused';
}));

// --- DATA TRANSFORMATION ---
// Mirrors the transformation in uptimerobot_status.php.
// All monitors are transformed without any filter so the cache stores
// the complete dataset; per-request filters are applied at serve time.
$transformMonitor = function ($m) {
    $status = strtolower((string)($m['status'] ?? 'unknown'));

    $lastCheck = null;
    if (isset($m['currentStateDuration']) && is_numeric($m['currentStateDuration'])) {
        $lastCheck = time() - (int)$m['currentStateDuration'];
    }

    $nextCheck = null;
    if (!empty($m['interval']) && is_numeric($m['interval'])) {
        $nextCheck = time() + (int)$m['interval'];
    }

    $lastDayUptimeRatio = null;

    if ($status === 'up' && !empty($m['lastIncident'])) {
        $incident       = $m['lastIncident'];
        $incidentStatus = strtolower((string)($incident['status'] ?? ''));

        if ($incidentStatus === 'resolved' && !empty($incident['startedAt']) && !empty($incident['duration'])) {
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
                $incidentResolved = $incidentStart + (int)$incident['duration'];
                $uptimeDuration   = time() - $incidentResolved;
                $totalDuration    = time() - $incidentStart;

                if ($totalDuration > 0 && $uptimeDuration >= 0) {
                    $lastDayUptimeRatio = ($uptimeDuration / $totalDuration) * 100;
                    $lastDayUptimeRatio = max(0, min(round($lastDayUptimeRatio, 2), 100));
                }
            }
        }
    }

    if ($lastDayUptimeRatio === null && !empty($m['lastDayUptimes']['histogram']) && is_array($m['lastDayUptimes']['histogram'])) {
        $uptimes = array_column($m['lastDayUptimes']['histogram'], 'uptime');
        $uptimes = array_filter($uptimes, 'is_numeric');
        if (!empty($uptimes)) {
            $lastDayUptimeRatio = round(array_sum($uptimes) / count($uptimes), 2);
            $lastDayUptimeRatio = max(0, min($lastDayUptimeRatio, 100));
        }
    }

    return [
        'id'                  => $m['id'] ?? null,
        'friendly_name'       => $m['friendlyName'] ?? '',
        'url'                 => $m['url'] ?? '',
        'type'                => $m['type'] ?? null,
        'interval'            => isset($m['interval']) ? (int)$m['interval'] : null,
        'status_code'         => null,
        'status'              => $status,
        'all_time_uptime_ratio' => $lastDayUptimeRatio,
        'custom_uptime_ratios'  => null,
        'last_check'          => $lastCheck,
        'next_check'          => $nextCheck,
        'recent_incident'     => $m['lastIncident'] ?? null,
        'logs'                => null,
        'alert_contacts'      => $m['assignedAlertContacts'] ?? null,
        'tags'                => $m['tags'] ?? [],
    ];
};

$allMonitorsTransformed = array_map($transformMonitor, $monitors);

// --- WRITE CACHE ---
// Store the unfiltered, transformed monitor list plus API metadata.
// Per-request filters (only_problems, showPausedDevices) are applied at
// serve time in uptimerobot_status.php, so every client gets a consistent
// view of the same underlying dataset.
$cachePayload = json_encode([
    'fetched_at_utc'           => $nowUtc,
    'all_monitors_transformed' => $allMonitorsTransformed,
    'paused_count'             => $pausedCount,
    'meta'                     => $data['meta'] ?? new stdClass(),
    'rateLimit'                => $rateLimit,
], JSON_UNESCAPED_SLASHES);

if ($cachePayload === false) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . "] ERROR: Failed to encode cache payload as JSON\n");
    exit(1);
}

// Write atomically with exclusive lock to prevent race conditions when
// multiple cron processes overlap.
$written = file_put_contents($cacheFile, $cachePayload, LOCK_EX);
if ($written === false) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . "] ERROR: Failed to write cache file: $cacheFile\n");
    exit(1);
}

$monitorCount = count($allMonitorsTransformed);
echo '[' . date('Y-m-d H:i:s') . "] OK: Cached $monitorCount monitors ($pausedCount paused) — hash " . substr($cacheHash, 0, 16) . "...\n";
exit(0);
