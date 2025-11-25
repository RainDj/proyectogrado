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

$stmt = $conexion->prepare("SELECT id, nombre, descripcion, vigencia_tipo, vigencia_cantidad FROM categorias ORDER BY nombre ASC");
$stmt->execute();
$result = $stmt->get_result();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Categorías de documentos</h4>
        <a href="<?php echo BASE_URL; ?>pages/categorias/new.php" class="btn btn-primary rounded-pill px-4">
            Nueva categoría
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Tipo vigencia</th>
                        <th>Cantidad</th>
                        <th style="width: 140px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">
                                No hay categorías registradas. Haz clic en <strong>Nueva categoría</strong> para crear la primera.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($c = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($c['descripcion']); ?></td>
                                <td>
                                    <?php
                                    if ($c['vigencia_tipo'] === 'no_aplica') {
                                        echo 'No aplica';
                                    } elseif ($c['vigencia_tipo'] === 'dias') {
                                        echo 'Días';
                                    } else {
                                        echo htmlspecialchars($c['vigencia_tipo']);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($c['vigencia_tipo'] === 'dias') {
                                        echo (int)$c['vigencia_cantidad'];
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>pages/categorias/edit.php?id=<?php echo $c['id']; ?>"
                                       class="btn btn-sm btn-outline-secondary me-1">
                                        Editar
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>pages/categorias/delete.php?id=<?php echo $c['id']; ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('¿Eliminar esta categoría? Solo es posible si no está en uso.');">
                                        Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$stmt->close();
require_once __DIR__ . '/../../includes/footer.php';
?>
