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

// --- LÓGICA DEL FORMULARIO ---
$datosForm = null;     // Datos para rellenar los inputs
$accion = 'crear';     // Por defecto, creamos nuevo
$titulo = 'Añadir Nuevo Producto';
$imagenRequerida = 'required'; 

// A) Si estamos EDITANDO un producto existente
if (isset($_GET['editar'])) {
    $stmt = $conexion->prepare("SELECT * FROM producto WHERE idProducto = ?");
    $stmt->execute([$_GET['editar']]);
    $datosForm = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($datosForm) {
        $accion = 'editar';
        $titulo = 'Editar Producto: ' . htmlspecialchars($datosForm['nombre']);
        $imagenRequerida = ''; // No obligatoria al editar
    }
}

// B) Si estamos AÑADIENDO UNA TALLA (Clonando datos)
if (isset($_GET['nueva_talla'])) {
    $stmt = $conexion->prepare("SELECT * FROM producto WHERE idProducto = ?");
    $stmt->execute([$_GET['nueva_talla']]);
    $origen = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($origen) {
        $datosForm = $origen;
        // Limpiamos los campos que deben ser nuevos
        $datosForm['idProducto'] = null; // Es un ID nuevo
        $datosForm['talla'] = '';        // Para que escribas la nueva talla
        $datosForm['stock'] = '';        // Para que pongas el nuevo stock
        
        $accion = 'crear'; // Sigue siendo crear, pero con datos pre-rellenos
        $titulo = 'Añadir Talla para: ' . htmlspecialchars($origen['nombre']);
        $imagenRequerida = ''; // No obligatoria porque ya existe en la carpeta con ese nombre
    }
}

