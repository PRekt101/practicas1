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
    $order    = ltrim($params['Ds_Order'], '0'); // idCarrito real

    if ($response <= 99) {

    try {
        $conexion->beginTransaction();

        // 1️⃣ Marcar carrito como pagado
        $stmt = $conexion->prepare("
            UPDATE carrito
            SET estado = 'pagado'
            WHERE idCarrito = ?
        ");
        $stmt->execute([$order]);

        // 2️⃣ Obtener todos los productos comprados en este carrito
        $stmt = $conexion->prepare("
            SELECT idProducto, cantidad
            FROM carritodetalle
            WHERE idCarrito = ?
        ");
        $stmt->execute([$order]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3️⃣ Restar stock (una fila por cada línea del carrito)
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

            // Si no se ha actualizado → stock insuficiente
            if ($stmtStock->rowCount() === 0) {
                throw new Exception("Stock insuficiente para el producto ID {$d['idProducto']}");
            }
        }

        // 4️⃣ Confirmar todo
        $conexion->commit();

        // Limpiar sesión
        unset($_SESSION['carrito']);

        $estado  = 'ok';
        $mensaje = 'Pago realizado correctamente';

    } catch (Exception $e) {
        $conexion->rollBack();

        $estado  = 'error';
        $mensaje = 'Error al procesar el pedido: ' . $e->getMessage();
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
    <a href="/comercio/practicas1/index.php">Volver a la tienda</a>

<?php elseif ($estado === 'ko'): ?>
    <div class="icon ko">✖</div>
    <h1><?= $mensaje ?></h1>
    <a href="/comercio/practicas1/html/ver_carrito.php">Volver al carrito</a>

<?php else: ?>
    <div class="icon ko">⚠</div>
    <h1>Error</h1>
    <p><?= $mensaje ?></p>
    <a href="/comercio/practicas1/index.php">Volver a la tienda</a>
<?php endif; ?>
</div>

</body>
</html>
