<?php
// ✅ LÍNEAS AÑADIDAS PARA MOSTRAR ERRORES PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once ('../config/conexion.php');
require_once ('../modelos/Empleado.php');

$empleado = new Empleado();

$op = isset($_GET["op"]) ? $_GET["op"] : "";

switch ($op) {
    case "guardaryeditar":
        $empleado_id = isset($_POST["empleado_id"]) ? limpiarCadena($_POST["empleado_id"]) : "";
        $nombre = isset($_POST["nombre"]) ? limpiarCadena($_POST["nombre"]) : "";
        $apellido = isset($_POST["apellidos"]) ? limpiarCadena($_POST["apellidos"]) : "";
        $documento_numero = isset($_POST["documento_numero"]) ? limpiarCadena($_POST["documento_numero"]) : "";
        $telefono = isset($_POST["telefono"]) ? limpiarCadena($_POST["telefono"]) : "";
        $codigo = isset($_POST["codigo"]) ? limpiarCadena($_POST["codigo"]) : "";

        if (empty($empleado_id)) {
            $rpta = $empleado->insertar($nombre, $apellido, $documento_numero, $telefono, $codigo);
            echo json_encode(['status' => $rpta ? 'success' : 'error', 'message' => $rpta ? 'Datos registrados correctamente' : 'No se pudo registrar los datos']);
        } else {
            $rpta = $empleado->editar($empleado_id, $nombre, $apellido, $documento_numero, $telefono, $codigo);
            echo json_encode(['status' => $rpta ? 'success' : 'error', 'message' => $rpta ? 'Datos actualizados correctamente' : 'No se pudo actualizar los datos']);
        }
        break;

    case "mostrar":
        $id = isset($_POST["empleado_id"]) ? limpiarCadena($_POST["empleado_id"]) : "";
        if (!empty($id)) {
            $data = $empleado->mostrar($id); 
            
            if ($data && is_array($data) && !empty($data)) { 
                echo json_encode($data); 
            } else {
                echo json_encode((object)[]); 
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID de empleado no proporcionado para mostrar']);
        }
        break;

    case "eliminar": 
        $empleado_id = isset($_POST["empleado_id"]) ? limpiarCadena($_POST["empleado_id"]) : "";
        if (!empty($empleado_id)) {
            $rpta = $empleado->eliminar($empleado_id); 
            echo json_encode(['status' => $rpta ? 'success' : 'error', 'message' => $rpta ? 'Registro eliminado correctamente' : 'No se pudo eliminar el registro']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID de empleado no proporcionado para eliminar']);
        }
        break;

    case "listar":
        $rpta = $empleado->Listar();
        $data = array();

        while ($reg = $rpta->fetch_object()) {
            $data[] = array(
                "0" => '<button class="btn btn-warning btn-xs" onclick="mostrar(' . $reg->id . ')"><i class="fa fa-pencil"></i></button>' .
                       ' <button class="btn btn-danger btn-xs" onclick="eliminar(' . $reg->id . ')"><i class="fa fa-trash"></i></button>',
                "1" => $reg->id,
                "2" => $reg->nombre,
                "3" => $reg->apellidos,
                "4" => $reg->documento_numero,
                "5" => $reg->telefono,
                "6" => $reg->codigo
            );
        }

        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count( $data),
            "aaData" => $data
        );

        echo json_encode($results);
        break;

    case "select_Empleado":
        $rpta = $empleado->Select();
        $options = "";
        while ($reg = $rpta->fetch_object()) {
            $options .= '<option value="' . $reg->id . '">' . $reg->nombre . ' ' . $reg->apellidos . '</option>';
        }
        echo json_encode(['html' => $options]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Operación no válida']);
        break;
}
?>