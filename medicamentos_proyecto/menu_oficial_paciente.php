<?php
session_start();
require_once 'conexion.php';

// Control de seguridad: Si no es paciente, no entra
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Paciente') {
    header("Location: login.php");
    exit();
}

$id_paciente = $_SESSION['id_usuario'];

// Traemos los medicamentos de este paciente para listarlos de forma seria en su panel
$sql_meds = "SELECT nombre_comercial, stock_actual FROM medicamento WHERE fk_id_usuario = $id_paciente";
$resultado_meds = $conexion->query($sql_meds);

$dolencias_paciente = isset($_SESSION['dolencias']) ? $_SESSION['dolencias'] : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Control de Salud - MedStock</title>
    <style>
        /* DISEÑO PROFESIONAL INSTITUCIONAL (Blanco, Gris y Azul Clínico. Cero juegos) */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background-color: #f1f5f9; color: #0f172a; min-height: 100vh; display: flex; flex-direction: column; }
        
        /* Barra superior seria */
        header {
            background: #ffffff; padding: 18px 40px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 2px solid #cbd5e1; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        header h1 { color: #0284c7; font-size: 1.5rem; font-weight: 700; }
        .user-info { display: flex; align-items: center; gap: 20px; }
        .user-info span { font-size: 1rem; font-weight: 600; color: #334155; }
        .btn-logout { padding: 8px 16px; background: #ef4444; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 0.9rem; transition: background 0.2s; }
        .btn-logout:hover { background: #dc2626; }

        main { flex: 1; padding: 40px 20px; max-width: 1000px; margin: 0 auto; width: 100%; }
        
        .welcome-block { margin-bottom: 30px; }
        .welcome-block h2 { font-size: 1.8rem; color: #1e293b; margin-bottom: 5px; }
        .welcome-block p { color: #64748b; font-size: 1.05rem; }

        /* Contenedor principal de dos columnas */
        .dashboard-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }

        /* Botonera grande para accesibilidad del paciente */
        .menu-actions-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .action-card {
            background: #ffffff; border: 1px solid #e2e8f0; padding: 35px 25px; border-radius: 14px;
            text-align: center; text-decoration: none; transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .action-card:hover { transform: translateY(-3px); border-color: #0284c7; box-shadow: 0 10px 15px -3px rgba(2, 132, 199, 0.1); }
        .action-icon { font-size: 3rem; margin-bottom: 12px; display: block; }
        .action-card h3 { font-size: 1.4rem; color: #1e293b; margin-bottom: 6px; }
        .action-card p { color: #64748b; font-size: 0.95rem; line-height: 1.4; }

        /* Paneles laterales informativos */
        .info-panel { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .info-panel h4 { font-size: 1.1rem; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 15px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; }
        
        .med-list-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; }
        .med-list-item:last-child { border-bottom: none; }
        .stock-badge { background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 0.85rem; }

        /* Banners médicos serios */
        .consejo-box { background: #f0fdf4; border-left: 4px solid #16a34a; padding: 15px; border-radius: 0 8px 8px 0; margin-bottom: 15px; font-size: 0.95rem; color: #14532d; line-height: 1.5; }
        .consejo-box strong { color: #166534; display: block; margin-bottom: 2px; }
    </style>
</head>
<body>

    <header>
        <h1>MedStock — Control Médico</h1>
        <div class="user-info">
            <span>👴 Paciente: <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </header>

    <main>
        <div class="welcome-block">
            <h2>Panel de Control Principal</h2>
            <p>Bienvenido a su registro digital de salud. Desde aquí puede acceder a sus alarmas y revisar su stock disponible.</p>
        </div>

        <div class="dashboard-layout">
            
            <!-- COLUMNA IZQUIERDA: BOTONES GRANDES PROFESIONALES -->
            <div class="menu-actions-grid">
                <a href="mis_alarmas.php" class="action-card">
                    <span class="action-icon">⏰</span>
                    <h3>Mis Alarmas</h3>
                    <p>Revise los horarios cargados de sus próximas tomas médicas y confirme la ingesta.</p>
                </a>

                <a href="mi_botiquin.php" class="action-card">
                    <span class="action-icon">💊</span>
                    <h3>Mi Botiquín</h3>
                    <p>Consulte el listado de sus remedios activos, presentaciones y controle las cajas.</p>
                </a>
            </div>

            <!-- COLUMNA DERECHA: RECOMENDACIONES CLÍNICAS Y VISTA RÁPIDA -->
            <div style="display: flex; flex-direction: column; gap: 25px;">
                
                <!-- Mi Botiquín Resumen -->
                <div class="info-panel">
                    <h4>Medicamentos Activos</h4>
                    <?php if ($resultado_meds && $resultado_meds->num_rows > 0): ?>
                        <?php while($med = $resultado_meds->fetch_assoc()): ?>
                            <div class="med-list-item">
                                <span><?php echo htmlspecialchars($med['nombre_comercial']); ?></span>
                                <span class="stock-badge"><?php echo $med['stock_actual']; ?> u.</span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: #64748b; font-size: 0.9rem;">No hay medicamentos registrados.</p>
                    <?php endif; ?>
                </div>

                <!-- Consejos Profesionales por Dolencia -->
                <?php if (!empty($dolencias_paciente)): ?>
                    <div class="info-panel">
                        <h4>Indicaciones Médicas</h4>
                        
                        <?php if (in_array('espalda', $dolencias_paciente)): ?>
                            <div class="consejo-box">
                                <strong>Recomendación Postural:</strong>
                                Evite movimientos de rotación bruscos en la columna. Mantenga una postura erguida al sentarse.
                            </div>
                        <?php endif; ?>

                        <?php if (in_array('presion', $dolencias_paciente)): ?>
                            <div class="consejo-box" style="background: #fff7ed; border-left-color: #ea580c; color: #7c2d12;">
                                <strong style="color: #9a3412;">Control de Presión Arterial:</strong>
                                Realice mediciones diarias por la mañana antes de la primera ingesta de fármacos. Reduzca la sal.
                            </div>
                        <?php endif; ?>

                        <?php if (in_array('azucar', $dolencias_paciente)): ?>
                            <div class="consejo-box" style="background: #f0f9ff; border-left-color: #0284c7; color: #0c4a6e;">
                                <strong style="color: #0369a1;">Monitoreo de Glucemia:</strong>
                                Mantenga un registro estricto en ayunas. Evite períodos prolongados de ayuno entre comidas.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </main>

</body>
</html>
