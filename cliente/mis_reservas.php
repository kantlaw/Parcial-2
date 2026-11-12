<?php
// ============================================================
// Reservas del CLIENTE: solo se consultan las suyas
// (WHERE r.usuario_id = id de la sesión)
// ============================================================
require_once '../includes/seguridad.php';
$usuario = requiere_rol('CLIENTE');

$sql = "SELECT r.id, r.estado, r.fecha_registro, s.nombre AS servicio,
               h.fecha, h.hora_inicio, h.hora_fin
        FROM reservas r
        JOIN servicios s ON s.id = r.servicio_id
        JOIN horarios h ON h.id = r.horario_id
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
        <tr><th>Servicio</th><th>Fecha</th><th>Hora</th><th>Estado</th></tr>
      </thead>
      <tbody>
        <?php foreach ($reservas as $reserva): ?>
          <tr>
            <td><?= e($reserva['servicio']) ?></td>
            <td><?= e(date('d/m/Y', strtotime($reserva['fecha']))) ?></td>
            <td><?= e(substr($reserva['hora_inicio'], 0, 5)) ?> – <?= e(substr($reserva['hora_fin'], 0, 5)) ?></td>
            <td><span class="badge text-bg-<?= $colores[$reserva['estado']] ?>"><?= e($reserva['estado']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php require '../includes/footer.php'; ?>
