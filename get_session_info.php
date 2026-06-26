<?php

header('Content-Type: application/json');
require_once 'db.php';

if (empty($_SESSION['role'])) {
    http_response_code(401); respond(false, 'Not logged in.');
}

if ($_SESSION['role'] === 'student') {
    $sid  = (int) $_SESSION['student_id'];
    $stmt = db()->prepare(
        "SELECT full_name, matric_number, department, level, email
         FROM students WHERE id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $sid);
    $stmt->execute();
    $s = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    respond(true, 'OK', [
        'role'        => 'student',
        'student_id'  => $sid,
        'full_name'   => $s['full_name']     ?? '',
        'matric'      => $s['matric_number'] ?? '',
        'department'  => $s['department']    ?? '',
        'level'       => $s['level']         ?? '',
        'email'       => $s['email']         ?? '',
    ]);
}

if ($_SESSION['role'] === 'office') {
    respond(true, 'OK', [
        'role'        => 'office',
        'office'      => $_SESSION['office']      ?? '',
        'office_name' => $_SESSION['office_name'] ?? '',
    ]);
}

respond(false, 'Unknown role.');