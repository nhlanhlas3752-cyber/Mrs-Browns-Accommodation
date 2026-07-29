<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/email.php';

$app = db()->query("SELECT * FROM applications ORDER BY id DESC LIMIT 1")->fetch();

if (!$app) {
    echo json_encode(['error' => 'No applications found']);
    exit;
}

send_rejection_email($app);

echo json_encode([
    'sent_to' => $app['email'],
    'app_id'  => $app['id'],
    'ref'     => 'HT-' . str_pad($app['id'], 5, '0', STR_PAD_LEFT),
    'name'    => $app['first_name'] . ' ' . $app['last_name'],
    'result'  => 'sent (check inbox)',
], JSON_PRETTY_PRINT);
