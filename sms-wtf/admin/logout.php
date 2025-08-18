<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();

if ($auth->isAdminLoggedIn()) {
    $auth->logoutAdmin();
}

header('Location: login.php');
exit;
?>
