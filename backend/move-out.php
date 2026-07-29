<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
    header('Location: /dashboard/residents.php?error=csrf');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /dashboard/residents.php');
    exit;
}

$pdo         = db();
$resident_id = (int)($_POST['resident_id'] ?? 0);
$move_out    = $_POST['move_out_date'] ?? date('Y-m-d');
$reason      = trim($_POST['reason'] ?? 'Lease ended');
$refund_dep  = !empty($_POST['refund_deposit']);

if (!$resident_id) {
    header('Location: /dashboard/residents.php?msg=error');
    exit;
}

$res = $pdo->prepare(
    'SELECT r.*, rm.residence_id, rm.room_number
     FROM residents r JOIN rooms rm ON rm.id = r.room_id
     WHERE r.id = ?'
);
$res->execute([$resident_id]);
$res = $res->fetch();

if (!$res) {
    header('Location: /dashboard/residents.php?msg=error');
    exit;
}

$pdo->beginTransaction();

// Mark resident as moved-out
$pdo->prepare(
    'UPDATE residents SET status = "moved-out", lease_end = ?,
     deposit_status = ? WHERE id = ?'
)->execute([
    $move_out,
    $refund_dep ? 'refunded' : $res['deposit_status'],
    $resident_id
]);

// Free the room
$pdo->prepare('UPDATE rooms SET status = "available" WHERE id = ?')
    ->execute([$res['room_id']]);

// Recalculate available rooms
$avail = (int) $pdo->query(
    "SELECT COUNT(*) FROM rooms WHERE residence_id = {$res['residence_id']} AND status = 'available'"
)->fetchColumn();
$pdo->prepare('UPDATE residences SET available_rooms = ? WHERE id = ?')
    ->execute([$avail, $res['residence_id']]);

// Add note to application if linked
if ($res['application_id']) {
    $note = 'Moved out on ' . $move_out . '. ' . $reason;
    $pdo->prepare('UPDATE applications SET review_notes = ? WHERE id = ?')
        ->execute([$note, $res['application_id']]);
}

$pdo->commit();

header('Location: /dashboard/residents.php?msg=moved_out');
exit;
