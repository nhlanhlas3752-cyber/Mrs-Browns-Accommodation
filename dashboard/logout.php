<?php
require_once dirname(__DIR__) . '/backend/auth.php';
logout();
header('Location: /dashboard/login.php');
exit;
