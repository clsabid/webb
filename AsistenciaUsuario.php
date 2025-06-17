<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once "../modelos/AsistenciaUsuario.php"; 
require_once "../modelos/Usuario.php"; // Puedes requerir Usuario.php si lo usas para otras cosas aquí

$asistenciaUsuario = new AsistenciaUsuario();

$op = isset($_GET["op"]) ? $_GET["op"] : "";

switch ($op) {
    case "registrar": 
        $codigo_asistencia = isset($_POST["codigo"]) ? limpiarCadena($_POST["codigo"]) : "";
        $tipo_asistencia = isset($_POST["tipo"]) ? limpiarCadena($_POST["tipo"]) : ""; 

        if (empty($codigo_asistencia)) {
            echo json_encode(['status' => 'error', 'message' => 'Código de asistencia no proporcionado.']);
            exit();
        }

        $usuario_data = $asistenciaUsuario->buscarUsuarioPorCodigo($codigo_asistencia); 

        if ($usuario_data) {
            $id_usuario = $usuario_data['id']; 
            $nombre_completo_usuario = $usuario_data['nombre'] . ' ' . $usuario_data['apellidos'];

            $rpta = $asistenciaUsuario->registrarAsistencia($id_usuario, $codigo_asistencia, $tipo_asistencia);

            if ($rpta) {
                echo json_encode(['status' => 'success', 'message' => 'Asistencia de ' . $nombre_completo_usuario . ' registrada como ' . $tipo_asistencia . ' correctamente.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al registrar la asistencia del usuario.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No hay usuario registrado con este código: ' . $codigo_asistencia]);
        }
        break;

    case "listar":
        $rpta = $asistenciaUsuario->listar();
        $data = array();

        $item = 0;
        while ($reg = $rpta->fetch_object()) {
            $data[] = array(
                "0" => $reg->id,
                "1" => $reg->codigo,
                "2" => $reg->nombre_usuario . ' ' . $reg->apellidos_usuario, 
                "3" => $reg->hora,
                "4" => $reg->tipo == "entrada" ? '<span class="label bg-green">entrada</span>' : '<span class="label bg-orange">salida</span>',
                "5" => $reg->fecha
            );
            $item++;
        }

        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );

        echo json_encode($results);
        break;

    case "listar_asistencia": // Para reportes específicos de usuarios
        $fecha_inicio = isset($_REQUEST["fecha_inicio"]) ? $_REQUEST["fecha_inicio"] : "";
        $fecha_fin = isset($_REQUEST["fecha_fin"]) ? $_REQUEST["fecha_fin"] : "";
        $codigo_o_id_usuario = isset($_REQUEST["usuario_id"]) ? $_REQUEST["usuario_id"] : ""; // Usar usuario_id en lugar de empleado_id

        $rpta = $asistenciaUsuario->listar_reporte($fecha_inicio, $fecha_fin, $codigo_o_id_usuario); 

        $data = array();
        $item = 0;
        while ($reg = $rpta->fetch_object()) {
            $data[] = array(
                "0" => $item + 1,
                "1" => $reg->codigo,
                "2" => $reg->nombre_usuario . ' ' . $reg->apellidos_usuario, 
                "3" => $reg->fecha,
                "4" => $reg->hora,
                "5" => $reg->tipo == "entrada" ? '<span class="label bg-green">entrada</span>' : '<span class="label bg-orange">salida</span>'
            );
            $item++;
        }

        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );

        echo json_encode($results);
        break;
}