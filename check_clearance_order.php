<?php

// Direct DB connection — no session needed
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'clearance_db';

$conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) { die('DB Error: ' . $conn->connect_error); }
$conn->set_charset('utf8mb4');

// Get all students with submitted forms
$students = $conn->query(
    "SELECT s.id, s.full_name, s.matric_number
     FROM students s
     JOIN clearance_forms cf ON cf.student_id = s.id
     ORDER BY s.id"
)->fetch_all(MYSQLI_ASSOC);

// Get clearance requests for each student
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8"/>
  <title>Clearance Order Check</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:Arial,sans-serif;background:#f8fafc;padding:24px;color:#0f172a}
    h1{font-size:1.2rem;margin-bottom:4px}
    .sub{color:#94a3b8;font-size:.85rem;margin-bottom:24px}
    .student{background:white;border:1px solid #e2e8f0;border-radius:10px;padding:20px;margin-bottom:20px}
    .student h2{font-size:1rem;font-weight:700;margin-bottom:4px}
    .student .matric{font-size:.8rem;color:#64748b;margin-bottom:14px}
    table{width:100%;border-collapse:collapse;font-size:.85rem}
    th{background:#f1f5f9;padding:8px 12px;text-align:left;border:1px solid #e2e8f0;font-size:.78rem}
    td{padding:8px 12px;border:1px solid #e2e8f0}
    .approved{background:#f0fdf4;color:#16a34a;font-weight:700}
    .rejected{background:#fef2f2;color:#dc2626;font-weight:700}
    .pending{background:#fefce8;color:#854d0e}
    .note{background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:14px;font-size:.82rem;color:#9a3412;margin-top:16px}
    .offices{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:14px;margin-bottom:20px;font-size:.85rem}
    .offices strong{display:block;margin-bottom:8px;color:#1d4ed8}
    .offices ol{padding-left:18px;line-height:2}
  </style>
</head>
<body>
<h1>Clearance Order Checker</h1>
<p class="sub">Shows exact office order in your database vs what the code expects.</p>

<!-- What code expects -->
<div class="offices">
  <strong>What update_clearance.php expects (OFFICE_ORDER array):</strong>
  <ol>
    <li>bursary</li>
    <li>head_of_department</li>
    <li>dean_of_school</li>
    <li>school_office</li>
    <li>student_affairs</li>
    <li>center_of_enterprenur</li>
    <li>admission_office</li>
    <li>accademic_affairs</li>
    <li>rectors_office</li>
  </ol>
</div>

<?php foreach ($students as $student): ?>
<?php
  $rows = $conn->query(
      "SELECT office, status, sort_order
       FROM clearance_requests
       WHERE student_id = {$student['id']}
       ORDER BY sort_order ASC"
  )->fetch_all(MYSQLI_ASSOC);
?>
<div class="student">
  <h2><?= htmlspecialchars($student['full_name']) ?></h2>
  <div class="matric">Matric: <?= htmlspecialchars($student['matric_number']) ?></div>
  <table>
    <thead>
      <tr>
        <th>Sort Order (DB)</th>
        <th>Office Username (DB)</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= $r['sort_order'] ?></td>
        <td><strong><?= htmlspecialchars($r['office']) ?></strong></td>
        <td class="<?= $r['status'] ?>"><?= strtoupper($r['status']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endforeach; ?>

<?php if (empty($students)): ?>
<div class="student"><p style="color:#94a3b8">No students have submitted clearance forms yet.</p></div>
<?php endif; ?>

<div class="note">
  ⚠️ Compare the <strong>Office Username (DB)</strong> column above with the OFFICE_ORDER list at the top.<br>
  If any name is different (e.g. spaces vs underscores), that's the cause of the error.<br>
  Delete this file after fixing.
</div>
</body>
</html>