<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
require_once __DIR__ . '/../includes/settings.php';
require_student();
$db = get_db();

$pk = current_student_pk();
$fee_id = (int)($_GET['fee_id'] ?? 0);
$session_id = $_GET['session_id'] ?? '';

// Verify payment with PayMongo if session_id provided
if ($session_id && $fee_id) {
    // Admin PayMongo secret key (admins column or settings store)
    $secret = paymongo_keys($db)['secret'];
    if ($secret !== '') {
        $ch = curl_init('https://api.paymongo.com/v1/checkout_sessions/' . $session_id);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode($secret . ':'),
                'Accept: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode((string)$response, true);
        $payment_status = $result['data']['attributes']['payment_status'] ?? '';

        if ($payment_status === 'paid') {
            if (column_exists($db, 'payments', 'paymongo_session_id')) {
                $stmt = $db->prepare('UPDATE payments SET status = "Paid", paid_at = NOW() WHERE paymongo_session_id = ? AND student_pk = ? AND fee_id = ?');
                $stmt->bind_param('sii', $session_id, $pk, $fee_id);
            } else {
                $stmt = $db->prepare('UPDATE payments SET status = "Paid", paid_at = NOW() WHERE transaction_id = ? AND student_pk = ? AND fee_id = ? AND status = "Pending"');
                $stmt->bind_param('sii', $session_id, $pk, $fee_id);
            }
            $stmt->execute();
        }
    }
}

// Get payment record
$stmt = $db->prepare('
    SELECT p.*, f.title AS fee_title, s.full_name
    FROM payments p
    JOIN fees f ON f.fee_id = p.fee_id
    JOIN students s ON s.student_pk = p.student_pk
    WHERE p.student_pk = ? AND p.fee_id = ? AND p.status = "Paid"
    ORDER BY p.paid_at DESC LIMIT 1
');
$stmt->bind_param('ii', $pk, $fee_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if (!$payment) { header('Location: ' . url('/student/fees.php')); exit; }

$active_page = 'fees';
$role = 'student';
$display_name = $_SESSION['full_name'] ?? 'Student';
$photo_path = $_SESSION['photo_path'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SilidTrack — Payment Successful</title>
<link rel="stylesheet" href="<?= url("/assets/css/style.css") ?>">
</head>
<body>
<div class="page-wrap">
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <div class="card" style="max-width:520px; margin:40px auto; text-align:center;">
    <div style="color:var(--royal-blue); font-weight:800; font-size:1.6rem; letter-spacing:-0.5px">PayMongo</div>
    <h2 class="mt-24" style="display:flex; align-items:center; justify-content:center; gap:10px">
      <?= icon('user-check', 'icon', 26) ?> Your payment was successful!
    </h2>
    <p class="text-gray">Thank you for the payment!<br>Your payment for <?= htmlspecialchars($payment['fee_title']) ?> has been received!</p>

    <div style="text-align:left; margin:20px 0;">
      <p><strong>Date:</strong> <?= date('j M Y, H:i', strtotime($payment['paid_at'])) ?></p>
      <p><strong>Payment:</strong> PHP <?= number_format((float)$payment['amount'], 2) ?> From <?= htmlspecialchars($payment['full_name']) ?></p>
    </div>
    <hr class="divider" style="margin:22px 0">
    <div class="flex-between"><span>Subtotal</span><span>PHP <?= number_format((float)$payment['amount'], 2) ?></span></div>
    <div class="flex-between" style="font-weight:800"><span>Total</span><span>PHP <?= number_format((float)$payment['amount'], 2) ?></span></div>
    <p class="text-gray mt-24">If you need help with anything, don't hesitate to send us an email: <a class="link-blue" href="mailto:silidtrack@google.com">silidtrack@google.com</a></p>

    <button class="btn btn-primary mt-24" onclick="window.print()">Save Receipt</button>
    <p class="text-gray" style="font-size:.8rem; margin-top:8px">Transaction ID: <?= htmlspecialchars($payment['transaction_id']) ?></p>
  </div>
</div>
</body>
</html>
