<?php
// 1. Incluimos la conexión a la base de datos
include 'conexion.php';

// 2. Verificamos que los datos hayan llegado por el método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 3. Recibimos y limpiamos los datos para evitar inyecciones SQL básicas
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $email = $conexion->real_escape_string($_POST['email']);
    $rol = $conexion->real_escape_string($_POST['rol']);
    
    // Encriptamos la contraseña para que no se guarde en texto plano en la BD
    $contrasenia_encriptada = password_hash($_POST['contrasenia'], PASSWORD_DEFAULT);

    // Por ahora dejamos el id_superior como NULL (luego el cuidador podrá vincularlo)
    $id_superior = "NULL"; 

    // 4. Preparamos la consulta SQL para insertar en la tabla USUARIO
    $sql = "INSERT INTO USUARIO (nombre, email, contrasenia, rol, id_superior) 
            VALUES ('$nombre', '$email', '$contrasenia_encriptada', '$rol', $id_superior)";

    // 5. Ejecutamos la consulta y verificamos si se guardó con éxito
    if ($conexion->query($sql) === TRUE) {
        echo "<script>
                alert('¡Registro exitoso! Ya puedes iniciar sesión.');
                window.location.href='login.php'; 
              </script>";
    } else {
        // Si el correo ya existe, saltará un error porque en el SQL lo pusimos como UNIQUE
        echo "<script>
                alert('Error al registrar: El correo electrónico ya está en uso.');
                window.history.back();
              </script>";
    }
}

// 6. Cerramos la conexión para liberar recursos
$conexion->close();
?>
