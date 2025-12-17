<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago cancelado</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>
<?php include '../html/header.php'; ?>

<div class="contenedor">
    <h2>❌ El pago no se ha completado</h2>
    <p>No se ha realizado ningún cargo.</p>
    <a href="../html/ver_carrito.php">Volver al carrito</a>
</div>

<?php include '../html/footer.php'; ?>
</body>
</html>
