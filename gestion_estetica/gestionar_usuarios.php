<?php
session_start();

// Validar que sea admin para entrar acá
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'config/conexion.php';

$mensaje_exito = "";
$error = "";

// Procesar la creación de un usuario nuevo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'vendedor';

    if (!empty($usuario) && !empty($password)) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, ?)");
            $stmt->execute([$usuario, $password_hash, $rol]);
            
            $mensaje_exito = "¡El usuario fue registrado con éxito!";
        } catch (PDOException $e) {
            $error = "Error al registrar el usuario (es probable que el nombre ya exista).";
        }
    } else {
        $error = "Por favor, completá todos los campos.";
    }
}

// Procesar la eliminación de un usuario
if (isset($_GET['eliminar'])) {
    $id_eliminar = intval($_GET['eliminar']);
    
    // Evitar que el admin se borre a sí mismo por accidente
    if ($id_eliminar == $_SESSION['id'] ?? 0) { 
        $error = "No podés eliminar tu propia cuenta de administrador activa.";
    } else {
        try {
            $stmt_del = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt_del->execute([$id_eliminar]);
            
            // Redirigir para limpiar la URL y mostrar la alerta de eliminado
            header("Location: gestionar_usuarios.php?status=deleted");
            exit();
        } catch (PDOException $e) {
            $error = "No se pudo eliminar el usuario.";
        }
    }
}

// Obtener la lista de usuarios actuales
try {
    $stmt_usuarios = $pdo->query("SELECT id, usuario, rol FROM usuarios ORDER BY id DESC");
    $lista_usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lista_usuarios = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - K-Beauty & Cosmética</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body style="background-color: #fffaf9; font-family: 'Poppins', sans-serif;">

    <div class="container" style="max-width: 850px; margin-top: 40px; margin-bottom: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 style="color: #7d6660; font-size: 1.8rem;">Gestión de Usuarios 👥</h1>
            <a href="index.php" style="text-decoration: none; color: #7d6660; font-weight: 600; background: #f3d1cb; padding: 8px 15px; border-radius: 10px; transition: background 0.2s;">← Volver al Inicio</a>
        </div>

        <!-- Formulario para nuevo usuario -->
        <div class="card" style="background: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <h2 style="font-size: 1.2rem; color: #7d6660; margin-bottom: 15px;">Añadir Nuevo Usuario 🔑</h2>
            <form action="gestionar_usuarios.php" method="POST">
                <input type="hidden" name="accion" value="crear">
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                        <label for="usuario">Nombre de Usuario</label>
                        <input type="text" id="usuario" name="usuario" placeholder="Ej: vendedora1" required style="width: 100%; padding: 10px; border: 1px solid #ddd1cf; border-radius: 8px;">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" placeholder="••••••••" required style="width: 100%; padding: 10px; border: 1px solid #ddd1cf; border-radius: 8px;">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 150px; margin-bottom: 0;">
                        <label for="rol">Rol</label>
                        <select id="rol" name="rol" style="width: 100%; padding: 10px; border: 1px solid #ddd1cf; border-radius: 8px; background: white;">
                            <option value="vendedor">Vendedor / Empleado</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-submit" style="margin-top: 15px; background-color: #dca397; border: none; padding: 10px 20px; color: white; border-radius: 8px; cursor: pointer; font-weight: bold;">Crear Usuario</button>
            </form>
        </div>

        <!-- Tabla de usuarios -->
        <div class="card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <h2 style="font-size: 1.2rem; color: #7d6660; margin-bottom: 15px;">Usuarios Registrados 📋</h2>
            <table class="venta-tabla" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #fffaf9; color: #7d6660;">
                        <th style="padding: 10px; border: 1px solid #f5ebe9;">ID</th>
                        <th style="padding: 10px; border: 1px solid #f5ebe9;">Usuario</th>
                        <th style="padding: 10px; border: 1px solid #f5ebe9;">Rol</th>
                        <th style="padding: 10px; border: 1px solid #f5ebe9; text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lista_usuarios as $u): ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #f5ebe9;"><?php echo $u['id']; ?></td>
                        <td style="padding: 10px; border: 1px solid #f5ebe9;"><strong><?php echo htmlspecialchars($u['usuario']); ?></strong></td>
                        <td style="padding: 10px; border: 1px solid #f5ebe9;">
                            <?php 
                                $rol_db = strtolower(trim($u['rol']));
                                if ($rol_db === 'admin') {
                                    $etiqueta = 'Administrador';
                                    $color_fondo = '#f3d1cb';
                                } else {
                                    $etiqueta = 'Vendedor / Empleado';
                                    $color_fondo = '#e2eff0';
                                }
                            ?>
                            <span style="background: <?php echo $color_fondo; ?>; color: #7d6660; padding: 4px 8px; border-radius: 6px; font-size: 0.85rem; font-weight: 600;">
                                <?php echo $etiqueta; ?>
                            </span>
                        </td>
                        <td style="padding: 10px; border: 1px solid #f5ebe9; text-align: center;">
                            <?php if ($u['usuario'] !== $_SESSION['usuario']): ?>
                                <a href="gestionar_usuarios.php?eliminar=<?php echo $u['id']; ?>" class="btn-icon" title="Eliminar Usuario" onclick="confirmarEliminacionUsuario(event, '<?php echo htmlspecialchars($u['usuario']); ?>')" style="color: #dca397; text-decoration: none; font-size: 1.2rem;">🗑️</a>
                            <?php else: ?>
                                <span style="font-size: 0.8rem; color: #aaa;" title="Tu usuario actual">Sesión activa</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // SweetAlert para confirmación de eliminación
        function confirmarEliminacionUsuario(event, nombreUsuario) {
            event.preventDefault();
            const urlEliminar = event.currentTarget.getAttribute('href');

            Swal.fire({
                title: '¿Dar de baja usuario?',
                text: `¿Estás segura de que querés eliminar el acceso a "${nombreUsuario}"?`,
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

        // Alertas automáticas según estado (creado o eliminado)
        <?php if (!empty($mensaje_exito)): ?>
            Swal.fire({
                icon: 'success',
                title: '¡Usuario Creado! ✨',
                text: '<?php echo $mensaje_exito; ?>',
                confirmButtonColor: '#dca397'
            });
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Atención ⚠️',
                text: '<?php echo $error; ?>',
                confirmButtonColor: '#dca397'
            });
        <?php endif; ?>

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status') === 'deleted') {
            Swal.fire({
                icon: 'success',
                title: '¡Usuario Eliminado! 🗑️',
                text: 'El acceso fue revocado correctamente.',
                confirmButtonColor: '#dca397'
            }).then(() => {
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }
    </script>
</body>
</html>