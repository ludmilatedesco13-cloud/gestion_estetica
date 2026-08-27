<?php
// 1. Manejo de sesiones y validación de autenticación
session_start();

if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit();
}

$rol_usuario = $_SESSION['rol']; // "admin" o "empleado"
$es_admin = ($rol_usuario === 'admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K-Beauty & Cosmética - Gestión de Inventario</title>
    
    <link rel="stylesheet" href="css/estilos.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .btn-icon { font-size: 1.2rem; text-decoration: none; margin-right: 8px; display: inline-block; transition: transform 0.2s; cursor: pointer; }
        .btn-icon:hover { transform: scale(1.2); }
        .venta-tabla { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 15px; }
        .venta-tabla th, .venta-tabla td { border: 1px solid #f5ebe9; padding: 10px; text-align: left; }
        .venta-tabla th { background-color: #fffaf9; color: #7d6660; }
        .total-box { font-size: 1.4rem; font-weight: 600; color: #6b534c; text-align: right; margin-top: 10px; padding: 10px; background: #fffaf9; border-radius: 8px; }
        .row-agregar { display: flex; gap: 10px; align-items: flex-end; margin-bottom: 15px; flex-wrap: wrap; }
        .btn-agregar-lista { background-color: #dca397; color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: bold; transition: background 0.3s; width: 100%; }
        .btn-agregar-lista:hover { background-color: #c98e82; }

        /* Navigation Header Bar */
        .main-header { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 25px 15px; }
        .header-nav { margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
        .header-nav a { color: #7d6660; text-decoration: none; font-weight: 600; font-size: 0.9rem; padding: 6px 14px; border-radius: 15px; background-color: #fffaf9; border: 1px solid #f3d1cb; transition: all 0.2s; }
        .header-nav a:hover { background-color: #f3d1cb; color: #fff; }
        
        /* Modal para la lupita */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); justify-content: center; align-items: center; padding: 15px; box-sizing: border-box; }
        .modal-content { background-color: white; padding: 20px; border-radius: 12px; max-width: 450px; width: 100%; box-shadow: 0 4px 15px rgba(0,0,0,0.2); position: relative; text-align: center; }
        .close-modal { position: absolute; top: 10px; right: 15px; font-size: 1.5rem; cursor: pointer; color: #7d6660; }
        .modal-img { max-width: 150px; height: auto; border-radius: 8px; margin-bottom: 15px; background: #eee; display: block; margin-left: auto; margin-right: auto; object-fit: cover; }

        /* Contenedor con scroll para las características */
        .modal-scroll-caracteristicas {
            width: 100%;
            max-height: 140px;
            overflow-y: auto;
            background-color: #fcf9f8;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #e6dbd8;
            box-sizing: border-box;
            text-align: left;
            margin-top: 8px;
            font-size: 0.95rem;
            line-height: 1.5;
            color: #4a3f3c;
            white-space: pre-line;
        }

        .modal-scroll-caracteristicas::-webkit-scrollbar { width: 6px; }
        .modal-scroll-caracteristicas::-webkit-scrollbar-thumb { background-color: #7d6660; border-radius: 10px; }
        .modal-scroll-caracteristicas::-webkit-scrollbar-track { background: #f1f1f1; }

        .badge-categoria { background-color: #f3d1cb; color: #7d6660; padding: 4px 8px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; display: inline-block; }
        .subcategoria-texto { color: #8a736d; font-size: 0.8rem; display: block; margin-top: 2px; }

        /* Estilos adaptativos para el resumen del negocio */
        .resumen-grid {
            display: flex;
            justify-content: space-around;
            text-align: center;
            gap: 15px;
        }
        .resumen-item {
            flex: 1;
            padding: 10px;
        }
        .resumen-item.mid {
            border-left: 1px solid #f5ebe9;
            border-right: 1px solid #f5ebe9;
        }

        @media (max-width: 600px) {
            .row-agregar {
                flex-direction: column;
                align-items: stretch;
            }
            .resumen-grid {
                flex-direction: column;
            }
            .resumen-item.mid {
                border-left: none;
                border-right: none;
                border-top: 1px solid #f5ebe9;
                border-bottom: 1px solid #f5ebe9;
            }
        }
    </style>
</head>
<body>

<header class="main-header">
    
    <!-- Botón de salir posicionado arriba a la derecha -->
    <a href="logout.php" class="btn-logout-pill" title="Cerrar Sesión">
        <span class="icon-power">⏻</span> Salir
    </a>

    <h1>Sistema de Gestión - Estética & Cosmética 🌸</h1>

    <p style="color: #7d6660; margin-top: 5px; font-size: 0.95rem;">
        ¡Hola, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong>!
    </p>

    <nav class="header-nav">
        
    <a href="#sec-venta">🛍️Nueva Venta</a>
    <a href="#sec-inventario">📦Inventario</a>

    <?php if ($es_admin): ?>
        <a href="#sec-resumen">📈Resumen del Negocio</a>
        <a href="#sec-nuevo-producto">💄Nuevo Producto</a>
        <a href="#" id="btnCalendario">📅 Reporte Mensual</a>
        <a href="gestionar_usuarios.php">👥 Usuarios</a>
    <?php endif; ?>
    </nav>
</header>

    <div class="container">
        
        <?php
        include 'config/conexion.php';

        $cant_ventas = 0;
        $caja_total_formateada = "0";
        $sin_stock = 0;

        try {
           $stmt_stats = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM ventas) as total_ventas,
        (SELECT SUM(total) FROM ventas) as caja_total,
        (SELECT COUNT(*) FROM productos WHERE stock = 0) as sin_stock
");
            $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

            $cant_ventas = $stats['total_ventas'] ?? 0;
            $caja_total = $stats['caja_total'] ?? 0;
            $caja_total_formateada = number_format($caja_total, 0, ',', '.');
            $sin_stock = $stats['sin_stock'] ?? 0;
        } catch (PDOException $e) {
            // Manejo silencioso en el resumen
        }

        $color_alerta = $sin_stock > 0 ? '#b81212' : '#dca397';
        ?>

        <?php if ($es_admin): ?>
        <section id="sec-resumen" class="card stats-section" style="background-color: #fffaf9; border-color: #f3d1cb;">
            <h2>Resumen del Negocio 📈</h2>
            <div class="resumen-grid">
                <div class="resumen-item">
                    <p style="font-size: 0.9rem; color: #7d6660; font-weight: 600;">Total de Ventas Registradas</p>
                    <span style="font-size: 1.8rem; font-weight: 600; color: #7a9e9f;"><?php echo $cant_ventas; ?></span>
                </div>

                <div class="resumen-item mid">
                    <p style="font-size: 0.9rem; color: #7d6660; font-weight: 600;">Caja Total Recaudada</p>
                    <span style="font-size: 1.8rem; font-weight: 600; color: #6b534c;">$<?php echo $caja_total_formateada; ?></span>
                </div>

                <div class="resumen-item">
                    <p style="font-size: 0.9rem; color: #7d6660; font-weight: 600;">Productos Sin Stock</p>
                    <span style="font-size: 1.8rem; font-weight: 600; color: <?php echo $color_alerta; ?>;"><?php echo $sin_stock; ?></span>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section id="sec-venta" class="card sales-section">
            <h2>Registrar Nueva Venta 🛍️</h2>
            <form action="registrar_venta.php" method="POST" id="form-ventas-multiples">
                <div class="row-agregar">
                    <div class="form-group" style="flex: 2; margin-bottom: 0;">
                        <label for="producto_buscar">Buscar Producto (Nombre o marca)</label>
                        <input type="text" id="producto_buscar" list="lista_productos" placeholder="Escribí para buscar... Ej: COSRX" autocomplete="off">
                    </div>
                    <div class="form-group" style="flex: 1; margin-bottom: 0;">
                        <label for="cantidad">Cantidad</label>
                        <input type="number" id="cantidad" min="1" value="1">
                    </div>
                    <div style="flex: 0 1 auto; align-self: flex-end;">
                        <button type="button" id="btn-agregar-item" class="btn-agregar-lista">Añadir</button>
                    </div>
                </div>

                <datalist id="lista_productos">
                    <?php
                    try {
                        $stmt_prod = $pdo->query("SELECT id, nombre, marca, precio_venta, stock FROM productos WHERE stock > 0");
                        while ($prod = $stmt_prod->fetch(PDO::FETCH_ASSOC)) {
                            $marca_texto = !empty($prod['marca']) ? " ({$prod['marca']})" : "";
                            $nombre_option = htmlspecialchars($prod['nombre'] . $marca_texto, ENT_QUOTES, 'UTF-8');
                            echo "<option value='{$nombre_option}' data-id='{$prod['id']}' data-stock='{$prod['stock']}' data-precio='{$prod['precio_venta']}'></option>";
                        }
                    } catch (PDOException $e) {
                        echo "<option value='Error al cargar productos'></option>";
                    }
                    ?>
                </datalist>

                <div class="table-responsive">
                    <table class="venta-tabla" id="tabla-carrito">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio Unitario</th>
                                <th>Cantidad</th>
                                <th>Total</th>
                                <th>Quitar</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="total-box">
                    Total Operación: $<span id="monto-total-venta">0</span>
                </div>

                <input type="hidden" id="json_productos_venta" name="json_productos" required>
                <button type="submit" class="btn-submit" style="background-color: #dca397; margin-top: 15px;">Finalizar y Registrar Venta</button>
            </form>
        </section>

        <?php if ($es_admin): ?>
        <section id="sec-nuevo-producto" class="card form-section">
            <h2>Registrar Nuevo Cosmético 💄</h2>
            <form action="guardar_producto.php" method="POST" enctype="multipart/form-data" id="form-nuevo-producto">
                
                <div class="form-group-row">
                    <div class="form-group">
                        <label for="nombre">Nombre del Producto</label>
                        <input type="text" id="nombre" name="nombre" placeholder="Ej: Protector Solar Aloe Soothing" required>
                    </div>
                    <div class="form-group">
                        <label for="marca">Marca</label>
                        <input type="text" id="marca" name="marca" placeholder="Ej: COSRX">
                    </div>
                </div>
                
                 <div class="form-group-row">
                    <div class="form-group">
                        <label for="origen">Origen</label>
                        <select id="origen" name="origen" required>
                            <option value="Nacional / Otro">Nacional / Otro</option>
                            <option value="Coreano">Coreano</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-row">
                    <div class="form-group">
                        <label for="categoria">Categoría</label>
                        <select id="categoria" name="categoria" required onchange="actualizarSubcategorias()">
                            <option value="">-- Seleccionar Categoría --</option>
                            <option value="Aromaterapia">Aromaterapia</option>
                            <option value="Capilar">Capilar</option>
                            <option value="Corporales">Corporales</option>
                            <option value="Cosmética Coreana">Cosmética Coreana</option>
                            <option value="Cursos online">Cursos online</option>
                            <option value="Faciales">Faciales</option>
                            <option value="Insumos">Insumos</option>
                            <option value="Kits">Kits</option>
                            <option value="Maquillaje">Maquillaje</option>
                            <option value="Perfumes">Perfumes</option>
                            <option value="Promociones">Promociones</option>
                            <option value="Suplementos">Suplementos</option>
                            <option value="Tratamientos Profesionales">Tratamientos Profesionales</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subcategoria">Subcategoría</label>
                        <select id="subcategoria" name="subcategoria" required>
                            <option value="">-- Primero elegí una categoría --</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-row">
                    <div class="form-group">
                        <label for="precio_costo">Precio Costo ($)</label>
                        <input type="number" step="0.01" min="0" id="precio_costo" name="precio_costo" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label for="precio_venta">Precio Venta ($)</label>
                        <input type="number" step="0.01" min="0" id="precio_venta" name="precio_venta" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label for="stock">Stock</label>
                        <input type="number" min="0" id="stock" name="stock" placeholder="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="caracteristicas">Características / Descripción detallada</label>
                    <textarea id="caracteristicas" name="caracteristicas" rows="3" placeholder="Ej: Hidratante, ideal para pieles sensibles, contiene extracto de centella asiática..." style="width: 100%; padding: 14px 18px; border: 1px solid #ddd1cf; border-radius: 10px; font-family: 'Poppins', sans-serif; resize: vertical; outline: none; font-size: 1rem;"></textarea>
                </div>

                <div class="form-group">
                    <label for="foto">Foto del Producto</label>
                    <input type="file" id="foto" name="foto" accept="image/*" style="border: none; padding: 5px 0;">
                </div>

                <button type="submit" class="btn-submit">Guardar Producto</button>
            </form>
        </section>
        <?php endif; ?>

        <section id="sec-inventario" class="card table-section">
            <h2>Inventario Actual 📦</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Marca</th>
                            <th>Clasificación</th>
                            <th>Precio Venta</th>
                            <th>Stock</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT * FROM productos ORDER BY id DESC");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $precio_formateado = number_format($row['precio_venta'], 0, ',', '.');
                                
                                $caracteristicas = !empty($row['caracteristicas']) ? $row['caracteristicas'] : "Sin descripción disponible para este cosmético.";
                                $foto_url = !empty($row['foto']) ? htmlspecialchars($row['foto'], ENT_QUOTES, 'UTF-8') : "img/producto_defecto.jpg";
                                
                                $cat_mostrar = !empty($row['categoria']) ? htmlspecialchars($row['categoria'], ENT_QUOTES, 'UTF-8') : "General";
                                $sub_mostrar = !empty($row['subcategoria']) ? htmlspecialchars($row['subcategoria'], ENT_QUOTES, 'UTF-8') : "-";

                                $nombre_esc = htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8');
                                $marca_esc = htmlspecialchars($row['marca'], ENT_QUOTES, 'UTF-8');
                                $caract_esc = htmlspecialchars($caracteristicas, ENT_QUOTES, 'UTF-8');

                                echo "<tr>";
                                echo "<td>{$row['id']}</td>";
                                echo "<td><strong>{$nombre_esc}</strong></td>";
                                echo "<td>{$marca_esc}</td>";
                                echo "<td><span class='badge-categoria'>{$cat_mostrar}</span><span class='subcategoria-texto'>{$sub_mostrar}</span></td>";
                                echo "<td>\${$precio_formateado}</td>";
                                echo "<td>{$row['stock']} u.</td>";
                                echo "<td>
                                        <a href='#' class='btn-icon btn-ver' title='Ver Detalles' 
                                        data-nombre='{$nombre_esc}' 
                                        data-marca='{$marca_esc}' 
                                        data-precio='{$precio_formateado}' 
                                        data-stock='{$row['stock']}' 
                                        data-info='{$caract_esc}' 
                                        data-foto='{$foto_url}'>🔎</a>";
                                
                                if ($es_admin) {
                                    echo "<a href='editar_producto.php?id={$row['id']}' class='btn-icon' title='Editar' style='color: #7a9e9f;'>✏️</a>
                                          <a href='eliminar_producto.php?id={$row['id']}' class='btn-icon' title='Eliminar' onclick='confirmarEliminacion(event, \"{$nombre_esc}\")' style='color: #dca397;'>🗑️</a>";
                                }

                                echo "</td>";
                                echo "</tr>";
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='7' style='text-align:center;'>Error al cargar los productos del inventario.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div id="modal-detalles" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="btn-close-modal">&times;</span>
            <h2 id="modal-titulo-prod" style="font-size: 1.3rem; margin-bottom: 15px;">Nombre Producto</h2>
            <img id="modal-img-prod" src="" alt="Foto del Producto" class="modal-img">
            <p><strong>Marca:</strong> <span id="modal-marca-prod"></span></p>
            <p><strong>Precio Venta:</strong> $<span id="modal-precio-prod"></span></p>
            <p><strong>Stock Disponible:</strong> <span id="modal-stock-prod"></span> u.</p>
            <hr style="border: 0; border-top: 1px solid #f5ebe9; margin: 15px 0;">
            <p style="text-align: left; margin-bottom: 0;"><strong>Características:</strong></p>
            
            <div id="modal-info-prod" class="modal-scroll-caracteristicas"></div>
        </div>
    </div>

    <div id="modalCalendario" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 550px; text-align: left;">
        <span class="close-modal" id="cerrarModalCalendario">&times;</span>
        <h2 style="font-size: 1.3rem; margin-bottom: 15px; text-align: center;">📊 Estadísticas y Reportes Mensuales</h2>
        
        <!-- Botón de Descarga Excel -->
        <div style="margin-bottom: 15px; text-align: right;">
            <a href="exportar_excel.php" target="_blank" style="background-color: #a38883; color: white; padding: 8px 12px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 0.85rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: inline-block;">
                📊 Descargar Reporte en Excel
            </a>
        </div>

        <div id="resultadoEstadisticas">
            <p style="text-align: center;">Cargando estadísticas...</p>
        </div>
    </div>
</div>

    <script>
        // Categorías y subcategorías
        const subcategoriasPorCategoria = {
            "Aromaterapia": ["Aceites esenciales", "Aromatizantes de ambiente", "Esencias para hornillo"],
            "Capilar": ["Capilar Productos"],
            "Corporales": ["Cremas Corporales", "Geles Corporales", "Manos y Pies", "Tratamientos Corporales"],
            "Cosmética Coreana": ["General K-Beauty"],
            "Cursos online": ["General Cursos"],
            "Faciales": ["Accesorios", "Ácidos", "Contorno de Ojos", "Cremas Humectantes y Emulsiones", "Despigmentantes", "Exosomas", "Geles y Sueros", "Labios", "Limpieza y Exfoliación", "Lociones y Brumas", "Máscaras Faciales", "Óleos y Aceites", "Protección Solar", "Renovadores", "Sérum y Boosters"],
            "Insumos": ["Accesorios", "Básicos de Gabinete", "Jeringas y Agujas"],
            "Kits": ["Kits Productos"],
            "Maquillaje": ["Accesorios", "Bases", "Correctores", "Fijadores y Polvos", "Labiales y gloss", "Máscaras de Pestañas", "Rubores, Contornos e Iluminadores"],
            "Perfumes": ["Body splash", "Femeninos"],
            "Promociones": ["General Promos"],
            "Suplementos": ["Suplementos Productos"],
            "Tratamientos Profesionales": ["Ampollas y Concentrados", "Microneedling", "Radiofrecuencia"]
        };

        function actualizarSubcategorias() {
            const selectCategoria = document.getElementById("categoria");
            const selectSubcategoria = document.getElementById("subcategoria");
            if (!selectCategoria || !selectSubcategoria) return;
            
            const categoriaSeleccionada = selectCategoria.value;
            selectSubcategoria.innerHTML = '<option value="">-- Seleccionar Subcategoría --</option>';

            if (categoriaSeleccionada && subcategoriasPorCategoria[categoriaSeleccionada]) {
                subcategoriasPorCategoria[categoriaSeleccionada].forEach(sub => {
                    const option = document.createElement("option");
                    option.value = sub;
                    option.textContent = sub;
                    selectSubcategoria.appendChild(option);
                });
            }
        }

        // Eliminación con SweetAlert2
        function confirmarEliminacion(event, nombreProducto) {
            event.preventDefault();
            const urlEliminar = event.currentTarget.getAttribute('href');

            Swal.fire({
                title: '¿Eliminar producto?',
                text: `¿Estás seguro de que deseas eliminar "${nombreProducto}" del inventario?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dca397',
                cancelButtonColor: '#7d6660',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = urlEliminar;
                }
            });
        }

        // --- manejo del carrito de ventas ---
        let carritoVenta = [];

        function actualizarTablaCarrito() {
            const tbody = document.querySelector("#tabla-carrito tbody");
            const spanTotal = document.getElementById("monto-total-venta");
            const inputJson = document.getElementById("json_productos_venta");
            
            tbody.innerHTML = "";
            let totalGeneral = 0;

            carritoVenta.forEach((item, index) => {
                const totalItem = item.precio * item.cantidad;
                totalGeneral += totalItem;

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td><strong>${item.nombre}</strong></td>
                    <td>$${item.precio.toLocaleString('es-AR')}</td>
                    <td>${item.cantidad}</td>
                    <td>$${totalItem.toLocaleString('es-AR')}</td>
                    <td><a href="#" style="color: #dca397; text-decoration: none; font-weight: bold;" onclick="quitarDelCarrito(${index}); return false;">❌</a></td>
                `;
                tbody.appendChild(tr);
            });

            spanTotal.textContent = totalGeneral.toLocaleString('es-AR');
            inputJson.value = carritoVenta.length > 0 ? JSON.stringify(carritoVenta) : "";
        }

        function quitarDelCarrito(index) {
            carritoVenta.splice(index, 1);
            actualizarTablaCarrito();
        }

        // Inicialización tras cargar el DOM
        document.addEventListener("DOMContentLoaded", function() {
            
            // --- botón para añadir venta ---
            const btnAgregar = document.getElementById("btn-agregar-item");
            const inputBuscar = document.getElementById("producto_buscar");
            const inputCantidad = document.getElementById("cantidad");
            const formVenta = document.getElementById("form-ventas-multiples");

            // Evitar que el Enter en el input de búsqueda envíe todo el formulario
            if (inputBuscar) {
                inputBuscar.addEventListener("keydown", function(e) {
                    if (e.key === "Enter") {
                        e.preventDefault();
                        if (btnAgregar) btnAgregar.click();
                    }
                });
            }

            if (btnAgregar) {
                btnAgregar.addEventListener("click", function() {
                    const valorIngresado = inputBuscar.value.trim();
                    const cantidadIngresada = parseInt(inputCantidad.value, 10);
                    
                    if (!valorIngresado || isNaN(cantidadIngresada) || cantidadIngresada <= 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Atención',
                            text: 'Por favor, seleccioná un producto válido y una cantidad mayor a 0.',
                            confirmButtonColor: '#dca397'
                        });
                        return;
                    }

                    const datalist = document.getElementById("lista_productos");
                    const opcion = Array.from(datalist.options).find(opt => opt.value === valorIngresado);

                    if (!opcion) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Producto no encontrado',
                            text: 'Seleccioná un producto existente desplegado en la lista.',
                            confirmButtonColor: '#dca397'
                        });
                        return;
                    }

                    const id = parseInt(opcion.dataset.id, 10);
                    const stockDisponible = parseInt(opcion.dataset.stock, 10);
                    const precio = parseFloat(opcion.dataset.precio);

                    const itemExistente = carritoVenta.find(prod => prod.id === id);
                    const cantidadPrevios = itemExistente ? itemExistente.cantidad : 0;

                    if ((cantidadPrevios + cantidadIngresada) > stockDisponible) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stock insuficiente',
                            text: `No hay suficiente stock. Disponibles: ${stockDisponible} u. (Ya agregados: ${cantidadPrevios})`,
                            confirmButtonColor: '#dca397'
                        });
                        return;
                    }

                    if (itemExistente) {
                        itemExistente.cantidad += cantidadIngresada;
                    } else {
                        carritoVenta.push({
                            id: id,
                            nombre: valorIngresado,
                            precio: precio,
                            cantidad: cantidadIngresada
                        });
                    }

                    actualizarTablaCarrito();

                    inputBuscar.value = "";
                    inputCantidad.value = 1;
                    inputBuscar.focus();
                });
            }

            if (formVenta) {
                formVenta.addEventListener("submit", function(e) {
                    if (carritoVenta.length === 0) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Carrito vacío',
                            text: 'Añadí al menos un producto a la lista antes de finalizar la venta.',
                            confirmButtonColor: '#dca397'
                        });
                    }
                });
            }

            // Manejo del Modal (Lupita)
            const modal = document.getElementById("modal-detalles");
            const btnClose = document.getElementById("btn-close-modal");

            document.querySelectorAll(".btn-ver").forEach(btn => {
                btn.addEventListener("click", function(e) {
                    e.preventDefault();
                    document.getElementById("modal-titulo-prod").textContent = this.dataset.nombre;
                    document.getElementById("modal-marca-prod").textContent = this.dataset.marca || 'N/A';
                    document.getElementById("modal-precio-prod").textContent = this.dataset.precio;
                    document.getElementById("modal-stock-prod").textContent = this.dataset.stock;
                    document.getElementById("modal-info-prod").textContent = this.dataset.info;
                    document.getElementById("modal-img-prod").src = this.dataset.foto;
                    modal.style.display = "flex";
                });
            });

            if (btnClose) {
                btnClose.addEventListener("click", function() {
                    modal.style.display = "none";
                });
            }

            window.addEventListener("click", function(e) {
                if (e.target === modal) {
                    modal.style.display = "none";
                }
            });

            // Control de Alertas Emergentes con SweetAlert2
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('status')) {
                const status = urlParams.get('status');
                if (status === 'created' || status === 'inserted' || status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Producto Guardado! ✨',
                        text: 'El cosmético fue registrado con éxito en el sistema.',
                        confirmButtonColor: '#dca397'
                    });
                } else if (status === 'updated') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Producto Modificado! ✏️',
                        text: 'El cosmético se actualizó correctamente en el inventario.',
                        confirmButtonColor: '#dca397'
                    });
                } else if (status === 'deleted') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminado! 🗑️',
                        text: 'El cosmético fue eliminado del inventario de forma segura.',
                        confirmButtonColor: '#dca397'
                    });
                } else if (status === 'error') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error ⚠️',
                        text: 'Ocurrió un problema al procesar la solicitud.',
                        confirmButtonColor: '#dca397'
                    });
                }
            }

            if (urlParams.has('error')) {
                const mensajeError = urlParams.get('error');
                Swal.fire({
                    icon: 'error',
                    title: 'Atención ⚠️',
                    text: mensajeError,
                    confirmButtonColor: '#dca397'
                });
            }

            if (urlParams.get('venta') === 'ok') {
                Swal.fire({
                    icon: 'success',
                    title: '¡Venta Registrada! 🎉',
                    text: 'La operación fue exitosa y el stock ha sido actualizado.',
                    confirmButtonColor: '#dca397'
                });
            }

            if (urlParams.has('venta') || urlParams.has('status') || urlParams.has('error')) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }

            // Modal Reporte Mensual
            const btnCalendario = document.getElementById("btnCalendario");
            const modalCalendario = document.getElementById("modalCalendario");
            const cerrarModalCalendario = document.getElementById("cerrarModalCalendario");
            const resultadoEstadisticas = document.getElementById("resultadoEstadisticas");

            if (btnCalendario) {
                btnCalendario.addEventListener("click", function(e) {
                    e.preventDefault();
                    modalCalendario.style.display = "flex";

                    fetch("obtener_estadisticas.php")
                        .then(response => response.text())
                        .then(data => {
                            resultadoEstadisticas.innerHTML = data;
                        })
                        .catch(error => {
                            resultadoEstadisticas.innerHTML = "<p style='color: red; text-align: center;'>Error al cargar las estadísticas.</p>";
                            console.error("Error:", error);
                        });
                });
            }

            if (cerrarModalCalendario) {
                cerrarModalCalendario.addEventListener("click", function() {
                    modalCalendario.style.display = "none";
                });
            }

            window.addEventListener("click", function(e) {
                if (e.target === modalCalendario) {
                    modalCalendario.style.display = "none";
                }
            });
        });
    </script>
</body>
</html>