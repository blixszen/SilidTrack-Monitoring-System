<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
require_admin();
$db = get_db();

$at_risk = $db->query('SELECT * FROM v_at_risk LIMIT 20')->fetch_all(MYSQLI_ASSOC);
$unpaid = $db->query('SELECT * FROM v_unpaid_balances ORDER BY due_date ASC LIMIT 50')->fetch_all(MYSQLI_ASSOC);

$total_absences = array_sum(array_map(fn($r) => (int)$r['total_absences'], $at_risk));
$unpaid_total = array_sum(array_map(fn($r) => (float)$r['amount'], $unpaid));

$active_page = 'report';
$role = 'admin';
$display_name = $_SESSION['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SilidTrack — Report & Analytics</title>
<link rel="stylesheet" href="<?= url("/assets/css/style.css") ?>">
</head>
<body>
<div class="page-wrap">
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <h1 class="page-title">Report & Analytics</h1>

  <div class="card mb-24">
    <span class="pill-title"><?= icon('user-x', 'icon', 18) ?> At Risk Attendance</span>
    <p class="text-gray" style="margin:16px 0 0"><?= count($at_risk) ?> student<?= count($at_risk) === 1 ? '' : 's' ?> flagged · <?= (int)$total_absences ?> total absences in the last 30 days.</p>
    <table class="data-table mt-16">
      <thead>
        <tr><th>Student ID</th><th>Student Name</th><th>Total Absences</th></tr>
      </thead>
      <tbody>
        <?php foreach ($at_risk as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['student_id']) ?></td>
            <td><?= htmlspecialchars($r['full_name']) ?></td>
            <td><span class="badge badge-absent"><?= (int)$r['total_absences'] ?> Absences</span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$at_risk): ?>
          <tr class="empty-row"><td colspan="3">No at-risk students right now.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="card">
    <span class="pill-title"><?= icon('wallet', 'icon', 18) ?> Unpaid Balances</span>
    <p class="text-gray" style="margin:16px 0 0">Outstanding fees of PHP <?= number_format($unpaid_total, 2) ?> across <?= count($unpaid) ?> record(s).</p>
    <table class="data-table mt-16" id="unpaidTable">
      <thead>
        <tr><th>Student ID</th><th>Student Name</th><th>Fee</th><th>Amount</th><th>Due Date</th><th style="width:120px">Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($unpaid as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['student_id']) ?></td>
            <td><?= htmlspecialchars($r['full_name']) ?></td>
            <td><?= htmlspecialchars($r['fee_title']) ?></td>
            <td class="num">PHP <?= number_format((float)$r['amount'], 2) ?></td>
            <td><?= date('D, M j, Y', strtotime($r['due_date'])) ?></td>
            <td>
              <div class="row-actions">
                <button class="btn btn-success btn-sm" type="button" title="Mark as Paid"
                        onclick="markUnpaidPaid(<?= (int)$r['student_pk'] ?>, <?= (int)$r['fee_id'] ?>, <?= (float)$r['amount'] ?>)">
                  <?= icon('check', 'icon', 16) ?> Paid
                </button>
                <button class="btn btn-danger btn-sm" type="button" title="Waive / Remove"
                        onclick="waiveUnpaid(<?= (int)$r['student_pk'] ?>, <?= (int)$r['fee_id'] ?>)">
                  <?= icon('trash', 'icon', 16) ?> Waive
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$unpaid): ?>
          <tr class="empty-row"><td colspan="6">No unpaid balances.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>window.APP_BASE = "<?= APP_BASE_PATH ?>";</script>
<script src="<?= url("/assets/js/admin.js") ?>"></script>
</body>
</html>
