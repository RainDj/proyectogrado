<?php
require_once __DIR__ . '/../db.php';
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$errores = [];
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
	$fecha_emision = $_POST['fecha_emision'] ?? '';

	if ($categoria_id <= 0 || $fecha_emision === '') {
		$errores[] = 'Todos los campos son obligatorios.';
	}


    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        $errores[] = 'Debes seleccionar un archivo válido.';
    }

    if (empty($errores)) {
        $uploadDir = __DIR__ . '/uploads/' . $usuario_id;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $nombreArchivo = basename($_FILES['archivo']['name']);
        $nombreSeguro = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombreArchivo);
        $rutaDestino = $uploadDir . '/' . $nombreSeguro;

        if (move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaDestino)) {
            $rutaRelativa = 'uploads/' . $usuario_id . '/' . $nombreSeguro;
			// Obtener regla de vigencia de la categoría
			$stmtCat = $conexion->prepare('SELECT vigencia_tipo, vigencia_cantidad FROM categorias WHERE id = ?');
			$stmtCat->bind_param('i', $categoria_id);
			$stmtCat->execute();
			$stmtCat->bind_result($vigencia_tipo, $vigencia_cantidad);
			$stmtCat->fetch();
			$stmtCat->close();

			// Calcular fecha de vencimiento
			$fecha_vencimiento = $fecha_emision; // valor por defecto

			if ($vigencia_tipo === 'dias' && !empty($vigencia_cantidad)) {
				$dt = new DateTime($fecha_emision);
				$dt->modify('+' . intval($vigencia_cantidad) . ' days');
				$fecha_vencimiento = $dt->format('Y-m-d');
			}
			// Si es 'no_aplica', dejamos fecha_vencimiento = fecha_emision
			// y el procedimiento la marcará siempre como 'vigente'

            $stmt = $conexion->prepare('INSERT INTO documentos (usuario_id, categoria_id, nombre_archivo, ruta_archivo, fecha_emision, fecha_vencimiento) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('iissss', $usuario_id, $categoria_id, $nombreArchivo, $rutaRelativa, $fecha_emision, $fecha_vencimiento);
            if ($stmt->execute()) {
                $exito = 'Documento cargado correctamente.';
                $conexion->query("CALL actualizar_estado_vigencias()");
            } else {
                $errores[] = 'Error al guardar el registro en la base de datos.';
                @unlink($rutaDestino);
            }
            $stmt->close();
        } else {
            $errores[] = 'No se pudo mover el archivo al servidor.';
        }
    }
}

$categorias = $conexion->query('SELECT id, nombre FROM categorias ORDER BY nombre ASC');

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <div class="card p-4">
      <h2 class="mb-3">Subir documento</h2>
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
      <form method="post" action="" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label">Categoría de documento</label>
          <select name="categoria_id" class="form-select">
            <option value="">Seleccione...</option>
            <?php while ($c = $categorias->fetch_assoc()): ?>
              <option value="<?php echo $c['id']; ?>" <?php echo (isset($_POST['categoria_id']) && (int)$_POST['categoria_id'] === (int)$c['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($c['nombre']); ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
		<div class="mb-3">
		  <label class="form-label">Fecha de emisión</label>
		  <input type="date" name="fecha_emision" class="form-control" 
				 value="<?php echo htmlspecialchars($_POST['fecha_emision'] ?? ''); ?>">
		  <small class="text-muted">
			La fecha de vencimiento se calculará automáticamente según la categoría seleccionada.
		  </small>
		</div>
        <div class="mb-3">
          <label class="form-label">Archivo</label>
          <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Guardar documento</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
