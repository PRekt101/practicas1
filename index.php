<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'php/obtener_productos.php';
session_start(); 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>JP Calzados</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include 'html/header.php'; ?>

    <div class="contenedor">
        <?php include 'html/filtros.php'; ?>

        <main>
            <h2>Catálogo de Productos</h2>
            <div class="productos">
                <?php if (empty($productos)): ?>
                    <p>No se encontraron productos con esos filtros.</p>
                <?php else: ?>
                    <?php foreach ($productos as $p): ?>
                        <?php
                            // Lógica de imagen
                            $nombreArchivo = preg_replace('/[^A-Za-z0-9_\-]/', '_', $p['nombre']); 
                            $extensiones = ['jpg', 'jpeg', 'png', 'webp'];
                            $rutaImagen = 'imagenes/no-imagen.png';

                            foreach ($extensiones as $ext) {
                                if (file_exists("imagenes/$nombreArchivo.$ext")) {
                                    $rutaImagen = "imagenes/$nombreArchivo.$ext";
                                    break; 
                                }
                            }
                        ?>

                        <a href="html/detalle_producto.php?id=<?= $p['idProducto'] ?>" class="producto">
                            
                            <div class="imagen">
                                <img src="<?= htmlspecialchars($rutaImagen) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>">
                            </div>
                            
                            <h3><?= htmlspecialchars($p['nombre']) ?></h3>
                            <p><?= htmlspecialchars($p['marcaNombre']) ?> · <?= htmlspecialchars($p['color']) ?></p>
                            
                            <p class="precio">
                                <?php if ($p['precioMin'] < $p['precioMax']): ?>
                                    Desde € <?= number_format($p['precioMin'], 2) ?>
                                <?php else: ?>
                                    € <?= number_format($p['precioMin'], 2) ?>
                                <?php endif; ?>
                            </p>

                            <?php if ($p['stockTotal'] <= 5): ?>
                                <p style="color: #d40000; font-weight: bold; font-size: 0.9rem; margin-top: 5px; background: #ffebee; padding: 2px 8px; border-radius: 4px; display: inline-block;">
                                    <i class="fas fa-fire"></i> ¡Últimas <?= $p['stockTotal'] ?> uds!
                                </p>
                            <?php endif; ?>
                            
                        </a>

                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include 'html/footer.php'; ?>
</body>
</html>