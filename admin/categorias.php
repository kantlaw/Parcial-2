<?php
// ============================================================
// ADMIN - Listado de categorías (la "R" del CRUD)
// ============================================================
require_once '../includes/seguridad.php';
requiere_rol('ADMIN');

$sql = "SELECT c.id, c.nombre, c.descripcion, c.estado, COUNT(s.id) AS servicios
        FROM categorias c
        LEFT JOIN servicios s ON s.categoria_id = c.id
        GROUP BY c.id, c.nombre, c.descripcion, c.estado
        ORDER BY c.nombre";
$categorias = $pdo->query($sql)->fetchAll();

$titulo = 'Categorías';
require '../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h2 mb-0">Categorías</h1>
  <a href="<?= url('admin/categoria_form.php') ?>" class="btn btn-acento">Nueva categoría</a>
</div>

<div class="table-responsive">
  <table class="table table-hover bg-white align-middle">
    <thead>
      <tr><th>Nombre</th><th>Descripción</th><th>Servicios</th><th>Estado</th><th class="text-end">Acciones</th></tr>
    </thead>
    <tbody>
      <?php foreach ($categorias as $cat): ?>
        <tr>
          <td class="fw-semibold"><?= e($cat['nombre']) ?></td>
          <td class="text-secondary"><?= e($cat['descripcion']) ?></td>
          <td><?= (int) $cat['servicios'] ?></td>
          <td><span class="badge text-bg-<?= $cat['estado'] === 'ACTIVO' ? 'success' : 'secondary' ?>"><?= e($cat['estado']) ?></span></td>
          <td class="text-end text-nowrap">
            <a href="<?= url('admin/categoria_form.php?id=' . $cat['id']) ?>" class="btn btn-sm btn-outline-primary">Editar</a>
            <?php if ((int) $cat['servicios'] === 0): ?>
              <form action="<?= url('admin/categoria_eliminar.php') ?>" method="post" class="d-inline"
                    data-confirmar="¿Eliminar la categoría <?= e($cat['nombre']) ?>?">
                <?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
              </form>
            <?php else: ?>
              <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                      title="Tiene servicios: desactívala desde Editar">Eliminar</button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p class="small text-secondary">Una categoría con servicios no se puede eliminar; en su lugar, cámbiala a INACTIVO para ocultarla del catálogo.</p>
<?php require '../includes/footer.php'; ?>
