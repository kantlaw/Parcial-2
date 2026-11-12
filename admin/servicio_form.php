<?php
// ============================================================
// ADMIN - Crear o editar un servicio
// ============================================================
require_once '../includes/seguridad.php';
requiere_rol('ADMIN');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$servicio = ['categoria_id' => '', 'nombre' => '', 'descripcion' => '', 'precio' => '', 'duracion_min' => '', 'estado' => 'ACTIVO'];
$errores = [];

$categorias = $pdo->query('SELECT id, nombre, estado FROM categorias ORDER BY nombre')->fetchAll();
$ids_categorias = array_map('intval', array_column($categorias, 'id'));

if ($id) {
    $consulta = $pdo->prepare('SELECT categoria_id, nombre, descripcion, precio, duracion_min, estado FROM servicios WHERE id = :id');
    $consulta->execute([':id' => $id]);
    $servicio = $consulta->fetch();
    if (!$servicio) {
        flash('warning', 'El servicio no existe.');
        redirigir('admin/servicios.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();

    $servicio['categoria_id'] = filter_input(INPUT_POST, 'categoria_id', FILTER_VALIDATE_INT);
    $servicio['nombre']       = trim($_POST['nombre'] ?? '');
    $servicio['descripcion']  = trim($_POST['descripcion'] ?? '');
    $servicio['precio']       = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
    $servicio['duracion_min'] = filter_input(INPUT_POST, 'duracion_min', FILTER_VALIDATE_INT);
    $servicio['estado']       = $_POST['estado'] ?? '';

    if (!in_array($servicio['categoria_id'], $ids_categorias, true)) {
        $errores[] = 'Selecciona una categoría válida.';
    }
    if ($servicio['nombre'] === '' || mb_strlen($servicio['nombre']) > 100) {
        $errores[] = 'El nombre es obligatorio (máximo 100 caracteres).';
    }
    if ($servicio['descripcion'] === '') {
        $errores[] = 'La descripción es obligatoria.';
    }
    // Regla del negocio: precio no negativo
    if ($servicio['precio'] === false || $servicio['precio'] === null || $servicio['precio'] < 0 || $servicio['precio'] > 99999) {
        $errores[] = 'El precio debe ser un número entre 0 y 99,999.';
    }
    // Regla del negocio: duración mayor que cero
    if ($servicio['duracion_min'] === false || $servicio['duracion_min'] === null || $servicio['duracion_min'] <= 0) {
        $errores[] = 'La duración debe ser un número entero mayor que 0.';
    }
    if (!in_array($servicio['estado'], ['ACTIVO', 'INACTIVO'], true)) {
        $errores[] = 'Selecciona un estado válido.';
    }

    if (!$errores) {
        $parametros = [
            ':categoria_id' => $servicio['categoria_id'],
            ':nombre'       => $servicio['nombre'],
            ':descripcion'  => $servicio['descripcion'],
            ':precio'       => $servicio['precio'],
            ':duracion_min' => $servicio['duracion_min'],
            ':estado'       => $servicio['estado'],
        ];
        if ($id) {
            $sql = 'UPDATE servicios
                    SET categoria_id = :categoria_id, nombre = :nombre, descripcion = :descripcion,
                        precio = :precio, duracion_min = :duracion_min, estado = :estado
                    WHERE id = :id';
            $parametros[':id'] = $id;
        } else {
            $sql = 'INSERT INTO servicios (categoria_id, nombre, descripcion, precio, duracion_min, estado)
                    VALUES (:categoria_id, :nombre, :descripcion, :precio, :duracion_min, :estado)';
        }
        $pdo->prepare($sql)->execute($parametros);

        flash('success', $id ? 'Servicio actualizado.' : 'Servicio creado.');
        redirigir('admin/servicios.php');
    }
}

$titulo = $id ? 'Editar servicio' : 'Nuevo servicio';
require '../includes/header.php';
?>
<section class="mx-auto caja-media">
  <a href="<?= url('admin/servicios.php') ?>" class="small">← Volver a servicios</a>
  <h1 class="h2 mt-2"><?= e($titulo) ?></h1>

  <?php if ($errores): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" class="card tarjeta card-body">
    <?= csrf_campo() ?>
    <div class="row g-3">
      <div class="col-md-6">
        <label for="categoria_id" class="form-label">Categoría</label>
        <select id="categoria_id" name="categoria_id" class="form-select" required>
          <option value="">Selecciona una categoría</option>
          <?php foreach ($categorias as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>" <?= (int) $servicio['categoria_id'] === (int) $cat['id'] ? 'selected' : '' ?>>
              <?= e($cat['nombre']) ?><?= $cat['estado'] === 'INACTIVO' ? ' (inactiva)' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label for="nombre" class="form-label">Nombre</label>
        <input type="text" id="nombre" name="nombre" class="form-control" maxlength="100" required value="<?= e($servicio['nombre']) ?>">
      </div>
      <div class="col-12">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea id="descripcion" name="descripcion" class="form-control" rows="3" required><?= e($servicio['descripcion']) ?></textarea>
      </div>
      <div class="col-md-4">
        <label for="precio" class="form-label">Precio (USD)</label>
        <input type="number" id="precio" name="precio" class="form-control" min="0" step="0.01" required value="<?= e($servicio['precio']) ?>">
      </div>
      <div class="col-md-4">
        <label for="duracion_min" class="form-label">Duración (minutos)</label>
        <input type="number" id="duracion_min" name="duracion_min" class="form-control" min="1" step="1" required value="<?= e($servicio['duracion_min']) ?>">
      </div>
      <div class="col-md-4">
        <label for="estado" class="form-label">Estado</label>
        <select id="estado" name="estado" class="form-select">
          <option value="ACTIVO" <?= $servicio['estado'] === 'ACTIVO' ? 'selected' : '' ?>>ACTIVO</option>
          <option value="INACTIVO" <?= $servicio['estado'] === 'INACTIVO' ? 'selected' : '' ?>>INACTIVO</option>
        </select>
      </div>
    </div>
    <button type="submit" class="btn btn-primary mt-4">Guardar</button>
  </form>
</section>
<?php require '../includes/footer.php'; ?>
