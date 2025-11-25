<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/template.php';
?>
<?php require_once __DIR__ . '/../tasks/update_document_status.php'; ?>
<?php require_once __DIR__ . '/../tasks/update_process_status.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
	<link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/img/logo-pgd.png">

    <?php render_theme_styles(); ?>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?php echo BASE_URL; ?>pages/dashboard.php">
            <img src="<?php echo BASE_URL; ?>/assets/img/logo-pgd.png"
				 alt="Sistema de gestión documental"
				 class="me-2"
				 style="height:32px;">
			<span class="fw-semibold">PGD Contratistas</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <li class="nav-item">
						<?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin'): ?>
				<li class="nav-item">
					<a class="nav-link " href="<?php echo BASE_URL; ?>pages/admin/index.php"><i class="bi bi-shield-lock me-1"></i>Administrar</a>
				</li>
			<?php endif; ?>
                <a class="nav-link" href="<?php echo BASE_URL; ?>pages/dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Panel</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>pages/upload.php"><i class="bi bi-cloud-upload me-1"></i> Subir documento</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>pages/documents.php"><i class="bi bi-folder2-open me-1"></i> Mis documentos</a>
            </li>
			<li class="nav-item">
				<a class="nav-link" href="<?php echo BASE_URL; ?>pages/procesos/index.php"><i class="bi bi-tags me-1"></i>
					Procesos documentales
				</a>
			</li>
			</li>
			
			
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>auth/logout.php"><i class="bi bi-box-arrow-right me-1"></i> Cerrar sesión</a>
            </li>


        <?php else: ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>auth/login.php"><i class="bi bi-box-arrow-in-right me-1"></i> Iniciar sesión</a>
            </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container mb-5">
