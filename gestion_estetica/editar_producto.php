<?php
ob_start(); // Previene errores de envío de cabeceras en la redirección
include 'config/conexion.php';

// 1. Obtener los datos actuales del producto seleccionado usando la columna 'id'
$producto = null;

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        header("Location: index.php?error=" . urlencode("El producto solicitado no existe."));
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// 2. Procesar la actualización cuando se envíe el formulario modificado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = intval($_POST['id']);
        $nombre = trim($_POST['nombre']);
        $marca = isset($_POST['marca']) ? trim($_POST['marca']) : '';
        $origen = isset($_POST['origen']) ? trim($_POST['origen']) : '';
        $categoria = isset($_POST['categoria']) ? trim($_POST['categoria']) : '';       
        $subcategoria = isset($_POST['subcategoria']) ? trim($_POST['subcategoria']) : ''; 
        $caracteristicas = isset($_POST['caracteristicas']) ? trim($_POST['caracteristicas']) : '';
        
        // Limpiamos los precios formateados para guardarlos correctamente en formato numérico
        $precio_costo_raw = str_replace(['.', ','], ['', '.'], $_POST['precio_costo']);
        $precio_venta_raw = str_replace(['.', ','], ['', '.'], $_POST['precio_venta']);

        $precio_costo = floatval(preg_replace('/[^\d.]/', '', $precio_costo_raw));
        $precio_venta = floatval(preg_replace('/[^\d.]/', '', $precio_venta_raw));
        $stock = intval($_POST['stock']);

        if (empty($nombre)) {
            throw new Exception("El nombre del producto no puede estar vacío.");
        }

        $ruta_foto_final = $_POST['foto_actual'];

        // Procesar subida de nueva foto si se adjunta
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $permitidos = ['jpg', 'jpeg', 'png', 'webp'];
            $nombre_archivo = $_FILES['foto']['name'];
            $ext = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));

            if (in_array($ext, $permitidos)) {
                $carpeta_destino = "img/";
                
                if (!file_exists($carpeta_destino)) {
                    mkdir($carpeta_destino, 0777, true);
                }

                $nuevo_nombre = time() . "_" . preg_replace("/[^a-zA-Z0-9]/", "", $nombre) . "." . $ext;
                $ruta_completa = $carpeta_destino . $nuevo_nombre;

                if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_completa)) {
                    // Borramos la foto anterior si no es la foto por defecto
                    if (!empty($_POST['foto_actual']) && $_POST['foto_actual'] !== 'img/producto_defecto.jpg' && file_exists($_POST['foto_actual'])) {
                        @unlink($_POST['foto_actual']);
                    }
                    $ruta_foto_final = $ruta_completa;
                }
            } else {
                throw new Exception("Formato de imagen no válido. Formatos permitidos: JPG, JPEG, PNG o WEBP.");
            }
        }

        // Consulta de actualización en la base de datos
        $sql = "UPDATE productos SET 
                    nombre = ?, 
                    marca = ?, 
                    origen = ?, 
                    precio_costo = ?, 
                    precio_venta = ?, 
                    stock = ?, 
                    categoria = ?, 
                    subcategoria = ?, 
                    caracteristicas = ?, 
                    foto = ? 
                WHERE id = ?";
                
        $stmt_update = $pdo->prepare($sql);
        
        $stmt_update->execute([
            $nombre, 
            $marca, 
            $origen, 
            $precio_costo, 
            $precio_venta, 
            $stock, 
            $categoria, 
            $subcategoria, 
            $caracteristicas, 
            $ruta_foto_final, 
            $id
        ]);

        // Redirección con estado de actualización exitosa
        header("Location: index.php?status=updated");
        exit();

    } catch (Exception $e) {
        header("Location: index.php?error=" . urlencode("Error al actualizar: " . $e->getMessage()));
        exit();
    }
}

