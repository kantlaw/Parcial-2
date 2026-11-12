<?php
// ============================================================
// Panel del ADMIN
// ============================================================
require_once '../includes/seguridad.php';
$usuario = requiere_rol('ADMIN');

// Resumen rápido
$resumen = [
    'Categorías activas' => $pdo->query("SELECT COUNT(*) FROM categorias WHERE estado = 'ACTIVO'")->fetchColumn(),
    'Servicios activos'  => $pdo->query("SELECT COUNT(*) FROM servicios WHERE estado = 'ACTIVO'")->fetchColumn(),
    'Horarios próximos'  => $pdo->query("SELECT COUNT(*) FROM horarios WHERE estado = 'ACTIVO' AND fecha >= CURDATE()")->fetchColumn(),
    'Reservas pendientes'=> $pdo->query("SELECT COUNT(*) FROM reservas WHERE estado = 'PENDIENTE'")->fetchColumn(),
    'Clientes'           => $pdo->query("SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE r.nombre = 'CLIENTE'")->fetchColumn(),
];

$titulo = 'Panel de administración';
require '../includes/header.php';
?>
<h1 class="h2">Panel de administración</h1>
<p class="text-secondary">Hola, <?= e($usuario['nombre']) ?>. Desde aquí gestionas el catálogo de <?= e(NOMBRE_NEGOCIO) ?>.</p>

<div class="row g-3 mb-4">
  <?php foreach ($resumen as $etiqueta => $valor): ?>
    <div class="col-6 col-md-4 col-xl">
      <div class="card tarjeta h-100">
        <div class="card-body">
          <p class="text-secondary small mb-1"><?= e($etiqueta) ?></p>
          <p class="h3 mb-0"><?= (int) $valor ?></p>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="d-flex flex-wrap gap-2">
  <a href="<?= url('admin/categorias.php') ?>" class="btn btn-primary">Gestionar categorías</a>
  <a href="<?= url('admin/servicios.php') ?>" class="btn btn-primary">Gestionar servicios</a>
  <a href="<?= url('admin/horarios.php') ?>" class="btn btn-primary">Gestionar horarios</a>
  <a href="<?= url('admin/reservas.php') ?>" class="btn btn-acento">Ver reservas</a>
</div>
<?php require '../includes/footer.php'; ?>
