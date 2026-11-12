<?php
// ============================================================
// Inicio de sesión
// - password_verify() compara la clave con el hash guardado
// - Bloqueo temporal tras 5 intentos fallidos (por correo o por IP)
// - El mensaje de error es el mismo exista o no el correo
// ============================================================
require_once 'includes/seguridad.php';

const MAX_INTENTOS = 5;
const MINUTOS_BLOQUEO = 15;

if (usuario_actual()) {
    redirigir(es_admin() ? 'admin/index.php' : 'index.php');
}

$error = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();

    $correo = strtolower(trim($_POST['correo'] ?? ''));
    $clave  = $_POST['clave'] ?? '';
    $ip     = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';

    // 1. ¿Hay demasiados intentos fallidos recientes con este correo o desde esta IP?
    $sql = 'SELECT COUNT(*) FROM intentos_login
            WHERE (correo = :correo OR ip = :ip)
              AND fecha > (NOW() - INTERVAL ' . MINUTOS_BLOQUEO . ' MINUTE)';
    $consulta = $pdo->prepare($sql);
    $consulta->execute([':correo' => $correo, ':ip' => $ip]);
    $intentos = (int) $consulta->fetchColumn();

    if ($intentos >= MAX_INTENTOS) {
        $error = 'Demasiados intentos fallidos. Espera ' . MINUTOS_BLOQUEO . ' minutos e intenta de nuevo.';
    } else {
        // 2. Buscar al usuario por correo (consulta preparada)
        $sql = 'SELECT u.id, u.nombre, u.password_hash, u.estado, r.nombre AS rol
                FROM usuarios u
                JOIN roles r ON r.id = u.rol_id
                WHERE u.correo = :correo';
        $consulta = $pdo->prepare($sql);
        $consulta->execute([':correo' => $correo]);
        $usuario = $consulta->fetch();

        // Si el correo no existe se verifica contra un hash falso, para que
        // la respuesta tarde lo mismo y no delate qué correos existen
        $hash = $usuario['password_hash'] ?? '$2y$10$0ovg8sj3WcsDP7nVhv0g7umzNqTIbX0xvQ8tIgf1RXXHmL/RBERCS';
        $clave_correcta = password_verify($clave, $hash);

        if ($usuario && $clave_correcta && $usuario['estado'] === 'ACTIVO') {
            // 3. Éxito: se limpian los intentos y se regenera el ID de sesión
            $pdo->prepare('DELETE FROM intentos_login WHERE correo = :correo')
                ->execute([':correo' => $correo]);

            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario['id'];

            flash('success', 'Bienvenido, ' . $usuario['nombre'] . '.');
            redirigir($usuario['rol'] === 'ADMIN' ? 'admin/index.php' : 'index.php');
        } elseif ($usuario && $clave_correcta) {
            $error = 'Tu cuenta está desactivada. Comunícate con el negocio.';
        } else {
            // 4. Fallo: se registra el intento y se da un mensaje genérico
            $pdo->prepare('INSERT INTO intentos_login (correo, ip) VALUES (:correo, :ip)')
                ->execute([':correo' => $correo, ':ip' => $ip]);
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}

$titulo = 'Iniciar sesión';
require 'includes/header.php';
?>
<section class="caja-formulario mx-auto">
  <div class="card tarjeta shadow-sm">
    <div class="card-body p-4">
      <h1 class="h3 mb-1">Iniciar sesión</h1>
      <p class="text-secondary">Entra para reservar y revisar tus citas.</p>

      <?php if (isset($_GET['salida'])): ?>
        <div class="alert alert-info">Cerraste sesión correctamente.</div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post">
        <?= csrf_campo() ?>
        <div class="mb-3">
          <label for="correo" class="form-label">Correo electrónico</label>
          <input type="email" id="correo" name="correo" class="form-control" required autofocus value="<?= e($correo) ?>">
        </div>
        <div class="mb-3">
          <label for="clave" class="form-label">Contraseña</label>
          <input type="password" id="clave" name="clave" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Entrar</button>
      </form>
      <p class="mt-3 mb-0 text-center">¿No tienes cuenta? <a href="<?= url('registro.php') ?>">Regístrate</a></p>
    </div>
  </div>
</section>
<?php require 'includes/footer.php'; ?>
