<?php
// ============================================================
// ADMIN - Crear o editar un horario
// Reglas: no en el pasado, hora final > hora inicial, cupos > 0,
// y al editar, los cupos totales no pueden quedar por debajo de
// los cupos ya reservados.
// ============================================================
require_once '../includes/seguridad.php';
requiere_rol('ADMIN');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$horario = ['servicio_id' => '', 'fecha' => '', 'hora_inicio' => '', 'hora_fin' => '', 'cupos_totales' => 1, 'estado' => 'ACTIVO'];
$reservados = 0;      // cupos ocupados por reservas activas
$tiene_reservas = false;
$errores = [];

$servicios = $pdo->query('SELECT id, nombre, estado FROM servicios ORDER BY nombre')->fetchAll();
$ids_servicios = array_map('intval', array_column($servicios, 'id'));

if ($id) {
    $consulta = $pdo->prepare('SELECT servicio_id, fecha, hora_inicio, hora_fin, cupos_totales, cupos_disponibles, estado
                               FROM horarios WHERE id = :id');
    $consulta->execute([':id' => $id]);
    $actual = $consulta->fetch();
    if (!$actual) {
        flash('warning', 'El horario no existe.');
        redirigir('admin/horarios.php');
    }
    $horario = $actual;
    $horario['hora_inicio'] = substr($actual['hora_inicio'], 0, 5);
    $horario['hora_fin']    = substr($actual['hora_fin'], 0, 5);
    $reservados = (int) $actual['cupos_totales'] - (int) $actual['cupos_disponibles'];

    $consulta = $pdo->prepare('SELECT COUNT(*) FROM reservas WHERE horario_id = :id');
    $consulta->execute([':id' => $id]);
    $tiene_reservas = (int) $consulta->fetchColumn() > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();

    $nuevo = [
        'servicio_id'   => filter_input(INPUT_POST, 'servicio_id', FILTER_VALIDATE_INT),
        'fecha'         => trim($_POST['fecha'] ?? ''),
        'hora_inicio'   => trim($_POST['hora_inicio'] ?? ''),
        'hora_fin'      => trim($_POST['hora_fin'] ?? ''),
        'cupos_totales' => filter_input(INPUT_POST, 'cupos_totales', FILTER_VALIDATE_INT),
        'estado'        => $_POST['estado'] ?? '',
    ];

    // Formato de fecha y horas (AAAA-MM-DD y HH:MM)
    $fecha_ok  = DateTime::createFromFormat('Y-m-d', $nuevo['fecha']);
    $inicio_ok = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $nuevo['hora_inicio']);
    $fin_ok    = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $nuevo['hora_fin']);

    if (!in_array($nuevo['servicio_id'], $ids_servicios, true)) {
        $errores[] = 'Selecciona un servicio válido.';
    }
    if ($tiene_reservas && $nuevo['servicio_id'] !== (int) $horario['servicio_id']) {
        $errores[] = 'No puedes cambiar el servicio de un horario que ya tiene reservas.';
    }
    if (!$fecha_ok || $fecha_ok->format('Y-m-d') !== $nuevo['fecha']) {
        $errores[] = 'Escribe una fecha válida.';
    }
    if (!$inicio_ok || !$fin_ok) {
        $errores[] = 'Escribe la hora de inicio y de fin con formato HH:MM.';
    } elseif ($nuevo['hora_fin'] <= $nuevo['hora_inicio']) {
        // Regla del negocio: hora final posterior a la inicial
        $errores[] = 'La hora de finalización debe ser posterior a la hora de inicio.';
    }

    // Regla del negocio: no crear horarios en el pasado.
    // Al editar, solo se exige si cambian la fecha o la hora de inicio.
    $cambia_momento = !$id || $nuevo['fecha'] !== $horario['fecha'] || $nuevo['hora_inicio'] !== $horario['hora_inicio'];
    if ($fecha_ok && $inicio_ok && $cambia_momento) {
        $inicio = new DateTime($nuevo['fecha'] . ' ' . $nuevo['hora_inicio']);
        if ($inicio <= new DateTime()) {
            $errores[] = 'El horario debe ser en el futuro.';
        }
    }

    if ($nuevo['cupos_totales'] === false || $nuevo['cupos_totales'] === null || $nuevo['cupos_totales'] < 1 || $nuevo['cupos_totales'] > 100) {
        $errores[] = 'Los cupos totales deben ser un número entre 1 y 100.';
    } elseif ($nuevo['cupos_totales'] < $reservados) {
        $errores[] = "Ya hay $reservados cupos reservados; los cupos totales no pueden ser menos que eso.";
    }
    if (!in_array($nuevo['estado'], ['ACTIVO', 'INACTIVO'], true)) {
        $errores[] = 'Selecciona un estado válido.';
    }

    $horario = array_merge($horario, $nuevo);

    if (!$errores) {
        // Los disponibles se recalculan: total nuevo - cupos ya reservados
        $parametros = [
            ':servicio_id'  => $nuevo['servicio_id'],
            ':fecha'        => $nuevo['fecha'],
            ':hora_inicio'  => $nuevo['hora_inicio'],
            ':hora_fin'     => $nuevo['hora_fin'],
            ':total'        => $nuevo['cupos_totales'],
            ':disponibles'  => $nuevo['cupos_totales'] - $reservados,
            ':estado'       => $nuevo['estado'],
        ];
        if ($id) {
            $sql = 'UPDATE horarios
                    SET servicio_id = :servicio_id, fecha = :fecha, hora_inicio = :hora_inicio, hora_fin = :hora_fin,
                        cupos_totales = :total, cupos_disponibles = :disponibles, estado = :estado
                    WHERE id = :id';
            $parametros[':id'] = $id;
        } else {
            $sql = 'INSERT INTO horarios (servicio_id, fecha, hora_inicio, hora_fin, cupos_totales, cupos_disponibles, estado)
                    VALUES (:servicio_id, :fecha, :hora_inicio, :hora_fin, :total, :disponibles, :estado)';
        }
        $pdo->prepare($sql)->execute($parametros);

        flash('success', $id ? 'Horario actualizado.' : 'Horario creado.');
        redirigir('admin/horarios.php');
    }
}

