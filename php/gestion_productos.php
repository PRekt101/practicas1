<?php
session_start();
require_once 'conexion.php';

// Seguridad: Solo admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'admin') {
    die("Acceso denegado.");
}

// ACCIÓN: BORRAR PRODUCTO
if (isset($_GET['accion']) && $_GET['accion'] == 'borrar' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conexion->prepare("DELETE FROM producto WHERE idProducto = ?");
    $stmt->execute([$id]);
    
    header("Location: ../html/panel_admin.php");
    exit();
}

// ACCIÓN: CREAR PRODUCTO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'crear') {
    
    $nombre = $_POST['nombre'];
    $marca  = $_POST['idMarca'];
    $tipo   = $_POST['tipo'];
    $color  = $_POST['color'];
    $talla  = $_POST['talla'];
    $stock  = $_POST['stock'];  
    $precio = $_POST['precio'];

    // 2. Actualizamos la consulta SQL añadiendo la columna 'stock'
    $sql = "INSERT INTO producto (nombre, idMarca, tipo, color, talla, stock, precio) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    
    // 3. Añadimos $stock al array de ejecución
    if ($stmt->execute([$nombre, $marca, $tipo, $color, $talla, $stock, $precio])) {
        
        // Procesar Imagen (Igual que antes)
        $nombreLimpio = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombre);
        $infoArchivo = pathinfo($_FILES['imagen']['name']);
        $extension = strtolower($infoArchivo['extension']);
        $rutaDestino = "../imagenes/" . $nombreLimpio . "." . $extension;
        
        move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino);
        
        header("Location: ../html/panel_admin.php?status=ok");
    } else {
        echo "Error al guardar en la base de datos.";
    }
}
?>