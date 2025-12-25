<?php
session_start();
require_once "../php/conexion.php";

if (isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $email = trim($_POST['email']);
    $direccion = trim($_POST['direccion']);
    $telefono = trim($_POST['telefono']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validación de campos
    if (empty($usuario) || empty($email) || empty($password)) {
        $mensaje = "Nombre, Email y Contraseña son obligatorios.";
    } elseif ($password !== $confirm_password) {
        $mensaje = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 6) {
        $mensaje = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        try {
            // 2. Comprobar si el usuario O el email ya existen
            $sql_check = "SELECT idUsuario FROM usuario WHERE nombre = :usuario OR email = :email";
            $stmt_check = $conexion->prepare($sql_check);
            $stmt_check->execute([':usuario' => $usuario, ':email' => $email]);

            if ($stmt_check->rowCount() > 0) {
                $mensaje = "El usuario o el email ya están registrados.";
            } else {
                // 3. Insertar con todos los campos de tu BBDD
                $sql_insert = "INSERT INTO usuario (nombre, contraseña, email, direccion, telefono, rol) 
                               VALUES (:usuario, :password, :email, :direccion, :telefono, 'cliente')";
                
                $stmt_insert = $conexion->prepare($sql_insert);
                $res = $stmt_insert->execute([
                    ':usuario'   => $usuario,
                    ':password'  => $password,
                    ':email'     => $email,
                    ':direccion' => $direccion,
                    ':telefono'  => $telefono
                ]);

                if ($res) {
                    // Registro exitoso: guardamos en sesión y entramos
                    $_SESSION['usuario'] = $usuario;
                    $_SESSION['idUsuario'] = $conexion->lastInsertId();
                    $_SESSION['rol'] = 'cliente';
                    
                    header("Location: ../index.php");
                    exit();
                } else {
                    $mensaje = "Error al crear la cuenta.";
                }
            }
        } catch (PDOException $e) {
            $mensaje = "Error de base de datos: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Cuenta - JP Calzados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Arial', sans-serif; background: linear-gradient(135deg, #f2f2f2 0%, #e0e0e0 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 20px 0; }
        .login-container { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); width: 400px; text-align: center; }
        .login-container h2 { color: #d40000; margin-bottom: 20px; }
        label { display: block; text-align: left; margin-top: 10px; font-weight: bold; color: #333; font-size: 0.85em; }
        input { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; }
        button { background: #d40000; color: white; border: none; padding: 12px; margin-top: 25px; width: 100%; cursor: pointer; border-radius: 8px; font-weight: bold; }
        .mensaje { background-color: #fcebeb; color: #cc0033; padding: 10px; margin-bottom: 15px; border-radius: 5px; font-size: 13px; border: 1px solid #cc0033; }
        .link-volver { display: block; margin-top: 15px; color: #d40000; text-decoration: none; font-size: 0.9em; font-weight: bold; }
    </style>
</head>
<body>
    <div class="login-container">
        <i class="fa fa-user-plus" style="font-size: 2em; color: #d40000;"></i>
        <h2>Registrarse</h2>

        <?php if (!empty($mensaje)): ?>
            <p class="mensaje"><?php echo $mensaje; ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <label>Nombre de Usuario *</label>
            <input type="text" name="usuario" required value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">

            <label>Correo Electrónico *</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <label>Dirección</label>
            <input type="text" name="direccion" value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>">

            <label>Teléfono</label>
            <input type="number" name="telefono" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">

            <label>Contraseña *</label>
            <input type="password" name="password" required>
            
            <label>Repetir Contraseña *</label>
            <input type="password" name="confirm_password" required>

            <button type="submit">Crear mi cuenta</button>
        </form>
        
        <a href="login.php" class="link-volver">¿Ya tienes cuenta? Inicia Sesión</a>
    </div>
</body>
</html>