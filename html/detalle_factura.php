<?php
session_start();
require_once '../php/conexion.php';

if (!isset($_SESSION['idUsuario']) || !isset($_GET['id'])) {
    header("Location: ver_facturas.php");
    exit();
}

$idCarrito = intval($_GET['id']);
$idUsuario = $_SESSION['idUsuario'];

try {
    // Seguridad: Verificar que el carrito pertenece al usuario
    $check = $conexion->prepare("SELECT idCarrito FROM carrito WHERE idCarrito = ? AND idUsuario = ?");
    $check->execute([$idCarrito, $idUsuario]);
    
    if (!$check->fetch()) {
        die("No tienes permiso para ver esta factura.");
    }

    // Consulta de los productos comprados
    // Asegúrate de que en carritodetalle la columna se llame idProducto y cantidad
    $sql = "SELECT cd.*, p.nombre 
            FROM carritodetalle cd
            JOIN producto p ON cd.idProducto = p.idProducto
            WHERE cd.idCarrito = :idCarrito";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute([':idCarrito' => $idCarrito]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener el total real desde precioTotal
    $stmt2 = $conexion->prepare("SELECT precioTotal FROM carrito WHERE idCarrito = ?");
    $stmt2->execute([$idCarrito]);
    $resumen = $stmt2->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Pedido #<?= $idCarrito ?></title>
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .detalle-box { max-width: 700px; margin: 40px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .tabla-productos { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .tabla-productos th, .tabla-productos td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .total-final { text-align: right; font-size: 1.3rem; margin-top: 20px; color: #d40000; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="contenedor">
        <div class="detalle-box">
            <h2>Detalles del Pedido #<?= $idCarrito ?></h2>
            
            <table class="tabla-productos">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unit.</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nombre']) ?></td>
                            <td><?= $item['cantidad'] ?></td>
                            <td>€ <?= number_format($item['precioUnitario'], 2) ?></td>
                            <td>€ <?= number_format($item['cantidad'] * $item['precioUnitario'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-final">
                <strong>Total: € <?= number_format($resumen['precioTotal'], 2) ?></strong>
            </div>

            <a href="ver_facturas.php" style="display:inline-block; margin-top:20px; color:#666; text-decoration:none;">
                <i class="fa fa-arrow-left"></i> Volver al listado
            </a>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>