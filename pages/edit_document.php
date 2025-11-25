<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$usuario_id   = (int) $_SESSION['usuario_id'];
$documento_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($documento_id <= 0) {
    header('Location: ' . BASE_URL . 'pages/documents.php');
    exit;
}

$errores = [];
$exito   = '';

if (isset($_GET['delete']) && (int)$_GET['delete'] === 1) {
    $stmtDel = $conexion->prepare(
        'SELECT ruta_archivo FROM documentos WHERE id = ? AND usuario_id = ?'
    );
    $stmtDel->bind_param('ii', $documento_id, $usuario_id);
    $stmtDel->execute();
    $stmtDel->bind_result($ruta_archivo);
    if ($stmtDel->fetch()) {
        $ruta_fisica = __DIR__ . '/../' . $ruta_archivo;
        if (is_file($ruta_fisica)) {
            @unlink($ruta_fisica);
        }
    }
    $stmtDel->close();

    $del = $conexion->prepare(
        'DELETE FROM documentos WHERE id = ? AND usuario_id = ?'
    );
    $del->bind_param('ii', $documento_id, $usuario_id);
    $del->execute();
    $del->close();

    header('Location: ' . BASE_URL . 'pages/documents.php?success=deleted');
    exit;
}

$stmt = $conexion->prepare(
    'SELECT id, categoria_id, nombre_archivo, ruta_archivo, fecha_emision, fecha_vencimiento
     FROM documentos
     WHERE id = ? AND usuario_id = ?'
);
$stmt->bind_param('ii', $documento_id, $usuario_id);
$stmt->execute();
$res = $stmt->get_result();
$doc = $res->fetch_assoc();
$stmt->close();

if (!$doc) {
    header('Location: ' . BASE_URL . 'pages/documents.php');
    exit;
}

$categorias = $conexion->query('SELECT id, nombre FROM categorias ORDER BY nombre ASC');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria_id      = (int)($_POST['categoria_id'] ?? 0);
    $fecha_emision     = $_POST['fecha_emision'] ?? '';
    $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? '';

    if ($categoria_id <= 0) {
        $errores[] = 'Debes seleccionar una categoría.';
    }
    if ($fecha_emision === '') {
        $errores[] = 'La fecha de emisión es obligatoria.';
    }
    if ($fecha_vencimiento === '') {
        $errores[] = 'La fecha de vencimiento es obligatoria.';
    }
    if ($fecha_emision !== '' && $fecha_vencimiento !== '' && $fecha_emision > $fecha_vencimiento) {
        $errores[] = 'La fecha de emisión no puede ser posterior a la fecha de vencimiento.';
    }

    $nombre_archivo = $doc['nombre_archivo'];
    $ruta_archivo   = $doc['ruta_archivo'];

    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/' . $usuario_id;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $nombre_subido = basename($_FILES['archivo']['name']);
        $nombre_seguro = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombre_subido);
        $destino       = $upload_dir . '/' . $nombre_seguro;

        if (move_uploaded_file($_FILES['archivo']['tmp_name'], $destino)) {
            $ruta_fisica_anterior = __DIR__ . '/../' . $doc['ruta_archivo'];
            if (is_file($ruta_fisica_anterior)) {
                @unlink($ruta_fisica_anterior);
            }

            $nombre_archivo = $nombre_subido;
            $ruta_archivo   = 'uploads/' . $usuario_id . '/' . $nombre_seguro;
        } else {
            $errores[] = 'No se pudo subir el archivo al servidor.';
        }
    }

    if (empty($errores)) {
        $upd = $conexion->prepare(
            'UPDATE documentos
             SET categoria_id = ?, nombre_archivo = ?, ruta_archivo = ?, fecha_emision = ?, fecha_vencimiento = ?
             WHERE id = ? AND usuario_id = ?'
        );
        $upd->bind_param(
            'issssii',
            $categoria_id,
            $nombre_archivo,
            $ruta_archivo,
            $fecha_emision,
            $fecha_vencimiento,
            $documento_id,
            $usuario_id
        );

        if ($upd->execute()) {
            $exito = 'Documento actualizado correctamente.';

            $doc['categoria_id']      = $categoria_id;
            $doc['nombre_archivo']    = $nombre_archivo;
            $doc['ruta_archivo']      = $ruta_archivo;
            $doc['fecha_emision']     = $fecha_emision;
            $doc['fecha_vencimiento'] = $fecha_vencimiento;
        } else {
            $errores[] = 'Error al actualizar el documento.';
        }

        $upd->close();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <div class="card p-4">
      <h2 class="mb-3">Editar documento</h2>

      <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errores as $e): ?>
              <li><?php echo htmlspecialchars($e); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($exito !== ''): ?>
        <div class="alert alert-success">
          <?php echo htmlspecialchars($exito); ?>
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label">Categoría</label>
          <select name="categoria_id" class="form-select" required>
            <option value="">Seleccione...</option>
            <?php
            mysqli_data_seek($categorias, 0);
            while ($c = $categorias->fetch_assoc()):
            ?>
              <option value="<?php echo $c['id']; ?>"
                <?php echo ((int)$doc['categoria_id'] === (int)$c['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($c['nombre']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Fecha de emisión</label>
          <input type="date" name="fecha_emision" class="form-control" required
                 value="<?php echo htmlspecialchars($doc['fecha_emision']); ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Fecha de vencimiento</label>
          <input type="date" name="fecha_vencimiento" class="form-control" required
                 value="<?php echo htmlspecialchars($doc['fecha_vencimiento']); ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Archivo actual</label><br>
          <a href="<?php echo BASE_URL . htmlspecialchars($doc['ruta_archivo']); ?>" target="_blank">
            <?php echo htmlspecialchars($doc['nombre_archivo']); ?>
          </a>
        </div>

        <div class="mb-3">
          <label class="form-label">Reemplazar archivo (opcional)</label>
          <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
          <small class="text-muted">Si no seleccionas un archivo, se conservará el existente.</small>
        </div>

        <div class="d-flex justify-content-between">
          <a href="documents.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
          </a>
          <div>
            <a href="edit_document.php?id=<?php echo $documento_id; ?>&delete=1"
               class="btn btn-outline-danger me-2"
               onclick="return confirm('¿Eliminar este documento? Esta acción no se puede deshacer.');">
              <i class="bi bi-trash me-1"></i> Eliminar
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save me-1"></i> Guardar cambios
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
