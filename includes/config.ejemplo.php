<?php
// ============================================================
// Copia este archivo como config.php y ajusta los valores.
// config.php NO se sube a GitHub (está en .gitignore) porque
// contiene las credenciales de la base de datos.
// ============================================================

// Datos de conexión a MySQL (en XAMPP: usuario root sin contraseña)
define('DB_HOST', 'localhost');
define('DB_NOMBRE', 'bookgo_lite');
define('DB_USUARIO', 'root');
define('DB_CLAVE', '');

// Carpeta del proyecto dentro de htdocs, tal como aparece en la URL.
// Ejemplo: http://localhost/bookgo-lite/  ->  '/bookgo-lite'
define('BASE_URL', '/bookgo-lite');

// Nombre comercial que se muestra en la interfaz
define('NOMBRE_NEGOCIO', 'ByteFix Soporte');
