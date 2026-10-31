<?php
/**
 * Floating top navigation bar.
 * Expects $active_page (string: 'home'|'attendance'|'fees'|'report') to be set by the including page.
 * Expects $role ('admin'|'student'), optional $display_name and $photo_path.
 */
require_once __DIR__ . '/icons.php';

$active_page = $active_page ?? 'home';
$role = $role ?? 'student';
$base = url($role === 'admin' ? '/admin' : '/student');
$display_name = $display_name ?? ($_SESSION['full_name'] ?? ($role === 'admin' ? 'Admin' : 'Student'));
$photo_path = $photo_path ?? ($_SESSION['photo_path'] ?? null);

// Notification bell: red dot while there is an announcement from the last 7 days
// the user has not opened yet (the "seen" id lives in the browser's localStorage).
$latest_announcement_id = 0;
try {
    $nav_db = get_db();
    $row = $nav_db->query('SELECT announcement_id FROM announcements WHERE created_at >= (NOW() - INTERVAL 7 DAY) ORDER BY created_at DESC LIMIT 1')->fetch_assoc();
    $latest_announcement_id = (int) ($row['announcement_id'] ?? 0);
} catch (Throwable $e) {
    $latest_announcement_id = 0;
}

$nav_items = [
    'home'       => ['Home',       'home',       '/dashboard.php'],
    'attendance' => ['Attendance', 'attendance', '/attendance.php'],
    'fees'       => ['Fees',       'receipt',    '/fees.php'],
    'report'     => ['Report',     'report',     '/report.php'],
];
?>
<nav class="topnav">
  <a class="topnav-left" href="<?= $base ?>/dashboard.php">
    <img src="<?= url('/assets/img/SilidTrack Logo.png') ?>" alt="SilidTrack Logo" class="nav-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
    <?= icon('logo', 'icon fallback-logo', 30, 1.8) ?>
    <span class="logo-text">SilidTrack</span>
  </a>

  <div class="topnav-center">
    <?php foreach ($nav_items as $key => [$label, $ico, $path]): ?>
      <a class="nav-pill <?= $active_page === $key ? 'active' : '' ?>" href="<?= $base . $path ?>"<?= $active_page === $key ? ' aria-current="page"' : '' ?>>
        <?= icon($ico, 'icon', 20, 1.9) ?><span><?= $label ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="topnav-right">
    <?php if ($role === 'admin'): ?>
      <span class="rfid-badge" id="rfidStatus" data-online="true"><span class="pulse"></span>ESP32 RFID Online</span>
      <button class="icon-btn" type="button" title="Settings" onclick="location.href='<?= $base ?>/fees.php'"><?= icon('gear', 'icon', 20) ?></button>
    <?php endif; ?>

    <a class="icon-btn bell-btn" id="bellBtn" href="<?= $base ?>/dashboard.php#announcements" title="Announcements" aria-label="Announcements" data-latest="<?= $latest_announcement_id ?>">
      <?= icon('bell', 'icon', 21, 1.9) ?>
      <?php if ($latest_announcement_id): ?><span class="bell-dot" id="bellDot"></span><?php endif; ?>
    </a>

    <div class="avatar-menu">
      <button class="avatar-btn" type="button" id="avatarBtn" title="<?= htmlspecialchars($display_name) ?>" aria-haspopup="true">
        <?php if ($photo_path): ?>
          <img src="<?= htmlspecialchars(url($photo_path)) ?>" alt="Profile photo">
        <?php else: ?>
          <?= htmlspecialchars(initials_of($display_name)) ?>
        <?php endif; ?>
      </button>
      <div class="avatar-dropdown" id="avatarDropdown">
        <div class="avatar-dropdown-head"><?= htmlspecialchars($display_name) ?><small><?= $role === 'admin' ? 'Administrator' : 'Student' ?></small></div>
        <?php if ($role === 'student'): ?>
          <a href="#" id="editPhotoLink">Edit Photo</a>
          <a href="#" id="changePasswordLink">Change Password</a>
        <?php endif; ?>
        <a href="<?= url('/api/logout.php') ?>" class="signout">Sign Out</a>
      </div>
    </div>
  </div>
</nav>
<script>
document.addEventListener('click', (e) => {
  const btn = document.getElementById('avatarBtn');
  const menu = document.getElementById('avatarDropdown');
  if (!btn || !menu) return;
  if (btn.closest('.avatar-menu').contains(e.target)) {
    menu.classList.toggle('open');
  } else {
    menu.classList.remove('open');
  }
});
// Hide the bell's red dot once this announcement has been opened.
(function () {
  const bell = document.getElementById('bellBtn');
  const dot = document.getElementById('bellDot');
  if (!bell || !dot) return;
  const key = 'silidtrack.seenAnnouncement';
  const latest = bell.dataset.latest;
  try { if (localStorage.getItem(key) === latest) dot.remove(); } catch (e) {}
  bell.addEventListener('click', () => {
    try { localStorage.setItem(key, latest); } catch (e) {}
    dot.remove();
  });
})();
</script>
