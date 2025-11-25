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

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Panel de administración</h4>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-people-fill fs-1 text-primary"></i>
                    </div>
                    <h5 class="card-title">Gestión de usuarios</h5>
                    <p class="card-text text-muted">
                        Administra las cuentas de acceso, roles y permisos de los usuarios del sistema.
                    </p>
                    <a href="<?php echo BASE_URL; ?>pages/admin/usuarios.php" class="btn btn-outline-primary">
                        Ir a usuarios
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-folder2-open fs-1 text-primary"></i>
                    </div>
                    <h5 class="card-title">Categorías de documentos</h5>
                    <p class="card-text text-muted">
                        Configura las categorías y la vigencia de los documentos requeridos a los contratistas.
                    </p>
                    <a href="<?php echo BASE_URL; ?>pages/categorias/index.php" class="btn btn-outline-primary">
                        Gestionar categorías
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-sliders fs-1 text-primary"></i>
                    </div>
                    <h5 class="card-title">Parámetros del sistema</h5>
                    <p class="card-text text-muted">
                        Espacio reservado para futuras configuraciones generales del sistema.
                    </p>
                    <button class="btn btn-outline-secondary" disabled>
                        Próximamente
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
