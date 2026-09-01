<?php
session_start();
require_once 'conexion.php';

// CONTROL DE ROL ESTRICTO: Si no es Cuidador, rebote inmediato al login
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Cuidador') {
    header("Location: login.php");
    exit();
}

$id_cuidador_actual = $_SESSION['id_usuario'];

// PERSISTENCIA REAL DEL EXAMEN: Comprobamos si ya aprobó el examen en la BD (id_superior = id_usuario)
$sql_check_aprobado = "SELECT id_superior FROM usuario WHERE id_usuario = $id_cuidador_actual LIMIT 1";
$res_check = $conexion->query($sql_check_aprobado);
if ($res_check && $res_check->num_rows > 0) {
    $row_check = $res_check->fetch_assoc();
    if (intval($row_check['id_superior']) === intval($id_cuidador_actual)) {
        $_SESSION['cuidador_aprobado'] = true;
    }
}

if (!isset($_SESSION['cuidador_aprobado'])) {
    $_SESSION['cuidador_aprobado'] = false;
}

// CONSULTA MYSQL REAL: Buscamos a todos los pacientes asignados a este profesional
$sql_mis_pacientes = "SELECT id_usuario, nombre, email FROM usuario WHERE id_superior = $id_cuidador_actual AND rol = 'Paciente' ORDER BY nombre ASC";
$resultado_pacientes = $conexion->query($sql_mis_pacientes);

