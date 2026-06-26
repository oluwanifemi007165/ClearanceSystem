<?php
/**
 * resubmit_clearance.php
 * Allows a student to resubmit their clearance after a rejection.
 * Resets ONLY the rejected office's status back to 'pending'
 * so the clearance can move forward again.
 *
 * POST body: { "office": "bursary" }
 */
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); respond(false, 'Method not allowed.');
}
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    http_response_code(401); respond(false, 'Unauthorized. Please log in again.');
}

$b      = body();
$office = trim($b['office'] ?? '');
$sid    = (int) $_SESSION['student_id'];

if (!$office) respond(false, 'Office is required.');

// Check that this office actually rejected this student
$stmt = db()->prepare(
    "SELECT id, status FROM clearance_requests
     WHERE student_id = ? AND office = ? LIMIT 1"
);
$stmt->bind_param('is', $sid, $office);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) respond(false, 'No clearance request found for this office.');
if ($row['status'] !== 'rejected') respond(false, 'This clearance was not rejected — no resubmission needed.');

// Reset ONLY this office back to pending
$stmt = db()->prepare(
    "UPDATE clearance_requests
     SET status = 'pending', comment = NULL, updated_at = NOW()
     WHERE student_id = ? AND office = ?"
);
$stmt->bind_param('is', $sid, $office);
if (!$stmt->execute()) {
    http_response_code(500); respond(false, 'Could not resubmit. Please try again.');
}
$stmt->close();

respond(true, 'Clearance resubmitted successfully. The office will review your request again.');