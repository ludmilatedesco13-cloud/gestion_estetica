<?php
include 'config/conexion.php';

// Verificar que llegue un ID válido por la URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        // Se prepara la sentencia para evitar inyecciones SQL
        $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        
        // Redireccionamos al index con tu parámetro original de éxito
        header("Location: index.php?status=deleted");
        exit();
    } catch (PDOException $e) {
        // En vez de die(), usamos un alert controlado y volvemos al inicio para proteger el diseño
        echo "<script>alert('Error al eliminar el producto: " . addslashes($e->getMessage()) . "'); window.location.href='index.php';</script>";
        exit();
    }
} else {
    // Si entraron al archivo de colados sin pasar un ID, los saca volando al index
    header("Location: index.php");
    exit();
}
?>