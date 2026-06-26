<?php
/**
 * clearance_certificate.php
 * Generates a printable HTML clearance certificate for fully cleared students.
 * Only accessible when ALL 9 offices have approved.
 */
require_once 'db.php';

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: index.html'); exit;
}

$sid = (int) $_SESSION['student_id'];

// Get student info
$stmt = db()->prepare(
    "SELECT full_name, matric_number, department, level, email
     FROM students WHERE id = ? LIMIT 1"
);
$stmt->bind_param('i', $sid);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) { header('Location: dashboard.html'); exit; }

// Get clearance form
$stmt = db()->prepare("SELECT submitted_at FROM clearance_forms WHERE student_id = ? LIMIT 1");
$stmt->bind_param('i', $sid);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$form) { header('Location: dashboard.html'); exit; }

// Get all office statuses
$stmt = db()->prepare(
    "SELECT office, status, updated_at FROM clearance_requests
     WHERE student_id = ? ORDER BY sort_order"
);
$stmt->bind_param('i', $sid);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check all approved
$totalOffices   = count($rows);
$approvedCount  = count(array_filter($rows, fn($r) => $r['status'] === 'approved'));
$fullyCleared   = $approvedCount === $totalOffices && $totalOffices === 9;

if (!$fullyCleared) {
    header('Location: dashboard.html'); exit;
}

// Office display names
$officeNames = [
    'bursary'               => 'Bursary',
    'head_of_department'    => 'Head of Department',
    'dean_of_school'        => 'Dean of School',
    'school_office'         => 'School Office',
    'student_affairs'       => 'Student Affairs',
    'center_of_enterprenur' => 'Center of Entrepreneur',
    'admission_office'      => 'Admission Office',
    'library'               => 'Library',
    'accademic_affairs'     => 'Academic Affairs',
];

$clearanceId  = 'CLR' . str_pad($sid, 3, '0', STR_PAD_LEFT);
$issuedDate   = date('d F Y');
$submittedDate= date('d F Y', strtotime($form['submitted_at']));

