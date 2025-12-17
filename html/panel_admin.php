<?php
session_start();
require_once '../php/conexion.php';

// 1. Seguridad: Solo admin
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// 2. Obtener marcas para el select
$marcas = $conexion->query("SELECT * FROM marca")->fetchAll(PDO::FETCH_ASSOC);

// 3. Lógica de EDICIÓN: Si hay un ID en la URL, buscamos los datos
$prodEditar = null; // Variable vacía por defecto
if (isset($_GET['editar'])) {
    $stmtEdit = $conexion->prepare("SELECT * FROM producto WHERE idProducto = ?");
    $stmtEdit->execute([$_GET['editar']]);
    $prodEditar = $stmtEdit->fetch(PDO::FETCH_ASSOC);
}

// 4. Obtener listado de productos para la tabla
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
        
        .card-form { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .card-form h3 { margin-top: 0; color: #d40000; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        
        .tabla-productos { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .tabla-productos th, .tabla-productos td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        .tabla-productos th { background: #333; color: white; }
        
        /* Botones de acción */
        .btn-accion { text-decoration: none; padding: 5px 10px; border-radius: 4px; transition: 0.3s; margin-right: 5px; display: inline-block; }
        .btn-borrar { color: red; border: 1px solid red; }
        .btn-borrar:hover { background: red; color: white; }
        .btn-editar { color: #007bff; border: 1px solid #007bff; }
        .btn-editar:hover { background: #007bff; color: white; }

        .img-miniatura { width: 60px !important; height: 60px !important; object-fit: contain; background: #fff; border: 1px solid #ddd; border-radius: 4px; display: block; }
    </style>
</head>
<body>
    
    <?php include 'header.php'; ?>

    <div class="admin-container">
        <h1><i class="fa fa-cogs"></i> Gestión de Productos</h1>
        
        <?php if(isset($_GET['status'])): ?>
            <?php if($_GET['status'] == 'ok'): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 5px;">Producto guardado correctamente.</div>
            <?php elseif($_GET['status'] == 'updated'): ?>
                <div style="background: #cce5ff; color: #004085; padding: 10px; margin-bottom: 20px; border-radius: 5px;">Producto actualizado correctamente.</div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="panel-grid">
            
            <aside class="card-form">
                <h3><?= $prodEditar ? 'Editar Producto' : 'Añadir Nuevo Producto' ?></h3>
                
                <form action="../php/gestion_productos.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="<?= $prodEditar ? 'editar' : 'crear' ?>">
                    <?php if($prodEditar): ?>
                        <input type="hidden" name="idProducto" value="<?= $prodEditar['idProducto'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Nombre:</label>
                        <input type="text" name="nombre" required 
                               value="<?= $prodEditar['nombre'] ?? '' ?>" 
                               placeholder="Ej: Nike Tiempo">
                    </div>

                    <div class="form-group">
                        <label>Marca:</label>
                        <select name="idMarca" required>
                            <?php foreach($marcas as $m): ?>
                                <option value="<?= $m['idMarca'] ?>" 
                                    <?= ($prodEditar && $prodEditar['idMarca'] == $m['idMarca']) ? 'selected' : '' ?>>
                                    <?= $m['nombre'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tipo:</label>
                        <select name="tipo" required>
                            <?php 
                            $tipos = ['Zapatillas', 'Botas', 'Tacones', 'chanclas'];
                            foreach($tipos as $t): 
                            ?>
                                <option value="<?= $t ?>" <?= ($prodEditar && $prodEditar['tipo'] == $t) ? 'selected' : '' ?>>
                                    <?= $t ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Color:</label>
                        <input type="text" name="color" required 
                               value="<?= $prodEditar['color'] ?? '' ?>" placeholder="Ej: Negro">
                    </div>

                    <div class="form-group">
                        <label>Talla:</label>
                        <input type="number" name="talla" required 
                               value="<?= $prodEditar['talla'] ?? '' ?>" placeholder="42">
                    </div>

                    <div class="form-group">
                        <label>Stock:</label>
                        <input type="number" name="stock" required min="0" 
                               value="<?= $prodEditar['stock'] ?? '' ?>" placeholder="10">
                    </div>

                    <div class="form-group">
                        <label>Precio (€):</label>
                        <input type="number" step="0.01" name="precio" required 
                               value="<?= $prodEditar['precio'] ?? '' ?>" placeholder="50.00">
                    </div>

                    <!-- <div class="form-group">
                        <label>Imagen <?= $prodEditar ? '(Opcional)' : '(Obligatoria)' ?>:</label>
                        <input type="file" name="imagen" accept="image/*" <?= $prodEditar ? '' : 'required' ?>>
                        <?php if($prodEditar): ?>
                            <small style="color:#666;">Deja vacío para mantener la imagen actual.</small>
                        <?php endif; ?>
                    </div> -->

                    <button type="submit" style="width:100%; background:<?= $prodEditar ? '#007bff' : '#28a745' ?>; color: white; padding: 10px; border:none; border-radius:5px; font-size:1rem; cursor:pointer;">
                        <?= $prodEditar ? 'Actualizar Producto' : 'Guardar Producto' ?>
                    </button>
                    
                    <?php if($prodEditar): ?>
                        <a href="panel_admin.php" style="display:block; text-align:center; margin-top:10px; color:#666; text-decoration:none;">Cancelar Edición</a>
                    <?php endif; ?>
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
                            <th>Stock</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($productos as $p): ?>
                            <?php
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
                            <td><img src="<?= $rutaImagen ?>" alt="Foto" class="img-miniatura"></td>
                            <td><?= $p['idProducto'] ?></td>
                            <td><?= htmlspecialchars($p['nombre']) ?></td>
                            <td><?= htmlspecialchars($p['nombre_marca']) ?></td>
                            <td><?= number_format($p['precio'], 2) ?>€</td>
                            <td><?= $p['stock'] ?></td>
                            <td>
                                <a href="panel_admin.php?editar=<?= $p['idProducto'] ?>" class="btn-accion btn-editar" title="Editar">
                                    <i class="fa fa-pencil-alt"></i>
                                </a>
                                <a href="../php/gestion_productos.php?accion=borrar&id=<?= $p['idProducto'] ?>" 
                                   class="btn-accion btn-borrar"
                                   onclick="return confirm('¿Seguro que quieres borrar este producto?');" title="Borrar">
                                    <i class="fa fa-trash"></i>
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