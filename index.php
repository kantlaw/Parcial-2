<?php
// ============================================================
// Página de inicio
// ============================================================
require_once 'includes/seguridad.php';

// Categorías activas con la cantidad de servicios activos de cada una
$sql = "SELECT c.id, c.nombre, c.descripcion, COUNT(s.id) AS total
        FROM categorias c
        LEFT JOIN servicios s ON s.categoria_id = c.id AND s.estado = 'ACTIVO'
        WHERE c.estado = 'ACTIVO'
        GROUP BY c.id, c.nombre, c.descripcion
        ORDER BY c.id";
$categorias = $pdo->query($sql)->fetchAll();

// Ícono de cada categoría de prueba; las que cree el ADMIN usan uno general
$iconos = [1 => 'cat-reparacion.svg', 2 => 'cat-software.svg', 3 => 'cat-redes.svg'];

$titulo = 'Inicio';
require 'includes/header.php';
?>
<section class="portada p-4 p-md-5 mb-5">
  <div class="col-lg-8">
    <h1 class="display-5 fw-bold">Tu equipo en buenas manos, sin hacer fila</h1>
    <p class="lead my-3">Reserva una cita con un técnico de <?= e(NOMBRE_NEGOCIO) ?> para reparar tu computadora, instalar programas o asegurar tu red de casa.</p>
    <a href="<?= url('catalogo.php') ?>" class="btn btn-acento btn-lg">Ver servicios</a>
  </div>
</section>

<section class="mb-5">
  <h2 class="h3 mb-3">¿Qué necesitas resolver?</h2>
  <div class="row g-3">
    <?php foreach ($categorias as $categoria): ?>
      <div class="col-md-4">
        <a href="<?= url('catalogo.php?categoria=' . $categoria['id']) ?>" class="card tarjeta tarjeta-servicio h-100 text-decoration-none">
          <div class="card-body">
            <img src="<?= url('img/' . ($iconos[$categoria['id']] ?? 'cat-general.svg')) ?>" alt="" class="icono-categoria mb-3">
            <h3 class="h5"><?= e($categoria['nombre']) ?></h3>
            <p class="text-secondary mb-2"><?= e($categoria['descripcion']) ?></p>
            <span class="small fw-semibold"><?= (int) $categoria['total'] ?> servicios disponibles</span>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section>
  <h2 class="h3 mb-3">Cómo reservar</h2>
  <div class="row g-3">
    <div class="col-md-4">
      <span class="paso-numero mb-2">1</span>
      <h3 class="h6">Elige el servicio</h3>
      <p class="text-secondary">Busca por nombre o explora por categoría.</p>
    </div>
    <div class="col-md-4">
      <span class="paso-numero mb-2">2</span>
      <h3 class="h6">Escoge un horario</h3>
      <p class="text-secondary">Verás los horarios con cupos disponibles.</p>
    </div>
    <div class="col-md-4">
      <span class="paso-numero mb-2">3</span>
      <h3 class="h6">Trae tu equipo</h3>
      <p class="text-secondary">Te confirmamos la cita y te atendemos a la hora acordada.</p>
    </div>
  </div>
</section>
<?php require 'includes/footer.php'; ?>
