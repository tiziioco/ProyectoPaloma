<?php
session_start();
include 'conexion.php';

if (isset($_GET['id_paciente'])) {
    $id_p = intval($_GET['id_paciente']);
    
    // Consulta real sobre tu esquema original de base de datos
    $sql_meds = "SELECT nombre_comercial, compuesto, presentacion, stock_actual, stock_minimo, horario FROM MEDICAMENTO WHERE fk_id_usuario = $id_p ORDER BY horario ASC";
    $res = $conexion->query($sql_meds);
    
    $medicamentos = [];
    if ($res && $res->num_rows > 0) {
        while($row = $res->fetch_assoc()) {
            $medicamentos[] = $row;
        }
    }
    
    // Enviamos los datos en formato JSON para que el Javascript los dibuje en el modal
    header('Content-Type: application/json');
    echo json_encode($medicamentos);
    exit();
}
?>
