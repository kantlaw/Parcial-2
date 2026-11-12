<?php
// ============================================================
// ADMIN - Crear o editar una categoría (la "C" y la "U" del CRUD)
// Sin ?id= crea una nueva; con ?id= edita la existente.
// ============================================================
require_once '../includes/seguridad.php';
requiere_rol('ADMIN');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: null;
$categoria = ['nombre' => '', 'descripcion' => '', 'estado' => 'ACTIVO'];
$errores = [];

// Si es edición, cargar los datos actuales
if ($id) {
    $consulta = $pdo->prepare('SELECT nombre, descripcion, estado FROM categorias WHERE id = :id');
    $consulta->execute([':id' => $id]);
    $categoria = $consulta->fetch();
    if (!$categoria) {
        flash('warning', 'La categoría no existe.');
        redirigir('admin/categorias.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();

    $categoria['nombre']      = trim($_POST['nombre'] ?? '');
    $categoria['descripcion'] = trim($_POST['descripcion'] ?? '');
    $categoria['estado']      = $_POST['estado'] ?? '';

    if ($categoria['nombre'] === '' || mb_strlen($categoria['nombre']) > 80) {
        $errores[] = 'El nombre es obligatorio (máximo 80 caracteres).';
    }
    if ($categoria['descripcion'] === '' || mb_strlen($categoria['descripcion']) > 255) {
        $errores[] = 'La descripción es obligatoria (máximo 255 caracteres).';
    }
    // Lista permitida: solo se aceptan estos dos valores
    if (!in_array($categoria['estado'], ['ACTIVO', 'INACTIVO'], true)) {
        $errores[] = 'Selecciona un estado válido.';
    }

    if (!$errores) {
        $parametros = [
            ':nombre'      => $categoria['nombre'],
            ':descripcion' => $categoria['descripcion'],
            ':estado'      => $categoria['estado'],
        ];
        try {
            if ($id) {
                $sql = 'UPDATE categorias SET nombre = :nombre, descripcion = :descripcion, estado = :estado WHERE id = :id';
                $parametros[':id'] = $id;
            } else {
                $sql = 'INSERT INTO categorias (nombre, descripcion, estado) VALUES (:nombre, :descripcion, :estado)';
            }
            $pdo->prepare($sql)->execute($parametros);

            flash('success', $id ? 'Categoría actualizada.' : 'Categoría creada.');
            redirigir('admin/categorias.php');
        } catch (PDOException $ex) {
            // 23000 = violación de restricción (aquí: nombre repetido)
            if ($ex->getCode() === '23000') {
                $errores[] = 'Ya existe una categoría con ese nombre.';
            } else {
                throw $ex;
            }
        }
    }
}

$titulo = $id ? 'Editar categoría' : 'Nueva categoría';
require '../includes/header.php';
?>
<section class="mx-auto caja-media">
  <a href="<?= url('admin/categorias.php') ?>" class="small">← Volver a categorías</a>
  <h1 class="h2 mt-2"><?= e($titulo) ?></h1>

  <?php if ($errores): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errores as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" class="card tarjeta card-body">
    <?= csrf_campo() ?>
    <div class="mb-3">
      <label for="nombre" class="form-label">Nombre</label>
      <input type="text" id="nombre" name="nombre" class="form-control" maxlength="80" required value="<?= e($categoria['nombre']) ?>">
    </div>
    <div class="mb-3">
      <label for="descripcion" class="form-label">Descripción</label>
      <textarea id="descripcion" name="descripcion" class="form-control" rows="3" maxlength="255" required><?= e($categoria['descripcion']) ?></textarea>
    </div>
    <div class="mb-3">
      <label for="estado" class="form-label">Estado</label>
      <select id="estado" name="estado" class="form-select">
        <option value="ACTIVO" <?= $categoria['estado'] === 'ACTIVO' ? 'selected' : '' ?>>ACTIVO (visible en el catálogo)</option>
        <option value="INACTIVO" <?= $categoria['estado'] === 'INACTIVO' ? 'selected' : '' ?>>INACTIVO (oculta)</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Guardar</button>
  </form>
</section>
<?php require '../includes/footer.php'; ?>
