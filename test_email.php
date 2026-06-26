<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$checks     = [];
$testResult = null;

//  Check PHPMailer files
$phpmailerPath = __DIR__ . '/PHPMailer/';
$hasMailer = file_exists($phpmailerPath . 'PHPMailer.php')
          && file_exists($phpmailerPath . 'SMTP.php')
          && file_exists($phpmailerPath . 'Exception.php');

$checks[] = [
    'label'  => 'PHPMailer files found in /PHPMailer/ folder',
    'status' => $hasMailer ? 'OK' : 'FAIL',
    'detail' => $hasMailer
        ? 'PHPMailer.php, SMTP.php, Exception.php all found'
        : 'Files NOT found. Create a PHPMailer folder inside ClearanceSystem and paste the 3 files there.'
];

//  Check email_config.php
$hasConfig = file_exists(__DIR__ . '/email_config.php');
$checks[] = [
    'label'  => 'email_config.php exists',
    'status' => $hasConfig ? 'OK' : 'FAIL',
    'detail' => $hasConfig ? 'email_config.php found' : 'email_config.php is missing from your folder'
];

// 3. Check OpenSSL
$hasSSL = extension_loaded('openssl');
$checks[] = [
    'label'  => 'OpenSSL extension (needed for Gmail)',
    'status' => $hasSSL ? 'OK' : 'FAIL',
    'detail' => $hasSSL
        ? 'OpenSSL is enabled'
        : 'OpenSSL is disabled. Open C:/xampp/php/php.ini, find ;extension=openssl and remove the semicolon, then restart XAMPP'
];

// 4. PHP version
$checks[] = [
    'label'  => 'PHP Version',
    'status' => 'OK',
    'detail' => 'Running PHP ' . PHP_VERSION
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['test_to'])) {
    $testTo   = trim($_POST['test_to']);
    $testFrom = trim($_POST['test_from']);
    $testPass = trim($_POST['test_pass']);

    if (!$hasMailer) {
        $testResult = ['ok' => false, 'msg' => 'PHPMailer files not found. Fix that first.'];
    } elseif (!$hasSSL) {
        $testResult = ['ok' => false, 'msg' => 'OpenSSL is not enabled. Enable it in php.ini first.'];
    } else {
        require_once $phpmailerPath . 'Exception.php';
        require_once $phpmailerPath . 'PHPMailer.php';
        require_once $phpmailerPath . 'SMTP.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $testFrom;
            $mail->Password   = $testPass;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->setFrom($testFrom, 'Clearance System Test');
            $mail->addAddress($testTo);
            $mail->isHTML(true);
            $mail->Subject = 'Test Email from Student Clearance System';
            $mail->Body    = '<h2 style="color:#16a34a">It works!</h2><p>Your Gmail setup is correct. Email notifications will now work.</p>';
            $mail->AltBody = 'Test Email - Gmail setup is working!';
            $mail->send();
            $testResult = ['ok' => true, 'msg' => 'Email sent successfully to ' . $testTo . '! Check your inbox (and spam folder).'];
        } catch (Exception $e) {
            $testResult = ['ok' => false, 'msg' => 'Failed: ' . $mail->ErrorInfo];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Email Test</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:Arial,sans-serif;background:#f8fafc;padding:30px;color:#0f172a}
    .wrap{max-width:620px;margin:0 auto}
    h1{font-size:1.3rem;margin-bottom:4px}
    .sub{color:#94a3b8;font-size:.85rem;margin-bottom:24px}
    .card{background:white;border:1px solid #e2e8f0;border-radius:10px;padding:22px;margin-bottom:16px}
    .card h2{font-size:.95rem;font-weight:700;margin-bottom:14px}
    .item{display:flex;gap:10px;padding:9px 0;border-bottom:1px solid #f1f5f9;font-size:.84rem;align-items:flex-start}
    .item:last-child{border-bottom:none}
    .badge{font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:4px;flex-shrink:0;margin-top:1px}
    .OK{background:#16a34a;color:white}
    .FAIL{background:#dc2626;color:white}
    label{display:block;font-size:.8rem;font-weight:600;margin-bottom:5px;margin-top:12px}
    input{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.875rem;outline:none}
    input:focus{border-color:#2563eb}
    .btn{width:100%;padding:12px;background:#0f172a;color:white;border:none;border-radius:8px;font-size:.9rem;font-weight:700;cursor:pointer;margin-top:16px}
    .btn:hover{background:#1e293b}
    .ok-box{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;padding:12px 16px;border-radius:8px;margin-top:14px;font-size:.875rem;line-height:1.6}
    .fail-box{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:8px;margin-top:14px;font-size:.875rem;line-height:1.6}
    .note{background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:12px 16px;font-size:.82rem;color:#9a3412;margin-top:4px}
    code{background:#e2e8f0;padding:1px 5px;border-radius:3px;font-family:monospace;font-size:.8rem}
  </style>
</head>
<body>
<div class="wrap">
  <h1>Email System Test</h1>
  <p class="sub">Checks your Gmail setup and sends a real test email.</p>

  <div class="card">
    <h2>System Checks</h2>
    <?php foreach ($checks as $c): ?>
    <div class="item">
      <span class="badge <?= $c['status'] ?>"><?= $c['status'] ?></span>
      <div>
        <strong><?= htmlspecialchars($c['label']) ?></strong><br>
        <span style="color:#64748b;font-size:.8rem"><?= htmlspecialchars($c['detail']) ?></span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h2>Send Test Email</h2>
    <p style="font-size:.82rem;color:#64748b">Fill in your Gmail details and send a real test email.</p>
    <form method="POST">
      <label>Your Gmail Address (sender)</label>
      <input type="email" name="test_from" placeholder="yourname@gmail.com" required/>

      <label>Gmail App Password (16 characters, no spaces)</label>
      <input type="text" name="test_pass" placeholder="Paste your App Password here" required/>

      <label>Send test email to (any email you can check)</label>
      <input type="email" name="test_to" placeholder="recipient@gmail.com" required/>

      <button class="btn" type="submit">Send Test Email Now</button>
    </form>

    <?php if ($testResult): ?>
      <div class="<?= $testResult['ok'] ? 'ok-box' : 'fail-box' ?>">
        <?= htmlspecialchars($testResult['msg']) ?>
        <?php if (!$testResult['ok']): ?>
        <br><br>
        <strong>Common fixes:</strong><br>
        1. Make sure 2-Step Verification is ON at <code>myaccount.google.com</code><br>
        2. Generate App Password at <code>myaccount.google.com</code> > Security > App Passwords<br>
        3. Remove all spaces from the App Password when pasting<br>
        4. Make sure the Gmail address matches the account that created the App Password
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="note">
    Delete <code>test_email.php</code> from your server after testing.
  </div>
</div>
</body>
</html>