<?php
/**
 * Hilret Trust — Email helper functions
 * Included by application-view.php and submit-application.php
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

define('HT_SMTP_HOST',  'mail.hilrettrust.co.za');
define('HT_SMTP_USER',  'admissions@hilrettrust.co.za');
define('HT_SMTP_PASS',  'Admin@2026');
define('HT_SMTP_PORT',  465);
define('HT_FROM_NAME',  'Hilret Trust Accommodation');

function ht_mail(string $to, string $subject, string $body): void {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = HT_SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = HT_SMTP_USER;
        $mail->Password   = HT_SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = HT_SMTP_PORT;

        $mail->setFrom(HT_SMTP_USER, HT_FROM_NAME);
        $mail->addReplyTo(HT_SMTP_USER, HT_FROM_NAME);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
    } catch (Exception $e) {
        // Silently fail — log to error_log for debugging
        error_log('Hilret Trust mailer error: ' . $mail->ErrorInfo);
    }
}

function ht_email_wrap(string $banner_color, string $banner_text, string $inner_html): string {
    return '<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:\'Segoe UI\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:32px 16px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">

  <!-- Header -->
  <tr>
    <td style="background:#2C3E6B;padding:36px 40px;text-align:center;">
      <div style="font-size:26px;font-weight:900;color:#FE6600;letter-spacing:3px;text-transform:uppercase;">HILRET</div>
      <div style="font-size:18px;color:rgba(255,255,255,.75);font-style:italic;margin-top:2px;">Trust</div>
      <div style="width:80px;height:2px;background:linear-gradient(90deg,transparent,#FE6600,transparent);margin:12px auto 0;"></div>
      <div style="font-size:13px;color:rgba(255,255,255,.5);margin-top:10px;letter-spacing:1px;text-transform:uppercase;">Botanic Grove · Berea · Durban</div>
    </td>
  </tr>

  <!-- Status banner -->
  <tr>
    <td style="background:' . $banner_color . ';padding:16px 40px;text-align:center;">
      <div style="color:#fff;font-size:15px;font-weight:700;letter-spacing:.5px;">' . $banner_text . '</div>
    </td>
  </tr>

  <!-- Body -->
  <tr><td style="padding:40px 40px 32px;">' . $inner_html . '</td></tr>

  <!-- Footer -->
  <tr>
    <td style="background:#F8F9FB;border-top:1px solid #E5E7EB;padding:20px 40px;text-align:center;">
      <p style="margin:0;font-size:11px;color:#9CA3AF;line-height:1.6;">
        To contact us, email <a href="mailto:admin@hilrettrust.co.za" style="color:#6B7280;">admin@hilrettrust.co.za</a>
        &nbsp;·&nbsp; <a href="https://hilrettrust.co.za" style="color:#6B7280;">hilrettrust.co.za</a>
      </p>
      <p style="margin:8px 0 0;font-size:11px;color:#9CA3AF;">&copy; ' . date('Y') . ' Hilret Trust (Pty) Ltd. All rights reserved.</p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>';
}

// ── Admin: New Application Alert ─────────────────────────────
function send_admin_new_application(
    int $app_id, string $first_name, string $last_name,
    string $email, string $phone, string $residence,
    string $funding, ?string $move_in
): void {
    $ref           = 'HT-' . str_pad($app_id, 5, '0', STR_PAD_LEFT);
    $full_name     = trim($first_name . ' ' . $last_name);
    $residence_fmt = ucwords(str_replace(['-'], [' '], $residence));
    $move_in_fmt   = $move_in ? date('d F Y', strtotime($move_in)) : 'Not specified';
    $dashboard_url = 'https://www.hilrettrust.co.za/dashboard/application-view.php?id=' . $app_id;

    $inner = '
      <p style="margin:0 0 16px;font-size:15px;color:#1a2436;font-weight:600;">New Application Received</p>
      <p style="margin:0 0 20px;font-size:14px;color:#374151;line-height:1.8;">
        A new accommodation application has been submitted and is waiting for your review.
      </p>

      <table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9FB;border:1px solid #E5E7EB;border-left:4px solid #FE6600;border-radius:6px;margin-bottom:28px;">
        <tr><td style="padding:20px 24px;">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#6B7280;margin-bottom:14px;">Application Details</div>
          <table width="100%" cellpadding="4" cellspacing="0">
            <tr><td style="font-size:13px;color:#6B7280;width:150px;">Reference</td><td style="font-size:13px;color:#1a2436;font-weight:700;">' . $ref . '</td></tr>
            <tr><td style="font-size:13px;color:#6B7280;">Applicant</td><td style="font-size:13px;color:#1a2436;font-weight:600;">' . htmlspecialchars($full_name) . '</td></tr>
            <tr><td style="font-size:13px;color:#6B7280;">Email</td><td style="font-size:13px;color:#1a2436;">' . htmlspecialchars($email) . '</td></tr>
            <tr><td style="font-size:13px;color:#6B7280;">Phone</td><td style="font-size:13px;color:#1a2436;">' . htmlspecialchars($phone) . '</td></tr>
            <tr><td style="font-size:13px;color:#6B7280;">Residence</td><td style="font-size:13px;color:#1a2436;font-weight:600;">' . htmlspecialchars($residence_fmt) . '</td></tr>
            <tr><td style="font-size:13px;color:#6B7280;">Funding</td><td style="font-size:13px;color:#1a2436;">' . htmlspecialchars(ucfirst($funding)) . '</td></tr>
            <tr><td style="font-size:13px;color:#6B7280;">Move-in Date</td><td style="font-size:13px;color:#1a2436;">' . htmlspecialchars($move_in_fmt) . '</td></tr>
            <tr><td style="font-size:13px;color:#6B7280;">Submitted</td><td style="font-size:13px;color:#1a2436;">' . date('d F Y, H:i') . '</td></tr>
          </table>
        </td></tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0">
        <tr><td style="text-align:center;padding:8px 0 0;">
          <a href="' . $dashboard_url . '"
             style="display:inline-block;background:#2C3E6B;color:#fff;font-family:\'Segoe UI\',Arial,sans-serif;
                    font-size:14px;font-weight:700;padding:14px 32px;border-radius:8px;text-decoration:none;">
            View Application in Dashboard &rarr;
          </a>
        </td></tr>
      </table>';

    $body = ht_email_wrap('#FE6600', '&#128276; &nbsp; New Application — ' . htmlspecialchars($full_name), $inner);
    ht_mail(HT_SMTP_USER, 'New Application: ' . $full_name . ' | Ref: ' . $ref, $body);
}

// ── Application Received ──────────────────────────────────────
function send_application_confirmation(
    string $to_email, string $first_name, string $last_name,
    int $app_id, string $residence, ?string $move_in
): void {
    $ref = 'HT-' . str_pad($app_id, 5, '0', STR_PAD_LEFT);

    $inner = '
      <p style="margin:0 0 20px;font-size:15px;color:#1a2436;line-height:1.8;">Dear Applicant,</p>
      <p style="margin:0 0 16px;font-size:14px;color:#374151;line-height:1.8;">
        Thank you for submitting your application to <strong>Hilret Trust Accommodation</strong>.
      </p>
      <p style="margin:0 0 16px;font-size:14px;color:#374151;line-height:1.8;">
        We would like to confirm that we have successfully received your application. Our team is currently
        reviewing the documents you submitted, and we will contact you as soon as the review process
        has been completed.
      </p>
      <p style="margin:0 0 32px;font-size:14px;color:#374151;line-height:1.8;">
        We appreciate your interest in Hilret Trust Accommodation and thank you for your patience.
      </p>
      <p style="margin:0;font-size:14px;color:#374151;line-height:1.8;">
        Kind regards,<br><br>
        <strong style="color:#2C3E6B;">Hilret Trust Accommodation</strong><br>
        <span style="color:#6B7280;font-size:13px;">Admissions Team</span>
      </p>';

    $body = ht_email_wrap('#27AE60', '&#10003; &nbsp; Application Successfully Received', $inner);
    ht_mail($to_email, 'Application Received — Hilret Trust | Ref: ' . $ref, $body);
}

// ── Application Approved ──────────────────────────────────────
function send_approval_email(array $app): void {
    $ref = 'HT-' . str_pad($app['id'], 5, '0', STR_PAD_LEFT);

    $inner = '
      <p style="margin:0 0 20px;font-size:15px;color:#1a2436;line-height:1.8;">Dear Applicant,</p>
      <p style="margin:0 0 16px;font-size:15px;color:#1a2436;font-weight:700;">Congratulations!</p>
      <p style="margin:0 0 16px;font-size:14px;color:#374151;line-height:1.8;">
        We are pleased to inform you that your application for accommodation at
        <strong>Hilret Trust Accommodation</strong> has been successfully reviewed and approved.
      </p>
      <p style="margin:0 0 16px;font-size:14px;color:#374151;line-height:1.8;">
        Your application met our requirements, and we are delighted to welcome you to
        Hilret Trust Accommodation. Our team will contact you shortly with the next steps,
        including any documentation, payment details, and check-in information required
        to complete your booking.
      </p>
      <p style="margin:0 0 32px;font-size:14px;color:#374151;line-height:1.8;">
        If you have any questions, please do not hesitate to contact us. We look forward to welcoming you.
      </p>
      <p style="margin:0;font-size:14px;color:#374151;line-height:1.8;">
        Kind regards,<br><br>
        <strong style="color:#2C3E6B;">Hilret Trust Accommodation</strong><br>
        <span style="color:#6B7280;font-size:13px;">Admissions Team</span>
      </p>';

    $body = ht_email_wrap('#27AE60', '&#127881; &nbsp; Your Application Has Been Approved!', $inner);
    ht_mail($app['email'], 'Your Application is Approved — Hilret Trust | Ref: ' . $ref, $body);
}

// ── Application Declined ──────────────────────────────────────
function send_rejection_email(array $app): void {
    $ref = 'HT-' . str_pad($app['id'], 5, '0', STR_PAD_LEFT);

    $inner = '
      <p style="margin:0 0 20px;font-size:15px;color:#1a2436;line-height:1.8;">Dear Applicant,</p>
      <p style="margin:0 0 16px;font-size:14px;color:#374151;line-height:1.8;">
        Thank you for your interest in <strong>Hilret Trust Accommodation</strong> and for taking the time
        to submit your application.
      </p>
      <p style="margin:0 0 16px;font-size:14px;color:#374151;line-height:1.8;">
        After carefully reviewing your application and supporting documents, we regret to inform you
        that your application has not been successful on this occasion.
      </p>
      <p style="margin:0 0 16px;font-size:14px;color:#374151;line-height:1.8;">
        This decision was made after careful consideration of our accommodation requirements and availability.
        We sincerely appreciate your interest in Hilret Trust Accommodation and encourage you to apply again
        should suitable opportunities become available in the future.
      </p>
      <p style="margin:0 0 32px;font-size:14px;color:#374151;line-height:1.8;">
        We wish you every success in your future endeavours and thank you for considering
        Hilret Trust Accommodation.
      </p>
      <p style="margin:0;font-size:14px;color:#374151;line-height:1.8;">
        Kind regards,<br><br>
        <strong style="color:#2C3E6B;">Hilret Trust Accommodation</strong><br>
        <span style="color:#6B7280;font-size:13px;">Admissions Team</span>
      </p>';

    $body = ht_email_wrap('#EF4444', '&#x2139; &nbsp; Application Outcome — Hilret Trust', $inner);
    ht_mail($app['email'], 'Your Application Outcome — Hilret Trust | Ref: ' . $ref, $body);
}
