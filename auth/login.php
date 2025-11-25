<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errores[] = 'Todos los campos son obligatorios.';
    } else {
        $stmt = $conexion->prepare('SELECT id, nombre, password, rol FROM usuarios WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($id, $nombre, $hash, $rol);

        if ($stmt->fetch()) {
            if (password_verify($password, $hash)) {
                $_SESSION['usuario_id'] = $id;
                $_SESSION['usuario_nombre'] = $nombre;
                $_SESSION['usuario_rol'] = $rol;

                header('Location: ' . BASE_URL . 'pages/dashboard.php');
                exit;
            } else {
                $errores[] = 'Credenciales incorrectas.';
            }
        } else {
            $errores[] = 'No se encontró un usuario con ese correo.';
        }

        $stmt->close();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header text-center">
                    <h5 class="mb-0">Iniciar sesión</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errores)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errores as $e): ?>
                                <div><?php echo $e; ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <button class="btn btn-primary w-100">Ingresar</button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="<?php echo BASE_URL; ?>auth/register.php">Crear cuenta</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
