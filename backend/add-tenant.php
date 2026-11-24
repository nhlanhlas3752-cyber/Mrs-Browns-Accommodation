<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!csrf_verify()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.']);
    exit;
}

$pdo = db();

$first_name     = trim($_POST['first_name']     ?? '');
$last_name      = trim($_POST['last_name']      ?? '');
$email          = trim($_POST['email']          ?? '');
$phone          = trim($_POST['phone']          ?? '');
$dob            = $_POST['dob']                 ?? null;
$gender         = in_array($_POST['gender'] ?? '', ['male','female','other']) ? $_POST['gender'] : 'male';
$room_id        = (int)($_POST['room_id']       ?? 0);
$lease_start    = $_POST['lease_start']         ?? null;
$lease_end      = $_POST['lease_end']           ?? null;
$monthly_rent   = (float)($_POST['monthly_rent'] ?? 0);
$deposit_amount = (float)($_POST['deposit_amount'] ?? 0);
$deposit_status = in_array($_POST['deposit_status'] ?? '', ['unpaid','paid','refunded']) ? $_POST['deposit_status'] : 'unpaid';
$notes          = trim($_POST['notes']          ?? '');

// Validate
$errors = [];
if (!$first_name) $errors[] = 'First name is required.';
if (!$last_name)  $errors[] = 'Last name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if (!$phone)      $errors[] = 'Phone number is required.';
if (!$room_id)    $errors[] = 'Room is required.';
if (!$lease_start || !$lease_end) $errors[] = 'Lease start and end dates are required.';
if ($lease_end && $lease_start && $lease_end <= $lease_start) $errors[] = 'Lease end must be after start date.';

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Confirm room exists and is available
$room = $pdo->prepare('SELECT r.*, res.id as res_id, res.name as res_name FROM rooms r JOIN residences res ON res.id = r.residence_id WHERE r.id = ?');
$room->execute([$room_id]);
$room = $room->fetch();

if (!$room) {
    echo json_encode(['success' => false, 'errors' => ['Room not found.']]);
    exit;
}
if ($room['status'] === 'occupied') {
    echo json_encode(['success' => false, 'errors' => ['This room is already occupied.']]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Create an application record (auto-approved)
    $appStmt = $pdo->prepare(
        'INSERT INTO applications
         (first_name, last_name, email, phone, dob, gender,
          occupation, funding_source, preferred_residence, room_type,
          move_in_date, notes, status, terms_accepted, submitted_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())'
    );
    $appStmt->execute([
        $first_name, $last_name, $email, $phone,
        $dob ?: null, $gender,
        'other', 'self',
        'house' . preg_replace('/[^0-9]/', '', $room['res_name']),
        $room['type'],
        $lease_start, $notes ?: null,
        'approved', 1
    ]);
    $application_id = (int) $pdo->lastInsertId();

    // 2. Create resident record
    $resStmt = $pdo->prepare(
        'INSERT INTO residents
         (application_id, room_id, lease_start, lease_end, monthly_rent, deposit_amount, deposit_status, status)
         VALUES (?,?,?,?,?,?,?,?)'
    );
    $resStmt->execute([
        $application_id, $room_id,
        $lease_start, $lease_end,
        $monthly_rent ?: $room['price'],
        $deposit_amount ?: ($monthly_rent ?: $room['price']),
        $deposit_status, 'active'
    ]);

    // 3. Mark room as occupied
    $pdo->prepare('UPDATE rooms SET status = "occupied" WHERE id = ?')->execute([$room_id]);

    // 4. Recalculate available_rooms for the residence
    $availStmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE residence_id = ? AND status = 'available'");
    $availStmt->execute([$room['res_id']]);
    $avail = (int) $availStmt->fetchColumn();
    $pdo->prepare('UPDATE residences SET available_rooms = ? WHERE id = ?')->execute([$avail, $room['res_id']]);

    $pdo->commit();

    echo json_encode([
        'success'  => true,
        'message'  => $first_name . ' ' . $last_name . ' assigned to Room ' . $room['room_number'] . '.',
        'redirect' => '/dashboard/rooms.php?residence_id=' . $room['res_id'] . '&msg=tenant_added'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['Server error. Please try again.']]);
}
