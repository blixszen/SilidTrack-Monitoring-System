<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
require_student();
$db = get_db();
$pk = current_student_pk();

$stmt = $db->prepare("
    SELECT
      SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) AS present_count,
      SUM(CASE WHEN status='Late' THEN 1 ELSE 0 END) AS late_count,
      SUM(CASE WHEN status='Absent' THEN 1 ELSE 0 END) AS absent_count
    FROM attendance WHERE student_pk = ?
");
$stmt->bind_param('i', $pk);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

$stmt = $db->prepare('SELECT SUM(amount) AS total_due FROM v_unpaid_balances WHERE student_pk = ?');
$stmt->bind_param('i', $pk);
$stmt->execute();
$remaining = (float)($stmt->get_result()->fetch_assoc()['total_due'] ?? 0);

$stmt = $db->prepare("
    SELECT f.title AS fee_title, p.amount, p.status, p.paid_at, p.transaction_id
    FROM payments p JOIN fees f ON f.fee_id = p.fee_id
    WHERE p.student_pk = ? ORDER BY p.paid_at DESC
");
$stmt->bind_param('i', $pk);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$active_page = 'report';
$role = 'student';
$display_name = $_SESSION['full_name'] ?? 'Student';
$photo_path = $_SESSION['photo_path'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SilidTrack — Your Report</title>
<link rel="stylesheet" href="<?= url("/assets/css/style.css") ?>">
</head>
<body>
<div class="page-wrap">
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <h1 class="page-title" style="text-align:center">Your Report</h1>

  <div class="stat-grid" style="grid-template-columns: repeat(3, 1fr); max-width:900px; margin:0 auto 32px;">
    <div class="stat-card total">
      <div><div class="value"><?= (int)($summary['present_count'] ?? 0) ?></div><div class="label">Present</div></div>
      <div class="stat-icon"><?= icon('user-check', 'icon', 28) ?></div>
    </div>
    <div class="stat-card late">
      <div><div class="value"><?= (int)($summary['late_count'] ?? 0) ?></div><div class="label">Late</div></div>
      <div class="stat-icon"><?= icon('clock', 'icon', 28) ?></div>
    </div>
    <div class="stat-card absent">
      <div><div class="value"><?= (int)($summary['absent_count'] ?? 0) ?></div><div class="label">Absent</div></div>
      <div class="stat-icon"><?= icon('user-x', 'icon', 28) ?></div>
    </div>
  </div>

  <div class="flex-between mb-24">
    <h3 style="margin:0">Payment History</h3>
    <span class="fee-chip"><?= icon('wallet', 'icon', 14) ?> Remaining: PHP <?= number_format($remaining, 2) ?></span>
  </div>

  <div class="card">
    <table class="data-table">
      <thead><tr><th>Fee</th><th>Amount</th><th>Status</th><th>Paid On</th><th>Transaction ID.</th></tr></thead>
      <tbody>
        <?php foreach ($history as $h): ?>
        <tr>
          <td><?= htmlspecialchars($h['fee_title']) ?></td>
          <td class="num">PHP <?= number_format((float)$h['amount'], 2) ?></td>
          <td><span class="badge <?= $h['status'] === 'Paid' ? 'badge-present' : 'badge-late' ?>"><?= htmlspecialchars($h['status']) ?></span></td>
          <td><?= $h['paid_at'] ? date('M j, Y', strtotime($h['paid_at'])) : '—' ?></td>
          <td><?= htmlspecialchars($h['transaction_id']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$history): ?><tr class="empty-row"><td colspan="5">No payments yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
