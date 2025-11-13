<?php
require_once 'conexion.php';

// Inicializamos array de filtros dinámicos
$filtros = [];
$parametros = [];

// Comprobamos si hay filtros enviados por GET
if (!empty($_GET['marca'])) {
    $filtros[] = "p.marca_id = :marca";
    $parametros[':marca'] = $_GET['marca'];
}

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

// Construimos la consulta SQL base
$sql = "
    SELECT 
        p.idProducto,
        p.nombre,
        p.color,
        p.talla,
        p.precio,
        m.nombre AS marcaNombre
    FROM producto p
    INNER JOIN marca m ON p.idMarca = m.idMarca
";

// Añadimos los filtros dinámicos
if (!empty($filtros)) {
    $sql .= " WHERE " . implode(" AND ", $filtros);
}

// Orden
$sql .= " ORDER BY p.nombre ASC";

try {
    $stmt = $conexion->prepare($sql);
    $stmt->execute($parametros);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener productos: " . $e->getMessage());
}
?>