// Lista de categorías para el selector
$lista_categorias = [
    "Aromaterapia", "Capilar", "Corporales", "Cosmética Coreana", "Cursos online", 
    "Faciales", "Insumos", "Kits", "Maquillaje", "Perfumes", "Promociones", 
    "Suplementos", "Tratamientos Profesionales"
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cosmético - Gestión de Inventario</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
    <header class="main-header">
        <h1>Modificar Cosmético 🌸</h1>
    </header>

    <div class="container">
        <section class="card form-section">
            <h2>Editar Datos de: <?php echo htmlspecialchars($producto['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h2>
            
            <form action="editar_producto.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo intval($producto['id'] ?? 0); ?>">
                <input type="hidden" name="foto_actual" value="<?php echo htmlspecialchars($producto['foto'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label for="nombre">Nombre del Producto</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($producto['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="form-group-row">
                    <div class="form-group">
                        <label for="marca">Marca</label>
                        <input type="text" id="marca" name="marca" value="<?php echo htmlspecialchars($producto['marca'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="origen">Origen</label>
                        <select id="origen" name="origen">
                            <option value="Coreano (K-Beauty)" <?php if(($producto['origen'] ?? '') === 'Coreano (K-Beauty)') echo 'selected'; ?>>Coreano (K-Beauty)</option>
                            <option value="Nacional/Otro" <?php if(($producto['origen'] ?? '') === 'Nacional/Otro') echo 'selected'; ?>>Nacional / Otro</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-row">
                    <div class="form-group">
                        <label for="categoria">Categoría</label>
                        <select id="categoria" name="categoria" required>
                            <option value="">-- Seleccionar Categoría --</option>
                            <?php foreach ($lista_categorias as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>" <?php if(($producto['categoria'] ?? '') === $cat) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="subcategoria">Subcategoría</label>
                        <select id="subcategoria" name="subcategoria" required>
                            <option value="">-- Seleccionar Subcategoría --</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="caracteristicas">Características e Información del Producto</label>
                    <textarea id="caracteristicas" name="caracteristicas" rows="4" placeholder="Ej: Componentes activos, tipo de piel ideal, modo de uso..."><?php echo htmlspecialchars($producto['caracteristicas'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="form-group-row">
                    <div class="form-group">
                        <label for="precio_costo">Precio Costo ($)</label>
                        <input type="text" id="precio_costo" name="precio_costo" value="<?php echo number_format($producto['precio_costo'] ?? 0, 0, ',', '.'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="precio_venta">Precio Venta ($)</label>
                        <input type="text" id="precio_venta" name="precio_venta" value="<?php echo number_format($producto['precio_venta'] ?? 0, 0, ',', '.'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="stock">Stock</label>
                        <input type="number" id="stock" name="stock" value="<?php echo intval($producto['stock'] ?? 0); ?>" min="0" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label for="foto">Foto del Producto (Opcional)</label>
                    <?php if (!empty($producto['foto']) && file_exists($producto['foto'])): ?>
                        <div style="margin-bottom: 10px;">
                            <small style="display:block; margin-bottom:5px; color:#555;">Foto actual:</small>
                            <img src="<?php echo htmlspecialchars($producto['foto'], ENT_QUOTES, 'UTF-8'); ?>" alt="Vista previa" style="max-width: 120px; max-height: 120px; border-radius: 8px; border: 1px solid #ddd; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="foto" name="foto" accept="image/*">
                    <small style="color: #666; display: block; margin-top: 4px;">Dejar vacío si no deseas cambiar la imagen actual.</small>
                </div>

                <button type="submit" class="btn-submit" style="margin-top: 20px;">Guardar Cambios</button>
                <a href="index.php" style="display: block; text-align: center; margin-top: 15px; color: #7d6660; font-weight: 600; text-decoration: none;">Volver al Panel</a>
            </form>
        </section>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Variables de valores guardados en la BD
            const categoriaActual = "<?php echo addslashes($producto['categoria'] ?? ''); ?>";
            const subcategoriaActual = "<?php echo addslashes($producto['subcategoria'] ?? ''); ?>";

            // Mapa dinámico de categorías y subcategorías
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

            const selectCategoria = document.getElementById("categoria");
            const selectSubcategoria = document.getElementById("subcategoria");

            function actualizarSubcategorias(categoriaSeleccionada, preseleccionarValue = "") {
                selectSubcategoria.innerHTML = '<option value="">-- Seleccionar Subcategoría --</option>';
                
                if (categoriaSeleccionada && subcategoriasPorCategoria[categoriaSeleccionada]) {
                    subcategoriasPorCategoria[categoriaSeleccionada].forEach(function (sub) {
                        const option = document.createElement("option");
                        option.value = sub;
                        option.textContent = sub;
                        
                        if (sub === preseleccionarValue) {
                            option.selected = true;
                        }
                        
                        selectSubcategoria.appendChild(option);
                    });
                }
            }

            selectCategoria.addEventListener("change", function () {
                actualizarSubcategorias(this.value);
            });

            if (categoriaActual) {
                actualizarSubcategorias(categoriaActual, subcategoriaActual);
            }

            // Máscara y limpieza de precios
            const inputCosto = document.getElementById("precio_costo");
            const inputVenta = document.getElementById("precio_venta");

            function aplicarMascaraMoneda(input) {
                if (!input) return;
                input.addEventListener("focus", function () {
                    input.value = input.value.replace(/\./g, "");
                });
                input.addEventListener("blur", function () {
                    let valor = input.value.replace(/\D/g, "");
                    if (valor) {
                        input.value = new Intl.NumberFormat("es-AR").format(parseInt(valor, 10));
                    }
                });
            }

            aplicarMascaraMoneda(inputCosto);
            aplicarMascaraMoneda(inputVenta);

            document.querySelector("form").addEventListener("submit", function() {
                if (inputCosto) inputCosto.value = inputCosto.value.replace(/\./g, "");
                if (inputVenta) inputVenta.value = inputVenta.value.replace(/\./g, "");
            });
        });
    </script>
</body>
</html>