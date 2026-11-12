-- ============================================================
-- ByteFix Soporte - Book&Go Lite
-- Script de creación de la base de datos y datos de prueba
-- Importar en phpMyAdmin (pestaña Importar) o con:
--   mysql -u root < database/bookgo_lite.sql
-- ============================================================

SET NAMES utf8mb4;

DROP DATABASE IF EXISTS bookgo_lite;
CREATE DATABASE bookgo_lite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bookgo_lite;

-- ------------------------------------------------------------
-- 1. ROLES: ADMIN y CLIENTE
-- ------------------------------------------------------------
CREATE TABLE roles (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. USUARIOS
--    - correo UNIQUE: no se puede registrar dos veces el mismo correo
--    - password_hash guarda el resultado de password_hash(), nunca la clave
-- ------------------------------------------------------------
CREATE TABLE usuarios (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  rol_id         INT NOT NULL,
  nombre         VARCHAR(60) NOT NULL,
  apellido       VARCHAR(60) NOT NULL,
  correo         VARCHAR(120) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  estado         ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. CATEGORIAS
-- ------------------------------------------------------------
CREATE TABLE categorias (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(80) NOT NULL UNIQUE,
  descripcion VARCHAR(255) NOT NULL,
  estado      ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO'
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. SERVICIOS
--    - CHECK: precio no negativo y duración mayor que cero
--    - ON DELETE RESTRICT: no se puede borrar una categoría con servicios
-- ------------------------------------------------------------
CREATE TABLE servicios (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT NOT NULL,
  nombre       VARCHAR(100) NOT NULL,
  descripcion  TEXT NOT NULL,
  precio       DECIMAL(8,2) NOT NULL,
  duracion_min INT NOT NULL,
  estado       ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  CONSTRAINT fk_servicios_categoria FOREIGN KEY (categoria_id)
    REFERENCES categorias(id) ON DELETE RESTRICT,
  CONSTRAINT chk_servicio_precio   CHECK (precio >= 0),
  CONSTRAINT chk_servicio_duracion CHECK (duracion_min > 0)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. HORARIOS
--    - CHECK: hora final mayor que la inicial
--    - CHECK: cupos disponibles entre 0 y el total
--    - UNIQUE (id, servicio_id): permite que reservas valide que el
--      horario pertenece al servicio (ver fk_reservas_horario_servicio)
-- ------------------------------------------------------------
CREATE TABLE horarios (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  servicio_id       INT NOT NULL,
  fecha             DATE NOT NULL,
  hora_inicio       TIME NOT NULL,
  hora_fin          TIME NOT NULL,
  cupos_totales     INT NOT NULL,
  cupos_disponibles INT NOT NULL,
  estado            ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  CONSTRAINT fk_horarios_servicio FOREIGN KEY (servicio_id)
    REFERENCES servicios(id) ON DELETE RESTRICT,
  CONSTRAINT uq_horario_servicio UNIQUE (id, servicio_id),
  CONSTRAINT chk_horario_horas  CHECK (hora_fin > hora_inicio),
  CONSTRAINT chk_horario_total  CHECK (cupos_totales > 0),
  CONSTRAINT chk_horario_cupos  CHECK (cupos_disponibles >= 0 AND cupos_disponibles <= cupos_totales)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. RESERVAS
--    - FK compuesta (horario_id, servicio_id): la base de datos rechaza
--      una reserva cuyo horario no pertenezca al servicio indicado
--    - activa + UNIQUE: un cliente no puede tener dos reservas
--      PENDIENTE/CONFIRMADA en el mismo horario (las canceladas y
--      realizadas quedan en NULL y no cuentan)
--    - UNIQUE (id, usuario_id): permite validar el dueño en resenas
-- ------------------------------------------------------------
CREATE TABLE reservas (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id          INT NOT NULL,
  servicio_id         INT NOT NULL,
  horario_id          INT NOT NULL,
  estado              ENUM('PENDIENTE','CONFIRMADA','REALIZADA','CANCELADA') NOT NULL DEFAULT 'PENDIENTE',
  fecha_registro      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  activa TINYINT GENERATED ALWAYS AS
    (CASE WHEN estado IN ('PENDIENTE','CONFIRMADA') THEN 1 ELSE NULL END) STORED,
  CONSTRAINT fk_reservas_usuario  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)  ON DELETE RESTRICT,
  CONSTRAINT fk_reservas_servicio FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE RESTRICT,
  CONSTRAINT fk_reservas_horario_servicio FOREIGN KEY (horario_id, servicio_id)
    REFERENCES horarios(id, servicio_id) ON DELETE RESTRICT,
  CONSTRAINT uq_reserva_activa  UNIQUE (usuario_id, horario_id, activa),
  CONSTRAINT uq_reserva_usuario UNIQUE (id, usuario_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. RESENAS
--    - reserva_id UNIQUE: una sola reseña por reserva
--    - FK compuesta (reserva_id, usuario_id): solo el dueño de la
--      reserva puede reseñarla
--    - CHECK: calificación entre 1 y 5
-- ------------------------------------------------------------
CREATE TABLE resenas (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  reserva_id   INT NOT NULL UNIQUE,
  usuario_id   INT NOT NULL,
  calificacion TINYINT NOT NULL,
  comentario   TEXT NOT NULL,
  fecha        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_resenas_reserva_usuario FOREIGN KEY (reserva_id, usuario_id)
    REFERENCES reservas(id, usuario_id) ON DELETE RESTRICT,
  CONSTRAINT chk_resena_calificacion CHECK (calificacion BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabla de apoyo para seguridad: intentos fallidos de login.
-- Se cuenta por correo y por IP, exista o no el correo, para no
-- revelar qué correos están registrados.
-- ------------------------------------------------------------
CREATE TABLE intentos_login (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  correo VARCHAR(120) NOT NULL,
  ip     VARCHAR(45) NOT NULL,
  fecha  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_intentos_correo (correo, fecha),
  INDEX idx_intentos_ip (ip, fecha)
) ENGINE=InnoDB;


-- ============================================================
-- DATOS DE PRUEBA
-- No se incluyen usuarios con contraseña: el ADMIN se crea con
-- instalar.php y los clientes desde registro.php.
-- ============================================================

INSERT INTO roles (id, nombre) VALUES (1, 'ADMIN'), (2, 'CLIENTE');

INSERT INTO categorias (id, nombre, descripcion) VALUES
(1, 'Reparación de equipos', 'Diagnóstico y reparación de laptops, computadoras de escritorio y componentes.'),
(2, 'Software y sistema',    'Instalación, optimización y limpieza de sistemas operativos y programas.'),
(3, 'Redes y seguridad',     'Configuración de redes domésticas, Wi-Fi y protección de tus equipos.');

INSERT INTO servicios (id, categoria_id, nombre, descripcion, precio, duracion_min) VALUES
(1, 1, 'Diagnóstico general de equipo',  'Revisamos hardware y software para identificar la falla y te entregamos un informe con el presupuesto.', 15.00, 30),
(2, 1, 'Limpieza interna y cambio de pasta térmica', 'Limpieza de polvo, ventiladores y disipador, con cambio de pasta térmica para reducir la temperatura.', 30.00, 60),
(3, 1, 'Cambio de disco a SSD',          'Instalamos un SSD y clonamos tu sistema para que tu equipo arranque y trabaje más rápido.', 45.00, 90),
(4, 2, 'Instalación de Windows',         'Instalación limpia de Windows con controladores, actualizaciones y programas básicos.', 35.00, 120),
(5, 2, 'Eliminación de virus y malware', 'Análisis completo, eliminación de amenazas y recomendaciones para evitar nuevas infecciones.', 25.00, 60),
(6, 3, 'Configuración de router y Wi-Fi','Configuramos tu router, cambiamos claves por defecto y optimizamos la cobertura Wi-Fi.', 20.00, 45),
(7, 3, 'Asesoría de seguridad para el hogar', 'Revisión de contraseñas, copias de seguridad y autenticación en dos pasos en tus cuentas.', 25.00, 60);

-- Horarios en fechas futuras relativas al día de importación
INSERT INTO horarios (servicio_id, fecha, hora_inicio, hora_fin, cupos_totales, cupos_disponibles) VALUES
(1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '09:00', '09:30', 2, 2),
(1, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '14:00', '14:30', 2, 2),
(2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '10:00', '11:00', 1, 1),
(3, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '09:00', '10:30', 1, 1),
(4, DATE_ADD(CURDATE(), INTERVAL 6 DAY), '13:00', '15:00', 1, 1),
(5, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '16:00', '17:00', 2, 2),
(6, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '10:00', '10:45', 3, 3),
(7, DATE_ADD(CURDATE(), INTERVAL 8 DAY), '18:00', '19:00', 4, 4);
