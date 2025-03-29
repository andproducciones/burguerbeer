<?php
// Encabezado para permitir solicitudes desde otros orígenes si lo necesitas
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Puedes agregar lógica si lo deseas (como verificar conexión a la base de datos)

$response = array(
    "estado" => true,
    "mensaje" => "✅ Conexión con el servidor exitosa"
);

echo json_encode($response);
?>
