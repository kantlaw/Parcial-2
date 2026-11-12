<?php
// ============================================================
// ADMIN - Todas las reservas, con filtro por estado
// ============================================================
require_once '../includes/seguridad.php';
requiere_rol('ADMIN');

$estados = ['PENDIENTE', 'CONFIRMADA', 'REALIZADA', 'CANCELADA'];
$filtro = $_GET['estado'] ?? '';
// Lista permitida: si no es un estado válido, no se filtra
if (!in_array($filtro, $estados, true)) {
    $filtro = '';
}

$sql = "SELECT r.id, r.estado, r.fecha_registro, r.fecha_actualizacion,
               u.nombre, u.apellido, u.correo,
               s.nombre AS servicio, h.fecha, h.hora_inicio, h.hora_fin
        FROM reservas r
        JOIN usuarios u ON u.id = r.usuario_id
        JOIN servicios s ON s.id = r.servicio_id
        JOIN horarios h ON h.id = r.horario_id";
$parametros = [];
if ($filtro !== '') {
    $sql .= ' WHERE r.estado = :estado';
    $parametros[':estado'] = $filtro;
}
$sql .= ' ORDER BY h.fecha DESC, h.hora_inicio DESC, r.id DESC';

$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);
$reservas = $consulta->fetchAll();

// Transiciones permitidas desde cada estado (las mismas que valida reserva_estado.php)
$acciones = [
    'PENDIENTE'  => ['CONFIRMADA' => ['Confirmar', 'btn-outline-primary'], 'CANCELADA' => ['Cancelar', 'btn-outline-danger']],
    'CONFIRMADA' => ['REALIZADA' => ['Marcar realizada', 'btn-outline-success'], 'CANCELADA' => ['Cancelar', 'btn-outline-danger']],
];
$colores = ['PENDIENTE' => 'warning', 'CONFIRMADA' => 'primary', 'REALIZADA' => 'success', 'CANCELADA' => 'secondary'];

$titulo = 'Reservas';
require '../includes/header.php';
?>
<h1 class="h2 mb-3">Reservas</h1>

<form method="get" class="row g-2 mb-3">
  <div class="col-md-4">
    <label for="estado" class="visually-hidden">Estado</label>
    <select id="estado" name="estado" class="form-select">
      <option value="">Todos los estados</option>
      <?php foreach ($estados as $estado): ?>
        <option value="<?= $estado ?>" <?= $filtro === $estado ? 'selected' : '' ?>><?= $estado ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2 d-grid">
    <button type="submit" class="btn btn-primary">Filtrar</button>
  </div>
</form>

<?php if (!$reservas): ?>
  <div class="alert alert-secondary">No hay reservas<?= $filtro ? ' con estado ' . e($filtro) : '' ?>.</div>
<?php else: ?>
<div class="table-responsive">
  <table class="table table-hover bg-white align-middle">
    <thead>
      <tr><th>#</th><th>Cliente</th><th>Servicio</th><th>Cita</th><th>Estado</th><th>Actualizada</th><th class="text-end">Acciones</th></tr>
    </thead>
    <tbody>
      <?php foreach ($reservas as $r): ?>
        <tr>
          <td><?= (int) $r['id'] ?></td>
          <td><?= e($r['nombre'] . ' ' . $r['apellido']) ?><br><span class="small text-secondary"><?= e($r['correo']) ?></span></td>
          <td><?= e($r['servicio']) ?></td>
          <td><?= e(date('d/m/Y', strtotime($r['fecha']))) ?><br><span class="small"><?= e(substr($r['hora_inicio'], 0, 5)) ?> – <?= e(substr($r['hora_fin'], 0, 5)) ?></span></td>
          <td><span class="badge text-bg-<?= $colores[$r['estado']] ?>"><?= e($r['estado']) ?></span></td>
          <td class="small text-secondary"><?= e(date('d/m/Y H:i', strtotime($r['fecha_actualizacion']))) ?></td>
          <td class="text-end text-nowrap">
            <?php foreach ($acciones[$r['estado']] ?? [] as $nuevo => [$texto, $clase]): ?>
              <form action="<?= url('admin/reserva_estado.php') ?>" method="post" class="d-inline"
                    <?= $nuevo === 'CANCELADA' ? 'data-confirmar="¿Cancelar la reserva #' . (int) $r['id'] . '?"' : '' ?>>
                <?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <input type="hidden" name="estado" value="<?= $nuevo ?>">
                <button type="submit" class="btn btn-sm <?= $clase ?>"><?= $texto ?></button>
              </form>
            <?php endforeach; ?>
            <?php if (!isset($acciones[$r['estado']])): ?>
              <span class="text-secondary small">Sin acciones</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<p class="small text-secondary">Flujo: PENDIENTE → CONFIRMADA → REALIZADA. Al cancelar una reserva pendiente o confirmada se devuelve un cupo al horario.</p>
<?php require '../includes/footer.php'; ?>
