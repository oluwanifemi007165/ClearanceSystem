<?php
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); respond(false, 'Method not allowed.'); }

$b    = body();
$role = strtolower(trim($b['role'] ?? ''));

// STUDENT LOGIN
if ($role === 'student') {
    $matric = trim($b['matric_number'] ?? '');
    if (!$matric) respond(false, 'Matric number is required.');

    $stmt = db()->prepare(
        "SELECT id, full_name, matric_number, department, level FROM students WHERE matric_number = ? LIMIT 1"
    );
    $stmt->bind_param('s', $matric);
    $stmt->execute();
    $s = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$s) respond(false, 'Matric number not found. Please contact the Academic Office.');

    // Check payment
    $stmt = db()->prepare("SELECT id FROM payments WHERE student_id = ? AND status = 'success' LIMIT 1");
    $stmt->bind_param('i', $s['id']);
    $stmt->execute();
    $paid = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Check form submitted
    $stmt = db()->prepare("SELECT id FROM clearance_forms WHERE student_id = ? LIMIT 1");
    $stmt->bind_param('i', $s['id']);
    $stmt->execute();
    $formDone = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Start session
    $_SESSION['role']       = 'student';
    $_SESSION['student_id'] = $s['id'];
    $_SESSION['matric']     = $s['matric_number'];
    $_SESSION['full_name']  = $s['full_name'];

    respond(true, 'Login successful.', [
        'role'           => 'student',
        'full_name'      => $s['full_name'],
        'matric'         => $s['matric_number'],
        'department'     => $s['department'],
        'level'          => $s['level'],
        'payment_status' => $paid     ? 'paid'   : 'not_paid',
        'form_submitted' => $formDone ? 'true'   : 'false',
        'redirect'       => 'dashboard.html',
    ]);
}

//  OFFICE LOGIN 
if ($role === 'office') {
    $office   = trim($b['office']   ?? '');
    $password = trim($b['password'] ?? '');
    if (!$office || !$password) respond(false, 'Office and password are required.');

    $stmt = db()->prepare("SELECT id, office_name, username, password_hash FROM offices WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $office);
    $stmt->execute();
    $o = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$o) respond(false, 'Office not found.');
    if (!password_verify($password, $o['password_hash'])) respond(false, 'Incorrect password.');

    $_SESSION['role']        = 'office';
    $_SESSION['office_id']   = $o['id'];
    $_SESSION['office']      = $o['username'];
    $_SESSION['office_name'] = $o['office_name'];

    respond(true, 'Login successful.', [
        'role'        => 'office',
        'office'      => $o['username'],
        'office_name' => $o['office_name'],
        'redirect'    => 'office_dashboard.html',
    ]);
}

respond(false, 'Invalid role.');