<?php

header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); respond(false, 'Method not allowed.');
}
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'office') {
    http_response_code(401); respond(false, 'Unauthorized.');
}
if ($_SESSION['office'] !== 'bursary') {
    respond(true, 'OK', ['payments' => []]);
}

$stmt = db()->prepare(
    "SELECT p.id, p.amount, p.tx_ref, p.receipt_file, p.paid_at,
            s.full_name, s.matric_number, s.department,
            DATE_FORMAT(p.paid_at, '%d/%m/%Y %H:%i') AS uploaded_date
     FROM payments p
     JOIN students s ON s.id = p.student_id
     WHERE p.status = 'pending' AND p.receipt_file IS NOT NULL
     ORDER BY p.paid_at ASC"
);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

respond(true, 'OK', ['payments' => $payments]);