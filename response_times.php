<?php

// API Documentation: https://uptimerobot.com/api/v3/#get-/monitors/-id-/stats/response-time

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

// Load configuration
require_once __DIR__ . '/config-utils.php';

$configPath = findConfigPath();
$TOKEN = '';

if ($configPath !== null) {
    $parsed = parseEnvFile($configPath, ['UPTIMEROBOT_API_TOKEN']);
    if (isset($parsed['UPTIMEROBOT_API_TOKEN'])) {
        $TOKEN = $parsed['UPTIMEROBOT_API_TOKEN'];
    }
}

if (!$TOKEN) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Missing UPTIMEROBOT_API_TOKEN']);
    exit;
}

// Get monitor ID from query parameter
$monitorId = $_GET['monitor_id'] ?? '';

if (empty($monitorId) || !is_numeric($monitorId)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing or invalid monitor_id parameter']);
    exit;
}

// Cache configuration
$cacheDir = sys_get_temp_dir() . '/uptimerobot_cache';
$cacheFile = $cacheDir . '/response_time_' . $monitorId . '.json';
$cacheDuration = 300; // 5 minutes cache

// Create cache directory if it doesn't exist
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0700, true);
}

// Check cache
$useCache = false;
if (file_exists($cacheFile)) {
    $cacheAge = time() - filemtime($cacheFile);
    if ($cacheAge < $cacheDuration) {
        $cachedData = @file_get_contents($cacheFile);
        if ($cachedData !== false) {
            $decoded = @json_decode($cachedData, true);
            if (is_array($decoded)) {
                echo $cachedData;
                exit;
            }
        }
    }
}

// Calculate time range for last hour
$to = time();
$from = $to - 3600; // 1 hour ago

$API_BASE = 'https://api.uptimerobot.com/v3';
$url = $API_BASE . '/monitors/' . urlencode($monitorId) . '/stats/response-time';
$url .= '?from=' . urlencode(gmdate('Y-m-d\TH:i:s\Z', $from));
$url .= '&to=' . urlencode(gmdate('Y-m-d\TH:i:s\Z', $to));
$url .= '&includeTimeSeries=true';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Authorization: Bearer ' . $TOKEN,
    ],
    CURLOPT_TIMEOUT => 10,
]);

$response = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlErr) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'cURL error: ' . $curlErr]);
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    // Return error but don't fail completely - some monitors may not have response time data
    $result = json_encode(['ok' => false, 'error' => 'HTTP ' . $httpCode, 'has_data' => false]);
    echo $result;
    exit;
}

$data = json_decode($response, true);
if (!is_array($data)) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON from UptimeRobot v3']);
    exit;
}

// Return the response time data
$result = [
    'ok' => true,
    'monitor_id' => $monitorId,
    'has_data' => isset($data['time_series']) && is_array($data['time_series']) && count($data['time_series']) > 0,
    'data' => $data
];

$resultJson = json_encode($result);

// Cache the result
@file_put_contents($cacheFile, $resultJson, LOCK_EX);

echo $resultJson;
