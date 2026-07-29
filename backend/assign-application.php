<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$pdo = db();

$application_id = (int)($_POST['application_id'] ?? 0);
$room_id        = (int)($_POST['room_id']        ?? 0);
$lease_start    = $_POST['lease_start']           ?? null;
$lease_end      = $_POST['lease_end']             ?? null;
$monthly_rent   = (float)($_POST['monthly_rent']  ?? 0);
$deposit_amount = (float)($_POST['deposit_amount'] ?? 0);
$deposit_status = in_array($_POST['deposit_status'] ?? '', ['unpaid','paid','refunded'])
                  ? $_POST['deposit_status'] : 'unpaid';

/* ── Validate ──────────────────────────────────────────────── */
$errors = [];
if (!$application_id) $errors[] = 'Application is required.';
if (!$room_id)        $errors[] = 'Room is required.';
if (!$lease_start)    $errors[] = 'Lease start date is required.';
if (!$lease_end)      $errors[] = 'Lease end date is required.';
if ($lease_end && $lease_start && $lease_end <= $lease_start)
    $errors[] = 'Lease end must be after start date.';

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

/* ── Check application exists ──────────────────────────────── */
$app = $pdo->prepare('SELECT * FROM applications WHERE id = ?');
$app->execute([$application_id]);
$app = $app->fetch();
if (!$app) {
    echo json_encode(['success' => false, 'errors' => ['Application not found.']]);
    exit;
}

/* ── Check no active resident already ─────────────────────── */
$existing = $pdo->prepare(
    "SELECT id FROM residents WHERE application_id = ? AND status = 'active'"
);
$existing->execute([$application_id]);
if ($existing->fetch()) {
    echo json_encode(['success' => false, 'errors' => ['This applicant is already assigned to a room.']]);
    exit;
}

/* ── Check room is available ───────────────────────────────── */
$room = $pdo->prepare(
    'SELECT r.*, res.id AS res_id, res.name AS res_name
     FROM rooms r JOIN residences res ON res.id = r.residence_id
     WHERE r.id = ?'
);
$room->execute([$room_id]);
$room = $room->fetch();

if (!$room) {
    echo json_encode(['success' => false, 'errors' => ['Room not found.']]);
    exit;
}
if ($room['status'] === 'occupied') {
    echo json_encode(['success' => false, 'errors' => ['This room is already occupied. Please choose another.']]);
    exit;
}

/* ── Assign ────────────────────────────────────────────────── */
try {
    $pdo->beginTransaction();

    // Create resident record linked to the existing application
    $pdo->prepare(
        'INSERT INTO residents
         (application_id, room_id, lease_start, lease_end, monthly_rent, deposit_amount, deposit_status, status)
         VALUES (?,?,?,?,?,?,?,?)'
    )->execute([
        $application_id, $room_id,
        $lease_start, $lease_end,
        $monthly_rent  ?: $room['price'],
        $deposit_amount ?: ($monthly_rent ?: $room['price']),
        $deposit_status, 'active'
    ]);

    // Ensure application is marked approved
    if ($app['status'] !== 'approved') {
        $pdo->prepare(
            'UPDATE applications SET status="approved", reviewed_by=?, reviewed_at=NOW() WHERE id=?'
        )->execute([$_SESSION['admin_id'], $application_id]);
    }

    // Mark room occupied
    $pdo->prepare('UPDATE rooms SET status="occupied" WHERE id=?')->execute([$room_id]);

    // Recalculate available_rooms
    $avail = (int)$pdo->query(
        "SELECT COUNT(*) FROM rooms WHERE residence_id={$room['res_id']} AND status='available'"
    )->fetchColumn();
    $pdo->prepare('UPDATE residences SET available_rooms=? WHERE id=?')
        ->execute([$avail, $room['res_id']]);

    $pdo->commit();

    echo json_encode([
        'success'  => true,
        'message'  => $app['first_name'] . ' ' . $app['last_name'] . ' assigned to Room ' . $room['room_number'] . ', ' . $room['res_name'] . '.',
        'redirect' => '/dashboard/rooms.php?residence_id=' . $room['res_id'] . '&msg=tenant_added',
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Server error. Please try again.']]);
}
