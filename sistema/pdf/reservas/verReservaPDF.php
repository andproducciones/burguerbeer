<?php

require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;

require_once __DIR__ . '/../../../conexion.php';
require_once __DIR__ . '/../../includes/functions.php';
date_default_timezone_set('America/Guayaquil');

/* =================== Helpers =================== */


/* =================== Entrada =================== */
$idreserva = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_detalle = isset($_GET['detalle']) ? (int)$_GET['detalle'] : 0;

if ($idreserva <= 0 && $id_detalle <= 0) {
    die("Parámetros inválidos");
}

/* =================== Carga de contexto =================== */
/*
   Modos:
   - MODO RESERVA:   ?id=123  -> documento global de la reserva
   - MODO DETALLE:   ?detalle=456 -> documento por habitación (usa datos de la reserva si existe)
*/
$modo = $id_detalle > 0 ? 'detalle' : 'reserva';

$cliente_nombre = '';
$cliente_usuario = '';
$cliente_telefono = '';
$cliente_correo = '';
$usuario_registra = '-';

$fecha_entrada = '';
$fecha_salida  = '';
$estado_doc    = '';
$canal_reserva = '';
$total_doc     = 0.0;

$detalles = [];           // filas de habitaciones a renderizar (en modo detalle, solo 1)
$tiene_desayuno = $tiene_tour = $tiene_garaje = false;
$total_personas = 0;

$total_abonado = 0.0;     // sumatoria de pagos (por reserva o por detalle segun modo)
$pagos_html = '';

