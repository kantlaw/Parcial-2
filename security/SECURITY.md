# Seguridad · ByteFix Soporte

## Controles implementados

| Control | Dónde |
|---|---|
| Contraseñas con `password_hash()` / `password_verify()`, mínimo 8 caracteres | `registro.php`, `login.php`, `instalar.php` |
| Sesión: `session_regenerate_id(true)` al entrar, sesión destruida al salir, cookie `HttpOnly` y `SameSite=Strict` (`Secure` con HTTPS) | `includes/seguridad.php`, `login.php`, `logout.php` |
| Bloqueo de 15 minutos tras 5 intentos fallidos, con el mismo mensaje exista o no el correo | `login.php`, tabla `intentos_login` |
| PDO con consultas preparadas en todos los valores recibidos | Todas las páginas |
| Salida escapada con `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` mediante `e()` | `includes/seguridad.php` |
| Token CSRF en todos los formularios POST (incluido cerrar sesión y eliminar) | `csrf_campo()` y `csrf_verificar()` |
| Rol y propiedad validados en PHP | `requiere_rol()`; consultas del cliente con `WHERE usuario_id = :usuario` |
| Cabeceras `Content-Security-Policy`, `X-Content-Type-Options`, `X-Frame-Options` | `includes/seguridad.php` |
| Errores genéricos sin SQL ni rutas internas | `set_exception_handler()` en `includes/seguridad.php` |

## Pruebas

Para cada prueba: objetivo, pasos, resultado observado y control aplicado. Las capturas van en `security/evidencias/`.

### 1. SQL Injection en login o búsqueda
### 2. XSS en comentario o campo visible
### 3. Acceso directo de CLIENTE a página ADMIN
### 4. Acceso a reserva de otro cliente
### 5. Formulario sin token CSRF válido
### 6. Cinco intentos de login fallidos
### 7. Inspección de cabeceras HTTP
