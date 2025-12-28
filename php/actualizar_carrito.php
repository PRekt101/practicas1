<?php
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idProducto = $_POST['id'];
    $accion = $_POST['accion'];

    // Buscar el producto en el carrito de la sesión
    $indiceEncontrado = -1;
    if (isset($_SESSION['carrito'])) {
        foreach ($_SESSION['carrito'] as $indice => $item) {
            if ($item['id'] == $idProducto) {
                $indiceEncontrado = $indice;
                break;
            }
        }
    }

    if ($indiceEncontrado !== -1) {
        if ($accion === 'sumar') {
            // 1. Consultar el stock REAL en la base de datos
            $stmt = $conexion->prepare("SELECT stock FROM producto WHERE idProducto = ?");
            $stmt->execute([$idProducto]);
            $productoDB = $stmt->fetch(PDO::FETCH_ASSOC);

            // 2. Verificar si podemos aumentar
            $cantidadActual = $_SESSION['carrito'][$indiceEncontrado]['cantidad'];
            
            if ($productoDB && $cantidadActual < $productoDB['stock']) {
                $_SESSION['carrito'][$indiceEncontrado]['cantidad']++;
            } else {
                // Guardamos un mensaje de error para mostrarlo
                $_SESSION['mensaje_error'] = "¡No hay más stock disponible de este producto!";
            }
            
        } elseif ($accion === 'restar') {
            // Solo restamos si es mayor que 1
            if ($_SESSION['carrito'][$indiceEncontrado]['cantidad'] > 1) {
                $_SESSION['carrito'][$indiceEncontrado]['cantidad']--;
            }
        }
    }
}

// Volver al carrito
header("Location: ../html/ver_carrito.php");
exit();
?>