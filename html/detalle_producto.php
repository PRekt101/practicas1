<?php
// 1. Iniciar sesión para poder usar el carrito más adelante
session_start();

require_once '../php/conexion.php';

// 2. Validación básica del ID
if (!isset($_GET['id'])) {
    // Redirigir al index si no hay ID, es más elegante que un "die"
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

// 4. Lógica de la imagen (adaptada a la ruta relativa ../)
$nombreArchivo = preg_replace('/[^A-Za-z0-9_\-]/', '_', $producto['nombre']);
$extensiones = ['jpg', 'jpeg', 'png', 'webp'];
$rutaImagen = '';

foreach ($extensiones as $ext) {
    // Nota: Como estamos en /html, salimos una carpeta (..) para ir a imagenes
    $ruta = "../imagenes/$nombreArchivo.$ext";
    if (file_exists($ruta)) {
        $rutaImagen = $ruta;
        break;
    }
}

if ($rutaImagen === '') {
    $rutaImagen = "../imagenes/no-imagen.png";
}

// 5. Lógica para AÑADIR AL CARRITO
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['talla'])) {
    $talla = $_POST['talla'];
    
    // Estructura básica del producto para el carrito
    $item = [
        'id' => $producto['idProducto'],
        'nombre' => $producto['nombre'],
        'precio' => $producto['precio'],
        'imagen' => $rutaImagen,
        'talla' => $talla,
        'cantidad' => 1
    ];

    // Inicializar carrito si no existe
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }

    // Aquí podrías agregar lógica para no duplicar productos, sino sumar cantidad
    $_SESSION['carrito'][] = $item;
    
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

        <form method="POST" action="" style="margin-top: 30px;">
          <div style="margin-bottom: 20px;">
              <label for="talla" style="display: block; margin-bottom: 5px; font-weight: bold;">Selecciona tu talla:</label>
              <select name="talla" id="talla" required style="padding: 10px; width: 100%; max-width: 200px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="">Elige una opción...</option>
                <?php for ($i = 35; $i <= 45; $i++): ?>
                  <option value="<?= $i ?>">EU <?= $i ?></option>
                <?php endfor; ?>
              </select>
          </div>
          
          <button type="submit" class="btn-comprar" style="background: #333; color: #fff; padding: 12px 24px; border: none; cursor: pointer; font-size: 1rem; border-radius: 4px;">
            Añadir al carrito <i class="fas fa-shopping-cart"></i>
          </button>
        </form>
      </div>
    </main>
  </div>

  <?php include 'footer.php'; ?>
</body>
</html>