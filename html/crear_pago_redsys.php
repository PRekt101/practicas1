<?php
session_start();
require_once __DIR__ . '/../php/conexion.php';
$pdo = $conexion;
require_once __DIR__ . '/sermepa-master/src/Sermepa/Tpv/Tpv.php';

use Sermepa\Tpv\Tpv;

$carrito = $_SESSION['carrito'] ?? [];

if (empty($carrito)) {
    header("Location: ver_carrito.php");
    exit;
}

/* ===========================
   1️⃣ Calcular total (EUROS)
   =========================== */
$total = 0;
foreach ($carrito as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

/* ===========================
   2️⃣ Crear carrito en BD
   =========================== */
$stmt = $pdo->prepare("
    INSERT INTO carrito (idUsuario, precioTotal, fechaCreacion, estado)
    VALUES (?, ?, NOW(), 'pendiente')
");
$stmt->execute([
    $_SESSION['idUsuario'] ?? 1, // ajusta si usas login
    $total
]);

$idCarrito = $pdo->lastInsertId();

/* ===========================
   3️⃣ Insertar detalles
   =========================== */
$stmtDetalle = $pdo->prepare("
    INSERT INTO carritodetalle (idCarrito, idProducto, precioUnitario, cantidad, talla)
    VALUES (?, ?, ?, ?, ?)
");

foreach ($carrito as $item) {
    $stmtDetalle->execute([
        $idCarrito,
        $item['id'],
        $item['precio'],
        $item['cantidad'],
        $item['talla']
    ]);
}

/* ===========================
   4️⃣ Preparar Redsys
   =========================== */
$key          = 'sq7HjrUOBfKmC576ILgskD5srU870gJ7';
$merchantCode = '999008881';
$terminal     = '1';

/**
 * Ds_Order: 4–12 caracteres numéricos
 * Usamos el idCarrito con padding
 */
$order = str_pad($idCarrito, 4, '0', STR_PAD_LEFT) . date('His');
$amount = intval(round($total)); // CÉNTIMOS

try {
    $redsys = new Tpv();

    $redsys->setAmount($amount);
    $redsys->setOrder($order);
    $redsys->setMerchantcode($merchantCode);
    $redsys->setCurrency('978');
    $redsys->setTransactiontype('0');
    $redsys->setTerminal($terminal);

    $redsys->setNotification("http://localhost/comercio/practicas1/php/notificacion.php");
    $redsys->setUrlOk("http://localhost/comercio/practicas1/php/ok.php");
    $redsys->setUrlKo("http://localhost/comercio/practicas1/php/ko.php");

    $redsys->setVersion('HMAC_SHA256_V1');
    $redsys->setTradeName('Mi Tienda');
    $redsys->setProductDescription('Compra en tienda de zapatillas');
    $redsys->setTitular('Cliente');
    $redsys->setEnvironment('test');

    $signature = $redsys->generateMerchantSignature($key);
    $redsys->setMerchantSignature($signature);

    $form = $redsys->createForm();

} catch (Exception $e) {
    die("Error en Redsys: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Procesando pago</title>
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
.loader {
    border: 6px solid #eee;
    border-top: 6px solid #007bff;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    margin: 0 auto 20px;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
</head>
<body>

<div class="box">
    <div class="loader"></div>
    <h1>Redirigiendo al pago seguro</h1>
    <p>Importe: <strong><?= number_format($total, 2) ?> €</strong></p>
    <p>No cierres esta ventana.</p>

    <div style="display:none">
        <?= $form ?>
    </div>
</div>

<script>
    document.querySelector('form').submit();
</script>

</body>
</html>
