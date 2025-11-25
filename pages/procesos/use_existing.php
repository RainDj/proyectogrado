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

$sql = "SELECT pi.*, c.nombre AS categoria, pd.usuario_id AS propietario, pi.proceso_id
        FROM proceso_items pi
        INNER JOIN categorias c ON pi.categoria_id = c.id
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

$categoria_id = $item['categoria_id'];
$proceso_id = $item['proceso_id'];

$sqlDocs = "SELECT * FROM documentos
            WHERE usuario_id = ? AND categoria_id = ?
            ORDER BY fecha_vencimiento DESC";
$stmt2 = $conexion->prepare($sqlDocs);
$stmt2->bind_param('ii', $usuario_id, $categoria_id);
$stmt2->execute();
$docs = $stmt2->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_id = intval($_POST['doc_id']);

    $estado = 'faltante';
    $sqlEstado = "SELECT estado FROM documentos WHERE id = ? AND usuario_id = ?";
    $e = $conexion->prepare($sqlEstado);
    $e->bind_param('ii', $doc_id, $usuario_id);
    $e->execute();
    $r = $e->get_result()->fetch_assoc();
    if ($r) {
        if ($r['estado'] === 'vigente' || $r['estado'] === 'proximo') $estado = 'vigente';
        if ($r['estado'] === 'vencido') $estado = 'vencido';
    }

    $up = $conexion->prepare("UPDATE proceso_items SET documento_id = ?, estado = ? WHERE id = ?");
    $up->bind_param('isi', $doc_id, $estado, $item_id);
    $up->execute();

    header('Location: ' . BASE_URL . 'pages/procesos/view.php?id=' . $proceso_id);
    exit;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h5>Usar documento existente: <?php echo htmlspecialchars($item['categoria']); ?></h5>
        </div>
        <div class="card-body">
            <?php if ($docs->num_rows === 0): ?>
                <div class="alert alert-warning">No tienes documentos cargados para esta categoría.</div>
            <?php else: ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Selecciona un documento</label>
                        <select name="doc_id" class="form-control" required>
                            <?php while ($d = $docs->fetch_assoc()): ?>
                                <option value="<?php echo $d['id']; ?>">
                                    <?php echo htmlspecialchars($d['nombre_archivo']); ?> —
                                    <?php echo $d['estado']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Asignar documento</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
