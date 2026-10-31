<?php
/**
 * One-time setup utility.
 * Run from CLI: php reset_admin_password.php admin@silidtrack.edu newpassword123
 * Or open in a browser as: reset_admin_password.php?email=...&password=...
 * DELETE THIS FILE after you've set your real admin password.
 */
require_once __DIR__ . '/config/db.php';

$email = $argv[1] ?? $_GET['email'] ?? null;
$password = $argv[2] ?? $_GET['password'] ?? null;

if (!$email || !$password) {
    die("Usage: php reset_admin_password.php <email> <new_password>\n");
}

$db = get_db();
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare('UPDATE admins SET password_hash = ? WHERE email = ?');
$stmt->bind_param('ss', $hash, $email);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Password updated for $email\n";
} else {
    // Admin didn't exist yet — create it.
    $stmt = $db->prepare('INSERT INTO admins (email, password_hash, full_name) VALUES (?, ?, "Admin")');
    $stmt->bind_param('ss', $email, $hash);
    $stmt->execute();
    echo "Admin account created for $email\n";
}
