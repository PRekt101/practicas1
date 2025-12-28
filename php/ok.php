<?php
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../html/sermepa-master/src/Sermepa/Tpv/Tpv.php';

use Sermepa\Tpv\Tpv;

$key = 'sq7HjrUOBfKmC576ILgskD5srU870gJ7';
$redsys = new Tpv();

$estado  = 'error';
$mensaje = 'Firma no válida';

if ($redsys->check($key, $_GET)) {

    $params   = $redsys->getMerchantParameters($_GET['Ds_MerchantParameters']);
    $response = (int)$params['Ds_Response'];

    if ($response <= 99) {

        try {
            $conexion->beginTransaction();

            // 1️⃣ Obtener el último carrito pendiente del usuario (Y SU PRECIO TOTAL)
            $stmt = $conexion->prepare("
                SELECT idCarrito, precioTotal 
                FROM carrito
                WHERE idUsuario = ?
                  AND estado = 'pendiente'
                ORDER BY idCarrito DESC
                LIMIT 1
            ");
            $stmt->execute([$_SESSION['idUsuario']]);
            $datosCarrito = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$datosCarrito) {
                throw new Exception('No se encontró carrito pendiente');
            }
            
            $idCarrito = $datosCarrito['idCarrito'];
            $precioTotal = $datosCarrito['precioTotal'];

            // 2️⃣ Marcar carrito como pagado
            $stmt = $conexion->prepare("
                UPDATE carrito
                SET estado = 'pagado'
                WHERE idCarrito = ?
            ");
            $stmt->execute([$idCarrito]);

            // 3️⃣ Obtener productos del carrito
            $stmt = $conexion->prepare("
                SELECT idProducto, cantidad
                FROM carritodetalle
                WHERE idCarrito = ?
            ");
            $stmt->execute([$idCarrito]);
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 4️⃣ Restar stock
            $stmtStock = $conexion->prepare("
                UPDATE producto
                SET stock = stock - ?
                WHERE idProducto = ?
                  AND stock >= ?
            ");

            foreach ($detalles as $d) {
                $stmtStock->execute([
                    $d['cantidad'],
                    $d['idProducto'],
                    $d['cantidad']
                ]);

                if ($stmtStock->rowCount() === 0) {
                    throw new Exception("Stock insuficiente");
                }
            }

            // 5️⃣ GENERAR FACTURA (NUEVO CÓDIGO)
            $stmtFactura = $conexion->prepare("
                INSERT INTO factura (idUsuario, idCarrito, fecha, total)
                VALUES (?, ?, NOW(), ?)
            ");
            $stmtFactura->execute([$_SESSION['idUsuario'], $idCarrito, $precioTotal]);

            $conexion->commit();

            unset($_SESSION['carrito']);

            $estado  = 'ok';
            $mensaje = 'Pago realizado correctamente';

        } catch (Exception $e) {
            $conexion->rollBack();

            $estado  = 'error';
            $mensaje = 'Error al procesar el pedido';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Resultado del pago</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f8;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
.box {
    background: #fff;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,.15);
    text-align: center;
    width: 420px;
}
.icon {
    font-size: 60px;
    margin-bottom: 20px;
}
.ok { color: #28a745; }
.ko { color: #dc3545; }
a {
    display: inline-block;
    padding: 12px 25px;
    background: #007bff;
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
    font-weight: bold;
}
a:hover { background: #0056b3; }
</style>
</head>
<body>

<div class="box">
<?php if ($estado === 'ok'): ?>
    <div class="icon ok">✔</div>
    <h1><?= $mensaje ?></h1>
    <p>Gracias por tu compra.</p>
    <a href="/practicas1/index.php">Volver a la tienda</a>

<?php elseif ($estado === 'ko'): ?>
    <div class="icon ko">✖</div>
    <h1><?= $mensaje ?></h1>
    <a href="/practicas1/html/ver_carrito.php">Volver al carrito</a>

<?php else: ?>
    <div class="icon ko">⚠</div>
    <h1>Error</h1>
    <p><?= $mensaje ?></p>
    <a href="/practicas1/index.php">Volver a la tienda</a>
<?php endif; ?>
</div>

</body>
</html>
