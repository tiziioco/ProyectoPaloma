<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Paciente') {
    header("Location: login.php");
    exit();
}

$id_paciente = $_SESSION['id_usuario'];

// Procesar actualización rápida de stock si el usuario envía el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion_stock'])) {
    $id_med_edit = intval($_POST['id_medicamento']);
    $nuevo_stock = intval($_POST['nuevo_stock']);
    
    $sql_update = "UPDATE medicamento SET stock_actual = $nuevo_stock WHERE id_medicamento = $id_med_edit AND fk_id_usuario = $id_paciente";
    $conexion->query($sql_update);
    
    header("Location: mi_botiquin.php?msg=updated");
    exit();
}

// Traemos todos los medicamentos del abuelo ordenados alfabéticamente
$sql_botiquin = "SELECT id_medicamento, nombre_comercial, stock_actual, stock_minimo, horario FROM medicamento WHERE fk_id_usuario = $id_paciente ORDER BY nombre_comercial ASC";
$resultado_botiquin = $conexion->query($sql_botiquin);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Botiquín - MedStock</title>
    <style>
        :root {
            --bg-main: #ffffff;
            --bg-surface: #f8fafc;
            --bg-input: #ffffff;
            --neon-blue: #0ea5e9;
            --neon-glow: rgba(14, 165, 233, 0.1);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --emerald-green: #10b981;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background-color: var(--bg-main); color: var(--text-main); min-height: 100vh; display: flex; flex-direction: column; }
        
        header {
            background: #ffffff; padding: 18px 40px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 2px solid var(--neon-blue); box-shadow: 0 4px 15px var(--neon-glow); z-index: 10;
        }
        header h1 { color: var(--neon-blue); font-size: 1.6rem; font-weight: 800; letter-spacing: -0.5px; cursor: pointer; }
        .user-info { display: flex; align-items: center; gap: 20px; }
        .user-info span { font-size: 0.95rem; font-weight: 600; color: #334155; }
        .btn-back-menu { padding: 8px 16px; background: var(--bg-surface); color: var(--neon-blue); text-decoration: none; border: 1px solid var(--neon-blue); border-radius: 6px; font-weight: 700; font-size: 0.85rem; transition: 0.2s ease; }
        .btn-back-menu:hover { background: var(--neon-glow); transform: translateX(-2px); }

        main { flex: 1; padding: 40px 20px; max-width: 1100px; margin: 0 auto; width: 100%; display: flex; flex-direction: column; }
        
        .section-title-wrapper { margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .section-title-wrapper h2 { font-size: 1.8rem; font-weight: 800; color: var(--text-main); }
        .section-title-wrapper p { color: var(--text-muted); font-size: 0.95rem; margin-top: 2px; }

        .btn-add-medicine {
            padding: 12px 20px; background: var(--neon-blue); color: white; border: none; border-radius: 8px;
            font-weight: 700; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px;
            transition: background 0.2s; text-decoration: none;
        }
        .btn-add-medicine:hover { background: #0284c7; box-shadow: 0 4px 12px var(--neon-glow); }
        /* --- ESTILOS DE LA TABLA CLÍNICA DE MEDICAMENTOS --- */
        .table-container-card {
            background: #ffffff; border: 1px solid var(--border-color); border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02); overflow: hidden; margin-bottom: 25px;
        }

        .botiquin-table { width: 100%; border-collapse: collapse; text-align: left; }
        .botiquin-table th {
            background: var(--bg-surface); padding: 16px 20px; color: var(--text-muted);
            font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-color);
        }
        .botiquin-table td { padding: 18px 20px; border-bottom: 1px solid var(--border-color); font-size: 0.95rem; }
        .botiquin-table tr:last-child td { border-bottom: none; }
        .botiquin-table tr:hover td { background: rgba(248, 250, 252, 0.5); }

        .med-name-cell { font-weight: 700; color: var(--text-main); font-size: 1.05rem; }
        
        .pill-badge { font-size: 0.75rem; font-weight: 800; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; display: inline-block; }
        .badge-normal { background: rgba(16, 185, 129, 0.1); color: var(--emerald-green); border: 1px solid rgba(16, 185, 129, 0.2); }
        .badge-low { background: rgba(245, 158, 11, 0.1); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.2); }

        /* --- FORMULARIO DE ACCIÓN RÁPIDA DENTRO DE LA TABLA --- */
        .form-quick-stock { display: flex; align-items: center; gap: 8px; }
        .input-quick-stock {
            width: 70px; padding: 8px 10px; border: 1px solid var(--border-color); border-radius: 6px;
            font-family: monospace; font-size: 0.95rem; font-weight: 700; text-align: center; outline: none;
            transition: border-color 0.2s;
        }
        .input-quick-stock:focus { border-color: var(--neon-blue); box-shadow: 0 0 8px var(--neon-glow); }
        
        .btn-update-stock {
            padding: 8px 12px; background: transparent; border: 1px solid var(--neon-blue);
            color: var(--neon-blue); font-weight: 700; font-size: 0.8rem; border-radius: 6px;
            cursor: pointer; transition: all 0.2s;
        }
        .btn-update-stock:hover { background: var(--neon-blue); color: white; box-shadow: 0 2px 8px var(--neon-glow); }

        .btn-action-delete {
            padding: 8px 12px; background: transparent; border: 1px solid #ef4444;
            color: #ef4444; font-weight: 700; font-size: 0.8rem; border-radius: 6px;
            cursor: pointer; text-decoration: none; transition: all 0.2s;
        }
        .btn-action-delete:hover { background: #ef4444; color: white; box-shadow: 0 2px 8px rgba(239, 68, 68, 0.15); }

        /* --- COMPONENTE ALERTA FLOTANTE EXITO --- */
        .toast-msg-success {
            background: var(--emerald-green); color: white; padding: 12px 24px; border-radius: 8px;
            font-weight: 700; font-size: 0.95rem; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
            display: flex; align-items: center; gap: 10px; animation: slideInToast 0.3s cubic-bezier(0, 1, 0, 1);
        }

        @keyframes slideInToast {
            0% { transform: translateY(-10px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>

    <header>
        <h1 onclick="window.location.href='menu_paciente.php'">MedStock</h1>
        <div class="user-info">
            <span>👴 Paciente: <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            <a href="menu_paciente.php" class="btn-back-menu">◀ Volver al Menú</a>
        </div>
    </header>

    <main>
        <!-- Notificación de stock actualizado con éxito -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div class="toast-msg-success">
                <span>✔</span> El stock del medicamento se actualizó correctamente en la base de datos.
            </div>
        <?php endif; ?>

        <!-- Notificación de medicamento eliminado con éxito -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="toast-msg-success" style="background: #ef4444; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);">
                <span>🗑</span> El medicamento fue retirado del botiquín correctamente.
            </div>
        <?php endif; ?>

        <div class="section-title-wrapper">
            <div>
                <h2>Administrar mi Botiquín</h2>
                <p>Modificá las unidades disponibles, revisá tus alertas o carga nuevas dosis.</p>
            </div>
            <a href="nuevo_remedio.php" class="btn-add-medicine">
                <span>➕</span> Cargar Nuevo Medicamento
            </a>
        </div>

        <div class="table-container-card">
            <table class="botiquin-table">
                <thead>
                    <tr>
                        <th>Nombre Comercial</th>
                        <th>Horario de Toma</th>
                        <th>Estado de Alerta</th>
                        <th>Modificar Existencias</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($resultado_botiquin && $resultado_botiquin->num_rows > 0): ?>
                        <?php while($med = $resultado_botiquin->fetch_assoc()): 
                            $id_med = intval($med['id_medicamento']);
                            $stock = intval($med['stock_actual']);
                            $minimo = intval($med['stock_minimo']);
                            $es_critico = ($stock <= $minimo);
                        ?>
                            <tr>
                                <td class="med-name-cell"><?php echo htmlspecialchars($med['nombre_comercial']); ?></td>
                                <td>
                                    <span style="font-weight: 600; color: #334155;">
                                        ⏰ <?php echo (!empty($med['horario'])) ? date("H:i", strtotime($med['horario'])) : "11:11"; ?> hs
                                    </span>
                                </td>
                                <td>
                                    <?php if ($es_critico): ?>
                                        <span class="pill-badge badge-low">Poco Stock ⚠️</span>
                                    <?php else: ?>
                                        <span class="pill-badge badge-normal">Suficiente ✔</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Formulario en línea para actualizar el stock al toque sin salir de la página -->
                                    <form action="mi_botiquin.php" method="POST" class="form-quick-stock">
                                        <input type="hidden" name="id_medicamento" value="<?php echo $id_med; ?>">
                                        <input type="hidden" name="accion_stock" value="1">
                                        <input type="number" name="nuevo_stock" class="input-quick-stock" value="<?php echo $stock; ?>" min="0" required>
                                        <button type="submit" class="btn-update-stock">Guardar</button>
                                    </form>
                                </td>
                                <td>
                                    <!-- Botón directo para dar de baja un medicamento viejo -->
                                    <a href="eliminar_medicamento.php?id=<?php echo $id_med; ?>" class="btn-action-delete" onclick="return confirmarBajaMed('<?php echo htmlspecialchars($med['nombre_comercial']); ?>')">Eliminar</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px 0;">
                                No tenés medicamentos cargados en tu botiquín clínico en este momento.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <script>
        // Función interactiva para evitar que el paciente elimine una pastilla por error
        function confirmarBajaMed(nombreMedicamento) {
            return confirm("¿Estás seguro de que querés eliminar '" + nombreMedicamento + "' por completo de tu botiquín clínico?");
        }
    </script>
</body>
</html>
