<?php
// --- CONTROL DE SEGURIDAD ANTICACHÉ (EVITA CRUCE DE ROLES AL USAR LAS FLECHAS) ---
header("Cache-Control: no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// 1. Iniciamos el manejo de sesiones en PHP
session_start();

// Si quedó alguna sesión vieja colgada dando vueltas, la destruimos a fondo por seguridad antes de loguear
if (isset($_SESSION['id_usuario'])) {
    $_SESSION = array();
    session_destroy();
    session_start();
}

// 2. Incluimos la conexión a la base de datos
include 'conexion.php';

// 3. Verificamos que los datos hayan llegado por el método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 4. Limpiamos el email para evitar inyecciones SQL
    $email = $conexion->real_escape_string($_POST['email']);
    $contrasenia_ingresada = $_POST['contrasenia'];

    // 5. Buscamos al usuario en la tabla por su correo electrónico (USUARIO en mayúsculas como tu script)
    $sql = "SELECT id_usuario, nombre, contrasenia, rol FROM USUARIO WHERE email = '$email'";
    $resultado = $conexion->query($sql);

    // 6. Verificamos si encontramos una coincidencia
    if ($resultado && $resultado->num_rows === 1) {
        // Extraemos los datos del usuario en un arreglo asociativo
        $usuario = $resultado->fetch_assoc();

        // 7. Validamos si la contraseña ingresada coincide con el hash encriptado de la BD
        if (password_verify($contrasenia_ingresada, $usuario['contrasenia'])) {
            
            // ¡Contraseña correcta! Guardamos las variables de sesión globales
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['rol'] = $usuario['rol'];

            // --- REDIRECCIÓN INTELIGENTE CORREGIDA (SOLUCIONA EL ERROR 404) ---
            $rol = $usuario['rol'];
            
            if ($rol === 'Paciente') {
                $destino = 'menu_paciente.php';
            } elseif ($rol === 'Cuidador') {
                $destino = 'menu_cuidador.php';
            } else {
                // CORREGIDO: En vez de mandar a menu_independiente.php que tira 404, usamos el menú paciente de respaldo
                $destino = 'menu_paciente.php'; 
            }

            echo "<script>
                    alert('¡Bienvenido/a " . $usuario['nombre'] . "!');
                    window.location.href='" . $destino . "';
                  </script>";
            exit();
            
        } else {
            // Contraseña incorrecta
            echo "<script>
                    alert('Error: La contraseña ingresada es incorrecta.');
                    window.history.back();
                  </script>";
        }
    } else {
        // El correo electrónico no está registrado
        echo "<script>
                alert('Error: El correo electrónico no coincide con ninguna cuenta registrada.');
                window.history.back();
              </script>";
    }
}

// 8. Cerramos la conexión para liberar recursos del servidor
$conexion->close();
?>
