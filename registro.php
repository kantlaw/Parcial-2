<?php
// ============================================================
// Registro público: SIEMPRE crea usuarios con rol CLIENTE
// ============================================================
require_once 'includes/seguridad.php';

if (usuario_actual()) {
    redirigir('index.php');
}

$errores = [];
$datos = ['nombre' => '', 'apellido' => '', 'correo' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();

    $datos['nombre']   = trim($_POST['nombre'] ?? '');
    $datos['apellido'] = trim($_POST['apellido'] ?? '');
    $datos['correo']   = strtolower(trim($_POST['correo'] ?? ''));
    $clave             = $_POST['clave'] ?? '';
    $confirmacion      = $_POST['confirmacion'] ?? '';

    // Validaciones en el servidor
    if ($datos['nombre'] === '' || mb_strlen($datos['nombre']) > 60) {
        $errores[] = 'Escribe tu nombre (máximo 60 caracteres).';
    }
    if ($datos['apellido'] === '' || mb_strlen($datos['apellido']) > 60) {
        $errores[] = 'Escribe tu apellido (máximo 60 caracteres).';
    }
    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL) || mb_strlen($datos['correo']) > 120) {
        $errores[] = 'Escribe un correo electrónico válido.';
    }
    // Política de contraseñas (ver validar_clave() en includes/seguridad.php)
    $errores = array_merge($errores, validar_clave($clave, $datos['correo']));
    if ($clave !== $confirmacion) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (!$errores) {
        // ¿El correo ya existe?
        $consulta = $pdo->prepare('SELECT id FROM usuarios WHERE correo = :correo');
        $consulta->execute([':correo' => $datos['correo']]);
        if ($consulta->fetch()) {
            $errores[] = 'Ese correo ya tiene una cuenta. Inicia sesión o usa otro correo.';
        }
    }

    if (!$errores) {
        // El rol se busca por nombre: el formulario NO puede elegir el rol
        $sql = "INSERT INTO usuarios (rol_id, nombre, apellido, correo, password_hash)
                VALUES ((SELECT id FROM roles WHERE nombre = 'CLIENTE'), :nombre, :apellido, :correo, :hash)";
        $consulta = $pdo->prepare($sql);
        $consulta->execute([
            ':nombre'   => $datos['nombre'],
            ':apellido' => $datos['apellido'],
            ':correo'   => $datos['correo'],
            ':hash'     => password_hash($clave, PASSWORD_DEFAULT),
        ]);

        flash('success', 'Tu cuenta fue creada. Ya puedes iniciar sesión.');
        redirigir('login.php');
    }
}

$titulo = 'Crear cuenta';
require 'includes/header.php';
?>
<section class="caja-formulario mx-auto">
  <div class="card tarjeta shadow-sm">
    <div class="card-body p-4">
      <h1 class="h3 mb-1">Crear cuenta</h1>
      <p class="text-secondary">Regístrate para reservar una cita con nuestros técnicos.</p>

      <?php if ($errores): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errores as $error): ?>
              <li><?= e($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" novalidate>
        <?= csrf_campo() ?>
        <div class="row g-3">
          <div class="col-sm-6">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" id="nombre" name="nombre" class="form-control" maxlength="60" required value="<?= e($datos['nombre']) ?>">
          </div>
          <div class="col-sm-6">
            <label for="apellido" class="form-label">Apellido</label>
            <input type="text" id="apellido" name="apellido" class="form-control" maxlength="60" required value="<?= e($datos['apellido']) ?>">
          </div>
          <div class="col-12">
            <label for="correo" class="form-label">Correo electrónico</label>
            <input type="email" id="correo" name="correo" class="form-control" maxlength="120" required value="<?= e($datos['correo']) ?>">
          </div>
          <div class="col-12">
            <label for="clave" class="form-label">Contraseña</label>
            <input type="password" id="clave" name="clave" class="form-control" minlength="12" maxlength="72" required autocomplete="new-password">
            <?= requisitos_clave_html() ?>
          </div>
          <div class="col-12">
            <label for="confirmacion" class="form-label">Repite la contraseña</label>
            <input type="password" id="confirmacion" name="confirmacion" class="form-control" minlength="12" maxlength="72" required autocomplete="new-password">
          </div>
        </div>
        <button type="submit" class="btn btn-acento w-100 mt-4">Crear cuenta</button>
      </form>
      <p class="mt-3 mb-0 text-center">¿Ya tienes cuenta? <a href="<?= url('login.php') ?>">Inicia sesión</a></p>
    </div>
  </div>
</section>
<?php require 'includes/footer.php'; ?>
