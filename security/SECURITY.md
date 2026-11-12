# Seguridad · ByteFix Soporte (Book&Go Lite)

Todas las pruebas se hicieron **solo en el entorno local de desarrollo** (XAMPP, `localhost`) el 12 de noviembre de 2026. Las capturas están en `security/evidencias/`.

Usuarios de prueba usados: un ADMIN creado con `instalar.php` y dos CLIENTES registrados desde `registro.php` (Cliente A y Cliente B). Sus contraseñas no se publican en este repositorio.

## Controles implementados

| Control | Cómo se aplica | Dónde |
|---|---|---|
| Contraseñas | `password_hash()` / `password_verify()`. Política: mínimo 12 caracteres (máx. 72), mayúscula, minúscula, número y símbolo, sin espacios, sin 4 caracteres repetidos, sin contener el correo y fuera de una lista de claves comunes. Se valida en PHP; la lista en verde del formulario es solo ayuda visual | `validar_clave()` en `includes/seguridad.php`, `registro.php`, `instalar.php`, `js/app.js` |
| Sesiones | `session_regenerate_id(true)` al iniciar sesión; al salir se vacía `$_SESSION`, se borra la cookie y `session_destroy()`. Cookie `HttpOnly`, `SameSite=Strict` y `Secure` cuando hay HTTPS. En cada página se comprueba que el usuario siga ACTIVO | `includes/seguridad.php`, `login.php`, `logout.php` |
| Intentos de login | Bloqueo de 15 minutos tras 5 fallos por correo **o** por IP. Mismo mensaje y mismo tiempo de respuesta exista o no el correo (hash señuelo) | `login.php`, tabla `intentos_login` |
| SQL Injection | PDO con `EMULATE_PREPARES = false` y parámetros en todos los valores. Los únicos valores "dinámicos" (estado de reserva, estado ACTIVO/INACTIVO) se validan con listas permitidas | Todas las consultas |
| XSS | Toda salida pasa por `e()` = `htmlspecialchars($t, ENT_QUOTES, 'UTF-8')`. Además la CSP bloquea scripts en línea | `includes/seguridad.php` |
| CSRF | Token aleatorio de 64 caracteres (`random_bytes`) por sesión, comparado con `hash_equals()` en **todo** POST: login, registro, cerrar sesión, CRUD, reservar, cancelar, cambiar estado y reseñas | `csrf_campo()` / `csrf_verificar()` |
| Autorización | `requiere_rol('ADMIN')` o `requiere_rol('CLIENTE')` al inicio de cada página privada. La propiedad se valida en cada consulta con `AND usuario_id = :usuario` (id de la sesión, nunca de la URL) | `admin/*`, `cliente/*` |
| Cabeceras HTTP | `Content-Security-Policy` (incluye `frame-ancestors 'none'`), `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy` y se elimina `X-Powered-By` | `includes/seguridad.php` |
| Errores | `display_errors = 0` y `set_exception_handler()`: el usuario ve un mensaje general; el detalle va a `error_log()` | `includes/seguridad.php`, `includes/conexion.php` |
| Integridad de reservas | Transacción + `SELECT ... FOR UPDATE` + `UPDATE ... WHERE cupos_disponibles > 0`. Cancelar devuelve un cupo solo si la fila cambió de PENDIENTE/CONFIRMADA a CANCELADA. CHECK en MySQL: `0 <= cupos_disponibles <= cupos_totales` | `cliente/reservar.php`, `includes/reservas.php`, `database/bookgo_lite.sql` |

## Pruebas

### 1. SQL Injection en login y en búsqueda

- **Objetivo:** comprobar que una entrada con código SQL no altera la consulta ni permite entrar sin contraseña.
- **Pasos:**
  1. En `login.php`, F12 → cambiar el campo correo a `type="text"` y escribir `admin@bytefix.com' OR '1'='1' -- ` con cualquier contraseña.
  2. En el catálogo, buscar `' OR '1'='1`.
- **Resultado observado:** el login responde "Correo o contraseña incorrectos" y no inicia sesión (`1a_sqli_login.png`). La búsqueda devuelve 0 servicios en lugar de todos (`1b_sqli_busqueda.png`).
- **Control aplicado:** consultas preparadas de PDO (`WHERE u.correo = :correo`, `s.nombre LIKE :busqueda`). MySQL recibe el texto como dato, no como código.

### 2. XSS en comentario de reseña

- **Objetivo:** verificar que un script escrito por un usuario no se ejecuta en el navegador de otros.
- **Pasos:** con el Cliente A, en una reserva REALIZADA, publicar la reseña `<script>alert('XSS')</script> Excelente diagnóstico, rápido y bien explicado.` y abrir el detalle del servicio.
- **Resultado observado:** el comentario se muestra como texto literal y no aparece ninguna alerta (`2_xss_resena.png`). En el código fuente se ve `&lt;script&gt;`.
- **Control aplicado:** salida con `e()` (`htmlspecialchars` con `ENT_QUOTES` y UTF-8) y CSP `script-src 'self'`.

