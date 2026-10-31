<?php
/**
 * SilidTrack - ESP32 Heartbeat Endpoint
 * Called by ESP32 periodically to indicate it's online
 */
require_once __DIR__ . '/../config/db.php';

const DEVICE_KEY = 'silidtrack-esp32-key-change-me';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || ($input['device_key'] ?? '') !== DEVICE_KEY) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized device']);
    exit;
}

$db = get_db();

// Store/update heartbeat timestamp
$stmt = $db->prepare('
    INSERT INTO app_settings (setting_key, setting_value) VALUES ("esp32_heartbeat", NOW())
    ON DUPLICATE KEY UPDATE setting_value = NOW()
');
$stmt->execute();

echo json_encode(['ok' => true, 'message' => 'Heartbeat received']);