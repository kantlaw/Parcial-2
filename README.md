# ByteFix Soporte · Book&Go Lite

Gestor de citas con reseñas para un negocio de **soporte técnico**. Proyecto Parcial 2 de Desarrollo Web (UTP · FISC · 2026).

**Integrantes:** Andrés Sinclair, Ricardo Cedeño, Jassier Hernández y Carlos Castillo.

- **Repositorio:** https://github.com/kantlaw/Parcial-2 (historial de contribuciones en la pestaña *Commits* y en *Insights → Contributors*)
- **Tecnologías:** PHP 8, MySQL/MariaDB (PDO), HTML5, CSS3, JavaScript y Bootstrap 5.3.3

## Requisitos

- XAMPP con Apache y MySQL encendidos (PHP 8.0 o superior).

## Instalación

1. Copia la carpeta `bookgo-lite` dentro de `C:\xampp\htdocs\`.
2. Abre phpMyAdmin (`http://localhost/phpmyadmin`), quédate en la página principal (sin seleccionar ninguna base), ve a **Importar** y selecciona `database/bookgo_lite.sql`. Se crea la base `bookgo_lite` con sus tablas y los datos de prueba. Los avisos amarillos "#1046 Base de datos no seleccionada" se pueden ignorar.
3. Copia `includes/config.ejemplo.php` como `includes/config.php`. Si tu MySQL tiene contraseña o la carpeta tiene otro nombre, ajusta `DB_CLAVE` y `BASE_URL`.
4. Crea el administrador en `http://localhost/bookgo-lite/instalar.php`. Solo funciona desde el mismo equipo y mientras no exista un ADMIN; después queda bloqueada.
5. Registra al menos un cliente desde **Crear cuenta** (`registro.php`). Para probar la seguridad entre clientes, registra dos.
6. Entra a `http://localhost/bookgo-lite/`.

El repositorio no contiene contraseñas: `includes/config.php` está en `.gitignore`, el ADMIN se crea en el paso 4 y los clientes en el paso 5.

**Contraseñas:** mínimo 12 caracteres, con mayúscula, minúscula, número y símbolo, sin espacios y sin contener el correo.

**Si el login queda bloqueado** por 5 intentos fallidos: espera 15 minutos o ejecuta `DELETE FROM intentos_login;` en phpMyAdmin.

## Funciones

| Rol | Puede |
|---|---|
| Visitante | Ver inicio, catálogo (búsqueda por nombre y filtro por categoría), detalle del servicio con horarios y reseñas, registrarse e iniciar sesión |
| CLIENTE | Reservar un horario futuro con cupos, ver sus reservas, cancelar las suyas antes de la cita (si están PENDIENTE o CONFIRMADA), reseñar una reserva REALIZADA |
| ADMIN | Panel con resumen; CRUD de categorías, servicios y horarios; ver todas las reservas y cambiar su estado (PENDIENTE → CONFIRMADA → REALIZADA, o CANCELADA) |

## Flujo de una reserva

1. El cliente abre un servicio y pulsa **Reservar** en un horario con cupos.
2. `cliente/reservar.php` abre una transacción, bloquea el horario (`SELECT ... FOR UPDATE`), verifica que pertenezca al servicio, que sea futuro y que el cliente no tenga otra reserva activa ahí, descuenta el cupo con `UPDATE ... WHERE cupos_disponibles > 0` e inserta la reserva PENDIENTE.
3. El ADMIN la confirma y, después de la cita, la marca REALIZADA.
4. Si se cancela estando PENDIENTE o CONFIRMADA, se devuelve **un** cupo. Cancelarla otra vez no devuelve nada, y una REALIZADA no se puede cancelar.
5. Con la reserva REALIZADA, el cliente puede dejar **una** reseña de 1 a 5 estrellas, que aparece en el detalle del servicio.

## Estructura

