<?php

session_start();
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); respond(false, 'Method not allowed.'); }
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'student') { http_response_code(401); respond(false, 'Unauthorized.'); }

$sid = (int) $_SESSION['student_id'];

// Get clearance form
$stmt = db()->prepare("SELECT submitted_at FROM clearance_forms WHERE student_id = ? LIMIT 1");
$stmt->bind_param('i', $sid);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$form) respond(false, 'No clearance form found.');

// Get all office statuses
$stmt = db()->prepare(
    "SELECT office, status, comment, updated_at FROM clearance_requests WHERE student_id = ? ORDER BY sort_order"
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

$approved = count(array_filter($statuses, fn($s) => $s['status'] === 'approved'));
$total    = 9;

respond(true, 'OK', [
    'clearance_id'   => 'CLR' . str_pad($sid, 3, '0', STR_PAD_LEFT),
    'submitted_at'   => $form['submitted_at'],
    'statuses'       => $statuses,
    'approved_count' => $approved,
    'total_offices'  => $total,
    'overall_status' => $approved === $total ? 'completed' : 'in_progress',
]);