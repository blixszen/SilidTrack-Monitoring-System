<?php
require_once __DIR__ . '/../includes/auth.php';
require_student();
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false], 405);

$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$pk = current_student_pk();

$stmt = $db->prepare('SELECT password_hash FROM students WHERE student_pk = ?');
$stmt->bind_param('i', $pk);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row || !password_verify($current, $row['password_hash'])) {
    json_response(['ok' => false, 'error' => 'Current password is incorrect.'], 403);
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $db->prepare('UPDATE students SET password_hash = ? WHERE student_pk = ?');
$stmt->bind_param('si', $hash, $pk);
$stmt->execute();

json_response(['ok' => true]);
