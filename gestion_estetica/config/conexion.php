<?php
$host = "localhost";
$db   = "gestion_estetica";
$user = "root";
$pass = ""; //no tiene contraseña
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass);
    // Configurar PDO para que lance excepciones en caso de error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
     die("Error de conexion a la base de datos: " . $e->getMessage());
}
?>