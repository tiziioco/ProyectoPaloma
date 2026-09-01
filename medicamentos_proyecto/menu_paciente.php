<?php
// 1. Iniciamos el manejo de sesiones en PHP siempre primero
session_start();

// 2. Incluimos de forma obligatoria la conexión a la base de datos
include 'conexion.php';

// 3. Verificamos la seguridad del rol Paciente
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Paciente') {
    header("Location: login.php");
    exit();
}

$id_paciente = $_SESSION['id_usuario'];

// --- PARCHE DE LIMPIEZA DE ALERTAS EN BASE DE DATOS REPARADO ---
if (isset($_GET['limpiar_alerta_bd'])) {
    $conexion->query("DELETE FROM PROGRAMACION WHERE fk_id_medicamento = $id_paciente");
    header("Location: menu_paciente.php");
    exit();
}




$sql_verificar_med = "SELECT id_medicamento FROM medicamento WHERE fk_id_usuario = $id_paciente LIMIT 1";
$res_verificar = $conexion->query($sql_verificar_med);
$tiene_medicamentos = ($res_verificar && $res_verificar->num_rows > 0); 

$sql_lista_meds = "SELECT nombre_comercial, stock_actual, stock_minimo, horario FROM medicamento WHERE fk_id_usuario = $id_paciente";
$resultado_meds = $conexion->query($sql_lista_meds);

$dolencias_paciente = isset($_SESSION['dolencias']) ? $_SESSION['dolencias'] : [];

// REPARADO: Solo lista a los cuidadores que tienen su licencia aprobada de verdad (id_superior = 1)
// CORREGIDO: Trae a los cuidadores reales que tengan su examen aprobado de verdad (id_superior = id_usuario)
$sql_cuidadores = "SELECT id_usuario, nombre FROM usuario WHERE rol = 'Cuidador' AND id_superior = id_usuario ORDER BY nombre ASC";
$resultado_cuidadores = $conexion->query($sql_cuidadores);
$lista_cuidadores = [];
if ($resultado_cuidadores && $resultado_cuidadores->num_rows > 0) {
    while ($row = $resultado_cuidadores->fetch_assoc()) {
        $lista_cuidadores[] = $row;
    }
}






$proximo_remedio_nombre = "Ninguno";
$proximo_remedio_hora = "00:00:00";

