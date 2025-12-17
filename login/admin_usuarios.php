<?php
session_start();
require_once "../php/conexion.php";

$registros = [];
$error_db = "";

try {
    $sql = "SELECT h.idhistorico, u.nombre, h.fecha, h.hora, h.ip 
            FROM historicousuarios h
            INNER JOIN usuario u ON h.idUsuario = u.idUsuario
            ORDER BY h.idhistorico DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_db = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Histórico - JP Calzados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 900px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #d40000; border-bottom: 2px solid #d40000; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #fafafa; }
        .error { color: #d40000; background: #fff0f0; padding: 10px; margin-bottom: 20px; border: 1px solid #fcc; }
    </style>
</head>
<body>
<div class="container">
    <h2><i class="fa fa-history"></i> Histórico de Conexiones</h2>
    <?php if ($error_db): ?>
        <div class="error"><?php echo htmlspecialchars($error_db); ?></div>
    <?php endif; ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registros as $reg): ?>
                <tr>
                    <td>#<?php echo $reg['idhistorico']; ?></td>
                    <td><?php echo htmlspecialchars($reg['nombre']); ?></td>
                    <td><?php echo $reg['fecha']; ?></td>
                    <td><?php echo $reg['hora']; ?></td>
                    <td><?php echo $reg['ip']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>