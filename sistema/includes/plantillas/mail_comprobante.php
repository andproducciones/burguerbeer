<?php

require_once '../../../conexion.php';
require_once '../../pdf/vendor/autoload.php';
require_once '../email.php'; // aquí debe estar tu función enviarCorreo()

use Dompdf\Dompdf;

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("ID de reserva inválido");
}

// =================== CONSULTA RESERVA ===================
$query = mysqli_query($conection, "
    SELECT r.*, CONCAT(c.nombre, ' ', c.p_apellido) AS cliente, c.usuario, c.telefono, c.correo_c
    FROM reservas r
    INNER JOIN clientes c ON r.id_cliente = c.usuario
    WHERE r.idreserva = $id
");
if (!$query || mysqli_num_rows($query) == 0) {
    die("Reserva no encontrada");
}
$reserva = mysqli_fetch_assoc($query);

// =================== VALIDAR ESTADO ===================
$estadoValido = in_array(strtolower($reserva['estado']), ['confirmada', 'checkin', 'checkout', 'finalizada']);
if (!$estadoValido) {
    die("No se puede enviar comprobante en estado: {$reserva['estado']}");
}

// =================== GENERAR PDF ===================
$_GET['modoCorreo'] = true;
ob_start();
include __DIR__ . '/../../pdf/reservas/verReservaPDF.php';
$pdf_html = ob_get_clean();

$dompdf = new Dompdf();
$options = $dompdf->getOptions();
$options->set('isRemoteEnabled', true);
$dompdf->setOptions($options);
$dompdf->loadHtml($pdf_html);
$dompdf->setPaper('A5', 'portrait');
$dompdf->render();

$archivo_temp = __DIR__ . "/comprobante_temp_{$id}.pdf";
file_put_contents($archivo_temp, $dompdf->output());

// =================== ENVIAR CORREO ===================
$nombreCliente = $reserva['cliente'];
$correoCliente = $reserva['correo_c'];
$titulo = in_array(strtolower($reserva['estado']), ['checkin', 'checkout', 'finalizada']) ? 'Comprobante de estadía' : 'Comprobante de reserva';

// Plantilla HTML personalizada para el cuerpo del correo
$logoCID = 'logoGrupo';
$plantillaHTML = file_get_contents('../../plantillas/plantillaComprobante.html');
$plantillaHTML = str_replace('{{NOMBRE}}', $nombreCliente, $plantillaHTML);
$plantillaHTML = str_replace('{{TITULO}}', $titulo, $plantillaHTML);

$enviado = enviarCorreo(
    $correoCliente,
    $nombreCliente,
    "$titulo – Grupo C Cañalimeña",
    $plantillaHTML,
    [$archivo_temp],
    [['ruta' => '../../../img/logo.jpg', 'cid' => $logoCID]]
);

unlink($archivo_temp);

if ($enviado) {
    echo "✅ Correo enviado con éxito a: $correoCliente";
} else {
    echo "❌ ERROR: El comprobante no se pudo enviar al correo: $correoCliente. Por favor verifique que el correo sea válido y que exista conexión SMTP.";

    // Mostrar error técnico si se desea:
    if (!empty($GLOBALS['lastPHPMailerError'])) {
        echo "\n\n🛠️ Detalle técnico: " . $GLOBALS['lastPHPMailerError'];
    }

    // Opcional: enviar alerta al admin (puedes implementarlo si deseas)
}
