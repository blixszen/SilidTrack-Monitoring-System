<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/icons.php';

// Already logged in? Redirect straight to the right dashboard.
if (is_admin_logged_in()) { header('Location: ' . url('/admin/dashboard.php')); exit; }
if (is_student_logged_in()) { header('Location: ' . url('/student/dashboard.php')); exit; }

$role = ($_GET['role'] ?? 'admin') === 'student' ? 'student' : 'admin';
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SilidTrack Login</title>
<link rel="stylesheet" href="<?= url("/assets/css/style.css") ?>">
</head>
<body>
<div class="login-screen">
  <button class="login-back" type="button" title="Go back" onclick="history.back()">
    <?= icon('arrow-left', 'icon', 22) ?>
  </button>

  <div class="login-brand">
    <div class="brand-name">
      <img src="<?= url('/assets/img/SilidTrack Logo.png') ?>" alt="SilidTrack Logo" class="brand-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
      <?= icon('logo', 'icon fallback-logo', 34, 1.8) ?>
      SilidTrack
    </div>
    <div class="brand-rule"></div>
    <p>SilidTrack is a Smart Classroom Monitoring System that tracks student attendance, classroom conditions, occupancy, and payment records through a web-based dashboard powered by an ESP32 RFID reader and sensors.</p>
  </div>

  <div class="login-card">
    <div class="role-toggle">
      <a href="?role=student" class="<?= $role === 'student' ? 'active' : '' ?>">Students</a>
      <a href="?role=admin" class="<?= $role === 'admin' ? 'active' : '' ?>">Admin</a>
    </div>

    <div class="login-title">SilidTrack Login</div>

    <?php if ($error): ?>
      <div class="login-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= url("/api/login.php") ?>">
      <input type="hidden" name="role" value="<?= $role ?>">

      <?php if ($role === 'admin'): ?>
        <label class="field-label"><?= icon('mail', 'icon', 15) ?> Your Email</label>
        <input class="field-input" type="email" name="email" placeholder="admin@silidtrack.edu" required>
        <label class="field-label"><?= icon('lock', 'icon', 15) ?> Password</label>
        <input class="field-input" type="password" name="password" placeholder="••••••••" required>
      <?php else: ?>
        <label class="field-label"><?= icon('id-card', 'icon', 15) ?> Student ID</label>
        <input class="field-input" type="text" name="student_id" placeholder="e.g. 2024-101-1" required>
        <label class="field-label"><?= icon('lock', 'icon', 15) ?> Password</label>
        <input class="field-input" type="password" name="password" placeholder="••••••••" required>
      <?php endif; ?>

      <div class="form-actions mt-16">
        <label class="checkbox-row"><input type="checkbox" required> I accept the <a href="#" class="link-blue">Term</a></label>
        <button class="btn btn-primary btn-signin" type="submit">Sign In <?= icon('arrow-right', 'icon', 17) ?></button>
      </div>
    </form>

    <?php if ($role === 'student'): ?>
      <p class="login-hint">Student accounts are created by an Admin — self-registration is disabled.</p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
