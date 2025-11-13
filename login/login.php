<?php
session_start();
require_once "../php/conexion.php"; // 🔹 Ruta corregida: sube una carpeta hasta /php/

// Si ya está logueado, redirige a index.php
if (isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Si se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    // Consulta segura con PDO
    $sql = "SELECT * FROM usuario WHERE nombre = :usuario AND contraseña = :password";
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':usuario', $usuario);
    $stmt->bindParam(':password', $password);
    $stmt->execute();

    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        $_SESSION['usuario'] = $resultado['nombre'];
        header("Location: ../index.php");
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Tienda</title>
    <link rel="stylesheet" href="../css/estilos.css"> <!-- 🔹 Ruta corregida -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.2);
            width: 300px;
            text-align: center;
        }

        input {
            width: 90%;
            padding: 8px;
            margin: 8px 0;
        }

        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px;
            width: 100%;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background: #0056b3;
        }

        .error {
            color: red;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Iniciar sesión</h2>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <!-- 🔹 Solo un formulario -->
        <form method="POST" action="">
            <label>Usuario:</label><br>
            <input type="text" name="usuario" required><br>

            <label>Contraseña:</label><br>
            <input type="password" name="password" required><br>

            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>
