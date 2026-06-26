<?php
/**
 * submit_form.php
 * Saves clearance form and creates office request rows.
 * Email notification is wrapped in try-catch so if email
 * fails for any reason, the form submission still succeeds.
 */
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); respond(false, 'Method not allowed.');
}
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    http_response_code(401); respond(false, 'Unauthorized. Please log in again.');
}

$b         = body();
$sid       = (int) $_SESSION['student_id'];
$reason    = trim($b['reason']    ?? '');
$address   = trim($b['address']   ?? '');
$phone     = trim($b['phone']     ?? '');
$emergency = trim($b['emergency'] ?? '');

if (!$reason || !$address || !$phone || !$emergency) {
    respond(false, 'All fields are required.');
}

// Must have paid
$stmt = db()->prepare("SELECT id FROM payments WHERE student_id = ? AND status = 'success' LIMIT 1");
$stmt->bind_param('i', $sid);
$stmt->execute();
$hasPaid = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$hasPaid) {
    respond(false, 'Payment required before submitting clearance form.');
}

// Must not have already submitted
$stmt = db()->prepare("SELECT id FROM clearance_forms WHERE student_id = ? LIMIT 1");
$stmt->bind_param('i', $sid);
$stmt->execute();
$alreadySubmitted = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($alreadySubmitted) {
    respond(false, 'Clearance form already submitted.');
}

// Save clearance form
$stmt = db()->prepare(
    "INSERT INTO clearance_forms (student_id, reason, address, phone, emergency_contact)
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param('issss', $sid, $reason, $address, $phone, $emergency);
if (!$stmt->execute()) {
    http_response_code(500);
    respond(false, 'Could not save form. Please try again.');
}
$stmt->close();

// Office order
$officeOrder = [
    'bursary', 'head_of_department', 'dean_of_school', 'school_office',
    'student_affairs', 'center_of_enterprenur', 'admission_office',
    'library', 'accademic_affairs'
];

// Create one clearance_request row per office
$stmt = db()->prepare(
    "INSERT IGNORE INTO clearance_requests (student_id, office, status, sort_order)
     VALUES (?, ?, 'pending', ?)"
);
foreach ($officeOrder as $i => $office) {
    $order = $i + 1;
    $stmt->bind_param('isi', $sid, $office, $order);
    $stmt->execute();
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

// Send email
try {
    if (file_exists(__DIR__ . '/email_config.php')) {
        require_once __DIR__ . '/email_config.php';
        if ($student) {
            notifyFirstOffice($student);
        }
    }
} catch (Exception $e) {
    error_log('Email notification failed: ' . $e->getMessage());
}

respond(true, 'Clearance form submitted successfully.', [
    'redirect' => 'dashboard.html'
]);