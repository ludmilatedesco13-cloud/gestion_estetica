<?php
include 'config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recibimos el string JSON con todos los productos del carrito (usando el name correcto)
    $json_productos = isset($_POST['json_productos_venta']) ? $_POST['json_productos_venta'] : (isset($_POST['json_productos']) ? $_POST['json_productos'] : '[]');
    
    // Lo decodificamos para transformarlo en un array de PHP
    $productos_carrito = json_decode($json_productos, true);

    if (empty($productos_carrito)) {
        header("Location: index.php?error=" . urlencode("El carrito de ventas está vacío."));
        exit();
    }

    try {
        // Transacción para asegurarnos de que se guarde TODO o NADA
        $pdo->beginTransaction();

        $total_general_operacion = 0;
        $items_a_procesar = [];

        // 1. Validamos primero que todos los productos tengan stock suficiente
        foreach ($productos_carrito as $item) {
            $producto_id = intval($item['id']);
            $cantidad = intval($item['cantidad']);

            $stmt_prod = $pdo->prepare("SELECT precio_venta, stock, nombre FROM productos WHERE id = :id");
            $stmt_prod->execute([':id' => $producto_id]);
            $producto = $stmt_prod->fetch(PDO::FETCH_ASSOC);

            if (!$producto) {
                throw new Exception("El producto con ID {$producto_id} no existe en el sistema.");
            }

            if ($producto['stock'] < $cantidad) {
                throw new Exception("Stock insuficiente para: " . $producto['nombre'] . ". Disponible: " . $producto['stock'] . " u.");
            }

            // Calculamos los costos acumulados
            $subtotal = $producto['precio_venta'] * $cantidad;
            $total_general_operacion += $subtotal;

            // Guardamos temporalmente los datos limpios
            $items_a_procesar[] = [
                'id' => $producto_id,
                'cantidad' => $cantidad
            ];
        }

        // 2. Insertamos la cabecera de la venta con el total acumulado
        $stmt_venta = $pdo->prepare("INSERT INTO ventas (total) VALUES (:total)");
        $stmt_venta->execute([':total' => $total_general_operacion]);

        // 3. Descontamos el stock de cada producto
        $stmt_actualizar_stock = $pdo->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :id");
        
        foreach ($items_a_procesar as $item_final) {
            $stmt_actualizar_stock->execute([
                ':cantidad' => $item_final['cantidad'],
                ':id'       => $item_final['id']
            ]);
        }

        // Confirmamos los cambios definitivos en MySQL
        $pdo->commit();

        // Volver al índice con éxito
        header("Location: index.php?venta=ok");
        exit();

    } catch (Exception $e) {
        // Si algo falló, cancelamos todo para no alterar la BD
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        header("Location: index.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>