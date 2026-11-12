<?php
// ============================================================
// ADMIN - Eliminar horario (solo si no tiene reservas)
// ============================================================
require_once '../includes/seguridad.php';
requiere_rol('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('admin/horarios.php');
}
csrf_verificar();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

$consulta = $pdo->prepare('SELECT COUNT(*) FROM reservas WHERE horario_id = :id');
$consulta->execute([':id' => $id]);

if ((int) $consulta->fetchColumn() > 0) {
    flash('danger', 'No se puede eliminar: el horario tiene reservas. Puedes desactivarlo.');
} else {
    $consulta = $pdo->prepare('DELETE FROM horarios WHERE id = :id');
    $consulta->execute([':id' => $id]);
    flash($consulta->rowCount() ? 'success' : 'warning',
          $consulta->rowCount() ? 'Horario eliminado.' : 'El horario no existe.');
}
redirigir('admin/horarios.php');
