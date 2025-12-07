<?php
session_start();
require_once '../php/conexion.php';

// 1. VERIFICACIONES DE SEGURIDAD
// Si el usuario no está logueado, lo mandamos al login
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login/login.php");
    exit;
}

// Si el carrito está vacío, lo mandamos al inicio
if (empty($_SESSION['carrito'])) {
    header("Location: ../index.php");
    exit;
}

// IMPORTANTE: Asumo que en tu login.php guardaste el ID del usuario en la sesión.
// Si solo guardaste el nombre, necesitarás ajustar esto.
// Por ejemplo: $idUsuario = $_SESSION['idUsuario']; 
// Si no tienes el ID en sesión, tendrías que buscarlo con una query usando el nombre.
// Para este ejemplo, simularemos que lo tenemos o lo buscamos:

$idUsuario = 0;
if (isset($_SESSION['idUsuario'])) {
    $idUsuario = $_SESSION['idUsuario'];
} else {
    // FALLBACK: Si no guardaste el ID al loguear, lo buscamos por el nombre
    // Asegúrate de que tu tabla de usuarios se llame 'usuarios' o ajusta esta línea
    $stmtUser = $conexion->prepare("SELECT idUsuario FROM usuario WHERE nombre = ?"); 
    $stmtUser->execute([$_SESSION['usuario']]);
    $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if ($userRow) {
        $idUsuario = $userRow['idUsuario'];
    } else {
        die("Error: No se puede identificar al usuario. Revisa tu script de login.");
    }
}

$mensaje = "";
$tipoMensaje = ""; // 'exito' o 'error'

// 2. PROCESAR LA COMPRA
try {
    $conexion->beginTransaction();

    // ... (Tu código de calcular total e insertar en CARRITO sigue igual) ...
    // A. Calcular total
    $totalCompra = 0;
    foreach ($_SESSION['carrito'] as $item) {
        $totalCompra += ($item['precio'] * $item['cantidad']);
    }

    // B. Insertar Cabecera
    $sqlCabecera = "INSERT INTO carrito (idUsuario, precioTotal, fechaCreacion) VALUES (?, ?, NOW())";
    $stmt = $conexion->prepare($sqlCabecera);
    $stmt->execute([$idUsuario, $totalCompra]);
    $idCarritoGenerado = $conexion->lastInsertId();

    // Preparamos consultas
    $sqlDetalle = "INSERT INTO carritodetalle (idCarrito, idProducto, precioUnitario, cantidad, talla) VALUES (?, ?, ?, ?, ?)";
    $stmtDetalle = $conexion->prepare($sqlDetalle);

    $sqlStock = "UPDATE producto SET stock = stock - ? WHERE idProducto = ?";
    $stmtStock = $conexion->prepare($sqlStock);

    // Consulta para VERIFICAR stock antes de restar
    $sqlCheck = "SELECT stock, nombre FROM producto WHERE idProducto = ?";
    $stmtCheck = $conexion->prepare($sqlCheck);

    // E. Recorrer productos
    foreach ($_SESSION['carrito'] as $prod) {
        
        // 1. ¡SEGURIDAD! Verificamos si hay stock suficiente en la BBDD
        $stmtCheck->execute([$prod['id']]);
        $productoActual = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$productoActual || $productoActual['stock'] < $prod['cantidad']) {
            // Si no hay stock, lanzamos un error y se cancela TODO (gracias al rollback)
            throw new Exception("Lo sentimos, el producto '" . $prod['nombre'] . "' se acaba de agotar.");
        }

        // 2. Si hay stock, procedemos a guardar detalle
        $stmtDetalle->execute([
            $idCarritoGenerado,
            $prod['id'],
            $prod['precio'],
            $prod['cantidad'],
            $prod['talla']
        ]);

        // 3. Restar el stock
        $stmtStock->execute([
            $prod['cantidad'],
            $prod['id']
        ]);
    }

    $conexion->commit();
    unset($_SESSION['carrito']);
    $mensaje = "¡Gracias por tu compra! Tu pedido #$idCarritoGenerado ha sido registrado.";
    $tipoMensaje = "exito";

} catch (Exception $e) {
    $conexion->rollBack();
    // Mostramos el mensaje de error (ej: "se acaba de agotar")
    $mensaje = "Error en la compra: " . $e->getMessage();
    $tipoMensaje = "error";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Finalizar Compra - JP Calzados</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="contenedor">
        <?php if ($tipoMensaje === 'exito'): ?>
            <div class="mensaje-compra exito">
                <i class="fas fa-check-circle icono-mensaje"></i>
                <h2>¡Compra realizada con éxito!</h2>
                <p><?= $mensaje ?></p>
                <p>En breve prepararemos tus zapatos.</p>
                <a href="../index.php" class="btn-inicio">Volver a la tienda</a>
            </div>
        <?php else: ?>
            <div class="mensaje-compra error">
                <i class="fas fa-times-circle icono-mensaje"></i>
                <h2>Ups, algo salió mal</h2>
                <p><?= $mensaje ?></p>
                <a href="ver_carrito.php" class="btn-inicio">Volver al carrito</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>