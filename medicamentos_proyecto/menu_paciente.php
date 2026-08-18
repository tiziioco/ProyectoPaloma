<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Paciente') {
    header("Location: login.php");
    exit();
}

$id_paciente = $_SESSION['id_usuario'];

$sql_verificar_med = "SELECT id_medicamento FROM medicamento WHERE fk_id_usuario = $id_paciente LIMIT 1";
$res_verificar = $conexion->query($sql_verificar_med);
$tiene_medicamentos = ($res_verificar && $res_verificar->num_rows > 0); 

$sql_lista_meds = "SELECT nombre_comercial, stock_actual, stock_minimo FROM medicamento WHERE fk_id_usuario = $id_paciente";
$resultado_meds = $conexion->query($sql_lista_meds);

$dolencias_paciente = isset($_SESSION['dolencias']) ? $_SESSION['dolencias'] : [];

$sql_cuidadores = "SELECT id_usuario, nombre FROM usuario WHERE rol = 'Cuidador' LIMIT 3";
$resultado_cuidadores = $conexion->query($sql_cuidadores);

$lista_cuidadores = [];
if ($resultado_cuidadores) {
    while ($row = $resultado_cuidadores->fetch_assoc()) {
        $lista_cuidadores[] = $row;
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
        .btn-logout:hover { background: #dc2626; box-shadow: 0 0 12px rgba(239, 68, 68, 0.2); }

        main { flex: 1; padding: 40px 20px; max-width: 1150px; margin: 0 auto; width: 100%; display: flex; flex-direction: column; z-index: 5; }
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
        .btn-submit-form {
            width: 100%; padding: 14px; background: var(--neon-blue); color: white;
            border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer;
            transition: background 0.2s ease; margin-top: 15px;
        }
        .btn-submit-form:hover { background: #0284c7; }

        .dashboard-header { margin-bottom: 30px; }
        .dashboard-header h2 { font-size: 2rem; font-weight: 800; color: var(--text-main); }
        .dashboard-header p { color: var(--text-muted); font-size: 1rem; margin-top: 2px; }
        
        .alert-clinical {
            background: #ffffff; border: 1.5px solid var(--neon-blue);
            padding: 16px 20px; border-radius: 10px; margin-bottom: 25px;
            display: flex; align-items: center; gap: 16px;
            box-shadow: 0 4px 12px var(--neon-glow);
        }
        .alert-clinical .alert-icon { font-size: 1.8rem; color: var(--neon-blue); }
        .alert-clinical h4 { color: var(--neon-blue); font-size: 1.05rem; font-weight: 700; margin-bottom: 2px; }
        .alert-clinical p { color: var(--text-main); font-size: 0.9rem; line-height: 1.4; }

        .layout-workspace { display: grid; grid-template-columns: 1.1fr 1fr; gap: 30px; }
        .column-actions { display: flex; flex-direction: column; gap: 20px; }
        
        .panel-nav-link {
            background: #ffffff; border: 1px solid var(--border-color);
            padding: 24px; border-radius: 12px; display: flex; align-items: center; gap: 20px;
            text-decoration: none; transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .panel-nav-link:hover { border-color: var(--neon-blue); transform: translateX(3px); box-shadow: 0 4px 15px var(--neon-glow); }
        .panel-nav-link .visual-box { font-size: 2.2rem; background: var(--bg-surface); padding: 10px; border-radius: 10px; }
        .panel-nav-link h3 { font-size: 1.2rem; color: var(--text-main); font-weight: 700; margin-bottom: 3px; }
        .panel-nav-link p { color: var(--text-muted); font-size: 0.85rem; line-height: 1.3; }

        .panel-stock { background: #ffffff; border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .panel-stock h3 { font-size: 1rem; color: var(--neon-blue); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
        
        .stock-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border-color); }
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
                                <span class="box-icon">Bone</span>
                                <span class="box-label">Dolor de Espalda</span>
                            </div>
                            <div class="selector-box" onclick="alternarDolor(this, 'presion')">
                                <input type="checkbox" name="dolencias[]" value="presion">
                                <span class="box-icon">Heart</span>
                                <span class="box-label">Presión Alta</span>
                            </div>
                            <div class="selector-box" onclick="alternarDolor(this, 'azucar')">
                                <input type="checkbox" name="dolencias[]" value="azucar">
                                <span class="box-icon">Drop</span>
                                <span class="box-label">Diabetes / Azúcar</span>
                            </div>
                            <div class="selector-box" onclick="alternarDolor(this, 'articulaciones')">
                                <input type="checkbox" name="dolencias[]" value="articulaciones">
                                <span class="box-icon">Run</span>
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

                <?php if (in_array('espalda', $dolencias_paciente)): ?>
                    <div class="alert-clinical">
                        <span class="alert-icon">🩻</span>
                        <div>
                            <h4>Indicación Médica: Dolor de Espalda</h4>
                            <p>Evitá levantar objetos pesados del suelo de forma brusca. Recordá flexionar las rodillas y mantener la espalda recta al agacharte.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (in_array('presion', $dolencias_paciente)): ?>
                    <div class="alert-clinical" style="border-color: #ea580c; background: rgba(234, 88, 12, 0.01);">
                        <span class="alert-icon" style="color: #ea580c;">🍏</span>
                        <div>
                            <h4 style="color: #ea580c;">Protocolo de Control: Presión Arterial</h4>
                            <p>Procurá medir tu presión en reposo antes de la primera toma del día. Evitá los alimentos con exceso de sodio añadidos.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (in_array('azucar', $dolencias_paciente)): ?>
                    <div class="alert-clinical" style="border-color: #0284c7; background: rgba(2, 132, 199, 0.01);">
                        <span class="alert-icon" style="color: #0284c7;">🩸</span>
                        <div>
                            <h4 style="color: #0284c7;">Seguimiento Clínico: Niveles de Glucemia</h4>
                            <p>Recordá llevar un registro de tus niveles de azúcar en ayunas. Mantener una rutina de caminatas ligeras ayuda a regular la glucemia.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="layout-workspace">
                    
                    <!-- COLUMNA IZQUIERDA: ACCESOS DIRECTOS -->
                    <div class="column-actions">
                        <a href="mis_alarmas.php" class="panel-nav-link">
                            <span class="visual-box">⏰</span>
                            <div>
                                <h3>Próximas Alarmas</h3>
                                <p>Revisá los horarios asignados para hoy y registrá tus tomas tomadas.</p>
                            </div>
                        </a>

                        <a href="mi_botiquin.php" class="panel-nav-link">
                            <span class="visual-box">💊</span>
                            <div>
                                <h3>Administrar Botiquín</h3>
                                <p>Cargá nuevas cajas, modificá dosis o editá tus remedios activos.</p>
                            </div>
                        </a>
                    </div>

                    <!-- COLUMNA DERECHA: REVISIÓN DE STOCK EN TIEMPO REAL -->
                    <div class="panel-stock">
                        <h3>Medicamentos Disponibles</h3>
                        
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
                                        <?php if ($es_critico): ?>
                                            <span class="pill-status status-alert">Bajo Stock ⚠️</span>
                                        <?php else: ?>
                                            <span class="pill-status status-valid">Correcto</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding-top: 10px;">No hay medicamentos en seguimiento clínico.</p>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        <?php endif; ?>
    </main>
    <script>
        const dbCuidadores = <?php echo json_encode($lista_cuidadores); ?>;

        const especialidadesFicticias = [
            { badge: "Kinesiología y Postura", desc: "Especialista en rehabilitation motriz, masajes terapéuticos y alivio del dolor crónico de columna.", gatillos: ["espalda", "hueso", "articulaciones", "ibuprofeno", "diclofenac", "kine"] },
            { badge: "Cardiología y Control", desc: "Experto en seguimiento de hipertensión, control de pulso y administración estricta de medicación coronaria.", gatillos: ["presion", "corazon", "alta", "losartan", "enalapril", "atenolol"] },
            { badge: "Nutrición y Metabolismo", desc: "Especialista en control de glucemia en sangre, armado de viandas saludables y alarmas de insulina.", gatillos: ["azucar", "diabetes", "metformina", "insulina", "gluco"] }
        ];

        const cuidadoresRespaldo = [
            { id_usuario: "991", nombre: "Dr. Carlos Gómez" },
            { id_usuario: "992", nombre: "Lic. María Rodríguez" },
            { id_usuario: "993", nombre: "Enf. Juan Herrera" }
        ];

        let dolenciasSeleccionadas = [];

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
                    </div>
                `;
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
                    if(textoCompleto.includes(palabra)) {
                        conteoVotos[idx] = conteoVotos[idx] + 2; 
                    }
                });
            });

            let maxVotos = -1;
            let ganadorIndex = 0;
            
            for (let clave in conteoVotos) {
                if (conteoVotos[clave] > maxVotos) {
                    maxVotos = conteoVotos[clave];
                    ganadorIndex = parseInt(clave);
                }
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
                </div>
            `;
            contenedor.appendChild(bloqueNuevo);
            analizarMedicamentos();
        }

        function quitarPastilla(boton) {
            boton.parentElement.remove();
            analizarMedicamentos();
        }

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
