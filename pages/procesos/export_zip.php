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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proceso_id = intval($_POST['proceso_id'] ?? 0);
    $confirmado = isset($_POST['confirm']) ? 1 : 0;
} else {
    $proceso_id = intval($_GET['id'] ?? 0);
    $confirmado = 0;
}

if ($proceso_id <= 0) {
    header('Location: ' . BASE_URL . 'pages/procesos/index.php');
    exit;
}

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

$sqlItems = "SELECT pi.estado,
                    d.id AS documento_id,
                    d.nombre_archivo,
                    d.ruta_archivo,
                    c.nombre AS categoria
             FROM proceso_items pi
             LEFT JOIN documentos d ON pi.documento_id = d.id
             INNER JOIN categorias c ON pi.categoria_id = c.id
             WHERE pi.proceso_id = ?";
$stmt2 = $conexion->prepare($sqlItems);
$stmt2->bind_param('i', $proceso_id);
$stmt2->execute();
$resItems = $stmt2->get_result();

$faltantes = 0;
$vencidos = 0;
$vigentes = 0;
$docs = [];

while ($row = $resItems->fetch_assoc()) {
    if ($row['estado'] === 'faltante') $faltantes++;
    if ($row['estado'] === 'vencido') $vencidos++;
    if ($row['estado'] === 'vigente') $vigentes++;

    if (!empty($row['documento_id']) && !empty($row['ruta_archivo'])) {
        $docs[] = $row;
    }
}

$stmt2->close();

if ($confirmado && !empty($docs)) {
    if (!class_exists('ZipArchive')) {
        die('ZipArchive no está disponible en el servidor.');
    }

    $tmpDir = __DIR__ . '/../../uploads/tmp';
    if (!is_dir($tmpDir)) {
        mkdir($tmpDir, 0777, true);
    }

    $tmpZip = tempnam($tmpDir, 'proc_' . $proceso_id . '_');
    $zip = new ZipArchive();
    if ($zip->open($tmpZip, ZipArchive::OVERWRITE) !== true) {
        die('No fue posible crear el archivo ZIP.');
    }

    $added = 0;

    foreach ($docs as $d) {
        $filename = basename($d['ruta_archivo']);
        $ruta_fs  = __DIR__ . '/../../uploads/' . $usuario_id . '/' . $filename;

        if (file_exists($ruta_fs)) {
            $nombre_categoria = preg_replace('/[^A-Za-z0-9 _.-]/', '', $d['categoria']);
            $nombre_categoria = trim($nombre_categoria);

            $nombre_original = strtolower(pathinfo($filename, PATHINFO_FILENAME));
            $nombre_normalizado = preg_replace('/[^a-z0-9 _.-]/', '', $nombre_original);
            $nombre_normalizado = preg_replace('/[ ]+/', ' ', $nombre_normalizado);
            $nombre_normalizado = str_replace(' ', '_', $nombre_normalizado);

            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $nombre_con_extension = $nombre_normalizado . '.' . $extension;

            $nombre_zip = $nombre_categoria . ' - ' . $nombre_con_extension;

            $zip->addFile($ruta_fs, $nombre_zip);
            $added++;
        }
    }

    $zip->close();

    if ($added === 0) {
        if (file_exists($tmpZip)) {
            unlink($tmpZip);
        }
        die('No se pudo agregar ningún archivo al ZIP. Verifica que los archivos estén en /uploads/' . $usuario_id . '/');
    }

    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="proceso_' . $proceso_id . '.zip"');
    header('Content-Length: ' . filesize($tmpZip));
    readfile($tmpZip);

    if (file_exists($tmpZip)) {
        unlink($tmpZip);
    }

    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h5>Descargar ZIP del proceso documental</h5>
        </div>
        <div class="card-body">
            <p><strong>Entidad:</strong> <?php echo htmlspecialchars($proceso['entidad']); ?></p>
            <p><strong>Descripción:</strong> <?php echo nl2br(htmlspecialchars($proceso['descripcion'])); ?></p>

            <?php if (empty($docs)): ?>
                <div class="alert alert-warning">
                    No hay documentos asociados a este proceso para generar el ZIP.
                </div>
                <a href="<?php echo BASE_URL; ?>pages/procesos/view.php?id=<?php echo $proceso_id; ?>" class="btn btn-secondary">
                    Volver al proceso
                </a>
            <?php else: ?>
                <div class="alert alert-info">
                    Documentos vigentes: <?php echo $vigentes; ?>,
                    vencidos: <?php echo $vencidos; ?>,
                    faltantes: <?php echo $faltantes; ?>.
                </div>

                <?php if ($faltantes > 0 || $vencidos > 0): ?>
                    <div class="alert alert-warning">
                        Aún hay documentos faltantes o no vigentes en este proceso.
                        ¿Deseas descargar el ZIP de todos modos?
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        Todos los documentos requeridos están vigentes. Puedes generar el ZIP para entrega.
                    </div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="proceso_id" value="<?php echo $proceso_id; ?>">
                    <input type="hidden" name="confirm" value="1">

                    <button type="submit" class="btn btn-primary">
                        Descargar ZIP
                    </button>

                    <a href="<?php echo BASE_URL; ?>pages/procesos/view.php?id=<?php echo $proceso_id; ?>" class="btn btn-secondary">
                        Cancelar
                    </a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
