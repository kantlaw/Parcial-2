<?php
// ============================================================
// CLIENTE - Crear una reserva
// Todo ocurre dentro de una TRANSACCIÓN:
//   1. Se bloquea el horario (SELECT ... FOR UPDATE) y se valida que
//      pertenezca al servicio, esté activo, sea futuro y tenga cupos.
//   2. Se comprueba que el cliente no tenga otra reserva activa ahí.
//   3. Se descuenta el cupo con un UPDATE condicionado
//      (WHERE cupos_disponibles > 0).
//   4. Se inserta la reserva PENDIENTE.
// Si algo falla se hace ROLLBACK y no cambia nada.
// ============================================================
require_once '../includes/seguridad.php';
$usuario = requiere_rol('CLIENTE');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('catalogo.php');
}
csrf_verificar();

$horario_id  = filter_input(INPUT_POST, 'horario_id', FILTER_VALIDATE_INT);
$servicio_id = filter_input(INPUT_POST, 'servicio_id', FILTER_VALIDATE_INT);
$volver = $servicio_id ? 'servicio.php?id=' . $servicio_id : 'catalogo.php';

if (!$horario_id || !$servicio_id) {
    flash('danger', 'Selecciona un horario válido.');
    redirigir($volver);
}

try {
    $pdo->beginTransaction();

    // 1. Horario del servicio indicado, activo, futuro y con cupos
    $sql = "SELECT h.id, h.cupos_disponibles
            FROM horarios h
            JOIN servicios s ON s.id = h.servicio_id
            JOIN categorias c ON c.id = s.categoria_id
            WHERE h.id = :horario AND h.servicio_id = :servicio
              AND h.estado = 'ACTIVO' AND s.estado = 'ACTIVO' AND c.estado = 'ACTIVO'
              AND TIMESTAMP(h.fecha, h.hora_inicio) > NOW()
            FOR UPDATE";
    $consulta = $pdo->prepare($sql);
    $consulta->execute([':horario' => $horario_id, ':servicio' => $servicio_id]);
    $horario = $consulta->fetch();

    if (!$horario) {
        throw new RuntimeException('Ese horario no está disponible para este servicio.');
    }

    // 2. ¿El cliente ya tiene una reserva activa en este horario?
    $sql = "SELECT COUNT(*) FROM reservas
            WHERE usuario_id = :usuario AND horario_id = :horario
              AND estado IN ('PENDIENTE', 'CONFIRMADA')";
    $consulta = $pdo->prepare($sql);
    $consulta->execute([':usuario' => $usuario['id'], ':horario' => $horario_id]);
    if ((int) $consulta->fetchColumn() > 0) {
        throw new RuntimeException('Ya tienes una reserva activa en este horario.');
    }

    // 3. Descontar un cupo SOLO si todavía hay cupos
    $consulta = $pdo->prepare('UPDATE horarios SET cupos_disponibles = cupos_disponibles - 1
                               WHERE id = :horario AND cupos_disponibles > 0');
    $consulta->execute([':horario' => $horario_id]);
    if ($consulta->rowCount() !== 1) {
        throw new RuntimeException('Ya no quedan cupos en ese horario.');
    }

    // 4. Registrar la reserva en estado PENDIENTE
    $consulta = $pdo->prepare('INSERT INTO reservas (usuario_id, servicio_id, horario_id, estado)
                               VALUES (:usuario, :servicio, :horario, \'PENDIENTE\')');
    $consulta->execute([':usuario' => $usuario['id'], ':servicio' => $servicio_id, ':horario' => $horario_id]);

    $pdo->commit();
    flash('success', 'Reserva registrada. Queda PENDIENTE hasta que el negocio la confirme.');
    redirigir('cliente/mis_reservas.php');

} catch (RuntimeException $ex) {
    // Errores de negocio: se deshace todo y se explica al cliente
    $pdo->rollBack();
    flash('danger', $ex->getMessage());
    redirigir($volver);

} catch (PDOException $ex) {
    $pdo->rollBack();
    // 23000: la base de datos rechazó una reserva activa duplicada (uq_reserva_activa)
    if ($ex->getCode() === '23000') {
        flash('danger', 'Ya tienes una reserva activa en este horario.');
        redirigir($volver);
    }
    throw $ex;
}
