<?php
require_once 'conexion.php';

// Inicializamos filtros
$filtros = [];
$parametros = [];

// 1. Filtros (Igual que antes, pero asegurando usar alias 'p')
if (!empty($_GET['busqueda'])) {
    $filtros[] = "p.nombre LIKE :busqueda";
    $parametros[':busqueda'] = "%" . $_GET['busqueda'] . "%";
}
if (!empty($_GET['marca'])) {
    $filtros[] = "p.idMarca = :marca";
    $parametros[':marca'] = $_GET['marca'];
}
if (!empty($_GET['tipo'])) {
    $filtros[] = "p.tipo = :tipo";
    $parametros[':tipo'] = $_GET['tipo'];
}
// El filtro de talla ahora busca si "alguna" de las variantes tiene esa talla
if (!empty($_GET['talla'])) {
    $filtros[] = "p.talla = :talla";
    $parametros[':talla'] = $_GET['talla'];
}
if (!empty($_GET['color'])) {
    $filtros[] = "p.color = :color";
    $parametros[':color'] = $_GET['color'];
}
if (!empty($_GET['min_precio'])) {
    $filtros[] = "p.precio >= :min_precio";
    $parametros[':min_precio'] = $_GET['min_precio'];
}
if (!empty($_GET['max_precio'])) {
    $filtros[] = "p.precio <= :max_precio";
    $parametros[':max_precio'] = $_GET['max_precio'];
}

// Filtro de Stock > 0 (Para que el modelo aparezca si al menos UNA talla tiene stock)
// Nota: Lo gestionaremos mejor con el HAVING SUM(stock) > 0 si quisieras ocultar agotados totales,
// pero por ahora mantenemos el filtro básico.
$filtros[] = "p.stock > 0";

// 2. Consulta SQL con GROUP BY
// Seleccionamos el ID min, el Precio min y max, y sumamos el stock total del modelo
$sql = "
    SELECT 
        MIN(p.idProducto) as idProducto, 
        p.nombre,
        p.color,
        p.tipo,
        MIN(p.precio) as precioMin,
        MAX(p.precio) as precioMax,
        SUM(p.stock) as stockTotal,
        m.nombre AS marcaNombre
    FROM producto p
    INNER JOIN marca m ON p.idMarca = m.idMarca
";

if (!empty($filtros)) {
    $sql .= " WHERE " . implode(" AND ", $filtros);
}

// AGUPAMOS POR NOMBRE (y color/marca para evitar mezclar modelos distintos con mismo nombre)
$sql .= " GROUP BY p.nombre, p.color, p.tipo, m.nombre";

$sql .= " ORDER BY p.nombre ASC";

try {
    $stmt = $conexion->prepare($sql);
    $stmt->execute($parametros);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al obtener productos: " . $e->getMessage());
}
?>