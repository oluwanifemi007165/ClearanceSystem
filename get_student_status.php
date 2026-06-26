<?php

header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); respond(false, 'Method not allowed.');
}
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    http_response_code(401); respond(false, 'Unauthorized.');
}

$sid = (int) $_SESSION['student_id'];

// Payment status
$stmt = db()->prepare(
    "SELECT status, payment_method FROM payments
     WHERE student_id = ? ORDER BY id DESC LIMIT 1"
);
$stmt->bind_param('i', $sid);
$stmt->execute();
$payRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

$paymentStatus = 'not_paid';
if ($payRow) {
    if ($payRow['status'] === 'success') $paymentStatus = 'paid';
    if ($payRow['status'] === 'pending') $paymentStatus = 'pending';
}

// Form submitted
$stmt = db()->prepare("SELECT submitted_at FROM clearance_forms WHERE student_id = ? LIMIT 1");
$stmt->bind_param('i', $sid);
$stmt->execute();
$form     = $stmt->get_result()->fetch_assoc();
$stmt->close();
$formDone = (bool) $form;

// Clearance statuses
$stmt = db()->prepare(
    "SELECT office, status, comment, updated_at
     FROM clearance_requests WHERE student_id = ? ORDER BY sort_order"
);
$stmt->bind_param('i', $sid);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$statuses = [];
foreach ($rows as $r) {
    $statuses[$r['office']] = [
        'status'  => $r['status'],
        'comment' => $r['comment'] ?? '',
        'date'    => $r['updated_at'] ? date('M j, Y', strtotime($r['updated_at'])) : '',
    ];
}

respond(true, 'OK', [
    'payment_status' => $paymentStatus,
    'payment_method' => $payRow['payment_method'] ?? '',
    'form_submitted' => $formDone ? 'true' : 'false',
    'submitted_at'   => $form['submitted_at'] ?? null,
    'clearance_id'   => $formDone ? 'CLR' . str_pad($sid, 3, '0', STR_PAD_LEFT) : null,
    'statuses'       => $statuses,
]);