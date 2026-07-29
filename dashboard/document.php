<?php
require_once __DIR__ . '/partials/auth-check.php';
require_once dirname(__DIR__) . '/backend/db.php';

$file = trim($_GET['file'] ?? '');

// Allow only files inside uploads/applications/, no path traversal
if (!$file || strpos($file, '..') !== false || !preg_match('#^uploads/applications/[^/]+$#', $file)) {
    http_response_code(400); exit('Invalid request.');
}

$abs = dirname(__DIR__) . '/' . $file;
if (!file_exists($abs)) {
    http_response_code(404); exit('File not found.');
}

$ext  = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
$mime = match($ext) {
    'pdf'  => 'application/pdf',
    'jpg', 'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    default => 'application/octet-stream',
};

$inline   = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png']);
$disposition = $inline ? 'inline' : 'attachment';

header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . basename($abs) . '"');
header('Content-Length: ' . filesize($abs));
header('X-Content-Type-Options: nosniff');
readfile($abs);
