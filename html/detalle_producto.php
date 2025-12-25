<?php
// 1. Iniciar sesión para poder usar el carrito más adelante
session_start();

require_once '../php/conexion.php';

// 2. Validación básica del ID
if (!isset($_GET['id'])) {
    header("Location: ../index.php");
    exit;
}

$id = intval($_GET['id']);

// 3. Consulta a la base de datos
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

// 4. Lógica de la imagen
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

// 5. Lógica para AÑADIR AL CARRITO (MODIFICADA PARA STACKEAR)
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['talla']) && isset($_SESSION['usuario'])) {
    $talla = $_POST['talla'];
    
    // Inicializar carrito si no existe
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }

    $encontrado = false;

    // Buscamos si el producto con la misma ID y misma TALLA ya está en el carrito
    foreach ($_SESSION['carrito'] as $indice => $item) {
        if ($item['id'] == $producto['idProducto'] && $item['talla'] == $talla) {
            // Si existe, aumentamos la cantidad
            $_SESSION['carrito'][$indice]['cantidad'] += 1;
            $encontrado = true;
            break;
        }
    }

    // Si no se encontró, lo añadimos como un nuevo registro
    if (!$encontrado) {
        $_SESSION['carrito'][] = [
            'id' => $producto['idProducto'],
            'nombre' => $producto['nombre'],
            'precio' => $producto['precio'],
            'imagen' => $rutaImagen,
            'talla' => $talla,
            'cantidad' => 1
        ];
    }
    
    $mensaje = "¡Producto añadido al carrito correctamente!";
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
        <div class="alerta-exito" style="background: #d4edda; color: #155724; padding: 10px; margin: 20px 0; border-radius: 5px;">
            <?= $mensaje ?> <a href="../index.php">Seguir comprando</a>
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
            Este es un calzado de excelente calidad, ideal para el uso diario o deportivo. 
            Fabricado con materiales resistentes y diseño ergonómico.
        </p>

        <?php if ($producto['stock'] > 0): ?>
      
          <form method="POST" action="">
            <label for="talla">Selecciona tu talla:</label>
            <select name="talla" id="talla" required>
              <option value="">Elige tu talla</option>
              <?php for ($i = 35; $i <= 45; $i++): ?>
                <option value="<?= $i ?>"><?= $i ?></option>
              <?php endfor; ?>
            </select>
            
            <?php if ($producto['stock'] < 5): ?>
                <p style="color: orange; font-size: 0.9em;">¡Date prisa! Solo quedan <?= $producto['stock'] ?> unidades.</p>
            <?php endif; ?>

            <?php if (isset($_SESSION['usuario'])): ?>
                <button type="submit" style="width: 100%; background-color: #d40000; color: white; padding: 12px; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; font-weight: bold;">
                    Añadir al carrito
                </button>
            <?php else: ?>
                <button type="button" disabled style="width: 100%; background-color: #ccc; color: #666; padding: 12px; border: none; border-radius: 5px; cursor: not-allowed; font-size: 1rem; font-weight: bold;">
                    Inicia sesión para comprar
                </button>
                <div style="text-align: center; margin-top: 10px;">
                    <a href="../login/login.php" style="color: #d40000; text-decoration: underline;">Ir a Iniciar Sesión</a>
                </div>
            <?php endif; ?>
          </form>

      <?php else: ?>
          
          <div class="agotado-container" style="margin-top: 20px;">
              <p style="color: red; font-weight: bold; font-size: 1.2rem;">PRODUCTO AGOTADO</p>
              <button disabled style="background-color: #ccc; cursor: not-allowed; border: none; padding: 10px 20px;">No disponible</button>
          </div>

      <?php endif; ?>
      </div>
    </main>
  </div>

  <?php include 'footer.php'; ?>
</body>
</html>