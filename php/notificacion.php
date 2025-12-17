<?php
require_once __DIR__ . '/../html/sermepa-master/src/Sermepa/Tpv/Tpv.php';
use Sermepa\Tpv\Tpv;

$key = 'sq7HjrUOBfKmC576ILgskD5srU870gJ7';

try {
    $redsys = new Tpv();

    $params = $redsys->getMerchantParameters($_POST["Ds_MerchantParameters"]);
    $response = (int)$params["Ds_Response"];

    if ($redsys->check($key, $_POST) && $response <= 99) {
        // ✅ PAGO OK
        // aquí guardar pedido, vaciar carrito, etc
    } else {
        // ❌ PAGO KO
    }

    http_response_code(200);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    exit;
}
