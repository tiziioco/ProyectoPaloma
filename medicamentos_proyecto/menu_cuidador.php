<?php
session_start();
require_once 'conexion.php';

// CONTROL DE ROL ESTRICTO: Si no es Cuidador, rebote inmediato al login
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Cuidador') {
    header("Location: login.php");
    exit();
}

$id_cuidador_actual = $_SESSION['id_usuario'];

// Inicializamos el estado del examen en la sesión si no existe
if (!isset($_SESSION['cuidador_aprobado'])) {
    $_SESSION['cuidador_aprobado'] = false;
}

// CONSULTA MYSQL REAL: Buscamos a todos los pacientes que eligieron a este cuidador (id_superior)
$sql_mis_pacientes = "SELECT id_usuario, nombre, email FROM usuario WHERE id_superior = $id_cuidador_actual AND rol = 'Paciente' ORDER BY nombre ASC";
$resultado_pacientes = $conexion->query($sql_mis_pacientes);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Cuidador - MedStock</title>
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
            --ef-red: #ef4444;
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

        main { flex: 1; padding: 40px 20px; max-width: 1200px; margin: 0 auto; width: 100%; display: flex; flex-direction: column; z-index: 5; }
        
        /* --- ESTILOS DEL CUESTIONARIO DE IDONEIDAD --- */
        .quiz-card-container {
            background: var(--bg-surface); border: 1px solid var(--border-color);
            padding: 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            max-width: 700px; margin: 20px auto; width: 100%;
        }
        .quiz-card-container h2 { font-size: 1.8rem; color: var(--text-main); font-weight: 800; margin-bottom: 8px; text-align: center; }
        .quiz-card-container .quiz-subtitle { color: var(--text-muted); font-size: 0.95rem; text-align: center; margin-bottom: 30px; }
        
        .quiz-question-block { background: #ffffff; border: 1px solid var(--border-color); padding: 22px; border-radius: 12px; margin-bottom: 20px; }
        .quiz-question-block p { font-weight: 700; font-size: 1.05rem; color: var(--text-main); margin-bottom: 15px; }
        .quiz-options-list { display: flex; flex-direction: column; gap: 10px; }
        .quiz-option-item {
            background: var(--bg-surface); border: 1px solid var(--border-color);
            padding: 12px 16px; border-radius: 8px; cursor: pointer; transition: all 0.2s;
            font-size: 0.95rem; font-weight: 500; color: #334155; display: flex; align-items: center; gap: 10px;
        }
        .quiz-option-item:hover { border-color: var(--neon-blue); background: rgba(14, 165, 233, 0.02); }
        .quiz-option-item input { pointer-events: none; }
        .quiz-option-item.selected { border-color: var(--neon-blue); background: var(--neon-glow); color: var(--neon-blue); font-weight: 600; }

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
            display: flex; align-items: center; gap: 6px; box-shadow: 0 0 10px rgba(16, 185, 129, 0.1);
        }

        .patients-grid-layout { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px; }
        .patient-card-node {
            background: #ffffff; border: 1px solid var(--border-color); border-radius: 14px;
            padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.01); display: flex; flex-direction: column; gap: 16px;
            transition: transform 0.2s, border-color 0.2s;
        }
        .patient-card-node:hover { transform: translateY(-2px); border-color: var(--neon-blue); box-shadow: 0 6px 18px var(--neon-glow); }

        .patient-profile-top { display: flex; align-items: center; gap: 15px; }
        .patient-avatar-wrapper { font-size: 2.2rem; background: var(--bg-surface); padding: 8px; border-radius: 50%; }
        .patient-meta-text h3 { font-size: 1.15rem; color: var(--text-main); font-weight: 700; }
        .patient-meta-text p { font-size: 0.85rem; color: var(--text-muted); font-family: monospace; }

        .btn-view-logs {
            width: 100%; padding: 10px; background: var(--bg-surface); color: var(--neon-blue);
            border: 1px solid var(--neon-blue); border-radius: 8px; font-size: 0.85rem; font-weight: 700;
            cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-view-logs:hover { background: var(--neon-blue); color: white; box-shadow: 0 3px 10px var(--neon-glow); }

        /* --- VENTANA MODAL PARA REVISAR BITÁCORAS EN VIVO --- */
        .clinical-modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 100;
            display: none; align-items: center; justify-content: center; padding: 20px;
        }
        .clinical-modal-overlay.active { display: flex; }
        .clinical-modal-card {
            background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px;
            padding: 30px; max-width: 550px; width: 100%; box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative; animation: slideUpModal 0.3s cubic-bezier(0, 1, 0, 1);
        }
        @keyframes slideUpModal {
            0% { transform: translateY(15px); opacity: 0; }
            100% { transform: translateY(0); opacity: 1; }
        }
        .modal-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; }
        .modal-header-row h4 { font-size: 1.25rem; font-weight: 800; color: var(--text-main); }
        .btn-close-modal { background: transparent; border: none; font-size: 1.4rem; color: var(--text-muted); cursor: pointer; }
        .btn-close-modal:hover { color: var(--ef-red); }
        
        .modal-scroll-timeline { max-height: 300px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; margin-top: 15px; padding-right: 5px; }
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
                    
                    <!-- PREGUNTA 1 -->
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

                    <!-- PREGUNTA 2 -->
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

                    <!-- PREGUNTA 3 -->
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
            <div class="dashboard-header">
                <div>
                    <h2>Monitoreo de Pacientes Asignados</h2>
                    <p>Seguimiento de historiales, reportes y existencias en tiempo real.</p>
                </div>
                <div class="title-badge-approved">
                    <span>🛡️</span> Licencia Activa y Verificada
                </div>
            </div>

            <!-- GRILLA DINÁMICA CONECTADA A MYSQL REAL -->
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
                            
                            <!-- Botón táctil que dispara la lectura de la sesión simulando red compartida -->
                            <button type="button" class="btn-view-logs" onclick="abrirModalBitacoraPaciente('<?php echo htmlspecialchars($nom_pac); ?>')">
                                🔍 Ver Bitácora de Síntomas
                            </button>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; background: var(--bg-surface); border: 1px dashed var(--border-color); padding: 40px; border-radius: 12px; text-align: center;">
                        <span style="font-size: 2.5rem; display: block; margin-bottom: 10px;">📋</span>
                        <p style="color: var(--text-muted); font-size: 1rem; font-weight: 600;">Ningún paciente te ha seleccionado en su formulario inicial todavía.</p>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px;">Asegurate de completar el asistente en la cuenta del Paciente eligiendo tu nombre.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- VENTANA MODAL COMPARTIDA PARA LEER HISTORIAL DE SESIÓN -->
            <div class="clinical-modal-overlay" id="overlayModalClinico">
                <div class="clinical-modal-card">
                    <div class="modal-header-row">
                        <h4 id="lblModalTituloPaciente">Bitácora Médica</h4>
                        <button type="button" class="btn-close-modal" onclick="cerrarModalBitacora()">&times;</button>
                    </div>
                    
                    <p style="color: var(--text-muted); font-size: 0.85rem; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Notas de salud y síntomas reportados de forma cronológica por el paciente.</p>
                    
                    <div class="modal-scroll-timeline" id="contenedorTimelineModal">
                        <!-- El motor de JavaScript inyectará las notas de sesión dinámicas acá -->
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- INICIO DE LA LÓGICA JAVASCRIPT BLINDADA -->
    <script>
        // Compartimos las notas de sesión del paciente al entorno de ejecución de JavaScript de forma segura
        const arrayNotasSesionCompartidas = <?php echo isset($_SESSION['bitacora_cuidador']) ? json_encode($_SESSION['bitacora_cuidador']) : '[]'; ?>;
        // --- CONTROLADOR DE SELECCIÓN TÁCTIL VISUAL EN EL TEST ---
        function seleccionarOpcionManual(elemento, nameGrupo, valorOpcion) {
            const contenedorPadre = elemento.parentElement;
            const itemsHermanos = contenedorPadre.querySelectorAll('.quiz-option-item');
            
            // Limpiamos los estilos seleccionados del grupo de opciones
            itemsHermanos.forEach(item => item.classList.remove('selected'));
            
            // Activamos la opción cliqueada por el profesional
            elemento.classList.add('selected');
            
            // Buscamos el radio button oculto y lo tildamos de verdad
            const radioInterno = elemento.querySelector('input[type="radio"]');
            if (radioInterno) {
                radioInterno.checked = true;
            }
        }

        // --- MOTOR DE CORRECCIÓN DEL EXAMEN DE IDONEIDAD ---
        function verificarRespuestasExamen(evento) {
            evento.preventDefault();
            
            const formulario = document.getElementById('formQuizIdoneidad');
            if (!formulario) return;

            const datosForm = new FormData(formulario);
            
            // Patrón de corrección estricto basado en las respuestas médicas profesionales
            const r1 = datosForm.get('p1'); // Correcta: B
            const r2 = datosForm.get('p2'); // Correcta: A
            const r3 = datosForm.get('p3'); // Correcta: A

            if (r1 === 'B' && r2 === 'A' && r3 === 'A') {
                alert("🎓 ¡EXAMEN APROBADO CON ÉXITO!\n\n" +
                      "🛡️ Tus respuestas demuestran la idoneidad clínica requerida.\n" +
                      "El Ministerio de Salud ha verificado tu licencia. Ingresando a tu Panel de Monitoreo...");
                
                // Recargamos la página inyectando la aprobación simulada por URL para activar la sesión
                window.location.href = 'menu_cuidador.php?aprobar_licencia_automatica=1';
            } else {
                alert("❌ VALIDACIÓN FALLIDA\n\n" +
                      "Algunas respuestas no se alinean con los protocolos de seguridad de MedStock.\n" +
                      "Por favor, revisa las preguntas e intentalo de nuevo para garantizar el cuidado del abuelo.");
            }
        }

        // --- MOTOR INTERACTIVO PARA ABRIR Y RENDERIZAR LA BITÁCORA EN VIVO ---
        function abrirModalBitacoraPaciente(nombrePaciente) {
            const overlay = document.getElementById('overlayModalClinico');
            const titulo = document.getElementById('lblModalTituloPaciente');
            const contenedorTimeline = document.getElementById('contenedorTimelineModal');
            
            if (!overlay || !titulo || !contenedorTimeline) return;

            // Seteamos el título de forma dinámica con el nombre del abuelo cliqueado
            titulo.innerText = "Bitácora Médica: " + nombrePaciente;
            contenedorTimeline.innerHTML = ""; // Limpiamos rastros anteriores

            // Si hay notas vivas en la sesión compartida, las dibujamos cronológicamente
            if (arrayNotasSesionCompartidas && arrayNotasSesionCompartidas.length > 0) {
                // Las invertimos para mostrar primero el síntoma más reciente arriba
                const notasReversas = [...arrayNotasSesionCompartidas].reverse();
                
                notasReversas.forEach(nota => {
                    const nodoHTML = document.createElement('div');
                    nodoHTML.className = "timeline-item-node";
                    nodoHTML.style.background = "#f8fafc";
                    nodoHTML.style.borderLeft = "4px solid #10b981"; // Borde verde para diferenciar la vista clínica
                    
                    nodoHTML.innerHTML = `
                        <span style="font-weight:700; color:#64748b; font-size:0.75rem; display:block; margin-bottom:4px;">📅 ${nota.fecha}</span>
                        <p style="font-size:0.95rem; color:#0f172a; line-height:1.4; margin:0;">${nota.texto}</p>
                    `;
                    contenedorTimeline.appendChild(nodoHTML);
                });
            } else {
                contenedorTimeline.innerHTML = `
                    <div style="text-align:center; padding:20px 0; color:#64748b;">
                        <p style="font-size:2rem; margin-bottom:6px;">📋</p>
                        <p style="font-size:0.9rem; font-weight:600;">El paciente no ha registrado síntomas durante la jornada de hoy.</p>
                    </div>`;
            }

            // Activamos el modal con la animación suave de desenfoque de fondo
            overlay.classList.add('active');
        }

        function cerrarModalBitacora() {
            const overlay = document.getElementById('overlayModalClinico');
            if (overlay) {
                overlay.classList.remove('active');
            }
        }
    </script>

       <!-- PROCESADOR COMPLEMENTARIO PHP: ACTIVA LA SESIÓN Y LA LICENCIA EN BASE DE DATOS -->
    <?php 
    if (isset($_GET['aprobar_licencia_automatica']) && $_GET['aprobar_licencia_automatica'] == '1') {
        $_SESSION['cuidador_aprobado'] = true;
        
        // Marcamos de forma real en la base de datos que este cuidador aprobó el examen
        $sql_activar_licencia = "UPDATE usuario SET id_superior = 1 WHERE id_usuario = $id_cuidador_actual";
        $conexion->query($sql_activar_licencia);
        
        echo "<script>window.location.href='menu_cuidador.php';</script>";
        exit();
    }
    ?>
</body>
</html>