if ($tiene_medicamentos) {
    $sql_cronograma = "SELECT nombre_comercial, horario FROM medicamento WHERE fk_id_usuario = $id_paciente ORDER BY horario ASC";
    $res_cronograma = $conexion->query($sql_cronograma);
    
    $hora_actual = date("H:i:s");
    $encontrado = false;
    $primer_remedio_dia = null;
    
    if ($res_cronograma && $res_cronograma->num_rows > 0) {
        while ($med_crono = $res_cronograma->fetch_assoc()) {
            if (!$primer_remedio_dia) {
                $primer_remedio_dia = $med_crono;
            }
            if ($med_crono['horario'] >= $hora_actual && !$encontrado) {
                $proximo_remedio_nombre = $med_crono['nombre_comercial'];
                $proximo_remedio_hora = $med_crono['horario'];
                $encontrado = true;
            }
        }
        if (!$encontrado && $primer_remedio_dia) {
            $proximo_remedio_nombre = $primer_remedio_dia['nombre_comercial'];
            $proximo_remedio_hora = $primer_remedio_dia['horario'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Médico - MedStock</title>
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
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background-color: var(--bg-main); color: var(--text-main); min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden; }
        
        header {
            background: #ffffff; padding: 18px 40px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 2px solid var(--neon-blue); box-shadow: 0 4px 15px var(--neon-glow); z-index: 10;
        }
        header h1 { color: var(--neon-blue); font-size: 1.6rem; font-weight: 800; letter-spacing: -0.5px; }
        .user-info { display: flex; align-items: center; gap: 20px; }
        .user-info span { font-size: 0.95rem; font-weight: 600; color: #334155; }
        .btn-logout { padding: 8px 16px; background: #ef4444; color: white; text-decoration: none; border-radius: 6px; font-weight: 700; font-size: 0.85rem; transition: 0.2s ease; }
        .btn-logout:hover { background: #ef4444; box-shadow: 0 0 12px rgba(239, 68, 68, 0.2); }

        main { flex: 1; padding: 40px 20px; max-width: 1150px; margin: 0 auto; width: 100%; display: flex; flex-direction: column; z-index: 5; }
        /* --- ESTILOS DEL ASISTENTE INICIAL --- */
        .onboarding-card {
            background: var(--bg-surface); border: 1px solid var(--border-color);
            padding: 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); width: 100%;
        }
        .onboarding-card h2 { font-size: 2.2rem; color: var(--neon-blue); margin-bottom: 8px; font-weight: 800; text-align: center; }
        .onboarding-card .step-info { color: var(--text-muted); text-align: center; margin-bottom: 35px; font-size: 1rem; }
        
        .field-group { margin-bottom: 24px; }
        .field-group label { display: block; margin-bottom: 8px; color: #334155; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .field-group input {
            width: 100%; padding: 14px 16px; background: var(--bg-input); border: 1px solid var(--border-color);
            border-radius: 8px; font-size: 0.95rem; color: var(--text-main); transition: all 0.2s ease; outline: none;
        }
        .field-group input:focus { border-color: var(--neon-blue); box-shadow: 0 0 10px var(--neon-glow); }
        
        .field-row { display: flex; gap: 16px; }
        .field-row .field-group { flex: 1; margin-bottom: 0; }

        .form-repeater-item {
            background: #ffffff; padding: 24px; border-radius: 10px;
            border: 1px solid var(--border-color); margin-bottom: 20px; position: relative;
        }

        .btn-delete-row {
            position: absolute; top: 15px; right: 15px; background: transparent;
            border: 1px solid #ef4444; color: #ef4444; padding: 4px 10px; border-radius: 4px;
            font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
        }
        .btn-delete-row:hover { background: #ef4444; color: white; }

        .btn-trigger-add {
            background: transparent; border: 1px dashed var(--neon-blue); color: var(--neon-blue);
            padding: 12px; width: 100%; border-radius: 8px; font-weight: 600; cursor: pointer;
            font-size: 0.9rem; margin-bottom: 35px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-trigger-add:hover { background: var(--neon-glow); }

        .selector-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-top: 10px; }
        .selector-box {
            background: #ffffff; border: 1px solid var(--border-color);
            padding: 20px 10px; border-radius: 10px; text-align: center; cursor: pointer; transition: all 0.2s;
            display: flex; flex-direction: column; align-items: center; gap: 8px; user-select: none;
        }
        .selector-box input { display: none; }
        .selector-box .box-icon { font-size: 1.8rem; pointer-events: none; }
        .selector-box .box-label { font-size: 0.85rem; font-weight: 700; color: var(--text-muted); pointer-events: none; }
        
        .selector-box.active { border-color: var(--neon-blue); background: var(--neon-glow); }
        .selector-box.active .box-label { color: var(--neon-blue); }

        .cards-stack { display: flex; flex-direction: column; gap: 12px; margin-top: 15px; }
        .stack-item-card {
            background: #ffffff; border: 1px solid var(--border-color);
            padding: 18px; border-radius: 10px; cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; justify-content: space-between; position: relative; user-select: none;
        }
        
        .card-body-left { display: flex; align-items: center; gap: 15px; pointer-events: none; }
        .card-avatar-wrapper { font-size: 1.8rem; background: var(--bg-surface); padding: 8px; border-radius: 50%; }
        .card-meta h4 { font-size: 1.05rem; color: var(--text-main); font-weight: 600; margin-bottom: 2px; }
        .card-meta-badge { display: inline-block; background: rgba(14, 165, 233, 0.1); color: var(--neon-blue); font-size: 0.65rem; font-weight: 700; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; margin-bottom: 4px; }
        .card-meta-desc { color: var(--text-muted); font-size: 0.85rem; line-height: 1.4; }

        .floating-badge {
            display: none; position: absolute; top: -9px; right: 15px; background: #10b981; color: white;
            font-size: 0.65rem; font-weight: 700; padding: 3px 8px; border-radius: 4px;
        }

        .stack-item-card.active { border-color: var(--neon-blue); background: var(--neon-glow); }
        .stack-item-card.featured { border-color: #10b981 !important; }
        .stack-item-card.featured .floating-badge { display: block; }
        /* --- ESTILOS DEL CRONÓMETRO DIGITAL ADAPTADO PARA EFECTO CASCADA --- */
        .countdown-container-block {
            background: #060b16; border: 2px solid var(--neon-blue); border-radius: 16px;
            padding: 24px; margin-bottom: 25px; box-shadow: 0 4px 20px rgba(14, 165, 233, 0.15);
            display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;
            position: relative; overflow: hidden;
        }
        .countdown-title-bar, .countdown-display-grid { position: relative; z-index: 2; }
        
        .cross-cascade-canvas {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1;
            pointer-events: none; opacity: 0.22;
        }

        .countdown-title-bar { color: #94a3b8; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 12px; font-weight: 700; }
        .countdown-title-bar span { color: var(--neon-blue); font-weight: 800; border-bottom: 2px solid var(--neon-blue); padding-bottom: 2px; }
        
        .countdown-display-grid { display: flex; gap: 20px; align-items: center; justify-content: center; margin-top: 10px; }
        .time-segment-box { display: flex; flex-direction: column; align-items: center; min-width: 75px; }
        .time-number-block {
            font-family: 'Courier New', Courier, monospace; font-size: 2.2rem; font-weight: 800;
            color: var(--neon-blue); background: #030712; width: 100%; padding: 12px 6px;
            border-radius: 10px; border: 1px solid rgba(14, 165, 233, 0.3);
            box-shadow: inset 0 0 10px rgba(0,0,0,0.8), 0 0 12px rgba(14, 165, 233, 0.1);
        }
        .time-label-muted { color: #64748b; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; margin-top: 8px; letter-spacing: 1px; }
        .time-divider-dots { font-size: 2rem; font-weight: 800; color: rgba(14, 165, 233, 0.4); padding-bottom: 22px; }

        .btn-submit-form {
            width: 100%; padding: 14px; background: var(--neon-blue); color: white;
            border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer;
            transition: background 0.2s ease; margin-top: 15px;
        }
        .btn-submit-form:hover { background: #0284c7; }

        .dashboard-header { margin-bottom: 30px; }
        .dashboard-header h2 { font-size: 2rem; font-weight: 800; color: var(--text-main); }
        .dashboard-header p { color: var(--text-muted); font-size: 1rem; margin-top: 2px; }
        
        /* --- TARJETA DE RECOMENDACIONES CON EFECTO DE PULSO VERDE --- */
        .alert-clinical {
            background: #ffffff; border: 1px solid #10b981; padding: 22px 28px; border-radius: 14px; margin-bottom: 30px;
            display: flex; align-items: center; gap: 20px; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.04), 0 0 15px rgba(16, 185, 129, 0.08);
            transition: opacity 0.5s ease; opacity: 1;
        }
        .alert-clinical h4 { color: #10b981; font-size: 1.3rem; font-weight: 800; margin-bottom: 6px; letter-spacing: -0.3px; }
        .alert-clinical p { color: #1e293b; font-size: 1.15rem; line-height: 1.5; font-weight: 500; }

        /* DISEÑO DE CONTENEDOR E ICONO SVG CON ANIMACIÓN DE LATIDO REAL */
        .svg-cross-container { display: flex; align-items: center; justify-content: center; flex-shrink: 0; animation: latidoCrossVerde 2.2s infinite ease-in-out; }
        .svg-cross-icon { fill: #10b981; filter: drop-shadow(0 0 4px rgba(16, 185, 129, 0.6)); }

        @keyframes latidoCrossVerde {
            0% { transform: scale(1); filter: drop-shadow(0 0 2px rgba(16, 185, 129, 0.4)); }
            50% { transform: scale(1.15); filter: drop-shadow(0 0 12px rgba(16, 185, 129, 0.9)) drop-shadow(0 0 25px rgba(16, 185, 129, 0.4)); }
            100% { transform: scale(1); filter: drop-shadow(0 0 2px rgba(16, 185, 129, 0.4)); }
        }

        .layout-workspace { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .column-actions { display: flex; flex-direction: column; gap: 20px; }
        .panel-nav-link {
            background: #ffffff; border: 1px solid var(--border-color); padding: 24px; border-radius: 12px;
            display: flex; align-items: center; gap: 20px; text-decoration: none; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .panel-nav-link:hover { border-color: var(--neon-blue); transform: translateX(3px); box-shadow: 0 4px 15px var(--neon-glow); }
        .panel-nav-link .visual-box { font-size: 2.2rem; background: var(--bg-surface); padding: 10px; border-radius: 10px; }
        .panel-nav-link h3 { font-size: 1.2rem; color: var(--text-main); font-weight: 700; margin-bottom: 3px; }
        .panel-nav-link p { color: var(--text-muted); font-size: 0.85rem; line-height: 1.3; }

        /* --- ESTILOS DEL ACORDEÓN DESPLEGABLE DE STOCK --- */
        .panel-stock-accordion { background: #ffffff; border: 2px solid var(--border-color); border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.02); transition: border-color 0.2s; }
        .panel-stock-accordion.active-border { border-color: var(--neon-blue); box-shadow: 0 4px 15px var(--neon-glow); }
        
        .accordion-trigger-header { background: #ffffff; padding: 24px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none; }
        .accordion-trigger-header h3 { font-size: 1.1rem; color: var(--neon-blue); font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .accordion-icon-arrow { font-size: 1.2rem; color: var(--neon-blue); transition: transform 0.3s ease; }
        .accordion-icon-arrow.rotated { transform: rotate(180deg); }

        .accordion-content-sliding { max-height: 0; overflow: hidden; transition: max-height 0.4s cubic-bezier(0, 1, 0, 1); background: var(--bg-surface); padding: 0 24px; }
        .accordion-content-sliding.opened { max-height: 1000px; transition: max-height 0.4s cubic-bezier(1, 0, 1, 0); padding: 12px 24px 24px 24px; }
        
        .stock-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid var(--border-color); }
        .stock-row:last-child { border-bottom: none; }
        .stock-label-name { font-size: 0.95rem; font-weight: 600; color: var(--text-main); }
        .stock-meta-wrapper { display: flex; align-items: center; gap: 12px; }
        .stock-count-number { font-family: monospace; font-size: 1rem; font-weight: 700; color: var(--text-main); }
        
        .pill-status { font-size: 0.7rem; font-weight: 800; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; }
        .status-valid { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
        .status-alert { background: rgba(245, 158, 11, 0.1); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.2); font-weight: 700; }
    </style>
</head>
<body>

    <header>
        <h1>MedStock</h1>
        <div class="user-info">
            <span>👴 Paciente: <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            <a href="logout.php" class="btn-logout">Salir</a>
        </div>
    </header>

    <main>
        <?php if (!$tiene_medicamentos): ?>
            <!-- ======================================================== -->
            <!-- CONFIGURACIÓN INICIAL (NUEVOS USUARIOS)                  -->
            <!-- ======================================================== -->
            <div class="onboarding-card">
                <h2>¡Te damos la bienvenida!</h2>
                <p class="step-info">Configurá tus medicamentos y dolencias para recibir recordatorios y consejos de salud a medida.</p>
                
                <form action="guardar_configuracion_inicial.php" method="POST">
                    
                    <label style="display:block; margin-bottom:12px; font-weight:700; color:var(--neon-blue); font-size:1.1rem; text-transform:uppercase;">1. Tus Medicamentos</label>
                    <div id="contenedorPastillas">
                        <div class="form-repeater-item">
                            <div class="field-group">
                                <label>Nombre de la Pastilla / Remedio</label>
                                <input type="text" name="nombre_med[]" class="med-input" placeholder="Ej: Paracetamol, Losartán, Metformina..." oninput="analizarMedicamentos()" required>
                            </div>
                            <div class="field-row">
                                <div class="field-group">
                                    <label>¿A qué hora la tomás?</label>
                                    <input type="time" name="horario[]" required>
                                </div>
                                <div class="field-group">
                                    <label>Cantidad inicial en caja</label>
                                    <input type="number" name="stock[]" min="1" placeholder="Ej: 30" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn-trigger-add" onclick="agregarNuevaPastilla()">
                        <span>➕</span> Agregar otro medicamento
                    </button>
                    <div class="field-group" style="margin-bottom: 45px;">
                        <label style="color:var(--neon-blue); font-weight:700; font-size:1.1rem; text-transform:uppercase;">2. Dolencias Recurrentes</label>
                        <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:15px;">Tocá las opciones para habilitar consejos personalizados.</p>
                        
                        <div class="selector-grid">
                            <div class="selector-box" onclick="alternarDolor(this, 'espalda')">
                                <input type="checkbox" name="dolencias[]" value="espalda">
                                <span class="box-icon">🦴</span>
                                <span class="box-label">Dolor de Espalda</span>
                            </div>
                            <div class="selector-box" onclick="alternarDolor(this, 'presion')">
                                <input type="checkbox" name="dolencias[]" value="presion">
                                <span class="box-icon">❤️</span>
                                <span class="box-label">Presión Alta</span>
                            </div>
                            <div class="selector-box" onclick="alternarDolor(this, 'azucar')">
                                <input type="checkbox" name="dolencias[]" value="azucar">
                                <span class="box-icon">🩸</span>
                                <span class="box-label">Diabetes / Azúcar</span>
                            </div>
                            <div class="selector-box" onclick="alternarDolor(this, 'articulaciones')">
                                <input type="checkbox" name="dolencias[]" value="articulaciones">
                                <span class="box-icon">🏃‍♂️</span>
                                <span class="box-label">Articulaciones</span>
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="margin-bottom: 30px;">
                        <label style="color:var(--neon-blue); font-weight:700; font-size:1.1rem; text-transform:uppercase;">3. Seleccioná tu Cuidador Especializado</label>
                        <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:15px;">El sistema te recomendará al profesional ideal según las pastillas y dolores que cargaste arriba.</p>
                        
                        <div class="cards-stack" id="panelCuidadores"></div>
                    </div>

                    <input type="hidden" id="id_superior" name="id_superior" value="" required>
                    <button type="submit" class="btn-submit-form">Guardar y Activar mi Panel Inteligente 🚀</button>
                </form>
            </div>
        <?php else: ?>
            <!-- ======================================================== -->
            <!-- CONTROL OPERATIVO REAL (USUARIOS ACTIVOS)                -->
            <!-- ======================================================== -->
            <div class="dashboard-workspace">
                <div class="dashboard-header">
                    <h2>Panel del Paciente</h2>
                    <p>Seguimiento de tomas automáticas y existencias disponibles.</p>
                </div>

                               <!-- BLOQUE RECEPTOR DE ALERTAS DESDE BASE DE DATOS REAL -->
                <?php 
                // Consultamos si el cuidador dejó una alerta física guardada en la base de datos para este paciente
                $sql_leer_alerta_bd = "SELECT estado FROM PROGRAMACION WHERE fk_id_medicamento = $id_paciente LIMIT 1";
                $res_alerta_bd = $conexion->query($sql_leer_alerta_bd);
                
                if ($res_alerta_bd && $res_alerta_bd->num_rows > 0): 
                    $row_alerta = $res_alerta_bd->fetch_assoc();
                    $texto_alerta_bd = $row_alerta['estado'];
                ?>
                    <div style="background: rgba(14, 165, 233, 0.05); border: 2px dashed var(--neon-blue); padding: 20px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 0 15px var(--neon-glow); display: flex; flex-direction: column; gap: 10px; position: relative; z-index: 999;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <strong style="color: var(--neon-blue); font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">📢 Mensaje de tu Cuidador (Enviado por Sistema Real)</strong>
                            <a href="?limpiar_alerta_bd=1" style="color: #ffffff; font-size: 0.75rem; text-decoration: none; background: var(--neon-blue); padding: 5px 10px; border-radius: 6px; font-weight: 700; transition: background 0.2s;">✕ Entendido</a>
                        </div>
                        <p style="color: var(--text-main); font-size: 1.1rem; font-weight: 600; line-height: 1.4; margin: 0;"><?php echo htmlspecialchars($texto_alerta_bd); ?></p>
                    </div>
                <?php endif; ?>



                <div class="countdown-container-block" id="cronometroContenedor">
                    <canvas id="canvasCascadaCruces" class="cross-cascade-canvas"></canvas>

                    <div class="countdown-title-bar">
                        Próxima Toma Médica Programada: <span id="lblProximoRemedio"><?php echo htmlspecialchars($proximo_remedio_nombre); ?></span>
                    </div>
                    
                    <div class="countdown-display-grid">
                        <div class="time-segment-box">
                            <div class="time-number-block" id="block-hours">00</div>
                            <div class="time-label-muted">Horas</div>
                        </div>
                        
                        <div class="time-divider-dots">:</div>
                        
                        <div class="time-segment-box">
                            <div class="time-number-block" id="block-minutes">00</div>
                            <div class="time-label-muted">Minutos</div>
                        </div>
                        
                        <div class="time-divider-dots">:</div>
                        
                        <div class="time-segment-box">
                            <div class="time-number-block" id="block-seconds">00</div>
                            <div class="time-label-muted">Segundos</div>
                        </div>
                    </div>
                </div>

                <!-- TARJETA CON LA CRUZ MÉDICA REAL LATIENDO (SVG NATIVO) -->
                <div class="alert-clinical" id="bannerConsejosRotativos">
                    <div class="svg-cross-container">
                        <svg class="svg-cross-icon" width="34" height="34" viewBox="0 0 24 24">
                            <path d="M19 10.5h-5.5V5c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v5.5H5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5h5.5V19c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-5.5H19c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 id="tituloConsejo">Consejo de Bienestar Diario</h4>
                        <p id="textoConsejo">Recordá tomar al menos 2 litros de agua durante el día para mantener tus riñones sanos y tu cuerpo con energía.</p>
                    </div>
                </div>
                <!-- COMPOSICIÓN DEL ESPACIO DE TRABAJO EN DOS COLUMNAS -->
                <div class="layout-workspace">
                    
                    <!-- COLUMNA IZQUIERDA: CUIDADOR ASIGNADO -->
                    <div class="column-actions">
                        <a href="mi_cuidador.php" class="panel-nav-link" style="width: 100%;">
                            <span class="visual-box">🤝</span>
                            <div>
                                <h3>Mi Cuidador Asignado</h3>
                                <p>Consultá los datos de tu profesional médico, horarios de visita o iniciá un chat directo de asistencia.</p>
                            </div>
                        </a>
                    </div>

                    <!-- COLUMNA DERECHA: ACORDEÓN INTERACTIVO DE MEDICAMENTOS -->
                    <div class="panel-stock-accordion" id="stockAccordionWrapper">
                        <div class="accordion-trigger-header" onclick="toggleStockAccordion()">
                            <h3>Medicamentos Disponibles</h3>
                            <span class="accordion-icon-arrow" id="accordionArrow">▼</span>
                        </div>
                        
                        <div class="accordion-content-sliding" id="accordionContent">
                            <?php if ($resultado_meds && $resultado_meds->num_rows > 0): ?>
                                <?php while($med = $resultado_meds->fetch_assoc()): 
                                    $stock = intval($med['stock_actual']);
                                    $minimo = intval($med['stock_minimo']);
                                    $es_critico = ($stock <= $minimo);
                                ?>
                                    <div class="stock-row">
                                        <span class="stock-label-name"><?php echo htmlspecialchars($med['nombre_comercial']); ?></span>
                                        <div class="stock-meta-wrapper">
                                            <span class="stock-count-number"><?php echo $stock; ?> u.</span>
                                            <span class="pill-status status-valid">
                                                ⏰ TOMAR <?php echo (!empty($med['horario'])) ? date("H:i", strtotime($med['horario'])) : "11:11"; ?> HS
                                            </span>
                                            <?php if ($es_critico): ?>
                                                <span class="pill-status status-alert">Poco Stock ⚠️</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 10px 0;">No hay medicamentos en seguimiento clínico.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- BOTÓN DE ADMINISTRAR BOTIQUÍN EXTENDIDO ABAJO DE TODO -->
                <div style="margin-top: 30px;">
                    <a href="mi_botiquin.php" class="panel-nav-link" style="width: 100%; display: flex;">
                        <span class="visual-box">💊</span>
                        <div>
                            <h3>Administrar mi Botiquín</h3>
                            <p>Ingresá acá para cargar nuevas cajas de medicamentos, modificar tus dosis o editar los datos de tus remedios activos en el sistema.</p>
                        </div>
                    </a>
                </div>

            </div>
        <?php endif; ?>
    </main>

    <script>
        const dbCuidadores = <?php echo json_encode($lista_cuidadores); ?>;

        const especialidadesFicticias = [
            { badge: "Kinesiología y Postura", desc: "Especialista en rehabilitación motriz, masajes terapéuticos y alivio del dolor crónico de columna.", gatillos: ["espalda", "hueso", "articulaciones", "ibuprofeno", "diclofenac", "kine"] },
            { badge: "Cardiología y Control", desc: "Experto en seguimiento de hipertensión, control de pulso y administración estricta de medicación coronaria.", gatillos: ["presion", "corazon", "alta", "losartan", "enalapril", "atenolol"] },
            { badge: "Nutrición y Metabolismo", desc: "Especialista en control de glucemia en sangre, armado de viandas saludables y alarmas de insulina.", gatillos: ["azucar", "diabetes", "metformina", "insulina", "gluco"] }
        ];

               const cuidadoresRespaldo = [
            { id_usuario: "6", nombre: "Sebastian Quintero" },
            { id_usuario: "7", nombre: "Leonel Tello" },
            { id_usuario: "8", nombre: "Ian Vecchio" }
        ];


        let dolenciasSeleccionadas = [];

        // --- MOTOR INTERACTIVO DEL ACORDEÓN DESPLEGABLE ---
        function toggleStockAccordion() {
            const wrapper = document.getElementById('stockAccordionWrapper');
            const content = document.getElementById('accordionContent');
            const arrow = document.getElementById('accordionArrow');
            
            if (!content || !arrow || !wrapper) return;

            content.classList.toggle('opened');
            arrow.classList.toggle('rotated');
            wrapper.classList.toggle('active-border');
        }
        // --- MOTOR DE CUENTA REGRESIVA CONECTADA AL HORARIO REAL (REPARADO) ---
        const horaObjetivoBD = "<?php echo $proximo_remedio_hora; ?>"; 

        function actualizarCronometroBloques() {
            const blockHours = document.getElementById('block-hours');
            const blockMinutes = document.getElementById('block-minutes');
            const blockSeconds = document.getElementById('block-seconds');
            
            if (!blockHours || !blockMinutes || !blockSeconds) return;

            const ahora = new Date();
            const objetivo = new Date();

            const partesHora = horaObjetivoBD.split(':');
            const h_target = parseInt(partesHora[0]) || 0;
            const m_target = parseInt(partesHora[1]) || 0;
            const s_target = parseInt(partesHora[2]) || 0;
            
            objetivo.setHours(h_target, m_target, s_target, 0);

            if (objetivo < ahora) {
                objetivo.setDate(objetivo.getDate() + 1);
            }

            const diferenciaMilisegundos = objetivo - ahora;

            const horasRestantes = Math.floor(diferenciaMilisegundos / (1000 * 60 * 60));
            const minutosRestantes = Math.floor((diferenciaMilisegundos % (1000 * 60 * 60)) / (1000 * 60));
            const segundosRestantes = Math.floor((diferenciaMilisegundos % (1000 * 60)) / 1000);

            blockHours.innerText = horasRestantes.toString().padStart(2, '0');
            blockMinutes.innerText = minutosRestantes.toString().padStart(2, '0');
            blockSeconds.innerText = segundosRestantes.toString().padStart(2, '0');
        }

        // --- MOTOR DE ROTACIÓN DE CONSEJOS CON EFECTO DE DESVANECIMIENTO ---
        const listaConsejosVida = [
            { titulo: "Consejo de Bienestar Diario", texto: "Recordá tomar al menos 2 litros de agua durante el día para mantener tus riñones sanos y tu cuerpo con energía." },
            { titulo: "Hábito Saludable Recomendado", texto: "Intentá realizar una caminata ligera de 15 a 20 minutos por la tarde. Mantener los músculos activos cuida tus articulaciones." },
            { titulo: "Sugerencia de Descanso", texto: "Evitá usar pantallas de celulares o televisión media hora antes de dormir. Un descanso profundo ayuda a fijar tu memoria." },
            { titulo: "Control de Alimentación", texto: "Procurá sumar más porciones de frutas y verduras frescas a tus comidas semanales para aportar vitaminas esenciales a tus defensas." }
        ];

        let indiceConsejoActual = 0;

        function rotarConsejosPrincipales() {
            const banner = document.getElementById('bannerConsejosRotativos');
            const elTitulo = document.getElementById('tituloConsejo');
            const elTexto = document.getElementById('textoConsejo');
            
            if (!banner || !elTitulo || !elTexto) return;

            banner.style.opacity = 0;

            setTimeout(() => {
                indiceConsejoActual = (indiceConsejoActual + 1) % listaConsejosVida.length;
                elTitulo.innerText = listaConsejosVida[indiceConsejoActual].titulo;
                elTexto.innerText = listaConsejosVida[indiceConsejoActual].texto;
                banner.style.opacity = 1;
            }, 500);
        }

        setInterval(rotarConsejosPrincipales, 15000);
        // --- MOTOR DE CASCADA DE CRUCES MÉDICAS DE FONDO (REPARADO Y BLINDADO) ---
        function iniciarCascadaCrucesWidget() {
            const canvas = document.getElementById('canvasCascadaCruces');
            const contenedor = document.getElementById('cronometroContenedor');
            if (!canvas || !contenedor) return;

            const ctx = canvas.getContext('2d');

            function redimensionarCanvas() {
                canvas.width = contenedor.getBoundingClientRect().width;
                canvas.height = contenedor.getBoundingClientRect().height;
            }
            redimensionarCanvas();
            window.addEventListener('resize', redimensionarCanvas);

            const tamañoFuente = 14;
            const columnas = Math.max(20, Math.floor(canvas.width / 20));
            const crucesCayendo = Array(columnas).fill(0);
            const simbolosMedicos = ['+', '✚', '✙', '†'];

            function dibujarLluvia() {
                if(canvas.width === 0 || canvas.height === 0) { redimensionarCanvas(); }

                ctx.fillStyle = 'rgba(6, 11, 22, 0.12)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.font = 'bold ' + tamañoFuente + 'px monospace';

                for (let i = 0; i < crucesCayendo.length; i++) {
                    const texto = simbolosMedicos[Math.floor(Math.random() * simbolosMedicos.length)];
                    const x = i * 20;
                    const y = crucesCayendo[i] * tamañoFuente;

                    ctx.fillStyle = Math.random() > 0.5 ? '#10b981' : '#0ea5e9';
                    ctx.fillText(texto, x, y);

                    if (y > canvas.height && Math.random() > 0.975) { crucesCayendo[i] = 0; }
                    crucesCayendo[i]++;
                }
            }
            setInterval(dibujarLluvia, 40);
        }

        // --- INICIALIZADOR SEGURO DE EVENTOS (REVIVE TODO EL RELOJ Y LA CASCADA) ---
        window.addEventListener('DOMContentLoaded', () => {
            setInterval(actualizarCronometroBloques, 1000);
            actualizarCronometroBloques();
            iniciarCascadaCrucesWidget();
        });
        
        setTimeout(() => {
            actualizarCronometroBloques();
            if(document.getElementById('canvasCascadaCruces')) { iniciarCascadaCrucesWidget(); }
        }, 200);

        // --- FUNCIONES DEL FORMULARIO ASISTENTE (REGISTRO INICIAL) ---
        function renderCuidadores(recomendadoIndex = 0) {
            const container = document.getElementById('panelCuidadores');
            if (!container) return;
            container.innerHTML = "";
            const cuidadoresParaMostrar = (dbCuidadores && dbCuidadores.length > 0) ? dbCuidadores : cuidadoresRespaldo;

            cuidadoresParaMostrar.forEach((cuidador, index) => {
                const esp = especialidadesFicticias[index % especialidadesFicticias.length];
                const esRecomendado = (index === recomendadoIndex);
                const card = document.createElement('div');
                card.className = "stack-item-card " + (esRecomendado ? "featured" : "");
                card.setAttribute('data-id', cuidador.id_usuario);
                card.onclick = function() { seleccionarCuidadorCard(this); };

                card.innerHTML = `
                    <div class="floating-badge">⭐ RECOMENDADO</div>
                    <div class="card-body-left">
                        <span class="card-avatar-wrapper">🤝</span>
                        <div class="card-meta">
                            <span class="card-meta-badge">${esp.badge}</span>
                            <h4>${cuidador.nombre}</h4>
                            <p class="card-meta-desc">${esp.desc}</p>
                        </div>
                    </div>`;
                container.appendChild(card);
                if(esRecomendado) {
                    const inputHidden = document.getElementById('id_superior');
                    if(inputHidden) inputHidden.value = cuidador.id_usuario;
                }
            });
        }

        function analizarMedicamentos() {
            const inputs = document.querySelectorAll('.med-input');
            let textoCompleto = dolenciasSeleccionadas.join(" ");
            inputs.forEach(inp => { textoCompleto += " " + inp.value.toLowerCase(); });
            let conteoVotos = { 0: 0, 1: 0, 2: 0 };

            especialidadesFicticias.forEach((esp, idx) => {
                esp.gatillos.forEach(palabra => {
                    if(textoCompleto.includes(palabra)) { conteoVotos[idx] = conteoVotos[idx] + 2; }
                });
            });

            let maxVotos = -1; let ganadorIndex = 0;
            for (let clave in conteoVotos) {
                if (conteoVotos[clave] > maxVotos) { maxVotos = conteoVotos[clave]; ganadorIndex = parseInt(clave); }
            }
            const cantidadCuidadores = (dbCuidadores && dbCuidadores.length > 0) ? dbCuidadores.length : cuidadoresRespaldo.length;
            renderCuidadores(ganadorIndex % cantidadCuidadores);
        }

        function agregarNuevaPastilla() {
            const contenedor = document.getElementById('contenedorPastillas');
            if (!contenedor) return;
            const bloqueNuevo = document.createElement('div');
            bloqueNuevo.className = 'form-repeater-item';
            bloqueNuevo.innerHTML = `
                <button type="button" class="btn-delete-row" onclick="quitarPastilla(this)">🗑️ Quitar</button>
                <div class="field-group">
                    <label>Nombre de la Pastilla / Remedio extra</label>
                    <input type="text" name="nombre_med[]" class="med-input" placeholder="Ej: Ibuprofeno, Losartán..." oninput="analizarMedicamentos()" required>
                </div>
                <div class="field-row">
                    <div class="field-group">
                        <label>¿A qué hora?</label>
                        <input type="time" name="horario[]" required>
                    </div>
                    <div class="field-group">
                        <label>Cantidad inicial en caja</label>
                        <input type="number" name="stock[]" min="1" placeholder="Ej: 20" required>
                    </div>
                </div>`;
            contenedor.appendChild(bloqueNuevo);
            analizarMedicamentos();
        }

        function quitarPastilla(boton) { boton.parentElement.remove(); analizarMedicamentos(); }
        
        function alternarDolor(elemento, valorDolor) {
            const checkbox = elemento.querySelector('input[type="checkbox"]');
            if (!checkbox) return;
            checkbox.checked = !checkbox.checked;
            if (checkbox.checked) {
                elemento.classList.add('active');
                if(!dolenciasSeleccionadas.includes(valorDolor)) dolenciasSeleccionadas.push(valorDolor);
            } else {
                elemento.classList.remove('active');
                dolenciasSeleccionadas = dolenciasSeleccionadas.filter(d => d !== valorDolor);
            }
            analizarMedicamentos(); 
        }

        function seleccionarCuidadorCard(elemento) {
            const cards = document.querySelectorAll('.stack-item-card');
            cards.forEach(c => c.classList.remove('active'));
            elemento.classList.add('active');
            const inputHidden = document.getElementById('id_superior');
            if(inputHidden) inputHidden.value = elemento.getAttribute('data-id');
        }

        renderCuidadores(0);
    </script>
</body>
</html>
