<?php
session_start();
require_once __DIR__ . '/../php/conexion.php';

/*
 ─────────────────────────────────────
  SEGURIDAD
 ─────────────────────────────────────
*/
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login/login.php');
    exit;
}

/*
 ─────────────────────────────────────
  KPIs GLOBALES
 ─────────────────────────────────────
*/
$stmt = $conexion->query("
    SELECT
        COUNT(DISTINCT u.idUsuario) AS usuarios,
        COUNT(c.idCarrito) AS pedidos,
        IFNULL(SUM(c.precioTotal),0) AS facturacion,
        IFNULL(AVG(c.precioTotal),0) AS ticket_medio
    FROM usuario u
    LEFT JOIN carrito c
        ON u.idUsuario = c.idUsuario
        AND c.estado = 'pagado'
");
$totales = $stmt->fetch(PDO::FETCH_ASSOC);

/*
 ─────────────────────────────────────
  PEDIDOS POR MES (GRÁFICO)
 ─────────────────────────────────────
*/
$stmt = $conexion->query("
    SELECT 
        DATE_FORMAT(fechaCreacion, '%Y-%m') AS mes,
        COUNT(*) AS total
    FROM carrito
    WHERE estado = 'pagado'
    GROUP BY mes
    ORDER BY mes
");
$pedidosMes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
 ─────────────────────────────────────
  INFORME GLOBAL DE USUARIOS
 ─────────────────────────────────────
*/
$stmt = $conexion->prepare("
    SELECT 
        u.nombre,
        u.email,
        COUNT(c.idCarrito) AS total_pedidos,
        IFNULL(SUM(c.precioTotal), 0) AS total_gastado,
        MAX(c.fechaCreacion) AS ultima_compra
    FROM usuario u
    LEFT JOIN carrito c
        ON u.idUsuario = c.idUsuario
        AND c.estado = 'pagado'
    GROUP BY u.idUsuario
    ORDER BY total_gastado DESC
");
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de usuarios</title>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="info-box">

    <h1>Informe de información de usuarios</h1>

    <!-- DASHBOARD -->
    <div class="dashboard">

        <div class="kpis">
            <div class="kpi">
                <h3><?= $totales['usuarios'] ?></h3>
                <span>Usuarios</span>
            </div>
            <div class="kpi">
                <h3><?= $totales['pedidos'] ?></h3>
                <span>Pedidos</span>
            </div>
            <div class="kpi">
                <h3><?= number_format($totales['facturacion'], 2) ?> €</h3>
                <span>Facturación</span>
            </div>
            <div class="kpi">
                <h3><?= number_format($totales['ticket_medio'], 2) ?> €</h3>
                <span>Ticket medio</span>
            </div>
        </div>

        <div class="chart-box">
            <h2>Pedidos por mes</h2>
            <canvas id="chartPedidos"></canvas>
        </div>

    </div>

    <!-- TABLA -->
    <table>
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Email</th>
                <th>Pedidos</th>
                <th>Total gastado (€)</th>
                <th>Última compra</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['nombre']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= $u['total_pedidos'] ?></td>
                    <td><?= number_format($u['total_gastado'], 2) ?></td>
                    <td><?= $u['ultima_compra'] ?? '—' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="muted">
        Este informe muestra datos agregados de los usuarios con fines estadísticos
        y de mejora del servicio. No se recopila información innecesaria ni sensible,
        cumpliendo principios de minimización y transparencia.
    </p>

</div>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chartPedidos'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($pedidosMes, 'mes')) ?>,
        datasets: [{
            label: 'Pedidos',
            data: <?= json_encode(array_column($pedidosMes, 'total')) ?>,
            borderColor: '#d10000',
            backgroundColor: 'rgba(209,0,0,0.15)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        responsive: true
    }
});
</script>

</body>
</html>
