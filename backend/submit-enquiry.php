<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$name    = trim(strip_tags($_POST['name']    ?? ''));
$email   = filter_var(trim($_POST['email']   ?? ''), FILTER_SANITIZE_EMAIL);
$message = trim(strip_tags($_POST['message'] ?? ''));

$errors = [];
if (!$name)    $errors[] = 'Name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if (!$message) $errors[] = 'Message is required.';

if ($errors) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $stmt = db()->prepare(
        'INSERT INTO enquiries (name, email, message) VALUES (?, ?, ?)'
    );
    $stmt->execute([$name, $email, $message]);

    echo json_encode(['success' => true, 'message' => 'Message sent successfully.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error. Please try again.']);
}
