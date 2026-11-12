<?php
// ============================================================
// ADMIN - Listado de horarios (con filtro por servicio)
// ============================================================
require_once '../includes/seguridad.php';
requiere_rol('ADMIN');

$servicio_filtro = filter_input(INPUT_GET, 'servicio', FILTER_VALIDATE_INT) ?: null;
$servicios = $pdo->query('SELECT id, nombre FROM servicios ORDER BY nombre')->fetchAll();

$sql = "SELECT h.id, h.fecha, h.hora_inicio, h.hora_fin, h.cupos_totales, h.cupos_disponibles, h.estado,
               s.nombre AS servicio,
               (SELECT COUNT(*) FROM reservas r WHERE r.horario_id = h.id) AS reservas,
               TIMESTAMP(h.fecha, h.hora_inicio) <= NOW() AS pasado
        FROM horarios h
        JOIN servicios s ON s.id = h.servicio_id";
$parametros = [];
if ($servicio_filtro) {
    $sql .= ' WHERE h.servicio_id = :servicio';
    $parametros[':servicio'] = $servicio_filtro;
}
$sql .= ' ORDER BY h.fecha DESC, h.hora_inicio DESC';

$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);
$horarios = $consulta->fetchAll();

$titulo = 'Horarios';
require '../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h2 mb-0">Horarios</h1>
  <a href="<?= url('admin/horario_form.php') ?>" class="btn btn-acento">Nuevo horario</a>
</div>

<form method="get" class="row g-2 mb-3">
  <div class="col-md-6">
    <label for="servicio" class="visually-hidden">Servicio</label>
    <select id="servicio" name="servicio" class="form-select">
      <option value="">Todos los servicios</option>
      <?php foreach ($servicios as $s): ?>
        <option value="<?= (int) $s['id'] ?>" <?= $servicio_filtro === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2 d-grid">
    <button type="submit" class="btn btn-primary">Filtrar</button>
  </div>
</form>

<?php if (!$horarios): ?>
  <div class="alert alert-secondary">No hay horarios registrados.</div>
<?php else: ?>
<div class="table-responsive">
  <table class="table table-hover bg-white align-middle">
    <thead>
      <tr><th>Servicio</th><th>Fecha</th><th>Inicio</th><th>Fin</th><th>Cupos totales</th><th>Disponibles</th><th>Estado</th><th class="text-end">Acciones</th></tr>
    </thead>
    <tbody>
      <?php foreach ($horarios as $h): ?>
        <tr class="<?= $h['pasado'] ? 'text-secondary' : '' ?>">
          <td class="fw-semibold"><?= e($h['servicio']) ?></td>
          <td><?= e(date('d/m/Y', strtotime($h['fecha']))) ?><?= $h['pasado'] ? ' <span class="badge text-bg-light">pasado</span>' : '' ?></td>
          <td><?= e(substr($h['hora_inicio'], 0, 5)) ?></td>
          <td><?= e(substr($h['hora_fin'], 0, 5)) ?></td>
          <td><?= (int) $h['cupos_totales'] ?></td>
          <td><?= (int) $h['cupos_disponibles'] ?></td>
          <td><span class="badge text-bg-<?= $h['estado'] === 'ACTIVO' ? 'success' : 'secondary' ?>"><?= e($h['estado']) ?></span></td>
          <td class="text-end text-nowrap">
            <a href="<?= url('admin/horario_form.php?id=' . $h['id']) ?>" class="btn btn-sm btn-outline-primary">Editar</a>
            <?php if ((int) $h['reservas'] === 0): ?>
              <form action="<?= url('admin/horario_eliminar.php') ?>" method="post" class="d-inline"
                    data-confirmar="¿Eliminar este horario?">
                <?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= (int) $h['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
              </form>
            <?php else: ?>
              <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                      title="Tiene reservas: desactívalo desde Editar">Eliminar</button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
<p class="small text-secondary">Un horario con reservas no se puede eliminar; cámbialo a INACTIVO para que deje de ofrecerse.</p>
<?php require '../includes/footer.php'; ?>
