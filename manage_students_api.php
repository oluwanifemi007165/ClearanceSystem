<?php
/**
 * manage_students_api.php
 * Backend API for manage_students.html / manage_students.js
 * Handles: login, list, add, edit, delete, bulk_import
 */
session_start();
header('Content-Type: application/json');
require_once 'db.php';

define('ADMIN_PASSWORD', 'admin123'); // change this

function out(bool $ok, string $msg, array $extra = []) {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
}

// Handle GET (list students) 
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'list') {
    if (empty($_SESSION['manage_students_auth'])) out(false, 'Not authenticated.');
    $students = db()->query("SELECT * FROM students ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
    out(true, 'OK', ['students' => $students]);
}

// Handle GET (list pending registration requests) 
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'list_requests') {
    if (empty($_SESSION['manage_students_auth'])) out(false, 'Not authenticated.');
    $requests = db()->query(
        "SELECT * FROM registration_requests WHERE status = 'pending' ORDER BY submitted_at ASC"
    )->fetch_all(MYSQLI_ASSOC);
    out(true, 'OK', ['requests' => $requests]);
}

//  Handle CSV bulk import (multipart form, not JSON) 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_import') {
    if (empty($_SESSION['manage_students_auth'])) out(false, 'Not authenticated.');

    if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        out(false, 'Please select a CSV file.');
    }

    $imported = 0; $skipped = 0;
    if (($handle = fopen($_FILES['csv_file']['tmp_name'], 'r')) !== false) {
        $headers = null;
        $stmt = db()->prepare(
            "INSERT IGNORE INTO students (full_name, matric_number, department, level, email)
             VALUES (?,?,?,?,?)"
        );
        while (($row = fgetcsv($handle)) !== false) {
            if (!$headers) { $headers = array_map('strtolower', array_map('trim', $row)); continue; }
            if (empty(array_filter($row))) continue;
            $mapped = array_combine($headers, $row);
            $name   = trim($mapped['full_name']     ?? $mapped['name']   ?? '');
            $matric = trim($mapped['matric_number'] ?? $mapped['matric'] ?? '');
            $dept   = trim($mapped['department']    ?? '');
            $level  = trim($mapped['level']         ?? '');
            $email  = trim($mapped['email']         ?? '');
            if (!$name || !$matric) { $skipped++; continue; }
            $stmt->bind_param('sssss', $name, $matric, $dept, $level, $email);
            $stmt->execute();
            if ($stmt->affected_rows > 0) $imported++; else $skipped++;
        }
        $stmt->close();
        fclose($handle);
    }

    out(true, "Import complete: $imported student(s) added, $skipped skipped (duplicates or missing data).");
}

// ── Handle JSON POST requests ────────────────────────────────
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

// ── Login ─────────────────────────────────────────────────────
if ($action === 'login') {
    if (($input['password'] ?? '') === ADMIN_PASSWORD) {
        $_SESSION['manage_students_auth'] = true;
        out(true, 'Login successful.');
    } else {
        out(false, 'Wrong password.');
    }
}

// All actions below require authentication
if (empty($_SESSION['manage_students_auth'])) out(false, 'Not authenticated.');

// ── Add single student ──────────────────────────────────────
if ($action === 'add') {
    $name   = trim($input['full_name']     ?? '');
    $matric = trim($input['matric_number'] ?? '');
    $dept   = trim($input['department']    ?? '');
    $level  = trim($input['level']         ?? '');
    $email  = trim($input['email']         ?? '');

    if (!$name || !$matric) out(false, 'Name and Matric Number are required.');

    $stmt = db()->prepare(
        "INSERT INTO students (full_name, matric_number, department, level, email)
         VALUES (?,?,?,?,?)"
    );
    $stmt->bind_param('sssss', $name, $matric, $dept, $level, $email);

    if ($stmt->execute()) {
        out(true, "Student '$name' ($matric) added successfully.");
    } else {
        out(false, 'Could not add student. Matric number may already exist.');
    }
}

// ── Edit student ─────────────────────────────────────────────
if ($action === 'edit') {
    $id     = intval($input['student_id'] ?? 0);
    $name   = trim($input['full_name']     ?? '');
    $matric = trim($input['matric_number'] ?? '');
    $dept   = trim($input['department']    ?? '');
    $level  = trim($input['level']         ?? '');
    $email  = trim($input['email']         ?? '');

    if (!$id || !$name || !$matric) out(false, 'Missing required fields.');

    $stmt = db()->prepare(
        "UPDATE students SET full_name=?, matric_number=?, department=?, level=?, email=?
         WHERE id=?"
    );
    $stmt->bind_param('sssssi', $name, $matric, $dept, $level, $email, $id);

    if ($stmt->execute()) {
        out(true, 'Student updated successfully.');
    } else {
        out(false, 'Could not update — matric number may already be in use.');
    }
}

