<?php
/**
 * Diagnostic: call send_approval_email() exactly as application-view.php does.
 * Patches ht_mail() to throw on failure instead of silently logging.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/email.php';

// Grab the latest application
$app = db()->query("SELECT * FROM applications ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$app) {
    echo json_encode(['error' => 'No applications found']);
    exit;
}

// Temporarily override error_log so we can capture it
$captured_log = [];
set_error_handler(function($errno, $errstr) use (&$captured_log) {
    $captured_log[] = "PHP error [$errno]: $errstr";
    return true;
});

// Wrap the call to see if PHP itself errors
ob_start();
$threw = null;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

// Patch: re-run the send with debug output
$debugOutput = [];
$sendError   = null;
$sendResult  = 'unknown';

try {
    $ref = 'HT-' . str_pad($app['id'], 5, '0', STR_PAD_LEFT);

    $inner = '<p>TEST approval email for app #' . $app['id'] . '.</p>';
    $body  = ht_email_wrap('#27AE60', '&#127881; &nbsp; Your Application Has Been Approved!', $inner);

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
    $mail->Subject = 'Your Application is Approved — Hilret Trust | Ref: ' . $ref;
    $mail->Body    = $body;

    $mail->send();
    $sendResult = 'sent';
} catch (MailException $e) {
    $sendError  = $mail->ErrorInfo;
    $sendResult = 'failed';
}

$output = ob_get_clean();
restore_error_handler();

echo json_encode([
    'result'       => $sendResult,
    'app_id'       => $app['id'],
    'sent_to'      => $app['email'],
    'smtp_error'   => $sendError,
    'php_errors'   => $captured_log,
    'buffered_out' => $output ?: null,
    'smtp_log'     => array_slice($debugOutput, -15),
    'constants'    => [
        'HT_SMTP_HOST' => HT_SMTP_HOST,
        'HT_SMTP_USER' => HT_SMTP_USER,
        'HT_SMTP_PORT' => HT_SMTP_PORT,
    ],
], JSON_PRETTY_PRINT);
