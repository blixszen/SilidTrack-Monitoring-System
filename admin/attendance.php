<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
require_admin();
$db = get_db();

$statusClasses = ['Present' => 'badge-present', 'Late' => 'badge-late', 'Absent' => 'badge-absent', 'Excused' => 'badge-excused'];

// All-student view: every registered student for the selected date (today by default).
$view = ($_GET['view'] ?? 'all') === 'history' ? 'history' : 'all';
$scanDate = $_GET['date'] ?? ph_time_now()->format('Y-m-d');

$rosterStmt = $db->prepare("
    SELECT s.student_pk, s.student_id, s.full_name, s.section,
           a.attendance_id, a.scan_time, a.status, a.id_detected
    FROM students s
    LEFT JOIN attendance a ON a.student_pk = s.student_pk AND a.scan_date = ?
    WHERE s.is_active = 1
    ORDER BY s.student_id ASC
");
$rosterStmt->bind_param('s', $scanDate);
$rosterStmt->execute();
$roster = $rosterStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// History view: recent scans grouped under bold date headers.
$history = [];
if ($view === 'history') {
    $rows = $db->query("
        SELECT a.attendance_id, a.scan_date, a.scan_time, a.status, a.id_detected,
               s.student_id, s.full_name
        FROM attendance a
        JOIN students s ON s.student_pk = a.student_pk
        ORDER BY a.scan_date DESC, a.scan_time DESC
        LIMIT 150
    ")->fetch_all(MYSQLI_ASSOC);

    foreach ($rows as $r) {
        $history[$r['scan_date']][] = $r;
    }
}

$active_page = 'attendance';
$role = 'admin';
$display_name = $_SESSION['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SilidTrack — Student Management</title>
<link rel="stylesheet" href="<?= url("/assets/css/style.css") ?>">
</head>
<body>
<div class="page-wrap">
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <div class="flex-between wrap mb-24">
    <div>
      <h1 class="page-title" style="margin-bottom:6px">Student Management</h1>
      <p class="page-subtitle" style="margin:0">Use Set Status on an absent student to correct the record.</p>
    </div>
    <div class="flex-center wrap">
      <div class="search-bar">
        <?= icon('search', 'icon', 19) ?>
        <input type="text" id="searchStudent" placeholder="Search Student" oninput="filterStudents()">
      </div>
      <button class="btn btn-primary" type="button" onclick="openModal('addStudentModal')">
        <?= icon('plus', 'icon', 17) ?> Add New Student
      </button>
    </div>
  </div>

  <div class="flex-center wrap mb-24">
    <a class="btn <?= $view === 'all' ? 'btn-primary' : 'btn-muted' ?> btn-sm"
       href="?view=all&amp;date=<?= urlencode($scanDate) ?>"><?= icon('users', 'icon', 16) ?> All Students</a>
    <a class="btn <?= $view === 'history' ? 'btn-primary' : 'btn-muted' ?> btn-sm"
       href="?view=history"><?= icon('clipboard', 'icon', 16) ?> Scan History</a>
    <form method="GET" class="flex-center" style="margin-left:8px">
      <input type="hidden" name="view" value="all">
      <label class="field-label" style="margin:0">Roster Date</label>
      <input class="field-input" type="date" name="date" value="<?= htmlspecialchars($scanDate) ?>" onchange="this.form.submit()" style="margin:0;padding:9px 14px">
    </form>
    <span class="text-gray" style="font-size:.88rem">
      <?= count($roster) ?> student<?= count($roster) === 1 ? '' : 's' ?> registered
      &nbsp;·&nbsp; <?= date('l, F j, Y', strtotime($scanDate)) ?>
    </span>
  </div>

  <div class="card">
    <table class="data-table" id="attendanceTable">
      <thead>
        <tr>
          <th>Student ID</th>
          <th>Student Name</th>
          <th>Time</th>
          <th>Status</th>
          <th>ID Detected</th>
          <th>Override</th>
          <th>Password</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($view === 'history'): ?>
          <?php foreach ($history as $date => $items): ?>
            <tr class="date-sep"><td colspan="7"><?= date('l, F j, Y', strtotime($date)) ?></td></tr>
            <?php foreach ($items as $r): ?>
              <tr class="student-row" data-status="<?= htmlspecialchars($r['status']) ?>">
                <td><?= htmlspecialchars($r['student_id']) ?></td>
                <td class="student-name"><?= htmlspecialchars($r['full_name']) ?></td>
                <td class="num"><?= $r['scan_time'] ? date('h:i:s A', strtotime($r['scan_time'])) : '—' ?></td>
                <td class="status-cell">
                  <?php $badge = $statusClasses[$r['status']] ?? 'badge-unset'; ?>
                  <span class="badge <?= $badge ?> status-trigger"
                          data-attendance-id="<?= (int)$r['attendance_id'] ?>"
                          data-student-id="<?= htmlspecialchars($r['student_id'], ENT_QUOTES) ?>"
                          data-date="<?= htmlspecialchars($date) ?>">
                    <?= htmlspecialchars($r['status']) ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($r['id_detected']) ?></td>
                <td><?php if ($r['status'] === 'Absent'): ?><button class="btn btn-muted btn-sm" type="button" onclick="openOverride(this)">Set Status</button><?php else: ?><span class="text-faint">—</span><?php endif; ?></td>
                <td>
                  <button class="btn btn-primary btn-sm" type="button"
                          onclick="openPasswordReset('<?= htmlspecialchars($r['student_id'], ENT_QUOTES) ?>')">Edit</button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endforeach; ?>
          <?php if (!$history): ?>
            <tr class="empty-row"><td colspan="7">No scans recorded yet.</td></tr>
          <?php endif; ?>

        <?php else: ?>
          <tr class="date-sep"><td colspan="7"><?= date('l, F j, Y', strtotime($scanDate)) ?> — All Students</td></tr>
          <?php foreach ($roster as $r):
            $status = $r['status'] ?? '';
            $badge = $statusClasses[$status] ?? 'badge-unset';
          ?>
          <tr class="student-row" data-status="<?= htmlspecialchars($status) ?>">
            <td><?= htmlspecialchars($r['student_id']) ?></td>
            <td class="student-name"><?= htmlspecialchars($r['full_name']) ?></td>
            <td class="num"><?= $r['scan_time'] ? date('h:i:s A', strtotime($r['scan_time'])) : '—' ?></td>
            <td class="status-cell">
              <span class="badge <?= $badge ?> status-trigger"
                      data-attendance-id="<?= (int)($r['attendance_id'] ?? 0) ?>"
                      data-student-id="<?= htmlspecialchars($r['student_id'], ENT_QUOTES) ?>"
                      data-date="<?= htmlspecialchars($scanDate) ?>">
                <?= $status ?: 'No record' ?>
              </span>
            </td>
            <td><?= $r['id_detected'] !== null ? htmlspecialchars($r['id_detected']) : '—' ?></td>
            <td><?php if ($status === '' || $status === 'Absent'): ?><button class="btn btn-muted btn-sm" type="button" onclick="openOverride(this)">Set Status</button><?php else: ?><span class="text-faint">—</span><?php endif; ?></td>
            <td>
              <button class="btn btn-primary btn-sm" type="button"
                      onclick="openPasswordReset('<?= htmlspecialchars($r['student_id'], ENT_QUOTES) ?>')">Edit</button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$roster): ?>
            <tr class="empty-row"><td colspan="7">No students registered yet — use “Add New Student”.</td></tr>
          <?php endif; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add New Student Modal -->
<div class="modal-overlay" id="addStudentModal">
  <div class="modal-box">
    <span class="modal-title">New Student Account</span>
    <form id="addStudentForm">
      <label class="field-label"><?= icon('id-card', 'icon', 15) ?> Student ID</label>
      <input class="field-input" name="student_id" placeholder="e.g. 2024-101-1" required>
      <label class="field-label"><?= icon('user', 'icon', 15) ?> Full Name</label>
      <input class="field-input" name="full_name" placeholder="Juan Dela Cruz" required>
      <div class="field-row">
        <div>
          <label class="field-label">Program</label>
          <input class="field-input" name="program" placeholder="BS Information Technology">
        </div>
        <div>
          <label class="field-label">Section</label>
          <input class="field-input" name="section" placeholder="BSIT-1A">
        </div>
      </div>
      <label class="field-label"><?= icon('wifi', 'icon', 15) ?> UID</label>
      <input class="field-input" name="rfid_uid" placeholder="Tap card on enrollment reader">
      <label class="field-label"><?= icon('lock', 'icon', 15) ?> Password</label>
      <input class="field-input" name="password" type="password" placeholder="••••••••" required>
      <div class="form-actions">
        <label class="checkbox-row"><input type="checkbox" required> Confirm Account</label>
        <button class="btn btn-primary" type="submit">Create</button>
      </div>
    </form>
  </div>
</div>

<!-- Override Status Modal -->
<div class="modal-overlay" id="overrideModal">
  <div class="modal-box">
    <span class="modal-title">Set Attendance Status</span>
    <form id="overrideForm">
      <input type="hidden" name="attendance_id" id="overrideAttendanceId">
      <input type="hidden" name="student_id" id="overrideStudentId">
      <input type="hidden" name="scan_date" id="overrideDate">
      <p class="text-gray" id="overrideStudentName" style="margin-top:-6px"></p>
      <label class="field-label">Status</label>
      <div class="field-row">
        <select class="field-input" name="status" id="overrideStatusSelect">
          <option value="Present">Present</option>
          <option value="Late">Late</option>
          <option value="Absent">Absent</option>
          <option value="Excused">Excused</option>
        </select>
      </div>
      <div class="form-actions">
        <button class="btn btn-muted" type="button" onclick="closeModal('overrideModal')">Cancel</button>
        <button class="btn btn-primary" type="submit">Save Status</button>
      </div>
    </form>
  </div>
</div>

<!-- Password Reset Modal -->
<div class="modal-overlay" id="passwordModal">
  <div class="modal-box">
    <span class="modal-title">Reset Student Password</span>
    <form id="passwordForm">
      <input type="hidden" name="student_id" id="passwordStudentId">
      <label class="field-label"><?= icon('key', 'icon', 15) ?> New Password</label>
      <input class="field-input" name="new_password" type="password" placeholder="••••••••" required>
      <div class="form-actions">
        <button class="btn btn-muted" type="button" onclick="closeModal('passwordModal')">Cancel</button>
        <button class="btn btn-primary" type="submit">Update Password</button>
      </div>
    </form>
  </div>
</div>

<script>window.APP_BASE = "<?= APP_BASE_PATH ?>";</script>
<script src="<?= url("/assets/js/admin.js") ?>"></script>
</body>
</html>
