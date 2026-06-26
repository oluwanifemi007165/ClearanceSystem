<?php
/**
 * check_student_records.php
 * Returns ONLY the records relevant to the requesting office.
 * Bursary → only sees outstanding fees
 * Library → only sees unreturned books
 *
 * GET params: ?student_id=5
 */
header('Content-Type: application/json');
require_once 'db.php';
require_once 'external_db.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'office') {
    http_response_code(401); respond(false, 'Unauthorized.');
}

$sid    = intval($_GET['student_id'] ?? 0);
$office = $_SESSION['office'] ?? '';

if (!$sid) respond(false, 'Student ID is required.');

// Get the student's matric number from main DB
$stmt = db()->prepare("SELECT matric_number FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $sid);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) respond(false, 'Student not found.');

$matric = $student['matric_number'];

// Only check the records relevant to THIS office 
$outstandingFees = [];
$libraryRecords  = [];

if ($office === 'bursary') {
    $outstandingFees = checkOutstandingFees($matric);
} elseif ($office === 'library') {
    $libraryRecords = checkLibraryRecords($matric);
}

$hasFees   = count($outstandingFees) > 0;
$hasBooks  = count($libraryRecords)  > 0;
$totalOwed = array_sum(array_column($outstandingFees, 'amount'));

respond(true, 'OK', [
    'matric_number'    => $matric,
    'office'           => $office,
    'has_outstanding'  => $hasFees,
    'has_unreturned'   => $hasBooks,
    'outstanding_fees' => $outstandingFees,
    'library_records'  => $libraryRecords,
    'total_owed'       => $totalOwed,
]);