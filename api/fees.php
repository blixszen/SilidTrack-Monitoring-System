<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/settings.php';
$db = get_db();
$action = $_GET['action'] ?? '';

if ($action === 'create') {
    require_admin();
    $title = trim($_POST['title'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $due_date = $_POST['due_date'] ?? '';
    if (!$title || $amount <= 0 || !$due_date) {
        json_response(['ok' => false, 'error' => 'All fields are required.'], 400);
    }
    $admin_id = current_admin_id();
    $stmt = $db->prepare('INSERT INTO fees (title, amount, due_date, created_by) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('sdsi', $title, $amount, $due_date, $admin_id);
    $stmt->execute();
    json_response(['ok' => true, 'fee_id' => $stmt->insert_id]);
}

if ($action === 'update') {
    require_admin();
    $id = (int)($_POST['fee_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $due_date = $_POST['due_date'] ?? '';
    if (!$id || !$title || $amount <= 0 || !$due_date) {
        json_response(['ok' => false, 'error' => 'All fields are required.'], 400);
    }
    $stmt = $db->prepare('UPDATE fees SET title = ?, amount = ?, due_date = ?, is_active = 1 WHERE fee_id = ?');
    $stmt->bind_param('sdsi', $title, $amount, $due_date, $id);
    $stmt->execute();
    json_response(['ok' => true]);
}

if ($action === 'delete') {
    require_admin();
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare('UPDATE fees SET is_active = 0 WHERE fee_id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    json_response(['ok' => true]);
}

if ($action === 'save_maya') {
    require_admin();
    $name = trim($_POST['maya_account_name'] ?? '');
    $number = trim($_POST['maya_account_number'] ?? '');
    if ($name === '' || $number === '') {
        json_response(['ok' => false, 'error' => 'Account name and number are required.'], 400);
    }

    $qrPath = null;
    if (!empty($_FILES['maya_qr']['tmp_name'])) {
        $file = $_FILES['maya_qr'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            json_response(['ok' => false, 'error' => 'Unsupported image type.'], 400);
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            json_response(['ok' => false, 'error' => 'Image is too large (max 2MB).'], 400);
        }

        $dir = __DIR__ . '/../assets/img';
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $filename = 'maya_qr.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            json_response(['ok' => false, 'error' => 'Failed to save the image.'], 500);
        }
        $qrPath = '/assets/img/' . $filename;
    }

    save_maya_account($db, (int)current_admin_id(), $name, $number, $qrPath);
    json_response(['ok' => true]);
}

if ($action === 'save_paymongo') {
    require_admin();
    $secret_key = trim($_POST['paymongo_secret_key'] ?? '');
    $public_key = trim($_POST['paymongo_public_key'] ?? '');
    save_paymongo_keys($db, (int)current_admin_id(), $secret_key, $public_key);
    json_response(['ok' => true]);
}

// ---- Student: view fees + their unpaid ones ----
if ($action === 'mine') {
    require_student();
    $pk = current_student_pk();
    $stmt = $db->prepare('SELECT * FROM v_unpaid_balances WHERE student_pk = ?');
    $stmt->bind_param('i', $pk);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    json_response(['ok' => true, 'unpaid' => $rows]);
}

json_response(['ok' => false, 'error' => 'Unknown action.'], 400);
