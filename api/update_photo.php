<?php
require_once __DIR__ . '/../includes/auth.php';
require_student();
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['photo']['tmp_name'])) {
    json_response(['ok' => false, 'error' => 'No photo uploaded.'], 400);
}

if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    json_response(['ok' => false, 'error' => 'Upload failed. Please try again.'], 400);
}

if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
    json_response(['ok' => false, 'error' => 'Image is too large (max 2MB).'], 400);
}

$pk = current_student_pk();
$ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
$allowed = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array(strtolower($ext), $allowed, true)) {
    json_response(['ok' => false, 'error' => 'Unsupported file type.'], 400);
}

$dir = __DIR__ . '/../assets/img';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$filename = 'student_' . $pk . '.' . $ext;
$dest = $dir . '/' . $filename;

if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
    json_response(['ok' => false, 'error' => 'Failed to save the image.'], 500);
}

$path = '/assets/img/' . $filename;
$stmt = $db->prepare('UPDATE students SET photo_path = ? WHERE student_pk = ?');
$stmt->bind_param('si', $path, $pk);
$stmt->execute();

$_SESSION['photo_path'] = $path;

json_response(['ok' => true, 'photo_path' => $path]);
