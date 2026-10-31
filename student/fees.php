<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
require_once __DIR__ . '/../includes/settings.php';
require_student();
$db = get_db();
$pk = current_student_pk();

$stmt = $db->prepare('SELECT * FROM v_unpaid_balances WHERE student_pk = ? ORDER BY due_date ASC');
$stmt->bind_param('i', $pk);
$stmt->execute();
$unpaid = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$maya = maya_account($db, null);
$mayaName = $maya['name'];
$mayaNumber = $maya['number'];
$mayaQr = $maya['qr'];

$active_page = 'fees';
$role = 'student';
$display_name = $_SESSION['full_name'] ?? 'Student';
$photo_path = $_SESSION['photo_path'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SilidTrack — Fees &amp; Payments</title>
<link rel="stylesheet" href="<?= url("/assets/css/style.css") ?>">
</head>
<body>
<div class="page-wrap">
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <h1 class="page-title">Fees &amp; Payments</h1>

  <?php if (!$unpaid): ?>
    <div class="card"><p class="text-gray" style="margin:0">You're all caught up — no unpaid fees.</p></div>
  <?php endif; ?>

  <?php foreach ($unpaid as $fee): ?>
    <div class="status-flag"><span class="dot"></span> Unpaid</div>
    <div class="fee-ticket">
      <span><?= htmlspecialchars($fee['fee_title']) ?> &nbsp;&mdash;&nbsp; PHP <?= number_format((float)$fee['amount'], 2) ?></span>
      <a class="btn btn-primary btn-noshine" href="<?= url('/student/fees_checkout.php') ?>?fee_id=<?= (int)$fee['fee_id'] ?>">
        Pay Now <?= icon('arrow-right', 'icon', 16) ?>
      </a>
    </div>
    <p class="text-gray" style="margin:0 0 26px">Due <?= date('F j, Y', strtotime($fee['due_date'])) ?></p>
  <?php endforeach; ?>

  <?php if ($mayaName || $mayaNumber): ?>
    <div class="maya-banner mt-24">
      <span class="pill-title"><?= icon('bank', 'icon', 18) ?> Admin Maya Bank Account</span>
      <div class="flex-center wrap mt-24" style="gap:28px; align-items:flex-start">
        <div>
          <div class="label text-gray" style="font-size:.82rem; font-weight:600">Account Name</div>
          <div style="font-weight:700"><?= htmlspecialchars($mayaName) ?></div>
        </div>
        <div>
          <div class="label text-gray" style="font-size:.82rem; font-weight:600">Account Number</div>
          <div style="font-weight:700"><?= htmlspecialchars($mayaNumber) ?></div>
        </div>
        <?php if ($mayaQr): ?>
          <img class="qr-preview" style="display:block; width:96px; height:96px" alt="Maya QR code" src="<?= htmlspecialchars(url($mayaQr)) ?>">
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>window.APP_BASE = "<?= APP_BASE_PATH ?>";</script>
<script src="<?= url("/assets/js/student.js") ?>"></script>
</body>
</html>
