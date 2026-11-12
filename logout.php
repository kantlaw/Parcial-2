<?php
// ============================================================
// Cerrar sesión: solo por POST y con token CSRF
// ============================================================
require_once 'includes/seguridad.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('index.php');
}
csrf_verificar();

// 1. Vaciar los datos de la sesión
$_SESSION = [];

// 2. Borrar la cookie de sesión del navegador
$params = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires'  => time() - 3600,
    'path'     => $params['path'],
    'secure'   => $params['secure'],
    'httponly' => $params['httponly'],
    'samesite' => $params['samesite'],
]);

// 3. Destruir la sesión en el servidor
session_destroy();

// El mensaje de despedida se indica en la URL, porque la sesión ya no existe
redirigir('login.php?salida=1');
