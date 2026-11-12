<?php
// ============================================================
// Seguridad común: se incluye al inicio de TODAS las páginas.
// Cabeceras HTTP, sesión, manejo de errores, XSS, CSRF y roles.
// ============================================================
require_once __DIR__ . '/conexion.php';

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
