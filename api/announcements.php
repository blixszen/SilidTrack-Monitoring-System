<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$db = get_db();
$action = $_GET['action'] ?? '';

if ($action === 'create') {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $fee_id = !empty($_POST['fee_id']) ? (int)$_POST['fee_id'] : null;
    if (!$title || !$message) json_response(['ok' => false, 'error' => 'Title and message required.'], 400);

    $admin_id = current_admin_id();
    $stmt = $db->prepare('INSERT INTO announcements (title, message, fee_id, created_by) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssii', $title, $message, $fee_id, $admin_id);
    $stmt->execute();
    json_response(['ok' => true, 'announcement_id' => $stmt->insert_id]);
}

if ($action === 'update') {
    $id = (int)($_POST['announcement_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if (!$id || !$title || !$message) json_response(['ok' => false, 'error' => 'Title and message required.'], 400);

    $stmt = $db->prepare('UPDATE announcements SET title = ?, message = ? WHERE announcement_id = ?');
    $stmt->bind_param('ssi', $title, $message, $id);
    $stmt->execute();
    json_response(['ok' => true]);
}

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare('DELETE FROM announcements WHERE announcement_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    json_response(['ok' => true]);
}

json_response(['ok' => false, 'error' => 'Unknown action.'], 400);
