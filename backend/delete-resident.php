<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /dashboard/residents.php');
    exit;
}

if (!csrf_verify()) {
    http_response_code(403);
    echo '<p>Security error — please go back and try again.</p>';
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    header('Location: /dashboard/residents.php?msg=error');
    exit;
}

try {
    $pdo = db();

    // Fetch resident to get room_id and status
    $stmt = $pdo->prepare('SELECT room_id, status FROM residents WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row) {
        // Free the room only if tenant was still active
        if ($row['status'] === 'active') {
            $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ?")->execute([$row['room_id']]);
        }

        $pdo->prepare('DELETE FROM residents WHERE id = ?')->execute([$id]);
        audit_log($_SESSION['admin_id'] ?? null, 'resident_deleted', 'resident', $id);
    }

    header('Location: /dashboard/residents.php?msg=deleted');
    exit;

} catch (Throwable $e) {
    error_log('delete-resident error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    header('Location: /dashboard/residents.php?msg=error');
    exit;
}
