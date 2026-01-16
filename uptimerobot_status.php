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
// Request specific fields from API v3 - by default only basic fields are returned
$fields = [
    'id',
    'friendly_name',
    'url',
    'type',
    'interval',
    'status',
    'all_time_uptime_ratio',
    'custom_uptime_ratios',
    'last_check_at',
    'next_check_at',
    'tags',
    'alert_contacts'
];
$url = $API_BASE . '/monitors?page_size=100&fields=' . implode(',', $fields);

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

    // API v3 uses last_check_at and next_check_at (not last_check/next_check)
    $lastCheck = isset($m['last_check_at']) && (int)$m['last_check_at'] > 0 ? (int)$m['last_check_at'] : null;
    $nextCheck = isset($m['next_check_at']) && (int)$m['next_check_at'] > 0 ? (int)$m['next_check_at'] : null;

    return [
        'id' => $m['id'] ?? null,
        'friendly_name' => $m['friendly_name'] ?? ($m['name'] ?? ''),
        'url' => $m['url'] ?? ($m['hostname'] ?? ''),
        'type' => $m['type'] ?? null,
        'interval' => isset($m['interval']) ? (int)$m['interval'] : null,
        'status_code' => null,
        'status' => $status,
        'all_time_uptime_ratio' => $m['all_time_uptime_ratio'] ?? null,
        'custom_uptime_ratios' => $m['custom_uptime_ratios'] ?? null,
        'last_check' => $lastCheck,
        'next_check' => $nextCheck,
        'recent_incident' => null,
        'logs' => null,
        'alert_contacts' => $m['alert_contacts'] ?? null,
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
