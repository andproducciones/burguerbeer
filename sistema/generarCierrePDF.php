<?php
session_start();
require_once __DIR__ . '/libreries/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

include '../conexion.php';

$is_preview = isset($_GET['preview']) && $_GET['preview'] == 1;
$data = $is_preview ? ($_SESSION['preview_form'] ?? null) : ($_SESSION['data_cierre_pdf'] ?? null);

if (!$data) {
    die("No hay datos para generar el PDF.");
}

if ($is_preview && is_string($data)) {
    $html = $data;
} else {
    $data = array_merge([
        'idArqueo' => '',
        'nombre' => '',
        'apellido' => '',
        'fecha_inicio' => '',
        'fecha_fin' => '',
        'monto_inicial' => 0,
        'ventas_totales' => 0,
        'monto_total' => 0,
        'total_efectivo' => 0,
        'total_tarjeta' => 0,
        'total_transferencia' => 0,
        'total_deuna' => 0,
        'efectivo' => 0,
        'tarjeta' => 0,
        'transferencia' => 0,
        'deuna' => 0,
        'entregar' => 0,
        'salidas' => [],
        'observaciones' => '',
        'compras' => '',
        'total_salidas' => 0,
        'montoEfectivo' => 0,
        'montoTarjeta' => 0,
        'montoTransferencia' => 0,
        'montoDeUna' => 0,
        'empleado_1' => 0,
        'empleado_2' => 0,
        'empleado_3' => 0
    ], $data);

    $data['total_ventas'] = $data['ventas_totales'] ?? 0;
    $data['total_cash'] = $data['monto_total'] ?? 0;
    $data['monto_final'] = $data['entregar'] ?? 0;
    $data['total_movimientos'] = is_array($data['salidas']) ? array_sum(array_column($data['salidas'], 'valor')) : 0;

    $salarios = 0;
    $salarios += isset($data['empleado_1']) ? floatval($data['empleado_1']) : 0;
    $salarios += isset($data['empleado_2']) ? floatval($data['empleado_2']) : 0;
    $salarios += isset($data['empleado_3']) ? floatval($data['empleado_3']) : 0;
    $efectivoFinal = floatval($data['montoEfectivo']) - $salarios;

    $html = '<style>
        body { font-family: monospace; font-size: 11px; }
        h2, h3 { text-align: center; margin: 5px 0; }
        .line { border-top: 1px dashed black; margin: 6px 0; }
        .item { margin-left: 0px; }
    </style>';

    $html .= '<h2>CIERRE DE CAJA</h2>';
    $html .= "<div><strong>F. Inicio:</strong> {$data['fecha_inicio']}<br>";
    $html .= "<strong>F. Fin:</strong> {$data['fecha_fin']}<br>";
    $html .= "<strong>ID Cierre:</strong> {$data['idArqueo']}<br>";
    $html .= "<strong>Cajero:</strong> {$_SESSION['nombre']} {$_SESSION['apellido']}</div>";
    $html .= "<div class='line'></div>";

    $html .= "<div class='item'>Monto Inicial: $ " . number_format($data['monto_inicial'], 2) . "</div>";
    $html .= "<div class='line'></div>";

    $html .= "<h3>VENTAS DEL DÍA</h3>";
    $html .= "<div class='item'>Cantidad de Ventas: {$data['total_ventas']}</div>";
    $html .= "<div class='item'><strong>TOTAL VENTAS: $ " . number_format($data['monto_total'], 2) . "</strong></div>";
    $html .= "<div class='line'></div>";

    if (!empty($data['movimientos'])) {
        $html .= "<h3>MOVIMIENTOS DE CAJA</h3>";
        $html .= "<div>{$data['movimientos']}</div>";
        $html .= "<div class='item'><strong>TOTAL MOVIMIENTOS: $ " . number_format($data['total_salidas'], 2) . "</strong></div>";
        $html .= "<div class='line'></div>";
    }

    $html .= "<h3>MONTOS POR ENTREGAR</h3>";
    $html .= "<div class='item'>Efectivo: $ " . number_format($data['montoEfectivo'], 2) . "</div>";
    $html .= "<div class='item'>Tarjeta: $ " . number_format($data['montoTarjeta'], 2) . "</div>";
    $html .= "<div class='item'>Transferencia: $ " . number_format($data['montoTransferencia'], 2) . "</div>";
    $html .= "<div class='item'>DeUna: $ " . number_format($data['montoDeUna'], 2) . "</div>";
    $html .= "<div class='item'><strong>Efectivo Neto Final: $ " . number_format($efectivoFinal, 2) . "</strong></div>";
    $html .= "<div class='line'></div>";

    $html .= "<h3>MONTOS ENTREGADOS</h3>";
    $html .= "<div class='item'>Efectivo: $ " . number_format($data['efectivo'], 2) . "</div>";
    $html .= "<div class='item'>Tarjeta: $ " . number_format($data['tarjeta'], 2) . "</div>";
    $html .= "<div class='item'>Transferencia: $ " . number_format($data['transferencia'], 2) . "</div>";
    $html .= "<div class='item'>DeUna: $ " . number_format($data['deuna'], 2) . "</div>";

    $montoEntregado = $data['efectivo'] + $data['tarjeta'] + $data['transferencia'] + $data['deuna'];
    $html .= "<div class='item'><strong>TOTAL ENTREGADO: $ " . number_format($montoEntregado, 2) . "</strong></div>";
    $html .= "<div class='line'></div>";

    if (!empty($data['observaciones'])) {
        $html .= "<h3>OBSERVACIONES</h3>";
        $html .= "<div class='item'>" . nl2br(htmlspecialchars($data['observaciones'])) . "</div>";
        $html .= "<div class='line'></div>";
    }

    if (!empty($data['compras'])) {
        $html .= "<h3>COMPRAS PENDIENTES</h3>";
        $html .= "<div class='item'>" . nl2br(htmlspecialchars($data['compras'])) . "</div>";
        $html .= "<div class='line'></div>";
    }

    $html .= "<div style='text-align:center;'>CIERRE COMPLETO</div>";
    $html .= "<div class='line'></div>";
}

// Generar PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$customPaper = [0, 0, 226.77, 1000]; // 80mm de ancho
$dompdf->setPaper($customPaper, 'portrait');
$dompdf->render();
$dompdf->stream("CierreCaja_" . ($data['idArqueo'] ?? 'preview') . ".pdf", ["Attachment" => false]);
exit;
