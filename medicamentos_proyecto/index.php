<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedStock - Gestión Inteligente de Medicamentos</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body { 
            background-color: #000000; 
            color: #ffffff; 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            overflow: hidden;
            position: relative;
            padding: 20px;
        }

        /* Capa de fondo para las cruces médicas neón flotantes */
        .neon-particles { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; pointer-events: none; }

        .medical-cross {
            position: absolute; width: 24px; height: 24px; opacity: 0; background: #10b981;
            clip-path: polygon(33% 0%, 66% 0%, 66% 33%, 100% 33%, 100% 66%, 66% 66%, 66% 100%, 33% 100%, 33% 66%, 0% 66%, 0% 33%, 33% 33%);
            filter: drop-shadow(0 0 8px #10b981) drop-shadow(0 0 15px #059669);
            animation: floatUp linear infinite;
        }

        @keyframes floatUp {
            0% { transform: translateY(105vh) rotate(0deg); opacity: 0; }
            10% { opacity: 0.4; }
            90% { opacity: 0.4; }
            100% { transform: translateY(-50px) rotate(360deg); opacity: 0; }
        }

        /* Contenedor central de bienvenida */
        .welcome-container {
            max-width: 650px;
            width: 100%;
            background: rgba(15, 23, 42, 0.55);
            padding: 50px 40px;
            border-radius: 32px;
            border: 1px solid rgba(56, 189, 248, 0.25);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.9), 0 0 40px rgba(56, 189, 248, 0.05);
            text-align: center;
            z-index: 10;
        }

        .welcome-container h1 { 
            font-size: 3.5rem; 
            font-weight: 800; 
            letter-spacing: -1.5px; 
            color: #38bdf8; 
            margin-bottom: 15px;
            text-shadow: 0 0 20px rgba(56, 189, 248, 0.2);
        }
        
        .welcome-container p { 
            font-size: 1.25rem; 
            color: #94a3b8; 
            line-height: 1.6; 
            margin-bottom: 40px; 
        }
        /* Contenedor de botones en paralelo */
        .button-group {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        /* Estilo base de los botones gigantes */
        .btn-welcome {
            flex: 1;
            padding: 18px 25px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 14px;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-block;
        }

        /* Botón Iniciar Sesión (Celeste Neón Líquido) */
        .btn-login {
            background: linear-gradient(135deg, #38bdf8, #0284c7);
            color: #ffffff;
            border: none;
            box-shadow: 0 4px 15px rgba(56, 189, 248, 0.35);
        }
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(56, 189, 248, 0.55);
            background: linear-gradient(135deg, #0ea5e9, #0369a1);
        }

        /* Botón Registrarse (Borde de Cristal Neón) */
        .btn-register {
            background: transparent;
            color: #38bdf8;
            border: 2px solid rgba(56, 189, 248, 0.4);
        }
        .btn-register:hover {
            transform: translateY(-3px);
            border-color: #38bdf8;
            background: rgba(56, 189, 248, 0.08);
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.25);
        }

        .btn-welcome:active { transform: translateY(0); }

        @media (max-width: 550px) {
            .button-group { flex-direction: column; gap: 15px; }
            .welcome-container h1 { font-size: 2.5rem; }
            .welcome-container p { font-size: 1.1rem; }
        }
    </style>
</head>
<body>

    <!-- Capa de Cruces Médicas Flotantes -->
    <div class="neon-particles" id="particleContainer"></div>

    <!-- Caja de Bienvenida Central -->
    <div class="welcome-container">
        <h1>MedStock</h1>
        <p>Cuidando los horarios de quienes más nos importan. Conectamos familias, pacientes y cuidadores de forma inteligente para una gestión de salud impecable.</p>
        
        <div class="button-group">
            <a href="login.php" class="btn-welcome btn-login">Iniciar Sesión</a>
            <a href="registro.php" class="btn-welcome btn-register">Crear Cuenta</a>
        </div>
    </div>

    <script>
        // --- LÓGICA DE LAS CRUCES NEÓN QUE FLOTAN HACIA ARRIBA ---
        const pContainer = document.getElementById('particleContainer');
        const maxCrosses = 15;

        function createFloatingCross() {
            if (pContainer.children.length >= maxCrosses) return;
            
            const cross = document.createElement('div');
            cross.classList.add('medical-cross');
            
            // Posición horizontal aleatoria por toda la pantalla
            cross.style.left = `${Math.random() * 100}%`;
            
            // Tamaños aleatorios para dar efecto de profundidad
            const randomSize = Math.random() * 16 + 12;
            cross.style.width = `${randomSize}px`;
            cross.style.height = `${randomSize}px`;
            
            // Tiempos de subida variables (entre 6 y 12 segundos)
            const randomDuration = Math.random() * 6 + 6;
            cross.style.animationDuration = `${randomDuration}s`;
            
            pContainer.appendChild(cross);
            
            // Remover el elemento una vez que termina de flotar para no saturar el navegador
            setTimeout(() => { cross.remove(); }, randomDuration * 1000);
        }
        
        // Genera una cruz nueva cada 800 milisegundos
        setInterval(createFloatingCross, 800);
    </script>

</body>
</html>
