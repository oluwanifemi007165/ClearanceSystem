<?php
/**
 * setup.php — STANDALONE VERSION
 * Does NOT include db.php so it won't trigger any session redirects.
 * Open: http://localhost/ClearanceSystem/setup.php
 * DELETE after running.
 */

// ── Your DB credentials (same as db.php) ─────────────────────
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';           // blank for XAMPP default
$DB_NAME = 'clearance_db';
// ─────────────────────────────────────────────────────────────

$results = [];

// Connect directly — no db.php, no session_start
$conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    $results[] = ['status'=>'ERROR','msg'=>'Cannot connect: '.$conn->connect_error];
    renderPage($results); exit;
}
$conn->set_charset('utf8mb4');
$results[] = ['status'=>'OK','msg'=>'Database connected successfully'];

// Generate correct hash for office123
$hash = password_hash('office123', PASSWORD_DEFAULT);
$results[] = ['status'=>'OK','msg'=>'Password hash generated successfully'];

// Update all offices
$stmt = $conn->prepare("UPDATE offices SET password_hash = ?");
$stmt->bind_param('s', $hash);
$stmt->execute();
$updated = $stmt->affected_rows;
$stmt->close();

if ($updated > 0) {
    $results[] = ['status'=>'OK','msg'=>"Updated $updated office(s) — all offices now use password: office123"];
} else {
    $r = $conn->query("SELECT COUNT(*) as c FROM offices");
    $cnt = $r->fetch_assoc()['c'];
    if ($cnt == 0) {
        $offices = [
            ['Library','library'],['Bursary','bursary'],['Department','department'],
            ['Hostel','hostel'],['Security','security'],["Rector's Office",'rectors_office'],
            ['Academic Affairs','academic'],['Sports Unit','sports'],['Medical Centre','medical'],
        ];
        $stmt = $conn->prepare("INSERT IGNORE INTO offices (office_name, username, password_hash) VALUES (?,?,?)");
        foreach ($offices as [$name,$uname]) { $stmt->bind_param('sss',$name,$uname,$hash); $stmt->execute(); }
        $stmt->close();
        $results[] = ['status'=>'OK','msg'=>'Offices were empty — inserted all 9 offices with password: office123'];
    } else {
        $results[] = ['status'=>'WARN','msg'=>"$cnt offices found but 0 rows updated — offices may already have correct hash"];
    }
}

// Verify password works
$r = $conn->query("SELECT password_hash FROM offices WHERE username='library' LIMIT 1");
$row = $r->fetch_assoc();
if ($row && password_verify('office123', $row['password_hash'])) {
    $results[] = ['status'=>'OK','msg'=>"Verification PASSED — office123 works correctly for login"];
} else {
    $results[] = ['status'=>'ERROR','msg'=>"Verification FAILED — please contact support"];
}

// Check / insert sample students
$r = $conn->query("SELECT COUNT(*) as c FROM students");
$cnt = $r->fetch_assoc()['c'];
if ($cnt > 0) {
    $results[] = ['status'=>'OK','msg'=>"$cnt student(s) already in database"];
} else {
    $students = [
        ['John Adebayo','CSC/2020/001','Computer Science','400'],
        ['Sarah Okon','CSC/2020/002','Computer Science','400'],
        ['Michael Okafor','ENG/2021/010','Electrical Engineering','300'],
        ['Aisha Bello','LAW/2019/045','Law','500'],
        ['David Chukwu','MED/2022/003','Medicine','200'],
    ];
    $stmt = $conn->prepare("INSERT IGNORE INTO students (full_name,matric_number,department,level) VALUES (?,?,?,?)");
    foreach ($students as [$n,$m,$d,$l]) { $stmt->bind_param('ssss',$n,$m,$d,$l); $stmt->execute(); }
    $stmt->close();
    $results[] = ['status'=>'OK','msg'=>'Inserted 5 sample students into database'];
}

$conn->close();
renderPage($results);

function renderPage(array $results): void {
    $err = in_array('ERROR', array_column($results,'status'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Clearance Setup</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:Arial,sans-serif;background:#f8fafc;padding:40px 20px;color:#0f172a}
    .wrap{max-width:640px;margin:0 auto}
    h1{font-size:1.4rem;margin-bottom:4px}
    .sub{color:#94a3b8;font-size:.875rem;margin-bottom:28px}
    .item{display:flex;gap:12px;padding:12px 16px;border-radius:8px;margin-bottom:8px;font-size:.875rem;align-items:flex-start}
    .OK{background:#f0fdf4;border:1px solid #bbf7d0}
    .WARN{background:#fefce8;border:1px solid #fde68a}
    .ERROR{background:#fef2f2;border:1px solid #fecaca}
    .dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;margin-top:3px}
    .OK .dot{background:#16a34a}.WARN .dot{background:#d97706}.ERROR .dot{background:#dc2626}
    .summary{padding:16px;border-radius:10px;font-weight:700;text-align:center;margin:20px 0;font-size:1rem}
    .ok{background:#f0fdf4;border:2px solid #16a34a;color:#16a34a}
    .fail{background:#fef2f2;border:2px solid #dc2626;color:#dc2626}
    .next{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:16px;margin-top:12px;font-size:.875rem;line-height:1.9}
    .next b{display:block;margin-bottom:4px;color:#1d4ed8}
    code{background:#e2e8f0;padding:2px 6px;border-radius:4px;font-size:.82rem}
    .note{background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:14px;font-size:.85rem;color:#9a3412;margin-top:12px}
  </style>
</head>
<body><div class="wrap">
  <h1>Clearance System — Setup</h1>
  <p class="sub">Running setup checks and fixing passwords…</p>
  <?php foreach($results as $r): ?>
  <div class="item <?=$r['status']?>"><div class="dot"></div><div><?=htmlspecialchars($r['msg'])?></div></div>
  <?php endforeach; ?>
  <div class="summary <?=$err?'fail':'ok'?>"><?=$err?'✗ Issues found — see above':'✓ Setup complete! Your system is ready.'?></div>
  <?php if(!$err): ?>
  <div class="next">
    <b>What to do next:</b>
    1. Delete <code>setup.php</code> and <code>debug.php</code> from your server folder<br>
    2. Open: <code>http://localhost/ClearanceSystem/login.html</code><br>
    3. Student login → select any matric number → Login<br>
    4. Office login → select any office → password: <code>office123</code>
  </div>
  <?php endif; ?>
  <div class="note">⚠️ Delete this file after setup. Never leave it on a live server.</div>
</div></body></html>
<?php }