// 4. Obtener listado para la tabla
$sqlProd = "SELECT p.*, m.nombre as nombre_marca FROM producto p JOIN marca m ON p.idMarca = m.idMarca ORDER BY p.nombre ASC, p.talla ASC";
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
        .admin-container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .panel-grid { display: grid; grid-template-columns: 350px 1fr; gap: 40px; }
        
        .card-form { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); position: sticky; top: 20px; height: fit-content; }
        .card-form h3 { margin-top: 0; color: #d40000; font-size: 1.2rem; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9rem; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        
        .tabla-contenedor { overflow-x: auto; background: white; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .tabla-productos { width: 100%; border-collapse: collapse; min-width: 600px; }
        .tabla-productos th, .tabla-productos td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        .tabla-productos th { background: #333; color: white; position: sticky; top: 0; }
        .tabla-productos tr:hover { background-color: #f9f9f9; }
        
        /* Botones de acción */
        .btn-accion { text-decoration: none; padding: 6px 10px; border-radius: 4px; transition: 0.3s; margin-right: 3px; display: inline-block; font-size: 0.9rem; border: 1px solid transparent; }
        
        .btn-editar { background: #e3f2fd; color: #0d47a1; border-color: #bbdefb; }
        .btn-editar:hover { background: #2196f3; color: white; }
        
        .btn-talla { background: #e8f5e9; color: #1b5e20; border-color: #c8e6c9; }
        .btn-talla:hover { background: #4caf50; color: white; }

        .btn-borrar { background: #ffebee; color: #b71c1c; border-color: #ffcdd2; }
        .btn-borrar:hover { background: #f44336; color: white; }

        .img-miniatura { width: 50px; height: 50px; object-fit: contain; background: #fff; border: 1px solid #ddd; border-radius: 4px; }
        
        .tag-stock { font-weight: bold; padding: 2px 6px; border-radius: 4px; font-size: 0.85rem; }
        .stock-bajo { background: #ffebee; color: #c62828; }
        .stock-ok { background: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>
    
    <?php include 'header.php'; ?>

    <div class="admin-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h1><i class="fa fa-cogs"></i> Gestión de Productos</h1>
            <a href="panel_admin.php" class="btn-accion" style="background:#333; color:white; padding:10px 20px;">
                <i class="fa fa-plus"></i> Limpiar Formulario
            </a>
        </div>

        <?php if(isset($_GET['status'])): ?>
            <?php if($_GET['status'] == 'ok'): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #c3e6cb;">
                    <i class="fa fa-check-circle"></i> Operación realizada con éxito.
                </div>
            <?php elseif($_GET['status'] == 'updated'): ?>
                <div style="background: #cce5ff; color: #004085; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #b8daff;">
                    <i class="fa fa-info-circle"></i> Producto actualizado correctamente.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="panel-grid">
            
            <aside class="card-form">
                <h3><?= $titulo ?></h3>
                
                <form action="../php/gestion_productos.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="<?= $accion ?>">
                    
                    <?php if($accion == 'editar'): ?>
                        <input type="hidden" name="idProducto" value="<?= $datosForm['idProducto'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Nombre del Producto:</label>
                        <input type="text" name="nombre" required 
                               value="<?= $datosForm['nombre'] ?? '' ?>" 
                               placeholder="Ej: Chanclas de Guiri">
                        <?php if(isset($_GET['nueva_talla'])): ?>
                            <small style="color: #d40000; font-size: 0.8rem;">* Mantén el nombre igual para usar la misma foto.</small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Marca:</label>
                        <select name="idMarca" required>
                            <option value="">Selecciona una marca</option>
                            <?php foreach($marcas as $m): ?>
                                <option value="<?= $m['idMarca'] ?>" 
                                    <?= ($datosForm && $datosForm['idMarca'] == $m['idMarca']) ? 'selected' : '' ?>>
                                    <?= $m['nombre'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tipo de Calzado:</label>
                        <select name="tipo" required>
                            <?php 
                            $tipos = ['Zapatillas', 'Botas', 'Tacones', 'chanclas'];
                            foreach($tipos as $t): 
                            ?>
                                <option value="<?= $t ?>" <?= ($datosForm && $datosForm['tipo'] == $t) ? 'selected' : '' ?>>
                                    <?= ucfirst($t) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <div class="form-group" style="flex:1;">
                            <label>Color:</label>
                            <input type="text" name="color" required 
                                   value="<?= $datosForm['color'] ?? '' ?>" placeholder="Ej: Azul">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>Talla:</label>
                            <input type="number" name="talla" required 
                                   value="<?= $datosForm['talla'] ?? '' ?>" placeholder="Ej: 23" style="background:#fff3cd; border-color:#ffeeba;">
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <div class="form-group" style="flex:1;">
                            <label>Stock:</label>
                            <input type="number" name="stock" required min="0" 
                                   value="<?= $datosForm['stock'] ?? '' ?>" placeholder="Ej: 50">
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label>Precio (€):</label>
                            <input type="number" step="0.01" name="precio" required 
                                   value="<?= $datosForm['precio'] ?? '' ?>" placeholder="0.00">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Imagen:</label>
                        <input type="file" name="imagen" accept="image/*" <?= $imagenRequerida ?>>
                        <?php if(!$imagenRequerida): ?>
                            <small style="color:#666; display:block; margin-top:5px;">
                                Si lo dejas vacío, se usará la imagen existente (basada en el nombre).
                            </small>
                        <?php endif; ?>
                    </div>

                    <button type="submit" style="width:100%; background: #d40000; color: white; padding: 12px; border:none; border-radius:5px; font-size:1rem; cursor:pointer; font-weight:bold; transition: background 0.3s;">
                        <i class="fa fa-save"></i> Guardar Cambios
                    </button>
                    
                </form>
            </aside>

            <section class="tabla-contenedor">
                <table class="tabla-productos">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nombre</th>
                            <th>Talla</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th style="text-align:center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($productos as $p): ?>
                            <?php
                            // Buscar imagen
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
                            <td>
                                <strong><?= htmlspecialchars($p['nombre']) ?></strong><br>
                                <small style="color:#666;"><?= htmlspecialchars($p['nombre_marca']) ?> - <?= htmlspecialchars($p['color']) ?></small>
                            </td>
                            <td style="font-weight:bold; font-size:1.1em; color:#333;"><?= $p['talla'] ?></td>
                            <td><?= number_format($p['precio'], 2) ?>€</td>
                            <td>
                                <span class="tag-stock <?= $p['stock'] < 5 ? 'stock-bajo' : 'stock-ok' ?>">
                                    <?= $p['stock'] ?> u.
                                </span>
                            </td>
                            <td style="text-align:center; white-space:nowrap;">
                                
                                <a href="panel_admin.php?nueva_talla=<?= $p['idProducto'] ?>" class="btn-accion btn-talla" title="Añadir otra talla de este modelo">
                                    <i class="fa fa-plus-circle"></i> Talla
                                </a>

                                <a href="panel_admin.php?editar=<?= $p['idProducto'] ?>" class="btn-accion btn-editar" title="Editar este producto">
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