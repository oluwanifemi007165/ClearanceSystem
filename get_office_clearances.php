<?php
/**
 * get_office_clearances.php
 * Returns pending and processed clearances for the logged-in office.
 * Enforces the sequential rule: a student only appears as pending
 * if the PREVIOUS office has approved them.
 */
header('Content-Type: application/json');
require_once 'db.php';

const OFFICE_ORDER = [
    'bursary', 'head_of_department', 'dean_of_school', 'school_office',
    'student_affairs', 'center_of_enterprenur', 'admission_office',
    'library', 'accademic_affairs'
];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); respond(false, 'Method not allowed.'); }
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'office') { http_response_code(401); respond(false, 'Unauthorized.'); }

// Normalize office username — replace spaces with underscores
// Fixes cases where old session stored spaces but DB now has underscores
$office = str_replace(' ', '_', strtolower(trim($_SESSION['office'])));
$_SESSION['office'] = $office; // update session too

$db    = db();
$today = date('Y-m-d');

$myIndex = array_search($office, OFFICE_ORDER);

// If office not found in order — return empty but valid response
if ($myIndex === false) {
    respond(true, 'OK', [
        'office'    => $office,
        'pending'   => [],
        'processed' => [],
        'stats'     => ['pending_count' => 0, 'processed_count' => 0, 'approved_today' => 0],
    ]);
}

//  PENDING
if ($myIndex === 0) {
    // First office: show all students who submitted a form
    $sql = "
        SELECT s.id, s.full_name, s.matric_number, s.department, s.level,
               cf.phone, cf.reason, cf.address, cf.emergency_contact, cf.submitted_at,
               CONCAT('CLR', LPAD(s.id,3,'0')) AS clearance_id
        FROM students s
        JOIN clearance_forms cf ON cf.student_id = s.id
        LEFT JOIN clearance_requests cr ON cr.student_id = s.id AND cr.office = ?
        WHERE cr.status IS NULL OR cr.status = 'pending'
        ORDER BY cf.submitted_at ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $office);
} else {
    // Other offices: only show students where previous office approved
    $prevOffice = OFFICE_ORDER[$myIndex - 1];
    $sql = "
        SELECT s.id, s.full_name, s.matric_number, s.department, s.level,
               cf.phone, cf.reason, cf.address, cf.emergency_contact, cf.submitted_at,
               CONCAT('CLR', LPAD(s.id,3,'0')) AS clearance_id
        FROM students s
        JOIN clearance_forms cf ON cf.student_id = s.id
        JOIN clearance_requests prev ON prev.student_id = s.id AND prev.office = ? AND prev.status = 'approved'
        LEFT JOIN clearance_requests cr ON cr.student_id = s.id AND cr.office = ?
        WHERE cr.status IS NULL OR cr.status = 'pending'
        ORDER BY cf.submitted_at ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ss', $prevOffice, $office);
}
$stmt->execute();
$pending = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

//  PROCESSED 
$sql = "
    SELECT s.id, s.full_name, s.matric_number, s.department, s.level,
           cf.submitted_at,
           CONCAT('CLR', LPAD(s.id,3,'0')) AS clearance_id,
           cr.status, cr.comment, cr.updated_at
    FROM students s
    JOIN clearance_forms cf ON cf.student_id = s.id
    JOIN clearance_requests cr ON cr.student_id = s.id AND cr.office = ?
    WHERE cr.status IN ('approved','rejected')
    ORDER BY cr.updated_at DESC";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $office);
$stmt->execute();
$processed = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Format dates
$fmt = fn($row) => array_merge($row, [
    'submitted_fmt'  => $row['submitted_at'] ? date('d/m/Y', strtotime($row['submitted_at'])) : '',
    'processed_date' => !empty($row['updated_at']) ? date('d/m/Y', strtotime($row['updated_at'])) : '',
]);

$approvedToday = count(array_filter($processed, fn($r) =>
    $r['status'] === 'approved' && substr($r['updated_at'] ?? '', 0, 10) === $today
));

respond(true, 'OK', [
    'office'    => $office,
    'pending'   => array_map($fmt, $pending),
    'processed' => array_map($fmt, $processed),
    'stats'     => [
        'pending_count'   => count($pending),
        'processed_count' => count($processed),
        'approved_today'  => $approvedToday,
    ],
]);