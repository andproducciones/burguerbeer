<?php

include "../../../conexion.php";
header('Content-Type: application/json');

// Datos del hotel
$configQ = mysqli_query($conection, "SELECT * FROM configuracion LIMIT 1");
$config = mysqli_fetch_assoc($configQ);
$razon_social = $config['razon_social'] ?? 'GRUPO CAÑALIMEÑA';
$nit = $config['nit'] ?? '';
$direccion = $config['direccion'] ?? '';
$telefono = $config['telefono'] ?? '';

$query = mysqli_query($conection, "
    SELECT 
        h.numero AS habitacion, 
        (rd.adultos + rd.ninos) AS total_desayunos,
        CONCAT(c.nombre, ' ', c.p_apellido) AS cliente
    FROM reservas_detalle rd
    INNER JOIN reservas r ON rd.idreserva = r.idreserva
    INNER JOIN habitaciones h ON rd.id_habitacion = h.idhabitacion
    INNER JOIN clientes c ON c.usuario = r.id_cliente
    WHERE 
        rd.incluye_desayuno = 1
        AND r.estado = 'checkin'
        AND CURDATE() BETWEEN DATE_ADD(r.fecha_entrada, INTERVAL 1 DAY) AND r.fecha_salida
    ORDER BY h.numero
");

$datos = [];
while ($row = mysqli_fetch_assoc($query)) {
    $datos[] = [
        'habitacion' => $row['habitacion'],
        'cantidad'   => $row['total_desayunos'],
        'cliente'    => $row['cliente']
    ];
}

echo json_encode([
    'config' => [
        'razon_social' => $razon_social,
        'nit' => $nit,
        'direccion' => $direccion,
        'telefono' => $telefono
    ],
    'desayunos' => $datos
]);
