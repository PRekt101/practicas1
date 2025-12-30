<?php
session_start();
require_once '../php/conexion.php';

// Validación ID
if (!isset($_GET['id'])) {
    header("Location: ../index.php");
    exit;
}
$id = intval($_GET['id']);

// Consulta producto base
$stmt = $conexion->prepare("SELECT p.*, m.nombre AS marcaNombre FROM producto p JOIN marca m ON p.idMarca = m.idMarca WHERE p.idProducto = ?");
$stmt->execute([$id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) { echo "Producto no encontrado"; exit; }

// Imagen
$nombreArchivo = preg_replace('/[^A-Za-z0-9_\-]/', '_', $producto['nombre']);
$rutaImagen = '../imagenes/no-imagen.png';
foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
    if (file_exists("../imagenes/$nombreArchivo.$ext")) { $rutaImagen = "../imagenes/$nombreArchivo.$ext"; break; }
}

// Lógica Añadir al Carrito (POST)
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['talla']) && isset($_SESSION['usuario'])) {
    $tallaSeleccionada = $_POST['talla'];
    
    // Buscamos el ID exacto de la talla elegida
    $stmt_variante = $conexion->prepare("SELECT idProducto, stock, precio FROM producto WHERE nombre = ? AND talla = ? LIMIT 1");
    $stmt_variante->execute([$producto['nombre'], $tallaSeleccionada]);
    $variante = $stmt_variante->fetch(PDO::FETCH_ASSOC);

    if ($variante) {
        $idVariante = $variante['idProducto'];
        $stockVariante = $variante['stock'];
        $precioVariante = $variante['precio'];

        if (!isset($_SESSION['carrito'])) { $_SESSION['carrito'] = []; }

        $indiceEncontrado = -1;
        $cantidadEnCarrito = 0;
        foreach ($_SESSION['carrito'] as $indice => $item) {
            if ($item['id'] == $idVariante) {
                $indiceEncontrado = $indice;
                $cantidadEnCarrito = $item['cantidad'];
                break;
            }
        }

        if (($cantidadEnCarrito + 1) <= $stockVariante) {
            if ($indiceEncontrado !== -1) {
                $_SESSION['carrito'][$indiceEncontrado]['cantidad']++;
            } else {
                $_SESSION['carrito'][] = [
                    'id' => $idVariante,
                    'nombre' => $producto['nombre'],
                    'precio' => $precioVariante, // Usamos el precio específico de la variante
                    'imagen' => $rutaImagen,
                    'talla' => $tallaSeleccionada,
                    'cantidad' => 1
                ];
            }
            $mensaje = "¡Añadido talla $tallaSeleccionada al carrito!";
        } else {
            $mensaje = "No hay suficiente stock de la talla $tallaSeleccionada.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($producto['nombre']) ?></title>
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="contenedor">
        <?php if ($mensaje): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; margin: 20px 0; border: 1px solid #c3e6cb; border-radius: 5px;">
                <?= $mensaje ?> <a href="../html/ver_carrito.php" style="font-weight:bold;">Ver Carrito</a>
            </div>
        <?php endif; ?>

        <main class="detalle-producto" style="display: flex; gap: 40px; margin-top: 40px;">
            <div class="detalle-imagen" style="flex: 1;">
                <img src="<?= htmlspecialchars($rutaImagen) ?>" style="width: 100%; max-width: 500px; border-radius: 8px;">
            </div>

            <div class="detalle-info" style="flex: 1;">
                <h2><?= htmlspecialchars($producto['nombre']) ?></h2>
                <h3 style="color: #666;"><?= htmlspecialchars($producto['marcaNombre']) ?></h3>
                
                <p id="precio-display" class="precio" style="font-size: 2rem; color: #e63946; font-weight: bold; margin: 20px 0;">
                    € <?= number_format($producto['precio'], 2) ?>
                </p>

                <form method="POST">
                    <label for="talla"><strong>Selecciona tu talla:</strong></label>
                    
                    <select name="talla" id="talla" required style="width: 100%; padding: 12px; margin-top: 10px; font-size: 1rem;">
                        <option value="" data-precio="<?= $producto['precio'] ?>">Elige...</option>
                        <?php
                        $stmt_tallas = $conexion->prepare("SELECT talla, stock, precio FROM producto WHERE nombre = ? ORDER BY talla ASC");
                        $stmt_tallas->execute([$producto['nombre']]);
                        $tallasDisponibles = $stmt_tallas->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($tallasDisponibles as $t):
                            $disabled = ($t['stock'] == 0) ? 'disabled' : '';
                            $textoStock = ($t['stock'] == 0) ? '(Agotado)' : "";
                            $selected = ($t['talla'] == $producto['talla']) ? 'selected' : '';
                        ?>
                            <option value="<?= $t['talla'] ?>" 
                                    data-precio="<?= $t['precio'] ?>" 
                                    <?= $disabled ?> <?= $selected ?>>
                                Talla <?= $t['talla'] ?> - € <?= number_format($t['precio'], 2) ?> <?= $textoStock ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div style="margin-top: 25px;">
                        <?php if (isset($_SESSION['usuario'])): ?>
                            <button type="submit" class="btn-pagar" style="width: 100%; background: #d40000;">
                                <i class="fa fa-shopping-cart"></i> Añadir al carrito
                            </button>
                        <?php else: ?>
                            <div style="background:#f8d7da; color:#721c24; padding:10px; text-align:center; border-radius:5px;">
                                <a href="../login/login.php" style="color:#721c24; font-weight:bold;">Inicia sesión</a> para comprar.
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
        const selectTalla = document.getElementById('talla');
        const precioDisplay = document.getElementById('precio-display');

        selectTalla.addEventListener('change', function() {
            // Obtenemos la opción seleccionada
            const opcion = this.options[this.selectedIndex];
            // Leemos el atributo data-precio
            const precio = opcion.getAttribute('data-precio');
            
            if(precio) {
                // Actualizamos el texto
                precioDisplay.innerHTML = '€ ' + parseFloat(precio).toFixed(2);
            }
        });
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>