<?php
session_start();
require_once "../php/conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    // 1. Verificamos usuario y contraseña
    $sql = "SELECT idUsuario, nombre FROM usuario WHERE nombre = :usuario AND contraseña = :password";
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':usuario', $usuario);
    $stmt->bindParam(':password', $password);
    $stmt->execute();
    
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        // 2. Intentamos insertar en el histórico
        try {
            $idUser = $resultado['idUsuario'];
            $fechaActual = date('Y-m-d');
            $horaActual = date('H:i:s');
            $ipActual = $_SERVER['REMOTE_ADDR'];

            // OJO: Revisa si en tu tabla la columna es 'fecha' o 'fechu'
            // En mi anterior respuesta puse 'fecha' porque suele ser lo común, 
            // pero si tu error decía "Unknown column h.fechu", quizás deba ser 'fecha'.
            $sql_hist = "INSERT INTO historicousuarios (idUsuario, fecha, hora, ip) 
                         VALUES (:id, :f, :h, :ip)";
            $stmt_hist = $conexion->prepare($sql_hist);
            $stmt_hist->execute([
                ':id' => $idUser,
                ':f'  => $fechaActual,
                ':h'  => $horaActual,
                ':ip' => $ipActual
            ]);

            // Si llegamos aquí, el insert funcionó.
            $_SESSION['usuario'] = $resultado['nombre'];
            header("Location: ../index.php");
            exit();

        } catch (PDOException $e) {
            // SI EL HISTORIAL FALLA, ESTO TE DIRÁ POR QUÉ:
            die("Error al registrar en el histórico: " . $e->getMessage());
        }
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - JP Calzados</title>
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

        /* --- MENSAJE DE ERROR --- */
        .error {
            background-color: #fcebeb;
            color: #cc0033;
            border: 1px solid #cc0033;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 14px;
        }

        /* Icono opcional sobre el título */
        .login-icon {
            font-size: 2.5em;
            color: #d40000;
            margin-bottom: 10px;
        }
        
        /* --- NUEVO ESTILO: Enlace a Crear Cuenta --- */
        .link-registro {
            display: block;
            margin-top: 20px;
            color: #d40000; /* Color de la marca */
            text-decoration: none;
            font-size: 0.9em;
            font-weight: bold;
        }
        .link-registro:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <i class="fa fa-user-circle login-icon"></i>
        <h2>Iniciar Sesión en JP Calzados</h2>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="usuario">Usuario:</label>
            <input type="text" id="usuario" name="usuario" required>

            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">
                <i class="fa fa-sign-in-alt"></i> Entrar
            </button>
        </form>

        <a href="crear_cuenta.php" class="link-registro">¿No tienes cuenta? Regístrate aquí.</a>
    </div>
</body>
</html>