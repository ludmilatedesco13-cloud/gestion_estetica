<?php
// 1. Iniciar la sesión para poder acceder a ella
session_start();

// 2. Limpiar todas las variables de sesión ($_SESSION['usuario'], $_SESSION['rol'], etc.)
$_SESSION = array();

// 3. Si se usaron cookies para la sesión, las eliminamos del navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destruir la sesión por completo en el servidor
session_destroy();

// 5. Redirigir al usuario de vuelta al login
header("Location: login.php");
exit();
?>