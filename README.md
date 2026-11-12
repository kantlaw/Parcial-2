# ByteFix Soporte · Book&Go Lite

Gestor de citas para un negocio de **soporte técnico**. Proyecto Parcial 2 de Desarrollo Web (UTP · FISC · 2026).

Tecnologías: PHP 8, MySQL/MariaDB (PDO), HTML5, CSS3, JavaScript y Bootstrap 5.3.3.

## Requisitos

- XAMPP con Apache y MySQL encendidos (PHP 8.0 o superior).

## Instalación

1. Copia la carpeta `bookgo-lite` dentro de `C:\xampp\htdocs\`.
2. Abre phpMyAdmin (`http://localhost/phpmyadmin`), ve a **Importar** y selecciona `database/bookgo_lite.sql`.
   Crea la base `bookgo_lite` con las 7 tablas y los datos de prueba.
3. Copia `includes/config.ejemplo.php` como `includes/config.php`.
   Si tu MySQL tiene contraseña o la carpeta tiene otro nombre, ajusta `DB_CLAVE` y `BASE_URL`.
4. Crea el administrador entrando a `http://localhost/bookgo-lite/instalar.php`.
   Solo funciona desde el mismo equipo y mientras no exista un ADMIN; después se bloquea sola.
5. Entra a `http://localhost/bookgo-lite/`.

Los clientes de prueba se crean desde **Crear cuenta** (`registro.php`).
El repositorio no contiene contraseñas: `config.php` está en `.gitignore` y el ADMIN se crea en el paso 4.

## Estructura

```
bookgo-lite/
├── index.php, catalogo.php, servicio.php   Páginas públicas
├── registro.php, login.php, logout.php     Autenticación
├── instalar.php                            Creación segura del ADMIN
├── admin/                                  Panel y CRUD (solo ADMIN)
├── cliente/                                Vistas del CLIENTE
├── includes/                               Conexión, seguridad y plantilla
├── css/, js/, img/                         Estilos, scripts e imágenes
├── database/bookgo_lite.sql                Script de la base de datos
└── security/                               SECURITY.md y evidencias
```

## Base de datos

Tablas: `roles`, `usuarios`, `categorias`, `servicios`, `horarios`, `reservas`, `resenas` (más `intentos_login` como apoyo para el bloqueo de login).

Cómo las relaciones impiden datos inconsistentes:

- **FK con `ON DELETE RESTRICT`**: no se puede borrar una categoría con servicios, ni un servicio con horarios o reservas.
- **FK compuesta `reservas(horario_id, servicio_id) → horarios(id, servicio_id)`**: una reserva no puede apuntar a un horario de otro servicio.
- **FK compuesta `resenas(reserva_id, usuario_id) → reservas(id, usuario_id)`**: solo el dueño de la reserva puede reseñarla.
- **`UNIQUE` en `resenas.reserva_id`**: una sola reseña por reserva.
- **`UNIQUE (usuario_id, horario_id, activa)`**: un cliente no puede tener dos reservas activas en el mismo horario.
- **`CHECK`**: precio ≥ 0, duración > 0, hora final > hora inicial, cupos entre 0 y el total, calificación de 1 a 5.

## Estado del proyecto

**Revisión preliminar (9 de noviembre):**

- [x] Base de datos con las 7 tablas, relaciones y datos de prueba
- [x] Registro, inicio y cierre de sesión
- [x] Navegación diferenciada para ADMIN y CLIENTE
- [x] CRUD de categorías y servicios
- [x] Catálogo con búsqueda por nombre y filtro por categoría, y detalle del servicio
- [x] Controles de seguridad base: `password_hash`, sesiones seguras, bloqueo tras 5 intentos, PDO preparado, `htmlspecialchars`, CSRF, cabeceras HTTP y validación de rol en PHP

**Pendiente para la entrega final (12 de noviembre):**

| Módulo | Qué falta | Responsable |
|---|---|---|
| Horarios | CRUD de horarios en `admin/` (sin fechas pasadas, hora final > inicial) | |
| Reservas | Reservar con transacción y `UPDATE ... WHERE cupos_disponibles > 0`; cancelar devolviendo un cupo; cambio de estados por el ADMIN | |
| Reseñas | Formulario de reseña para reservas REALIZADA propias | |
| Seguridad y pruebas | Ejecutar las 7 pruebas y documentarlas en `security/SECURITY.md` con capturas | |

## Bibliotecas de terceros

- [Bootstrap 5.3.3](https://getbootstrap.com/) (licencia MIT), incluido localmente en `css/` y `js/`. Se adaptaron los colores en `css/estilos.css`.

## Uso de inteligencia artificial

- Herramienta: Claude (Anthropic).
- Uso: generación de la base del código de la revisión preliminar y de una guía de estudio para comprender cada archivo.
- Verificación: probamos cada función en XAMPP (registro, login, CRUD, permisos y validaciones) antes de entregarla.
