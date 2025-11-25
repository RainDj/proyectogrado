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

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre          = trim($_POST['nombre'] ?? '');
    $descripcion     = trim($_POST['descripcion'] ?? '');
    $vigencia_tipo   = $_POST['vigencia_tipo'] ?? 'no_aplica';
    $vigencia_cant   = $_POST['vigencia_cantidad'] ?? null;

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    }

    if ($vigencia_tipo !== 'no_aplica' && $vigencia_tipo !== 'dias') {
        $errores[] = 'El tipo de vigencia no es válido.';
    }

    if ($vigencia_tipo === 'dias') {
        $vigencia_cant = (int)$vigencia_cant;
        if ($vigencia_cant <= 0) {
            $errores[] = 'La cantidad de días debe ser mayor a cero.';
        }
    } else {
        $vigencia_cant = null;
    }

    if (empty($errores)) {
        $sql = "INSERT INTO categorias (nombre, descripcion, vigencia_tipo, vigencia_cantidad)
                VALUES (?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(
            'sssi',
            $nombre,
            $descripcion,
            $vigencia_tipo,
            $vigencia_cant
        );
        $stmt->execute();
        $stmt->close();

        header('Location: ' . BASE_URL . 'pages/categorias/index.php');
        exit;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Nueva categoría</h5>
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

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" required
                                   value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2"><?php
                                echo isset($descripcion) ? htmlspecialchars($descripcion) : '';
                            ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo de vigencia</label>
                            <select name="vigencia_tipo" id="vigencia_tipo" class="form-select" required>
                                <option value="no_aplica" <?php echo (isset($vigencia_tipo) && $vigencia_tipo === 'no_aplica') ? 'selected' : ''; ?>>
                                    No aplica (siempre vigente)
                                </option>
                                <option value="dias" <?php echo (isset($vigencia_tipo) && $vigencia_tipo === 'dias') ? 'selected' : ''; ?>>
                                    Por número de días
                                </option>
                            </select>
                        </div>

                        <div class="mb-3" id="grupo_vigencia_dias" style="<?php
                            echo (isset($vigencia_tipo) && $vigencia_tipo === 'dias') ? '' : 'display:none;';
                        ?>">
                            <label class="form-label">Cantidad de días de vigencia</label>
                            <input type="number" name="vigencia_cantidad" class="form-control"
                                   value="<?php echo isset($vigencia_cant) ? (int)$vigencia_cant : ''; ?>">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>pages/categorias/index.php" class="btn btn-secondary">
                                Volver
                            </a>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectTipo = document.getElementById('vigencia_tipo');
    const grupoDias  = document.getElementById('grupo_vigencia_dias');

    if (!selectTipo || !grupoDias) return;

    selectTipo.addEventListener('change', function () {
        if (this.value === 'dias') {
            grupoDias.style.display = '';
        } else {
            grupoDias.style.display = 'none';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
