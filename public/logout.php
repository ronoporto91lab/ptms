<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/config/auth.php';

startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !hash_equals($_SESSION['csrf_token'] ?? '', (string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(405);
    exit('Invalid logout request.');
}

logoutUser();
header('Location: login.php?logged_out=1');
exit;
