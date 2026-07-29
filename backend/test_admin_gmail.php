<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/email.php';

$app = db()->query("SELECT * FROM applications ORDER BY id DESC LIMIT 1")->fetch();

if (!$app) {
    echo json_encode(['error' => 'No applications found']);
    exit;
}

// Temporarily send admin alert to Gmail to bypass same-mailbox issue
$ref           = 'HT-' . str_pad($app['id'], 5, '0', STR_PAD_LEFT);
$full_name     = $app['first_name'] . ' ' . $app['last_name'];
$residence_fmt = ucwords(str_replace(['-'], [' '], $app['preferred_residence']));
$move_in_fmt   = $app['move_in_date'] ? date('d F Y', strtotime($app['move_in_date'])) : 'Not specified';
$dashboard_url = 'https://www.hilrettrust.co.za/dashboard/application-view.php?id=' . $app['id'];

$inner = '
  <p style="margin:0 0 16px;font-size:15px;color:#1a2436;font-weight:600;">New Application Received</p>
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9FB;border:1px solid #E5E7EB;border-left:4px solid #FE6600;border-radius:6px;margin-bottom:28px;">
    <tr><td style="padding:20px 24px;">
      <table width="100%" cellpadding="4" cellspacing="0">
        <tr><td style="font-size:13px;color:#6B7280;width:150px;">Reference</td><td style="font-size:13px;color:#1a2436;font-weight:700;">' . $ref . '</td></tr>
        <tr><td style="font-size:13px;color:#6B7280;">Applicant</td><td style="font-size:13px;color:#1a2436;">' . htmlspecialchars($full_name) . '</td></tr>
        <tr><td style="font-size:13px;color:#6B7280;">Email</td><td style="font-size:13px;color:#1a2436;">' . htmlspecialchars($app['email']) . '</td></tr>
        <tr><td style="font-size:13px;color:#6B7280;">Phone</td><td style="font-size:13px;color:#1a2436;">' . htmlspecialchars($app['phone']) . '</td></tr>
        <tr><td style="font-size:13px;color:#6B7280;">Residence</td><td style="font-size:13px;color:#1a2436;">' . htmlspecialchars($residence_fmt) . '</td></tr>
        <tr><td style="font-size:13px;color:#6B7280;">Funding</td><td style="font-size:13px;color:#1a2436;">' . htmlspecialchars(ucfirst($app['funding_source'])) . '</td></tr>
        <tr><td style="font-size:13px;color:#6B7280;">Move-in</td><td style="font-size:13px;color:#1a2436;">' . htmlspecialchars($move_in_fmt) . '</td></tr>
      </table>
    </td></tr>
  </table>
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr><td style="text-align:center;">
      <a href="' . $dashboard_url . '" style="display:inline-block;background:#2C3E6B;color:#fff;font-size:14px;font-weight:700;padding:14px 32px;border-radius:8px;text-decoration:none;">
        View Application in Dashboard &rarr;
      </a>
    </td></tr>
  </table>';

$body = ht_email_wrap('#FE6600', '&#128276; &nbsp; New Application — ' . htmlspecialchars($full_name), $inner);
ht_mail('nhlanhlas3752@gmail.com', 'New Application: ' . $full_name . ' | Ref: ' . $ref, $body);

echo json_encode([
    'sent_to' => 'nhlanhlas3752@gmail.com',
    'app_id'  => $app['id'],
    'ref'     => $ref,
    'name'    => $full_name,
    'result'  => 'sent (check Gmail inbox)',
], JSON_PRETTY_PRINT);
