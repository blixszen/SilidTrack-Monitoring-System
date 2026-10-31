<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$db = get_db();
$action = $_GET['action'] ?? '';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $full_name  = trim($_POST['full_name'] ?? '');
    $program    = trim($_POST['program'] ?? '');
    $section    = trim($_POST['section'] ?? '');
    $rfid_uid   = trim($_POST['rfid_uid'] ?? '') ?: null;
    $password   = $_POST['password'] ?? '';

    if (!$student_id || !$full_name || !$password) {
        json_response(['ok' => false, 'error' => 'Student ID, Full Name and Password are required.'], 400);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO students (student_id, full_name, program, section, rfid_uid, password_hash) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssss', $student_id, $full_name, $program, $section, $rfid_uid, $hash);

    try {
        $stmt->execute();
        json_response(['ok' => true, 'student_pk' => $stmt->insert_id]);
    } catch (mysqli_sql_exception $e) {
        json_response(['ok' => false, 'error' => 'Student ID or RFID UID already exists.'], 409);
    }
}

if ($action === 'reset_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    if (!$student_id || !$new_password) {
        json_response(['ok' => false, 'error' => 'Missing fields.'], 400);
    }
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('UPDATE students SET password_hash = ? WHERE student_id = ?');
    $stmt->bind_param('ss', $hash, $student_id);
    $stmt->execute();
    json_response(['ok' => true]);
}

if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $db->query('SELECT student_id, full_name, program, section FROM students WHERE is_active = 1 ORDER BY full_name')->fetch_all(MYSQLI_ASSOC);
    json_response(['ok' => true, 'students' => $rows]);
}

json_response(['ok' => false, 'error' => 'Unknown action.'], 400);