$titulo = $id ? 'Editar horario' : 'Nuevo horario';
require '../includes/header.php';
?>
<section class="mx-auto caja-media">
  <a href="<?= url('admin/horarios.php') ?>" class="small">← Volver a horarios</a>
  <h1 class="h2 mt-2"><?= e($titulo) ?></h1>

  <?php if ($errores): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <?php if ($id && $reservados > 0): ?>
    <div class="alert alert-info">Este horario tiene <?= $reservados ?> cupo(s) reservado(s).</div>
  <?php endif; ?>

  <form method="post" class="card tarjeta card-body">
    <?= csrf_campo() ?>
    <div class="row g-3">
      <div class="col-12">
        <label for="servicio_id" class="form-label">Servicio</label>
        <select id="servicio_id" name="servicio_id" class="form-select" required>
          <option value="">Selecciona un servicio</option>
          <?php foreach ($servicios as $s): ?>
            <option value="<?= (int) $s['id'] ?>" <?= (int) $horario['servicio_id'] === (int) $s['id'] ? 'selected' : '' ?>>
              <?= e($s['nombre']) ?><?= $s['estado'] === 'INACTIVO' ? ' (inactivo)' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label for="fecha" class="form-label">Fecha</label>
        <input type="date" id="fecha" name="fecha" class="form-control" required <?= $id ? '' : 'min="' . date('Y-m-d') . '"' ?> value="<?= e($horario['fecha']) ?>">
      </div>
      <div class="col-md-4">
        <label for="hora_inicio" class="form-label">Hora de inicio</label>
        <input type="time" id="hora_inicio" name="hora_inicio" class="form-control" required value="<?= e($horario['hora_inicio']) ?>">
      </div>
      <div class="col-md-4">
        <label for="hora_fin" class="form-label">Hora de fin</label>
        <input type="time" id="hora_fin" name="hora_fin" class="form-control" required value="<?= e($horario['hora_fin']) ?>">
      </div>
      <div class="col-md-6">
        <label for="cupos_totales" class="form-label">Cupos totales</label>
        <input type="number" id="cupos_totales" name="cupos_totales" class="form-control" min="<?= max(1, $reservados) ?>" max="100" required value="<?= e($horario['cupos_totales']) ?>">
      </div>
      <div class="col-md-6">
        <label for="estado" class="form-label">Estado</label>
        <select id="estado" name="estado" class="form-select">
          <option value="ACTIVO" <?= $horario['estado'] === 'ACTIVO' ? 'selected' : '' ?>>ACTIVO (se puede reservar)</option>
          <option value="INACTIVO" <?= $horario['estado'] === 'INACTIVO' ? 'selected' : '' ?>>INACTIVO (oculto)</option>
        </select>
      </div>
    </div>
    <button type="submit" class="btn btn-primary mt-4">Guardar</button>
  </form>
</section>
<?php require '../includes/footer.php'; ?>
