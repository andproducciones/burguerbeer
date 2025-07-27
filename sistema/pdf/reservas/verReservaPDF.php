<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;

require_once __DIR__ . '/../../../conexion.php';
// ✅ Siempre funciona bien

date_default_timezone_set('America/Guayaquil');
function formatearFechaEspanol($fechaStr)
{
    $dias = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $fecha = strtotime($fechaStr);
    return ucfirst($dias[date('w', $fecha)]) . " " . date('d', $fecha) . " de " . $meses[date('n', $fecha) - 1] . " de " . date('Y', $fecha);
}

$fecha_emision = formatearFechaEspanol(date('Y-m-d H:i')) . " a las " . date('H:i');



$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Reserva inválida");
}

// =================== CONSULTA RESERVA ===================
$query = mysqli_query($conection, "
    SELECT r.*, 
           CONCAT(c.nombre, ' ', c.p_apellido) AS cliente, c.usuario, c.telefono, c.correo_c,
           u.nombre AS usuario_registra
    FROM reservas r
    INNER JOIN clientes c ON r.id_cliente = c.usuario
    LEFT JOIN usuario u ON r.usuario_id = u.usuario
    WHERE r.idreserva = $id
");
if (!$query || mysqli_num_rows($query) == 0) {
    die("Reserva no encontrada");
}
$reserva = mysqli_fetch_assoc($query);

// =================== DETALLE HABITACIONES ===================
$query_det = mysqli_query($conection, "
    SELECT h.numero, rd.adultos, rd.ninos,
           rd.incluye_desayuno, rd.incluye_tour,
           rd.precio_unitario, rd.precio_nino, rd.precio_desayuno, rd.precio_tour, rd.subtotal,
           lt.nombre AS lugar_tour
    FROM reservas_detalle rd
    INNER JOIN habitaciones h ON h.idhabitacion = rd.id_habitacion
    LEFT JOIN lugares_tour lt ON lt.id = rd.lugar_tour
    WHERE rd.idreserva = $id
");

$habitaciones = "";
$total_personas = 0;

if ($query_det && mysqli_num_rows($query_det) > 0) {
    $habitaciones .= "
    <table class='detalle'>
        <thead>
            <tr>
                <th>Hab</th>
                <th>Adultos</th>
                <th>Niños</th>
                <th>Tarifa Adulto</th>
                <th>Tarifa Niño</th>
                <th>Desayuno</th>
                <th>Tour</th>
                <th>Lugar Tour</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>";
    while ($row = mysqli_fetch_assoc($query_det)) {
        $total_personas += $row['adultos'] + $row['ninos'];
        $desayuno = ($row['incluye_desayuno']) ? 'Sí ($' . number_format($row['precio_desayuno'], 2) . ')' : 'No';
        $tour     = ($row['incluye_tour']) ? 'Sí ($' . number_format($row['precio_tour'], 2) . ')' : 'No';
        $lugar_tour = $row['lugar_tour'] ?? '-';

        $habitaciones .= "<tr>
            <td>{$row['numero']}</td>
            <td>{$row['adultos']}</td>
            <td>{$row['ninos']}</td>
            <td>$" . number_format($row['precio_unitario'], 2) . "</td>
            <td>$" . number_format($row['precio_nino'], 2) . "</td>
            <td>$desayuno</td>
            <td>$tour</td>
            <td>$lugar_tour</td>
            <td>$" . number_format($row['subtotal'], 2) . "</td>
        </tr>";
    }
    $habitaciones .= "</tbody></table>";
} else {
    $habitaciones = "<p>No hay habitaciones asociadas.</p>";
}

// =================== PAGOS ===================
$pagos_html = '';
$resPagos = mysqli_query($conection, "
    SELECT 
  monto,
  CASE metodo_pago
    WHEN 1 THEN 'Efectivo'
    WHEN 2 THEN 'Tarjeta'
    WHEN 3 THEN 'Transferencia'
    ELSE 'Otro'
  END AS metodo_pago,
  referencia_pago,
  DATE_FORMAT(fecha_pago, '%d/%m/%Y') AS fecha
FROM reservas_pagos
WHERE idreserva = $id
ORDER BY fecha ASC

");

$total_abonado = 0;
if ($resPagos && mysqli_num_rows($resPagos) > 0) {
    $pagos_html .= "
    <h3 style='margin:15px 0 5px;'>Pagos Registrados</h3>
    <table class='detalle'>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Método</th>
                <th>Referencia</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>";
    while ($p = mysqli_fetch_assoc($resPagos)) {
        $ref = $p['referencia_pago'] ?: '-';
        $total_abonado += $p['monto'];
        $pagos_html .= "<tr>
            <td>{$p['fecha']}</td>
            <td>{$p['metodo_pago']}</td>
            <td>$ref</td>
            <td>$" . number_format($p['monto'], 2) . "</td>
        </tr>";
    }
    $pagos_html .= "</tbody></table>";
}

$saldo = $reserva['total'] - $total_abonado;

$marca_agua_pagado = '';
if ($saldo <= 0) {
    $marca_agua_pagado = "
        <div style='position: fixed; top: 35%; left: 10%; width: 80%; text-align: center; 
                    font-size: 100px; color: rgba(255, 0, 0, 0.15); transform: rotate(-30deg); 
                    z-index: -1;'>
            PAGADO
        </div>
    ";
}

// =================== EMISIÓN Y LOGO ===================
$logoPath = __DIR__ . '/../../img/logo.jpg';
$logo = file_exists($logoPath) ? '<img src="data:image/jpg;base64,' . base64_encode(file_get_contents($logoPath)) . '" width="200">' : '';
$fecha_emision = date('d/m/Y H:i');
$firma = $logoPath;

$noches = max(1, round((strtotime($reserva['fecha_salida']) - strtotime($reserva['fecha_entrada'])) / 86400));
$dias = $noches + 1;

$estados_estadia = ['checkin', 'checkout', 'finalizada'];
$titulo_comprobante = in_array(strtolower($reserva['estado']), $estados_estadia)
    ? 'COMPROBANTE DE ESTADÍA'
    : 'COMPROBANTE DE RESERVA';

$numero_formateado = "01-" . date('Y') . "-" . str_pad($reserva['idreserva'], 4, '0', STR_PAD_LEFT);


$condiciones_html = '';

if (in_array(strtolower($reserva['estado']), ['checkin', 'checkout', 'finalizada'])) {
    // Condiciones para ESTADÍA
    $condiciones_html = "
<div class='terminos'>
    <p><strong>Términos y Condiciones de la Estadía:</strong></p>
    <ul style='padding-left:15px; margin:0; font-size:9px;'>
        <li>Check-in desde las 12:00 PM. Check-out hasta las 12:00 PM.</li>
        <li>Early Check-in sujeto a disponibilidad. Late Check-out tiene recargo de $10 por habitación hasta 6 horas adicionales.</li>
        <li>No se permite reingreso a habitaciones tras el check-out o entrega de llaves.</li>
        <li>Prohibido fumar o hacer fiestas en habitaciones. Actividades sociales solo en zonas comunes.</li>
        <li>Se exige trato respetuoso hacia el personal y huéspedes. Conductas agresivas serán motivo de desalojo.</li>
        <li>Grupo Cañalimeña se reserva el derecho de admisión y permanencia ante incumplimientos.</li>
        <li>El huésped será responsable por daños causados: 100% del valor del bien más $10 diarios si queda inhabilitado.</li>
        <li>Solo se permiten visitas con autorización previa y registro. Visitas no autorizadas se cobran como persona adicional.</li>
        <li>Desayuno y tours aplican solo si están incluidos en la reserva. Tours operados por terceros.</li>
        <li>El parqueadero es un servicio externo. El cliente asume plena responsabilidad sobre su uso.</li>
        <li>Solo se entrega nota de venta física. Para factura con RUC debe solicitarse durante la estadía. No se emite posterior.</li>
        <li>Objetos olvidados se conservan 7 días. No garantizamos recuperación ni envío.</li>
        <li>Se permiten mascotas con aviso previo. Costo por día según tamaño: pequeña $5, mediana $7, grande $10. Manchas o daños aplican cláusula de responsabilidad.</li>
        <li>Grupo Cañalimeña no se responsabiliza por fallos de terceros (tours, parqueo, taxis) ni por causas fortuitas (apagones, clima, etc.).</li>
        <li>Los datos están protegidos según la Ley Orgánica de Protección de Datos Personales (LOPDP).</li>
    </ul>
    <p style='margin-top:5px; font-style:italic; font-size:9px;'>
        <strong>Aviso:</strong> La permanencia en el establecimiento implica la aceptación total de estos términos. Reservas o abonos digitales constituyen aceptación electrónica legal.
    </p>
</div>";

} else {
    // Condiciones para RESERVA
    $condiciones_html = "
<div class='terminos'>
    <p><strong>Términos y Condiciones de la Reserva:</strong></p>
    <ul style='padding-left:15px; margin:0; font-size:9px;'>
        <li>Check-in desde las 12:00 PM. Check-out hasta las 12:00 PM del día de salida.</li>
        <li>Early Check-in sujeto a disponibilidad. Late Check-out tiene recargo de $10 por habitación (hasta 6 horas).</li>
        <li>Se requiere abono mínimo del 50% para confirmar. Reservas sin abono no están garantizadas.</li>
        <li>Cancelaciones:
            <ul style='margin-left:10px; list-style:circle;'>
                <li>Hasta 72h antes: sin penalización.</li>
                <li>Entre 48h y 71h: penalización del 50%.</li>
                <li>Menos de 24h o no presentación: sin reembolso.</li>
                <li>Casos de fuerza mayor serán evaluados con respaldo legal o médico.</li>
            </ul>
        </li>
        <li>Niños de 4 a 8 años: 20% de descuento. Desde 9 años aplica tarifa de adulto.</li>
        <li>Pago por: efectivo, tarjeta o transferencia con comprobante.</li>
        <li>El parqueadero es externo. Cliente asume su uso y riesgos.</li>
        <li>Desayunos y tours son opcionales. Tours operados por terceros. Solo garantizamos hora y destino.</li>
        <li>La factura debe solicitarse expresamente antes del Check-out. No se emite posterior si no fue pedida.</li>
        <li>Objetos olvidados serán custodiados por 7 días. No se garantiza recuperación ni envío.</li>
        <li>Se permiten mascotas previa notificación. Costo diario según tamaño: pequeña $5, mediana $7, grande $10. Daños o manchas se cobran según cláusula de daños.</li>
        <li>Los datos personales están protegidos conforme a la LOPDP vigente.</li>
    </ul>
    <p style='margin-top:5px; font-style:italic; font-size:9px;'>
        <strong>Aviso:</strong> Al realizar un abono, el cliente acepta íntegramente estos términos. Reservas digitales implican aceptación electrónica válida.
    </p>
</div>";



}


// =================== HTML ===================
$html = "
<style>
    @page { size: A5; margin: 10px 20px; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }
    h2, h3 { text-align: center; margin: 5px 0; }
    .header, .footer { text-align: center; margin: 5px 0; }
    .cliente, .datos, .resumen { margin: 8px 5px; }
    .cliente p, .datos p, .resumen p { margin: 2px 0; }
    .bold { font-weight: bold; }
    table.detalle { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 5px; }
    table.detalle th, table.detalle td { border: 1px solid #ccc; padding: 3px; text-align: center; }
    table.detalle thead { background-color: #eee; }
    .label_gracias { margin-top: 10px; text-align: center; font-style: italic; font-weight: bold; color: #444; }
    .terminos { font-size: 9px; border-top: 1px solid #ccc; padding-top: 5px; margin-top: 10px; }
    @page {
    margin: 10px 20px;
    @bottom-center {
        content: 'Grupo Cañalimeña RUC 1801096106 – Baños de Agua Santa, Ecuador';
        font-size: 10px;
    }
}

</style>

$marca_agua_pagado

<div class='header'>
    $logo
    <div style='text-align:center; font-size:11px; margin-bottom:5px; line-height:1.2;'>
        
        RUC 1801096106001<br>
        Espejo y 16 de Diciembre<br>
        hostalcanalimena.wixsite.com/hostalpage<br>
        0985385025
    </div>

</div>

<h2>{$titulo_comprobante}<br># {$numero_formateado}</h2>

<div class='cliente'>
    <p><span class='bold'>Cliente:</span> {$reserva['cliente']} ({$reserva['usuario']})</p>
    <p><span class='bold'>Tel:</span> {$reserva['telefono']}<br><span class='bold'>Correo:</span> {$reserva['correo_c']}</p>
</div>

<h3>Detalles de la " . (in_array(trim(strtolower($reserva['estado'])), ['checkin', 'checkout', 'finalizada']) ? 'Estadía' : 'Reserva') . "</h3>

<table style='width:100%; font-size:10px; margin-bottom:5px;'>
    <tr>
        <td><strong>Check-in:</strong> " . formatearFechaEspanol($reserva['fecha_entrada']) . "</td>
        <td><strong>Check-out:</strong> " . formatearFechaEspanol($reserva['fecha_salida']) . "</td>
    </tr>
    <tr>
        <td><strong>Estadía:</strong> {$noches} noche(s) / {$dias} día(s)</td>
        <td><strong>Estado:</strong> " . ucfirst($reserva['estado']) . "</td>
    </tr>
    <tr>
        <td><strong>Canal:</strong> " . ucfirst($reserva['canal_reserva']) . "</td>
        <td><strong>Registrado por:</strong> " . ($reserva['usuario_registra'] ?? '-') . "</td>
    </tr>
    <tr>
        <td colspan='2'><strong>Emitido:</strong> $fecha_emision</td>
    </tr>
</table>

<h3>Habitaciones</h3>
$habitaciones

$pagos_html

<div class='resumen'>
    <p><span class='bold'>Total de personas:</span> {$total_personas}</p>

    <table style='width:100%; font-size:10px; margin-top:10px; border-collapse:collapse;'>
        <tr>
            <td style='text-align:right;'>Subtotal:</td>
            <td style='text-align:right;'>$" . number_format($reserva['total'], 2) . "</td>
        </tr>
        <tr>
            <td style='text-align:right;'>IVA (0%):</td>
            <td style='text-align:right;'>$0.00</td>
        </tr>
        <tr>
            <td style='text-align:right; font-weight:bold;'>Total:</td>
            <td style='text-align:right; font-weight:bold;'>$" . number_format($reserva['total'], 2) . "</td>
        </tr>
        <tr>
            <td style='text-align:right;'>Total abonado:</td>
            <td style='text-align:right;'>$" . number_format($total_abonado, 2) . "</td>
        </tr>
        <tr>
            <td style='text-align:right;'>Saldo pendiente:</td>
            <td style='text-align:right;'>$" . number_format($saldo, 2) . "</td>
        </tr>
    </table>

    " . (!empty($reserva['observaciones']) ? "
<p style='margin-top:10px;'>
    <strong>Observaciones:</strong><br>
    <span style='display:inline-block; border:1px dashed #aaa; padding:5px; font-size:10px;'>
        " . nl2br(htmlspecialchars($reserva['observaciones'], ENT_QUOTES, 'UTF-8')) . "
    </span>
</p>" : "") . "

</div>


<div style='page-break-before: always;'>
    $condiciones_html

    <div style='margin-top:20px; font-size:10px;'>
        <p><strong>Nombre del Cliente:</strong> {$reserva['cliente']}</p><br><br><br>
        <p><strong>Firma del Cliente:</strong> ____________________________</p>
    </div>

    <div style='margin-top:25px; font-size:10px; text-align:right; padding-right:15px;'>
        <img src='data:image/png;base64," . base64_encode(file_get_contents($firma)) . "' width='120'><br>
        <p><strong>Yolanda Silva</strong><br>Gerente General – Grupo Cañalimeña</p>
    </div>

    <div class='label_gracias'>¡Gracias por preferirnos!</div>

    <div class='footer'>
        <hr style='border: 0; border-top: 1px solid #aaa; margin:10px 0;'>
        <p style='font-size:10px; text-align:center;'>Grupo Cañalimeña – RUC 1801096106 – Baños de Agua Santa, Ecuador</p>
    </div>
</div>

";

if (isset($_GET['modoCorreo'])) {
    echo $html; // ← ¡esto es lo que capturará ob_get_clean()!
    return;
}

// Si es visualización directa:
$dompdf = new Dompdf();
$options = $dompdf->getOptions();
$options->set('isRemoteEnabled', true);
$dompdf->setOptions($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A5', 'portrait');
$dompdf->render();
$nombre_archivo = ($titulo_comprobante == 'COMPROBANTE DE ESTADÍA') ? "estadia_{$id}.pdf" : "reserva_{$id}.pdf";
$dompdf->stream($nombre_archivo, ["Attachment" => false]);
