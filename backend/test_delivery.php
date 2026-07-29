<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/email.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

$targets = [
    'nhlanhlas3752@gmail.com',
    'pfunzosigebe@icloud.com',
];

$results = [];

foreach ($targets as $to) {
    $debugLog = [];
    $error    = null;
    $result   = 'unknown';

    try {
        $mail = new PHPMailer(true);
        $mail->SMTPDebug  = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str) use (&$debugLog) { $debugLog[] = trim($str); };

        $mail->isSMTP();
        $mail->Host       = HT_SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = HT_SMTP_USER;
        $mail->Password   = HT_SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = HT_SMTP_PORT;

        $mail->setFrom(HT_SMTP_USER, HT_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = 'Hilret Trust — Delivery Test';
        $mail->Body    = '<p>This is a delivery test from Hilret Trust. If you see this, email is working.</p>';
        $mail->AltBody = 'This is a delivery test from Hilret Trust. If you see this, email is working.';

        $mail->send();
        $result = 'accepted_by_smtp';
    } catch (MailException $e) {
        $error  = $mail->ErrorInfo;
        $result = 'smtp_rejected';
    }

    // Find the server's 250 OK line to confirm acceptance
    $accepted = array_values(array_filter($debugLog, fn($l) => str_contains($l, '250 OK')));

    $results[$to] = [
        'result'      => $result,
        'smtp_accept' => $accepted[0] ?? null,
        'error'       => $error,
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT);
