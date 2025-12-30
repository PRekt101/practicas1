<?php
session_start();
require_once '../php/conexion.php';
// Asegúrate de que la ruta a fpdf.php sea correcta según dónde pusiste la carpeta
require_once '../php/fpdf/fpdf.php'; 

// 1. SEGURIDAD Y DATOS (Copiado de tu lógica original)
if (!isset($_SESSION['idUsuario']) || !isset($_GET['id'])) {
    header("Location: ver_facturas.php");
    exit();
}

$idCarrito = intval($_GET['id']);
$idUsuario = $_SESSION['idUsuario'];

try {
    // CORRECCIÓN: Usamos 'fechaCreacion' en lugar de 'fecha'
    $check = $conexion->prepare("SELECT idCarrito, fechaCreacion FROM carrito WHERE idCarrito = ? AND idUsuario = ?");
    $check->execute([$idCarrito, $idUsuario]);
    $datosPedido = $check->fetch(PDO::FETCH_ASSOC);
    
    if (!$datosPedido) {
        die("No tienes permiso para ver esta factura.");
    }

    // Datos del Usuario (Para la cabecera de la factura)
    $stmtUser = $conexion->prepare("SELECT nombre, email FROM usuario WHERE idUsuario = ?");
    $stmtUser->execute([$idUsuario]);
    $userDatos = $stmtUser->fetch(PDO::FETCH_ASSOC);

    // Productos
    $sql = "SELECT cd.*, p.nombre 
            FROM carritodetalle cd
            JOIN producto p ON cd.idProducto = p.idProducto
            WHERE cd.idCarrito = :idCarrito";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([':idCarrito' => $idCarrito]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total
    $stmt2 = $conexion->prepare("SELECT precioTotal FROM carrito WHERE idCarrito = ?");
    $stmt2->execute([$idCarrito]);
    $resumen = $stmt2->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// 2. CREACIÓN DEL PDF CON FPDF
class PDF extends FPDF {
    // Cabecera de página (Se repite en cada página si la factura es larga)
    function Header() {
        // Logo (Asegúrate de tener un logo.png en imagenes o quita esta línea)
        // $this->Image('../imagenes/logo_tienda.png', 10, 6, 30);
        
        // Fuente Arial negrita 15
        $this->SetFont('Arial', 'B', 16);
        // Movernos a la derecha
        $this->Cell(80);
        // Título
        $this->Cell(30, 10, 'FACTURA', 0, 0, 'C');
        // Salto de línea
        $this->Ln(20);
    }

    // Pie de página
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Instanciamos el PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// --- DATOS DE LA EMPRESA (Hardcodeados porque es tu empresa) ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(100, 10, 'JP CALZADOS S.L.', 0, 1); // Nombre empresa

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(100, 5, 'C/ Falsa 123, Poligono Industrial', 0, 1);
$pdf->Cell(100, 5, '28000, Madrid, Espana', 0, 1);
$pdf->Cell(100, 5, 'CIF: B-12345678', 0, 1);
$pdf->Cell(100, 5, 'Email: contacto@jpcalzados.com', 0, 1);

// --- DATOS DEL CLIENTE Y FECHA (A la derecha) ---
// Movemos el cursor para ponerlo bonito
$pdf->SetXY(120, 30); 
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'DATOS CLIENTE:', 0, 1);

$pdf->SetX(120);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(50, 5, utf8_decode('Nombre: ' . $userDatos['nombre']), 0, 1);

$pdf->SetX(120);
$pdf->Cell(50, 5, 'Email: ' . $userDatos['email'], 0, 1);

$pdf->SetX(120);
$pdf->Cell(50, 5, 'Fecha: ' . date("d/m/Y", strtotime($datosPedido['fechaCreacion'])), 0, 1);

$pdf->SetX(120);
$pdf->Cell(50, 5, utf8_decode('Nº Factura: A-00') . $idCarrito, 0, 1);

$pdf->Ln(20); // Espacio antes de la tabla

// --- TABLA DE PRODUCTOS ---
// Encabezados
$pdf->SetFillColor(212, 0, 0); // Rojo corporativo
$pdf->SetTextColor(255); // Blanco
$pdf->SetFont('Arial', 'B', 11);

$pdf->Cell(90, 10, 'Producto', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Cantidad', 1, 0, 'C', true);
$pdf->Cell(35, 10, 'Precio Unit.', 1, 0, 'C', true);
$pdf->Cell(35, 10, 'Subtotal', 1, 1, 'C', true);

// Datos
$pdf->SetTextColor(0); // Volvemos a negro
$pdf->SetFont('Arial', '', 10);

foreach ($items as $item) {
    $subtotal = $item['cantidad'] * $item['precioUnitario'];
    
    // Usamos utf8_decode para que salgan bien las tildes en el PDF
    $pdf->Cell(90, 10, utf8_decode($item['nombre']), 1);
    $pdf->Cell(30, 10, $item['cantidad'], 1, 0, 'C');
    $pdf->Cell(35, 10, number_format($item['precioUnitario'], 2) . iconv('UTF-8', 'windows-1252', '€'), 1, 0, 'R');
    $pdf->Cell(35, 10, number_format($subtotal, 2) . iconv('UTF-8', 'windows-1252', '€'), 1, 1, 'R');
}

// --- TOTALES Y QR ---
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(155, 10, 'TOTAL A PAGAR:', 0, 0, 'R');
$pdf->SetTextColor(212, 0, 0); // Rojo
$pdf->Cell(35, 10, number_format($resumen['precioTotal'], 2) . iconv('UTF-8', 'windows-1252', '€'), 1, 1, 'R');

// Código QR (Truco: Usamos la API de Google Charts para generar la imagen al vuelo)
$pdf->Ln(10);
$pdf->SetTextColor(0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 10, 'Escanea este codigo para ver tu pedido online:', 0, 1);

// Generamos URL del QR
$contenidoQR = "Pedido #" . $idCarrito . " - Total: " . $resumen['precioTotal'];
$urlQR = "https://quickchart.io/qr?text=" . urlencode($contenidoQR) . "&size=150";
// Insertamos la imagen (x, y, ancho, alto)
// NOTA: Para que esto funcione, en php.ini debes tener allow_url_fopen = On
// Si no, descarga una imagen qr estática y ponla aquí.
$pdf->Image($urlQR, $pdf->GetX(), $pdf->GetY(), 30, 30, 'PNG');

// Salida del archivo (D = Descargar, I = Ver en navegador)
$pdf->Output('I', 'Factura_JPCalzados_' . $idCarrito . '.pdf');
?>