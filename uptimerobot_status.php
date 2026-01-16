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
$TOKEN = trim(file_get_contents(__DIR__ . '/api_token.tok'));

$onlyProblems = isset($_GET['only_problems']) && $_GET['only_problems'] === '1';

if (!$TOKEN) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Missing UPTIMEROBOT_API_TOKEN']);
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

    // API v3 doesn't have last_check_at/next_check_at
    // Use lastIncident.startedAt if available, otherwise use createDateTime
    $lastCheck = null;
    if (!empty($m['lastIncident']['startedAt'])) {
        $lastCheck = is_numeric($m['lastIncident']['startedAt']) 
            ? (int)$m['lastIncident']['startedAt'] 
            : strtotime($m['lastIncident']['startedAt']);
    } elseif (!empty($m['createDateTime'])) {
        $lastCheck = is_numeric($m['createDateTime']) 
            ? (int)$m['createDateTime'] 
            : strtotime($m['createDateTime']);
    }
    
    // Calculate next check from interval if available
    $nextCheck = null;
    if ($lastCheck && !empty($m['interval'])) {
        $nextCheck = $lastCheck + (int)$m['interval'];
    }
    
    // Calculate uptime from lastDayUptimes if available
    $uptimeRatio = null;
    if (!empty($m['lastDayUptimes']['histogram']) && is_array($m['lastDayUptimes']['histogram'])) {
        $uptimes = array_column($m['lastDayUptimes']['histogram'], 'uptime');
        if (!empty($uptimes)) {
            $uptimeRatio = round(array_sum($uptimes) / count($uptimes), 2);
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
        'all_time_uptime_ratio' => $uptimeRatio,
        'custom_uptime_ratios' => null,
        'last_check' => $lastCheck,
        'next_check' => $nextCheck,
        'recent_incident' => $m['lastIncident'] ?? null,
        'logs' => null,
        'alert_contacts' => $m['assignedAlertContacts'] ?? null,
        'tags' => $m['tags'] ?? [],
    ];
}, $monitors);

echo json_encode([
    'ok' => true,
    'fetched_at_utc' => $nowUtc,
    'count' => count($transformed),
    'monitors' => $transformed,
    'meta' => $data['meta'] ?? new stdClass(),
], JSON_UNESCAPED_SLASHES);
