<?php

// Load PHPMailer manually from /PHPMailer/ folder
$phpmailerPath = __DIR__ . '/PHPMailer/';

if (!file_exists($phpmailerPath . 'PHPMailer.php')) {
    error_log('PHPMailer not found in /PHPMailer/ folder. Emails disabled.');
    function notifyFirstOffice($s) {}
    function notifyNextOffice($o, $s) {}
    function notifyStudentRejection($o, $r, $s) {}
    return;
}

require_once $phpmailerPath . 'Exception.php';
require_once $phpmailerPath . 'PHPMailer.php';
require_once $phpmailerPath . 'SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//  GMAIL CREDENTIALS
define('GMAIL_FROM',         'afuyeoluwanifemi83@gmail.com');  
define('GMAIL_APP_PASSWORD', 'qndneahrbbpbutez');        
define('GMAIL_FROM_NAME',    'Clearance System');
define('SYSTEM_URL',         'http://localhost/ClearanceSystem');


// OFFICE EMAIL ADDRESSES 
define('OFFICE_EMAILS', [
    'bursary'               => 'afuyeoluwanifemi636@gmail.com',
    'head_of_department'    => 'afuyeoluwanifemi83@gmail.com',
    'dean_of_school'        => 'afuyeoluwanifemi636@gmail.com',
    'school_office'         => 'afuyeoluwanifemi83@gmail.com',
    'student_affairs'       => 'studentaffairs@university.edu.ng',
    'center_of_enterprenur' => 'entrepreneur@university.edu.ng',
    'admission_office'      => 'admissions@university.edu.ng',
    'accademic_affairs'     => 'academic@university.edu.ng',
    'library'                => 'library@university.edu.ng',
]);

define('OFFICE_NAMES', [
    'bursary'               => 'Bursary',
    'head_of_department'    => 'Head of Department',
    'dean_of_school'        => 'Dean of School',
    'school_office'         => 'School Office',
    'student_affairs'       => 'Student Affairs',
    'center_of_enterprenur' => 'Center of Entrepreneur',
    'admission_office'      => 'Admission Office',
    'accademic_affairs'     => 'Academic Affairs',
    'library'                => 'Library',
]);

define('OFFICE_ORDER', [
    'bursary', 'head_of_department', 'dean_of_school',
    'school_office', 'student_affairs', 'center_of_enterprenur',
    'admission_office', 'library', 'accademic_affairs',
]);

//  SEND EMAIL 
function sendEmail(string $toEmail, string $toName, string $subject, string $body): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_FROM;
        $mail->Password   = GMAIL_APP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom(GMAIL_FROM, GMAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email failed to ' . $toEmail . ': ' . $mail->ErrorInfo);
        return false;
    }
}

//  NOTIFY FIRST OFFICE (Bursary) when student submits form 
function notifyFirstOffice(array $student): void {
    $office = OFFICE_ORDER[0];
    $email  = OFFICE_EMAILS[$office] ?? null;
    $name   = OFFICE_NAMES[$office]  ?? $office;
    if (!$email) return;

    sendEmail(
        $email, $name,
        'New Clearance Application — Action Required',
        buildTemplate('#2563EB', 'New Application',
            'New Student Clearance Application',
            "Dear <strong>$name</strong>,",
            'A student has submitted a clearance application waiting for your review.',
            $student, 'Log in to review and approve or reject.', 'Review Now'
        )
    );
}

//  NOTIFY NEXT OFFICE after an approval 
function notifyNextOffice(string $approvedOffice, array $student): void {
    $order        = OFFICE_ORDER;
    $currentIndex = array_search($approvedOffice, $order);
    $approvedName = OFFICE_NAMES[$approvedOffice] ?? $approvedOffice;
    if ($currentIndex === false) return;

    // Notify next office
    $nextIndex = $currentIndex + 1;
    if ($nextIndex < count($order)) {
        $nextOffice = $order[$nextIndex];
        $nextEmail  = OFFICE_EMAILS[$nextOffice] ?? null;
        $nextName   = OFFICE_NAMES[$nextOffice]  ?? $nextOffice;
        if ($nextEmail) {
            sendEmail(
                $nextEmail, $nextName,
                'Clearance Forwarded to You — Action Required',
                buildTemplate('#2563EB', 'Action Required',
                    'Clearance Awaiting Your Approval',
                    "Dear <strong>$nextName</strong>,",
                    "A clearance has been approved by <strong>$approvedName</strong> and forwarded to your office.",
                    $student, 'Log in to review and approve or reject.', 'Review Now'
                )
            );
        }
    }

    // If last office (Academic Affairs) approved → email student
    if ($approvedOffice === $order[count($order) - 1]) {
        $studentEmail = $student['email'] ?? null;
        $studentName  = $student['full_name'] ?? 'Student';
        if ($studentEmail) {
            sendEmail(
                $studentEmail, $studentName,
                'Congratulations! Your Clearance is Complete',
                buildTemplate('#16A34A', 'Fully Cleared',
                    'Your Clearance is Complete!',
                    "Dear <strong>$studentName</strong>,",
                    'Your clearance has been fully approved by all offices including the Rector\'s Office!',
                    $student, 'Log in to view your clearance status.', 'Go to Dashboard'
                )
            );
        }
    }
}

