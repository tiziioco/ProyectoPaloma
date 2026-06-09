<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedStock - Iniciar Sesión</title>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --bg-card: rgba(255, 255, 255, 0.85);
            --border-color: #e5e7eb;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .glass-container {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15), 
                        inset 0 1px 1px rgba(255, 255, 255, 0.3);
            width: 100%;
            max-width: 440px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            transition: all 0.3s ease;
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-placeholder {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
            box-shadow: 0 8px 16px rgba(118, 75, 162, 0.3);
            margin-bottom: 16px;
        }

        h2 {
            color: var(--text-dark);
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        p.subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 6px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #4b5563;
        }

        .input-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            background-color: rgba(255, 255, 255, 0.7);
            font-size: 15px;
            color: var(--text-dark);
            outline: none;
            transition: all 0.2s ease-in-out;
        }

        .input-group input:focus {
            border-color: var(--primary);
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.25);
            transition: all 0.2s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.35);
            filter: brightness(1.05);
        }

        .footer-link {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .footer-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .footer-link a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="glass-container">
    <div class="header">
        <div class="logo-placeholder">M+</div>
        <h2>¡Hola de nuevo!</h2>
        <p class="subtitle">Ingresa tus credenciales para acceder</p>
    </div>

    <!-- Envía los datos a procesar_login.php -->
    <form action="procesar_login.php" method="POST">
        <div class="input-group">
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" required placeholder="correo@ejemplo.com">
        </div>

        <div class="input-group">
            <label for="contrasenia">Contraseña</label>
            <input type="password" id="contrasenia" name="contrasenia" required placeholder="Ingresa tu contraseña">
        </div>

        <button type="submit" class="btn-submit">Iniciar sesión</button>
    </form>

    <div class="footer-link">
        ¿No tienes cuenta todavía? <a href="registro.php">Regístrate aquí</a>
    </div>
</div>

</body>
</html>
