<?php
// ============================================================
// Detalle de un servicio: datos, horarios disponibles y reseñas
// ============================================================
require_once 'includes/seguridad.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$servicio = false;
if ($id) {
    $sql = "SELECT s.*, c.nombre AS categoria
            FROM servicios s
            JOIN categorias c ON c.id = s.categoria_id
            WHERE s.id = :id AND s.estado = 'ACTIVO' AND c.estado = 'ACTIVO'";
    $consulta = $pdo->prepare($sql);
    $consulta->execute([':id' => $id]);
    $servicio = $consulta->fetch();
}

if (!$servicio) {
    http_response_code(404);
    $titulo = 'Servicio no encontrado';
    require 'includes/header.php';
    echo '<div class="alert alert-warning">El servicio no existe o no está disponible. <a href="' . url('catalogo.php') . '">Volver a servicios</a>.</div>';
    require 'includes/footer.php';
    exit;
}

// Horarios futuros y activos de este servicio
$sql = "SELECT id, fecha, hora_inicio, hora_fin, cupos_totales, cupos_disponibles
        FROM horarios
        WHERE servicio_id = :id AND estado = 'ACTIVO'
          AND TIMESTAMP(fecha, hora_inicio) > NOW()
        ORDER BY fecha, hora_inicio";
$consulta = $pdo->prepare($sql);
$consulta->execute([':id' => $id]);
$horarios = $consulta->fetchAll();

// Reseñas del servicio
$sql = "SELECT re.calificacion, re.comentario, re.fecha, u.nombre
        FROM resenas re
        JOIN reservas r ON r.id = re.reserva_id
        JOIN usuarios u ON u.id = re.usuario_id
        WHERE r.servicio_id = :id
        ORDER BY re.fecha DESC";
$consulta = $pdo->prepare($sql);
$consulta->execute([':id' => $id]);
$resenas = $consulta->fetchAll();

// Promedio de calificaciones
$promedio = $resenas ? array_sum(array_column($resenas, 'calificacion')) / count($resenas) : 0;

$usuario = usuario_actual();
$es_cliente = $usuario && $usuario['rol'] === 'CLIENTE';

$titulo = $servicio['nombre'];
require 'includes/header.php';
?>
<nav aria-label="Ruta de navegación">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= url('catalogo.php') ?>">Servicios</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?= e($servicio['nombre']) ?></li>
  </ol>
</nav>

<div class="row g-4">
  <div class="col-lg-7">
    <span class="badge text-bg-light mb-2"><?= e($servicio['categoria']) ?></span>
    <h1 class="h2"><?= e($servicio['nombre']) ?></h1>
    <p class="lead"><?= e($servicio['descripcion']) ?></p>
    <p>
      <span class="precio">$<?= number_format((float) $servicio['precio'], 2) ?></span>
      <span class="text-secondary"> · <?= (int) $servicio['duracion_min'] ?> minutos</span>
    </p>

    <h2 class="h4 mt-4">Reseñas
      <?php if ($resenas): ?>
        <span class="fs-6 text-secondary fw-normal">· <?= number_format($promedio, 1) ?> de 5 (<?= count($resenas) ?>)</span>
      <?php endif; ?>
    </h2>
    <?php if (!$resenas): ?>
      <p class="text-secondary">Este servicio todavía no tiene reseñas.</p>
    <?php endif; ?>
    <?php foreach ($resenas as $resena): ?>
      <div class="card tarjeta mb-2">
        <div class="card-body">
          <strong><?= e($resena['nombre']) ?></strong>
          <span class="text-warning ms-2"><?= str_repeat('★', (int) $resena['calificacion']) . str_repeat('☆', 5 - (int) $resena['calificacion']) ?></span>
          <p class="mb-0 mt-1"><?= e($resena['comentario']) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <aside class="col-lg-5">
    <div class="card tarjeta">
      <div class="card-body">
        <h2 class="h5">Horarios disponibles</h2>
        <?php if (!$horarios): ?>
          <p class="text-secondary mb-0">No hay horarios disponibles por ahora.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr><th>Fecha</th><th>Hora</th><th>Cupos</th><th></th></tr>
              </thead>
              <tbody>
                <?php foreach ($horarios as $horario): ?>
                  <tr>
                    <td><?= e(date('d/m/Y', strtotime($horario['fecha']))) ?></td>
                    <td><?= e(substr($horario['hora_inicio'], 0, 5)) ?> – <?= e(substr($horario['hora_fin'], 0, 5)) ?></td>
                    <td><?= (int) $horario['cupos_disponibles'] ?> de <?= (int) $horario['cupos_totales'] ?></td>
                    <td class="text-end">
                      <?php if ((int) $horario['cupos_disponibles'] === 0): ?>
                        <span class="badge text-bg-secondary">Agotado</span>
                      <?php elseif ($es_cliente): ?>
                        <form action="<?= url('cliente/reservar.php') ?>" method="post">
                          <?= csrf_campo() ?>
                          <input type="hidden" name="horario_id" value="<?= (int) $horario['id'] ?>">
                          <input type="hidden" name="servicio_id" value="<?= (int) $servicio['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-acento">Reservar</button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
        <?php if (!$usuario): ?>
          <p class="small mt-3 mb-0"><a href="<?= url('login.php') ?>">Inicia sesión</a> o <a href="<?= url('registro.php') ?>">crea una cuenta</a> para reservar.</p>
        <?php endif; ?>
      </div>
    </div>
  </aside>
</div>
<?php require 'includes/footer.php'; ?>
