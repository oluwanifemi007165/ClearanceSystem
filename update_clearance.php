<?php
/**
 * update_clearance.php
 * Office approves or rejects a student clearance.
 * Email is wrapped in try-catch so approval never fails due to email issues.
 */
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); respond(false, 'Method not allowed.');
}
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'office') {
    http_response_code(401); respond(false, 'Unauthorized.');
}

$b       = body();
$sid     = intval($b['student_id'] ?? 0);
$status  = trim($b['status']       ?? '');
$comment = trim($b['comment']      ?? '');
$office  = $_SESSION['office'];

if (!$sid || !in_array($status, ['approved', 'rejected'])) {
    respond(false, 'Invalid data.');
}
if ($status === 'rejected' && !$comment) {
    respond(false, 'Rejection reason is required.');
}

// Office order
$officeOrder = [
    'bursary', 'head_of_department', 'dean_of_school', 'school_office',
    'student_affairs', 'center_of_enterprenur', 'admission_office',
    'library', 'accademic_affairs'
];

$myIndex = array_search($office, $officeOrder);

// Enforce sequential rule
if ($myIndex > 0) {
    $prev = $officeOrder[$myIndex - 1];
    $stmt = db()->prepare("SELECT status FROM clearance_requests WHERE student_id = ? AND office = ? LIMIT 1");
    $stmt->bind_param('is', $sid, $prev);
    $stmt->execute();
    $prevRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$prevRow || $prevRow['status'] !== 'approved') {
        respond(false, "The previous office has not approved this student yet.");
    }
}

// Check not already processed
$stmt = db()->prepare("SELECT status FROM clearance_requests WHERE student_id = ? AND office = ? LIMIT 1");
$stmt->bind_param('is', $sid, $office);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($existing && in_array($existing['status'], ['approved', 'rejected'])) {
    respond(false, 'You have already processed this clearance.');
}

// Update DB
$order = $myIndex + 1;
$stmt  = db()->prepare(
    "INSERT INTO clearance_requests (student_id, office, status, comment, sort_order)
     VALUES (?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE status = VALUES(status), comment = VALUES(comment), updated_at = NOW()"
);
$stmt->bind_param('isssi', $sid, $office, $status, $comment, $order);
if (!$stmt->execute()) {
    http_response_code(500); respond(false, 'Database error. Please try again.');
}
$stmt->close();

// Get student info for email
$stmt = db()->prepare(
    "SELECT full_name, matric_number AS matric, department, email
     FROM students WHERE id = ? LIMIT 1"
);
$stmt->bind_param('i', $sid);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Send email — wrapped in try-catch so approval never fails due to email
try {
    if (file_exists(__DIR__ . '/email_config.php')) {
        require_once __DIR__ . '/email_config.php';
        if ($student) {
            if ($status === 'approved') {
                notifyNextOffice($office, $student);
            } else {
                notifyStudentRejection($office, $comment, $student);
            }
        }
    }
} catch (Exception $e) {
    error_log('Email notification failed: ' . $e->getMessage());
}

respond(true, 'Clearance ' . $status . ' successfully.');