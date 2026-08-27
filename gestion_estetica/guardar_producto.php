<?php
// Valido usando las mismas claves que se definen en el index
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php?error=" . urlencode("No tenés permisos para realizar esta acción."));
    exit();
}

include 'config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Sanitización de datos del formulario
        $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
        $marca = isset($_POST['marca']) ? trim($_POST['marca']) : '';
        $origen = isset($_POST['origen']) ? trim($_POST['origen']) : '';
        
        // Limpieza de precios para asegurar números flotantes válidos
        $precio_costo_raw = isset($_POST['precio_costo']) ? str_replace(['.', ','], ['', '.'], $_POST['precio_costo']) : '0';
        $precio_venta_raw = isset($_POST['precio_venta']) ? str_replace(['.', ','], ['', '.'], $_POST['precio_venta']) : '0';

        $precio_costo = floatval(preg_replace('/[^\d.]/', '', $precio_costo_raw));
        $precio_venta = floatval(preg_replace('/[^\d.]/', '', $precio_venta_raw));
        
        $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
        $caracteristicas = isset($_POST['caracteristicas']) ? trim($_POST['caracteristicas']) : '';
        $categoria = isset($_POST['categoria']) ? trim($_POST['categoria']) : '';
        $subcategoria = isset($_POST['subcategoria']) ? trim($_POST['subcategoria']) : '';

        // 2. Validaciones estrictas de Backend
        if (empty($nombre)) {
            throw new Exception("El nombre del producto no puede estar vacío.");
        }
        if ($precio_costo <= 0 || $precio_venta <= 0) {
            throw new Exception("Los precios de costo y venta deben ser mayores a $0.");
        }
        if ($stock < 0) {
            throw new Exception("El stock no puede ser un número negativo.");
        }

        // 3. Definir la ruta por defecto para la imagen
        $ruta_foto_final = 'img/producto_defecto.jpg';

        // 4. Procesar la subida de la imagen
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            
            // Garantizar que exista el directorio destino 'img/'
            if (!file_exists('img')) {
                mkdir('img', 0777, true);
            }

            // Validar peso de imagen (Máximo 5 MB)
            $max_size = 5 * 1024 * 1024;
            if ($_FILES['foto']['size'] > $max_size) {
                throw new Exception("La imagen pesa demasiado. El tamaño máximo permitido es 5 MB.");
            }

            $permitidos = ['jpg', 'jpeg', 'png', 'webp'];
            $nombre_archivo = $_FILES['foto']['name'];
            $ext = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));

            // Validar formato permitido
            if (in_array($ext, $permitidos)) {
                // Generar un nombre único e irrepetible para el archivo
                $nuevo_nombre_archivo = "prod_" . time() . "_" . uniqid() . "." . $ext;
                $carpeta_destino = 'img/' . $nuevo_nombre_archivo;

                // Mover la imagen desde el directorio temporal a la carpeta del proyecto
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $carpeta_destino)) {
                    $ruta_foto_final = $carpeta_destino;
                } else {
                    throw new Exception("Error al mover y guardar la imagen en el servidor.");
                }
            } else {
                throw new Exception("Formato no válido. Solo se permiten imágenes JPG, JPEG, PNG y WEBP.");
            }
        }

        // 5. Consulta Preparada para insertar en la BD
        $sql = "INSERT INTO productos (nombre, marca, origen, precio_costo, precio_venta, stock, caracteristicas, foto, categoria, subcategoria) 
                VALUES (:nombre, :marca, :origen, :precio_costo, :precio_venta, :stock, :caracteristicas, :foto, :categoria, :subcategoria)";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':nombre'          => $nombre,
            ':marca'           => $marca,
            ':origen'          => $origen,
            ':precio_costo'    => $precio_costo,
            ':precio_venta'    => $precio_venta,
            ':stock'           => $stock,
            ':caracteristicas' => $caracteristicas,
            ':foto'            => $ruta_foto_final,
            ':categoria'       => $categoria,
            ':subcategoria'    => $subcategoria
        ]);

        // Redirección exitosa con patrón GET
        header("Location: index.php?status=inserted"); 
        exit();

    } catch (Exception $e) {
        // Redirección en caso de error
        header("Location: index.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>