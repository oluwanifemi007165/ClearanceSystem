<?php

header('Content-Type: application/json');
require_once 'db.php';
require_once 'paystack_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); respond(false, 'Method not allowed.');
}
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    http_response_code(401); respond(false, 'Unauthorized. Please log in again.');
}

$b   = body();
$ref = trim($b['reference'] ?? '');

if (!$ref) respond(false, 'Payment reference is required.');

$sid = (int) $_SESSION['student_id'];

// Check not already paid
$stmt = db()->prepare(
    "SELECT id FROM payments WHERE student_id = ? AND status = 'success' LIMIT 1"
);
$stmt->bind_param('i', $sid);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $stmt->close(); respond(false, 'Payment already completed.');
}
$stmt->close();

//  Verify with Paystack API 
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => PAYSTACK_VERIFY_URL . rawurlencode($ref),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json',
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$response || $httpCode !== 200) {
    respond(false, 'Could not verify payment with Paystack. Please try again.');
}

$data = json_decode($response, true);

// Check Paystack says payment was successful
if (!$data['status'] || $data['data']['status'] !== 'success') {
    respond(false, 'Payment was not successful. Please try again.');
}

// Check amount matches 
$paidKobo = (int) $data['data']['amount'];
if ($paidKobo < CLEARANCE_AMOUNT_KOBO) {
    respond(false, 'Payment amount does not match. Expected ₦' . number_format(CLEARANCE_AMOUNT_NAIRA, 2));
}

// Check reference not already used
$stmt = db()->prepare("SELECT id FROM payments WHERE tx_ref = ? LIMIT 1");
$stmt->bind_param('s', $ref);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $stmt->close(); respond(false, 'This payment reference has already been used.');
}
$stmt->close();

// Save verified payment to DB 
$amount = CLEARANCE_AMOUNT_NAIRA;
$method = 'paystack';
$stmt   = db()->prepare(
    "INSERT INTO payments (student_id, amount, tx_ref, status, payment_method)
     VALUES (?, ?, ?, 'success', ?)"
);
$stmt->bind_param('iiss', $sid, $amount, $ref, $method);
if (!$stmt->execute()) {
    http_response_code(500); respond(false, 'Could not save payment. Please contact support.');
}
$stmt->close();

//  Send confirmation email to student 
try {
    if (file_exists(__DIR__ . '/email_config.php')) {
        require_once __DIR__ . '/email_config.php';
        $stmt = db()->prepare(
            "SELECT full_name, matric_number AS matric, department, email
             FROM students WHERE id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $sid);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($student && $student['email']) {
            sendEmail(
                $student['email'],
                $student['full_name'],
                'Payment Confirmed — Fill Your Clearance Form Now',
                buildTemplate(
                    '#16A34A', 'Payment Confirmed',
                    'Your Payment Was Successful!',
                    "Dear <strong>{$student['full_name']}</strong>,",
                    "Your clearance fee of <strong>&#8358;" . number_format($amount, 2) . "</strong> has been received successfully via Paystack. You can now log in and fill your clearance form.",
                    $student,
                    'Log in to your dashboard and click Fill Clearance Form.',
                    'Fill Clearance Form'
                )
            );
        }
    }
} catch (Exception $e) {
    error_log('Email failed: ' . $e->getMessage());
}

respond(true, 'Payment verified successfully.', [
    'amount'    => $amount,
    'reference' => $ref,
    'redirect'  => 'clearance_form.html',
]);