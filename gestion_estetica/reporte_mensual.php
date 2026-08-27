<?php
session_start();
// Validación estricta: Solo administradores pueden ver los reportes detallados y por vendedor
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php?error=" . urlencode("Acceso no autorizado. Se requieren permisos de administrador."));
    exit();
}

include 'config/conexion.php';

// Obtener el mes y año seleccionados (por defecto el mes actual)
$mes_seleccionado = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$anio_seleccionado = isset($_GET['anio']) ? $_GET['anio'] : date('Y');

try {
    // 1. Resumen general del mes seleccionado
    $sql_resumen = "SELECT COUNT(id) as total_ventas, SUM(total) as recaudacion_total 
                    FROM ventas 
                    WHERE MONTH(fecha) = :mes AND YEAR(fecha) = :anio";
    $stmt_resumen = $pdo->prepare($sql_resumen);
    $stmt_resumen->execute([':mes' => $mes_seleccionado, ':anio' => $anio_seleccionado]);
    $resumen = $stmt_resumen->fetch(PDO::FETCH_ASSOC);

    // 2. Desglose de ventas por vendedor
    // (Asume que en tu tabla detalle o ventas guardas la relación; aquí unimos ventas con usuarios)
    $sql_vendedores = "SELECT u.nombre as vendedor, COUNT(v.id) as cantidad_ventas, SUM(v.total) as total_recaudado
                       FROM ventas v
                       LEFT JOIN usuarios u ON v.vendedor_id = u.id
                       WHERE MONTH(v.fecha) = :mes AND YEAR(v.fecha) = :anio
                       GROUP BY v.vendedor_id, u.nombre";
    $stmt_vendedores = $pdo->prepare($sql_vendedores);
    $stmt_vendedores->execute([':mes' => $mes_seleccionado, ':anio' => $anio_seleccionado]);
    $vendedores_data = $stmt_vendedores->fetchAll(PDO::FETCH_ASSOC);

    // 3. Ranking de productos más vendidos en el mes
    // (Ajustá los nombres de las tablas y columnas de detalles de venta si en tu base difieren, ej: detalle_ventas, producto_id, cantidad)
    $sql_productos = "SELECT p.nombre as producto, SUM(dv.cantidad) as total_unidades, SUM(dv.subtotal) as total_generado
                      FROM detalle_ventas dv
                      INNER JOIN ventas v ON dv.venta_id = v.id
                      INNER JOIN productos p ON dv.producto_id = p.id
                      WHERE MONTH(v.fecha) = :mes AND YEAR(v.fecha) = :anio
                      GROUP BY p.id, p.nombre
                      ORDER BY total_unidades DESC
                      LIMIT 10";
    $stmt_productos = $pdo->prepare($sql_productos);
    $stmt_productos->execute([':mes' => $mes_seleccionado, ':anio' => $anio_seleccionado]);
    $productos_top = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_db = "Error al consultar los datos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Mensual - K-Beauty System</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #fcf9f8; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1, h2 { color: #5a4033; }
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f0e6e2; padding-bottom: 15px; }
        .filter-form { display: flex; gap: 15px; background: #fdfaf8; padding: 15px; border-radius: 8px; margin-bottom: 25px; align-items: flex-end; border: 1px solid #f0e6e2; }
        .form-group { display: flex; flexDirection: column; }
        .form-group label { font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; color: #666; }
        select, button { padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; font-family: 'Poppins', sans-serif; }
        .btn-filtrar { background: #d4a373; color: white; border: none; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-filtrar:hover { background: #bc6c25; }
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card-metric { background: #fefae0; padding: 20px; border-radius: 8px; border-left: 5px solid #dda15e; }
        .card-metric h3 { margin: 0 0 5px 0; font-size: 0.9rem; color: #606c38; }
        .card-metric p { margin: 0; font-size: 1.5rem; font-weight: 600; color: #283618; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; font-size: 0.95rem; }
        th { background-color: #f7f1ed; color: #5a4033; font-weight: 600; }
        tr:hover { background-color: #fcf9f8; }
        .btn-volver { display: inline-block; padding: 8px 15px; background: #e0a96d; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin-bottom: 20px; }
        .btn-volver:hover { background: #b07d43; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-volver">← Volver al Panel</a>
        
        <div class="header-flex">
            <h1>📊 Reporte Mensual de Ventas 🌸</h1>
            <span>Admin: <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong></span>
        </div>

        <!-- Filtro por Mes y Año -->
        <form method="GET" action="reporte_mensual.php" class="filter-form">
            <div class="form-group">
                <label for="mes">Mes:</label>
                <select name="mes" id="mes">
                    <?php 
                    $meses = [
                        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
                        '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
                        '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
                    ];
                    foreach ($meses as $num => $nombre) {
                        $selected = ($mes_seleccionado == $num) ? 'selected' : '';
                        echo "<option value='$num' $selected>$nombre</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="anio">Año:</label>
                <select name="anio" id="anio">
                    <?php
                    $anio_actual = date('Y');
                    for ($a = $anio_actual; $a >= $anio_actual - 3; $a--) {
                        $selected = ($anio_seleccionado == $a) ? 'selected' : '';
                        echo "<option value='$a' $selected>$a</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn-filtrar">Generar Reporte</button>
        </form>

        <?php if (isset($error_db)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error_db); ?></p>
        <?php else: ?>

            <!-- Tarjetas de Resumen -->
            <div class="cards-grid">
                <div class="card-metric">
                    <h3>Total Recaudado</h3>
                    <p>$<?php echo number_format($resumen['recaudacion_total'] ?? 0, 2, ',', '.'); ?></p>
                </div>
                <div class="card-metric" style="border-left-color: #bc6c25; background: #faedcd;">
                    <h3>Ventas Realizadas</h3>
                    <p><?php echo number_format($resumen['total_ventas'] ?? 0, 0, ',', '.'); ?></p>
                </div>
            </div>

            <!-- Desglose por Vendedor -->
            <h2>👩‍💼 Rendimiento por Vendedor</h2>
            <table>
                <thead>
                    <tr>
                        <th>Vendedor</th>
                        <th>Cantidad de Ventas</th>
                        <th>Total Recaudado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($vendedores_data)): ?>
                        <?php foreach ($vendedores_data as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['vendedor'] ?? 'Sin asignar / General'); ?></td>
                                <td><?php echo $row['cantidad_ventas']; ?></td>
                                <td>$<?php echo number_format($row['total_recaudado'] ?? 0, 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align: center; color: #888;">No hay registros de ventas para este período.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Ranking de Productos Más Vendidos -->
            <h2>🏆 Top Productos Más Solicitados</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto (K-Beauty / Cosmética)</th>
                        <th>Unidades Vendidas</th>
                        <th>Ingresos Generados</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($productos_top)): ?>
                        <?php $pos = 1; foreach ($productos_top as $prod): ?>
                            <tr>
                                <td><strong>#<?php echo $pos++; ?></strong></td>
                                <td><?php echo htmlspecialchars($prod['producto']); ?></td>
                                <td><?php echo $prod['total_unidades']; ?> unidades</td>
                                <td>$<?php echo number_format($prod['total_generado'] ?? 0, 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; color: #888;">No hay datos de productos vendidos en este período.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </div>
</body>
</html>