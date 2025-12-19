<?php
session_start();
require_once __DIR__ . '/../php/conexion.php';

/*
 ─────────────────────────────────────
  SEGURIDAD
 ─────────────────────────────────────
*/
if (!isset($_SESSION['idUsuario'])) {
    header('Location: ../login/login.php');
    exit;
}

$idUsuario = $_SESSION['idUsuario'];
$mensaje = '';

/*
 ─────────────────────────────────────
  GUARDAR CAMBIOS
 ─────────────────────────────────────
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre    = trim($_POST['nombre']);
    $telefono  = trim($_POST['telefono']);
    $email     = trim($_POST['email']);
    $direccion = trim($_POST['direccion']);
    $password  = trim($_POST['password']);

    if ($password !== '') {
        // Si cambia contraseña
        $stmt = $conexion->prepare("
            UPDATE usuario
            SET nombre = ?, telefono = ?, email = ?, direccion = ?, contraseña = ?
            WHERE idUsuario = ?
        ");
        $stmt->execute([
            $nombre,
            $telefono,
            $email,
            $direccion,
            password_hash($password, PASSWORD_DEFAULT),
            $idUsuario
        ]);
    } else {
        // Sin cambiar contraseña
        $stmt = $conexion->prepare("
            UPDATE usuario
            SET nombre = ?, telefono = ?, email = ?, direccion = ?
            WHERE idUsuario = ?
        ");
        $stmt->execute([
            $nombre,
            $telefono,
            $email,
            $direccion,
            $idUsuario
        ]);
    }

    $mensaje = 'Perfil actualizado correctamente';
}

/*
 ─────────────────────────────────────
  CARGAR DATOS ACTUALES
 ─────────────────────────────────────
*/
$stmt = $conexion->prepare("
    SELECT nombre, telefono, email, direccion, rol
    FROM usuario
    WHERE idUsuario = ?
");
$stmt->execute([$idUsuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die('Usuario no encontrado');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar perfil</title>
<link rel="stylesheet" href="../css/estilos.css">
<style>
.form-box {
    max-width: 500px;
    margin: 40px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,.1);
}
.form-box h1 {
    margin-bottom: 20px;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 6px;
}
.form-group input {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
}
button {
    width: 100%;
    padding: 12px;
    background: #d10000;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
}
button:hover {
    background: #a80000;
}
.success {
    background: #e7f6ec;
    color: #1e7e34;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    text-align: center;
}
.back {
    margin-top: 15px;
    text-align: center;
}
.back a {
    text-decoration: none;
    color: #555;
}
</style>
</head>
<body>

<div class="form-box">

    <h1>Editar perfil</h1>

    <?php if ($mensaje): ?>
        <div class="success"><?= $mensaje ?></div>
    <?php endif; ?>

    <form method="post">

        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="nombre" required
                   value="<?= htmlspecialchars($usuario['nombre']) ?>">
        </div>

        <div class="form-group">
            <label>Teléfono</label>
            <input type="number" name="telefono"
                   value="<?= htmlspecialchars($usuario['telefono']) ?>">
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required
                   value="<?= htmlspecialchars($usuario['email']) ?>">
        </div>

        <div class="form-group">
            <label>Dirección</label>
            <input type="text" name="direccion"
                   value="<?= htmlspecialchars($usuario['direccion']) ?>">
        </div>

        <div class="form-group">
            <label>Nueva contraseña (opcional)</label>
            <input type="password" name="password"
                   placeholder="Déjala vacía para no cambiarla">
        </div>

        <button type="submit">Guardar cambios</button>

    </form>

    <div class="back">
        <a href="../index.php">← Volver</a>
    </div>

</div>

</body>
</html>
