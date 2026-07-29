<?php
// Mimic exactly what application-view.php does
require_once __DIR__ . '/db.php';

// Load email.php the same way application-view.php does
// (dashboard/ uses dirname(__DIR__) . '/backend/email.php')
require_once __DIR__ . '/email.php';

$app = db()->query("SELECT * FROM applications ORDER BY id DESC LIMIT 1")->fetch();

if (!$app) {
    echo json_encode(['error' => 'No applications found']);
    exit;
}

// Patch ht_mail to capture errors instead of silently logging
// Re-test with verbose PHPMailer output
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

$debugOutput = [];
$error       = null;

try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug  = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
        $debugOutput[] = trim($str);
    };

    $mail->isSMTP();
    $mail->Host       = HT_SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = HT_SMTP_USER;
    $mail->Password   = HT_SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = HT_SMTP_PORT;

    $mail->setFrom(HT_SMTP_USER, HT_FROM_NAME);
    $mail->addAddress($app['email']);
    $mail->isHTML(true);
    $mail->Subject = 'APPROVAL TEST — Hilret Trust';
    $mail->Body    = '<p>This is a diagnostic approval email test.</p>';

    $mail->send();
    $result = 'sent';
} catch (MailException $e) {
    $error  = $mail->ErrorInfo;
    $result = 'failed';
}

echo json_encode([
    'result'   => $result,
    'app_id'   => $app['id'],
    'sent_to'  => $app['email'],
    'error'    => $error,
    'smtp_log' => array_slice($debugOutput, -10), // last 10 SMTP lines
], JSON_PRETTY_PRINT);
