<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- LÓGICA DE RUTAS ---
// Detectamos si estamos en la raíz (donde está index.php) o dentro de la carpeta html
// Si existe la carpeta 'html' en el directorio actual, estamos en la raíz.
$en_raiz = is_dir('html'); 

// Ajustamos las rutas dependiendo de dónde estemos
$ruta_inicio  = $en_raiz ? 'index.php'            : '../index.php';
$ruta_carrito = $en_raiz ? 'html/ver_carrito.php' : 'ver_carrito.php';
$ruta_login   = $en_raiz ? 'login/login.php'      : '../login/login.php';
$ruta_logout  = $en_raiz ? 'login/logout.php'     : '../login/logout.php';

// --- CONTADOR DE PRODUCTOS ---
$cantidad_items = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cantidad_items += $item['cantidad'];
    }
}
?>

<header>
  <div class="topbar">
    <h1><a href="<?= $ruta_inicio ?>" style="text-decoration: none; color: inherit;">JP Calzados</a></h1>
    
    <form method="GET" action="<?= $ruta_inicio ?>" class="buscador">
      <input type="text" 
             name="busqueda" 
             placeholder="Buscar productos.." 
             value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
      <button type="submit"><i class="fa fa-search"></i></button>
    </form>
    
    <div class="iconos">
      
      <a href="<?= $ruta_carrito ?>" title="Ver carrito" style="position: relative;">
        <i class="fa fa-shopping-cart"></i>
        <?php if ($cantidad_items > 0): ?>
            <span style="
                position: absolute;
                top: -8px;
                right: -10px;
                background-color: red;
                color: white;
                border-radius: 50%;
                padding: 2px 6px;
                font-size: 0.7rem;
                font-weight: bold;
            ">
                <?= $cantidad_items ?>
            </span>
        <?php endif; ?>
      </a>

      <?php 
      // Definimos la ruta correcta dependiendo de si estamos en inicio o no
      $ruta_admin = $en_raiz ? 'html/panel_admin.php' : 'panel_admin.php'; 
      ?>

      <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
          <a href="<?= $ruta_admin ?>" title="Panel de Administración" style="color: #FFD700; margin-right: 15px;">
              <i class="fa fa-cogs"></i>
          </a>
      <?php endif; ?>

      <?php if (isset($_SESSION['usuario'])): ?>
        <span class="user-display">¡Hola, <strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong>!</span>
        <a href="<?= $ruta_logout ?>" class="user-icon logged-in" title="Cerrar sesión">
          <i class="fa fa-user"></i>
        </a>
      <?php else: ?>
        <a href="<?= $ruta_login ?>" class="user-icon logged-out" title="Iniciar sesión">
          <i class="fa fa-user"></i>
        </a>
      <?php endif; ?>
    </div>
  </div>

</header>