// Find completion date (date Academic Affairs — the final office — approved)
$completionDate = '';
foreach ($rows as $r) {
    if ($r['office'] === 'accademic_affairs' && $r['updated_at']) {
        $completionDate = date('d F Y', strtotime($r['updated_at']));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Clearance Certificate — <?= htmlspecialchars($student['full_name']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--blue:#2563EB;--black:#0F172A;--green:#16A34A;--gray-200:#E2E8F0;--gray-400:#94A3B8;--white:#FFFFFF}
    body{font-family:'Inter',sans-serif;background:#f8fafc;min-height:100vh;color:var(--black);padding:20px}

    /* Top bar — hidden when printing */
    .topbar{max-width:860px;margin:0 auto 20px;display:flex;justify-content:space-between;align-items:center}
    .back-btn{display:flex;align-items:center;gap:7px;background:none;border:1px solid var(--gray-200);border-radius:8px;padding:9px 16px;font-family:'Inter',sans-serif;font-size:.875rem;font-weight:500;cursor:pointer;color:var(--black);text-decoration:none}
    .back-btn:hover{background:#f1f5f9}
    .print-btn{display:flex;align-items:center;gap:7px;background:var(--black);color:white;border:none;border-radius:8px;padding:10px 20px;font-family:'Sora',sans-serif;font-size:.875rem;font-weight:600;cursor:pointer;transition:background .2s}
    .print-btn:hover{background:#1e293b}
    .print-btn svg{width:16px;height:16px}

    /* Certificate container */
    .cert-wrap{max-width:860px;margin:0 auto}
    .certificate{background:var(--white);border:1px solid var(--gray-200);border-radius:16px;padding:0;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)}

    /* Header */
    .cert-header{background:var(--black);padding:36px 48px;text-align:center;position:relative}
    .cert-header::after{content:'';position:absolute;bottom:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#2563EB,#16A34A,#2563EB)}
    .school-name{font-family:'Sora',sans-serif;font-size:1.1rem;font-weight:700;color:white;letter-spacing:.02em;margin-bottom:4px}
    .school-dept{font-size:.82rem;color:rgba(255,255,255,.6);margin-bottom:20px}
    .cert-title{font-family:'Sora',sans-serif;font-size:1.6rem;font-weight:800;color:white;letter-spacing:.04em;margin-bottom:6px}
    .cert-subtitle{font-size:.85rem;color:rgba(255,255,255,.7);letter-spacing:.08em;text-transform:uppercase}

    /* Body */
    .cert-body{padding:40px 48px}
    .cert-id-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;padding-bottom:20px;border-bottom:1px solid var(--gray-200)}
    .cert-id{font-size:.8rem;color:var(--gray-400)}.cert-id strong{color:var(--black);font-size:.95rem}
    .cert-date{font-size:.8rem;color:var(--gray-400);text-align:right}.cert-date strong{color:var(--black);font-size:.95rem;display:block}

    /* Certified statement */
    .cert-statement{text-align:center;margin-bottom:32px}
    .cert-statement p{font-size:.95rem;color:#475569;line-height:1.8;margin-bottom:4px}
    .cert-statement .student-name{font-family:'Sora',sans-serif;font-size:1.8rem;font-weight:800;color:var(--black);margin:12px 0;display:block}
    .cert-statement .matric{font-size:.9rem;color:var(--gray-400);margin-bottom:12px}
    .cert-statement .dept-info{display:inline-flex;align-items:center;gap:16px;background:#f8fafc;border:1px solid var(--gray-200);border-radius:8px;padding:10px 24px;font-size:.875rem;font-weight:600}
    .cert-statement .dept-info span{color:var(--gray-400);font-weight:400;font-size:.8rem;display:block;margin-bottom:2px}

    /* Office approvals table */
    .approvals-section{margin-bottom:32px}
    .approvals-section h3{font-family:'Sora',sans-serif;font-size:.875rem;font-weight:700;color:var(--black);margin-bottom:14px;display:flex;align-items:center;gap:8px}
    .approvals-section h3::after{content:'';flex:1;height:1px;background:var(--gray-200)}
    .approvals-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    .approval-item{display:flex;align-items:center;gap:10px;padding:10px 14px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:8px}
    .approval-check{width:22px;height:22px;border-radius:50%;background:var(--green);display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .approval-check svg{width:12px;height:12px;stroke:white;stroke-width:2.5}
    .approval-name{font-size:.82rem;font-weight:600;color:var(--black);flex:1}
    .approval-date{font-size:.72rem;color:var(--green)}

    /* Completion badge */
    .completion-badge{background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:2px solid #86EFAC;border-radius:12px;padding:20px 28px;text-align:center;margin-bottom:32px}
    .completion-badge svg{width:40px;height:40px;margin:0 auto 10px;display:block;stroke:var(--green);stroke-width:1.5}
    .completion-badge h3{font-family:'Sora',sans-serif;font-size:1.1rem;font-weight:700;color:var(--green);margin-bottom:4px}
    .completion-badge p{font-size:.82rem;color:#16a34a;opacity:.8}

    /* Footer / signatures */
    .cert-footer{border-top:1px solid var(--gray-200);padding:28px 48px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:24px}
    .sig-item{text-align:center}
    .sig-line{height:1px;background:var(--black);margin-bottom:8px;margin-top:40px}
    .sig-name{font-size:.8rem;font-weight:600;color:var(--black)}
    .sig-title{font-size:.72rem;color:var(--gray-400)}

    /* Watermark */
    .watermark{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-30deg);font-family:'Sora',sans-serif;font-size:5rem;font-weight:800;color:rgba(22,163,74,.05);pointer-events:none;white-space:nowrap;letter-spacing:.1em}

    /* Print styles */
    @media print {
      body{background:white;padding:0}
      .topbar{display:none}
      .certificate{border:none;border-radius:0;box-shadow:none}
      .cert-header{-webkit-print-color-adjust:exact;print-color-adjust:exact}
      .approval-item{-webkit-print-color-adjust:exact;print-color-adjust:exact}
      .completion-badge{-webkit-print-color-adjust:exact;print-color-adjust:exact}
    }
  </style>
</head>
<body>

<!-- Top bar -->
<div class="topbar">
  <a class="back-btn" href="dashboard.html">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Back to Dashboard
  </a>
  <button class="print-btn" onclick="window.print()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    Print Certificate
  </button>
</div>

<!-- Certificate -->
<div class="cert-wrap">
<div class="certificate" style="position:relative">
  <div class="watermark">CLEARED</div>

  <!-- Header -->
  <div class="cert-header">
    <div class="school-name">REDEEMERS COLLEGE OF TECHNOLOGY AND MANAGEMENT</div>
    <div class="school-dept">Department of Computer Science</div>
    <div class="cert-title">CLEARANCE CERTIFICATE</div>
    <div class="cert-subtitle">Official Student Clearance Document</div>
  </div>

  <!-- Body -->
  <div class="cert-body">

    <!-- ID + Date row -->
    <div class="cert-id-row">
      <div class="cert-id">Certificate No.<br><strong><?= htmlspecialchars($clearanceId) ?></strong></div>
      <div class="cert-date">Date of Issue<strong><?= $issuedDate ?></strong></div>
    </div>

    <!-- Certified statement -->
    <div class="cert-statement">
      <p>This is to certify that</p>
      <span class="student-name"><?= htmlspecialchars(strtoupper($student['full_name'])) ?></span>
      <div class="matric">Matric Number: <strong><?= htmlspecialchars($student['matric_number']) ?></strong></div>
      <div class="dept-info">
        <div><span>Department</span><?= htmlspecialchars($student['department']) ?></div>
        <div><span>Level</span><?= htmlspecialchars($student['level']) ?></div>
      </div>
      <p style="margin-top:16px">has successfully completed all clearance requirements and has been cleared by all departments of this institution.</p>
    </div>

    <!-- Completion badge -->
    <div class="completion-badge">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
        <polyline points="22 4 12 14.01 9 11.01"/>
      </svg>
      <h3>Fully Cleared ✅</h3>
      <p>Clearance completed on <?= $completionDate ?: $issuedDate ?>. Submitted on <?= $submittedDate ?>.</p>
    </div>

    <!-- Office approvals -->
    <div class="approvals-section">
      <h3>Departmental Approvals</h3>
      <div class="approvals-grid">
        <?php foreach ($rows as $r):
          $name = $officeNames[$r['office']] ?? $r['office'];
          $date = $r['updated_at'] ? date('d M Y', strtotime($r['updated_at'])) : '—';
        ?>
        <div class="approval-item">
          <div class="approval-check">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <div>
            <div class="approval-name"><?= htmlspecialchars($name) ?></div>
            <div class="approval-date">Approved: <?= $date ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div><!-- end cert-body -->

  <!-- Signatures footer -->
  <div class="cert-footer">
    <div class="sig-item">
      <div class="sig-line"></div>
      <div class="sig-name">Head of Department</div>
      <div class="sig-title">Department of Computer Science</div>
    </div>
    <div class="sig-item">
      <div class="sig-line"></div>
      <div class="sig-name">Registrar</div>
      <div class="sig-title">Academic Affairs</div>
    </div>
    <div class="sig-item">
      <div class="sig-line"></div>
      <div class="sig-name">Provost / Academic Director</div>
      <div class="sig-title">Redeemers College of Technology</div>
    </div>
  </div>

</div><!-- end certificate -->
</div><!-- end cert-wrap -->

</body>
</html>