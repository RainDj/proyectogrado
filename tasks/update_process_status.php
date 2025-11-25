<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../db.php';

$sql = "SELECT id FROM procesos_documentales";
$res = $conexion->query($sql);

while ($p = $res->fetch_assoc()) {
    $proceso_id = $p['id'];

    $items = $conexion->query("SELECT estado FROM proceso_items WHERE proceso_id = $proceso_id");

    $vigente = 0;
    $faltante = 0;
    $vencido = 0;
    $total = 0;

    while ($i = $items->fetch_assoc()) {
        $total++;
        if ($i['estado'] === 'vigente') $vigente++;
        if ($i['estado'] === 'faltante') $faltante++;
        if ($i['estado'] === 'vencido') $vencido++;
    }

    if ($total > 0 && $faltante === 0 && $vencido === 0) {
        $nuevo = 'completo';
    } elseif ($vigente > 0 || $vencido > 0) {
        $nuevo = 'incompleto';
    } else {
        $nuevo = 'pendiente';
    }

    $up = $conexion->prepare("UPDATE procesos_documentales SET estado = ? WHERE id = ?");
    $up->bind_param('si', $nuevo, $proceso_id);
    $up->execute();
    $up->close();
}
?>