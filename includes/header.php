<?php
// Plantilla superior: se incluye en todas las páginas.
// Antes de incluirla, cada página define $titulo.
$usuario = usuario_actual();
$rol = $usuario['rol'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo ?? NOMBRE_NEGOCIO) ?> | <?= e(NOMBRE_NEGOCIO) ?></title>
  <link rel="stylesheet" href="<?= url('css/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="<?= url('css/estilos.css') ?>">
</head>
<body>

<header>
  <nav class="navbar navbar-expand-lg navbar-dark barra">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url($rol === 'ADMIN' ? 'admin/index.php' : 'index.php') ?>">
        <img src="<?= url('img/logo.svg') ?>" alt="" width="34" height="34">
        <span><?= e(NOMBRE_NEGOCIO) ?></span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
              aria-controls="menu" aria-expanded="false" aria-label="Abrir menú">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="menu">
        <ul class="navbar-nav me-auto">
          <?php if ($rol === 'ADMIN'): ?>
            <!-- Menú del ADMIN -->
            <li class="nav-item"><a class="nav-link" href="<?= url('admin/index.php') ?>">Panel</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('admin/categorias.php') ?>">Categorías</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('admin/servicios.php') ?>">Servicios</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('admin/horarios.php') ?>">Horarios</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('admin/reservas.php') ?>">Reservas</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('catalogo.php') ?>">Ver catálogo</a></li>
          <?php else: ?>
            <!-- Menú público y del CLIENTE -->
            <li class="nav-item"><a class="nav-link" href="<?= url('index.php') ?>">Inicio</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('catalogo.php') ?>">Servicios</a></li>
            <?php if ($rol === 'CLIENTE'): ?>
              <li class="nav-item"><a class="nav-link" href="<?= url('cliente/mis_reservas.php') ?>">Mis reservas</a></li>
            <?php endif; ?>
          <?php endif; ?>
        </ul>

        <ul class="navbar-nav align-items-lg-center gap-lg-2">
          <?php if ($usuario): ?>
            <li class="nav-item navbar-text small">
              <?= e($usuario['nombre']) ?> · <?= $rol === 'ADMIN' ? 'Administrador' : 'Cliente' ?>
            </li>
            <li class="nav-item">
              <!-- Cerrar sesión por POST con token CSRF -->
              <form action="<?= url('logout.php') ?>" method="post" class="d-inline">
                <?= csrf_campo() ?>
                <button type="submit" class="btn btn-sm btn-outline-light">Cerrar sesión</button>
              </form>
            </li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="<?= url('login.php') ?>">Iniciar sesión</a></li>
            <li class="nav-item"><a class="btn btn-sm btn-acento" href="<?= url('registro.php') ?>">Crear cuenta</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
</header>

<main class="container py-4">
  <?php foreach (tomar_flash() as $aviso): ?>
    <div class="alert alert-<?= e($aviso['tipo']) ?>"><?= e($aviso['mensaje']) ?></div>
  <?php endforeach; ?>
