<?php

require_once __DIR__ . '/../config/config.php';
define('BASE_URL', '/pgd2');

session_start();
session_unset();
session_destroy();
header('Location: ' . BASE_URL . 'auth/login.php');
exit;
?>
