<?php
require_once dirname(__DIR__, 2) . '/backend/auth.php';
require_login();

// Prevent sensitive pages from being cached
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');

// CSRF: verify token on all state-changing POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
    csrf_abort();
}

$user = current_user();
