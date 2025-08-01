<?php

include "../../conexion.php";
header('Content-Type: application/json');

try {
    $query_config = mysqli_query($conection, "SELECT * FROM configuracion LIMIT 1");
    $config = mysqli_fetch_assoc($query_config);
    $razon_social = $config['razon_social'] ?? '';
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

    if (!$query || mysqli_num_rows($query) === 0) {
        echo json_encode(["error" => "No hay desayunos programados hoy."]);
        exit;
    }

    $lineas = [];
    $lineas[] = strtoupper($razon_social);
    $lineas[] = "RUC: $nit";
    $lineas[] = "Tel: $telefono";
    $lineas[] = "$direccion";
    $lineas[] = str_repeat("-", 42);
    $lineas[] = "DESAYUNOS PROGRAMADOS - " . date('d/m/Y');
    $lineas[] = str_repeat("-", 42);

    while ($row = mysqli_fetch_assoc($query)) {
        $lineas[] = "Hab. {$row['habitacion']} - {$row['total_desayunos']} desayuno(s)";
        $lineas[] = "Cliente: " . strtoupper($row['cliente']);
        $lineas[] = "";
    }

    $lineas[] = str_repeat("-", 42);
    $lineas[] = "Preparación por cocina";
    $lineas[] = "Impreso: " . date('d/m/Y H:i');
    $lineas[] = "";

    echo json_encode(["contenido" => $lineas]);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