```
bookgo-lite/
├── index.php, catalogo.php, servicio.php   Páginas públicas
├── registro.php, login.php, logout.php     Autenticación
├── instalar.php                            Creación segura del ADMIN
├── cliente/                                Reservar, cancelar, mis reservas, reseña (solo CLIENTE)
├── admin/                                  Panel, CRUD y reservas (solo ADMIN)
├── includes/                               Conexión, seguridad, reservas y plantilla
├── css/, js/, img/                         Estilos, scripts e imágenes
├── database/bookgo_lite.sql                Script de la base de datos
└── security/                               SECURITY.md y evidencias de las pruebas
```

## Base de datos

Tablas: `roles`, `usuarios`, `categorias`, `servicios`, `horarios`, `reservas`, `resenas` y `intentos_login` (apoyo para el bloqueo de login).

Cómo las relaciones impiden datos inconsistentes:

- **FK con `ON DELETE RESTRICT`**: no se puede borrar una categoría con servicios, un servicio con horarios o reservas, ni un horario con reservas. En esos casos se usa desactivación lógica (`estado = INACTIVO`).
- **FK compuesta `reservas(horario_id, servicio_id) → horarios(id, servicio_id)`**: una reserva no puede apuntar a un horario de otro servicio.
- **FK compuesta `resenas(reserva_id, usuario_id) → reservas(id, usuario_id)`**: solo el dueño de la reserva puede reseñarla.
- **`UNIQUE` en `resenas.reserva_id`**: una sola reseña por reserva.
- **`UNIQUE (usuario_id, horario_id, activa)`**: un cliente no puede tener dos reservas activas en el mismo horario (`activa` es una columna calculada que vale 1 para PENDIENTE/CONFIRMADA y NULL en otro caso).
- **`CHECK`**: precio ≥ 0, duración > 0, hora final > hora inicial, `0 ≤ cupos_disponibles ≤ cupos_totales`, calificación de 1 a 5.

## Seguridad

Los controles y las 7 pruebas (objetivo, pasos, resultado y control) están en [`security/SECURITY.md`](security/SECURITY.md), con capturas en `security/evidencias/`.

## Distribución del trabajo

| Módulo | Archivos principales | Responsable |
|---|---|---|
| Base del proyecto y reseñas e interfaz | Base de datos, autenticación, catálogo y CRUD de categorías y servicios (revisión preliminar); `cliente/resena.php`, `cliente/mis_reservas.php`, `css/`, `README.md` | Andrés Sinclair |
| Seguridad y pruebas | `includes/seguridad.php`, `includes/conexion.php`, `registro.php`, `instalar.php`, `js/app.js`, `security/` | Ricardo Cedeño |
| Horarios | `admin/horarios.php`, `admin/horario_form.php`, `admin/horario_eliminar.php`, `admin/index.php`, `includes/header.php` | Jassier Hernández |
| Reservas | `cliente/reservar.php`, `cliente/cancelar.php`, `includes/reservas.php`, `admin/reservas.php`, `admin/reserva_estado.php`, `servicio.php` | Carlos Castillo |

## Bibliotecas de terceros

- [Bootstrap 5.3.3](https://getbootstrap.com/) (licencia MIT), incluido localmente en `css/` y `js/`. Los colores y detalles se adaptaron en `css/estilos.css`.

## Uso de inteligencia artificial

- **Herramienta:** Claude (Anthropic).
- **Uso:** generación del código base de ambas fases (base de datos, autenticación, CRUD, horarios, reservas y reseñas), de la política de contraseñas, de la documentación de seguridad y de una guía de estudio para comprender cada archivo.
- **Verificación:** probamos cada función en XAMPP (registro, login, permisos, CRUD, reservas, cupos, cancelaciones, reseñas) y ejecutamos las 7 pruebas de seguridad documentadas en `SECURITY.md`. Cada integrante estudió su módulo para poder explicarlo en la sustentación.
