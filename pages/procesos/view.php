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

$usuario_id = $_SESSION['usuario_id'];

if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . 'pages/procesos/index.php');
    exit;
}

$proceso_id = intval($_GET['id']);

$sql = "SELECT * FROM procesos_documentales WHERE id = ? AND usuario_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('ii', $proceso_id, $usuario_id);
$stmt->execute();
$proceso = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$proceso) {
    header('Location: ' . BASE_URL . 'pages/procesos/index.php');
    exit;
}

$sqlItems = "SELECT pi.id AS item_id, pi.estado, pi.documento_id, c.nombre AS categoria
             FROM proceso_items pi
             INNER JOIN categorias c ON pi.categoria_id = c.id
             WHERE pi.proceso_id = ?
             ORDER BY c.nombre ASC";
$stmt2 = $conexion->prepare($sqlItems);
$stmt2->bind_param('i', $proceso_id);
$stmt2->execute();
$items = $stmt2->get_result();

$total = 0;
$vigentes = 0;
$faltantes = 0;
$vencidos = 0;

foreach ($items as $it) {
    $total++;
    if ($it['estado'] === 'vigente') $vigentes++;
    if ($it['estado'] === 'faltante') $faltantes++;
    if ($it['estado'] === 'vencido') $vencidos++;
}

if ($faltantes === 0 && $vencidos === 0) {
    $nuevo_estado = 'completo';
} elseif ($vigentes > 0 || $vencidos > 0) {
    $nuevo_estado = 'incompleto';
} else {
    $nuevo_estado = 'pendiente';
}

if ($nuevo_estado !== $proceso['estado']) {
    $update = $conexion->prepare("UPDATE procesos_documentales SET estado = ? WHERE id = ?");
    $update->bind_param('si', $nuevo_estado, $proceso_id);
    $update->execute();
    $update->close();
    $proceso['estado'] = $nuevo_estado;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0"><?php echo htmlspecialchars($proceso['entidad']); ?></h5>
            <a href="<?php echo BASE_URL; ?>pages/procesos/index.php" class="btn btn-outline-secondary btn-sm">Volver</a>
        </div>

        <div class="card-body">
            <p><strong>Descripción:</strong> <?php echo nl2br(htmlspecialchars($proceso['descripcion'])); ?></p>
            <p><strong>Fecha:</strong> <?php echo $proceso['fecha_creacion']; ?></p>
            <p><strong>Estado del proceso:</strong> 
                <?php if ($proceso['estado'] === 'completo'): ?>
                    <span class="badge bg-success">Completo</span>
                <?php elseif ($proceso['estado'] === 'incompleto'): ?>
                    <span class="badge bg-warning text-dark">Incompleto</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Pendiente</span>
                <?php endif; ?>
            </p>

            <div class="row text-center mb-4">
                <div class="col">
                    <span class="badge bg-success"><?php echo $vigentes; ?> Vigentes</span>
                </div>
                <div class="col">
                    <span class="badge bg-warning text-dark"><?php echo $vencidos; ?> Vencidos</span>
                </div>
                <div class="col">
                    <span class="badge bg-danger"><?php echo $faltantes; ?> Faltantes</span>
                </div>
            </div>

            <h6 class="mb-3">Documentos requeridos</h6>

            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>

                <?php
                $stmt2->execute();
                $items = $stmt2->get_result();
                foreach ($items as $it):
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($it['categoria']); ?></td>

                        <td>
                            <?php if ($it['estado'] === 'vigente'): ?>
                                <span class="badge bg-success">Vigente</span>
                            <?php elseif ($it['estado'] === 'vencido'): ?>
                                <span class="badge bg-warning text-dark">Vencido</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Faltante</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <a href="<?php echo BASE_URL; ?>pages/procesos/use_existing.php?item=<?php echo $it['item_id']; ?>" class="btn btn-sm btn-outline-primary">
                                Usar existente
                            </a>

                            <a href="<?php echo BASE_URL; ?>pages/procesos/add_document.php?item=<?php echo $it['item_id']; ?>" class="btn btn-sm btn-outline-success">
                                Subir nuevo
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
				<div class="mb-3">
                </tbody>
            </table>
        	<a href="<?php echo BASE_URL; ?>pages/procesos/export_zip.php?id=<?php echo $proceso_id; ?>" class="btn btn-outline-primary">
				Descargar ZIP para entrega
			</a>

		</div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
