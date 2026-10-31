<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/settings.php';
require_student();
$db = get_db();
$action = $_GET['action'] ?? '';

$hasSessionColumn = column_exists($db, 'payments', 'paymongo_session_id');

if ($action === 'create_checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $fee_id = (int)($_POST['fee_id'] ?? 0);
    $pk = current_student_pk();

    $stmt = $db->prepare('SELECT amount, title FROM fees WHERE fee_id = ? AND is_active = 1');
    $stmt->bind_param('i', $fee_id);
    $stmt->execute();
    $fee = $stmt->get_result()->fetch_assoc();
    if (!$fee) json_response(['ok' => false, 'error' => 'Fee not found.'], 404);

    // Admin PayMongo secret key (admins column or settings store)
    $secret = paymongo_keys($db)['secret'];
    if ($secret === '') {
        json_response(['ok' => false, 'error' => 'PayMongo not configured. Please contact admin.'], 500);
    }

    // Create PayMongo Checkout Session
    $amount_cents = (int)round($fee['amount'] * 100); // PayMongo expects amount in centavos
    $payload = json_encode([
        'data' => [
            'attributes' => [
                'send_email_receipt' => false,
                'show_description' => true,
                'show_line_items' => true,
                'description' => $fee['title'],
                'line_items' => [[
                    'currency' => 'PHP',
                    'amount' => $amount_cents,
                    'name' => $fee['title'],
                    'quantity' => 1,
                ]],
                'payment_method_types' => ['card', 'gcash', 'grab_pay', 'billease'],
                'success_url' => url('/student/fees_receipt.php?fee_id=' . $fee_id . '&session_id={checkout_session_id}'),
                'cancel_url' => url('/student/fees.php'),
            ]
        ]
    ]);

    $ch = curl_init('https://api.paymongo.com/v1/checkout_sessions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . base64_encode($secret . ':'),
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);
    if ($http_code >= 400 || !isset($result['data']['attributes']['checkout_url'])) {
        error_log('PayMongo error: ' . $response);
        json_response(['ok' => false, 'error' => 'Failed to create checkout session.'], 500);
    }

    $checkout_url = $result['data']['attributes']['checkout_url'];
    $session_id = $result['data']['id'];

    // Store pending payment (with the checkout session id when the column exists)
    $transaction_id = 'PM-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    if ($hasSessionColumn) {
        $stmt = $db->prepare('INSERT INTO payments (transaction_id, student_pk, fee_id, amount, status, paid_at, paymongo_session_id) VALUES (?, ?, ?, ?, "Pending", NULL, ?)');
        $stmt->bind_param('siids', $transaction_id, $pk, $fee_id, $fee['amount'], $session_id);
    } else {
        $stmt = $db->prepare('INSERT INTO payments (transaction_id, student_pk, fee_id, amount, status, paid_at) VALUES (?, ?, ?, ?, "Pending", NULL)');
        $stmt->bind_param('sidd', $transaction_id, $pk, $fee_id, $fee['amount']);
    }
    $stmt->execute();

    json_response(['ok' => true, 'checkout_url' => $checkout_url, 'transaction_id' => $transaction_id]);
}

if ($action === 'verify' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_id = $_POST['session_id'] ?? '';
    $fee_id = (int)($_POST['fee_id'] ?? 0);
    $pk = current_student_pk();

    if (!$session_id || !$fee_id) {
        json_response(['ok' => false, 'error' => 'Missing parameters.'], 400);
    }

    // Admin PayMongo secret key (admins column or settings store)
    $secret = paymongo_keys($db)['secret'];
    if ($secret === '') {
        json_response(['ok' => false, 'error' => 'PayMongo not configured.'], 500);
    }

    // Verify checkout session with PayMongo
    $ch = curl_init('https://api.paymongo.com/v1/checkout_sessions/' . $session_id);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . base64_encode($secret . ':'),
            'Accept: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);
    if ($http_code >= 400) {
        json_response(['ok' => false, 'error' => 'Failed to verify payment.'], 500);
    }

    $payment_status = $result['data']['attributes']['payment_status'] ?? '';
    if ($payment_status !== 'paid') {
        json_response(['ok' => false, 'error' => 'Payment not completed.'], 400);
    }

    // Update payment record
    if ($hasSessionColumn) {
        $stmt = $db->prepare('UPDATE payments SET status = "Paid", paid_at = NOW() WHERE paymongo_session_id = ? AND student_pk = ? AND fee_id = ?');
        $stmt->bind_param('sii', $session_id, $pk, $fee_id);
    } else {
        $stmt = $db->prepare('UPDATE payments SET status = "Paid", paid_at = NOW() WHERE transaction_id = ? AND student_pk = ? AND fee_id = ? AND status = "Pending"');
        $stmt->bind_param('sii', $session_id, $pk, $fee_id);
    }
    $stmt->execute();

    json_response(['ok' => true]);
}

json_response(['ok' => false, 'error' => 'Unknown action.'], 400);
