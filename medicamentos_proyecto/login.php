<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - MedStock</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body { display: flex; height: 100vh; background-color: #000000; overflow: hidden; }

        /* Columna del Carrusel (Izquierda) */
        .image-container {
            flex: 1.3;
            position: relative;
            display: flex;
            align-items: flex-end;
            padding: 50px;
            overflow: hidden;
            z-index: 5;
        }

        /* Capas de imágenes del carrusel que se superponen */
        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center center;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
            z-index: 1;
        }

        /* Capa oscura superior para mejorar la legibilidad */
        .slide::after {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.5));
        }

        .slide.active { opacity: 1; z-index: 2; }

        /* Contenedor del texto dinámico de presentación (UBICADO ABAJO A LA IZQUIERDA) */
        .image-text { 
            color: white; 
            text-shadow: 0 4px 15px rgba(0,0,0,0.8); 
            max-width: 550px; 
            z-index: 10; 
            position: absolute;
            bottom: 40px; 
            left: 40px;
            background: rgba(0, 0, 0, 0.65); 
            padding: 25px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(6px);
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.8s ease;
        }

        .slide.active .image-text {
            transform: translateY(0);
            opacity: 1;
            transition-delay: 0.5s;
        }

        .image-text h1 { font-size: 2.5rem; margin-bottom: 5px; font-weight: 800; letter-spacing: -1px; color: #38bdf8; }
        .image-text h3 { font-size: 1.2rem; color: #10b981; font-weight: 600; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .image-text p { font-size: 1.05rem; opacity: 0.9; font-weight: 400; line-height: 1.5; color: #e2e8f0; }

        /* Columna del Formulario (Derecha) */
        .form-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #000000;
            padding: 40px;
            position: relative;
            box-shadow: -10px 0 30px rgba(0,0,0,0.7);
            overflow: hidden;
        }

        .neon-particles { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; pointer-events: none; }

        .medical-cross {
            position: absolute; width: 24px; height: 24px; opacity: 0; background: #10b981;
            clip-path: polygon(33% 0%, 66% 0%, 66% 33%, 100% 33%, 100% 66%, 66% 66%, 66% 100%, 33% 100%, 33% 66%, 0% 66%, 0% 33%, 33% 33%);
            filter: drop-shadow(0 0 8px #10b981) drop-shadow(0 0 15px #059669);
            animation: fallAndSpin linear infinite;
        }

        @keyframes fallAndSpin {
            0% { transform: translateY(-50px) rotate(0deg); opacity: 0; }
            10% { opacity: 0.6; }
            90% { opacity: 0.6; }
            100% { transform: translateY(105vh) rotate(360deg); opacity: 0; }
        }

        .login-box { 
            width: 100%; max-width: 380px; background: rgba(15, 23, 42, 0.5); padding: 35px; border-radius: 24px;
            border: 1px solid rgba(16, 185, 129, 0.2); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8), 0 0 30px rgba(16, 185, 129, 0.05); z-index: 10;
        }

        .login-box h2 { font-size: 2.2rem; color: #ffffff; margin-bottom: 8px; font-weight: 800; letter-spacing: -0.5px; }
        .subtitle { color: #94a3b8; font-size: 0.95rem; margin-bottom: 35px; }
        /* Configuración de los campos de entrada */
        .form-group { margin-bottom: 25px; position: relative; }
        .form-group label { display: block; margin-bottom: 9px; color: #cbd5e1; font-weight: 600; font-size: 0.85rem; letter-spacing: 0.5px; text-transform: uppercase; }
        
        .form-group input {
            width: 100%; padding: 14px 18px; background: rgba(0, 0, 0, 0.6); border: 1.5px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px; font-size: 1rem; color: #ffffff; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); outline: none;
        }
        .form-group input:focus { border-color: #38bdf8; background: rgba(0, 0, 0, 0.8); box-shadow: 0 0 15px rgba(56, 189, 248, 0.25); }

        /* Botón celeste brillante */
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

        .register-link { text-align: center; margin-top: 30px; color: #94a3b8; font-size: 0.9rem; }
        .register-link a { color: #38bdf8; text-decoration: none; font-weight: 700; }
        .register-link a:hover { color: #7dd3fc; text-decoration: underline; }

        @media (max-width: 900px) {
            .image-container { display: none; }
            .form-container { flex: 1; box-shadow: none; }
        }
    </style>
</head>
<body>

    <!-- Panel Izquierdo: Slider con doble extensión de respaldo -->
    <div class="image-container" id="sliderContainer">
        
        <!-- Slide 1: El Equipo completo -->
        <div class="slide active" style="background-image: url('amigos.png');">
            <div class="image-text">
                <h1>MedStock</h1>
                <h3>El Equipo</h3>
                <p>Cuidando los horarios de quienes más nos importan, conectando familias y cuidadores de forma inteligente.</p>
            </div>
        </div>

        <!-- Slide 2: Sebastián Quintero -->
        <div class="slide" style="background-image: url('sebastian.jpeg'), url('sebastian.jpg');">
            <div class="image-text">
                <h1>Sebastián Quintero</h1>
                <h3>Desarrollador Backend</h3>
                <p>Experto en domar bases de datos y acomodar código PHP sin que explote el servidor. Si algo no conecta, es culpa de su café.</p>
            </div>
        </div>

        <!-- Slide 3: Tiziano Giacomozzi -->
        <div class="slide" style="background-image: url('tiziano.jpeg'), url('tiziano.jpg');">
            <div class="image-text">
                <h1>Tiziano Giacomozzi</h1>
                <h3>Desarrollador Frontend</h3>
                <p>Diseñador de las cruces neón. Se encarga de que todo se vea impecable, simétrico y ultra fachero para los usuarios.</p>
            </div>
        </div>

        <!-- Slide 4: Leonel Tello -->
        <div class="slide" style="background-image: url('leonel.png');">
            <div class="image-text">
                <h1>Leonel Tello</h1>
                <h3>Analista Funcional</h3>
                <p>El cerebro detrás de la lógica. Traduce los olvidos de las pastillas en requerimientos de software impecables.</p>
            </div>
        </div>

        <!-- Slide 5: Ian Vecchio -->
        <div class="slide" style="background-image: url('ian.jpeg'), url('ian.jpg');">
            <div class="image-text">
                <h1>Ian Vecchio</h1>
                <h3>Dueño Dictador</h3>
                <p>Máxima autoridad suprema del repositorio de Git. No programa una línea pero si el código no le gusta, te manda al calabozo.</p>
            </div>
        </div>

    </div>

    <!-- Panel Derecho: Formulario con Cruces Neón -->
    <div class="form-container">
        <div class="neon-particles" id="particleContainer"></div>

        <div class="login-box">
            <h2>¡Hola de nuevo!</h2>
            <p class="subtitle">Ingresá tus credenciales para acceder a la aplicación.</p>
            
            <form action="procesar_login.php" method="POST">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" id="email" name="email" placeholder="nombre@correo.com" required>
                </div>
                
                <div class="form-group">
                    <label for="contrasenia">Contraseña</label>
                    <input type="password" id="contrasenia" name="contrasenia" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn">Iniciar Sesión</button>
            </form>
            
            <p class="register-link">¿No tenés cuenta aún? <a href="registro.php">Registrate acá</a></p>
        </div>
    </div>

    <script>
        // --- LÓGICA DEL CARRUSEL DE FOTOS ---
        const slides = document.querySelectorAll('.slide');
        let currentSlide = 0;

        function nextSlide() {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }
        setInterval(nextSlide, 6000);

        // --- LÓGICA DE LAS CRUCES VERDES NEÓN ---
        const pContainer = document.getElementById('particleContainer');
        const maxCrosses = 15;

        function createCross() {
            if (pContainer.children.length >= maxCrosses) return;
            const cross = document.createElement('div');
            cross.classList.add('medical-cross');
            cross.style.left = `${Math.random() * 100}%`;
            const randomSize = Math.random() * 16 + 12;
            cross.style.width = `${randomSize}px`;
            cross.style.height = `${randomSize}px`;
            const randomDuration = Math.random() * 6 + 6;
            cross.style.animationDuration = `${randomDuration}s`;
            cross.style.animationDelay = `${Math.random() * 2}s`;
            pContainer.appendChild(cross);
            setTimeout(() => { cross.remove(); }, randomDuration * 1000);
        }
        setInterval(createCross, 800);
    </script>

</body>
</html>
