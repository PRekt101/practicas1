<?php
session_start();
require_once __DIR__ . '/../php/conexion.php';

/*
 ─────────────────────────────────────
  SEGURIDAD
 ─────────────────────────────────────
*/
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login/login.php');
    exit;
}

/*
 ─────────────────────────────────────
  CLIENTES DISPONIBLES
 ─────────────────────────────────────
*/
$stmt = $conexion->prepare("
    SELECT idUsuario, nombre, email
    FROM usuario
    WHERE rol = 'cliente'
    ORDER BY nombre
");
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($clientes)) {
    die('No hay clientes registrados');
}

/*
 ─────────────────────────────────────
  CLIENTE SELECCIONADO
 ─────────────────────────────────────
*/
$idCliente = $_GET['cliente'] ?? $clientes[0]['idUsuario'];

/*
 ─────────────────────────────────────
  KPIs GLOBALES (OPCIONAL)
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
  ESTADÍSTICAS DEL CLIENTE
 ─────────────────────────────────────
*/
$stmt = $conexion->prepare("
    SELECT
        COUNT(c.idCarrito) AS total_pedidos,
        IFNULL(SUM(c.precioTotal), 0) AS total_gastado,
        IFNULL(AVG(c.precioTotal), 0) AS ticket_medio,
        MAX(c.fechaCreacion) AS ultima_compra
    FROM carrito c
    WHERE c.idUsuario = ?
      AND c.estado = 'pagado'
");
$stmt->execute([$idCliente]);
$statsCliente = $stmt->fetch(PDO::FETCH_ASSOC);

/*
 ─────────────────────────────────────
  PEDIDOS POR MES (CLIENTE)
 ─────────────────────────────────────
*/
$stmt = $conexion->prepare("
    SELECT 
        DATE_FORMAT(fechaCreacion, '%Y-%m') AS mes,
        COUNT(*) AS total
    FROM carrito
    WHERE estado = 'pagado'
      AND idUsuario = ?
    GROUP BY mes
    ORDER BY mes
");
$stmt->execute([$idCliente]);
$pedidosMes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
 ─────────────────────────────────────
  DATOS DEL CLIENTE
 ─────────────────────────────────────
*/
$stmt = $conexion->prepare("
    SELECT nombre, email
    FROM usuario
    WHERE idUsuario = ?
");
$stmt->execute([$idCliente]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de clientes</title>
    <link rel="stylesheet" href="../css/estilos.css">

    <style>/* Estilo del botón volver */
        .btn-volver {
            background-color: #333;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9em;
            transition: background 0.3s;
        }
        .btn-volver:hover {
            background-color: #555;
        }
        .btn-volver i { margin-right: 5px; }
        </style>
</head>
<body>

<div class="info-box">

    <h1>Informe de clientes</h1>
    <div style="margin: 15px 0;">
    <a href="../index.php" class="btn-volver">← Volver al inicio</a>
    </div>

    <!-- SELECTOR DE CLIENTE -->
    <form method="get" style="margin-bottom:20px">
        <label><strong>Cliente:</strong></label>
        <select name="cliente" onchange="this.form.submit()">
            <?php foreach ($clientes as $c): ?>
                <option value="<?= $c['idUsuario'] ?>"
                    <?= ($c['idUsuario'] == $idCliente) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <!-- KPIs DEL CLIENTE -->
    <div class="dashboard">

        <div class="kpis">
            <div class="kpi">
                <h3><?= $statsCliente['total_pedidos'] ?></h3>
                <span>Pedidos</span>
            </div>
            <div class="kpi">
                <h3><?= number_format($statsCliente['total_gastado'], 2) ?> €</h3>
                <span>Total gastado</span>
            </div>
            <div class="kpi">
                <h3><?= number_format($statsCliente['ticket_medio'], 2) ?> €</h3>
                <span>Ticket medio</span>
            </div>
            <div class="kpi">
                <h3><?= $statsCliente['ultima_compra'] ?? '—' ?></h3>
                <span>Última compra</span>
            </div>
        </div>

        <div class="chart-box">
            <h2>Pedidos por mes</h2>
            <canvas id="chartPedidos"></canvas>
        </div>

    </div>

    <!-- INFO DEL CLIENTE -->
    <p class="muted">
        Cliente seleccionado:
        <strong><?= htmlspecialchars($cliente['nombre']) ?></strong>
        (<?= htmlspecialchars($cliente['email']) ?>)
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
