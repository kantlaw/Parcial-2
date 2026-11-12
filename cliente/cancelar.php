<?php
// ============================================================
// CLIENTE - Cancelar una reserva PROPIA
// Solo si está PENDIENTE o CONFIRMADA y la cita no ha comenzado.
// La búsqueda incluye "AND usuario_id = :usuario": si la reserva es
// de otro cliente, se comporta como si no existiera.
// ============================================================
require_once '../includes/seguridad.php';
require_once '../includes/reservas.php';
$usuario = requiere_rol('CLIENTE');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('cliente/mis_reservas.php');
}
csrf_verificar();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

try {
    $pdo->beginTransaction();

    $sql = "SELECT r.id, r.estado, r.horario_id,
                   TIMESTAMP(h.fecha, h.hora_inicio) <= NOW() AS comenzo
            FROM reservas r
            JOIN horarios h ON h.id = r.horario_id
            WHERE r.id = :id AND r.usuario_id = :usuario
            FOR UPDATE";
    $consulta = $pdo->prepare($sql);
    $consulta->execute([':id' => $id, ':usuario' => $usuario['id']]);
    $reserva = $consulta->fetch();

    if (!$reserva) {
        throw new RuntimeException('La reserva no existe.');
    }
    if (!in_array($reserva['estado'], ['PENDIENTE', 'CONFIRMADA'], true)) {
        throw new RuntimeException('Solo puedes cancelar reservas pendientes o confirmadas.');
    }
    if ($reserva['comenzo']) {
        throw new RuntimeException('La cita ya comenzó; no se puede cancelar.');
    }
    if (!cancelar_reserva($pdo, (int) $reserva['id'], (int) $reserva['horario_id'])) {
        throw new RuntimeException('La reserva ya no se puede cancelar.');
    }

    $pdo->commit();
    flash('success', 'Reserva cancelada. El cupo quedó libre para otra persona.');
} catch (RuntimeException $ex) {
    $pdo->rollBack();
    flash('danger', $ex->getMessage());
}
redirigir('cliente/mis_reservas.php');
