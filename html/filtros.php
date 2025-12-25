<?php
// 1. Conexión y Consultas Dinámicas
// Usamos require_once para asegurar que la conexión existe
require_once __DIR__ . '/../php/conexion.php'; 

try {
    // A) Obtener Tallas disponibles (sin repetir y ordenadas)
    $sqlTallas = "SELECT DISTINCT talla FROM producto ORDER BY talla ASC";
    $tallas = $conexion->query($sqlTallas)->fetchAll(PDO::FETCH_COLUMN);

    // B) Obtener Tipos disponibles
    $sqlTipos = "SELECT DISTINCT tipo FROM producto ORDER BY tipo ASC";
    $tipos = $conexion->query($sqlTipos)->fetchAll(PDO::FETCH_COLUMN);

    // C) Obtener Colores disponibles
    $sqlColores = "SELECT DISTINCT color FROM producto ORDER BY color ASC";
    $colores = $conexion->query($sqlColores)->fetchAll(PDO::FETCH_COLUMN);

    // D) Obtener Marcas (Ya lo tenías, pero lo movemos aquí para ordenar)
    $sqlMarcas = "SELECT idMarca, nombre FROM marca ORDER BY nombre ASC";
    $marcas = $conexion->query($sqlMarcas)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si falla la BDD, inicializamos arrays vacíos para que no rompa la página
    $tallas = [];
    $tipos = [];
    $colores = [];
    $marcas = [];
}
?>

<aside class="filtros">
    <h3>Filtros</h3>
    <form method="GET" action="index.php">

        <label for="busqueda">Buscar por Nombre</label>
        <input type="text" name="busqueda" id="busqueda" placeholder="Ej: Nike Mercurial" 
               value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">

        <label for="talla">Tamaño</label>
        <select name="talla" id="talla">
            <option value="">Todos</option>
            <?php foreach ($tallas as $t): ?>
                <option value="<?= $t ?>" <?= (isset($_GET['talla']) && $_GET['talla'] == $t) ? 'selected' : '' ?>>
                    <?= $t ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="tipo">Tipo</label>
        <select name="tipo" id="tipo">
            <option value="">Todos</option>
            <?php foreach ($tipos as $tp): ?>
                <option value="<?= $tp ?>" <?= (isset($_GET['tipo']) && $_GET['tipo'] == $tp) ? 'selected' : '' ?>>
                    <?= ucfirst($tp) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="color">Color</label>
        <select name="color" id="color">
            <option value="">Todos</option>
            <?php foreach ($colores as $c): ?>
                <option value="<?= $c ?>" <?= (isset($_GET['color']) && $_GET['color'] == $c) ? 'selected' : '' ?>>
                    <?= ucfirst($c) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="marca">Marca</label>
        <select name="marca" id="marca">
            <option value="">Todas</option>
            <?php foreach ($marcas as $m): ?>
                <option value="<?= $m['idMarca'] ?>" <?= (isset($_GET['marca']) && $_GET['marca'] == $m['idMarca']) ? 'selected' : '' ?>>
                    <?= $m['nombre'] ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="min_precio">Precio mínimo</label>
        <input type="number" name="min_precio" placeholder="0" 
               value="<?= htmlspecialchars($_GET['min_precio'] ?? '') ?>">

        <label for="max_precio">Precio máximo</label>
        <input type="number" name="max_precio" placeholder="300" 
               value="<?= htmlspecialchars($_GET['max_precio'] ?? '') ?>">

        <div class="botones">
            <button type="submit">Aplicar Filtros</button>
            <a href="index.php" class="limpiar">Limpiar</a>
        </div>
    </form>
</aside>