<?php 
require_once 'conexion.php';
// Buscamos los cuidadores registrados para asignarlos dinámicamente si se registra un paciente
$sql_cuidadores = "SELECT id_usuario, nombre FROM USUARIO WHERE rol = 'Cuidador'";
$resultado_cuidadores = $conexion->query($sql_cuidadores);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comenzar Ahora - MedStock</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            background-color: #000000; 
            overflow-x: hidden; 
            padding: 30px 20px;
        }

        /* Capa contenedora de las caras flotantes */
        .neon-particles { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; pointer-events: none; }

        /* ESTILO PARA CADA CARA QUE CAE (¡CÍRCULOS GRANDES!) */
        .face-particle {
            position: absolute;
            border-radius: 50%; /* Hace que la foto sea redonda */
            background-size: cover;
            background-position: center;
            opacity: 0;
            border: 2.5px solid #38bdf8; /* Borde celeste neón alrededor de la cara */
            filter: drop-shadow(0 0 10px rgba(56, 189, 248, 0.5));
            animation: fallAndSpin linear infinite;
        }

        /* ANIMACIÓN DE CAÍDA SUAVE Y ROTACIÓN */
        @keyframes fallAndSpin {
            0% { transform: translateY(-110px) rotate(0deg); opacity: 0; }
            10% { opacity: 0.65; }
            90% { opacity: 0.65; }
            100% { transform: translateY(105vh) rotate(360deg); opacity: 0; }
        }

        /* Cuadro contenedor del formulario de registro */
        .register-box { 
            width: 100%; 
            max-width: 550px; 
            background: rgba(15, 23, 42, 0.75); 
            padding: 40px; 
            border-radius: 28px;
            border: 1px solid rgba(56, 189, 248, 0.25); 
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.9), 0 0 40px rgba(56, 189, 248, 0.05); 
            z-index: 10;
            color: #ffffff;
        }

        .register-box h2 { font-size: 2.3rem; text-align: center; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 6px; }
        .subtitle { text-align: center; color: #94a3b8; font-size: 0.95rem; margin-bottom: 35px; }

        /* Campos de Entrada Estilizados */
        .form-row { display: flex; gap: 20px; margin-bottom: 22px; }
        .form-group { flex: 1; position: relative; }
        .form-group.full-width { width: 100%; margin-bottom: 22px; }
        
        .form-group label { display: block; margin-bottom: 8px; color: #cbd5e1; font-weight: 600; font-size: 0.85rem; letter-spacing: 0.5px; text-transform: uppercase; }
        
        .form-group input, .form-group select {
            width: 100%; padding: 14px 18px; background: rgba(0, 0, 0, 0.6); border: 1.5px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px; font-size: 1rem; color: #ffffff; transition: all 0.3s ease; outline: none;
        }
        .form-group input:focus, .form-group select:focus { border-color: #38bdf8; background: rgba(0, 0, 0, 0.8); box-shadow: 0 0 15px rgba(56, 189, 248, 0.25); }

        /* Selección de Roles por Tarjetas */
        .role-title { display: block; margin-bottom: 12px; color: #cbd5e1; font-weight: 600; font-size: 0.85rem; letter-spacing: 0.5px; text-transform: uppercase; }
        .role-cards-container { display: flex; gap: 12px; margin-bottom: 25px; }
        
        .role-card {
            flex: 1; background: rgba(15, 23, 42, 0.8); border: 1.5px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px; padding: 15px 10px; text-align: center; cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative;
        }
        
        .card-icon { font-size: 1.6rem; margin-bottom: 6px; display: block; }
        .role-card span { font-size: 0.85rem; font-weight: 700; color: #94a3b8; display: block; }
        
        .role-card.selected {
            border-color: #38bdf8;
            background: rgba(56, 189, 248, 0.1);
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
            transform: translateY(-3px);
        }
        .role-card.selected span { color: #38bdf8; }

        #seccion_cuidador { 
            display: none; background: rgba(56, 189, 248, 0.04); 
            padding: 20px; border-radius: 14px; 
            border: 1px dashed rgba(56, 189, 248, 0.3); 
            margin-bottom: 25px; animation: slideDown 0.4s ease-out;
        }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        /* Botón de envío */
        .btn {
            width: 100%; padding: 15px; background: linear-gradient(135deg, #38bdf8, #0284c7); color: white;
            border: none; border-radius: 12px; font-size: 1rem; font-weight: 700; cursor: pointer; position: relative;
            overflow: hidden; transition: all 0.3s ease; margin-top: 10px; box-shadow: 0 4px 15px rgba(56, 189, 248, 0.3);
        }
        .btn::after {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); transition: all 0.6s ease;
        }
        .btn:hover::after { left: 100%; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(56, 189, 248, 0.5); background: linear-gradient(135deg, #0ea5e9, #0369a1); }
        .btn:active { transform: translateY(0); }

        .login-link { text-align: center; margin-top: 25px; color: #94a3b8; font-size: 0.9rem; }
        .login-link a { color: #38bdf8; text-decoration: none; font-weight: 700; }
        .login-link a:hover { color: #7dd3fc; text-decoration: underline; }

        @media (max-width: 600px) {
            .form-row { flex-direction: column; gap: 0; }
            .role-cards-container { flex-direction: column; }
        }
    </style>
</head>
<body>

    <!-- Capa de Caras Flotantes -->
    <div class="neon-particles" id="particleContainer"></div>

    <!-- Caja de Registro -->
    <div class="register-box">
        <h2>Comenzar ahora</h2>
        <p class="subtitle">Gestioná tu salud de forma inteligente</p>
        
        <form action="procesar_registro.php" method="POST">
            
            <div class="form-group full-width">
                <label for="nombre">Nombre Completo</label>
                <input type="text" id="nombre" name="nombre" placeholder="Ingresá tu nombre y apellido" required>
            </div>
            
            <div class="form-group full-width">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" placeholder="correo@ejemplo.com" required>
            </div>
            
            <div class="form-group full-width">
                <label for="contrasenia">Contraseña</label>
                <input type="password" id="contrasenia" name="contrasenia" placeholder="Crea una contraseña segura" required>
            </div>

            <!-- SELECCIÓN DE ROLES POR TARJETAS INTERACTIVAS -->
            <label class="role-title">Asignación de Rol</label>
            <div class="role-cards-container">
                
                <div class="role-card selected" onclick="seleccionarRol('Independiente', this)">
                    <span class="card-icon">👤</span>
                    <span>Uso Personal</span>
                </div>
                
                <div class="role-card" onclick="seleccionarRol('Cuidador', this)">
                    <span class="card-icon">🤝</span>
                    <span>Cuidador</span>
                </div>
                
                <div class="role-card" onclick="seleccionarRol('Paciente', this)">
                    <span class="card-icon">👵</span>
                    <span>Paciente</span>
                </div>

            </div>

            <!-- Input oculto para el backend PHP -->
            <input type="hidden" id="rol" name="rol" value="Independiente">
            
            <!-- Panel Dinámico para Cuidador -->
            <div id="seccion_cuidador">
                <div class="form-group full-width" style="margin-bottom: 0;">
                    <label for="id_superior">Asignar Cuidador Responsable</label>
                    <select id="id_superior" name="id_superior">
                        <option value="">-- Seleccionar un Cuidador (Opcional) --</option>
                        <?php while($row = $resultado_cuidadores->fetch_assoc()): ?>
                            <option value="<?php echo $row['id_usuario']; ?>"><?php echo htmlspecialchars($row['nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn">Crear cuenta</button>
        </form>
        
        <p class="login-link">¿Ya tenés una cuenta? <a href="login.php">Iniciá sesión aquí</a></p>
    </div>

    
          <!-- Elemento de Audio Oculto para la música de fondo -->
    <audio id="musicaFondo" src="ascensor.mp3" loop></audio>

    <script>
        // --- MOTOR DE MÚSICA DE FONDO (CANCION DE ASCENSOR) ---
        const audio = document.getElementById('musicaFondo');
        
        function activarMusica() {
            audio.play().catch(error => {
                console.log("Esperando interacción para reproducir.");
            });
            document.removeEventListener('click', activarMusica);
            document.removeEventListener('keydown', activarMusica);
        }
        
        document.addEventListener('click', activarMusica);
        document.addEventListener('keydown', activarMusica);


        // --- LÓGICA DE SELECCIÓN DE TARJETAS ---
        function seleccionarRol(rolValue, cardElement) {
            const cards = document.querySelectorAll('.role-card');
            cards.forEach(card => card.classList.remove('selected'));
            cardElement.classList.add('selected');
            document.getElementById('rol').value = rolValue;
            
            const seccionCuidador = document.getElementById('seccion_cuidador');
            if (rolValue === 'Paciente') {
                seccionCuidador.style.display = 'block';
            } else {
                seccionCuidador.style.display = 'none';
                document.getElementById('id_superior').value = '';
            }
        }

        // --- LÓGICA DE LAS CARAS FLOTANTES ---
        const pContainer = document.getElementById('particleContainer');
        const maxParticles = 12;

        const equipoFotos = [
            { primary: 'leonel.png', fallback: 'leonel.png' },
            { primary: 'sebastian.jpeg', fallback: 'sebastian.jpg' },
            { primary: 'tiziano.jpeg', fallback: 'tiziano.jpg' },
            { primary: 'ian.jpeg', fallback: 'ian.jpg' }
        ];

        function createFaceParticle() {
            if (pContainer.children.length >= maxParticles) return;

            const particle = document.createElement('div');
            particle.classList.add('face-particle');
            const integrante = equipoFotos[Math.floor(Math.random() * equipoFotos.length)];
            
            particle.style.backgroundImage = `url('${integrante.primary}'), url('${integrante.fallback}')`;
            particle.style.left = `${Math.random() * 90}%`;

            const randomSize = Math.random() * 35 + 60;
            particle.style.width = `${randomSize}px`;
            particle.style.height = `${randomSize}px`;

            const randomDuration = Math.random() * 5 + 7;
            particle.style.animationDuration = `${randomDuration}s`;
            particle.style.animationDelay = `${Math.random() * 1}s`;

            pContainer.appendChild(particle);

            setTimeout(() => { particle.remove(); }, randomDuration * 1000);
        }
        setInterval(createFaceParticle, 900);
    
    </script>

</body>
</html>
