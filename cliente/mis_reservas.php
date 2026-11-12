<?php
// ============================================================
// CLIENTE - Mis reservas
// Solo se consultan las del usuario de la sesión
// (WHERE r.usuario_id = :usuario), nunca un id de la URL.
// ============================================================
require_once '../includes/seguridad.php';
$usuario = requiere_rol('CLIENTE');

$sql = "SELECT r.id, r.estado, r.fecha_registro, s.id AS servicio_id, s.nombre AS servicio,
               h.fecha, h.hora_inicio, h.hora_fin,
               TIMESTAMP(h.fecha, h.hora_inicio) <= NOW() AS comenzo,
               re.id AS resena_id, re.calificacion
        FROM reservas r
        JOIN servicios s ON s.id = r.servicio_id
        JOIN horarios h ON h.id = r.horario_id
        LEFT JOIN resenas re ON re.reserva_id = r.id
        WHERE r.usuario_id = :usuario
        ORDER BY h.fecha DESC, h.hora_inicio DESC";
$consulta = $pdo->prepare($sql);
$consulta->execute([':usuario' => $usuario['id']]);
$reservas = $consulta->fetchAll();

$colores = ['PENDIENTE' => 'warning', 'CONFIRMADA' => 'primary', 'REALIZADA' => 'success', 'CANCELADA' => 'secondary'];

$titulo = 'Mis reservas';
require '../includes/header.php';
?>
<h1 class="h2 mb-3">Mis reservas</h1>

<?php if (!$reservas): ?>
  <div class="alert alert-secondary">
    Todavía no tienes reservas. <a href="<?= url('catalogo.php') ?>">Explora los servicios</a> y elige un horario.
  </div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover bg-white align-middle">
      <thead>
        <tr><th>Servicio</th><th>Fecha</th><th>Hora</th><th>Estado</th><th class="text-end">Acciones</th></tr>
      </thead>
      <tbody>
        <?php foreach ($reservas as $reserva): ?>
          <tr>
            <td><a href="<?= url('servicio.php?id=' . $reserva['servicio_id']) ?>"><?= e($reserva['servicio']) ?></a></td>
            <td><?= e(date('d/m/Y', strtotime($reserva['fecha']))) ?></td>
            <td><?= e(substr($reserva['hora_inicio'], 0, 5)) ?> – <?= e(substr($reserva['hora_fin'], 0, 5)) ?></td>
            <td><span class="badge text-bg-<?= $colores[$reserva['estado']] ?>"><?= e($reserva['estado']) ?></span></td>
            <td class="text-end text-nowrap">
              <?php if (in_array($reserva['estado'], ['PENDIENTE', 'CONFIRMADA'], true) && !$reserva['comenzo']): ?>
                <form action="<?= url('cliente/cancelar.php') ?>" method="post" class="d-inline"
                      data-confirmar="¿Cancelar esta reserva?">
                  <?= csrf_campo() ?>
                  <input type="hidden" name="id" value="<?= (int) $reserva['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Cancelar</button>
                </form>
              <?php elseif ($reserva['estado'] === 'REALIZADA' && !$reserva['resena_id']): ?>
                <a href="<?= url('cliente/resena.php?reserva=' . $reserva['id']) ?>" class="btn btn-sm btn-acento">Dejar reseña</a>
              <?php elseif ($reserva['resena_id']): ?>
                <span class="text-warning" title="Tu calificación"><?= str_repeat('★', (int) $reserva['calificacion']) ?></span>
              <?php else: ?>
                <span class="text-secondary small">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="small text-secondary">Puedes cancelar una reserva pendiente o confirmada antes de que empiece la cita. Cuando el negocio la marque como realizada, podrás dejar tu reseña.</p>
<?php endif; ?>
<?php require '../includes/footer.php'; ?>
