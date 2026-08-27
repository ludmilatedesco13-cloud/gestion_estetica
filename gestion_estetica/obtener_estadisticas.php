<?php
include 'config/conexion.php';

try {
    // 1. Recaudación y cantidad de ventas por mes
    $stmtVentas = $pdo->query("
        SELECT 
            DATE_FORMAT(fecha, '%Y-%m') as mes, 
            COUNT(*) as cantidad_operaciones,
            SUM(total) as total_recaudado 
        FROM ventas 
        GROUP BY mes 
        ORDER BY mes DESC
    ");
    $ventasMes = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3 style='color: #7d6660; border-bottom: 2px solid #f3d1cb; padding-bottom: 5px; margin-top: 0;'>💰 Recaudación por Mes</h3>";
    
    if (count($ventasMes) > 0) {
        echo "<ul style='list-style: none; padding: 0; margin-bottom: 20px;'>";
        foreach ($ventasMes as $row) {
            $total_formateado = number_format($row['total_recaudado'], 0, ',', '.');
            echo "<li style='padding: 8px 0; border-bottom: 1px solid #f5ebe9; display: flex; justify-content: space-between;'>
                    <span>📅 <strong>{$row['mes']}</strong> ({$row['cantidad_operaciones']} ventas)</span> 
                    <span style='color: #6b534c; font-weight: bold;'>\${$total_formateado}</span>
                  </li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: #8a736d; margin-bottom: 20px;'>No hay registros de ventas todavía.</p>";
    }

    // 2. Productos más vendidos / destacados
    echo "<h3 style='color: #7d6660; border-bottom: 2px solid #f3d1cb; padding-bottom: 5px;'>🌸 Productos más Destacados</h3>";

    $stmtProductos = $pdo->query("
        SELECT 
            p.nombre, 
            p.marca, 
            SUM(dv.cantidad) as total_vendidos 
        FROM detalle_venta dv
        JOIN productos p ON dv.producto_id = p.id
        GROUP BY p.id, p.nombre, p.marca
        ORDER BY total_vendidos DESC
        LIMIT 5
    ");
    $productosDestacados = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

    if (count($productosDestacados) > 0) {
        echo "<ul style='list-style: none; padding: 0; margin-top: 10px;'>";
        foreach ($productosDestacados as $prod) {
            echo "<li style='padding: 8px 0; border-bottom: 1px solid #f5ebe9; display: flex; justify-content: space-between;'>
                    <span>✨ <strong>{$prod['nombre']}</strong> <small style='color: #8a736d;'>({$prod['marca']})</small></span> 
                    <span style='color: #6b534c; font-weight: bold;'>{$prod['total_vendidos']} un. vendidas</span>
                  </li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='font-size: 0.9rem; color: #8a736d; margin-top: 10px;'>Todavía no hay suficientes ventas registradas con detalle para mostrar los productos destacados.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: #b81212;'>Error al consultar la base de datos: " . $e->getMessage() . "</p>";
}
?>