// PROCESAR EL ENVÍO DE RECORDATORIOS DE MEDICAMENTOS (Guardado por sesión compartida)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_enviar_alerta_med'])) {
    $id_p_destino = intval($_POST['id_paciente_destino']);
    $msg_alerta = htmlspecialchars($_POST['mensaje_alerta']);
    $_SESSION['alerta_cuidador_paciente_'.$id_p_destino] = [
        'fecha' => date("H:i"),
        'texto' => $msg_alerta
    ];
    header("Location: menu_cuidador.php?msg_enviado=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoreo Clínico - MedStock</title>
    <style>
        :root {
            --bg-main: #060b16;
            --bg-surface: #0f172a;
            --bg-card: #1e293b;
            --bg-input: #0f172a;
            --neon-blue: #0ea5e9;
            --neon-glow: rgba(14, 165, 233, 0.25);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --emerald-green: #10b981;
            --emerald-glow: rgba(16, 185, 129, 0.2);
            --ef-red: #ef4444;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background-color: var(--bg-main); color: var(--text-main); min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden; }
        
        header {
            background: var(--bg-surface); padding: 18px 40px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 2px solid var(--neon-blue); box-shadow: 0 4px 20px var(--neon-glow); z-index: 10;
        }
        header h1 { color: var(--neon-blue); font-size: 1.6rem; font-weight: 800; letter-spacing: -0.5px; text-shadow: 0 0 10px var(--neon-glow); }
        .user-info { display: flex; align-items: center; gap: 20px; }
        .user-info span { font-size: 0.95rem; font-weight: 600; color: #cbd5e1; }
        .btn-logout { padding: 8px 16px; background: var(--ef-red); color: white; text-decoration: none; border-radius: 6px; font-weight: 700; font-size: 0.85rem; transition: 0.2s ease; box-shadow: 0 0 10px rgba(239, 68, 68, 0.15); }
        .btn-logout:hover { background: #dc2626; box-shadow: 0 0 15px rgba(239, 68, 68, 0.4); }

        main { flex: 1; padding: 40px 20px; max-width: 1250px; margin: 0 auto; width: 100%; display: flex; flex-direction: column; z-index: 5; }
        /* --- ESTILOS DEL CUESTIONARIO DE IDONEIDAD (ESTILO OSCURO) --- */
        .quiz-card-container {
            background: var(--bg-surface); border: 1px solid var(--border-color);
            padding: 40px; border-radius: 16px; box-shadow: 0 4px 25px rgba(0,0,0,0.3);
            max-width: 700px; margin: 20px auto; width: 100%;
        }
        .quiz-card-container h2 { font-size: 1.8rem; color: var(--neon-blue); font-weight: 800; margin-bottom: 8px; text-align: center; text-shadow: 0 0 10px var(--neon-glow); }
        .quiz-card-container .quiz-subtitle { color: var(--text-muted); font-size: 0.95rem; text-align: center; margin-bottom: 30px; }
        
        .quiz-question-block { background: var(--bg-card); border: 1px solid var(--border-color); padding: 22px; border-radius: 12px; margin-bottom: 20px; }
        .quiz-question-block p { font-weight: 700; font-size: 1.05rem; color: var(--text-main); margin-bottom: 15px; }
        .quiz-options-list { display: flex; flex-direction: column; gap: 10px; }
        .quiz-option-item {
            background: var(--bg-surface); border: 1px solid var(--border-color);
            padding: 12px 16px; border-radius: 8px; cursor: pointer; transition: all 0.2s;
            font-size: 0.95rem; font-weight: 500; color: #cbd5e1; display: flex; align-items: center; gap: 10px;
        }
        .quiz-option-item:hover { border-color: var(--neon-blue); background: rgba(14, 165, 233, 0.05); }
        .quiz-option-item input { pointer-events: none; display: none; }
        .quiz-option-item.selected { border-color: var(--neon-blue); background: var(--neon-glow); color: var(--neon-blue); font-weight: 600; box-shadow: 0 0 10px var(--neon-glow); }

        .btn-verify-quiz {
            width: 100%; padding: 14px; background: var(--neon-blue); color: white; border: none;
            border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background 0.2s;
            margin-top: 15px; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-verify-quiz:hover { background: #0284c7; box-shadow: 0 4px 15px var(--neon-glow); }

        /* --- ESTILOS DEL PANEL OPERATIVO REAL (APROBADO) --- */
        .dashboard-header { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .dashboard-header h2 { font-size: 2rem; font-weight: 800; color: var(--text-main); }
        .dashboard-header p { color: var(--text-muted); font-size: 1rem; margin-top: 2px; }

        .title-badge-approved {
            background: rgba(16, 185, 129, 0.1); color: var(--emerald-green);
            padding: 6px 14px; border-radius: 20px; font-weight: 800; font-size: 0.8rem;
            text-transform: uppercase; border: 1px solid rgba(16, 185, 129, 0.2);
            display: flex; align-items: center; gap: 6px; box-shadow: 0 0 10px var(--emerald-glow);
        }

        .patients-grid-layout { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px; }
        .patient-card-node {
            background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 14px;
            padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); display: flex; flex-direction: column; gap: 16px;
            transition: transform 0.2s, border-color 0.2s;
        }
        .patient-card-node:hover { transform: translateY(-2px); border-color: var(--neon-blue); box-shadow: 0 6px 18px var(--neon-glow); }

        .patient-profile-top { display: flex; align-items: center; gap: 15px; }
        .patient-avatar-wrapper { font-size: 2.2rem; background: var(--bg-card); padding: 8px; border-radius: 50%; border: 1px solid var(--border-color); }
        .patient-meta-text h3 { font-size: 1.15rem; color: var(--text-main); font-weight: 700; }
        .patient-meta-text p { font-size: 0.85rem; color: var(--text-muted); font-family: monospace; }

        .btn-view-logs {
            width: 100%; padding: 12px; background: transparent; color: var(--neon-blue);
            border: 1px solid var(--neon-blue); border-radius: 8px; font-size: 0.85rem; font-weight: 700;
            cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-view-logs:hover { background: var(--neon-blue); color: white; box-shadow: 0 3px 12px var(--neon-glow); }

        /* --- VENTANA MODAL AVANZADA: INTEGRACIÓN CLÍNICA COMPLETA --- */
        .clinical-modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(6, 11, 22, 0.7); backdrop-filter: blur(5px); z-index: 100;
            display: none; align-items: center; justify-content: center; padding: 20px;
        }
        .clinical-modal-overlay.active { display: flex; }
        .clinical-modal-card {
            background: var(--bg-surface); border: 2px solid var(--neon-blue); border-radius: 16px;
            padding: 30px; max-width: 650px; width: 100%; box-shadow: 0 10px 40px var(--neon-glow);
            position: relative; animation: slideUpModal 0.3s cubic-bezier(0, 1, 0, 1);
            color: var(--text-main); display: flex; flex-direction: column; gap: 15px;
        }
        @keyframes slideUpModal {
            0% { transform: translateY(20px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }
        .modal-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; }
        .modal-header-row h4 { font-size: 1.35rem; font-weight: 800; color: var(--neon-blue); text-shadow: 0 0 10px var(--neon-glow); }
        
        /* REPARADO: Botón rojo flotante bien visible en la esquina */
        .btn-close-modal { 
            background: var(--ef-red); border: none; font-size: 0.9rem; color: white; 
            cursor: pointer; padding: 6px 14px; border-radius: 6px; font-weight: 700;
            transition: background 0.2s; box-shadow: 0 0 8px rgba(239, 68, 68, 0.2);
        }
        .btn-close-modal:hover { background: #dc2626; }
        
        .modal-section-box { background: var(--bg-card); border: 1px solid var(--border-color); padding: 16px; border-radius: 10px; }
        .modal-section-box h5 { color: var(--neon-blue); font-size: 0.9rem; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 0.5px; }
        
        .med-row-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border-color); }
        .med-row-item:last-child { border-bottom: none; }
        
        .badge-info-toma { font-size: 0.7rem; font-weight: 800; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; }
        .badge-info-valid { background: rgba(16, 185, 129, 0.1); color: var(--emerald-green); border: 1px solid rgba(16, 185, 129, 0.2); }
        .badge-info-alert { background: rgba(239, 68, 68, 0.1); color: var(--ef-red); border: 1px solid rgba(239, 68, 68, 0.2); }

        .quick-alert-textarea { width: 100%; height: 75px; padding: 12px; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-main); font-size: 0.95rem; resize: none; outline: none; margin-bottom: 10px; }
        .quick-alert-textarea:focus { border-color: var(--neon-blue); box-shadow: 0 0 8px var(--neon-glow); }
        .btn-send-alert-action { width: 100%; padding: 10px; background: var(--emerald-green); color: white; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-send-alert-action:hover { background: #059669; box-shadow: 0 0 12px var(--emerald-glow); }

        .modal-scroll-timeline { max-height: 140px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; padding-right: 4px; }
        .toast-msg-success { background: var(--emerald-green); color: white; padding: 12px 18px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; margin-bottom: 20px; box-shadow: 0 4px 15px var(--emerald-glow); }
    </style>
</head>
<body>

    <header>
        <h1>MedStock 🩺</h1>
        <div class="user-info">
            <span>🧑‍⚕️ Profesional: <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            <a href="logout.php" class="btn-logout">Salir de mi Cuenta</a>
        </div>
    </header>

    <main>
        <?php if (!$_SESSION['cuidador_aprobado']): ?>
            <!-- ======================================================== -->
            <!-- VISTA A: EXAMEN DE VALIDACIÓN OBLIGATORIO DE IDONEIDAD  -->
            <!-- ======================================================== -->
            <div class="quiz-card-container">
                <h2>Examen de Validación Profesional</h2>
                <p class="quiz-subtitle">Para activar tu panel de monitoreo y recibir solicitudes de pacientes, completa este test rápido de idoneidad clínica.</p>
                
                <form id="formQuizIdoneidad" onsubmit="verificarRespuestasExamen(event)">
                    <div class="quiz-question-block">
                        <p>1. Si un abuelo asignado activa la "Alerta de Auxilio" roja desde su panel, ¿cuál es el protocolo inmediato?</p>
                        <div class="quiz-options-list">
                            <div class="quiz-option-item" onclick="seleccionarOpcionManual(this, 'p1', 'A')">
                                <input type="radio" name="p1" value="A" required> Esperar a que llame por teléfono para confirmar si es real.
                            </div>
                            <div class="quiz-option-item" onclick="seleccionarOpcionManual(this, 'p1', 'B')">
                                <input type="radio" name="p1" value="B"> Establecer comunicación de emergencia inmediata y despachar asistencia.
                            </div>
                            <div class="quiz-option-item" onclick="seleccionarOpcionManual(this, 'p1', 'C')">
                                <input type="radio" name="p1" value="C"> Ignorar el aviso si ya tomó sus pastillas del día correctamente.
                            </div>
                        </div>
                    </div>

                    <div class="quiz-question-block">
                        <p>2. ¿Cómo se debe actuar ante una alerta automática de "Poco Stock" en los medicamentos de un paciente?</p>
                        <div class="quiz-options-list">
                            <div class="quiz-option-item" onclick="seleccionarOpcionManual(this, 'p2', 'A')">
                                <input type="radio" name="p2" value="A" required> Coordinar de forma anticipada la reposición o aviso familiar.
                            </div>
                            <div class="quiz-option-item" onclick="seleccionarOpcionManual(this, 'p2', 'B')">
                                <input type="radio" name="p2" value="B"> Suspender las dosis hasta que el paciente consiga una nueva caja.
                            </div>
                            <div class="quiz-option-item" onclick="seleccionarOpcionManual(this, 'p2', 'C')">
                                <input type="radio" name="p2" value="C"> Dejar que el stock llegue a cero completo antes de actuar.
                            </div>
                        </div>
                    </div>

                    <div class="quiz-question-block">
                        <p>3. ¿Cuál es el canal idóneo para revisar la evolución diaria o reporte de dolores que escribe el abuelo?</p>
                        <div class="quiz-options-list">
                            <div class="quiz-option-item" onclick="seleccionarOpcionManual(this, 'p3', 'A')">
                                <input type="radio" name="p3" value="A" required> Revisar de forma constante la Bitácora de Síntomas por Sesión.
                            </div>
                            <div class="quiz-option-item" onclick="seleccionarOpcionManual(this, 'p3', 'B')">
                                <input type="radio" name="p3" value="B"> Consultar una vez al mes cuando toque la visita física obligatoria.
                            </div>
                            <div class="quiz-option-item" onclick="seleccionarOpcionManual(this, 'p3', 'C')">
                                <input type="radio" name="p3" value="C"> No llevar un registro cronológico de los comentarios del paciente.
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="quiz-option-item btn-verify-quiz">
                        <span>🎓</span> Validar mis Respuestas y Activar mi Licencia
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- ======================================================== -->
            <!-- VISTA B: PANEL DE CONTROL DE MONITOREO CLÍNICO OFICIAL   -->
            <!-- ======================================================== -->
            <?php if (isset($_GET['msg_enviado'])): ?>
                <div class="toast-msg-success">
                    <span>✔</span> Recordatorio enviado con éxito al panel del paciente.
                </div>
            <?php endif; ?>

            <div class="dashboard-header">
                <div>
                    <h2>Monitoreo de Pacientes Asignados</h2>
                    <p>Seguimiento de historiales, reportes y existencias en tiempo real.</p>
                </div>
                <div class="title-badge-approved">
                    <span>🛡️</span> Licencia Activa y Verificada
                </div>
            </div>

            <div class="patients-grid-layout">
                <?php if ($resultado_pacientes && $resultado_pacientes->num_rows > 0): ?>
                    <?php while($paciente = $resultado_pacientes->fetch_assoc()): 
                        $id_pac = intval($paciente['id_usuario']);
                        $nom_pac = $paciente['nombre'];
                    ?>
                        <div class="patient-card-node">
                            <div class="patient-profile-top">
                                <span class="patient-avatar-wrapper">👴</span>
                                <div class="patient-meta-text">
                                    <h3><?php echo htmlspecialchars($nom_pac); ?></h3>
                                    <p>✉️ <?php echo htmlspecialchars($paciente['email']); ?></p>
                                </div>
                            </div>
                            
                            <button type="button" class="btn-view-logs" onclick="cargarFichaClinicaCompleta(<?php echo $id_pac; ?>, '<?php echo htmlspecialchars($nom_pac); ?>', '<?php echo htmlspecialchars($paciente['email']); ?>')">
                                🔍 Abrir Ficha e Interactuar
                            </button>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; background: var(--bg-surface); border: 1px dashed var(--border-color); padding: 40px; border-radius: 12px; text-align: center;">
                        <span style="font-size: 2.5rem; display: block; margin-bottom: 10px;">📋</span>
                        <p style="color: var(--text-muted); font-size: 1rem; font-weight: 600;">Ningún paciente te ha seleccionado en su formulario inicial todavía.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- MODAL REPARADO CON BOTÓN ROJO DE CIERRE VISIBLE -->
            <div class="clinical-modal-overlay" id="overlayModalClinico">
                <div class="clinical-modal-card">
                    <div class="modal-header-row">
                        <h4 id="lblModalTituloPaciente">Ficha Clínica</h4>
                        <button type="button" class="btn-close-modal" onclick="cerrarModalBitacora()">✕ Cerrar Ficha</button>
                    </div>
                    
                    <div class="modal-section-box">
                        <h5>💊 Medicamentos y Próximas Tomas</h5>
                        <div id="contenedorMedicamentosModal"></div>
                    </div>

                    <div class="modal-section-box">
                        <h5>📢 Enviar Recordatorio / Alerta de Toma</h5>
                        <form action="menu_cuidador.php" method="POST">
                            <input type="hidden" id="id_paciente_destino" name="id_paciente_destino" value="<?php echo $id_pac; ?>">
                            <textarea id="mensaje_alerta" name="mensaje_alerta" class="quick-alert-textarea" placeholder="Escribí un mensaje personalizado o usá los botones rápidos de abajo..."></textarea>
                            
                            <div style="display:flex; gap:8px; margin-bottom:10px;">
                                <button type="button" class="btn-view-logs" style="padding:6px; font-size:0.75rem;" onclick="ponerMensajeRapido('⚠️ ¡Atención! Es hora de tomar tu medicación programada. Por favor confirma la toma.')">⏰ Alerta de Hora</button>
                                <button type="button" class="btn-view-logs" style="padding:6px; font-size:0.75rem;" onclick="ponerMensajeRapido('📦 Recargá tu Botiquín: Veo que te quedan pocas unidades de tus remedios. Recuerda comprar otra caja.')">📦 Alerta de Stock</button>
                            </div>

                            <button type="submit" name="btn_enviar_alerta_med" class="btn-send-alert-action">
                                ⚡ Enviar Recordatorio en Vivo
                            </button>
                        </form>
                    </div>

                    <div class="modal-section-box">
                        <h5>📝 Historial y Síntomas Reportados</h5>
                        <div class="modal-scroll-timeline" id="contenedorTimelineModal"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
    <!-- INICIO DE LA LÓGICA JAVASCRIPT BLINDADA -->
    <script>
        const arrayNotasSesionCompartidas = <?php echo isset($_SESSION['bitacora_cuidador']) ? json_encode($_SESSION['bitacora_cuidador']) : '[]'; ?>;

        // --- CONTROLADOR DE SELECCIÓN TÁCTIL VISUAL EN EL TEST ---
        function seleccionarOpcionManual(elemento, nameGrupo, valorOpcion) {
            const contenedorPadre = elemento.parentElement;
            const itemsHermanos = contenedorPadre.querySelectorAll('.quiz-option-item');
            itemsHermanos.forEach(item => item.classList.remove('selected'));
            elemento.classList.add('selected');
            
            const radioInterno = elemento.querySelector('input[type="radio"]');
            if (radioInterno) { radioInterno.checked = true; }
        }

        // --- MOTOR DE CORRECCIÓN DEL EXAMEN DE IDONEIDAD ---
        function verificarRespuestasExamen(evento) {
            evento.preventDefault();
            const formulario = document.getElementById('formQuizIdoneidad');
            if (!formulario) return;

            const datosForm = new FormData(formulario);
            const r1 = datosForm.get('p1');
            const r2 = datosForm.get('p2');
            const r3 = datosForm.get('p3');

            if (r1 === 'B' && r2 === 'A' && r3 === 'A') {
                alert("🎓 ¡EXAMEN APROBADO CON ÉXITO!\n\n🛡️ Licencia oficial activada en la base de datos.");
                window.location.href = 'menu_cuidador.php?aprobar_licencia_automatica=1';
            } else {
                alert("❌ VALIDACIÓN FALLIDA\n\nPor favor, revisa los protocolos de seguridad e intentalo de nuevo.");
            }
        }

        function ponerMensajeRapido(texto) {
            const textarea = document.getElementById('mensaje_alerta');
            if(textarea) textarea.value = texto;
        }

        // --- CARGA ASÍNCRONA DE FICHA CLÍNICA (INTERCONEXIÓN REAL) ---
        function cargarFichaClinicaCompleta(idPaciente, nombrePaciente, emailPaciente) {
            const overlay = document.getElementById('overlayModalClinico');
            const labelTitulo = document.getElementById('lblModalTituloPaciente');
            const inputDestino = document.getElementById('id_paciente_destino');
            const contenedorMeds = document.getElementById('contenedorMedicamentosModal');
            const contenedorTimeline = document.getElementById('contenedorTimelineModal');

            if (!overlay || !labelTitulo || !contenedorMeds || !contenedorTimeline) return;

            labelTitulo.innerText = "Ficha Médica Avanzada: " + nombrePaciente;
            if(inputDestino) inputDestino.value = idPaciente;
            
            contenedorMeds.innerHTML = "<p style='color:var(--text-muted); font-size:0.9rem;'>Consultando base de datos médica...</p>";
            contenedorTimeline.innerHTML = "";

            // 1. CARGA EN VIVO DE MEDICAMENTOS Y DOSIS VÍA FETCH API NATIVA
            fetch('obtener_detalles_paciente.php?id_paciente=' + idPaciente)
                .then(response => response.json())
                .then(data => {
                    contenedorMeds.innerHTML = "";
                    if(data && data.length > 0) {
                        data.forEach(med => {
                            const critico = (parseInt(med.stock_actual) <= parseInt(med.stock_minimo));
                            const row = document.createElement('div');
                            row.className = "med-row-item";
                            row.innerHTML = `
                                <div>
                                    <span style="font-weight:700; color:var(--text-main); font-size:0.95rem;">💊 ${med.nombre_comercial}</span>
                                    <p style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">Compuesto: ${med.compuesto ? med.compuesto : 'No especificado'} (${med.presentacion})</p>
                                </div>
                                <div style="text-align:right;">
                                    <span class="badge-info-toma ${critico ? 'badge-info-alert' : 'badge-info-valid'}">${med.stock_actual} unidades</span>
                                    <p style="font-size:0.8rem; color:var(--neon-blue); font-weight:700; margin-top:4px;">⏰ Toma: ${med.horario.substring(0,5)} hs</p>
                                </div>`;
                            contenedorMeds.appendChild(row);
                        });
                    } else {
                        contenedorMeds.innerHTML = "<p style='color:var(--text-muted); font-size:0.85rem; text-align:center;'>El paciente no tiene medicamentos activos configurados.</p>";
                    }
                })
                .catch(() => {
                    contenedorMeds.innerHTML = "<p style='color:var(--ef-red); font-size:0.85rem;'>Error al conectar con el servidor.</p>";
                });

            // 2. RENDERIZADO DE NOTAS DE SÍNTOMAS
            if (arrayNotasSesionCompartidas && arrayNotasSesionCompartidas.length > 0) {
                const notasReversas = [...arrayNotasSesionCompartidas].reverse();
                notasReversas.forEach(nota => {
                    const nodoHTML = document.createElement('div');
                    nodoHTML.className = "timeline-item-node";
                    nodoHTML.style.background = "var(--bg-surface)";
                    nodoHTML.style.borderLeft = "4px solid var(--emerald-green)";
                    nodoHTML.innerHTML = `
                        <span style="font-weight:700; color:var(--text-muted); font-size:0.75rem; display:block; margin-bottom:2px;">📅 ${nota.fecha}</span>
                        <p style="font-size:0.9rem; color:var(--text-main); margin:0;">${nota.texto}</p>`;
                    contenedorTimeline.appendChild(nodoHTML);
                });
            } else {
                contenedorTimeline.innerHTML = "<p style='color:var(--text-muted); font-size:0.85rem; text-align:center; padding-top:10px;'>No se registraron notas de síntomas hoy.</p>";
            }

            overlay.classList.add('active');
        }

        function cerrarModalBitacora() {
            const overlay = document.getElementById('overlayModalClinico');
            if (overlay) overlay.classList.remove('active');
        }
    </script>

    <!-- PROCESADOR COMPLEMENTARIO PHP: LICENCIA EN BASE DE DATOS -->
    <?php 
    if (isset($_GET['aprobar_licencia_automatica']) && $_GET['aprobar_licencia_automatica'] == '1') {
        $_SESSION['cuidador_aprobado'] = true;
        $sql_activar_licencia = "UPDATE usuario SET id_superior = id_usuario WHERE id_usuario = $id_cuidador_actual";
        $conexion->query($sql_activar_licencia);
        echo "<script>window.location.href='menu_cuidador.php';</script>";
        exit();
    }
    ?>
</body>
</html>
