<?php
// ============================================================
// ADMIN - Cambiar el estado de una reserva
// Transiciones permitidas (cualquier otra se rechaza):
//   PENDIENTE  -> CONFIRMADA | CANCELADA
//   CONFIRMADA -> REALIZADA  | CANCELADA
// REALIZADA y CANCELADA son estados finales.
// ============================================================
require_once '../includes/seguridad.php';
require_once '../includes/reservas.php';
requiere_rol('ADMIN');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('admin/reservas.php');
}
csrf_verificar();

const TRANSICIONES = [
    'PENDIENTE'  => ['CONFIRMADA', 'CANCELADA'],
    'CONFIRMADA' => ['REALIZADA', 'CANCELADA'],
];

$id    = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$nuevo = $_POST['estado'] ?? '';

try {
    $pdo->beginTransaction();

    $consulta = $pdo->prepare('SELECT id, estado, horario_id FROM reservas WHERE id = :id FOR UPDATE');
    $consulta->execute([':id' => $id]);
    $reserva = $consulta->fetch();

    if (!$reserva) {
        throw new RuntimeException('La reserva no existe.');
    }
    if (!in_array($nuevo, TRANSICIONES[$reserva['estado']] ?? [], true)) {
        throw new RuntimeException('No se puede pasar de ' . $reserva['estado'] . ' a ' . $nuevo . '.');
    }

    if ($nuevo === 'CANCELADA') {
        // Devuelve exactamente un cupo (ver includes/reservas.php)
        cancelar_reserva($pdo, (int) $reserva['id'], (int) $reserva['horario_id']);
    } else {
        // UPDATE condicionado: solo cambia si sigue en el estado que leímos
        $consulta = $pdo->prepare('UPDATE reservas SET estado = :nuevo WHERE id = :id AND estado = :actual');
        $consulta->execute([':nuevo' => $nuevo, ':id' => $reserva['id'], ':actual' => $reserva['estado']]);
    }

    $pdo->commit();
    flash('success', 'Reserva #' . $reserva['id'] . ' actualizada a ' . $nuevo . '.');
} catch (RuntimeException $ex) {
    $pdo->rollBack();
    flash('danger', $ex->getMessage());
}
redirigir('admin/reservas.php');
