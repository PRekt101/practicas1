<?php
session_start();
require_once '../php/conexion.php';

// 1. Seguridad: Solo admin puede entrar aquí
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// 2. Obtener marcas para el formulario
$marcas = $conexion->query("SELECT * FROM marca")->fetchAll(PDO::FETCH_ASSOC);

// 3. Obtener productos actuales para el listado
$sqlProd = "SELECT p.*, m.nombre as nombre_marca FROM producto p JOIN marca m ON p.idMarca = m.idMarca ORDER BY p.idProducto DESC";
$productos = $conexion->query($sqlProd)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin - JP Calzados</title>
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container { max-width: 1100px; margin: 40px auto; padding: 20px; }
        .panel-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 40px; }
        
        /* Estilos del Formulario */
        .card-form { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .card-form h3 { margin-top: 0; color: #d40000; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        
        /* Estilos de la Tabla */
        .tabla-productos { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .tabla-productos th, .tabla-productos td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        .tabla-productos th { background: #333; color: white; }
        .btn-borrar { color: red; cursor: pointer; text-decoration: none; padding: 5px 10px; border: 1px solid red; border-radius: 4px; transition: 0.3s; }
        .btn-borrar:hover { background: red; color: white; }

        /* --- CLASE NUEVA PARA FORZAR EL TAMAÑO DE LA IMAGEN --- */
        .img-miniatura {
            width: 60px !important;       /* Fuerza el ancho */
            height: 60px !important;      /* Fuerza el alto */
            object-fit: contain;          /* Ajusta la imagen sin deformarla */
            background: #fff;             /* Fondo blanco para verla bien */
            border: 1px solid #ddd;
            border-radius: 4px;
            display: block;               /* Evita comportamientos extraños de línea */
        }
    </style>
</head>
<body>
    
    <?php include 'header.php'; ?>

    <div class="admin-container">
        <h1><i class="fa fa-cogs"></i> Gestión de Productos</h1>
        
        <?php if(isset($_GET['status']) && $_GET['status'] == 'ok'): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
                Producto añadido correctamente.
            </div>
        <?php endif; ?>

        <div class="panel-grid">
            
            <aside class="card-form">
                <h3>Añadir Nuevo Producto</h3>
                <form action="../php/gestion_productos.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="crear">
                    
                    <div class="form-group">
                        <label>Nombre:</label>
                        <input type="text" name="nombre" required placeholder="Ej: Nike Tiempo Legend">
                    </div>

                    <div class="form-group">
                        <label>Marca:</label>
                        <select name="idMarca" required>
                            <?php foreach($marcas as $m): ?>
                                <option value="<?= $m['idMarca'] ?>"><?= $m['nombre'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tipo:</label>
                        <select name="tipo" required>
                            <option value="Zapatillas">Zapatillas</option>
                            <option value="Botas">Botas</option>
                            <option value="Tacones">Tacones</option>
                            <option value="chanclas">chanclas</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Color:</label>
                        <input type="text" name="color" required placeholder="Ej: Negro">
                    </div>

                    <div class="form-group">
                        <label>Talla:</label>
                        <input type="number" name="talla" required placeholder="42">
                    </div>

                    <div class="form-group">
                        <label>Stock (Unidades):</label>
                        <input type="number" name="stock" required placeholder="10" min="0">
                    </div>

                    <div class="form-group">
                        <label>Precio (€):</label>
                        <input type="number" step="0.01" name="precio" required placeholder="50.00">
                    </div>

                    <!-- <div class="form-group">
                        <label>Imagen del Producto:</label>
                        <input type="file" name="imagen" accept="image/*" required>
                    </div> -->

                    <button type="submit" style="width:100%; background:#28a745; color: white; padding: 10px; border:none; border-radius:5px; font-size:1rem; cursor:pointer;">
                        Guardar Producto
                    </button>
                </form>
            </aside>

            <section>
                <h3>Listado de Productos (<?= count($productos) ?>)</h3>
                <table class="tabla-productos">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Marca</th>
                            <th>Precio</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($productos as $p): ?>
                            <?php
                            // --- BÚSQUEDA DE IMAGEN ---
                            $nombreArchivo = preg_replace('/[^A-Za-z0-9_\-]/', '_', $p['nombre']);
                            $rutaImagen = '../imagenes/no-imagen.png'; 
                            
                            $extensiones = ['jpg', 'jpeg', 'png', 'webp'];
                            foreach ($extensiones as $ext) {
                                if (file_exists("../imagenes/$nombreArchivo.$ext")) {
                                    $rutaImagen = "../imagenes/$nombreArchivo.$ext";
                                    break;
                                }
                            }
                            ?>
                        <tr>
                            <td>
                                <img src="<?= $rutaImagen ?>" alt="Foto" class="img-miniatura">
                            </td>
                            
                            <td><?= $p['idProducto'] ?></td>
                            <td><?= htmlspecialchars($p['nombre']) ?></td>
                            <td><?= htmlspecialchars($p['nombre_marca']) ?></td>
                            <td><?= number_format($p['precio'], 2) ?>€</td>
                            <td>
                                <a href="../php/gestion_productos.php?accion=borrar&id=<?= $p['idProducto'] ?>" 
                                   class="btn-borrar"
                                   onclick="return confirm('¿Seguro que quieres borrar este producto?');">
                                    <i class="fa fa-trash"></i> Borrar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>