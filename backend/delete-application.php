<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /dashboard/applications.php');
    exit;
}

if (!csrf_verify()) {
    http_response_code(403);
    echo '<p>Security error — please go back and try again.</p>';
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    header('Location: /dashboard/applications.php?msg=error');
    exit;
}

try {
    $pdo = db();

    // Block deletion if a resident record is linked to this application
    $linked = $pdo->prepare('SELECT COUNT(*) FROM residents WHERE application_id = ?');
    $linked->execute([$id]);
    if ((int)$linked->fetchColumn() > 0) {
        header('Location: /dashboard/applications.php?msg=has_resident');
        exit;
    }

    $stmt = $pdo->prepare('SELECT doc_id_path, doc_enrollment_path, doc_bursary_path, doc_sponsorship_path, doc_income_path FROM applications WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row) {
        // Clean up uploaded files
        $docCols = ['doc_id_path','doc_enrollment_path','doc_bursary_path','doc_sponsorship_path','doc_income_path'];
        foreach ($docCols as $col) {
            if (!empty($row[$col])) {
                $fullPath = SITE_ROOT . '/' . ltrim(str_replace('\\', '/', $row[$col]), '/');
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }
        }

        $pdo->prepare('DELETE FROM applications WHERE id = ?')->execute([$id]);
        audit_log($_SESSION['admin_id'] ?? null, 'application_deleted', 'application', $id);
    }

    header('Location: /dashboard/applications.php?msg=deleted');
    exit;

} catch (Throwable $e) {
    error_log('delete-application error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    header('Location: /dashboard/applications.php?msg=error');
    exit;
}
