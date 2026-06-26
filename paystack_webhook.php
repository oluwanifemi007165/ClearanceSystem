<?php

require_once 'db.php';
require_once 'paystack_config.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

// Verify the request is from Paystack using signature
$input     = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
$expected  = hash_hmac('sha512', $input, PAYSTACK_SECRET_KEY);

if ($signature !== $expected) {
    http_response_code(401);
    error_log('Paystack webhook: invalid signature');
    exit;
}

$event = json_decode($input, true);

// Only handle successful charge events
if (($event['event'] ?? '') !== 'charge.success') {
    http_response_code(200); exit; 
}

$data      = $event['data'];
$ref       = $data['reference']        ?? '';
$paidKobo  = (int)($data['amount']     ?? 0);
$email     = $data['customer']['email'] ?? '';
$metadata  = $data['metadata']         ?? [];
$studentId = intval($metadata['student_id'] ?? 0);

if (!$ref || $paidKobo < CLEARANCE_AMOUNT_KOBO) {
    http_response_code(200); exit;
}

// Check reference not already saved
$stmt = db()->prepare("SELECT id FROM payments WHERE tx_ref = ? LIMIT 1");
$stmt->bind_param('s', $ref);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($exists) { http_response_code(200); exit; } // Already processed

// Find student by ID from metadata or by email
if (!$studentId && $email) {
    $stmt = db()->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $s = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $studentId = $s['id'] ?? 0;
}

if (!$studentId) { http_response_code(200); exit; }

// Check not already paid
$stmt = db()->prepare(
    "SELECT id FROM payments WHERE student_id = ? AND status = 'success' LIMIT 1"
);
$stmt->bind_param('i', $studentId);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $stmt->close(); http_response_code(200); exit;
}
$stmt->close();

// Save payment
$amount = CLEARANCE_AMOUNT_NAIRA;
$method = 'paystack';
$stmt   = db()->prepare(
    "INSERT INTO payments (student_id, amount, tx_ref, status, payment_method)
     VALUES (?, ?, ?, 'success', ?)"
);
$stmt->bind_param('iiss', $studentId, $amount, $ref, $method);
$stmt->execute();
$stmt->close();

http_response_code(200);
echo 'OK';