<?php
// 1. Iniciar sesión para poder usar el carrito y verificar usuario
session_start();

require_once '../php/conexion.php';

// 2. Validación del ID del producto
if (!isset($_GET['id'])) {
    header("Location: ../index.php");
    exit;
}

$id = intval($_GET['id']);

// 3. Consulta para obtener los datos básicos del producto y su marca
$stmt = $conexion->prepare("SELECT p.*, m.nombre AS marcaNombre 
                            FROM producto p 
                            JOIN marca m ON p.idMarca = m.idMarca
                            WHERE p.idProducto = ?");
$stmt->execute([$id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    echo "<h1>Producto no encontrado</h1>";
    echo "<a href='../index.php'>Volver a la tienda</a>";
    exit;
}

// 4. Lógica de la imagen (Rutas relativas)
$nombreArchivo = preg_replace('/[^A-Za-z0-9_\-]/', '_', $producto['nombre']);
$extensiones = ['jpg', 'jpeg', 'png', 'webp'];
$rutaImagen = '';

foreach ($extensiones as $ext) {
    $ruta = "../imagenes/$nombreArchivo.$ext";
    if (file_exists($ruta)) {
        $rutaImagen = $ruta;
        break;
    }
}

if ($rutaImagen === '') {
    $rutaImagen = "../imagenes/no-imagen.png";
}

// 5. Lógica para AÑADIR AL CARRITO (Con Stacking y Validación de Stock)
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['talla']) && isset($_SESSION['usuario'])) {
    $tallaSeleccionada = $_POST['talla'];
    
    // Consultamos el stock real y el ID específico de esa variante (nombre + talla)
    $stmt_stock = $conexion->prepare("SELECT idProducto, stock FROM producto WHERE nombre = ? AND talla = ?");
    $stmt_stock->execute([$producto['nombre'], $tallaSeleccionada]);
    $info_variante = $stmt_stock->fetch(PDO::FETCH_ASSOC);

    if ($info_variante) {
        $stockReal = intval($info_variante['stock']);
        $idEspecifico = $info_variante['idProducto'];

        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        // Verificamos cuánto hay ya de esa variante en el carrito
        $cantidadEnCarrito = 0;
        $indiceEncontrado = -1;

        foreach ($_SESSION['carrito'] as $indice => $item) {
            if ($item['id'] == $idEspecifico) {
                $cantidadEnCarrito = $item['cantidad'];
                $indiceEncontrado = $indice;
                break;
            }
        }

        // VALIDACIÓN: Solo añadir si hay stock suficiente
        if (($cantidadEnCarrito + 1) <= $stockReal) {
            if ($indiceEncontrado !== -1) {
                // Si ya existe, sumamos uno (Stacking)
                $_SESSION['carrito'][$indiceEncontrado]['cantidad'] += 1;
            } else {
                // Si no existe, lo creamos
                $_SESSION['carrito'][] = [
                    'id' => $idEspecifico,
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio'],
                    'imagen' => $rutaImagen,
                    'talla' => $tallaSeleccionada,
                    'cantidad' => 1
                ];
            }
            $mensaje = "¡Producto añadido correctamente!";
        } else {
            $mensaje = "Lo sentimos, no puedes añadir más unidades. Stock máximo disponible: $stockReal";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($producto['nombre']) ?> - JP Calzados</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    
    <?php include 'header.php'; ?>

    <div class="contenedor">
        
        <?php if ($mensaje): ?>
            <div class="alerta-exito" style="background: #d4edda; color: #155724; padding: 10px; margin: 20px 0; border-radius: 5px; border: 1px solid #c3e6cb;">
                <?= $mensaje ?> <a href="../index.php" style="color: #155724; font-weight: bold;">Seguir comprando</a>
            </div>
        <?php endif; ?>

        <main class="detalle-producto" style="display: flex; gap: 40px; margin-top: 40px;">
            
            <div class="detalle-imagen" style="flex: 1;">
                <img src="<?= htmlspecialchars($rutaImagen) ?>" 
                     alt="<?= htmlspecialchars($producto['nombre']) ?>"
                     style="width: 100%; max-width: 500px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            </div>

            <div class="detalle-info" style="flex: 1;">
                <h2 style="font-size: 2rem; margin-bottom: 10px;"><?= htmlspecialchars($producto['nombre']) ?></h2>
                <h3 style="color: #666; font-weight: normal;"><?= htmlspecialchars($producto['marcaNombre']) ?></h3>
                
                <p class="precio" style="font-size: 1.5rem; color: #e63946; font-weight: bold; margin: 20px 0;">
                    € <?= number_format($producto['precio'], 2) ?>
                </p>
                
                <p><strong>Color:</strong> <?= htmlspecialchars($producto['color']) ?></p>
                
                <p style="margin-top: 20px; line-height: 1.6;">
                    Calzado de alta gama fabricado con materiales premium. 
                    Garantiza comodidad y durabilidad para tu día a día.
                </p>

                <form method="POST" action="">
                    <label for="talla" style="display: block; font-weight: bold; margin-top: 20px;">Selecciona tu talla:</label>
                    <select name="talla" id="talla" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #ccc; margin-top: 10px;">
                        <option value="">Elige tu talla</option>
                        <?php 
                        // Buscamos todas las variantes (tallas) de este mismo modelo en la DB
                        $stmt_tallas = $conexion->prepare("SELECT talla, stock FROM producto WHERE nombre = ? ORDER BY talla ASC");
                        $stmt_tallas->execute([$producto['nombre']]);
                        $variantes = $stmt_tallas->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($variantes as $v): 
                            $talla = $v['talla'];
                            $stockTalla = intval($v['stock']);
                            
                            if ($stockTalla > 0): ?>
                                <option value="<?= $talla ?>" style="color: black;">
                                    Talla <?= $talla ?> (<?= $stockTalla ?> en stock)
                                </option>
                            <?php else: ?>
                                <option value="<?= $talla ?>" style="color: #ccc;" disabled>
                                    Talla <?= $talla ?> (Sin stock)
                                </option>
                            <?php endif; 
                        endforeach; ?>
                    </select>

                    <div style="margin-top: 25px;">
                        <?php if (isset($_SESSION['usuario'])): ?>
                            <button type="submit" style="width: 100%; background-color: #d40000; color: white; padding: 15px; border: none; border-radius: 8px; cursor: pointer; font-size: 1.1rem; font-weight: bold; transition: 0.3s;">
                                <i class="fa fa-shopping-cart"></i> Añadir al carrito
                            </button>
                        <?php else: ?>
                            <button type="button" disabled style="width: 100%; background-color: #ccc; color: #666; padding: 15px; border: none; border-radius: 8px; cursor: not-allowed; font-size: 1.1rem; font-weight: bold;">
                                Inicia sesión para comprar
                            </button>
                            <p style="text-align: center; margin-top: 10px;">
                                <a href="../login/login.php" style="color: #d40000; font-weight: bold; text-decoration: none;">Ir al Login</a>
                            </p>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>