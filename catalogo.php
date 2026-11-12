<?php
// ============================================================
// Catálogo: búsqueda por nombre y filtro por categoría
// ============================================================
require_once 'includes/seguridad.php';

// Datos del formulario de búsqueda (GET)
$busqueda  = trim($_GET['q'] ?? '');
$categoria = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT) ?: null;

// Lista de categorías para el filtro
$categorias = $pdo->query("SELECT id, nombre FROM categorias WHERE estado = 'ACTIVO' ORDER BY nombre")->fetchAll();

// La consulta se arma con condiciones fijas; los VALORES siempre van como parámetros
$sql = "SELECT s.id, s.nombre, s.descripcion, s.precio, s.duracion_min, c.nombre AS categoria
        FROM servicios s
        JOIN categorias c ON c.id = s.categoria_id
        WHERE s.estado = 'ACTIVO' AND c.estado = 'ACTIVO'";
$parametros = [];

if ($busqueda !== '') {
    $sql .= ' AND s.nombre LIKE :busqueda';
    $parametros[':busqueda'] = '%' . $busqueda . '%';
}
if ($categoria) {
    $sql .= ' AND s.categoria_id = :categoria';
    $parametros[':categoria'] = $categoria;
}
$sql .= ' ORDER BY c.nombre, s.nombre';

$consulta = $pdo->prepare($sql);
$consulta->execute($parametros);
$servicios = $consulta->fetchAll();

$titulo = 'Servicios';
require 'includes/header.php';
?>
<h1 class="h2 mb-3">Servicios</h1>

<form method="get" class="row g-2 mb-4" role="search">
  <div class="col-md-6">
    <label for="q" class="visually-hidden">Buscar servicio</label>
    <input type="search" id="q" name="q" class="form-control" placeholder="Buscar por nombre, por ejemplo: Windows" value="<?= e($busqueda) ?>">
  </div>
  <div class="col-md-4">
    <label for="categoria" class="visually-hidden">Categoría</label>
    <select id="categoria" name="categoria" class="form-select">
      <option value="">Todas las categorías</option>
      <?php foreach ($categorias as $cat): ?>
        <option value="<?= (int) $cat['id'] ?>" <?= $categoria === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['nombre']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2 d-grid">
    <button type="submit" class="btn btn-primary">Buscar</button>
  </div>
</form>

<?php if (!$servicios): ?>
  <div class="alert alert-secondary">
    No encontramos servicios con esos datos. <a href="<?= url('catalogo.php') ?>">Ver todos los servicios</a>.
  </div>
<?php endif; ?>

<div class="row g-3">
  <?php foreach ($servicios as $servicio): ?>
    <div class="col-md-6 col-lg-4">
      <article class="card tarjeta tarjeta-servicio h-100">
        <div class="card-body d-flex flex-column">
          <span class="badge text-bg-light align-self-start mb-2"><?= e($servicio['categoria']) ?></span>
          <h2 class="h5"><?= e($servicio['nombre']) ?></h2>
          <p class="text-secondary flex-grow-1"><?= e($servicio['descripcion']) ?></p>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="precio">$<?= number_format((float) $servicio['precio'], 2) ?></span>
              <span class="text-secondary small d-block"><?= (int) $servicio['duracion_min'] ?> minutos</span>
            </div>
            <a href="<?= url('servicio.php?id=' . $servicio['id']) ?>" class="btn btn-outline-primary">Ver detalle</a>
          </div>
        </div>
      </article>
    </div>
  <?php endforeach; ?>
</div>
<?php require 'includes/footer.php'; ?>
