<?php
require_once __DIR__ . '/config/config.php';

session_start();
if (isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . 'pages/dashboard.php');
    exit;
} else {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}
?>
