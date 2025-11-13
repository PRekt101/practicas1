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
      <i class="fa fa-shopping-cart"></i>

      <?php if (isset($_SESSION['usuario'])): ?>
        <!-- Si está logueado, el icono hace logout -->
        <a href="login/logout.php"><i class="fa fa-user" title="Cerrar sesión"></i></a>
      <?php else: ?>
        <!-- Si no está logueado, el icono lleva al login -->
        <a href="login/login.php"><i class="fa fa-user" title="Iniciar sesión"></i></a>
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