### 3. Acceso directo de CLIENTE a página ADMIN

- **Objetivo:** confirmar que ocultar el menú no es la única protección.
- **Pasos:** con sesión de CLIENTE, escribir en la barra `localhost/bookgo-lite/admin/reservas.php`.
- **Resultado observado:** respuesta HTTP 403 con el mensaje "No tienes permiso para ver esta página" (`3_cliente_a_admin.png`).
- **Control aplicado:** `requiere_rol('ADMIN')` en la primera línea de cada archivo de `admin/`.

### 4. Acceso a reserva de otro cliente

- **Objetivo:** comprobar que un cliente no puede ver ni modificar reservas ajenas.
- **Pasos:**
  1. Con el Cliente B, enviar a `cliente/cancelar.php` un POST con el `id` de una reserva del Cliente A (con token CSRF válido de su propia sesión).
  2. Abrir `cliente/resena.php?reserva=<id de la reserva del Cliente A>`.
- **Resultado observado:** en ambos casos aparece "La reserva no existe" y la reserva del Cliente A sigue PENDIENTE (`4a_cancelar_reserva_ajena.png`, `4b_resena_reserva_ajena.png`). En "Mis reservas" el Cliente B solo ve las suyas.
- **Control aplicado:** todas las consultas del cliente filtran con `AND r.usuario_id = :usuario`, usando el id de la sesión. Si la reserva no es suya, se trata igual que si no existiera.

### 5. Formulario sin token CSRF válido

- **Objetivo:** verificar que una operación que cambia datos se rechaza sin token válido.
- **Pasos:** como ADMIN, en `admin/reservas.php`, F12 → cambiar el valor del campo oculto `csrf` por `token-falso` y pulsar **Confirmar** en una reserva PENDIENTE. También se envió el mismo formulario sin el campo `csrf`.
- **Resultado observado:** respuesta HTTP 400 "Solicitud no válida…" y la reserva sigue PENDIENTE (`5_csrf_rechazado.png`).
- **Control aplicado:** `csrf_verificar()` compara el token con el de la sesión usando `hash_equals()` antes de tocar la base de datos.

### 6. Cinco intentos de login fallidos

- **Objetivo:** comprobar el bloqueo temporal contra fuerza bruta.
- **Pasos:** intentar entrar 5 veces con una contraseña incorrecta y luego una sexta vez, incluso con la contraseña correcta.
- **Resultado observado:** los 5 primeros intentos dicen "Correo o contraseña incorrectos"; desde el sexto aparece "Demasiados intentos fallidos. Espera 15 minutos e intenta de nuevo", aun con la contraseña correcta (`6a_bloqueo_login.png`). Cada intento queda registrado en la tabla `intentos_login`.
- **Control aplicado:** conteo de intentos de los últimos 15 minutos por correo o IP en `login.php`; el mensaje no indica si el correo existe.

### 7. Inspección de cabeceras HTTP

- **Objetivo:** confirmar que el servidor envía las cabeceras de seguridad.
- **Pasos:** `curl -I http://localhost/bookgo-lite/login.php` (o F12 → Red → Encabezados de respuesta).
- **Resultado observado:** aparecen `Content-Security-Policy` con `frame-ancestors 'none'`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY` y la cookie `BOOKGO_SESION` con `HttpOnly; SameSite=Strict` (`7_cabeceras_http.png`). No aparece `X-Powered-By`.
- **Control aplicado:** `header()` y `header_remove()` en `includes/seguridad.php`, y `session_set_cookie_params()`.

## Pruebas adicionales de reglas de negocio

| Prueba | Resultado |
|---|---|
| Reservar un horario con 0 cupos enviando el POST a mano | "Ya no quedan cupos en ese horario"; los cupos siguen en 0 |
| Reservar dos veces el mismo horario | "Ya tienes una reserva activa en este horario" |
| Enviar un horario de otro servicio | "Ese horario no está disponible para este servicio" |
| Cancelar dos veces la misma reserva | La segunda vez se rechaza y el cupo se devuelve una sola vez |
| ADMIN intenta cancelar una reserva REALIZADA | "No se puede pasar de REALIZADA a CANCELADA"; no se devuelve cupo |
| Crear horario en el pasado o con hora final menor | Se rechaza con ambos mensajes |
| Reseñar dos veces la misma reserva | "Ya dejaste una reseña para esta reserva" |
| Registro con contraseña débil | Se listan las reglas que no cumple |
