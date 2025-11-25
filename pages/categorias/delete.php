<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . BASE_URL . 'pages/categorias/index.php');
    exit;
}

$en_documentos = $conexion->query("SELECT id FROM documentos WHERE categoria_id = $id LIMIT 1");
$en_items      = $conexion->query("SELECT id FROM proceso_items WHERE categoria_id = $id LIMIT 1");

if ($en_documentos->num_rows > 0 || $en_items->num_rows > 0) {
    header('Location: ' . BASE_URL . 'pages/categorias/index.php?error=en_uso');
    exit;
}

$stmt = $conexion->prepare("DELETE FROM categorias WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

header('Location: ' . BASE_URL . 'pages/categorias/index.php?success=deleted');
exit;
