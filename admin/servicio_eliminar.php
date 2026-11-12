<?php
// ============================================================
// ADMIN - Eliminar servicio
// Solo si no tiene horarios ni reservas (integridad referencial).
// ============================================================
require_once '../includes/seguridad.php';
requiere_rol('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('admin/servicios.php');
}
csrf_verificar();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

$consulta = $pdo->prepare('SELECT (SELECT COUNT(*) FROM horarios WHERE servicio_id = :id1)
                                + (SELECT COUNT(*) FROM reservas WHERE servicio_id = :id2)');
$consulta->execute([':id1' => $id, ':id2' => $id]);

if ((int) $consulta->fetchColumn() > 0) {
    flash('danger', 'No se puede eliminar: el servicio tiene horarios o reservas. Puedes desactivarlo.');
} else {
    $consulta = $pdo->prepare('DELETE FROM servicios WHERE id = :id');
    $consulta->execute([':id' => $id]);
    flash($consulta->rowCount() ? 'success' : 'warning',
          $consulta->rowCount() ? 'Servicio eliminado.' : 'El servicio no existe.');
}
redirigir('admin/servicios.php');
