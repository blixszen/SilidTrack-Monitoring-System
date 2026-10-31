<?php
/**
 * SilidTrack - Unpaid Balance Actions (Mark Paid / Waive)
 */
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$db = get_db();
$action = $_GET['action'] ?? '';

if ($action === 'mark_paid') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false], 405);
    
    $studentPk = (int)($_POST['student_pk'] ?? 0);
    $feeId = (int)($_POST['fee_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    
    if (!$studentPk || !$feeId || $amount <= 0) {
        json_response(['ok' => false, 'error' => 'Invalid parameters.'], 400);
    }
    
    // Check if payment already exists
    $stmt = $db->prepare('SELECT payment_id FROM payments WHERE student_pk = ? AND fee_id = ? AND status = "Paid"');
    $stmt->bind_param('ii', $studentPk, $feeId);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        json_response(['ok' => false, 'error' => 'Already marked as paid.'], 400);
    }
    
    // Create payment record
    $transactionId = 'MANUAL-' . strtoupper(bin2hex(random_bytes(6)));
    $adminId = current_admin_id();
    $now = ph_time_now()->format('Y-m-d H:i:s');
    
    $stmt = $db->prepare('
        INSERT INTO payments (transaction_id, student_pk, fee_id, amount, status, paid_at, created_at)
        VALUES (?, ?, ?, ?, "Paid", ?, ?)
    ');
    $stmt->bind_param('siidss', $transactionId, $studentPk, $feeId, $amount, $now, $now);
    $stmt->execute();
    
    json_response(['ok' => true, 'payment_id' => $stmt->insert_id, 'transaction_id' => $transactionId]);
}

if ($action === 'waive') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok' => false], 405);
    
    $studentPk = (int)($_POST['student_pk'] ?? 0);
    $feeId = (int)($_POST['fee_id'] ?? 0);
    
    if (!$studentPk || !$feeId) {
        json_response(['ok' => false, 'error' => 'Invalid parameters.'], 400);
    }
    
    // Create a "Waived" payment record (status = Failed to indicate no money collected)
    // Or we could add a "Waived" status to payments table. For now, use a special transaction ID.
    $transactionId = 'WAIVED-' . strtoupper(bin2hex(random_bytes(6)));
    $adminId = current_admin_id();
    $now = ph_time_now()->format('Y-m-d H:i:s');
    
    // Get fee amount
    $stmt = $db->prepare('SELECT amount FROM fees WHERE fee_id = ?');
    $stmt->bind_param('i', $feeId);
    $stmt->execute();
    $fee = $stmt->get_result()->fetch_assoc();
    $amount = (float)($fee['amount'] ?? 0);
    
    $stmt = $db->prepare('
        INSERT INTO payments (transaction_id, student_pk, fee_id, amount, status, paid_at, created_at)
        VALUES (?, ?, ?, ?, "Failed", ?, ?)
    ');
    $stmt->bind_param('siidss', $transactionId, $studentPk, $feeId, $amount, $now, $now);
    $stmt->execute();
    
    json_response(['ok' => true, 'payment_id' => $stmt->insert_id]);
}

json_response(['ok' => false, 'error' => 'Unknown action.'], 400);