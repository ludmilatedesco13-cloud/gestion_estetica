<?php
include 'config/conexion.php';

// Definimos el nombre del archivo con extensión .csv
$filename = "reporte_ventas_kbeauty_" . date('Y-m-d') . ".csv";

// Cabeceras HTTP para forzar la descarga como CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Abrimos la salida estándar para escribir el CSV
$output = fopen('php://output', 'w');

// Agregamos el BOM para que reconozca tildes y eñes correctamente en Excel/Sheets
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

try {
    // 1. TÍTULO Y RECAUDACIÓN POR MES
    fputcsv($output, ['RECAUDACION POR MES']);
    fputcsv($output, ['Mes', 'Cantidad de Operaciones', 'Total Recaudado ($)']);

    $stmtVentas = $pdo->query("
        SELECT 
            DATE_FORMAT(fecha, '%Y-%m') as mes, 
            COUNT(*) as cantidad_operaciones,
            SUM(total) as total_recaudado 
        FROM ventas 
        GROUP BY mes 
        ORDER BY mes DESC
    ");
    
    while ($row = $stmtVentas->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['mes'], 
            $row['cantidad_operaciones'], 
            number_format($row['total_recaudado'], 2, ',', '.')
        ]);
    }

    // Espacio en blanco entre secciones
    fputcsv($output, []);
    fputcsv($output, []);

    // 2. PRODUCTOS MÁS VENDIDOS
    fputcsv($output, ['PRODUCTOS MAS VENDIDOS']);
    fputcsv($output, ['Producto', 'Marca', 'Unidades Vendidas']);

    $stmtProductos = $pdo->query("
        SELECT 
            p.nombre, 
            p.marca, 
            SUM(dv.cantidad) as total_vendidos 
        FROM detalle_venta dv
        JOIN productos p ON dv.producto_id = p.id
        GROUP BY p.id, p.nombre, p.marca
        ORDER BY total_vendidos DESC
    ");
    
    while ($prod = $stmtProductos->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $prod['nombre'], 
            ($prod['marca'] ?? '-'), 
            $prod['total_vendidos']
        ]);
    }

} catch (PDOException $e) {
    fputcsv($output, ['Error al exportar los datos: ' . $e->getMessage()]);
}

fclose($output);
exit();
?>