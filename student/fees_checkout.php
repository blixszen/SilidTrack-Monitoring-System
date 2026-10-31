<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
require_student();
$db = get_db();

$fee_id = (int)($_GET['fee_id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM fees WHERE fee_id = ? AND is_active = 1');
$stmt->bind_param('i', $fee_id);
$stmt->execute();
$fee = $stmt->get_result()->fetch_assoc();

if (!$fee) { header('Location: ' . url('/student/fees.php')); exit; }

$active_page = 'fees';
$role = 'student';
$display_name = $_SESSION['full_name'] ?? 'Student';
$photo_path = $_SESSION['photo_path'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SilidTrack — Checkout</title>
<link rel="stylesheet" href="<?= url("/assets/css/style.css") ?>">
</head>
<body>
<div class="page-wrap">
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <div class="card" style="max-width:480px; margin:40px auto;">
    <h2 class="text-center" style="margin:0 0 4px">Checkout</h2>
    <p class="text-gray text-center" style="margin:0">Complete your fee payment securely.</p>
    <div class="card" style="margin-top:20px; padding:30px; box-shadow:none; background:var(--bg)">
      <div style="color:var(--royal-blue); font-weight:800; font-size:1.5rem; text-align:center; letter-spacing:-0.5px">PayMongo</div>
      <h3 class="text-center" style="margin:20px 0 4px">SilidTrack</h3>
      <p class="text-gray" style="margin:0 0 14px"><?= htmlspecialchars($fee['title']) ?></p>
      <p style="font-weight:800; font-size:1.4rem; margin:0 0 20px">₱<?= number_format($fee['amount'], 2) ?></p>
      <hr style="border:none; border-top:1px solid var(--inactive-pill); margin-bottom:16px">
      <div class="flex-between"><span>Subtotal</span><span>₱<?= number_format($fee['amount'], 2) ?></span></div>
      <div class="flex-between" style="font-weight:800; margin-top:6px"><span>Total</span><span>₱<?= number_format($fee['amount'], 2) ?></span></div>

      <form id="payForm" class="mt-24">
        <input type="hidden" name="fee_id" value="<?= (int)$fee['fee_id'] ?>">
        <button class="btn btn-primary btn-block" type="submit" id="payBtn">Proceed to PayMongo Checkout</button>
      </form>
      <p class="text-gray mt-16 text-center" style="font-size:.85rem">You will be redirected to PayMongo to complete payment securely.</p>
    </div>
  </div>
</div>

<script>window.APP_BASE = "<?= APP_BASE_PATH ?>";</script>
<script src="<?= url("/assets/js/student.js") ?>"></script>
<script>
document.getElementById('payForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const btn = document.getElementById('payBtn');
  btn.disabled = true;
  btn.textContent = 'Creating checkout session...';

  const fd = new FormData(e.target);
  const res = await fetch('<?= url('/api/payments.php') ?>?action=create_checkout', { method: 'POST', body: fd });
  const data = await res.json();

  if (data.ok && data.checkout_url) {
    window.location.href = data.checkout_url;
  } else {
    btn.disabled = false;
    btn.textContent = 'Proceed to PayMongo Checkout';
    alert(data.error || 'Could not create checkout session. Please try again.');
  }
});
</script>
</body>
</html>
