<?php

require_once '../vendor/autoload.php';
use Dompdf\Dompdf;

include '../../../conexion.php';

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
    SELECT monto, metodo_pago, referencia_pago, DATE_FORMAT(fecha_pago, '%d/%m/%Y') AS fecha
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
$estado_pago_label = ($saldo <= 0) ? "<span style='color:green;font-weight:bold;'>✔ Pagado</span>" : "<span style='color:red;'>Pendiente</span>";

// =================== EMISIÓN Y LOGO ===================
$logoPath = __DIR__ . '/../../img/logo.jpg';
$logo = file_exists($logoPath) ? '<img src="data:image/jpg;base64,' . base64_encode(file_get_contents($logoPath)) . '" width="80">' : '';
$fecha_emision = date('d/m/Y H:i');

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
</style>

<div class='header'>$logo</div>
<h2>COMPROBANTE DE RESERVA</h2>
<h3>#{$reserva['idreserva']}</h3>

<div class='cliente'>
    <p><span class='bold'>Cliente:</span> {$reserva['cliente']} ({$reserva['usuario']})</p>
    <p><span class='bold'>Tel:</span> {$reserva['telefono']}<br><span class='bold'>Correo:</span> {$reserva['correo_c']}</p>
</div>

<div class='datos'>
    <p><span class='bold'>Entrada:</span> {$reserva['fecha_entrada']}</p>
    <p><span class='bold'>Salida:</span> {$reserva['fecha_salida']}</p>
    <p><span class='bold'>Noches:</span> " . max(1, round((strtotime($reserva['fecha_salida']) - strtotime($reserva['fecha_entrada'])) / 86400)) . "</p>
    <p><span class='bold'>Estado:</span> " . ucfirst($reserva['estado']) . "</p>
    <p><span class='bold'>Pago:</span> {$estado_pago_label}</p>
    <p><span class='bold'>Canal:</span> " . ucfirst($reserva['canal_reserva']) . "</p>
    <p><span class='bold'>Registrado por:</span> " . ($reserva['usuario_registra'] ?? '-') . "</p>
    <p><span class='bold'>Emitido:</span> $fecha_emision</p>
</div>

<h3>Habitaciones</h3>
$habitaciones

$pagos_html

<div class='resumen'>
    <p><span class='bold'>Total personas:</span> {$total_personas}</p>
    <p><span class='bold'>Total:</span> $" . number_format($reserva['total'], 2) . "</p>
    <p><span class='bold'>Abono:</span> $" . number_format($total_abonado, 2) . "</p>
    <p><span class='bold'>Saldo:</span> $" . number_format($saldo, 2) . "</p>
    <p><span class='bold'>Observaciones:</span><br>" . nl2br(htmlspecialchars($reserva['observaciones'] ?? '', ENT_QUOTES, 'UTF-8')) . "</p>
</div>

<div class='label_gracias'>¡Gracias por preferirnos!</div>
<div class='footer'>
    <p>Cancelaciones sin costo hasta 48h antes del ingreso.</p>
    <p>www.canalimena.com - Tel: 0985385025</p>
</div>
";

$dompdf = new Dompdf();
$options = $dompdf->getOptions();
$options->set('isRemoteEnabled', true);
$dompdf->setOptions($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A5', 'portrait');
$dompdf->render();
$dompdf->stream("reserva_{$id}.pdf", ["Attachment" => false]);
