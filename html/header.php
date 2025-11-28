<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<header>
  <div class="topbar">
    <h1>JP Calzados</h1>
    <form method="GET" action="index.php" class="buscador">
      <input type="text" name="buscar" placeholder="Buscar productos.." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
      <button type="submit"><i class="fa fa-search"></i></button>
    </form>
    
    <div class="iconos">
      <a href="#" title="Ver carrito"><i class="fa fa-shopping-cart"></i></a>

      <?php if (isset($_SESSION['usuario'])): ?>
        <span class="user-display">¡Hola, **<?= htmlspecialchars($_SESSION['usuario']) ?>**!</span>
        <a href="login/logout.php" class="user-icon logged-in" title="Cerrar sesión">
          <i class="fa fa-user"></i>
        </a>
      <?php else: ?>
        <a href="login/login.php" class="user-icon logged-out" title="Iniciar sesión">
          <i class="fa fa-user"></i>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <nav class="categorias">
    <a href="#">Zapatillas</a>
    <a href="#">Tacones</a>
    <a href="#">Chanclas</a>
    <a href="#">Botas</a>
    <a href="#">Casuales</a>
  </nav>
</header>