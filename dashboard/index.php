<?php
require_once dirname(__DIR__) . '/backend/auth.php';
if (is_logged_in()) {
    header('Location: /dashboard/overview.php');
} else {
    header('Location: /dashboard/login.php');
}
exit;
