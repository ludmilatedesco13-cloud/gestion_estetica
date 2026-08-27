<?php
session_start();

// 1. Validar que la sesión exista y sea exclusivamente de admin
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php?error=" . urlencode("No tenés permisos para realizar esta acción."));
    exit();
}

include 'config/conexion.php';

// 2. Verificar que llegue un ID válido por GET
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        // Obtenemos la ruta de la imagen antes de borrar el registro
        $stmt_foto = $pdo->prepare("SELECT foto FROM productos WHERE id = ?");
        $stmt_foto->execute([$id]);
        $producto = $stmt_foto->fetch(PDO::FETCH_ASSOC);

        if ($producto) {
            // Eliminamos el archivo físico si existe y no es la foto por defecto
            $ruta_foto = $producto['foto'] ?? '';
            if (!empty($ruta_foto) && $ruta_foto !== 'img/producto_defecto.jpg' && file_exists($ruta_foto)) {
                @unlink($ruta_foto);
            }

            // Eliminamos el registro de la base de datos
            $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->execute([$id]);

            header("Location: index.php?status=deleted");
            exit();
        } else {
            header("Location: index.php?error=" . urlencode("El producto a eliminar no existe."));
            exit();
        }

    } catch (PDOException $e) {
        header("Location: index.php?error=" . urlencode("Error al eliminar el producto de la base de datos."));
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>