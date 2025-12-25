<?php
session_start();
require_once "../php/conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    try {
        // 1. Verificamos que el usuario, contraseña y email existan y coincidan en la tabla 'usuario'
        $sql = "SELECT * FROM usuario WHERE nombre = :usuario AND contraseña = :password AND email = :email";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':usuario'  => $usuario,
            ':password' => $password,
            ':email'    => $email
        ]);
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            // 2. Si es correcto, registramos en 'historicousuarios'
            $idUser = $resultado['idUsuario'];
            $fechaActual = date('Y-m-d');
            $horaActual = date('H:i:s');
            $ipActual = $_SERVER['REMOTE_ADDR'];

            // Insertamos en el histórico (Asegúrate de que 'historicousuarios' tenga la columna 'email')
            $sql_hist = "INSERT INTO historicousuarios (idUsuario, fecha, hora, ip, email) 
                         VALUES (:id, :f, :h, :ip, :email)";
            
            $stmt_hist = $conexion->prepare($sql_hist);
            $stmt_hist->execute([
                ':id'    => $idUser,
                ':f'     => $fechaActual,
                ':h'     => $horaActual,
                ':ip'    => $ipActual,
                ':email' => $email
            ]);

            // Guardamos datos en la sesión y redirigimos
            $_SESSION['usuario'] = $resultado['nombre'];
            $_SESSION['idUsuario'] = $resultado['idUsuario']; 
            $_SESSION['rol'] = $resultado['rol'];
            
            header("Location: ../index.php");
            exit();

        } else {
            $error = "Los datos ingresados no coinciden con nuestros registros.";
        }
    } catch (PDOException $e) {
        die("Error en el sistema: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - JP Calzados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Mantengo tus estilos originales */
        body { font-family: 'Arial', sans-serif; background: linear-gradient(135deg, #f2f2f2 0%, #e0e0e0 100%); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); width: 350px; max-width: 90%; text-align: center; }
        .login-container h2 { color: #d40000; font-size: 1.8em; margin-bottom: 20px; }
        label { display: block; text-align: left; margin-top: 15px; margin-bottom: 5px; font-weight: bold; color: #333; font-size: 0.9em; }
        input { width: 100%; padding: 12px 10px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; }
        button { background: #d40000; color: white; border: none; padding: 12px; margin-top: 25px; width: 100%; cursor: pointer; border-radius: 8px; font-weight: bold; font-size: 1.1em; }
        .error { background-color: #fcebeb; color: #cc0033; border: 1px solid #cc0033; padding: 10px; margin-bottom: 15px; border-radius: 5px; font-size: 14px; }
        .login-icon { font-size: 2.5em; color: #d40000; margin-bottom: 10px; }
        .link-registro { display: block; margin-top: 20px; color: #d40000; text-decoration: none; font-size: 0.9em; font-weight: bold; }
    </style>
</head>
<body>
    <div class="login-container">
        <i class="fa fa-user-circle login-icon"></i>
        <h2>JP Calzados</h2>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="usuario">Usuario:</label>
            <input type="text" id="usuario" name="usuario" required>

            <label for="email">Correo Electrónico:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">
                <i class="fa fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>

        <a href="crear_cuenta.php" class="link-registro">¿No tienes cuenta? Regístrate aquí.</a>
    </div>
</body>
</html>