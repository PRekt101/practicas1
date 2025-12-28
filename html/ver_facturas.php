<?php
session_start();
require_once '../php/conexion.php';

if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../login/login.php");
    exit();
}

$idUsuario = $_SESSION['idUsuario'];

try {
    // Ahora seleccionamos de la tabla FACTURA
    $sql = "SELECT idFactura, idCarrito, fecha, total 
            FROM factura 
            WHERE idUsuario = :idUser 
            ORDER BY idFactura DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute([':idUser' => $idUsuario]);
    $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error al recuperar las facturas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Facturas - JP Calzados</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .facturas-container { max-width: 900px; margin: 40px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .tabla-facturas { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .tabla-facturas th, .tabla-facturas td { padding: 15px; text-align: center; border-bottom: 1px solid #eee; }
        .tabla-facturas th { background-color: #f8f8f8; }
        .btn-detalle { color: #d40000; text-decoration: none; font-weight: bold; }
        .estado-ok { color: green; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="contenedor">
        <main class="facturas-container">
            <h2><i class="fa fa-file-invoice"></i> Mis Compras</h2>

            <?php if (isset($error)): ?>
                <p style="color:red;"><?= $error ?></p>
            <?php elseif (empty($facturas)): ?>
                <p style="text-align:center;">No tienes compras registradas todavía.</p>
            <?php else: ?>
                <table class="tabla-facturas">
                    <thead>
                        <tr>
                            <th>Nº Pedido</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($facturas as $f): ?>
                            <tr>
                                <td>#<?= $f['idFactura'] ?></td>
                                
                                <td><?= date("d/m/Y H:i", strtotime($f['fecha'])) ?></td>
                                
                                <td><strong>€ <?= number_format($f['total'], 2) ?></strong></td>
                                
                                <td><span class="estado-ok">Pagado</span></td>
                                
                                <td>
                                    <a href="detalle_factura.php?id=<?= $f['idCarrito'] ?>" class="btn-detalle">
                                        <i class="fa fa-eye"></i> Ver detalle
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>