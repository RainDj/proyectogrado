<?php
session_start();
require_once "db.php";


if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}


if (!isset($_GET["id"])) {
    header("Location: documents.php?error=no_id");
    exit();
}

$documento_id = intval($_GET["id"]);
$usuario_id = intval($_SESSION["usuario_id"]);


$query = "SELECT ruta_archivo FROM documentos WHERE id = ? AND usuario_id = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("ii", $documento_id, $usuario_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    header("Location: documents.php?error=no_doc");
    exit();
}

$stmt->bind_result($ruta_archivo);
$stmt->fetch();

if (file_exists($ruta_archivo)) {
    unlink($ruta_archivo);
}


$delete = $conexion->prepare("DELETE FROM documentos WHERE id = ? AND usuario_id = ?");
$delete->bind_param("ii", $documento_id, $usuario_id);
$delete->execute();

$conexion->query("CALL actualizar_estado_vigencias()");

header("Location: documents.php?success=deleted");
exit();
?>