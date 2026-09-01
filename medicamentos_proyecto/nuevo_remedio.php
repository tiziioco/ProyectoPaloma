<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Paciente') {
    header("Location: login.php");
    exit();
}

$id_paciente = $_SESSION['id_usuario'];
$error_msg = "";

// MOTOR DE INSERCIÓN REAL EN MYSQL
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_guardar_med'])) {
    $nombre_comercial = $conexion->real_escape_string($_POST['nombre_comercial']);
    $horario_toma = $conexion->real_escape_string($_POST['horario']);
    $stock_inicial = intval($_POST['stock_actual']);
    $stock_minimo = intval($_POST['stock_minimo']);

    if (!empty($nombre_comercial) && !empty($horario_toma)) {
        // Formateamos valores clínicos por defecto para las columnas requeridas de la entrega escolar
        $compuesto = "No especificado";
        $presentacion = "Pastilla";
        $fecha_vence = "2028-12-31";

        // Insertamos el medicamento incluyendo la nueva columna física de tiempo
        $sql_insert = "INSERT INTO medicamento (nombre_comercial, compuesto, presentacion, stock_actual, stock_minimo, fecha_vencimiento, fk_id_usuario, horario) 
                       VALUES ('$nombre_comercial', '$compuesto', '$presentacion', $stock_inicial, $stock_minimo, '$fecha_vence', $id_paciente, '$horario_toma')";
        
        if ($conexion->query($sql_insert)) {
            // Si guarda bien, volvemos al botiquín con un cartel de éxito
            header("Location: mi_botiquin.php?msg=updated");
            exit();
        } else {
            $error_msg = "Error de base de datos al guardar: " . $conexion->error;
        }
    } else {
        $error_msg = "Por favor, completa los campos obligatorios (Nombre y Horario).";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargar Medicamento - MedStock</title>
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
            --ef-red: #ef4444;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background-color: var(--bg-main); color: var(--text-main); min-height: 100vh; display: flex; flex-direction: column; }
        
        header {
            background: #ffffff; padding: 18px 40px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 2px solid var(--neon-blue); box-shadow: 0 4px 15px var(--neon-glow); z-index: 10;
        }
        header h1 { color: var(--neon-blue); font-size: 1.6rem; font-weight: 800; letter-spacing: -0.5px; cursor: pointer; }
        
        .btn-back-botiquin { padding: 8px 16px; background: var(--bg-surface); color: var(--text-muted); text-decoration: none; border: 1px solid var(--border-color); border-radius: 6px; font-weight: 700; font-size: 0.85rem; transition: 0.2s ease; }
        .btn-back-botiquin:hover { background: #e2e8f0; color: var(--text-main); }

        main { flex: 1; padding: 40px 20px; max-width: 650px; margin: 0 auto; width: 100%; display: flex; flex-direction: column; }
        .form-card-container {
            background: var(--bg-surface); border: 1px solid var(--border-color);
            padding: 35px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); width: 100%;
        }
        .form-card-container h2 { font-size: 1.6rem; color: var(--text-main); margin-bottom: 6px; font-weight: 800; }
        .form-card-container .form-desc-muted { color: var(--text-muted); margin-bottom: 30px; font-size: 0.95rem; }
        
        .field-group { margin-bottom: 24px; }
        .field-group label { display: block; margin-bottom: 8px; color: #334155; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .field-group input {
            width: 100%; padding: 14px 16px; background: var(--bg-input); border: 1px solid var(--border-color);
            border-radius: 8px; font-size: 1.05rem; font-weight: 500; color: var(--text-main); transition: all 0.2s ease; outline: none;
        }
        .field-group input:focus { border-color: var(--neon-blue); box-shadow: 0 0 10px var(--neon-glow); }
        
        .field-row { display: flex; gap: 20px; }
        .field-row .field-group { flex: 1; margin-bottom: 0; }

        .btn-submit-action {
            width: 100%; padding: 15px; background: var(--neon-blue); color: white;
            border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer;
            transition: background 0.2s ease; margin-top: 10px; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-submit-action:hover { background: #0284c7; box-shadow: 0 4px 15px var(--neon-glow); }

        .alert-error-panel {
            background: rgba(239, 68, 68, 0.08); border: 1px solid var(--ef-red);
            padding: 14px 16px; border-radius: 8px; color: var(--ef-red);
            font-weight: 600; font-size: 0.9rem; margin-bottom: 25px; display: flex; align-items: center; gap: 8px;
        }
    </style>
</head>
<body>

    <header>
        <h1 onclick="window.location.href='menu_paciente.php'">MedStock</h1>
        <div class="user-info">
            <a href="mi_botiquin.php" class="btn-back-botiquin">◀ Cancelar</a>
        </div>
    </header>

    <main>
        <!-- Panel de control de errores de validación de XAMPP -->
        <?php if (!empty($error_msg)): ?>
            <div class="alert-error-panel">
                <span>⚠️</span> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <div class="form-card-container">
            <h2>Cargar Medicamento</h2>
            <p class="form-desc-muted">Ingresá los datos del nuevo remedio para darlo de alta en tu botiquín operativo.</p>
            
            <form action="nuevo_remedio.php" method="POST">
                
                <div class="field-group">
                    <label for="nombre_comercial">Nombre del Remedio / Pastilla</label>
                    <input type="text" id="nombre_comercial" name="nombre_comercial" placeholder="Ej: Losartán, Metformina, Diclofenac..." required>
                </div>

                <div class="field-group">
                    <label for="horario">¿A qué hora te toca tomarlo?</label>
                    <input type="time" id="horario" name="horario" required>
                </div>

                <div class="field-row" style="margin-bottom: 30px;">
                    <div class="field-group">
                        <label for="stock_actual">Unidades iniciales</label>
                        <input type="number" id="stock_actual" name="stock_actual" min="1" value="30" required>
                    </div>
                    
                    <div class="field-group">
                        <label for="stock_minimo">Aviso de Stock Mínimo</label>
                        <input type="number" id="stock_minimo" name="stock_minimo" min="1" value="5" required>
                    </div>
                </div>

                <button type="submit" name="btn_guardar_med" class="btn-submit-action">
                    <span>💾</span> Guardar en mi Botiquín
                </button>
                
            </form>
        </div>
    </main>
</body>
</html>
