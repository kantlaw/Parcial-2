<?php
// ============================================================
// CLIENTE - Dejar una reseña
// Solo para una reserva PROPIA, en estado REALIZADA y sin reseña.
// Además, la base de datos lo garantiza con:
//   - resenas.reserva_id UNIQUE (una reseña por reserva)
//   - FK (reserva_id, usuario_id) -> reservas(id, usuario_id)
//   - CHECK calificación entre 1 y 5
// ============================================================
require_once '../includes/seguridad.php';
$usuario = requiere_rol('CLIENTE');

$reserva_id = filter_input(INPUT_GET, 'reserva', FILTER_VALIDATE_INT);

$sql = "SELECT r.id, r.estado, s.nombre AS servicio, s.id AS servicio_id, h.fecha,
               (SELECT COUNT(*) FROM resenas re WHERE re.reserva_id = r.id) AS ya_resenada
        FROM reservas r
        JOIN servicios s ON s.id = r.servicio_id
        JOIN horarios h ON h.id = r.horario_id
        WHERE r.id = :id AND r.usuario_id = :usuario";
$consulta = $pdo->prepare($sql);
$consulta->execute([':id' => $reserva_id, ':usuario' => $usuario['id']]);
$reserva = $consulta->fetch();

// Reserva inexistente o de otro cliente: el mismo mensaje
if (!$reserva) {
    flash('danger', 'La reserva no existe.');
    redirigir('cliente/mis_reservas.php');
}
if ($reserva['estado'] !== 'REALIZADA') {
    flash('warning', 'Solo puedes reseñar una cita que ya fue realizada.');
    redirigir('cliente/mis_reservas.php');
}
if ($reserva['ya_resenada']) {
    flash('info', 'Ya dejaste una reseña para esta reserva.');
    redirigir('cliente/mis_reservas.php');
}

$errores = [];
$calificacion = 5;
$comentario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();

    $calificacion = filter_input(INPUT_POST, 'calificacion', FILTER_VALIDATE_INT,
                                 ['options' => ['min_range' => 1, 'max_range' => 5]]);
    $comentario = trim($_POST['comentario'] ?? '');

    if ($calificacion === false || $calificacion === null) {
        $errores[] = 'Elige una calificación de 1 a 5 estrellas.';
        $calificacion = 5;
    }
    if (mb_strlen($comentario) < 10 || mb_strlen($comentario) > 500) {
        $errores[] = 'El comentario debe tener entre 10 y 500 caracteres.';
    }

    if (!$errores) {
        try {
            $sql = 'INSERT INTO resenas (reserva_id, usuario_id, calificacion, comentario)
                    VALUES (:reserva, :usuario, :calificacion, :comentario)';
            $pdo->prepare($sql)->execute([
                ':reserva'      => $reserva['id'],
                ':usuario'      => $usuario['id'],
                ':calificacion' => $calificacion,
                ':comentario'   => $comentario,
            ]);
            flash('success', '¡Gracias! Tu reseña ya aparece en el servicio.');
            redirigir('servicio.php?id=' . $reserva['servicio_id']);
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {
                flash('info', 'Ya dejaste una reseña para esta reserva.');
                redirigir('cliente/mis_reservas.php');
            }
            throw $ex;
        }
    }
}

$titulo = 'Dejar reseña';
require '../includes/header.php';
?>
<section class="mx-auto caja-media">
  <a href="<?= url('cliente/mis_reservas.php') ?>" class="small">← Volver a mis reservas</a>
  <h1 class="h2 mt-2">Reseña: <?= e($reserva['servicio']) ?></h1>
  <p class="text-secondary">Cita del <?= e(date('d/m/Y', strtotime($reserva['fecha']))) ?></p>

  <?php if ($errores): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" class="card tarjeta card-body">
    <?= csrf_campo() ?>
    <fieldset class="mb-3">
      <legend class="form-label fs-6">Calificación</legend>
      <?php for ($i = 5; $i >= 1; $i--): ?>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="calificacion" id="cal<?= $i ?>" value="<?= $i ?>" <?= $calificacion === $i ? 'checked' : '' ?>>
          <label class="form-check-label text-warning" for="cal<?= $i ?>"><?= str_repeat('★', $i) ?></label>
        </div>
      <?php endfor; ?>
    </fieldset>
    <div class="mb-3">
      <label for="comentario" class="form-label">Comentario</label>
      <textarea id="comentario" name="comentario" class="form-control" rows="4" minlength="10" maxlength="500" required><?= e($comentario) ?></textarea>
      <div class="form-text">Entre 10 y 500 caracteres.</div>
    </div>
    <button type="submit" class="btn btn-acento">Publicar reseña</button>
  </form>
</section>
<?php require '../includes/footer.php'; ?>
