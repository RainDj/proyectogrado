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

$sql = "SELECT * FROM procesos_documentales WHERE usuario_id = ? ORDER BY fecha_creacion DESC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$procesos = $stmt->get_result();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Procesos documentales</h4>
        <a href="<?php echo BASE_URL; ?>pages/procesos/new.php" class="btn btn-primary">
            Nuevo proceso documental
        </a>
    </div>

    <?php if ($procesos->num_rows === 0): ?>
        <div class="alert alert-info">
            Aún no has creado procesos documentales.
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Entidad</th>
                            <th>Descripción</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = $procesos->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['entidad']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars(mb_strimwidth($p['descripcion'], 0, 80, '...'))); ?></td>
                                <td><?php echo $p['fecha_creacion']; ?></td>
                                <td>
                                    <?php if ($p['estado'] === 'completo'): ?>
                                        <span class="badge bg-success">Completo</span>
                                    <?php elseif ($p['estado'] === 'incompleto'): ?>
                                        <span class="badge bg-warning text-dark">Incompleto</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>pages/procesos/view.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
