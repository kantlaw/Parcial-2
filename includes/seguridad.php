<?php
// ============================================================
// Seguridad común: se incluye al inicio de TODAS las páginas.
// Cabeceras HTTP, sesión, manejo de errores, XSS, CSRF y roles.
// ============================================================
require_once __DIR__ . '/conexion.php';

// Zona horaria de Panamá, para comparar fechas y horas de los horarios
// con la misma hora que usa MySQL en el equipo local
date_default_timezone_set('America/Panama');

// ---------- 1. Errores: nunca mostrar SQL ni rutas internas ----------
ini_set('display_errors', '0');
set_exception_handler(function (Throwable $ex) {
    error_log('Error no controlado: ' . $ex->getMessage());
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="es"><meta charset="UTF-8"><title>Error</title>'
       . '<p>Ocurrió un error inesperado. '
       . 'Intenta de nuevo o vuelve al <a href="' . BASE_URL . '/index.php">inicio</a>.</p></html>';
});

// ---------- 2. Cabeceras de seguridad HTTP ----------
// CSP: solo se permiten scripts, estilos e imágenes del propio sitio
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; "
     . "img-src 'self' data:; form-action 'self'; base-uri 'self'; frame-ancestors 'none'");
header('X-Content-Type-Options: nosniff');   // el navegador no "adivina" tipos de archivo
header('X-Frame-Options: DENY');             // nadie puede meter el sitio en un iframe
header('Referrer-Policy: same-origin');
header_remove('X-Powered-By');           // no revelar la versión de PHP

// ---------- 3. Sesión con cookies seguras ----------
session_name('BOOKGO_SESION');
session_set_cookie_params([
    'lifetime' => 0,                       // la cookie dura hasta cerrar el navegador
    'path'     => BASE_URL . '/',
    'httponly' => true,                    // JavaScript no puede leer la cookie
    'samesite' => 'Strict',                // no se envía desde otros sitios
    'secure'   => !empty($_SERVER['HTTPS']), // solo por HTTPS cuando se usa HTTPS
]);
session_start();

// ---------- 4. Funciones de ayuda ----------

// Escapa texto antes de mostrarlo en HTML (protección XSS)
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Arma la URL de una página del proyecto
function url(string $ruta): string
{
    return BASE_URL . '/' . ltrim($ruta, '/');
}

function redirigir(string $ruta): void
{
    header('Location: ' . url($ruta));
    exit;
}

// Mensajes de éxito o error que se muestran una sola vez
function flash(string $tipo, string $mensaje): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function tomar_flash(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensajes;
}

// ---------- 5. Protección CSRF ----------

// Genera (una vez por sesión) un token aleatorio
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

// Campo oculto que va dentro de cada formulario POST
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

// Rechaza la petición si el token no coincide con el de la sesión
function csrf_verificar(): void
{
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrf_token(), $enviado)) {
        http_response_code(400);
        exit('Solicitud no válida: el formulario expiró o no tiene un token CSRF correcto. Vuelve a la página e intenta de nuevo.');
    }
}

// ---------- 6. Usuario actual y permisos ----------

// Devuelve el usuario de la sesión, comprobando en la BD que siga ACTIVO
function usuario_actual(): ?array
{
    global $pdo;
    static $usuario = false;

    if ($usuario !== false) {
        return $usuario;
    }
    $usuario = null;

    if (!empty($_SESSION['usuario_id'])) {
        $sql = 'SELECT u.id, u.nombre, u.apellido, u.correo, u.estado, r.nombre AS rol
                FROM usuarios u
                JOIN roles r ON r.id = u.rol_id
                WHERE u.id = :id';
        $consulta = $pdo->prepare($sql);
        $consulta->execute([':id' => $_SESSION['usuario_id']]);
        $fila = $consulta->fetch();

        if ($fila && $fila['estado'] === 'ACTIVO') {
            $usuario = $fila;
        } else {
            // Usuario borrado o desactivado: se cierra su sesión
            $_SESSION = [];
        }
    }
    return $usuario;
}

