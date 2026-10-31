<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('/index.php'));
    exit;
}

$role = $_POST['role'] ?? 'student';
$db = get_db();

if ($role === 'admin') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare('SELECT admin_id, password_hash, full_name, photo_path FROM admins WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result && password_verify($password, $result['password_hash'])) {
        $_SESSION['role'] = 'admin';
        $_SESSION['admin_id'] = $result['admin_id'];
        $_SESSION['full_name'] = $result['full_name'];
        $_SESSION['photo_path'] = $result['photo_path'];
        header('Location: ' . url('/admin/dashboard.php'));
        exit;
    }

    $_SESSION['login_error'] = 'Invalid email or password.';
    header('Location: ' . url('/index.php?role=admin'));
    exit;

} else {
    $student_id = trim($_POST['student_id'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare('SELECT student_pk, password_hash, full_name, photo_path, is_active FROM students WHERE student_id = ?');
    $stmt->bind_param('s', $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result && $result['is_active'] && password_verify($password, $result['password_hash'])) {
        $_SESSION['role'] = 'student';
        $_SESSION['student_pk'] = $result['student_pk'];
        $_SESSION['student_id'] = $student_id;
        $_SESSION['full_name'] = $result['full_name'];
        $_SESSION['photo_path'] = $result['photo_path'];
        header('Location: ' . url('/student/dashboard.php'));
        exit;
    }

    $_SESSION['login_error'] = 'Invalid Student ID or password.';
    header('Location: ' . url('/index.php?role=student'));
    exit;
}
