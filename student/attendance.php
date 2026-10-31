<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
require_student();
$db = get_db();

$pk = current_student_pk();
// Optional ?status=Late|Present|Absent|Excused filter (linked from the dashboard cards).
$filter = in_array($_GET['status'] ?? '', ['Present', 'Late', 'Absent', 'Excused'], true) ? $_GET['status'] : '';
$stmt = $db->prepare('
    SELECT s.full_name, s.student_id, a.scan_date, a.scan_time, a.status, a.id_detected
    FROM attendance a JOIN students s ON s.student_pk = a.student_pk
    WHERE a.student_pk = ? AND (? = \'\' OR a.status = ?)
    ORDER BY a.scan_date DESC, a.scan_time DESC LIMIT 60
');
$stmt->bind_param('iss', $pk, $filter, $filter);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$grouped = [];
foreach ($rows as $r) {
    $grouped[$r['scan_date']][] = $r;
}

$statusClasses = ['Present' => 'badge-present', 'Late' => 'badge-late', 'Absent' => 'badge-absent', 'Excused' => 'badge-excused'];

$active_page = 'attendance';
$role = 'student';
$display_name = $_SESSION['full_name'] ?? 'Student';
$photo_path = $_SESSION['photo_path'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SilidTrack — View Attendance</title>
<link rel="stylesheet" href="<?= url("/assets/css/style.css") ?>">
</head>
<body>
<div class="page-wrap">
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <h1 class="page-title">View Attendance</h1>

  <div class="card mb-24" style="padding:14px 20px">
    <div class="search-bar" style="box-shadow:none; padding:0; min-width:0">
      <?= icon('search', 'icon', 19) ?>
      <input type="text" id="searchStudent" placeholder="Search records" oninput="filterRows()">
    </div>
  </div>

  <div class="card">
    <table class="data-table" id="attTable">
      <thead><tr><th>Date</th><th>Student Name</th><th>Student ID</th><th>Time</th><th>Status</th><th>ID Detected</th></tr></thead>
      <tbody>
        <?php foreach ($grouped as $date => $items): ?>
          <tr class="date-sep"><td colspan="6"><?= date('l, F j, Y', strtotime($date)) ?></td></tr>
          <?php foreach ($items as $r): ?>
            <?php $badgeClass = $statusClasses[$r['status']] ?? 'badge-unset'; ?>
            <tr class="student-row">
              <td class="num"><?= date('M j', strtotime($date)) ?></td>
              <td><?= htmlspecialchars($r['full_name']) ?></td>
              <td><?= htmlspecialchars($r['student_id']) ?></td>
              <td class="num"><?= $r['scan_time'] ? date('h:i:s A', strtotime($r['scan_time'])) : '—' ?></td>
              <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($r['status']) ?></span></td>
              <td><?= htmlspecialchars($r['id_detected']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr class="empty-row"><td colspan="6">No attendance records yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>window.APP_BASE = "<?= APP_BASE_PATH ?>";</script>
<script src="<?= url("/assets/js/student.js") ?>"></script>
<script>
function filterRows() {
  const q = document.getElementById('searchStudent').value.toLowerCase();
  document.querySelectorAll('.student-row').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>
</body>
</html>
