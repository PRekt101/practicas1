  <?php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  require_once 'php/obtener_productos.php';

  session_start(); // solo inicia sesión, no redirige ni destruye


  // Si no está logueado, simplemente no hace nada especial.
  // No redirigimos al login automáticamente para poder ver la página.
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
        <h2>Lista de productos</h2>
        <div class="productos">
          <?php if (empty($productos)): ?>
            <p>No se encontraron productos con esos filtros.</p>
          <?php else: ?>
            <?php foreach ($productos as $p): ?>
              <?php
                // Ruta de la imagen según el nombre del producto
                $nombreArchivo = preg_replace('/[^A-Za-z0-9_\-]/', '_', $p['nombre']); // evita espacios y caracteres raros
                $extensiones = ['jpg', 'jpeg', 'png', 'webp'];
                $rutaImagen = '';

                foreach ($extensiones as $ext) {
                  $ruta = "imagenes/$nombreArchivo.$ext";
                  if (file_exists($ruta)) {
                    $rutaImagen = $ruta;
                    break;
                  }
                }

                if ($rutaImagen === '') {
                  $rutaImagen = "imagenes/no-imagen.png"; // imagen por defecto opcional
                }
              ?>

              <!-- Tarjeta del producto con enlace al detalle -->
              <a href="html/detalle_producto.php?id=<?= isset($p['idProducto']) ? urlencode((string)$p['idProducto']) : '' ?>" class="producto">
                <div class="imagen">
                  <img src="<?= htmlspecialchars($rutaImagen) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>">
                </div>
                <h3><?= htmlspecialchars($p['nombre']) ?></h3>
                <p><?= htmlspecialchars($p['marcaNombre']) ?> · Color <?= htmlspecialchars($p['color']) ?></p>
                <p class="precio">€ <?= number_format($p['precio'], 2) ?></p>
              </a>

            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </main>
    </div>

    <?php include 'html/footer.php'; ?>
  </body>
  </html>
