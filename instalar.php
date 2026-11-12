<?php
// ============================================================
// Inicialización segura del ADMIN
// - Solo funciona desde la misma computadora (localhost)
// - Solo funciona si todavía NO existe ningún ADMIN
// Así la contraseña del ADMIN nunca se guarda en el repositorio.
// ============================================================
require_once 'includes/seguridad.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('La instalación solo puede realizarse desde el equipo local.');
}

$consulta = $pdo->query("SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE r.nombre = 'ADMIN'");
if ((int) $consulta->fetchColumn() > 0) {
    http_response_code(403);
    exit('El administrador ya fue creado. Esta página quedó deshabilitada.');
}

$errores = [];
$datos = ['nombre' => '', 'apellido' => '', 'correo' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();

    $datos['nombre']   = trim($_POST['nombre'] ?? '');
    $datos['apellido'] = trim($_POST['apellido'] ?? '');
    $datos['correo']   = strtolower(trim($_POST['correo'] ?? ''));
    $clave             = $_POST['clave'] ?? '';

    if ($datos['nombre'] === '' || $datos['apellido'] === '') {
        $errores[] = 'Escribe nombre y apellido.';
    }
    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Escribe un correo válido.';
    }
    $errores = array_merge($errores, validar_clave($clave, $datos['correo']));

    if (!$errores) {
        $sql = "INSERT INTO usuarios (rol_id, nombre, apellido, correo, password_hash)
                VALUES ((SELECT id FROM roles WHERE nombre = 'ADMIN'), :nombre, :apellido, :correo, :hash)";
        $pdo->prepare($sql)->execute([
            ':nombre'   => $datos['nombre'],
            ':apellido' => $datos['apellido'],
            ':correo'   => $datos['correo'],
            ':hash'     => password_hash($clave, PASSWORD_DEFAULT),
        ]);
        flash('success', 'Administrador creado. Ya puedes iniciar sesión.');
        redirigir('login.php');
    }
}

$titulo = 'Instalación';
require 'includes/header.php';
?>
<section class="caja-formulario mx-auto">
  <div class="card tarjeta shadow-sm">
    <div class="card-body p-4">
      <h1 class="h3">Crear administrador</h1>
      <p class="text-secondary">Este paso se hace una sola vez. Después, esta página se bloquea.</p>

      <?php if ($errores): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post">
        <?= csrf_campo() ?>
        <div class="mb-3">
          <label for="nombre" class="form-label">Nombre</label>
          <input type="text" id="nombre" name="nombre" class="form-control" required value="<?= e($datos['nombre']) ?>">
        </div>
        <div class="mb-3">
          <label for="apellido" class="form-label">Apellido</label>
          <input type="text" id="apellido" name="apellido" class="form-control" required value="<?= e($datos['apellido']) ?>">
        </div>
        <div class="mb-3">
          <label for="correo" class="form-label">Correo</label>
          <input type="email" id="correo" name="correo" class="form-control" required value="<?= e($datos['correo']) ?>">
        </div>
        <div class="mb-3">
          <label for="clave" class="form-label">Contraseña</label>
          <input type="password" id="clave" name="clave" class="form-control" minlength="12" maxlength="72" required autocomplete="new-password">
          <?= requisitos_clave_html() ?>
        </div>
        <button type="submit" class="btn btn-primary w-100">Crear administrador</button>
      </form>
    </div>
  </div>
</section>
<?php require 'includes/footer.php'; ?>
