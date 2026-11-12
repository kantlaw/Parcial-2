<?php
// ============================================================
// ADMIN - Listado de servicios
// ============================================================
require_once '../includes/seguridad.php';
requiere_rol('ADMIN');

$sql = "SELECT s.id, s.nombre, s.precio, s.duracion_min, s.estado, c.nombre AS categoria,
               (SELECT COUNT(*) FROM horarios h WHERE h.servicio_id = s.id) +
               (SELECT COUNT(*) FROM reservas r WHERE r.servicio_id = s.id) AS dependencias
        FROM servicios s
        JOIN categorias c ON c.id = s.categoria_id
        ORDER BY c.nombre, s.nombre";
$servicios = $pdo->query($sql)->fetchAll();

$titulo = 'Servicios';
require '../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h2 mb-0">Servicios</h1>
  <a href="<?= url('admin/servicio_form.php') ?>" class="btn btn-acento">Nuevo servicio</a>
</div>

<div class="table-responsive">
  <table class="table table-hover bg-white align-middle">
    <thead>
      <tr><th>Servicio</th><th>Categoría</th><th>Precio</th><th>Duración</th><th>Estado</th><th class="text-end">Acciones</th></tr>
    </thead>
    <tbody>
      <?php foreach ($servicios as $servicio): ?>
        <tr>
          <td class="fw-semibold"><?= e($servicio['nombre']) ?></td>
          <td><?= e($servicio['categoria']) ?></td>
          <td>$<?= number_format((float) $servicio['precio'], 2) ?></td>
          <td><?= (int) $servicio['duracion_min'] ?> min</td>
          <td><span class="badge text-bg-<?= $servicio['estado'] === 'ACTIVO' ? 'success' : 'secondary' ?>"><?= e($servicio['estado']) ?></span></td>
          <td class="text-end text-nowrap">
            <a href="<?= url('admin/servicio_form.php?id=' . $servicio['id']) ?>" class="btn btn-sm btn-outline-primary">Editar</a>
            <?php if ((int) $servicio['dependencias'] === 0): ?>
              <form action="<?= url('admin/servicio_eliminar.php') ?>" method="post" class="d-inline"
                    data-confirmar="¿Eliminar el servicio <?= e($servicio['nombre']) ?>?">
                <?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= (int) $servicio['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
              </form>
            <?php else: ?>
              <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                      title="Tiene horarios o reservas: desactívalo desde Editar">Eliminar</button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p class="small text-secondary">Un servicio con horarios o reservas no se puede eliminar; cámbialo a INACTIVO para ocultarlo.</p>
<?php require '../includes/footer.php'; ?>
