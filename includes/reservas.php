<?php
// ============================================================
// Función compartida para cancelar una reserva.
// La usan el CLIENTE (cliente/cancelar.php) y el ADMIN
// (admin/reserva_estado.php).
//
// Devuelve exactamente UN cupo y solo una vez:
//   - el UPDATE de la reserva solo cambia filas PENDIENTE o CONFIRMADA;
//   - si ya estaba CANCELADA o REALIZADA, rowCount() es 0 y no se
//     devuelve nada;
//   - el cupo se suma solo si no supera el total.
// Debe llamarse dentro de una transacción.
// ============================================================

function cancelar_reserva(PDO $pdo, int $reserva_id, int $horario_id): bool
{
    $consulta = $pdo->prepare("UPDATE reservas SET estado = 'CANCELADA'
                               WHERE id = :id AND estado IN ('PENDIENTE', 'CONFIRMADA')");
    $consulta->execute([':id' => $reserva_id]);

    if ($consulta->rowCount() !== 1) {
        return false;   // ya estaba cancelada o realizada: no se devuelve cupo
    }

    $consulta = $pdo->prepare('UPDATE horarios SET cupos_disponibles = cupos_disponibles + 1
                               WHERE id = :horario AND cupos_disponibles < cupos_totales');
    $consulta->execute([':horario' => $horario_id]);
    return true;
}
