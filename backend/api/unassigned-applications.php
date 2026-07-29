<?php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/auth.php';
require_login();

// Applications that have no active resident yet
$rows = db()->query(
    "SELECT a.id, a.first_name, a.last_name, a.email, a.phone,
            a.preferred_residence, a.occupation, a.funding_source,
            a.status, a.submitted_at
     FROM applications a
     LEFT JOIN residents r ON r.application_id = a.id AND r.status = 'active'
     WHERE r.id IS NULL
       AND a.status IN ('approved','pending','waitlisted')
     ORDER BY
       FIELD(a.status,'approved','pending','waitlisted'),
       a.submitted_at DESC"
)->fetchAll();

echo json_encode(['success' => true, 'applications' => $rows]);
