<?php
session_start();
include 'config/conexion.php';

// Si ya está logueado, redirige al index directamente
if (isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!empty($usuario) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifica si el usuario existe y coincide la contraseña (soporta hash o texto plano)
            if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
                
                // Claves guardadas tal como las requiere tu index.php
                $_SESSION['usuario'] = $user['usuario'];
                $_SESSION['rol'] = $user['rol'];
                
                // Opcional por si lo usás en otra parte del sistema
                $_SESSION['usuario_id'] = $user['id']; 

                header("Location: index.php");
                exit();
            } else {
                $error = "Usuario o contraseña incorrectos.";
            }
        } catch (PDOException $e) {
            $error = "Error al conectar con la base de datos.";
        }
    } else {
        $error = "Por favor, completá todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - K-Beauty System</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background-color: #fcf9f8; }
        .login-card { width: 100%; max-width: 400px; padding: 30px; }
        .error-msg { color: #b81212; background: #fde8e8; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="card login-card">
        <h2 style="text-align: center; margin-bottom: 20px;">Iniciar Sesión 🌸</h2>
        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-submit" style="width: 100%; margin-top: 10px;">Ingresar</button>
        </form>
    </div>
</body>
</html>