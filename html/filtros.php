<aside class="filtros">
  <h3>Filtros</h3>
  <form method="GET" action="index.php">

    <label for="busqueda">Buscar por Nombre</label>
    <input type="text" name="busqueda" id="busqueda" placeholder="Ej: Bota de piel" value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
    <label for="talla">Tamaño</label>
    <select name="talla" id="talla">
      <option value="">Todos</option>
      <?php for ($i = 35; $i <= 45; $i++): ?>
        <option value="<?= $i ?>" <?= (isset($_GET['talla']) && $_GET['talla'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
      <?php endfor; ?>
    </select>

    <label for="talla">Tamaño</label>
    <select name="talla" id="talla">
      <option value="">Todos</option>
      <?php for ($i = 35; $i <= 45; $i++): ?>
        <option value="<?= $i ?>" <?= (isset($_GET['talla']) && $_GET['talla'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
      <?php endfor; ?>
    </select>

    <label for="tipo">Tipo</label>
    <select name="tipo" id="tipo">
      <option value="">Todos</option>
      <option value="Zapatillas" <?= (isset($_GET['tipo']) && $_GET['tipo'] == 'Zapatillas') ? 'selected' : '' ?>>Zapatillas</option>
      <option value="Tacones" <?= (isset($_GET['tipo']) && $_GET['tipo'] == 'Tacones') ? 'selected' : '' ?>>Tacones</option>
      <option value="Botas" <?= (isset($_GET['tipo']) && $_GET['tipo'] == 'Botas') ? 'selected' : '' ?>>Botas</option>
      <option value="chanclas" <?= (isset($_GET['tipo']) && $_GET['tipo'] == 'chanclas') ? 'selected' : '' ?>>chanclas</option>

    </select>

    <label for="color">Color</label>
    <select name="color" id="color">
      <option value="">Todos</option>
      <option value="Blanco" <?= (isset($_GET['color']) && $_GET['color'] == 'Blanco') ? 'selected' : '' ?>>Blanco</option>
      <option value="Negro" <?= (isset($_GET['color']) && $_GET['color'] == 'Negro') ? 'selected' : '' ?>>Negro</option>
      <option value="Rojo" <?= (isset($_GET['color']) && $_GET['color'] == 'Rojo') ? 'selected' : '' ?>>Rojo</option>
      <option value="Azul" <?= (isset($_GET['color']) && $_GET['color'] == 'Azul') ? 'selected' : '' ?>>Azul</option>
      <option value="Verde" <?= (isset($_GET['color']) && $_GET['color'] == 'Verde') ? 'selected' : '' ?>>Azul</option>
    </select>

    <label for="marca">Marca</label>
    <select name="marca" id="marca">
      <option value="">Todas</option>
      <?php
      require_once 'php/conexion.php';
      $marcas = $conexion->query("SELECT idMarca, nombre FROM marca")->fetchAll(PDO::FETCH_ASSOC);
      foreach ($marcas as $m):
      ?>
        <option value="<?= $m['idMarca'] ?>" <?= (isset($_GET['marca']) && $_GET['marca'] == $m['idMarca']) ? 'selected' : '' ?>><?= $m['nombre'] ?></option>
      <?php endforeach; ?>
    </select>

    <label for="min_precio">Precio mínimo</label>
    <input type="number" name="min_precio" placeholder="mín" value="<?= htmlspecialchars($_GET['min_precio'] ?? '') ?>">

    <label for="max_precio">Precio máximo</label>
    <input type="number" name="max_precio" placeholder="máx" value="<?= htmlspecialchars($_GET['max_precio'] ?? '') ?>">

    <div class="botones">
      <button type="submit">Aplicar</button>
      <a href="index.php" class="limpiar">Limpiar</a>
    </div>
  </form>
</aside>