/* =================== MODO DETALLE =================== */
if ($modo === 'detalle') {
    $q = mysqli_query($conection, "
        SELECT
            rd.*,
            h.numero AS hab_numero,
            r.idreserva        AS r_id,
            r.estado           AS r_estado,
            r.fecha_entrada    AS r_in,
            r.fecha_salida     AS r_out,
            r.canal_reserva    AS r_canal,
            r.usuario_id       AS r_usuario_id,
            c.usuario          AS c_usuario,
            CONCAT(c.nombre,' ',c.p_apellido) AS c_nombre,
            c.telefono         AS c_tel,
            c.correo_c         AS c_mail,
            u.nombre           AS u_registra
        FROM reservas_detalle rd
        JOIN habitaciones h   ON h.idhabitacion = rd.id_habitacion
        LEFT JOIN reservas r  ON r.idreserva = rd.idreserva
        LEFT JOIN clientes c  ON c.usuario = COALESCE(r.id_cliente, rd.idcliente)
        LEFT JOIN usuario u   ON u.usuario = r.usuario_id
        WHERE rd.id = $id_detalle
        LIMIT 1
    ");
    if (!$q || mysqli_num_rows($q) === 0) {
        die("Detalle no encontrado");
    }
    $d = mysqli_fetch_assoc($q);

    // Cliente y cabecera
    $cliente_nombre  = $d['c_nombre'] ?? '';
    $cliente_usuario = $d['c_usuario'] ?? '';
    $cliente_telefono = $d['c_tel'] ?? '';
    $cliente_correo  = $d['c_mail'] ?? '';
    $usuario_registra = $d['u_registra'] ?: '-';

    if (!empty($d['r_id'])) {
        // tiene reserva padre: fechas/estado/canal desde RESERVA
        $idreserva     = (int)$d['r_id'];
        $fecha_entrada = $d['r_in'];
        $fecha_salida  = $d['r_out'];
        $estado_doc    = $d['r_estado'];
        $canal_reserva = $d['r_canal'];
    } else {
        // check-in directo: fechas/estado desde DETALLE
        $fecha_entrada = $d['fecha_entrada'];
        $fecha_salida  = $d['fecha_salida'];
        $estado_doc    = $d['estado_detalle'];
        $canal_reserva = 'directo';
        $idreserva     = 0; // se mantiene como 0
    }

    // Tabla de habitaciones: solo este detalle
    $detalles[] = [
        'numero'           => $d['hab_numero'],
        'adultos'          => (int)$d['adultos'],
        'ninos'            => (int)$d['ninos'],
        'incluye_desayuno' => (int)$d['incluye_desayuno'],
        'incluye_tour'     => (int)$d['incluye_tour'],
        'precio_unitario'  => (float)$d['precio_unitario'],
        'precio_nino'      => (float)$d['precio_nino'],
        'precio_desayuno'  => (float)$d['precio_desayuno'],
        'precio_tour'      => (float)$d['precio_tour'],
        'garaje'           => (float)$d['garaje'],
        'subtotal'         => (float)$d['subtotal'],
        'lugar_tour'       => (string)$d['lugar_tour']
    ];

    $total_doc = (float)$d['subtotal']; // en modo detalle el total es el subtotal del detalle

    $tiene_desayuno = !empty($d['incluye_desayuno']);
    $tiene_tour     = !empty($d['incluye_tour']);
    $tiene_garaje   = ((float)$d['garaje'] > 0);
    $total_personas = ((int)$d['adultos'] + (int)$d['ninos']);

    // Pagos: por DETALLE (tenga o no reserva)
    $resPagos = mysqli_query($conection, "
        SELECT 
          monto,
          CASE metodo_pago
            WHEN '1' THEN 'Efectivo'
            WHEN '2' THEN 'Tarjeta'
            WHEN '3' THEN 'Transferencia'
            ELSE metodo_pago
          END AS metodo_pago,
          referencia_pago,
          DATE_FORMAT(fecha_pago, '%d/%m/%Y') AS fecha
        FROM reservas_pagos
        WHERE id_detalle = $id_detalle
        ORDER BY fecha_pago ASC
    ");
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
            $monto = (float)$p['monto'];
            $total_abonado += $monto;
            $pagos_html .= "<tr>
                <td>".htmlspecialchars($p['fecha'])."</td>
                <td>".htmlspecialchars($p['metodo_pago'])."</td>
                <td>".htmlspecialchars($ref)."</td>
                <td>$".number_format($monto, 2)."</td>
            </tr>";
        }
        $pagos_html .= "</tbody></table>";
    }

    /* =================== MODO RESERVA =================== */
} else {
    // Cabecera reserva
    $qr = mysqli_query($conection, "
        SELECT r.*,
               CONCAT(c.nombre,' ',c.p_apellido) AS cliente,
               c.usuario, c.telefono, c.correo_c,
               u.nombre AS usuario_registra
        FROM reservas r
        JOIN clientes c ON r.id_cliente = c.usuario
        LEFT JOIN usuario u ON r.usuario_id = u.usuario
        WHERE r.idreserva = $idreserva
        LIMIT 1
    ");
    if (!$qr || mysqli_num_rows($qr) === 0) {
        die("Reserva no encontrada");
    }
    $r = mysqli_fetch_assoc($qr);

    $cliente_nombre   = $r['cliente'];
    $cliente_usuario  = $r['usuario'];
    $cliente_telefono = $r['telefono'];
    $cliente_correo   = $r['correo_c'];
    $usuario_registra = $r['usuario_registra'] ?: '-';

    $fecha_entrada = $r['fecha_entrada'];
    $fecha_salida  = $r['fecha_salida'];
    $estado_doc    = $r['estado'];
    $canal_reserva = $r['canal_reserva'];
    $total_doc     = (float)$r['total'];

    // Detalles (todas las habitaciones)
    $qd = mysqli_query($conection, "
        SELECT h.numero, rd.adultos, rd.ninos,
               rd.incluye_desayuno, rd.incluye_tour,
               rd.precio_unitario, rd.precio_nino, rd.precio_desayuno, rd.precio_tour, rd.subtotal,
               rd.garaje,
               rd.lugar_tour
        FROM reservas_detalle rd
        JOIN habitaciones h ON h.idhabitacion = rd.id_habitacion
        WHERE rd.idreserva = $idreserva
    ");
    if ($qd && mysqli_num_rows($qd) > 0) {
        while ($row = mysqli_fetch_assoc($qd)) {
            $detalles[] = $row;
            if ($row['incluye_desayuno']) {
                $tiene_desayuno = true;
            }
            if ($row['incluye_tour']) {
                $tiene_tour = true;
            }
            if ($row['garaje'] > 0) {
                $tiene_garaje = true;
            }
            $total_personas += ((int)$row['adultos'] + (int)$row['ninos']);
        }
    }

    // Pagos: por RESERVA
    $resPagos = mysqli_query($conection, "
        SELECT 
          monto,
          CASE metodo_pago
            WHEN '1' THEN 'Efectivo'
            WHEN '2' THEN 'Tarjeta'
            WHEN '3' THEN 'Transferencia'
            ELSE metodo_pago
          END AS metodo_pago,
          referencia_pago,
          DATE_FORMAT(fecha_pago, '%d/%m/%Y') AS fecha
        FROM reservas_pagos
        WHERE idreserva = $idreserva
        ORDER BY fecha_pago ASC
    ");
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
            $monto = (float)$p['monto'];
            $total_abonado += $monto;
            $pagos_html .= "<tr>
                <td>".htmlspecialchars($p['fecha'])."</td>
                <td>".htmlspecialchars($p['metodo_pago'])."</td>
                <td>".htmlspecialchars($ref)."</td>
                <td>$".number_format($monto, 2)."</td>
            </tr>";
        }
        $pagos_html .= "</tbody></table>";
    }
}

/* =================== Render tabla de habitaciones =================== */
$habitaciones_html = '';
if (!empty($detalles)) {

    // ¿Hay algún niño en toda la lista?
    $hayNinos = false;
    foreach ($detalles as $r) {
        if (!empty($r['ninos']) && (int)$r['ninos'] > 0) {
            $hayNinos = true;
            break;
        }
    }

    $habitaciones_html .= "<table class='detalle'>
        <thead>
            <tr>
                <th>Hab</th>
                <th>Adultos</th>";
    if ($hayNinos) {
        $habitaciones_html .= "<th>Niños</th>";
    }
    $habitaciones_html .= "
                <th>Tarifa Adulto</th>";
    if ($hayNinos) {
        $habitaciones_html .= "<th>Tarifa Niño</th>";
    }
    if ($tiene_desayuno) {
        $habitaciones_html .= "<th>Desayuno</th>";
    }
    if ($tiene_tour) {
        $habitaciones_html .= "<th>Tour</th><th>Lugar Tour</th>";
    }
    if ($tiene_garaje) {
        $habitaciones_html .= "<th>Garaje</th>";
    }
    $habitaciones_html .= "<th>Subtotal</th>
            </tr>
        </thead>
        <tbody>";

    // cache para nombres de lugares
    $cache_lugares = [];

    foreach ($detalles as $row) {
        $desayuno = !empty($row['incluye_desayuno']) ? 'Sí ($'.number_format((float)$row['precio_desayuno'], 2).')' : 'No';
        $tour     = !empty($row['incluye_tour']) ? 'Sí ($'.number_format((float)$row['precio_tour'], 2).')' : 'No';

        $nombres_lugares = [];
        if (!empty($row['lugar_tour'])) {
            foreach (explode(',', (string)$row['lugar_tour']) as $id_lugar) {
                $id_lugar = (int)trim($id_lugar);
                if ($id_lugar > 0) {
                    if (!isset($cache_lugares[$id_lugar])) {
                        $resLugar = mysqli_query($conection, "SELECT nombre FROM lugares_tour WHERE id = $id_lugar LIMIT 1");
                        $cache_lugares[$id_lugar] = ($resLugar && mysqli_num_rows($resLugar))
                            ? mysqli_fetch_assoc($resLugar)['nombre']
                            : 'Desconocido';
                    }
                    $nombres_lugares[] = $cache_lugares[$id_lugar];
                }
            }
        }
        $lugar_tour = $nombres_lugares ? implode(', ', $nombres_lugares) : '-';
        $garaje_txt = (!empty($row['garaje']) && (float)$row['garaje'] > 0) ? 'Sí ($'.number_format((float)$row['garaje'], 2).')' : 'No';

        $habitaciones_html .= "<tr>
            <td>".htmlspecialchars((string)$row['numero'])."</td>
            <td>".(int)$row['adultos']."</td>";
        if ($hayNinos) {
            $habitaciones_html .= "<td>".(int)$row['ninos']."</td>";
        }
        $habitaciones_html .= "
            <td>$".number_format((float)$row['precio_unitario'], 2)."</td>";
        if ($hayNinos) {
            $habitaciones_html .= "<td>$".number_format((float)$row['precio_nino'], 2)."</td>";
        }
        if ($tiene_desayuno) {
            $habitaciones_html .= "<td>$desayuno</td>";
        }
        if ($tiene_tour) {
            $habitaciones_html .= "<td>$tour</td><td>".htmlspecialchars($lugar_tour)."</td>";
        }
        if ($tiene_garaje) {
            $habitaciones_html .= "<td>$garaje_txt</td>";
        }
        $habitaciones_html .= "<td>$".number_format((float)$row['subtotal'], 2)."</td>
        </tr>";
    }

    $habitaciones_html .= "</tbody></table>";
} else {
    $habitaciones_html = "<p>No hay habitaciones asociadas.</p>";
}


/* =================== Saldos y metadata =================== */
$saldo = round($total_doc - $total_abonado, 2);

// assets
$logoPath = __DIR__ . '/../../img/logo.jpg';
$logo = (file_exists($logoPath))
    ? '<img src="data:image/jpg;base64,'.base64_encode(@file_get_contents($logoPath)).'" width="200">'
    : '';

$firmaPath = __DIR__ . '/../../img/firma.jpg';
$firmaImg  = (file_exists($firmaPath))
    ? "<img src='data:image/png;base64," . base64_encode(@file_get_contents($firmaPath)) . "' width='120'>"
    : "<div style='width:120px;height:60px;border:1px dashed #999;display:inline-block;text-align:center;line-height:60px;color:#999;'>Sin firma</div>";

$qr_img = "<div style='width:100px;height:100px;border:1px dashed #999;display:inline-block;text-align:center;line-height:100px;color:#999;'>QR</div>";
$qr_url = 'https://api.qrserver.com/v1/create-qr-code/?data=https://wa.me/593985385025&size=100x100';
$qr_data = @file_get_contents($qr_url);
if ($qr_data) {
    $qr_img = "<img src='data:image/png;base64,".base64_encode($qr_data)."' width='100'>";
}

$noches = max(1, (int)round((strtotime($fecha_salida) - strtotime($fecha_entrada)) / 86400));
$dias   = $noches + 1;

// Determinar si es “reserva” o “estadía” segun estado (de reserva o de detalle)
$estado_lc = mb_strtolower((string)$estado_doc, 'UTF-8');
$es_estadia = in_array($estado_lc, ['checkin','checkout','finalizada']);
$titulo_comprobante = $es_estadia ? 'COMPROBANTE DE ESTADÍA' : 'COMPROBANTE DE RESERVA';

// Numeración amigable
if ($modo === 'reserva') {
    $numero_formateado = "R-" . date('Y') . "-" . str_pad($idreserva, 4, '0', STR_PAD_LEFT);
} else {
    $numero_formateado = ($idreserva > 0)
        ? "R-" . date('Y') . "-{$idreserva}-D" . str_pad($id_detalle, 3, '0', STR_PAD_LEFT)
        : "D-" . date('Y') . "-" . str_pad($id_detalle, 4, '0', STR_PAD_LEFT);
}

// Marca de agua si está pagado (en el alcance actual)
$marca_agua_pagado = '';
if ($saldo <= 0.00001) {
    $marca_agua_pagado = "
        <div style='position: fixed; top: 35%; left: 10%; width: 80%; text-align: center; 
                    font-size: 100px; color: rgba(255, 0, 0, 0.15); transform: rotate(-30deg); 
                    z-index: -1;'>
            PAGADO
        </div>
    ";
}

// Observaciones (solo si venimos por reserva y existen)
$observaciones_html = '';
if ($modo === 'reserva') {
    $qr_obs = mysqli_query($conection, "SELECT observaciones FROM reservas WHERE idreserva = $idreserva");
    if ($qr_obs && mysqli_num_rows($qr_obs)) {
        $obs = mysqli_fetch_assoc($qr_obs)['observaciones'] ?? '';
        if (!empty($obs)) {
            $observaciones_html = "
            <p style='margin-top:10px;'>
                <strong>Observaciones:</strong><br>
                <span style='display:inline-block; border:1px dashed #aaa; padding:5px; font-size:10px;'>"
                . nl2br(htmlspecialchars($obs, ENT_QUOTES, 'UTF-8')) .
                "</span>
            </p>";
        }
    }
}

/* =================== Condiciones =================== */
if ($es_estadia) {
    $condiciones_html = "
<div class='terminos'>
    <p><strong>Términos y Condiciones de la Estadía:</strong></p>
    <ul>
        <li>Check-in 12:00–03:00 del día siguiente. Check-out hasta 12:00.</li>
        <li>Late Check-out $10 hasta 6 horas, sujeto a disponibilidad.</li>
        <li>No se permite reingreso tras check-out o entrega de llaves.</li>
        <li>Prohibido fumar/fiestas en habitaciones; uso de zonas comunes.</li>
        <li>Daños: 100% del valor del bien + $10 diarios por inhabilitación.</li>
        <li>Visitas solo con autorización previa; no autorizadas cuentan como persona adicional.</li>
        <li>Desayunos y tours aplican solo si están incluidos. Tours de terceros.</li>
        <li>Parqueadero con costo variable ($3–$5). Pérdida de ticket: recargo $2.</li>
        <li>Mascotas con aviso previo: $5/$7/$10 según tamaño por noche.</li>
        <li>Manchas/daños por mascotas: aplica indemnización.</li>
        <li>Nota de venta física solo si se solicita antes del check-out.</li>
        <li>Objetos olvidados se conservan 7 días.</li>
        <li>Conductas agresivas implican desalojo sin reembolso.</li>
        <li>Derecho de admisión y permanencia.</li>
        <li>Datos personales protegidos (LOPDP).</li>
        <li><strong>No responsable por ruidos o eventos externos ajenos.</strong></li>
    </ul>
</div>";
} else {
    $condiciones_html = "
<div class='terminos'>
    <p><strong>Términos y Condiciones de la Reserva:</strong></p>
    <ul>
        <li>Check-in 12:00–03:00 del día siguiente. Check-out hasta 12:00.</li>
        <li>Late Check-out $10 hasta 6 horas, sujeto a disponibilidad.</li>
        <li>La reserva se confirma solo con abono mínimo.</li>
        <li>Cancelaciones: hasta 72h total, 72–24h 50%, &lt;24h o no show sin reembolso.</li>
        <li>Niños 0–3 gratis; 4–8 años 25% desc; ≥9 tarifa completa.</li>
        <li>Pagos: efectivo, tarjeta o transferencia con referencia.</li>
        <li>Garaje $3–$5 por noche. Pérdida de ticket: recargo $2.</li>
        <li>Desayunos y tours opcionales deben constar en la reserva.</li>
        <li>No se responde por calidad de terceros (tours, parqueos, taxis).</li>
        <li><strong>No responsable por ruidos o eventos externos ajenos.</strong></li>
        <li>Nota de venta física solo si se solicita antes del check-out.</li>
        <li>Mascotas con aviso previo: $5/$7/$10 según tamaño.</li>
        <li>Daños/manchas por mascotas se cobran + $10/día de inhabilitación.</li>
        <li>Datos personales protegidos (LOPDP).</li>
    </ul>
</div>";
}

/* =================== HTML =================== */
$html = "
<meta charset='UTF-8'>
<style>
    @page {
        size: A5;
        margin: 10px 20px;
        @bottom-center {
            content: 'Grupo Cañalimeña RUC 1801096106 – Baños de Agua Santa, Ecuador';
            font-size: 10px;
        }
    }
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
</style>

$marca_agua_pagado

<div class='header'>
    $logo
    <div style='text-align:center; font-size:11px; margin-bottom:5px; line-height:1.2;'>
        RUC 1801096106001<br>
        Espejo y 16 de Diciembre<br>
        hostalcanalimena.wixsite.com/hostalpage<br>
        +593 985385025
    </div>
</div>

<h2>{$titulo_comprobante}<br># {$numero_formateado}</h2>

<div class='cliente'>
    <p><span class='bold'>Cliente:</span> ".htmlspecialchars($cliente_nombre).($cliente_usuario ? " (".htmlspecialchars($cliente_usuario).")" : "")."</p>
    <p><span class='bold'>Tel:</span> ".htmlspecialchars($cliente_telefono)."<br><span class='bold'>Correo:</span> ".htmlspecialchars($cliente_correo)."</p>
</div>

<h3>Detalles de la ".($es_estadia ? 'Estadía' : 'Reserva')."</h3>

<table style='width:100%; font-size:10px; margin-bottom:5px; border-collapse:collapse;'>
    <tr>
        <td><strong>Check-in:</strong> ".formatearFechaEspanol($fecha_entrada)."</td>
        <td><strong>Check-out:</strong> ".formatearFechaEspanol($fecha_salida)."</td>
    </tr>
    <tr>
        <td><strong>Estadía:</strong> {$noches} noche(s) / {$dias} día(s)</td>
        <td><strong>Estado:</strong> ".ucfirst($estado_doc)."</td>
    </tr>
    <tr>
        <td><strong>Canal:</strong> ".ucfirst($canal_reserva)."</td>
        <td><strong>Registrado por:</strong> ".htmlspecialchars($usuario_registra)."</td>
    </tr>
    <tr>
        <td colspan='2'><strong>Emitido:</strong> ".date('d/m/Y H:i')."</td>
    </tr>
</table>

<h3>Habitaciones</h3>
$habitaciones_html

$pagos_html

<div class='resumen'>
    <p><span class='bold'>Total de personas:</span> {$total_personas}</p>

    <table style='width:100%; font-size:10px; margin-top:10px; border-collapse:collapse;'>
        <tr>
            <td style='text-align:right;'>Subtotal:</td>
            <td style='text-align:right;'>$".number_format($total_doc, 2)."</td>
        </tr>
        <tr>
            <td style='text-align:right;'>IVA (0%):</td>
            <td style='text-align:right;'>$0.00</td>
        </tr>
        <tr>
            <td style='text-align:right; font-weight:bold;'>Total:</td>
            <td style='text-align:right; font-weight:bold;'>$".number_format($total_doc, 2)."</td>
        </tr>
        <tr>
            <td style='text-align:right;'>Total abonado:</td>
            <td style='text-align:right;'>$".number_format($total_abonado, 2)."</td>
        </tr>
        <tr>
            <td style='text-align:right;'>Saldo pendiente:</td>
            <td style='text-align:right;'>$".number_format($saldo, 2)."</td>
        </tr>
    </table>

    ".($modo === 'reserva' ? $observaciones_html : '')."
</div>

<div style='page-break-before: always;'></div>

$condiciones_html

<div style='margin-top:20px; font-size:10px;'>
    <p><strong>Nombre del Cliente:</strong> ".htmlspecialchars($cliente_nombre)."</p>
</div>

<div style='margin-top:25px; font-size:10px; text-align:right; padding-right:15px;'>
    $firmaImg<br>
    <p><strong>Yolanda Silva</strong><br>Gerente General – Grupo Cañalimeña</p>
</div>
<hr style='border: 0; border-top: 1px solid #aaa; margin:10px 0;'>
<div class='label_gracias'>¡Gracias por preferirnos!</div>

<div class='footer'>
    
    <div style='text-align:center; margin-top:10px;'>
        $qr_img
        <p style='font-size:9px;'>Escanea para contactarnos por WhatsApp</p>
    </div>
</div>
";

/* =================== Salida =================== */
if (isset($_GET['modoCorreo'])) {
    echo $html; // lo capturará enviarComprobanteUniversal()
    return;
}

//echo $html;
//exit;

$dompdf = new Dompdf();
$options = $dompdf->getOptions();
$options->set('isRemoteEnabled', true);
$dompdf->setOptions($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A5', 'portrait');
$dompdf->render();
$nombre_archivo = ($es_estadia ? "estadia" : "reserva") . "_" . ($modo === 'reserva' ? $idreserva : "det{$id_detalle}") . ".pdf";
$dompdf->stream($nombre_archivo, ["Attachment" => false]);