// ── NOTIFY STUDENT of rejection ───────────────────────────────
function notifyStudentRejection(string $office, string $reason, array $student): void {
    $studentEmail = $student['email'] ?? null;
    $studentName  = $student['full_name'] ?? 'Student';
    $officeName   = OFFICE_NAMES[$office] ?? $office;
    if (!$studentEmail) return;

    sendEmail(
        $studentEmail, $studentName,
        'Clearance Update — Action Required',
        buildTemplate('#DC2626', 'Rejected',
            'Your Clearance Was Rejected',
            "Dear <strong>$studentName</strong>,",
            "Your clearance was rejected by <strong>$officeName</strong>.<br><br>
             <strong>Reason:</strong> $reason<br><br>
             Please resolve the issue and contact the office directly.",
            $student, 'Log in to view your clearance status.', 'View Dashboard'
        )
    );
}

// ── EMAIL HTML TEMPLATE ───────────────────────────────────────
function buildTemplate(
    string $color, string $badge, string $title,
    string $greeting, string $intro, array $student,
    string $actionMsg, string $actionLabel
): string {
    $name   = htmlspecialchars($student['full_name']  ?? '—');
    $matric = htmlspecialchars($student['matric']     ?? $student['matric_number'] ?? '—');
    $dept   = htmlspecialchars($student['department'] ?? '—');
    $url    = SYSTEM_URL . '/index.html';

    return "<!DOCTYPE html><html><head><meta charset='UTF-8'/></head>
<body style='margin:0;padding:20px;background:#f8fafc;font-family:Arial,sans-serif'>
<div style='max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0'>
  <div style='background:{$color};padding:24px 28px'>
    <h1 style='color:#fff;font-size:1.1rem;margin:0 0 6px'>{$title}</h1>
    <span style='background:rgba(255,255,255,.25);color:#fff;padding:2px 10px;border-radius:20px;font-size:.75rem;font-weight:600'>{$badge}</span>
  </div>
  <div style='padding:24px 28px'>
    <p style='margin:0 0 12px;font-size:.95rem;color:#0f172a'>{$greeting}</p>
    <p style='margin:0 0 20px;font-size:.875rem;color:#475569;line-height:1.7'>{$intro}</p>
    <div style='background:#f8fafc;border-radius:8px;padding:14px 18px;margin-bottom:20px;border:1px solid #e2e8f0'>
      <p style='margin:0 0 8px;font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em'>Student Details</p>
      <table style='width:100%;font-size:.85rem;border-collapse:collapse'>
        <tr><td style='color:#64748b;padding:3px 0;width:40%'>Full Name</td><td style='font-weight:600;color:#0f172a'>{$name}</td></tr>
        <tr><td style='color:#64748b;padding:3px 0'>Matric Number</td><td style='font-weight:600;color:#0f172a'>{$matric}</td></tr>
        <tr><td style='color:#64748b;padding:3px 0'>Department</td><td style='font-weight:600;color:#0f172a'>{$dept}</td></tr>
      </table>
    </div>
    <p style='font-size:.875rem;color:#475569;line-height:1.6;margin:0 0 20px'>{$actionMsg}</p>
    <div style='text-align:center'>
      <a href='{$url}' style='display:inline-block;background:{$color};color:#fff;padding:11px 28px;border-radius:8px;text-decoration:none;font-weight:700;font-size:.875rem'>{$actionLabel}</a>
    </div>
  </div>
  <div style='background:#f8fafc;padding:12px 28px;border-top:1px solid #e2e8f0;text-align:center'>
    <p style='margin:0;font-size:.72rem;color:#94a3b8'>Automated message — Student Clearance System. Do not reply.</p>
  </div>
</div>
</body></html>";
}