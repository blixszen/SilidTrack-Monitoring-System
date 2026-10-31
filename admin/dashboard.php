<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
require_once __DIR__ . '/../includes/dashboard_helpers.php';
require_admin();
$db = get_db();

$stats = $db->query('SELECT * FROM v_today_attendance')->fetch_assoc();
$total = (int)($stats['total_students'] ?? 0);
$present = (int)($stats['present_count'] ?? 0);
$late = (int)($stats['late_count'] ?? 0);
$absent = (int)($stats['absent_count'] ?? 0);

$announcements = $db->query('SELECT a.*, f.title AS fee_title, f.amount
                            FROM announcements a
                            LEFT JOIN fees f ON f.fee_id = a.fee_id
                            ORDER BY a.created_at DESC LIMIT 8')->fetch_all(MYSQLI_ASSOC);

$latest = $announcements[0] ?? null;

$greeting = dash_greeting();
$firstName = explode(' ', trim($_SESSION['full_name'] ?? 'Admin'))[0];
$attended = $present + $late;
$rate = $total > 0 ? (int) round($attended / $total * 100) : 0;
$cssV = @filemtime(__DIR__ . '/../assets/css/style.css') ?: time();
$dashV = @filemtime(__DIR__ . '/../assets/css/dashboard.css') ?: time();

$active_page = 'home';
$role = 'admin';
$display_name = $_SESSION['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SilidTrack — Admin Dashboard</title>
<link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>?v=<?= $cssV ?>">
<link rel="stylesheet" href="<?= url('/assets/css/dashboard.css') ?>?v=<?= $dashV ?>">
</head>
<body class="dash-body">
<div class="page-wrap">
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <header class="d-hero">
    <h1 class="d-greeting"><span id="greeting"><?= $greeting ?></span>, <em><?= htmlspecialchars($firstName) ?>!</em></h1>
    <p class="d-sub">Here's how attendance is looking across your classroom today.</p>
    <div class="d-meta">
      <span class="d-meta-item"><?= icon('shield-check', 'icon', 22, 1.9) ?>Administrator<i class="d-sep"></i><span id="attRate"><?= $rate ?>%</span>&nbsp;in today</span>
      <span class="d-meta-item"><?= icon('calendar', 'icon', 20, 1.9) ?><span id="todayLabel"><?= dash_today_label() ?></span></span>
    </div>
  </header>

  <section class="d-stats" aria-label="Today's attendance">
    <a class="d-stat total" href="<?= url('/admin/attendance.php') ?>">
      <span class="d-stat-ico"><?= icon('users', 'icon', 28, 1.9) ?></span>
      <span class="d-stat-body">
        <span class="d-stat-label">Total Students</span>
        <span class="d-stat-value" id="statTotal"><?= $total ?></span>
        <span class="d-stat-cap">Active students</span>
      </span>
      <?= icon('arrow-right', 'icon d-stat-arrow', 20, 2) ?>
    </a>
    <a class="d-stat present" href="<?= url('/admin/attendance.php?status=Present') ?>">
      <span class="d-stat-ico"><?= icon('check-circle', 'icon', 28, 2) ?></span>
      <span class="d-stat-body">
        <span class="d-stat-label">Present</span>
        <span class="d-stat-value" id="statPresent"><?= $present ?></span>
        <span class="d-stat-cap">Today</span>
      </span>
      <?= icon('arrow-right', 'icon d-stat-arrow', 20, 2) ?>
    </a>
    <a class="d-stat late" href="<?= url('/admin/attendance.php?status=Late') ?>">
      <span class="d-stat-ico"><?= icon('clock', 'icon', 28, 1.9) ?></span>
      <span class="d-stat-body">
        <span class="d-stat-label">Late</span>
        <span class="d-stat-value" id="statLate"><?= $late ?></span>
        <span class="d-stat-cap">Today</span>
      </span>
      <?= icon('arrow-right', 'icon d-stat-arrow', 20, 2) ?>
    </a>
    <a class="d-stat absent" href="<?= url('/admin/attendance.php?status=Absent') ?>">
      <span class="d-stat-ico"><?= icon('user-slash', 'icon', 28, 1.9) ?></span>
      <span class="d-stat-body">
        <span class="d-stat-label">Absent</span>
        <span class="d-stat-value" id="statAbsent"><?= $absent ?></span>
        <span class="d-stat-cap">Today</span>
      </span>
      <?= icon('arrow-right', 'icon d-stat-arrow', 20, 2) ?>
    </a>
</section>

  <section class="d-panels">
    <article class="d-card d-ann" id="announcements">
      <div class="d-card-head">
        <span class="d-card-ico"><?= icon('megaphone', 'icon', 24, 1.9) ?></span>
        <h2>Latest Announcement</h2>
        <a class="d-viewall" href="#manage">Manage <?= icon('arrow-right', 'icon', 16, 2.2) ?></a>
      </div>
      <?php if ($latest): ?>
        <div class="d-ann-body">
          <h3><?= htmlspecialchars($latest['title']) ?></h3>
          <p><?= nl2br(htmlspecialchars(mb_strimwidth($latest['message'], 0, 220, '…'))) ?></p>
          <?php if ($latest['amount'] !== null): ?>
            <span class="fee-chip"><?= icon('wallet', 'icon', 14) ?> <?= htmlspecialchars($latest['fee_title']) ?> · PHP <?= number_format((float)$latest['amount'], 2) ?></span>
          <?php endif; ?>
          <time class="d-ann-time"><?= date('M j, Y · g:i A', strtotime($latest['created_at'])) ?></time>
        </div>
      <?php else: ?>
        <div class="d-empty">
          <span class="d-empty-art"><?= icon('file-empty', 'icon', 64, 1.4) ?><span class="d-empty-badge"><?= icon('megaphone', 'icon', 20, 2) ?></span></span>
          <strong>No announcement yet.</strong>
          <p>Publish one below and it will appear<br>on every student dashboard.</p>
        </div>
      <?php endif; ?>
    </article>

    <article class="d-card d-cal" data-role="admin">
      <div class="d-card-head">
        <span class="d-card-ico"><?= icon('calendar', 'icon', 24, 1.9) ?></span>
        <h2>Attendance Calendar</h2>
        <div class="d-cal-nav">
          <button type="button" id="calPrev" aria-label="Previous week"><?= icon('chevron-left', 'icon', 18, 2.2) ?></button>
          <span id="calMonth">—</span>
          <button type="button" id="calNext" aria-label="Next week"><?= icon('chevron-right', 'icon', 18, 2.2) ?></button>
        </div>
      </div>
      <div class="d-week" id="wkStrip" role="tablist" aria-label="Week days"></div>
      <div class="d-day-detail" id="dayDetail" aria-live="polite"></div>
    </article>
  </section>

  <section class="d-manage" id="manage">
    <div class="d-section-head">
      <h2>Announcement Management</h2>
      <p>Publish updates for students and keep the list tidy.</p>
    </div>

    <div class="d-manage-grid">
      <article class="d-card d-form">
        <div class="d-card-head">
          <span class="d-card-ico"><?= icon('edit', 'icon', 22, 1.9) ?></span>
          <h2>Publish Announcement</h2>
        </div>
        <form id="announcementForm">
          <label class="field-label" for="annTitle">Title</label>
          <input class="field-input" id="annTitle" name="title" placeholder="e.g. Class Suspension" required>
          <label class="field-label" for="annMessage">Message</label>
          <textarea class="field-input" id="annMessage" name="message" placeholder="Write your announcement..." required></textarea>
          <div class="form-actions">
            <span class="hint">Published announcements appear on every student dashboard.</span>
            <button class="btn btn-primary" type="submit"><?= icon('megaphone', 'icon', 17) ?> Publish</button>
          </div>
        </form>
      </article>

      <article class="d-card d-list">
        <div class="d-card-head">
          <span class="d-card-ico"><?= icon('clipboard', 'icon', 22, 1.9) ?></span>
          <h2>Published Announcements</h2>
          <span class="badge badge-excused"><?= count($announcements) ?> active</span>
        </div>

        <div class="list-scroll" id="announcementList">
          <?php foreach ($announcements as $a): ?>
            <div class="announcement-item">
              <div>
                <h4><?= htmlspecialchars($a['title']) ?></h4>
                <p><?= htmlspecialchars(mb_strimwidth($a['message'], 0, 140, '…')) ?></p>
                <div class="announcement-meta">
                  <?php if ($a['amount'] !== null): ?>
                    <span class="fee-chip"><?= icon('wallet', 'icon', 14) ?> <?= htmlspecialchars($a['fee_title']) ?> · PHP <?= number_format((float)$a['amount'], 2) ?></span>
                  <?php endif; ?>
                  <span class="text-faint" style="font-size:.8rem"><?= date('M j, Y g:i A', strtotime($a['created_at'])) ?></span>
                </div>
              </div>
              <div class="row-actions">
                <button class="btn btn-success btn-icon" type="button" title="Edit"
                        onclick='editAnnouncement(<?= json_encode([
                            "id" => (int)$a['announcement_id'],
                            "title" => $a['title'],
                            "message" => $a['message'],
                        ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                  <?= icon('edit', 'icon', 17) ?>
                </button>
                <button class="btn btn-danger btn-icon" type="button" title="Delete"
                        onclick="deleteAnnouncement(<?= (int)$a['announcement_id'] ?>)">
                  <?= icon('trash', 'icon', 17) ?>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (!$announcements): ?>
            <div class="announcement-item text-faint">No announcements published yet.</div>
          <?php endif; ?>
        </div>
      </article>
    </div>
  </section>
</div>

<div class="modal-overlay" id="editAnnouncementModal">
  <div class="modal-box">
    <span class="modal-title">Edit Announcement</span>
    <form id="editAnnouncementForm">
      <input type="hidden" name="announcement_id" id="editAnnouncementId">
      <label class="field-label">Title</label>
      <input class="field-input" name="title" id="editAnnouncementTitle" required>
      <label class="field-label">Message</label>
      <textarea class="field-input" name="message" id="editAnnouncementMessage" rows="4" required></textarea>
      <div class="form-actions">
        <button class="btn btn-muted" type="button" onclick="closeModal('editAnnouncementModal')">Cancel</button>
        <button class="btn btn-primary" type="submit">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>window.APP_BASE = "<?= APP_BASE_PATH ?>";</script>
<script src="<?= url('/assets/js/admin.js') ?>?v=<?= @filemtime(__DIR__ . '/../assets/js/admin.js') ?: time() ?>"></script>
<script src="<?= url('/assets/js/dashboard.js') ?>?v=<?= @filemtime(__DIR__ . '/../assets/js/dashboard.js') ?: time() ?>"></script>
</body>
</html>