// ── Delete student (protected) ───────────────────────────────
if ($action === 'delete') {
    $id = intval($input['student_id'] ?? 0);
    if (!$id) out(false, 'Invalid student.');

    // Check if student has any payment or clearance history
    $stmt = db()->prepare("SELECT COUNT(*) as c FROM payments WHERE student_id=?");
    $stmt->bind_param('i', $id); $stmt->execute();
    $hasPayments = $stmt->get_result()->fetch_assoc()['c'] > 0;
    $stmt->close();

    $stmt = db()->prepare("SELECT COUNT(*) as c FROM clearance_forms WHERE student_id=?");
    $stmt->bind_param('i', $id); $stmt->execute();
    $hasForm = $stmt->get_result()->fetch_assoc()['c'] > 0;
    $stmt->close();

    if ($hasPayments || $hasForm) {
        out(false, 'Cannot delete — this student already has payment or clearance records. Protected to prevent data loss.');
    }

    $stmt = db()->prepare("DELETE FROM students WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    out(true, 'Student deleted successfully.');
}

// ── Approve registration request ─────────────────────────────
if ($action === 'approve_request') {
    $reqId = intval($input['request_id'] ?? 0);
    if (!$reqId) out(false, 'Invalid request.');

    $stmt = db()->prepare("SELECT * FROM registration_requests WHERE id = ? AND status = 'pending' LIMIT 1");
    $stmt->bind_param('i', $reqId);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$req) out(false, 'Request not found or already processed.');

    // Check matric not already a student (race condition safety)
    $stmt = db()->prepare("SELECT id FROM students WHERE matric_number = ? LIMIT 1");
    $stmt->bind_param('s', $req['matric_number']);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        out(false, 'A student with this matric number already exists.');
    }
    $stmt->close();

    // Add the student
    $stmt = db()->prepare(
        "INSERT INTO students (full_name, matric_number, department, level, email)
         VALUES (?,?,?,?,?)"
    );
    $stmt->bind_param('sssss', $req['full_name'], $req['matric_number'], $req['department'], $req['level'], $req['email']);
    if (!$stmt->execute()) {
        out(false, 'Could not create student record.');
    }
    $stmt->close();

    // Mark request as approved
    $stmt = db()->prepare("UPDATE registration_requests SET status='approved', reviewed_at=NOW() WHERE id=?");
    $stmt->bind_param('i', $reqId);
    $stmt->execute();
    $stmt->close();

    // Notify student by email
    try {
        if (file_exists(__DIR__ . '/email_config.php')) {
            require_once __DIR__ . '/email_config.php';
            sendEmail(
                $req['email'], $req['full_name'],
                'Your Clearance Access Has Been Approved',
                buildTemplate('#16A34A', 'Access Approved',
                    'You Can Now Log In',
                    "Dear <strong>{$req['full_name']}</strong>,",
                    'Your request for clearance access has been approved. You can now log in using your matric number.',
                    ['full_name' => $req['full_name'], 'matric' => $req['matric_number'], 'department' => $req['department']],
                    'Log in to begin your clearance process.', 'Login Now'
                )
            );
        }
    } catch (Exception $e) { error_log($e->getMessage()); }

    out(true, "Request approved. {$req['full_name']} can now log in.");
}

// ── Reject registration request ──────────────────────────────
if ($action === 'reject_request') {
    $reqId = intval($input['request_id'] ?? 0);
    $note  = trim($input['note'] ?? '');
    if (!$reqId) out(false, 'Invalid request.');

    $stmt = db()->prepare("SELECT * FROM registration_requests WHERE id = ? AND status = 'pending' LIMIT 1");
    $stmt->bind_param('i', $reqId);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$req) out(false, 'Request not found or already processed.');

    $stmt = db()->prepare(
        "UPDATE registration_requests SET status='rejected', rejection_note=?, reviewed_at=NOW() WHERE id=?"
    );
    $stmt->bind_param('si', $note, $reqId);
    $stmt->execute();
    $stmt->close();

    out(true, 'Request rejected.');
}

out(false, 'Invalid action.');