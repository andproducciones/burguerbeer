<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../email.php';
require_once __DIR__ . '/../../../conexion.php';

$response = [
    "status" => "ok",
    "procesados" => 0,
    "errores" => []
];

$limiteCorreos = 10;

$sql = mysqli_query(
    $conection,
    "SELECT * 
     FROM cola_envios 
     WHERE enviado = 0 
       AND intentos < 5
     LIMIT $limiteCorreos"
);

if (!$sql) {
    $response['status'] = 'error';
    $response['errores'][] = mysqli_error($conection);
    echo json_encode($response);
    exit;
}

$total = mysqli_num_rows($sql);
$procesados = 0;

while ($row = mysqli_fetch_assoc($sql)) {
    $id           = $row['id'];
    $correo       = $row['correo_destino'];
    $nombre       = $row['nombre_destino'];
    $asunto       = $row['asunto'];
    $contenido    = $row['contenido_html'];
    $imagenesJSON = $row['imagenes_embed'] ?? '[]';

    $imagenesEmbed = [];
    if (!empty($imagenesJSON)) {
        $imagenesEmbed = json_decode($imagenesJSON, true);
        if (!is_array($imagenesEmbed)) {
            $imagenesEmbed = [];
        } else {
            // Asegurarse de rutas absolutas para PHPMailer
            foreach ($imagenesEmbed as &$img) {
                if (isset($img['ruta'])) {
                    // Si la ruta no empieza con "/", prepéndele DOCUMENT_ROOT
                    if (strpos($img['ruta'], '/') !== 0) {
                        $img['ruta'] = $_SERVER['DOCUMENT_ROOT'] . '/burguerbeer/sistema/' . ltrim($img['ruta'], '/');
                    }
                }
            }
            unset($img);
        }
    }

    $ok = enviarCorreo(
        $correo,
        $nombre,
        $asunto,
        $contenido,
        [],                 // adjuntos
        $imagenesEmbed      // embed images
    );

    if ($ok) {
        mysqli_query(
            $conection,
            "UPDATE cola_envios 
             SET enviado = 1, fecha_envio = NOW() 
             WHERE id = $id"
        );
    } else {
        mysqli_query(
            $conection,
            "UPDATE cola_envios 
             SET intentos = intentos + 1 
             WHERE id = $id"
        );
        $response['errores'][] = "Error enviando a $correo. " . ($GLOBALS['lastPHPMailerError'] ?? '');
    }

    $procesados++;
}

$response['procesados'] = $procesados;
$response['total'] = $total;

echo json_encode($response, JSON_UNESCAPED_UNICODE);
