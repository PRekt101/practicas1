<?php
session_start();
require_once 'conexion.php';

// Seguridad: Solo admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'admin') {
    die("Acceso denegado.");
}

// --- 1. ACCIÓN: BORRAR PRODUCTO ---
if (isset($_GET['accion']) && $_GET['accion'] == 'borrar' && isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // PASO 1 (NUEVO): Borrar las referencias en 'carritodetalle' primero
        // Esto evita el error 1451. Eliminamos el producto de cualquier carrito (histórico o actual).
        $stmtDetalle = $conexion->prepare("DELETE FROM carritodetalle WHERE idProducto = ?");
        $stmtDetalle->execute([$id]);

        // PASO 2: Ahora sí, borramos el producto
        $stmt = $conexion->prepare("DELETE FROM producto WHERE idProducto = ?");
        $stmt->execute([$id]);
        
        // Opcional: Borrar la imagen física si quisieras
        
        header("Location: ../html/panel_admin.php");
        exit();

    } catch (PDOException $e) {
        // Si hay otro error, lo mostramos de forma legible
        die("Error al eliminar el producto: " . $e->getMessage());
    }
}

// --- 2. ACCIÓN: CREAR O EDITAR ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    
    $nombre = $_POST['nombre'];
    $marca  = $_POST['idMarca'];
    $tipo   = $_POST['tipo'];
    $color  = $_POST['color'];
    $talla  = $_POST['talla'];
    $stock  = $_POST['stock'];
    $precio = $_POST['precio'];

    // Lógica para manejar la subida de imagen
    $subirImagen = false;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $nombreLimpio = preg_replace('/[^A-Za-z0-9_\-]/', '_', $nombre);
        $infoArchivo = pathinfo($_FILES['imagen']['name']);
        $extension = strtolower($infoArchivo['extension']);
        $rutaDestino = "../imagenes/" . $nombreLimpio . "." . $extension;
        
        move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino);
        $subirImagen = true;
    }

    // A) CASO EDITAR
    if ($_POST['accion'] == 'editar') {
        $idProducto = $_POST['idProducto'];
        
        $sql = "UPDATE producto SET nombre=?, idMarca=?, tipo=?, color=?, talla=?, stock=?, precio=? WHERE idProducto=?";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$nombre, $marca, $tipo, $color, $talla, $stock, $precio, $idProducto]);
        
        header("Location: ../html/panel_admin.php?status=updated");
    
    // B) CASO CREAR
    } elseif ($_POST['accion'] == 'crear') {
        
        $sql = "INSERT INTO producto (nombre, idMarca, tipo, color, talla, stock, precio) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$nombre, $marca, $tipo, $color, $talla, $stock, $precio]);
        
        header("Location: ../html/panel_admin.php?status=ok");
    }
}
?>