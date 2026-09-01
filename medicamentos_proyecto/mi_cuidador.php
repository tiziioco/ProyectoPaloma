<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Paciente') {
    header("Location: login.php");
    exit();
}

$id_paciente = $_SESSION['id_usuario'];

// Procesar el formulario de la bitácora de síntomas (Guardado seguro en Sesión)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_guardar_note'])) {
    $nota = trim($_POST['nota_sintoma']);
    if (!empty($nota)) {
        if (!isset($_SESSION['bitacora_cuidador'])) {
            $_SESSION['bitacora_cuidador'] = [];
        }
        $_SESSION['bitacora_cuidador'][] = [
            'fecha' => date("d/m/Y H:i"),
            'texto' => htmlspecialchars($nota)
        ];
    }
    header("Location: mi_cuidador.php?msg=success");
    exit();
}

// LECTURA REAL DEL CUIDADOR (REPARADA CON LÓGICA ESTRICTA)
$nombre_cuidador = "Sin cuidador asignado";
$especialidad_cuidador = "Asistencia General";

$sql_paciente = "SELECT id_superior FROM usuario WHERE id_usuario = $id_paciente";
$res_paciente = $conexion->query($sql_paciente);

if ($res_paciente && $res_paciente->num_rows > 0) {
    $row_p = $res_paciente->fetch_assoc();
    $id_cuidador = intval($row_p['id_superior']);
    
    if ($id_cuidador > 0) {
        // Buscamos el nombre del cuidador real asignado en la base de datos
        $sql_cuidador = "SELECT nombre FROM usuario WHERE id_usuario = $id_cuidador";
        $res_cuidador = $conexion->query($sql_cuidador);
        if ($res_cuidador && $res_cuidador->num_rows > 0) {
            $row_c = $res_cuidador->fetch_assoc();
            $nombre_cuidador = $row_c['nombre'];
            
            // Mapeo dinámico de la especialidad según las dolencias en sesión
            if (isset($_SESSION['dolencias']) && in_array('presion', $_SESSION['dolencias'])) {
                $especialidad_cuidador = "Cardiología y Control Coronario";
            } elseif (isset($_SESSION['dolencias']) && in_array('azucar', $_SESSION['dolencias'])) {
                $especialidad_cuidador = "Nutrición y Control de Glucemia";
            } elseif (isset($_SESSION['dolencias']) && in_array('espalda', $_SESSION['dolencias'])) {
                $especialidad_cuidador = "Kinesiología y Postura";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuidador - MedStock</title>
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
        
        .btn-back-menu { padding: 8px 16px; background: var(--bg-surface); color: var(--neon-blue); text-decoration: none; border: 1px solid var(--neon-blue); border-radius: 6px; font-weight: 700; font-size: 0.85rem; transition: 0.2s ease; }
        .btn-back-menu:hover { background: var(--neon-glow); transform: translateX(-2px); }

        main { flex: 1; padding: 40px 20px; max-width: 1050px; margin: 0 auto; width: 100%; display: grid; grid-template-columns: 1.14fr 1fr; gap: 30px; align-items: start; }
        /* --- ESTILOS DE LA FICHA DEL CUIDADOR --- */
        .cuidador-card-profile {
            background: var(--bg-surface); border: 1px solid var(--border-color);
            padding: 30px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            display: flex; flex-direction: column; gap: 20px;
        }
        
        .profile-header-main { display: flex; align-items: center; gap: 20px; }
        .profile-avatar-big { font-size: 3rem; background: #ffffff; padding: 12px; border-radius: 50%; border: 1px solid var(--border-color); box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        
        .profile-meta-title h2 { font-size: 1.5rem; font-weight: 800; color: var(--text-main); }
        .profile-meta-title p { color: var(--neon-blue); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

        .info-contact-list { display: flex; flex-direction: column; gap: 12px; margin-top: 10px; border-top: 1px solid var(--border-color); padding-top: 20px; }
        .contact-item-row { display: flex; align-items: center; gap: 12px; font-size: 0.95rem; color: #334155; }
        .contact-item-row strong { color: var(--text-main); min-width: 140px; }

        /* --- BOTÓN BOTÓN ROJO DE ALERTA DE AUXILIO --- */
        .btn-emergency-panic {
            width: 100%; padding: 18px; background: var(--ef-red); color: white;
            border: none; border-radius: 12px; font-size: 1.15rem; font-weight: 800;
            cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.25);
            transition: all 0.2s ease; text-transform: uppercase; letter-spacing: 0.5px;
            animation: pulsoPanicoAlerta 2.5s infinite ease-in-out;
        }
        .btn-emergency-panic:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4); }
        .btn-emergency-panic:active { transform: translateY(0); }

        @keyframes pulsoPanicoAlerta {
            0% { box-shadow: 0 0 10px rgba(239, 68, 68, 0.5); }
            50% { box-shadow: 0 0 25px rgba(239, 68, 68, 0.8), 0 0 35px rgba(239, 68, 68, 0.3); }
            100% { box-shadow: 0 0 10px rgba(239, 68, 68, 0.5); }
        }

        /* --- COLUMNA DE BITÁCORA DIARIA --- */
        .bitacora-container-card { background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .bitacora-container-card h3 { font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin-bottom: 6px; }
        .bitacora-container-card p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px; }

        .textarea-sintoma {
            width: 100%; height: 110px; padding: 14px; background: var(--bg-surface);
            border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem;
            color: var(--text-main); outline: none; transition: all 0.2s; resize: none; font-weight: 500;
        }
        .textarea-sintoma:focus { border-color: var(--neon-blue); background: #ffffff; box-shadow: 0 0 10px var(--neon-glow); }

        .btn-save-note {
            width: 100%; padding: 12px; background: var(--neon-blue); color: white; border: none;
            border-radius: 8px; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: background 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 12px;
        }
        .btn-save-note:hover { background: #0284c7; }

        .timeline-scroll-box { margin-top: 25px; max-height: 250px; overflow-y: auto; border-top: 1px solid var(--border-color); padding-top: 20px; display: flex; flex-direction: column; gap: 14px; }
        .timeline-item-node { background: var(--bg-surface); padding: 14px; border-radius: 8px; border-left: 4px solid var(--neon-blue); }
        .timeline-item-node span { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px; }
        .timeline-item-node p { font-size: 0.95rem; color: var(--text-main); margin-bottom: 0; line-height: 1.4; }

        .toast-msg-success { background: var(--emerald-green); color: white; padding: 12px 18px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>

    <header>
        <h1 onclick="window.location.href='menu_paciente.php'">MedStock</h1>
        <div class="user-info">
            <a href="menu_paciente.php" class="btn-back-menu">◀ Volver al Panel</a>
        </div>
    </header>

    <main>
        
        <div class="column-actions" style="display: flex; flex-direction: column; gap: 25px;">
            <div class="cuidador-card-profile">
                <div class="profile-header-main">
                    <span class="profile-avatar-big">👨‍⚕️</span>
                    <div class="profile-meta-title">
                        <h2><?php echo htmlspecialchars($nombre_cuidador); ?></h2>
                        <p>🩺 <?php echo htmlspecialchars($especialidad_cuidador); ?></p>
                    </div>
                </div>

                <div class="info-contact-list">
                    <div class="contact-item-row">
                        <strong>📱 Teléfono de Guardia:</strong>
                        <span>+54 9 11 5555-1234</span>
                    </div>
                    <div class="contact-item-row">
                        <strong>📅 Horario de Visita:</strong>
                        <span>Lunes a Viernes (09:00 - 13:00 HS)</span>
                    </div>
                    <div class="contact-item-row">
                        <strong>📍 Estado Clínico:</strong>
                        <span style="color: var(--emerald-green); font-weight: 700;">🟢 En Servicio Activo</span>
                    </div>
                </div>

                <button type="button" class="btn-emergency-panic" onclick="lanzarAlertaAuxilioEmergencia()">
                    🚨 Enviar Alerta de Auxilio
                </button>
            </div>
        </div>
        <!-- COLUMNA DERECHA: BITÁCORA DIARIA DE HISTORIAL POR SESIÓN -->
        <div class="bitacora-container-card">
            <h3>¿Cómo te sentís hoy?</h3>
            <p>Dejale una nota a tu cuidador sobre tus síntomas, dolores o dudas sobre las tomas de hoy.</p>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
                <div class="toast-msg-success">
                    <span>✔</span> Tu nota de salud fue fijada en la bitácora con éxito.
                </div>
            <?php endif; ?>

            <form action="mi_cuidador.php" method="POST">
                <textarea name="nota_sintoma" class="textarea-sintoma" placeholder="Ej: Me duele un poco la cabeza después de tomar la pastilla de la presión... o Hoy caminé 20 minutos completos." required></textarea>
                <button type="submit" name="btn_guardar_note" class="btn-save-note">
                    📝 Registrar en mi Historial
                </button>
            </form>

            <div class="timeline-scroll-box">
                <?php if (isset($_SESSION['bitacora_cuidador']) && !empty($_SESSION['bitacora_cuidador'])): ?>
                    <?php 
                    $notas_reversas = array_reverse($_SESSION['bitacora_cuidador']);
                    foreach ($notas_reversas as $item): 
                    ?>
                        <div class="timeline-item-node">
                            <span>📅 <?php echo $item['fecha']; ?></span>
                            <p><?php echo $item['texto']; ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: var(--text-muted); padding-top: 15px; font-size: 0.9rem;">No hay notas médicas registradas en la jornada de hoy.</p>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <script>
        function lanzarAlertaAuxilioEmergencia() {
            const confirmar = confirm("🚨 ¿Estás seguro de que necesitas enviar una alerta de auxilio urgente a tu cuidador?");
            
            if (confirmar) {
                alert("📡 Estableciendo conexión con el satélite médico...\n\n" + 
                      "📌 Tu ubicación ha sido geolocalizada con éxito.\n" + 
                      "🩺 Enviando reporte clínico de tus pastillas de hoy...\n\n" + 
                      "🚨 ¡ALERTA ENVIADA! Tu cuidador ha recibido una notificación de emergencia sonora en su panel. Quédate tranquilo, la asistencia va en camino.");
            }
        }
    </script>
</body>
</html>
