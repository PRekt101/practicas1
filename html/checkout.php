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
    // Iniciamos la transacción (todo o nada)
    $conexion->beginTransaction();

    // A. Calcular el total
    $totalCompra = 0;
    foreach ($_SESSION['carrito'] as $item) {
        $totalCompra += ($item['precio'] * $item['cantidad']);
    }

    // B. Insertar en tabla CARRITO (Cabecera del pedido)
    // Según tu foto: idCarrito, idUsuario, precioTotal, fechaCreacion
    $sqlCabecera = "INSERT INTO carrito (idUsuario, precioTotal, fechaCreacion) VALUES (?, ?, NOW())";
    $stmt = $conexion->prepare($sqlCabecera);
    $stmt->execute([$idUsuario, $totalCompra]);
    
    // Obtener el ID del carrito que acabamos de crear
    $idCarritoGenerado = $conexion->lastInsertId();

    // C. Insertar en tabla DETALLE_CARRITO (Productos individuales)
    // Según tu foto: idDetalle, idCarrito, idProducto, precioUnitario, cantidad
    // Nota: He añadido 'talla' al SQL asumiendo que hiciste el Paso 0.
    $sqlDetalle = "INSERT INTO carritodetalle (idCarrito, idProducto, precioUnitario, cantidad, talla) VALUES (?, ?, ?, ?, ?)";
    $stmtDetalle = $conexion->prepare($sqlDetalle);

    foreach ($_SESSION['carrito'] as $prod) {
        $stmtDetalle->execute([
            $idCarritoGenerado,
            $prod['id'],
            $prod['precio'],
            $prod['cantidad'],
            $prod['talla']
        ]);
    }

    // D. Confirmar transacción
    $conexion->commit();

    // E. Vaciar el carrito de la sesión y mostrar éxito
    unset($_SESSION['carrito']);
    $mensaje = "¡Gracias por tu compra! Tu pedido #$idCarritoGenerado ha sido registrado.";
    $tipoMensaje = "exito";

} catch (Exception $e) {
    // Si algo falla, revertimos los cambios en la BD
    $conexion->rollBack();
    $mensaje = "Hubo un error al procesar tu pedido: " . $e->getMessage();
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