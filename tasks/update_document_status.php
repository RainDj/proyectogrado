<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../db.php';

$hoy = date('Y-m-d');

$sql = "SELECT id, fecha_vencimiento FROM documentos";
$res = $conexion->query($sql);

while ($d = $res->fetch_assoc()) {
    $id = $d['id'];
    $fecha = $d['fecha_vencimiento'];

    $diferencia = (strtotime($fecha) - strtotime($hoy)) / 86400;

    if ($diferencia < 0) {
        $estado = 'vencido';
    } elseif ($diferencia <= 10) {
        $estado = 'proximo';
    } else {
        $estado = 'vigente';
    }

    $up = $conexion->prepare("UPDATE documentos SET estado = ? WHERE id = ?");
    $up->bind_param('si', $estado, $id);
    $up->execute();
    $up->close();
}
?>