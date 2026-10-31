<?php
/**
 * SilidTrack - Hardware Scan Endpoint
 * Called by the ESP32 over HTTP POST whenever a student taps an RFID card.
 *
 * Expected JSON body: { "uid": "A1B2C3D4", "device_key": "YOUR_DEVICE_KEY" }
 *
 * Response JSON: { "ok": true, "status": "Present", "student_name": "...", "beep": "success" }
 *                { "ok": false, "error": "Unknown card", "beep": "error" }
 */
require_once __DIR__ . '/../config/db.php';

// Simple shared-secret device auth (replace with something stronger for production).
const DEVICE_KEY = 'silidtrack-esp32-key-change-me';

// Attendance policy: students scanning after this cutoff are marked Late.
const LATE_CUTOFF = '09:00:00';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || ($input['device_key'] ?? '') !== DEVICE_KEY) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized device', 'beep' => 'error']);
    exit;
}

$uid = trim($input['uid'] ?? '');
if (!$uid) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing UID', 'beep' => 'error']);
    exit;
}

$db = get_db();

$stmt = $db->prepare('SELECT student_pk, student_id, full_name FROM students WHERE rfid_uid = ? AND is_active = 1');
$stmt->bind_param('s', $uid);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    echo json_encode(['ok' => false, 'error' => 'Unregistered card', 'beep' => 'error']);
    exit;
}

$now = new DateTime('now', new DateTimeZone('Asia/Manila'));
$today = $now->format('Y-m-d');
$time = $now->format('H:i:s');
$status = $time > LATE_CUTOFF ? 'Late' : 'Present';

// Upsert: one attendance row per student per day. Re-tap keeps the earliest scan.
$stmt = $db->prepare('
    INSERT INTO attendance (student_pk, scan_date, scan_time, status, id_detected)
    VALUES (?, ?, ?, ?, "Yes")
    ON DUPLICATE KEY UPDATE
        scan_time = IF(is_override = 0, VALUES(scan_time), scan_time),
        status    = IF(is_override = 0, VALUES(status), status),
        id_detected = "Yes"
');
$stmt->bind_param('isss', $student['student_pk'], $today, $time, $status);
$stmt->execute();

echo json_encode([
    'ok' => true,
    'status' => $status,
    'student_name' => $student['full_name'],
    'student_id' => $student['student_id'],
    'beep' => 'success',
]);
