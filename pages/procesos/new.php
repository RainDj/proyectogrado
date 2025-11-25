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

require_once __DIR__ . '/../../includes/header.php';

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entidad = trim($_POST['entidad'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $usuario_id = $_SESSION['usuario_id'];

    if ($entidad === '') {
        $errores[] = 'La entidad es obligatoria.';
    }

    if (empty($errores)) {
        $sql = "INSERT INTO procesos_documentales (usuario_id, entidad, descripcion, estado)
                VALUES (?, ?, ?, 'pendiente')";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('iss', $usuario_id, $entidad, $descripcion);

        if ($stmt->execute()) {
            $proceso_id = $stmt->insert_id;
            $stmt->close();

            $cats = $conexion->query("SELECT id FROM categorias ORDER BY nombre ASC");

            if ($cats && $cats->num_rows > 0) {
                while ($c = $cats->fetch_assoc()) {
                    $categoria_id = (int)$c['id'];

                    $sqlDoc = "SELECT id, estado
                               FROM documentos
                               WHERE usuario_id = ? AND categoria_id = ?
                               ORDER BY fecha_vencimiento DESC
                               LIMIT 1";
                    $stmtDoc = $conexion->prepare($sqlDoc);
                    $stmtDoc->bind_param('ii', $usuario_id, $categoria_id);
                    $stmtDoc->execute();
                    $res = $stmtDoc->get_result();

                    $documento_id = null;
                    $estado_item = 'faltante';

                    if ($res && $res->num_rows > 0) {
                        $doc = $res->fetch_assoc();
                        $documento_id = (int)$doc['id'];

                        if ($doc['estado'] === 'vigente' || $doc['estado'] === 'proximo') {
                            $estado_item = 'vigente';
                        } elseif ($doc['estado'] === 'vencido') {
                            $estado_item = 'vencido';
                        }
                    }

                    $stmtDoc->close();

                    $sqlItem = "INSERT INTO proceso_items (proceso_id, categoria_id, documento_id, estado)
                                VALUES (?, ?, ?, ?)";
                    $stmtItem = $conexion->prepare($sqlItem);
                    $stmtItem->bind_param('iiis', $proceso_id, $categoria_id, $documento_id, $estado_item);
                    $stmtItem->execute();
                    $stmtItem->close();
                }
            }

            header('Location: ' . BASE_URL . 'pages/procesos/view.php?id=' . $proceso_id);
            exit;
        } else {
            $errores[] = 'No fue posible crear el proceso documental.';
        }
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Nuevo Proceso Documental</h5>
                    <a href="<?php echo BASE_URL; ?>pages/procesos/index.php" class="btn btn-sm btn-outline-secondary">
                        Volver al listado
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($errores)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errores as $e): ?>
                                    <li><?php echo htmlspecialchars($e); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="entidad" class="form-label">Entidad</label>
                            <input
                                type="text"
                                class="form-control"
                                id="entidad"
                                name="entidad"
                                required
                                value="<?php echo htmlspecialchars($_POST['entidad'] ?? ''); ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción del proceso</label>
                            <textarea
                                class="form-control"
                                id="descripcion"
                                name="descripcion"
                                rows="3"
                            ><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Crear proceso documental
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
