<?php
// controlador/Asistencia.php

// ✅ Mostrar errores PHP (solo para desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json'); // Asegura que la respuesta sea JSON

// Requerimos el archivo de conexión para tener acceso a 'limpiarCadena'
require_once "../admin/config/conexion.php"; 
// Requerimos el modelo Asistencia para usar sus métodos
require_once "../modelos/Asistencia.php"; 

// Instanciamos el modelo Asistencia
$asistencia = new Asistencia(); 

// Obtener la operación solicitada (op) y limpiarla para seguridad
$op = isset($_GET["op"]) ? limpiarCadena($_GET["op"]) : ""; 

switch ($op) {
    case "registrar": // Registrar asistencia desde QR para EMPLEADOS (tabla 'asistencia')
        // Limpiar y obtener datos del POST
        $codigo_asistencia = isset($_POST["codigo"]) ? limpiarCadena($_POST["codigo"]) : "";
        // El 'tipo' se determina automáticamente en el modelo, por lo que no es estrictamente necesario aquí,
        // pero lo mantengo si en algún punto decides forzar el tipo desde el frontend.
        // var $tipo_asistencia = isset($_POST["tipo"]) ? limpiarCadena($_POST["tipo"]) : ""; 

        if (empty($codigo_asistencia)) {
            echo json_encode(['status' => 'error', 'message' => 'Código no proporcionado.']);
            exit();
        }

        // Buscar el empleado por código usando el método del modelo Asistencia
        $empleado_data = $asistencia->buscarUsuarioPorCodigo($codigo_asistencia); 

        if ($empleado_data) {
            $id_empleado = $empleado_data['id'];
            $nombre_completo_empleado = $empleado_data['nombre'] . ' ' . $empleado_data['apellidos'];

            // Registrar asistencia. El método en el modelo decide si es entrada/salida.
            $rpta = $asistencia->registrarAsistencia($id_empleado, $codigo_asistencia);

            if ($rpta) {
                echo json_encode(['status' => 'success', 'message' => 'Asistencia de ' . $nombre_completo_empleado . ' registrada correctamente.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al registrar asistencia o ya se registraron entrada y salida para hoy.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No hay empleado registrado con este código: ' . $codigo_asistencia]);
        }
        break;

    case "listar": // Listar asistencias de EMPLEADOS para DataTables (tabla 'asistencia')
        $rpta = $asistencia->listar(); // Llama al método listar del modelo
        $data = array();

        if ($rpta) { // Asegúrate de que la consulta haya retornado un resultado válido
            while ($reg = $rpta->fetch_object()) {
                // Convertir a minúsculas y quitar espacios para una comparación robusta
                $tipo_limpio = strtolower(trim($reg->tipo)); 

                $data[] = array(
                    "0" => $reg->id, 
                    "1" => $reg->codigo,
                    "2" => $reg->empleado, // Usamos directamente la columna 'empleado'
                    "3" => $reg->hora,
                    // Lógica de color y texto para el tipo de asistencia
                    "4" => $tipo_limpio == "entrada" ? '<span class="label bg-green">entrada</span>' : '<span class="label bg-orange">salida</span>',
                    "5" => $reg->fecha
                );
            }
        } else {
            error_log("Error: La función listar() del modelo Asistencia devolvió false.");
            // Puedes enviar un mensaje de error al frontend si lo deseas
        }

        $results = array(
            "sEcho" => 1, // Número de draw (requerido por DataTables)
            "iTotalRecords" => count($data), // Total de registros en el array
            "iTotalDisplayRecords" => count($data), // Total de registros a mostrar (para paginación)
            "aaData" => $data // Los datos en sí
        );

        echo json_encode($results);
        break;

    case "listar_asistencia": // Reporte de asistencias de EMPLEADOS (desde un formulario de reporte)
        // Limpiar y obtener parámetros del REQUEST (GET o POST)
        $fecha_inicio = isset($_REQUEST["fecha_inicio"]) ? limpiarCadena($_REQUEST["fecha_inicio"]) : "";
        $fecha_fin = isset($_REQUEST["fecha_fin"]) ? limpiarCadena($_REQUEST["fecha_fin"]) : "";
        $codigo_o_id_usuario = isset($_REQUEST["empleado_id"]) ? limpiarCadena($_REQUEST["empleado_id"]) : ""; // Puede ser ID o código

        $rpta = $asistencia->listar_reporte($fecha_inicio, $fecha_fin, $codigo_o_id_usuario);

        $data = array();
        $item = 0; // Para numerar los ítems en el reporte si es necesario
        if ($rpta) {
            while ($reg = $rpta->fetch_object()) {
                $tipo_limpio = strtolower(trim($reg->tipo));

                $data[] = array(
                    "0" => $item + 1, // Columna para el número de ítem
                    "1" => $reg->codigo,
                    "2" => $reg->empleado,
                    "3" => $reg->fecha,
                    "4" => $reg->hora,
                    "5" => $tipo_limpio == "entrada" ? '<span class="label bg-green">entrada</span>' : '<span class="label bg-orange">salida</span>'
                );
                $item++;
            }
        } else {
             error_log("Error: La función listar_reporte() del modelo Asistencia devolvió false.");
        }

        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );

        echo json_encode($results);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Operación no válida']);
        break;
}
?>