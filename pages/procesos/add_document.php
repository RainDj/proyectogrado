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

if (!isset($_GET['item'])) {
    header('Location: ' . BASE_URL . 'pages/procesos/index.php');
    exit;
}

$item_id = intval($_GET['item']);
$usuario_id = $_SESSION['usuario_id'];

$sql = "SELECT pi.*, pi.proceso_id, pi.categoria_id, pd.usuario_id AS propietario
        FROM proceso_items pi
        INNER JOIN procesos_documentales pd ON pi.proceso_id = pd.id
        WHERE pi.id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item || $item['propietario'] != $usuario_id) {
    header('Location: ' . BASE_URL . 'pages/procesos/index.php');
    exit;
}

$proceso_id = $item['proceso_id'];
$categoria_id = $item['categoria_id'];

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== 0) {
        $errores[] = 'Debes seleccionar un archivo válido.';
    }

    $nombre_archivo = $_FILES['archivo']['name'];
    $tmp = $_FILES['archivo']['tmp_name'];

    $ext = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'jpg', 'png', 'jpeg'])) {
        $errores[] = 'Formato no permitido.';
    }

    $dir = __DIR__ . '/../../uploads/' . $usuario_id;
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $nuevo_nombre = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $nombre_archivo);
    $ruta_destino = $dir . '/' . $nuevo_nombre;
    $ruta_bd = 'uploads/' . $usuario_id . '/' . $nuevo_nombre;

    if (empty($errores)) {
        move_uploaded_file($tmp, $ruta_destino);

        $fecha_emision = $_POST['fecha_emision'];
        $fecha_vencimiento = $_POST['fecha_vencimiento'];

        $estado = 'vigente';
        $hoy = date('Y-m-d');
        $diferencia = (strtotime($fecha_vencimiento) - strtotime($hoy)) / 86400;

        if ($diferencia < 0) $estado = 'vencido';
        elseif ($diferencia <= 10) $estado = 'proximo';

        $sqlDoc = "INSERT INTO documentos (usuario_id, categoria_id, nombre_archivo, ruta_archivo, fecha_emision, fecha_vencimiento, estado)
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt2 = $conexion->prepare($sqlDoc);
        $stmt2->bind_param('iisssss', $usuario_id, $categoria_id, $nombre_archivo, $ruta_bd, $fecha_emision, $fecha_vencimiento, $estado);
        $stmt2->execute();
        $doc_id = $stmt2->insert_id;

        $up = $conexion->prepare("UPDATE proceso_items SET documento_id = ?, estado = ? WHERE id = ?");
        $up->bind_param('isi', $doc_id, $estado, $item_id);
        $up->execute();

        header('Location: ' . BASE_URL . 'pages/procesos/view.php?id=' . $proceso_id);
        exit;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h5>Subir documento</h5>
        </div>
        <div class="card-body">

            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errores as $e): ?>
                            <li><?php echo $e; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">

                <div class="mb-3">
                    <label class="form-label">Archivo</label>
                    <input type="file" name="archivo" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Fecha de emisión</label>
                    <input type="date" name="fecha_emision" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Fecha de vencimiento</label>
                    <input type="date" name="fecha_vencimiento" class="form-control" required>
                </div>

                <button class="btn btn-success">Cargar documento</button>

            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
