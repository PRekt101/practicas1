<?php
session_start();
require_once "../php/conexion.php";

// Si ya está logueado, redirige a index.php
if (isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

$mensaje = "";

// Si se envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Validación de campos
    if (empty($usuario) || empty($password) || empty($confirm_password)) {
        $mensaje = "Todos los campos son obligatorios.";
    } elseif ($password !== $confirm_password) {
        $mensaje = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 6) {
        $mensaje = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        try {
            // 2. Comprobar si el usuario ya existe
            $sql_check = "SELECT idUsuario FROM usuario WHERE nombre = :usuario";
            $stmt_check = $conexion->prepare($sql_check);
            $stmt_check->bindParam(':usuario', $usuario);
            $stmt_check->execute();

            if ($stmt_check->rowCount() > 0) {
                $mensaje = "El nombre de usuario ya está registrado.";
            } else {
                // 3. Insertar el nuevo usuario, solo con nombre y contraseña.
                // El rol_id se gestionará en la BBDD o manualmente.
                $sql_insert = "INSERT INTO usuario (nombre, contraseña) VALUES (:usuario, :password)";
                $stmt_insert = $conexion->prepare($sql_insert);

                $stmt_insert->bindParam(':usuario', $usuario);
                $stmt_insert->bindParam(':password', $password); // Nota: Por seguridad real, se recomienda usar password_hash()
                
                if ($stmt_insert->execute()) {
                    // Registro exitoso: iniciar sesión automáticamente y redirigir
                    $_SESSION['usuario'] = $usuario;
                    header("Location: ../index.php");
                    exit();
                } else {
                    $mensaje = "Error al crear la cuenta. Intente de nuevo.";
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
    <link rel="stylesheet" href="../css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* --- ESTILOS DE FONDO Y CONTENEDOR --- */
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #f2f2f2 0%, #e0e0e0 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            transition: background 0.5s;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 350px;
            max-width: 90%;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        /* --- TÍTULO Y LOGO --- */
        .login-container h2 {
            margin-top: 0;
            color: #d40000;
            font-size: 2em;
            margin-bottom: 25px;
        }

        /* --- CAMPOS DE FORMULARIO --- */
        label {
            display: block;
            text-align: left;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
            font-size: 0.9em;
        }

        input {
            width: 100%;
            padding: 12px 10px;
            margin: 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input:focus {
            border-color: #d40000;
            box-shadow: 0 0 5px rgba(212, 0, 0, 0.3);
            outline: none;
        }

        /* --- BOTÓN --- */
        button {
            background: #d40000;
            color: white;
            border: none;
            padding: 12px;
            margin-top: 30px;
            width: 100%;
            cursor: pointer;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1.1em;
            transition: background 0.3s, transform 0.1s;
        }

        button:hover {
            background: #a30000;
            transform: translateY(-1px);
        }

        /* --- MENSAJE DE ERROR/NOTIFICACIÓN --- */
        .mensaje {
            background-color: #fcebeb;
            color: #cc0033;
            border: 1px solid #cc0033;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .login-icon {
            font-size: 2.5em;
            color: #d40000;
            margin-bottom: 10px;
        }
        
        .link-volver {
            display: block;
            margin-top: 20px;
            color: #d40000;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: bold;
        }
        .link-volver:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <i class="fa fa-user-plus login-icon"></i>
        <h2>Crear Cuenta</h2>

        <?php if (!empty($mensaje)): ?>
            <p class="mensaje"><?php echo $mensaje; ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="usuario">Nombre de Usuario:</label>
            <input type="text" id="usuario" name="usuario" required value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">

            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            
            <label for="confirm_password">Repetir Contraseña:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit">
                <i class="fa fa-user-plus"></i> Registrarme
            </button>
        </form>
        
        <a href="login.php" class="link-volver">¿Ya tienes cuenta? Inicia Sesión</a>
    </div>
</body>
</html>