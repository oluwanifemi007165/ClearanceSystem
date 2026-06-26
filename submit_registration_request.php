<?php
/**
 * submit_registration_request.php
 * Public endpoint — no login required.
 * Saves a request from a former/returning student who wants
 * their account reactivated for clearance.
 */
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); respond(false, 'Method not allowed.');
}

$b      = body();
$name   = trim($b['full_name']     ?? '');
$matric = trim($b['matric_number'] ?? '');
$dept   = trim($b['department']    ?? '');
$level  = trim($b['level']         ?? '');
$email  = trim($b['email']         ?? '');
$phone  = trim($b['phone']         ?? '');
$reason = trim($b['reason']        ?? '');

if (!$name || !$matric || !$dept || !$level || !$email) {
    respond(false, 'Please fill in all required fields.');
}

// Check if student already exists in the main students table
$stmt = db()->prepare("SELECT id FROM students WHERE matric_number = ? LIMIT 1");
$stmt->bind_param('s', $matric);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $stmt->close();
    respond(false, 'This matric number is already registered. You can log in directly.');
}
$stmt->close();

// Check if a pending request already exists for this matric
$stmt = db()->prepare(
    "SELECT id FROM registration_requests WHERE matric_number = ? AND status = 'pending' LIMIT 1"
);
$stmt->bind_param('s', $matric);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    $stmt->close();
    respond(false, 'A request for this matric number is already pending review.');
}
$stmt->close();

// Save the request
$stmt = db()->prepare(
    "INSERT INTO registration_requests (full_name, matric_number, department, level, email, phone, reason)
     VALUES (?,?,?,?,?,?,?)"
);
$stmt->bind_param('sssssss', $name, $matric, $dept, $level, $email, $phone, $reason);

if (!$stmt->execute()) {
    http_response_code(500); respond(false, 'Could not submit request. Please try again.');
}
$stmt->close();

respond(true, 'Request submitted successfully.');