<?php
// ============================================================
// Conexión a MySQL con PDO
// ============================================================
require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NOMBRE . ';charset=utf8mb4',
        DB_USUARIO,
        DB_CLAVE,
        [
            // Si una consulta falla, PDO lanza una excepción
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // fetch() devuelve arreglos asociativos: $fila['nombre']
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Consultas preparadas reales en el servidor MySQL
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $ex) {
    // El detalle va al log del servidor; al usuario solo un mensaje general
    error_log('Error de conexión: ' . $ex->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Intenta más tarde.');
}
