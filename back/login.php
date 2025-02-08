<?php

include('../conexion.php');

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
        //$hashedclave = clave_hash($post['clave'], clave_BCRYPT);
        //$hashedclave = md5($post['clave']);
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
                $data = $row;
                $respuesta = ['response' => 'Login successful', 'estado' => true];
            } else {
                $respuesta = ['response' => 'Crecenciales invalidas', 'estado' => false];
            }
        } else {
            $respuesta = ['response' => 'Falla en la consulta', 'estado' => false];
        }
        break;

    default:
        $respuesta = ['response' => 'No hay accion 2', 'estado' => false];
        break;
}

echo json_encode(['respuesta' => $respuesta, 'data' => $data]);
