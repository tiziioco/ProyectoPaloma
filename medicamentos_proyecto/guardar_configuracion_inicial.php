<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Paciente') {
    header("Location: login.php");
    exit();
}

$id_paciente_actual = $_SESSION['id_usuario'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- PASO A: GUARDAR EL CUIDADOR SELECCIONADO ---
    if (isset($_POST['id_superior']) && !empty($_POST['id_superior'])) {
        $id_cuidador = intval($_POST['id_superior']);
        
        $check_cuidador = "SELECT id_usuario FROM usuario WHERE id_usuario = $id_cuidador";
        $res_check = $conexion->query($check_cuidador);
        
        if ($res_check && $res_check->num_rows > 0) {
            $sql_update_cuidador = "UPDATE usuario SET id_superior = $id_cuidador WHERE id_usuario = $id_paciente_actual";
            $conexion->query($sql_update_cuidador);
        }
    }

        // --- PASO B: GUARDAR LOS MEDICAMENTOS CON HORARIO INCLUIDO ---
    if (isset($_POST['nombre_med']) && is_array($_POST['nombre_med'])) {
        $nombres = $_POST['nombre_med'];
        $stocks = $_POST['stock'];
        $horarios = $_POST['horario']; 

        for ($i = 0; $i < count($nombres); $i++) {
            $nombre_comercial = $conexion->real_escape_string($nombres[$i]);
            $stock_actual = intval($stocks[$i]);
            $horario_toma = $conexion->real_escape_string($horarios[$i]); 

            if (!empty($nombre_comercial)) {
                // GUARDADO REAL: Se incluye el campo 'horario' al final
                $sql_med = "INSERT INTO medicamento (nombre_comercial, compuesto, presentacion, stock_actual, stock_minimo, fecha_vencimiento, fk_id_usuario, horario) 
                            VALUES ('$nombre_comercial', 'No especificado', 'Pastilla', $stock_actual, 5, '2027-12-31', $id_paciente_actual, '$horario_toma')";
                $conexion->query($sql_med);
            }
        }
    }


    // --- PASO C: GUARDAR LAS DOLENCIAS SELECCIONADAS ---
    if (isset($_POST['dolencias']) && is_array($_POST['dolencias'])) {
        $_SESSION['dolencias'] = $_POST['dolencias'];
    } else {
        $_SESSION['dolencias'] = [];
    }

    // REDIRECCIÓN CORRECTA: Volvemos al menú oscuro original
    echo "<script>
            alert('¡Configuración inicial guardada con éxito! Ingresando a tu panel...');
            window.location.href='menu_paciente.php';
          </script>";
    exit();

} else {
    header("Location: menu_paciente.php");
    exit();
}

$conexion->close();
?>
