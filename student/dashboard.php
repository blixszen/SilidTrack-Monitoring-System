<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
require_once __DIR__ . '/../includes/dashboard_helpers.php';
require_student();
$db = get_db();
$pk = current_student_pk();

// This month's attendance for the signed-in student (cards are labelled "This month").
[$monthStart, $monthEnd] = current_month_range();
$stmt = $db->prepare("
    SELECT
      SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present_count,
      SUM(CASE WHEN status='Late' THEN 1 ELSE 0 END) AS late_count,
      SUM(CASE WHEN status='Absent' THEN 1 ELSE 0 END) AS absent_count,
      SUM(CASE WHEN status='Excused' THEN 1 ELSE 0 END) AS excused_count
    FROM attendance WHERE student_pk = ? AND scan_date BETWEEN ? AND ?
");
$stmt->bind_param('iss', $pk, $monthStart, $monthEnd);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$present = (int)($summary['present_count'] ?? 0);
$late    = (int)($summary['late_count'] ?? 0);
$absent  = (int)($summary['absent_count'] ?? 0);
$excused = (int)($summary['excused_count'] ?? 0);
$total   = $present + $late + $absent + $excused;

// Program + year for the header line.
$stmt = $db->prepare('SELECT program, section FROM students WHERE student_pk = ?');
$stmt->bind_param('i', $pk);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc() ?: [];
$program   = trim($me['program'] ?? '');
$yearLabel = year_label_from_section($me['section'] ?? null);

$allAnnouncements = $db->query('SELECT a.*, f.amount, f.title AS fee_title
                            FROM announcements a
                            LEFT JOIN fees f ON f.fee_id = a.fee_id
                            ORDER BY a.created_at DESC LIMIT 30')->fetch_all(MYSQLI_ASSOC);
$announcement = $allAnnouncements[0] ?? null;

$greeting = dash_greeting();
$firstName = explode(' ', trim($_SESSION['full_name'] ?? 'Student'))[0];

$active_page = 'home';
$role = 'student';
$display_name = $_SESSION['full_name'] ?? 'Student';
$photo_path = $_SESSION['photo_path'] ?? null;
$cssV = @filemtime(__DIR__ . '/../assets/css/style.css') ?: time();
$dashV = @filemtime(__DIR__ . '/../assets/css/dashboard.css') ?: time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SilidTrack — Student Dashboard</title>
<link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>?v=<?= $cssV ?>">
<link rel="stylesheet" href="<?= url('/assets/css/dashboard.css') ?>?v=<?= $dashV ?>">
</head>
<body class="dash-body">
<div class="page-wrap">
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <header class="d-hero">
    <h1 class="d-greeting"><span id="greeting"><?= $greeting ?></span>, <em><?= htmlspecialchars($firstName) ?>!</em></h1>
    <p class="d-sub">Welcome back to your student dashboard.</p>
    <div class="d-meta">
      <?php if ($program !== ''): ?>
        <span class="d-meta-item"><?= icon('graduation', 'icon', 22, 1.9) ?><?= htmlspecialchars($program) ?><?php if ($yearLabel): ?><i class="d-sep"></i><?= $yearLabel ?><?php endif; ?></span>
      <?php endif; ?>
      <span class="d-meta-item"><?= icon('calendar', 'icon', 20, 1.9) ?><span id="todayLabel"><?= dash_today_label() ?></span></span>
    </div>
  </header>

  <section class="d-stats" aria-label="This month's attendance">
    <a class="d-stat total" href="<?= url('/student/attendance.php') ?>">
      <span class="d-stat-ico"><?= icon('scan', 'icon', 28, 1.9) ?></span>
      <span class="d-stat-body">
        <span class="d-stat-label">Total Scans</span>
        <span class="d-stat-value" id="statTotal"><?= $total ?></span>
        <span class="d-stat-cap">Attendance records</span>
      </span>
      <?= icon('arrow-right', 'icon d-stat-arrow', 20, 2) ?>
    </a>
    <a class="d-stat present" href="<?= url('/student/attendance.php?status=Present') ?>">
      <span class="d-stat-ico"><?= icon('check-circle', 'icon', 28, 2) ?></span>
      <span class="d-stat-body">
        <span class="d-stat-label">Present</span>
        <span class="d-stat-value" id="statPresent"><?= $present ?></span>
        <span class="d-stat-cap">This month</span>
      </span>
      <?= icon('arrow-right', 'icon d-stat-arrow', 20, 2) ?>
    </a>
    <a class="d-stat late" href="<?= url('/student/attendance.php?status=Late') ?>">
      <span class="d-stat-ico"><?= icon('clock', 'icon', 28, 1.9) ?></span>
      <span class="d-stat-body">
        <span class="d-stat-label">Late</span>
        <span class="d-stat-value" id="statLate"><?= $late ?></span>
        <span class="d-stat-cap">This month</span>
      </span>
      <?= icon('arrow-right', 'icon d-stat-arrow', 20, 2) ?>
    </a>
    <a class="d-stat absent" href="<?= url('/student/attendance.php?status=Absent') ?>">
      <span class="d-stat-ico"><?= icon('user-slash', 'icon', 28, 1.9) ?></span>
      <span class="d-stat-body">
        <span class="d-stat-label">Absent</span>
        <span class="d-stat-value" id="statAbsent"><?= $absent ?></span>
        <span class="d-stat-cap">This month</span>
      </span>
      <?= icon('arrow-right', 'icon d-stat-arrow', 20, 2) ?>
    </a>
</section>

  <section class="d-panels">
    <article class="d-card d-ann" id="announcements">
      <div class="d-card-head">
        <span class="d-card-ico"><?= icon('megaphone', 'icon', 24, 1.9) ?></span>
        <h2>Latest Announcement</h2>
        <?php if (count($allAnnouncements) > 1): ?>
          <button type="button" class="d-viewall" data-open-modal="allAnnouncements">View All <?= icon('arrow-right', 'icon', 16, 2.2) ?></button>
        <?php endif; ?>
      </div>
      <?php if ($announcement): ?>
        <div class="d-ann-body">
          <h3><?= htmlspecialchars($announcement['title']) ?></h3>
          <p><?= nl2br(htmlspecialchars($announcement['message'])) ?></p>
          <?php if ($announcement['amount'] !== null): ?>
            <span class="fee-chip"><?= icon('wallet', 'icon', 14) ?> <?= htmlspecialchars($announcement['fee_title']) ?> · PHP <?= number_format((float)$announcement['amount'], 2) ?></span>
          <?php endif; ?>
          <time class="d-ann-time"><?= date('M j, Y · g:i A', strtotime($announcement['created_at'])) ?></time>
        </div>
      <?php else: ?>
        <div class="d-empty">
          <span class="d-empty-art"><?= icon('file-empty', 'icon', 64, 1.4) ?><span class="d-empty-badge"><?= icon('megaphone', 'icon', 20, 2) ?></span></span>
          <strong>No announcement yet.</strong>
          <p>Check back later for important updates<br>from the school administration.</p>
        </div>
      <?php endif; ?>
    </article>

    <article class="d-card d-cal" data-role="student">
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
</div>

<div class="modal-overlay" id="allAnnouncements">
  <div class="modal-box d-modal">
    <span class="modal-title">All Announcements</span>
    <div class="d-modal-list">
      <?php foreach ($allAnnouncements as $a): ?>
        <div class="d-modal-item">
          <h4><?= htmlspecialchars($a['title']) ?></h4>
          <p><?= nl2br(htmlspecialchars($a['message'])) ?></p>
          <div class="announcement-meta">
            <?php if ($a['amount'] !== null): ?>
              <span class="fee-chip"><?= icon('wallet', 'icon', 14) ?> <?= htmlspecialchars($a['fee_title']) ?> · PHP <?= number_format((float)$a['amount'], 2) ?></span>
            <?php endif; ?>
            <span class="text-faint" style="font-size:.8rem"><?= date('M j, Y g:i A', strtotime($a['created_at'])) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="form-actions" style="justify-content:flex-end"><button class="btn btn-muted" type="button" data-close-modal>Close</button></div>
  </div>
</div>

<script>window.APP_BASE = "<?= APP_BASE_PATH ?>"; window.STUDENT_PK = <?= (int)$pk ?>;</script>
<script src="<?= url('/assets/js/student.js') ?>?v=<?= @filemtime(__DIR__ . '/../assets/js/student.js') ?: time() ?>"></script>
<script src="<?= url('/assets/js/dashboard.js') ?>?v=<?= @filemtime(__DIR__ . '/../assets/js/dashboard.js') ?: time() ?>"></script>
</body>
</html>
