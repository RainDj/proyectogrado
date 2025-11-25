<?php
require_once __DIR__ . '/../db.php';
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$conexion->query("CALL actualizar_estado_vigencias()");

$sql = "SELECT d.id, c.nombre AS categoria, d.nombre_archivo, d.ruta_archivo,
               d.fecha_emision, d.fecha_vencimiento, d.estado
        FROM documentos d
        INNER JOIN categorias c ON d.categoria_id = c.id
        WHERE d.usuario_id = ?
        ORDER BY d.fecha_vencimiento ASC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$docs = $stmt->get_result();
$stmt->close();

include __DIR__ . '/../includes/header.php';
?>
<h2 class="mb-3">Mis documentos</h2>
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
          <th>Descargar</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($docs->num_rows === 0): ?>
        <tr><td colspan="6" class="text-center text-muted">Aún no has cargado documentos.</td></tr>
      <?php else: ?>
        <?php while ($d = $docs->fetch_assoc()): ?>
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
              <a href="<?php echo htmlspecialchars($d['ruta_archivo']); ?>" class="btn btn-sm btn-secondary" target="_blank"><i class="bi bi-file-earmark-text me-1"></i> Ver/Descargar</a>
            </td>
			<td>
			  <a href="edit_document.php?id=<?php echo $d['id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil-square me-1"></i> Editar</a>
			  
			</td>
          </tr>
        <?php endwhile; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
