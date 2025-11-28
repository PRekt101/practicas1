<?php
session_start();
require_once '../php/conexion.php'; // Incluimos conexión por si queremos verificar stock más adelante

// --- LÓGICA PARA ELIMINAR O VACIAR ---

// 1. Eliminar un producto específico
if (isset($_GET['action']) && $_GET['action'] == 'eliminar' && isset($_GET['indice'])) {
    $indice = intval($_GET['indice']);
    if (isset($_SESSION['carrito'][$indice])) {
        unset($_SESSION['carrito'][$indice]);
        // Reindexar el array para evitar huecos en los índices
        $_SESSION['carrito'] = array_values($_SESSION['carrito']);
    }
    // Recargar para limpiar la URL
    header("Location: ver_carrito.php");
    exit;
}

// 2. Vaciar todo el carrito
if (isset($_GET['action']) && $_GET['action'] == 'vaciar') {
    unset($_SESSION['carrito']);
    header("Location: ver_carrito.php");
    exit;
}

// --- CÁLCULO DE TOTALES ---
$totalCarrito = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $totalCarrito += ($item['precio'] * $item['cantidad']);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tu Carrito - JP Calzados</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="contenedor">
        <main class="carrito-container" style="margin-top: 40px; margin-bottom: 40px;">
            <h2>Tu Carrito de Compras</h2>

            <?php if (empty($_SESSION['carrito'])): ?>
                
                <div class="carrito-vacio" style="text-align: center; padding: 50px;">
                    <i class="fas fa-shopping-basket" style="font-size: 4rem; color: #ccc;"></i>
                    <p style="margin-top: 20px;">Tu carrito está vacío.</p>
                    <a href="../index.php" class="btn-seguir">Volver a la tienda</a>
                </div>

            <?php else: ?>

                <table class="tabla-carrito">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Talla</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['carrito'] as $indice => $item): ?>
                            <tr>
                                <td class="info-prod">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="<?= htmlspecialchars($item['imagen']) ?>" 
                                             alt="<?= htmlspecialchars($item['nombre']) ?>" 
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                        <span><?= htmlspecialchars($item['nombre']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($item['talla']) ?></td>
                                <td>€ <?= number_format($item['precio'], 2) ?></td>
                                <td><?= $item['cantidad'] ?></td>
                                <td style="font-weight: bold;">
                                    € <?= number_format($item['precio'] * $item['cantidad'], 2) ?>
                                </td>
                                <td>
                                    <a href="ver_carrito.php?action=eliminar&indice=<?= $indice ?>" 
                                       class="btn-eliminar" 
                                       onclick="return confirm('¿Estás seguro de eliminar este producto?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="resumen-carrito">
                    <div class="acciones-carrito">
                        <a href="ver_carrito.php?action=vaciar" class="btn-vaciar">Vaciar Carrito</a>
                        <a href="../index.php" class="btn-seguir">Seguir Comprando</a>
                    </div>
                    
                    <div class="total-carrito">
                        <h3>Total a Pagar: <span>€ <?= number_format($totalCarrito, 2) ?></span></h3>
                        <a href="checkout.php" class="btn-pagar">Finalizar Compra</a>
                    </div>
                </div>

            <?php endif; ?>
        </main>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>