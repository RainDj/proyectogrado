<?php
require_once __DIR__ . '/../db.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$conexion->query("CALL actualizar_estado_vigencias()");

$totales = [
    'vigente' => 0,
    'proximo' => 0,
    'vencido' => 0
];

$sqlTotales = "SELECT estado, COUNT(*) AS total 
               FROM documentos 
               WHERE usuario_id = ? 
               GROUP BY estado";

$stmtTotales = $conexion->prepare($sqlTotales);
$stmtTotales->bind_param('i', $usuario_id);
$stmtTotales->execute();
$resultTotales = $stmtTotales->get_result();

while ($row = $resultTotales->fetch_assoc()) {
    $totales[$row['estado']] = (int)$row['total'];
}

$stmtTotales->close();

$sqlAlertas = "SELECT d.id, c.nombre AS categoria, d.nombre_archivo, d.fecha_vencimiento
               FROM documentos d
               INNER JOIN categorias c ON d.categoria_id = c.id
               WHERE d.usuario_id = ? AND d.estado = 'proximo'
               ORDER BY d.fecha_vencimiento ASC
               LIMIT 5";

$stmtAlertas = $conexion->prepare($sqlAlertas);
$stmtAlertas->bind_param('i', $usuario_id);
$stmtAlertas->execute();
$alertas = $stmtAlertas->get_result();
$cantidadAlertas = $alertas->num_rows;

$estadoFiltro = $_GET['estado'] ?? 'todos';

if (in_array($estadoFiltro, ['vigente', 'proximo', 'vencido'])) {
    $sqlDocs = "SELECT d.id, c.nombre AS categoria, d.nombre_archivo, d.ruta_archivo,
                       d.fecha_emision, d.fecha_vencimiento, d.estado
                FROM documentos d
                INNER JOIN categorias c ON d.categoria_id = c.id
                WHERE d.usuario_id = ? AND d.estado = ?
                ORDER BY d.fecha_vencimiento ASC";
    $stmt = $conexion->prepare($sqlDocs);
    $stmt->bind_param('is', $usuario_id, $estadoFiltro);
} else {
    $sqlDocs = "SELECT d.id, c.nombre AS categoria, d.nombre_archivo, d.ruta_archivo,
                       d.fecha_emision, d.fecha_vencimiento, d.estado
                FROM documentos d
                INNER JOIN categorias c ON d.categoria_id = c.id
                WHERE d.usuario_id = ?
                ORDER BY d.fecha_vencimiento ASC";
    $stmt = $conexion->prepare($sqlDocs);
    $stmt->bind_param('i', $usuario_id);
}

$stmt->execute();
$docs = $stmt->get_result();

include __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4">Hola, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></h1>

<?php if ($cantidadAlertas > 0) { ?>
  <div class="alert alert-warning d-flex flex-column flex-md-row justify-content-between align-items-md-center" role="alert">
    <div>
      <h5 class="alert-heading mb-1">Documentos próximos a vencer</h5>
      <p class="mb-2 mb-md-0">
        Tienes <strong><?php echo $cantidadAlertas; ?></strong> documento(s) que vencerán pronto.
        Actualízalos para evitar perder oportunidades contractuales.
      </p>
    </div>
    <div class="ms-md-3">
      <a href="documents.php?estado=proximo" class="btn btn-sm btn-outline-dark">
        <i class="bi bi-list-ul me-1"></i> Ver todos
      </a>
    </div>
  </div>

  <div class="card p-3 mb-4">
    <h6 class="mb-3">Próximos vencimientos</h6>
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead>
          <tr>
            <th>Categoría</th>
            <th>Nombre archivo</th>
            <th>Fecha de vencimiento</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($a = $alertas->fetch_assoc()) { ?>
          <tr>
            <td><?php echo htmlspecialchars($a['categoria']); ?></td>
            <td><?php echo htmlspecialchars($a['nombre_archivo']); ?></td>
            <td><?php echo htmlspecialchars($a['fecha_vencimiento']); ?></td>
            <td>
              <a href="edit_document.php?id=<?php echo $a['id']; ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-pencil-square me-1"></i> Actualizar
              </a>
            </td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  </div>

<?php } elseif ($totales['vencido'] > 0) { ?>
  <div class="alert alert-danger d-flex flex-column flex-md-row justify-content-between align-items-md-center" role="alert">
    <div>
      <h5 class="alert-heading mb-1">Documentos vencidos</h5>
      <p class="mb-2 mb-md-0">
        Tienes <strong><?php echo $totales['vencido']; ?></strong> documento(s) vencidos. 
        Actualízalos para restablecer tu habilitación en futuros procesos contractuales.
      </p>
    </div>
    <div class="ms-md-3">
      <a href="documents.php?estado=vencido" class="btn btn-sm btn-outline-dark">
        <i class="bi bi-list-ul me-1"></i> Ver todos
      </a>
    </div>
  </div>
<?php } else { ?>


  <div class="alert alert-success" role="alert">
    No tienes documentos próximos a vencer ni vencidos.
  </div>
<?php } ?>



<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card p-3 text-center">
      <h5>Vigentes</h5>
      <span class="display-5 text-success"><?php echo $totales['vigente']; ?></span>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3 text-center">
      <h5>Próximos vencimiento</h5>
      <span class="display-5 text-warning"><?php echo $totales['proximo']; ?></span>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3 text-center">
      <h5>Vencidos</h5>
      <span class="display-5 text-danger"><?php echo $totales['vencido']; ?></span>
    </div>
  </div>
</div>

<h2 class="mb-3">
  Mis documentos
  <?php if ($estadoFiltro !== 'todos') { ?>
    <small class="text-muted">(solo <?php echo htmlspecialchars($estadoFiltro); ?>)</small>
  <?php } ?>
</h2>

<div class="card p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Categoría</th>
          <th>Nombre archivo</th>
          <th>Fecha emisión</th>
          <th>Fecha vencimiento</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($docs->num_rows === 0) { ?>
        <tr><td colspan="6" class="text-center text-muted">Aún no has cargado documentos.</td></tr>
      <?php } else { ?>
        <?php while ($d = $docs->fetch_assoc()) { ?>
          <?php
            $badgeClass = 'badge-vigente';
            if ($d['estado'] === 'proximo') {
                $badgeClass = 'badge-proximo';
            } elseif ($d['estado'] === 'vencido') {
                $badgeClass = 'badge-vencido';
            }
          ?>
          <tr>
            <td><?php echo htmlspecialchars($d['categoria']); ?></td>
            <td><?php echo htmlspecialchars($d['nombre_archivo']); ?></td>
            <td><?php echo htmlspecialchars($d['fecha_emision']); ?></td>
            <td><?php echo htmlspecialchars($d['fecha_vencimiento']); ?></td>
            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($d['estado']); ?></span></td>
            <td>
              <a href="edit_document.php?id=<?php echo $d['id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil-square"></i></a>
            </td>
          </tr>
        <?php } ?>
      <?php } ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$stmt->close();
$stmtAlertas->close();
include __DIR__ . '/../includes/footer.php';
?>
