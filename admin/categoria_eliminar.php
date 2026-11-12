<?php
// ============================================================
// ADMIN - Eliminar categoría (la "D" del CRUD)
// Solo por POST con token CSRF y solo si no tiene servicios.
// ============================================================
require_once '../includes/seguridad.php';
requiere_rol('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('admin/categorias.php');
}
csrf_verificar();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

// ¿Tiene servicios asociados?
$consulta = $pdo->prepare('SELECT COUNT(*) FROM servicios WHERE categoria_id = :id');
$consulta->execute([':id' => $id]);

if ((int) $consulta->fetchColumn() > 0) {
    flash('danger', 'No se puede eliminar: la categoría tiene servicios. Puedes desactivarla.');
} else {
    $consulta = $pdo->prepare('DELETE FROM categorias WHERE id = :id');
    $consulta->execute([':id' => $id]);
    flash($consulta->rowCount() ? 'success' : 'warning',
          $consulta->rowCount() ? 'Categoría eliminada.' : 'La categoría no existe.');
}
redirigir('admin/categorias.php');
