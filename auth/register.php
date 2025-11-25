<?php
require_once __DIR__ . '/../db.php';
session_start();

$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($nombre === '' || $email === '' || $password === '' || $password2 === '') {
        $errores[] = 'Todos los campos son obligatorios.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo electrónico no es válido.';
    }

    if ($password !== $password2) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        $stmt = $conexion->prepare('SELECT id FROM usuarios WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errores[] = 'Ya existe un usuario registrado con este correo.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmtInsert = $conexion->prepare('INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)');
            $stmtInsert->bind_param('sss', $nombre, $email, $hash);
            if ($stmtInsert->execute()) {
                $_SESSION['usuario_id'] = $stmtInsert->insert_id;
                $_SESSION['usuario_nombre'] = $nombre;
                header('Location: ' . BASE_URL . 'pages/dashboard.php');
                exit;
            } else {
                $errores[] = 'Error al registrar el usuario.';
            }
            $stmtInsert->close();
        }
        $stmt->close();
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <div class="card p-4">
      <h2 class="mb-3 text-center">Crear cuenta</h2>
      <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errores as $e): ?>
              <li><?php echo htmlspecialchars($e); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <form method="post" action="">
        <div class="mb-3">
          <label class="form-label">Nombre completo</label>
          <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Correo electrónico</label>
          <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Contraseña</label>
          <input type="password" name="password" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Confirmar contraseña</label>
          <input type="password" name="password2" class="form-control">
        </div>
        <div class="d-grid">
          <button type="submit" class="btn btn-primary"><i class="bi bi-person-check me-1"></i> Registrarme</button>
        </div>
        <p class="mt-3 mb-0 text-center">
          ¿Ya tienes cuenta? <a href="<?php echo BASE_URL; ?>auth/login.php"><i class="bi bi-box-arrow-in-right me-1"></i> Inicia sesión</a>
        </p>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
