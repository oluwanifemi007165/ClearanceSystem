<?php
session_start();
require_once 'external_db.php';

define('ADMIN_PASSWORD', 'admin123'); // change this

$authed = isset($_SESSION['records_admin_auth']) && $_SESSION['records_admin_auth'] === true;
$message = '';

if (isset($_POST['admin_password'])) {
    if ($_POST['admin_password'] === ADMIN_PASSWORD) {
        $_SESSION['records_admin_auth'] = true;
        $authed = true;
    } else {
        $message = 'Wrong password.';
    }
}

$db = externalDb();

// Add new fee 
if ($authed && isset($_POST['add_fee'])) {
    $matric  = trim($_POST['fee_matric']);
    $type    = trim($_POST['fee_type']);
    $amount  = floatval($_POST['fee_amount']);
    $session = trim($_POST['fee_session']);
    $stmt = $db->prepare("INSERT INTO outstanding_fees (matric_number, fee_type, amount, session, status) VALUES (?,?,?,?,'owing')");
    $stmt->bind_param('ssds', $matric, $type, $amount, $session);
    $stmt->execute();
    $message = 'Fee added successfully.';
}

// Mark fee as paid 
if ($authed && isset($_GET['mark_paid'])) {
    $id = intval($_GET['mark_paid']);
    $stmt = $db->prepare("UPDATE outstanding_fees SET status='paid' WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $message = 'Fee marked as paid.';
}

//  Delete fee 
if ($authed && isset($_GET['delete_fee'])) {
    $id = intval($_GET['delete_fee']);
    $stmt = $db->prepare("DELETE FROM outstanding_fees WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $message = 'Fee record deleted.';
}

//  Add new book 
if ($authed && isset($_POST['add_book'])) {
    $matric   = trim($_POST['book_matric']);
    $title    = trim($_POST['book_title']);
    $code     = trim($_POST['book_code']);
    $borrowed = trim($_POST['book_borrowed']);
    $due      = trim($_POST['book_due']);
    $stmt = $db->prepare("INSERT INTO library_records (matric_number, book_title, book_code, date_borrowed, due_date, status) VALUES (?,?,?,?,?,'borrowed')");
    $stmt->bind_param('sssss', $matric, $title, $code, $borrowed, $due);
    $stmt->execute();
    $message = 'Book record added successfully.';
}

//  Mark book as returned 
if ($authed && isset($_GET['mark_returned'])) {
    $id = intval($_GET['mark_returned']);
    $stmt = $db->prepare("UPDATE library_records SET status='returned' WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $message = 'Book marked as returned.';
}

//  Delete book
if ($authed && isset($_GET['delete_book'])) {
    $id = intval($_GET['delete_book']);
    $stmt = $db->prepare("DELETE FROM library_records WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $message = 'Book record deleted.';
}

$fees  = $authed ? $db->query("SELECT * FROM outstanding_fees ORDER BY status, matric_number")->fetch_all(MYSQLI_ASSOC) : [];
$books = $authed ? $db->query("SELECT * FROM library_records ORDER BY status, matric_number")->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><title>Records Admin</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;background:#f8fafc;padding:30px;color:#0f172a}
.wrap{max-width:1000px;margin:0 auto}
h1{font-size:1.3rem;margin-bottom:4px}
.sub{color:#94a3b8;font-size:.85rem;margin-bottom:24px}
.card{background:white;border:1px solid #e2e8f0;border-radius:10px;padding:24px;margin-bottom:20px}
.card h2{font-size:1rem;font-weight:700;margin-bottom:16px}
label{display:block;font-size:.78rem;font-weight:600;margin-bottom:5px;margin-top:10px}
input,select{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.875rem;outline:none}
.row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.btn{padding:10px 20px;background:#0f172a;color:white;border:none;border-radius:8px;font-size:.85rem;font-weight:700;cursor:pointer;margin-top:16px}
.btn:hover{background:#1e293b}
.msg{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;padding:10px 16px;border-radius:8px;margin-bottom:16px;font-size:.85rem}
table{width:100%;border-collapse:collapse;font-size:.82rem;margin-top:10px}
th{background:#f1f5f9;padding:8px 10px;text-align:left;font-size:.75rem}
td{padding:8px 10px;border-bottom:1px solid #f1f5f9}
.tag{padding:2px 8px;border-radius:5px;font-size:.7rem;font-weight:700}
.tag.owing{background:#fef2f2;color:#dc2626}.tag.paid{background:#f0fdf4;color:#16a34a}
.tag.borrowed{background:#fff7ed;color:#d97706}.tag.returned{background:#f0fdf4;color:#16a34a}
.link{color:#2563eb;text-decoration:none;font-size:.78rem;font-weight:600;margin-right:10px}
.link.danger{color:#dc2626}
</style>
</head>
<body><div class="wrap">
<h1>School Records Admin (Simulated External Portal)</h1>
<p class="sub">Manage outstanding fees and library records without touching the database directly.</p>

<?php if ($message): ?><div class="msg">✓ <?= htmlspecialchars($message) ?></div><?php endif; ?>

<?php if (!$authed): ?>
<div class="card">
  <h2>Admin Login</h2>
  <form method="POST">
    <label>Password</label>
    <input type="password" name="admin_password" required/>
    <button class="btn" type="submit">Login</button>
  </form>
</div>
<?php else: ?>

<div class="card">
  <h2>Add Outstanding Fee</h2>
  <form method="POST">
    <div class="row">
      <div><label>Matric Number</label><input type="text" name="fee_matric" placeholder="CSC/2020/001" required/></div>
      <div><label>Fee Type</label><input type="text" name="fee_type" placeholder="Hostel Fee" required/></div>
    </div>
    <div class="row">
      <div><label>Amount (₦)</label><input type="number" step="0.01" name="fee_amount" required/></div>
      <div><label>Session</label><input type="text" name="fee_session" placeholder="2025/2026"/></div>
    </div>
    <button class="btn" type="submit" name="add_fee">Add Fee</button>
  </form>
</div>

<div class="card">
  <h2>Outstanding Fees Records (<?= count($fees) ?>)</h2>
  <table>
    <tr><th>Matric</th><th>Fee Type</th><th>Amount</th><th>Status</th><th>Action</th></tr>
    <?php foreach($fees as $f): ?>
    <tr>
      <td><?= htmlspecialchars($f['matric_number']) ?></td>
      <td><?= htmlspecialchars($f['fee_type']) ?></td>
      <td>₦<?= number_format($f['amount'],2) ?></td>
      <td><span class="tag <?= $f['status'] ?>"><?= $f['status'] ?></span></td>
      <td>
        <?php if($f['status']==='owing'): ?>
        <a class="link" href="?mark_paid=<?= $f['id'] ?>">Mark Paid</a>
        <?php endif; ?>
        <a class="link danger" href="?delete_fee=<?= $f['id'] ?>" onclick="return confirm('Delete this record?')">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="card">
  <h2>Add Library Book Record</h2>
  <form method="POST">
    <div class="row">
      <div><label>Matric Number</label><input type="text" name="book_matric" placeholder="CSC/2020/001" required/></div>
      <div><label>Book Title</label><input type="text" name="book_title" required/></div>
    </div>
    <div class="row">
      <div><label>Book Code</label><input type="text" name="book_code" placeholder="LIB-CS-014"/></div>
      <div><label>Due Date</label><input type="date" name="book_due"/></div>
    </div>
    <label>Date Borrowed</label><input type="date" name="book_borrowed"/>
    <button class="btn" type="submit" name="add_book">Add Book Record</button>
  </form>
</div>

<div class="card">
  <h2>Library Records (<?= count($books) ?>)</h2>
  <table>
    <tr><th>Matric</th><th>Book Title</th><th>Due Date</th><th>Status</th><th>Action</th></tr>
    <?php foreach($books as $b): ?>
    <tr>
      <td><?= htmlspecialchars($b['matric_number']) ?></td>
      <td><?= htmlspecialchars($b['book_title']) ?></td>
      <td><?= htmlspecialchars($b['due_date']) ?></td>
      <td><span class="tag <?= $b['status'] ?>"><?= $b['status'] ?></span></td>
      <td>
        <?php if($b['status']==='borrowed'): ?>
        <a class="link" href="?mark_returned=<?= $b['id'] ?>">Mark Returned</a>
        <?php endif; ?>
        <a class="link danger" href="?delete_book=<?= $b['id'] ?>" onclick="return confirm('Delete this record?')">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php endif; ?>
</div></body></html>