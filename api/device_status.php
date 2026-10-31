<?php
/**
 * SilidTrack - ESP32 Device Status Endpoint
 * Returns the connection status of the ESP32 RFID scanner based on last heartbeat.
 */
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$db = get_db();

// Check if there's a recent scan from ESP32 (within last 30 seconds)
// OR a recent heartbeat (within last 60 seconds)
$stmt = $db->prepare("
    SELECT MAX(created_at) AS last_scan
    FROM attendance
    WHERE id_detected = 'Yes'
      AND created_at >= DATE_SUB(NOW(), INTERVAL 30 SECOND)
");
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$lastScan = $row['last_scan'] ?? null;

// Check heartbeat from app_settings
$stmt = $db->prepare("
    SELECT setting_value AS last_heartbeat
    FROM app_settings
    WHERE setting_key = 'esp32_heartbeat'
      AND setting_value >= DATE_SUB(NOW(), INTERVAL 60 SECOND)
");
$stmt->execute();
$hbRow = $stmt->get_result()->fetch_assoc();
$lastHeartbeat = $hbRow['last_heartbeat'] ?? null;

$isOnline = ($lastScan !== null) || ($lastHeartbeat !== null);
$lastSeen = $lastScan ?? $lastHeartbeat;

json_response([
    'ok' => true,
    'online' => $isOnline,
    'last_seen' => $lastSeen,
    'message' => $isOnline ? 'ESP32 RFID Online' : 'ESP32 RFID Offline'
]);