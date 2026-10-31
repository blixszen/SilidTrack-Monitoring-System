<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
require_once __DIR__ . '/../includes/settings.php';
require_admin();
$db = get_db();

$fees = $db->query('SELECT * FROM fees WHERE is_active = 1 ORDER BY due_date ASC')->fetch_all(MYSQLI_ASSOC);

$paymongo = paymongo_keys($db);

$active_page = 'fees';
$role = 'admin';
$display_name = $_SESSION['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>SilidTrack — Fees & Payments</title>
<link rel="stylesheet" href="<?= url("/assets/css/style.css") ?>">
</head>
<body>
<div class="page-wrap">
  <?php include __DIR__ . '/../includes/navbar.php'; ?>

  <h1 class="page-title">Fees & Payments</h1>

  <div class="two-col">
    <div class="card">
      <span class="pill-title"><?= icon('plus', 'icon', 18) ?> Add New Fee</span>
      <form id="addFeeForm" class="mt-24">
        <label class="field-label"><?= icon('clipboard', 'icon', 15) ?> Title</label>
        <input class="field-input" name="title" placeholder="e.g. Laboratory Fee" required>
        <label class="field-label"><?= icon('wallet', 'icon', 15) ?> Amount (PHP)</label>
        <input class="field-input" name="amount" type="number" step="0.01" min="0" placeholder="0.00" required>
        <label class="field-label"><?= icon('calendar', 'icon', 15) ?> Due Date</label>
        <input class="field-input" name="due_date" type="date" required>
        <div class="form-actions">
          <label class="checkbox-row"><input type="checkbox" required> Confirm Fee</label>
          <button class="btn btn-primary" type="submit">Publish Fee</button>
        </div>
      </form>
    </div>

    <div class="card">
      <span class="pill-title"><?= icon('wallet', 'icon', 18) ?> Published Fee</span>
      <table class="data-table mt-24">
        <thead>
          <tr><th>Title</th><th>Amount</th><th>Due Date</th><th style="width:96px"></th></tr>
        </thead>
        <tbody id="feeTableBody">
          <?php foreach ($fees as $f): ?>
            <tr>
              <td><?= htmlspecialchars($f['title']) ?></td>
              <td class="num">PHP <?= number_format((float)$f['amount'], 2) ?></td>
              <td><?= date('D, M j, Y', strtotime($f['due_date'])) ?></td>
              <td>
                <div class="row-actions">
                  <button class="btn btn-success btn-icon" type="button" title="Edit"
                          onclick='editFee(<?= json_encode([
                              "id" => (int)$f['fee_id'],
                              "title" => $f['title'],
                              "amount" => (float)$f['amount'],
                              "due_date" => $f['due_date'],
                          ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                    <?= icon('edit', 'icon', 17) ?>
                  </button>
                  <button class="btn btn-danger btn-icon" type="button" title="Delete"
                          onclick="deleteFee(<?= (int)$f['fee_id'] ?>)">
                    <?= icon('trash', 'icon', 17) ?>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$fees): ?>
            <tr class="empty-row"><td colspan="4">No active fees published.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card mt-24">
    <div class="card-head">
      <?= icon('lock', 'icon', 20) ?>
      <h3>PayMongo API Keys</h3>
    </div>
    <form id="paymongoAccountForm" class="two-col">
      <div>
        <label class="field-label">Secret Key (sk_test_...)</label>
        <input class="field-input" name="paymongo_secret_key" value="<?= htmlspecialchars($paymongo['secret']) ?>" placeholder="sk_test_...">
      </div>
      <div>
        <label class="field-label">Public Key (pk_test_...)</label>
        <input class="field-input" name="paymongo_public_key" value="<?= htmlspecialchars($paymongo['public']) ?>" placeholder="pk_test_...">
      </div>
    </form>
    <button class="btn btn-primary" type="button" onclick="savePaymongoAccount()">Save PayMongo Settings</button>
  </div>
</div>

<div class="modal-overlay" id="editFeeModal">
  <div class="modal-box">
    <span class="modal-title">Edit Fee</span>
    <form id="editFeeForm">
      <input type="hidden" name="fee_id" id="editFeeId">
      <label class="field-label">Title</label>
      <input class="field-input" name="title" id="editFeeTitle" required>
      <label class="field-label">Amount (PHP)</label>
      <input class="field-input" name="amount" id="editFeeAmount" type="number" step="0.01" min="0" required>
      <label class="field-label">Due Date</label>
      <input class="field-input" name="due_date" id="editFeeDue" type="date" required>
      <div class="form-actions">
        <button class="btn btn-muted" type="button" onclick="closeModal('editFeeModal')">Cancel</button>
        <button class="btn btn-primary" type="submit">Save Fee</button>
      </div>
    </form>
  </div>
</div>

<script>window.APP_BASE = "<?= APP_BASE_PATH ?>";</script>
<script src="<?= url("/assets/js/admin.js") ?>"></script>
</body>
</html>