function requiere_login(): array
{
    $usuario = usuario_actual();
    if ($usuario === null) {
        flash('warning', 'Inicia sesión para continuar.');
        redirigir('login.php');
    }
    return $usuario;
}

// Valida el rol en el servidor, aunque se escriba la URL directamente
function requiere_rol(string $rol): array
{
    $usuario = requiere_login();
    if ($usuario['rol'] !== $rol) {
        http_response_code(403);
        $titulo = 'Acceso denegado';
        require __DIR__ . '/header.php';
        echo '<div class="alert alert-danger">No tienes permiso para ver esta página.</div>';
        require __DIR__ . '/footer.php';
        exit;
    }
    return $usuario;
}

function es_admin(): bool
{
    $usuario = usuario_actual();
    return $usuario !== null && $usuario['rol'] === 'ADMIN';
}

// ---------- 7. Política de contraseñas ----------

// Contraseñas muy usadas que se rechazan aunque cumplan las demás reglas
const CLAVES_COMUNES = [
    'password123!', 'contraseña123', 'contrasena123', 'qwerty123456', '123456789abc',
    'administrador', 'admin1234567', 'bienvenido123', 'panama123456', 'abc123456789',
];

// Devuelve la lista de reglas que la contraseña NO cumple (vacía = válida)
function validar_clave(string $clave, string $correo = ''): array
{
    $errores = [];

    if (mb_strlen($clave) < 12) {
        $errores[] = 'La contraseña debe tener al menos 12 caracteres.';
    }
    // password_hash (bcrypt) solo usa los primeros 72 bytes
    if (strlen($clave) > 72) {
        $errores[] = 'La contraseña no puede superar los 72 caracteres.';
    }
    if (!preg_match('/[A-ZÁÉÍÓÚÑ]/u', $clave)) {
        $errores[] = 'Incluye al menos una letra mayúscula.';
    }
    if (!preg_match('/[a-záéíóúñ]/u', $clave)) {
        $errores[] = 'Incluye al menos una letra minúscula.';
    }
    if (!preg_match('/[0-9]/', $clave)) {
        $errores[] = 'Incluye al menos un número.';
    }
    if (!preg_match('/[^A-Za-zÁÉÍÓÚÑáéíóúñ0-9\s]/u', $clave)) {
        $errores[] = 'Incluye al menos un símbolo, por ejemplo: ! # $ % & * ?';
    }
    if (preg_match('/\s/u', $clave)) {
        $errores[] = 'La contraseña no puede tener espacios.';
    }
    // Cuatro caracteres iguales seguidos: aaaa, 1111
    if (preg_match('/(.)\1{3,}/u', $clave)) {
        $errores[] = 'No repitas el mismo carácter 4 veces seguidas.';
    }
    // No puede contener la parte del correo antes de la @
    $usuario_correo = mb_strtolower(strstr($correo, '@', true) ?: '');
    if (mb_strlen($usuario_correo) >= 4 && str_contains(mb_strtolower($clave), $usuario_correo)) {
        $errores[] = 'La contraseña no puede contener tu correo.';
    }
    if (in_array(mb_strtolower($clave), CLAVES_COMUNES, true)) {
        $errores[] = 'Esa contraseña es demasiado común. Elige otra.';
    }
    return $errores;
}

// Lista de requisitos que se muestra debajo del campo de contraseña.
// app.js marca en verde cada regla a medida que se cumple.
function requisitos_clave_html(): string
{
    $reglas = [
        'largo'     => 'Mínimo 12 caracteres',
        'mayuscula' => 'Una letra mayúscula',
        'minuscula' => 'Una letra minúscula',
        'numero'    => 'Un número',
        'simbolo'   => 'Un símbolo (! # $ % & * ?)',
        'espacios'  => 'Sin espacios',
    ];
    $html = '<ul class="requisitos-clave small mt-2 mb-0" id="requisitos-clave">';
    foreach ($reglas as $regla => $texto) {
        $html .= '<li data-regla="' . $regla . '">' . e($texto) . '</li>';
    }
    return $html . '</ul>';
}
