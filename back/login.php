<?php

include('../conexion.php');
date_default_timezone_set('America/Guayaquil');

// Configurar encabezados
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Origin, Accept');
header('Content-Type: application/json; charset=utf-8');

// Leer el cuerpo de la solicitud
$post = json_decode(file_get_contents('php://input'), true);

// Verificar si hay acción definida
if (!isset($post['accion'])) {
    echo json_encode(['response' => 'No hay accion 1', 'estado' => false]);
    exit;
}

// Variables globales
$respuesta = [];
$data = [];

switch ($post['accion']) {
    case 'login':
        $usuario = $post['usuario'];
        $hashedclave = md5($post['clave']);

        // Verificar si el usuario existe
        $sql = sprintf(
            "SELECT * FROM usuario WHERE usuario='%s'",
            mysqli_real_escape_string($conection, $post['usuario'])
        );
        $query = mysqli_query($conection, $sql);

        if ($query->num_rows > 0) {
            $row = $query->fetch_assoc();

            // Verificar la contraseña
            if ($hashedclave == $row['clave']) {
                $idUser = $row['usuario'];

                // 🔐 Verificación de caja abierta
                $sqlCaja = "SELECT a.id_caja, c.lugar 
                            FROM arqueo_caja a 
                            INNER JOIN cajas c ON a.id_caja = c.id 
                            WHERE a.id_usuario = $idUser AND a.estatus = 1";

                $queryCaja = mysqli_query($conection, $sqlCaja);
                $resultCaja = mysqli_num_rows($queryCaja);

                if ($resultCaja === 1) {
                    $cajaData = mysqli_fetch_assoc($queryCaja);
                    
                    // Agregamos caja y lugar a la sesión de datos
                    $data = $row;
                    $data['fecha'] = date('Y-m-d H:i:s');
                    $data['id_caja'] = $cajaData['id_caja'];
                    $data['lugar'] = $cajaData['lugar'];

                    $respuesta = ['response' => 'Login successful', 'estado' => true];
                } else {
                    $respuesta = ['response' => 'Usuario no tiene una caja abierta', 'estado' => false];
                }

            } else {
                $respuesta = ['response' => 'Credenciales inválidas', 'estado' => false];
            }
        } else {
            $respuesta = ['response' => 'Falla en la consulta', 'estado' => false];
        }
        break;

    default:
        $respuesta = ['response' => 'No hay acción válida', 'estado' => false];
        break;
}

echo json_encode(['respuesta' => $respuesta, 'data' => $data ?? null]);

