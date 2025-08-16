<?php

date_default_timezone_set('America/Guayaquil');

use Dompdf\Dompdf;

// ======= (Opcional) Autoloads de librerías externas =======
// Ajusta a tu estructura real. Descomenta la que te aplique.
@include_once __DIR__ . '/../libreries/mike42/autoload.php';
// include_once 'C:\wamp64\www\burguerbeer\sistema\libreries\mike42\autoload.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

// ======= Constantes de impresoras =======
if (!defined('PRN_COMANDAS')) {
    define('PRN_COMANDAS', 'comandas');
}   // térmica restaurante
if (!defined('PRN_MATRICIAL')) {
    define('PRN_MATRICIAL', 'matricial');
} // matricial (impacto)
if (!defined('PRN_HOTEL')) {
    define('PRN_HOTEL', 'hotel2');
}        // térmica hotel

function fechaC(): string
{
    $mes = ["","Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio",
            "Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
    return date('d') . " de " . $mes[date('n')] . " de " . date('Y');
}

function buscarCliente(): string
{
    include "../conexion.php";
    mysqli_set_charset($conection, 'utf8mb4');

    $options = '';
    if ($q = mysqli_query($conection, "SELECT usuario, nombre, p_apellido FROM clientes WHERE estatus = 1 AND usuario <> 1")) {
        if (mysqli_num_rows($q) > 0) {
            while ($data = mysqli_fetch_assoc($q)) {
                $u = htmlspecialchars($data['usuario']);
                $n = htmlspecialchars($data['nombre']);
                $a = htmlspecialchars($data['p_apellido']);
                $options .= "<option value=\"$u\">$u | $n $a</option>";
            }
        } else {
            $options = '<option value="">No se encontraron clientes</option>';
        }
        mysqli_free_result($q);
    } else {
        $options = '<option value="">Error consultando clientes</option>';
    }

    @mysqli_close($conection);
    return $options;
}

/* ===================== IMPRESIONES (RESTAURANTE) ===================== */

function imprimirComandaMatricial($numeroMesa, $nombreMesera, array $productos)
{
    try {
        $printer = new Printer(new WindowsPrintConnector(PRN_MATRICIAL));

        $printer->setEmphasis(true);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("BURGER BEER\n");
        $printer->text("Mesa: $numeroMesa\n");
        $printer->text("Mesera: $nombreMesera\n");
        $printer->setEmphasis(false);
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text(str_repeat("-", 39) . "\n");

        // Productos: [nombre, cantidad, nombre, cantidad, ...]
        for ($i = 0; $i + 1 < count($productos); $i += 2) {
            $nombreProducto = (string)$productos[$i];
            $cantidad       = (string)$productos[$i + 1];
            $printer->text("$nombreProducto x $cantidad\n");
        }

        $printer->cut();
        try {
            $printer->close();
        } catch (\Throwable $e) {
        }
        return true;
    } catch (\Throwable $e) {
        return "Error: " . $e->getMessage();
    }
}

function imprimirFacturaMatricial(array $factura, $tl_sniva, $total, array $productos)
{
    try {
        include "../../conexion.php";
        mysqli_set_charset($conection, 'utf8mb4');

        $config = mysqli_fetch_assoc(mysqli_query($conection, "SELECT razon_social, nombre, nit, direccion, telefono FROM configuracion LIMIT 1")) ?: [];
        $razon_social = $config['razon_social'] ?? '';
        $nombre       = $config['nombre'] ?? '';
        $nit          = $config['nit'] ?? '';
        $direccion    = $config['direccion'] ?? '';
        $telefono     = $config['telefono'] ?? '';

        $nombre2    = $factura['nombre']     ?? '';
        $p_apellido = $factura['p_apellido'] ?? '';
        $direccion2 = $factura['direccion']  ?? '';
        $telefono2  = $factura['telefono']   ?? '';

        $printer = new Printer(new WindowsPrintConnector(PRN_MATRICIAL));

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text("$razon_social\n");
        $printer->setEmphasis(false);
        $printer->text("$nombre\n$nit\n$telefono\n$direccion\n");
        $printer->text(str_repeat("-", 39) . "\n");
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Cliente: $nombre2 $p_apellido\n");
        $printer->text("RUC: \n");
        $printer->text("Dirección: $direccion2\n");
        $printer->text("Teléfono: $telefono2\n");
        $printer->text(str_repeat("-", 39) . "\n");

        // Productos: [nombre, cantidad, ...]
        for ($i = 0; $i + 1 < count($productos); $i += 2) {
            $nombreProducto = (string)$productos[$i];
            $cantidad       = (string)$productos[$i + 1];
            $printer->text("$nombreProducto x $cantidad\n");
        }

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("SUBTOTAL: $tl_sniva\n");
        $printer->text("IVA: $total\n");

        $printer->cut();
        try {
            $printer->close();
        } catch (\Throwable $e) {
        }
        return true;
    } catch (\Throwable $e) {
        return "Error: " . $e->getMessage();
    }
}

function imprimirFactura(array $factura, $nombreCliente, $tl_sniva, $total, array $productos)
{
    try {
        include "../../conexion.php";
        mysqli_set_charset($conection, 'utf8mb4');

        $config = mysqli_fetch_assoc(mysqli_query($conection, "SELECT razon_social, nombre, nit, direccion, telefono FROM configuracion LIMIT 1")) ?: [];
        $razon_social = $config['razon_social'] ?? '';
        $nit          = $config['nit'] ?? '';
        $direccion    = $config['direccion'] ?? '';
        $telefono     = $config['telefono'] ?? '';

        $printer = new Printer(new WindowsPrintConnector(PRN_COMANDAS));

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text("$razon_social\n");
        $printer->setEmphasis(false);
        $printer->text("RUC: $nit\nTelefono: $telefono\n$direccion\n");
        $printer->text(str_repeat("-", 47) . "\n\n");
        $printer->text("$nombreCliente\n");
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text(str_repeat("-", 47) . "\n");

        // Encabezado columnas
        $printer->setEmphasis(true);
        $nombreProducto2 = str_pad('Descripcion', 28);
        $cantidad2       = str_pad('Cant', 5);
        $precio2         = str_pad('Precio', 6);
        $preciototal2    = str_pad('Total', 6);
        $printer->text("$cantidad2 $nombreProducto2 $precio2 $preciototal2\n");
        $printer->setEmphasis(false);

        // Productos: [nombre, cant, precioUnit, total]
        for ($i = 0; $i + 3 < count($productos); $i += 4) {
            $nombreProducto = str_pad((string)$productos[$i], 28);
            $cantidad       = str_pad((string)$productos[$i + 1], 5);
            $precio         = str_pad((string)$productos[$i + 2], 6);
            $preciototal    = str_pad((string)$productos[$i + 3], 6);
            $printer->text("$cantidad $nombreProducto $precio $preciototal\n");
        }

        $printer->text(str_pad('', 5) . str_pad('', 28) . str_pad('Subtotal', 9) . str_pad($tl_sniva, 5) . "\n");
        $printer->text(str_pad('', 5) . str_pad('', 28) . str_pad('IVA 0%', 9) . str_pad('00.00', 5) . "\n");
        $printer->setEmphasis(true);
        $printer->text(str_pad('', 5) . str_pad('', 28) . str_pad('Total', 9) . str_pad($total, 5) . "\n");
        $printer->setEmphasis(false);
        $printer->text(str_repeat("-", 47) . "\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("¡Gracias por su compra!\n");

        $printer->cut();
        try {
            $printer->close();
        } catch (\Throwable $e) {
        }
        return true;
    } catch (\Throwable $e) {
        return "Error: " . $e->getMessage();
    }
}

function imprimirPrecuenta($mesa, $nombreCliente, $tl_sniva, $total, array $productos)
{
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $fecha        = date('Y-m-d G:i:s');
        $nombreMesero = trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? ''));

        $printer = new Printer(new WindowsPrintConnector(PRN_COMANDAS));

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text("BURGER BEER\n\n");
        $printer->text("PRE-CUENTA\n");
        $printer->setEmphasis(false);
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->setEmphasis(true);
        $printer->text("A nombre de: $nombreCliente\n");
        $printer->setEmphasis(false);
        $printer->text("Mesa: $mesa\n");
        $printer->text("Mesero: $nombreMesero\n");
        $printer->text("Fecha: $fecha\n");
        $printer->text(str_repeat("-", 47) . "\n");

        $nombreProducto2 = str_pad('Descripcion', 28);
        $cantidad2       = str_pad('Cant', 5);
        $precio2         = str_pad('Precio', 6);
        $preciototal2    = str_pad('Total', 6);

        $printer->setEmphasis(true);
        $printer->text("$cantidad2 $nombreProducto2 $precio2 $preciototal2\n");
        $printer->setEmphasis(false);

        for ($i = 0; $i + 3 < count($productos); $i += 4) {
            $nombreProducto = str_pad((string)$productos[$i], 28);
            $cantidad       = str_pad((string)$productos[$i + 1], 5);
            $precio         = str_pad((string)$productos[$i + 2], 6);
            $preciototal    = str_pad((string)$productos[$i + 3], 6);
            $printer->text("$cantidad $nombreProducto $precio $preciototal\n");
        }

        $printer->text(str_pad('', 5) . str_pad('', 28) . str_pad('Subtotal', 9) . str_pad($tl_sniva, 5) . "\n");
        $printer->text(str_pad('', 5) . str_pad('', 28) . str_pad('IVA 0%', 9) . str_pad('00.00', 5) . "\n");
        $printer->setEmphasis(true);
        $printer->text(str_pad('', 5) . str_pad('', 28) . str_pad('Total', 9) . str_pad($total, 5) . "\n");
        $printer->setEmphasis(false);

        $printer->text(str_repeat("-", 47) . "\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text("¿DESEA NOTA DE VENTA?\nDEJE SUS DATOS EN CAJA\n");
        $printer->setEmphasis(false);
        $printer->setJustification(Printer::JUSTIFY_LEFT);

        $printer->setEmphasis(true);
        $printer->text("Propina\n");
        $printer->setEmphasis(false);
        $printer->text(str_repeat("_", 47) . "\n");
        $printer->text("Nombre\n"      . str_repeat("_", 47) . "\n");
        $printer->text("RUC\n"         . str_repeat("_", 47) . "\n");
        $printer->text("Dirección\n"   . str_repeat("_", 47) . "\n");
        $printer->text("Teléfono\n"    . str_repeat("_", 47) . "\n");
        $printer->text("Correo\n"      . str_repeat("_", 47) . "\n");

        $printer->cut();
        try {
            $printer->close();
        } catch (\Throwable $e) {
        }
        return true;
    } catch (\Throwable $e) {
        return "Error: " . $e->getMessage();
    }
}

function imprimirComanda($numeroMesa, $nombreCliente, $nombreMesera, array $productos, $fecha)
{
    try {
        $printer = new Printer(new WindowsPrintConnector(PRN_COMANDAS));
        $printer->setTextSize(1, 1);

        $printer->setEmphasis(true);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("BURGER BEER\n\n");
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("A nombre de: $nombreCliente\n");
        $printer->setEmphasis(false);
        $printer->text("Mesa: $numeroMesa\n");
        $printer->text("Fecha: $fecha\n");
        $printer->text("Mesero: $nombreMesera\n");
        $printer->text(str_repeat("-", 48) . "\n");

        // Productos: [nombre, cantidad, observaciones]
        for ($i = 0; $i + 2 < count($productos); $i += 3) {
            $nombreProducto = (string)$productos[$i];
            $cantidad       = (string)$productos[$i + 1];
            $observaciones  = (string)$productos[$i + 2];
            $printer->text("$cantidad $nombreProducto\n");
            if ($observaciones !== '') {
                $printer->text("   $observaciones\n");
            }
        }

        $printer->cut();
        try {
            $printer->close();
        } catch (\Throwable $e) {
        }
        return true;
    } catch (\Throwable $e) {
        return "Error: " . $e->getMessage();
    }
}

/* ===================== Útiles de Caja / Seguridad ===================== */

function imprimirCierreCaja(array $data)
{
    include "../conexion.php";
    mysqli_set_charset($conection, 'utf8mb4');

    $nombreImpresora = PRN_COMANDAS;

    // Extraer datos del arreglo
    $fecha_inicio       = $data['fecha_inicio'];
    $fecha_fin          = $data['fecha_fin'];
    $id_cierre          = (int)$data['idArqueo'];
    $user               = (int)$data['idUser'];
    $nombre             = $data['nombre'];
    $apellido           = $data['apellido'];
    $monto_inicial      = (float)$data['monto_inicial'];
    $monto_final        = (float)$data['monto_final'];
    $total_ventas       = (float)$data['total_ventas'];
    $total_cash         = (float)$data['total_cash'];
    $efectivo           = (float)$data['efectivo'];
    $transferencia      = (float)$data['transferencia'];
    $tarjeta            = (float)$data['tarjeta'];
    $deuna              = (float)$data['deuna'];
    $total_salidas      = (float)$data['total_movimientos'];
    $salidas            = is_array($data['salidas']) ? $data['salidas'] : [];
    // El backend ya trae este total desde pagos_personal (salariosCierre)
    $salarios_total     = isset($data['salarios']) ? (float)$data['salarios'] : 0.0;

    // Valores calculados del sistema (montos por entregar)
    $totalEfectivo      = (float)$data['total_efectivo'];
    $totalTarjeta       = (float)$data['total_tarjeta'];
    $totalTransferencia = (float)$data['total_transferencia'];
    $totalDeUna         = (float)$data['total_deuna'];

    $observaciones      = $data['observaciones'] ?? '';
    $compras            = $data['compras'] ?? '';

    // --- Traer detalle de salarios (pagos_personal) para imprimir desglose ---
    $detalle_salarios = [];
    $total_por_tipo   = ['por_dia' => 0.0, 'por_cierre' => 0.0];
    $sql_det = "
        SELECT empleado, tipo, monto
        FROM pagos_personal
        WHERE arqueo_id = {$id_cierre}
        ORDER BY tipo, empleado
    ";
    if ($rs = mysqli_query($conection, $sql_det)) {
        while ($row = mysqli_fetch_assoc($rs)) {
            $tipo = $row['tipo']; // 'por_dia' o 'por_cierre'
            $empleado = $row['empleado'];
            $monto = (float)$row['monto'];
            $detalle_salarios[] = ['tipo' => $tipo, 'empleado' => $empleado, 'monto' => $monto];
            if (isset($total_por_tipo[$tipo])) {
                $total_por_tipo[$tipo] += $monto;
            }
        }
        mysqli_free_result($rs);
    }

    // Si no vino del backend, suma por seguridad
    if ($salarios_total <= 0 && !empty($detalle_salarios)) {
        $salarios_total = array_reduce($detalle_salarios, fn ($acc, $it) => $acc + (float)$it['monto'], 0.0);
    }

    // Cierre final en efectivo (efectivo neto – salarios)
    $monto_final_final = $totalEfectivo - $salarios_total;

    try {
        $printer = new Printer(new WindowsPrintConnector($nombreImpresora));
    } catch (\Throwable $e) {
        return false;
    }

    try {
        $printer->setTextSize(1, 1);

        // Encabezado
        $printer->setEmphasis(true);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("CIERRE DE CAJA # {$id_cierre}\n");
        $printer->setEmphasis(false);
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Fecha Inicio: {$fecha_inicio}\n");
        $printer->text("Fecha Final:  {$fecha_fin}\n");
        $printer->text("Cajero:       {$nombre} {$apellido}\n");
        $printer->text("------------------------------------------------\n");
        $printer->text("Monto Inicial:          $ " . number_format($monto_inicial, 2) . "\n");
        $printer->text("------------------------------------------------\n");

        // Ventas del día
        $printer->setEmphasis(true);
        $printer->text("VENTAS DEL DIA\n");
        $printer->setEmphasis(false);
        $printer->text("Cantidad de Ventas:     " . number_format($total_ventas, 0) . "\n");
        $printer->text("TOTAL EN VENTAS:        $ " . number_format($total_cash, 2) . "\n");
        $printer->text("------------------------------------------------\n");

        // Movimientos de caja
        $printer->setEmphasis(true);
        $printer->text("MOVIMIENTOS DE CAJA\n");
        $printer->setEmphasis(false);

        // Nota: tipo_transaccion: 1=Salida (resta), 2=Entrada (suma)
        foreach ($salidas as $salida) {
            $nombre_usuario = $salida['nombre_usuario'] ?? '';
            $motivo = $salida['motivo'] ?? '';
            $valor = (float)($salida['valor'] ?? 0);
            $tipo_moneda = (isset($salida['tipo_moneda']) && (int)$salida['tipo_moneda'] === 1) ? 'EF' : 'TR';
            if (isset($salida['tipo_transaccion']) && (int)$salida['tipo_transaccion'] === 1) {
                $valor = -abs($valor);
            }
            $printer->text("{$nombre_usuario} ({$tipo_moneda}): {$motivo} - $ " . number_format($valor, 2) . "\n");
        }
        $printer->text("------------------------------------------------\n");

        // Montos a entregar (cálculo del sistema)
        $printer->setEmphasis(true);
        $printer->text("MONTOS A ENTREGAR (Sistema)\n");
        $printer->setEmphasis(false);
        $printer->text("Efectivo:        $ " . number_format($totalEfectivo, 2) . "\n");
        $printer->text("Tarjeta:         $ " . number_format($totalTarjeta, 2) . "\n");
        $printer->text("Transferencia:   $ " . number_format($totalTransferencia, 2) . "\n");
        $printer->text("DeUna:           $ " . number_format($totalDeUna, 2) . "\n");
        $printer->text("------------------------------------------------\n");

        // Montos entregados (lo que digitó el cajero)
        $printer->setEmphasis(true);
        $printer->text("MONTOS ENTREGADOS (Cajero)\n");
        $printer->setEmphasis(false);
        $printer->text("Efectivo:               $ " . number_format($efectivo, 2) . "\n");
        $printer->text("Tarjeta:                $ " . number_format($tarjeta, 2) . "\n");
        $printer->text("Transferencia:          $ " . number_format($transferencia, 2) . "\n");
        $printer->text("DeUna:                  $ " . number_format($deuna, 2) . "\n");
        $printer->setEmphasis(true);
        $printer->text("ENTREGA TOTAL:          $ " . number_format($monto_final, 2) . "\n");
        $printer->setEmphasis(false);
        $printer->text("------------------------------------------------\n");

        // Auditoría de diferencias
        $q_auditoria = mysqli_query(
            $conection,
            "SELECT tipo_pago, estado, diferencia 
             FROM auditoria_cierre_caja 
             WHERE id_cierre = {$id_cierre}"
        );
        $novedad = false;
        $auditoria_detalle = [];
        if ($q_auditoria && mysqli_num_rows($q_auditoria) > 0) {
            while ($row = mysqli_fetch_assoc($q_auditoria)) {
                $auditoria_detalle[] = $row;
                if (strtoupper($row['estado']) !== 'OK') {
                    $novedad = true;
                }
            }
            mysqli_free_result($q_auditoria);
        }
        if ($novedad) {
            $printer->setEmphasis(true);
            $printer->text("RESULTADO DE AUDITORIA\n");
            $printer->setEmphasis(false);
            foreach ($auditoria_detalle as $row) {
                $printer->text("{$row['tipo_pago']}: {$row['estado']} - $ " . number_format((float)$row['diferencia'], 2) . "\n");
            }
            $printer->text("------------------------------------------------\n");
        }

        // Códigos de pago (opcional)
        if (!empty($data['pagos_codigos'])) {
            $printer->setEmphasis(true);
            $printer->text("DETALLE CODIGOS DE PAGO\n");
            $printer->setEmphasis(false);
            foreach ($data['pagos_codigos'] as $tipo => $codigos) {
                $printer->text(strtoupper($tipo) . "\n");
                foreach ($codigos as $c) {
                    $printer->text("  Cod: " . $c['codigo'] . "   $ " . $c['total'] . "\n");
                }
            }
            $printer->text("------------------------------------------------\n");
        }

        // === Detalle de pagos al personal ===
        $printer->setEmphasis(true);
        $printer->text("PAGOS AL PERSONAL\n");
        $printer->setEmphasis(false);

        if (!empty($detalle_salarios)) {
            $t_por_dia    = $total_por_tipo['por_dia'];
            $t_por_cierre = $total_por_tipo['por_cierre'];

            if ($t_por_dia > 0) {
                $printer->text("Mensual (por dia):\n");
                foreach ($detalle_salarios as $d) {
                    if ($d['tipo'] === 'por_dia') {
                        $printer->text("  {$d['empleado']}: $ " . number_format($d['monto'], 2) . "\n");
                    }
                }
                $printer->text("  Total por dia:   $ " . number_format($t_por_dia, 2) . "\n");
            }
            if ($t_por_cierre > 0) {
                $printer->text("Por cierre:\n");
                foreach ($detalle_salarios as $d) {
                    if ($d['tipo'] === 'por_cierre') {
                        $printer->text("  {$d['empleado']}: $ " . number_format($d['monto'], 2) . "\n");
                    }
                }
                $printer->text("  Total por cierre: $ " . number_format($t_por_cierre, 2) . "\n");
            }
        } else {
            $printer->text("  (Sin registros)\n");
        }
        $printer->setEmphasis(true);
        $printer->text("TOTAL SALARIOS:         $ " . number_format($salarios_total, 2) . "\n");
        $printer->setEmphasis(false);
        $printer->text("------------------------------------------------\n");

        // Observaciones / Compras
        if (!empty($observaciones)) {
            $printer->setEmphasis(true);
            $printer->text("OBSERVACIONES\n");
            $printer->setEmphasis(false);
            $printer->text($observaciones . "\n");
            $printer->text("------------------------------------------------\n");
        }
        if (!empty($compras)) {
            $printer->setEmphasis(true);
            $printer->text("COMPRAS\n");
            $printer->setEmphasis(false);
            $printer->text($compras . "\n");
            $printer->text("------------------------------------------------\n");
        }

        // Cierre final en efectivo (efectivo neto – salarios)
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text("CIERRE FINAL EN EFECTIVO  $ " . number_format($monto_final_final, 2) . "\n");
        $printer->setEmphasis(false);
        $printer->cut();

        try {
            $printer->close();
        } catch (\Throwable $e) {
        }
        return true;
    } catch (\Throwable $e) {
        try {
            $printer->close();
        } catch (\Throwable $e2) {
        }
        return false;
    }
}

function mostrarTicketCierre(array $data): void
{
    $monto_inicial      = (float)$data['monto_inicial'];
    $monto_final        = (float)$data['monto_final'];
    $total_ventas       = (float)$data['total_ventas'];
    $total_cash         = (float)$data['total_cash'];
    $total_venta        = $monto_inicial + $total_cash;

    $totalEfectivo      = (float)$data['total_efectivo'];
    $totalTarjeta       = (float)$data['total_tarjeta'];
    $totalTransferencia = (float)$data['total_transferencia'];
    $totalDeUna         = (float)$data['total_deuna'];

    $efectivo           = (float)$data['efectivo'];
    $transferencia      = (float)$data['transferencia'];
    $tarjeta            = (float)$data['tarjeta'];
    $deuna              = (float)$data['deuna'];

    $total_salidas      = (float)$data['total_movimientos'];
    $salidas            = is_array($data['salidas']) ? $data['salidas'] : [];

    echo "<pre style='font-family:monospace; font-size:13px'>";
    echo "CIERRE DE CAJA\n";
    echo "Fecha Inicio: {$data['fecha_inicio']}\n";
    echo "Fecha Fin:    {$data['fecha_fin']}\n";
    echo "Código:       {$data['idArqueo']}\n";
    echo "Cajero:       {$data['nombre']} {$data['apellido']}\n";
    echo str_repeat("-", 48) . "\n";
    echo "Monto Inicial:         $" . number_format($monto_inicial, 2) . "\n";
    echo "Cantidad de Ventas:    " . number_format($total_ventas, 0) . "\n";
    echo "Monto por Ventas:      $" . number_format($total_cash, 2) . "\n";
    echo "TOTAL VENTAS:          $" . number_format($total_venta, 2) . "\n";
    echo str_repeat("-", 48) . "\n";
    echo "DETALLE DEL SISTEMA\n";
    echo "Efectivo (calc):       $" . number_format($totalEfectivo, 2) . "\n";
    echo "Tarjeta (calc):        $" . number_format($totalTarjeta, 2) . "\n";
    echo "Transferencia (calc):  $" . number_format($totalTransferencia, 2) . "\n";
    echo "DeUna (calc):          $" . number_format($totalDeUna, 2) . "\n";

    if (!empty($salidas)) {
        echo str_repeat("-", 48) . "\n";
        echo "SALIDAS DE CAJA\n";
        foreach ($salidas as $s) {
            $tipo = ((int)($s['tipo_moneda'] ?? 1) === 1) ? 'EF' : 'TR';
            $valor = (float)($s['valor'] ?? 0);
            if ((int)($s['tipo_transaccion'] ?? 1) === 1) {
                $valor = -abs($valor);
            }
            echo "{$s['nombre_usuario']} ($tipo): {$s['motivo']} - $" . number_format($valor, 2) . "\n";
        }
        echo "Total Movimientos:     $" . number_format($total_salidas, 2) . "\n";
    }
    echo str_repeat("-", 48) . "\n";
    echo "MONTOS ENTREGADOS\n";
    echo "Efectivo:              $" . number_format($efectivo, 2) . "\n";
    echo "Tarjeta:               $" . number_format($tarjeta, 2) . "\n";
    echo "Transferencia:         $" . number_format($transferencia, 2) . "\n";
    echo "DeUna:                 $" . number_format($deuna, 2) . "\n";
    echo "TOTAL ENTREGADO:       $" . number_format($monto_final, 2) . "\n";
    echo str_repeat("-", 48) . "\n";
    echo "CIERRE COMPLETO\n";
    echo "</pre>";
}

function imprimirSalidaDinero(array $data)
{
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $fecha      = $data['fecha'];
        $id         = $data['id'];
        $nombre2    = $_SESSION['nombre']   ?? '';
        $apellido2  = $_SESSION['apellido'] ?? '';
        $nombre     = $data['nombre'];
        $monto      = $data['monto'];
        $motivo     = $data['motivo'];
        $moneda     = $data['moneda']; // EF/TR, etc.
        $tipo       = (int)$data['tipo']; // 1=SALIDA 2=ENTRADA
        $tipoN      = ($tipo === 2) ? "ENTRADA" : "SALIDA";

        $printer = new Printer(new WindowsPrintConnector(PRN_COMANDAS));
        $printer->setTextSize(1, 1);

        $printer->setEmphasis(true);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("$tipoN DE DINERO\n");
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->setEmphasis(false);
        $printer->text("Fecha: $fecha\n");
        $printer->text("ID: $id\n");
        $printer->text("Nombre: $nombre\n");
        $printer->text(str_repeat("-", 48) . "\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text("OBSERVACIONES\n");
        $printer->setEmphasis(false);
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->setEmphasis(true);
        $printer->text("Monto: $ $monto\n");
        $printer->setEmphasis(false);
        $printer->text("Tipo Transaccion: $moneda\n");
        $printer->text("Motivo: $motivo\n");
        $printer->text("Cajero: $nombre2 $apellido2\n");
        $printer->text(str_repeat("-", 48) . "\n\n\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("-----------------------\n");
        $printer->text("$nombre\n\n");

        $printer->cut();
        try {
            $printer->close();
        } catch (\Throwable $e) {
        }
        return true;
    } catch (\Throwable $e) {
        return "Error: " . $e->getMessage();
    }
}

function compararMontosEntregadosVsCalculados(array $calculado, array $entregado): array
{
    $diferencias     = [];
    $faltanteGeneral = 0.0;
    $sobranteGeneral = 0.0;

    foreach ($calculado as $tipo => $valorCalculado) {
        $valorEntregado = isset($entregado[$tipo]) ? (float)$entregado[$tipo] : 0.0;
        $diferencia     = round($valorEntregado - (float)$valorCalculado, 2);

        if ($diferencia < 0) {
            $diferencias[$tipo] = ['estado' => 'FALTANTE', 'diferencia' => abs($diferencia)];
            $faltanteGeneral   += abs($diferencia);
        } elseif ($diferencia > 0) {
            $diferencias[$tipo] = ['estado' => 'SOBRANTE', 'diferencia' => $diferencia];
            $sobranteGeneral   += $diferencia;
        } else {
            $diferencias[$tipo] = ['estado' => 'OK', 'diferencia' => 0];
        }
    }

    $diferencias['TOTAL'] = [
        'faltante' => $faltanteGeneral,
        'sobrante' => $sobranteGeneral,
        'estado'   => ($faltanteGeneral == 0 && $sobranteGeneral == 0) ? 'CUADRA' : 'DESCUADRE'
    ];

    return $diferencias;
}

function verificarSesionPOS(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['active'], $_SESSION['idUser'], $_SESSION['rol']) || $_SESSION['active'] !== true) {
        session_unset();
        session_destroy();
        header("Location: ../");
        exit;
    }

    $ip_actual = $_SERVER['REMOTE_ADDR']     ?? '';
    $ua_actual = $_SERVER['HTTP_USER_AGENT'] ?? '';

    if (isset($_SESSION['ip'], $_SESSION['ua']) && ($_SESSION['ip'] !== $ip_actual || $_SESSION['ua'] !== $ua_actual)) {
        session_unset();
        session_destroy();
        header("Location: ../index.php?secure=fail");
        exit;
    }
}

function sanearPost(array $post): array
{
    $limpio = [];
    foreach ($post as $key => $valor) {
        if (is_array($valor)) {
            $limpio[$key] = sanearPost($valor);
        } else {
            $valor = strip_tags($valor);
            $valor = preg_replace('/[<>{}"\'()%;$&#*!=\\\\[\]{}]/', '', $valor);
            $valor = trim($valor);
            $valor = htmlspecialchars($valor, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $limpio[$key] = $valor;
        }
    }
    return $limpio;
}

/* ===================== IMPRESIONES (HOTEL) ===================== */

function imprimirDesayunosHoy()
{
    try {
        include "../conexion.php";
        mysqli_set_charset($conection, 'utf8mb4');

        $hoy = date('Y-m-d');

        // Encabezado hotel
        $config = mysqli_fetch_assoc(mysqli_query($conection, "SELECT razon_social, nit, direccion, telefono FROM configuracion LIMIT 1")) ?: [];
        $razon_social = $config['razon_social'] ?? 'GRUPO CAÑALIMEÑA';
        $nit          = $config['nit'] ?? '';
        $direccion    = $config['direccion'] ?? '';
        $telefono     = $config['telefono'] ?? '';

        // Desayunos del día: detalle con estado_detalle=checkin, día siguiente a entrada hasta salida
        $sql = "
            SELECT 
                h.numero AS habitacion, 
                (d.adultos + d.ninos) AS total_desayunos,
                CONCAT(c.nombre, ' ', c.p_apellido) AS cliente
            FROM reservas_detalle d
            INNER JOIN habitaciones h ON h.idhabitacion = d.id_habitacion
            INNER JOIN clientes c     ON c.usuario = d.idcliente
            WHERE 
                d.incluye_desayuno = 1
                AND d.estado_detalle = 'checkin'
                AND ? BETWEEN DATE_ADD(d.fecha_entrada, INTERVAL 1 DAY) AND d.fecha_salida
            ORDER BY CAST(h.numero AS UNSIGNED)
        ";
        $stmt = mysqli_prepare($conection, $sql);
        mysqli_stmt_bind_param($stmt, "s", $hoy);
        mysqli_stmt_execute($stmt);
        $query = mysqli_stmt_get_result($stmt);

        if (!$query || mysqli_num_rows($query) === 0) {
            throw new Exception("No hay desayunos programados hoy.");
        }

        $printer = new Printer(new WindowsPrintConnector(PRN_HOTEL));

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text(mb_strtoupper($razon_social) . "\n");
        $printer->setEmphasis(false);
        $printer->text("RUC: $nit\n");
        $printer->text("Tel: $telefono\n");
        $printer->text("$direccion\n");
        $printer->text(str_repeat("-", 42) . "\n");
        $printer->setEmphasis(true);
        $printer->text("DESAYUNOS PROGRAMADOS - " . date('d/m/Y') . "\n");
        $printer->setEmphasis(false);
        $printer->text(str_repeat("-", 42) . "\n");

        while ($row = mysqli_fetch_assoc($query)) {
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $hab  = str_pad("Hab. " . $row['habitacion'], 15);
            $cant = str_pad("🟢 " . (int)$row['total_desayunos'] . " desayuno(s)", 25);
            $printer->text("$hab $cant\n");
            $printer->text("Cliente: " . mb_strtoupper($row['cliente']) . "\n\n");
        }

        $printer->text(str_repeat("-", 42) . "\n");
        $printer->text("Preparación por cocina\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Impreso: " . date('d/m/Y H:i') . "\n");
        $printer->cut();
        try {
            $printer->close();
        } catch (\Throwable $e) {
        }

        return true;
    } catch (\Throwable $e) {
        return "Error al imprimir: " . $e->getMessage();
    }
}

function imprimirComprobanteEstadia($idreserva = null, $id_detalle = null)
{
    include "../conexion.php";
    mysqli_set_charset($conection, 'utf8mb4');

    // ====== MODO DETALLE ======
    if (!is_null($id_detalle)) {
        $id = intval($id_detalle);
        if ($id <= 0) {
            return "❌ ID de detalle inválido.";
        }

        $q = mysqli_query($conection, "
            SELECT d.id, d.idcliente, d.fecha_entrada, d.fecha_salida, d.subtotal,
                   d.adultos, d.ninos, d.incluye_desayuno, d.incluye_tour, d.lugar_tour, d.garaje,
                   h.numero AS habitacion,
                   CONCAT(c.nombre,' ',c.p_apellido) AS cliente, c.usuario AS usuario_cliente
            FROM reservas_detalle d
            INNER JOIN habitaciones h ON h.idhabitacion = d.id_habitacion
            INNER JOIN clientes c     ON c.usuario = d.idcliente
            WHERE d.id = $id
            LIMIT 1
        ");
        if (!$q || mysqli_num_rows($q) === 0) {
            return "❌ Detalle no encontrado.";
        }
        $r = mysqli_fetch_assoc($q);

        // LUGARES
        $lugares = '';
        if (!empty($r['lugar_tour'])) {
            $csv = mysqli_real_escape_string($conection, $r['lugar_tour']);
            $qL = mysqli_query($conection, "
                SELECT GROUP_CONCAT(nombre SEPARATOR ', ') AS lugares
                FROM lugares_tour
                WHERE FIND_IN_SET(id, '$csv')
            ");
            if ($qL && mysqli_num_rows($qL)) {
                $lugares = mysqli_fetch_assoc($qL)['lugares'] ?? '';
            }
        }

        $numeroContrato = "01-".date('Y')."-D".str_pad($id, 4, '0', STR_PAD_LEFT);
        $hash           = strtoupper(substr(sha1("ESTADIADET{$id}".$r['fecha_entrada']), 0, 10));
        $cliente        = $r['cliente'];
        $usuario_cli    = $r['usuario_cliente'];
        $entrada        = function_exists('formatearFechaEspanol') ? formatearFechaEspanol($r['fecha_entrada']) : $r['fecha_entrada'];
        $salida         = function_exists('formatearFechaEspanol') ? formatearFechaEspanol($r['fecha_salida']) : $r['fecha_salida'];
        $total          = number_format((float)$r['subtotal'], 2);
        $servicios      = [];
        if ((int)$r['incluye_desayuno']) {
            $servicios[] = "Desayuno";
        }
        if ((int)$r['incluye_tour']) {
            $servicios[] = "Tour".($lugares ? ": $lugares" : "");
        }
        if ((float)$r['garaje'] > 0) {
            $servicios[] = "Garaje";
        }

        try {
            $printer = new Printer(new WindowsPrintConnector(PRN_HOTEL));
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("GRUPO CAÑALIMEÑA\n");
            $printer->text("COMPROBANTE DE ESTADÍA\n");
            $printer->setEmphasis(false);
            $printer->text("Contrato N°: $numeroContrato\n");
            $printer->text("Verificación: #$hash\n");
            $printer->text(str_repeat("-", 46)."\n");

            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("Cliente: $cliente ($usuario_cli)\n");
            $printer->text("Entrada: $entrada\n");
            $printer->text("Salida:  $salida\n");
            $printer->text("Hab: {$r['habitacion']} | Adultos: {$r['adultos']}  Niños: {$r['ninos']}\n");
            if ($servicios) {
                $printer->text("Servicios: ".implode(", ", $servicios)."\n");
            }

            $printer->text(str_repeat("-", 46)."\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("Total pagado: $ $total\n");
            $printer->setEmphasis(false);
            $printer->text("\n\n");
            $printer->text("________________________________________\n");
            $printer->text("$cliente ($usuario_cli)\n");
            $printer->text(str_repeat("-", 46)."\n");
            $printer->text("Al firmar, el cliente declara haber leído y\n");
            $printer->text("aceptado los términos enviados al correo.\n");

            $printer->cut();
            try {
                $printer->close();
            } catch (\Throwable $e) {
            }
            return true;
        } catch (\Throwable $e) {
            return "❌ Error de impresión: ".$e->getMessage();
        }
    }

    // ====== MODO RESERVA ======
    $id = intval($idreserva);
    if ($id <= 0) {
        return "❌ ID de reserva inválido.";
    }

    $query = mysqli_query($conection, "
        SELECT r.*, CONCAT(c.nombre,' ',c.p_apellido) AS cliente, c.usuario AS usuario_cliente
        FROM reservas r
        INNER JOIN clientes c ON r.id_cliente = c.usuario
        WHERE r.idreserva = $id
        LIMIT 1
    ");
    if (!$query || mysqli_num_rows($query) == 0) {
        return "❌ No se encontró la reserva.";
    }
    $reserva = mysqli_fetch_assoc($query);

    $detalle = mysqli_query($conection, "
        SELECT h.numero, d.adultos, d.ninos, d.incluye_desayuno, d.incluye_tour,
               d.lugar_tour, d.garaje
        FROM reservas_detalle d
        INNER JOIN habitaciones h ON h.idhabitacion = d.id_habitacion
        WHERE d.idreserva = $id
    ");
    $habitaciones = [];
    $adultos = 0;
    $ninos   = 0;
    $servicios = [];
    if ($detalle) {
        while ($row = mysqli_fetch_assoc($detalle)) {
            $habitaciones[] = $row['numero'];
            $adultos += (int)$row['adultos'];
            $ninos   += (int)$row['ninos'];

            $lugares = '';
            if (!empty($row['lugar_tour'])) {
                $csv = mysqli_real_escape_string($conection, $row['lugar_tour']);
                $qL  = mysqli_query($conection, "
                    SELECT GROUP_CONCAT(nombre SEPARATOR ', ') AS lugares
                    FROM lugares_tour
                    WHERE FIND_IN_SET(id, '$csv')
                ");
                if ($qL && mysqli_num_rows($qL)) {
                    $lugares = mysqli_fetch_assoc($qL)['lugares'] ?? '';
                }
            }
            if ((int)$row['incluye_desayuno']) {
                $servicios[] = "Desayuno";
            }
            if ((int)$row['incluye_tour']) {
                $servicios[] = "Tour".($lugares ? ": $lugares" : "");
            }
            if ((float)$row['garaje'] > 0) {
                $servicios[] = "Garaje";
            }
        }
    }

    $cliente     = $reserva['cliente'];
    $usuario_cli = $reserva['usuario_cliente'];
    $entrada     = function_exists('formatearFechaEspanol') ? formatearFechaEspanol($reserva['fecha_entrada']) : $reserva['fecha_entrada'];
    $salida      = function_exists('formatearFechaEspanol') ? formatearFechaEspanol($reserva['fecha_salida']) : $reserva['fecha_salida'];
    $total       = number_format((float)$reserva['total'], 2);
    $numeroC     = "01-".date('Y')."-".str_pad($id, 4, '0', STR_PAD_LEFT);
    $hash        = strtoupper(substr(sha1("ESTADIA{$id}".$reserva['fecha_entrada']), 0, 10));

    try {
        $printer = new Printer(new WindowsPrintConnector(PRN_HOTEL));
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text("GRUPO CAÑALIMEÑA\n");
        $printer->text("COMPROBANTE DE ESTADÍA\n");
        $printer->setEmphasis(false);
        $printer->text("Contrato de estadía N°: $numeroC\n");
        $printer->text("Verificación: #$hash\n");
        $printer->text(str_repeat("-", 48)."\n");

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Cliente: $cliente ($usuario_cli)\n");
        $printer->text("Entrada: $entrada\n");
        $printer->text("Salida:  $salida\n");
        $printer->text("Hab(s): ".implode(', ', $habitaciones)."\n");
        $printer->text("Adultos: $adultos  Niños: $ninos\n");
        if ($servicios) {
            $printer->text("Servicios: ".implode(', ', array_unique($servicios))."\n");
        }

        $printer->text(str_repeat("-", 48)."\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text("Total pagado: $ $total\n");
        $printer->setEmphasis(false);
        $printer->cut();
        try {
            $printer->close();
        } catch (\Throwable $e) {
        }
        return true;
    } catch (\Throwable $e) {
        return "❌ Error de impresión: ".$e->getMessage();
    }
}

function imprimirComprobanteEstadiaCliente($idreserva = null, $id_detalle = null)
{
    include "../conexion.php";
    mysqli_set_charset($conection, 'utf8mb4');

    // ====== DETALLE ======
    if (!is_null($id_detalle)) {
        $id = intval($id_detalle);
        if ($id <= 0) {
            return "❌ ID de detalle inválido.";
        }

        $q = mysqli_query($conection, "
            SELECT d.id, d.idcliente, d.fecha_entrada, d.fecha_salida, d.subtotal, d.adultos, d.ninos,
                   d.incluye_desayuno, d.incluye_tour, d.lugar_tour, d.garaje, d.precio_unitario,
                   d.precio_nino, d.precio_desayuno, d.precio_tour,
                   h.numero AS habitacion,
                   CONCAT(c.nombre,' ',c.p_apellido) AS cliente, c.usuario AS usuario_cliente,
                   c.telefono, c.correo_c AS correo
            FROM reservas_detalle d
            INNER JOIN habitaciones h ON h.idhabitacion = d.id_habitacion
            INNER JOIN clientes c     ON c.usuario = d.idcliente
            WHERE d.id = $id
            LIMIT 1
        ");
        if (!$q || mysqli_num_rows($q) === 0) {
            return "❌ Detalle no encontrado.";
        }
        $r = mysqli_fetch_assoc($q);

        $lugares = '';
        if (!empty($r['lugar_tour'])) {
            $csv = mysqli_real_escape_string($conection, $r['lugar_tour']);
            $rs  = mysqli_query($conection, "
                SELECT GROUP_CONCAT(nombre SEPARATOR ', ') AS lugares
                FROM lugares_tour
                WHERE FIND_IN_SET(id, '$csv')
            ");
            if ($rs && mysqli_num_rows($rs)) {
                $lugares = mysqli_fetch_assoc($rs)['lugares'] ?? '';
            }
        }

        $numeroC  = "01-".date('Y')."-D".str_pad($id, 4, '0', STR_PAD_LEFT);
        $hash     = strtoupper(substr(sha1("ESTADIADET{$id}".$r['fecha_entrada']), 0, 10));
        $entrada  = formatearFechaEspanol($r['fecha_entrada']);
        $salida   = formatearFechaEspanol($r['fecha_salida']);
        $total    = number_format((float)$r['subtotal'], 2);

        $servs = [];
        if ((int)$r['incluye_desayuno']) {
            $servs[] = "Desayuno";
        }
        if ((int)$r['incluye_tour']) {
            $servs[] = "Tour".($lugares ? ": $lugares" : "");
        }
        if ((float)$r['garaje'] > 0) {
            $servs[] = "Garaje";
        }

        try {
            $printer = new Printer(new WindowsPrintConnector(PRN_HOTEL));
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("GRUPO CAÑALIMEÑA\n");
            $printer->setEmphasis(false);
            $printer->text("COMPROBANTE SIN VALOR TRIBUTARIO\n");
            $printer->text(str_repeat("-", 48)."\n");

            $printer->setEmphasis(true);
            $printer->text("COMPROBANTE DE ESTADÍA\n");
            $printer->setEmphasis(false);
            $printer->text("Contrato N°: $numeroC\n");
            $printer->text("Verificación: #$hash\n");
            $printer->text(str_repeat("-", 48)."\n");

            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("Cliente: {$r['cliente']} ({$r['usuario_cliente']})\n");
            $printer->text("Entrada: $entrada\n");
            $printer->text("Salida:  $salida\n");
            $printer->text("Hab: {$r['habitacion']} | Adultos: {$r['adultos']}  Niños: {$r['ninos']}\n");
            if ($servs) {
                $printer->text("Servicios: ".implode(", ", $servs)."\n");
            }

            $printer->text(str_repeat("-", 48)."\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("Total pagado: $ $total\n");
            $printer->setEmphasis(false);
            $printer->text(str_repeat("-", 48)."\n");
            $printer->setEmphasis(true);
            $printer->text("¡Gracias por preferirnos!\n");
            $printer->setEmphasis(false);
            $printer->cut();

            try {
                $printer->close();
            } catch (\Throwable $e) {
            }
            return true;
        } catch (\Throwable $e) {
            return "❌ Error de impresión cliente: ".$e->getMessage();
        }
    }

    // ====== RESERVA ======
    $id = intval($idreserva);
    if ($id <= 0) {
        return "❌ ID de reserva inválido.";
    }

    $qR = mysqli_query($conection, "
        SELECT r.*, CONCAT(c.nombre,' ',c.p_apellido) AS cliente, c.usuario AS usuario_cliente
        FROM reservas r
        INNER JOIN clientes c ON r.id_cliente = c.usuario
        WHERE r.idreserva = $id
        LIMIT 1
    ");
    if (!$qR || mysqli_num_rows($qR) === 0) {
        return "❌ Reserva no encontrada.";
    }
    $reserva = mysqli_fetch_assoc($qR);

    $detalle = mysqli_query($conection, "
        SELECT h.numero, d.adultos, d.ninos, d.incluye_desayuno, d.incluye_tour, d.lugar_tour,
               d.garaje, d.precio_unitario
        FROM reservas_detalle d
        INNER JOIN habitaciones h ON h.idhabitacion = d.id_habitacion
        WHERE d.idreserva = $id
    ");

    $habitaciones = [];
    $adultos = 0;
    $ninos = 0;
    $servicios = [];
    $aplicaPromo = false;
    $personas = 0;

    while ($row = mysqli_fetch_assoc($detalle)) {
        $habitaciones[] = $row['numero'];
        $adultos   += (int)$row['adultos'];
        $ninos     += (int)$row['ninos'];
        $personas  += (int)$row['adultos'] + (int)$row['ninos'];
        if ((float)$row['precio_unitario'] >= 12) {
            $aplicaPromo = true;
        }

        $lugares = '';
        if (!empty($row['lugar_tour'])) {
            $csv = mysqli_real_escape_string($conection, $row['lugar_tour']);
            $rs  = mysqli_query($conection, "
                SELECT GROUP_CONCAT(nombre SEPARATOR ', ') AS lugares
                FROM lugares_tour
                WHERE FIND_IN_SET(id, '$csv')
            ");
            if ($rs && mysqli_num_rows($rs)) {
                $lugares = mysqli_fetch_assoc($rs)['lugares'] ?? '';
            }
        }
        if ((int)$row['incluye_desayuno']) {
            $servicios[] = "Desayuno";
        }
        if ((int)$row['incluye_tour']) {
            $servicios[] = "Tour".($lugares ? ": $lugares" : "");
        }
        if ((float)$row['garaje'] > 0) {
            $servicios[] = "Garaje";
        }
    }

    $entrada = formatearFechaEspanol($reserva['fecha_entrada']);
    $salida  = formatearFechaEspanol($reserva['fecha_salida']);
    $total   = number_format((float)$reserva['total'], 2);
    $numeroC = "01-".date('Y')."-".str_pad($id, 4, '0', STR_PAD_LEFT);
    $hash    = strtoupper(substr(sha1("ESTADIA{$id}".$reserva['fecha_entrada']), 0, 10));

    try {
        $printer = new Printer(new WindowsPrintConnector(PRN_HOTEL));
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text("GRUPO CAÑALIMEÑA\n");
        $printer->setEmphasis(false);
        $printer->text("COMPROBANTE SIN VALOR TRIBUTARIO\n");
        $printer->text(str_repeat("-", 48)."\n");

        $printer->setEmphasis(true);
        $printer->text("COMPROBANTE DE ESTADÍA\n");
        $printer->setEmphasis(false);
        $printer->text("Contrato N°: $numeroC\n");
        $printer->text("Verificación: #$hash\n");
        $printer->text(str_repeat("-", 48)."\n");

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Cliente: {$reserva['cliente']} ({$reserva['usuario_cliente']})\n");
        $printer->text("Entrada: $entrada\n");
        $printer->text("Salida:  $salida\n");
        $printer->text("Hab(s): ".implode(', ', $habitaciones)."\n");
        $printer->text("Adultos: $adultos  Niños: $ninos\n");
        if ($servicios) {
            $printer->text("Servicios: ".implode(', ', array_unique($servicios))."\n");
        }

        $printer->text(str_repeat("-", 48)."\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text("Total pagado: $ $total\n");
        $printer->setEmphasis(false);
        $printer->text(str_repeat("-", 48)."\n");
        $printer->setEmphasis(true);
        $printer->text("¡Gracias por preferirnos!\n");
        $printer->setEmphasis(false);
        $printer->cut();

        try {
            $printer->close();
        } catch (\Throwable $e) {
        }
        return true;
    } catch (\Throwable $e) {
        return "❌ Error de impresión cliente: ".$e->getMessage();
    }
}

function imprimirTicketsTourYGaraje($idreserva = null, $id_detalle = null)
{
    include "../conexion.php";
    mysqli_set_charset($conection, 'utf8mb4');

    // ====== DETALLE ======
    if (!is_null($id_detalle)) {
        $id = intval($id_detalle);
        if ($id <= 0) {
            return "❌ ID de detalle inválido.";
        }

        $q = mysqli_query($conection, "
            SELECT d.*, h.numero AS habitacion
            FROM reservas_detalle d
            INNER JOIN habitaciones h ON h.idhabitacion = d.id_habitacion
            WHERE d.id = $id
            LIMIT 1
        ");
        if (!$q || mysqli_num_rows($q) === 0) {
            return "❌ Detalle no encontrado.";
        }
        $d = mysqli_fetch_assoc($q);

        // TOUR
        if ((int)$d['incluye_tour']) {
            $lugares = 'Destino';
            if (!empty($d['lugar_tour'])) {
                $csv = mysqli_real_escape_string($conection, $d['lugar_tour']);
                $rs  = mysqli_query($conection, "
                    SELECT GROUP_CONCAT(nombre SEPARATOR ', ') AS lugares
                    FROM lugares_tour
                    WHERE FIND_IN_SET(id, '$csv')
                ");
                if ($rs && mysqli_num_rows($rs)) {
                    $lugares = mysqli_fetch_assoc($rs)['lugares'] ?? $lugares;
                }
            }
            $hash     = strtoupper(substr(sha1("TOUR".$d['habitacion'].$d['fecha_entrada'].$lugares), 0, 10));
            $personas = (int)$d['adultos'] + (int)$d['ninos'];

            try {
                $p = new Printer(new WindowsPrintConnector(PRN_HOTEL));
                $p->setJustification(Printer::JUSTIFY_CENTER);
                $p->setEmphasis(true);
                $p->text("TICKET DE TOUR\n");
                $p->setEmphasis(false);
                $p->setJustification(Printer::JUSTIFY_LEFT);
                $p->text("Habitación: ".$d['habitacion']."\n");
                $p->text("Fecha:      ".$d['fecha_entrada']."\n");
                $p->text("Destino:    $lugares\n");
                $p->text("Personas:   $personas\n");
                $p->text("Código:     #$hash\n");
                $p->cut();
                try {
                    $p->close();
                } catch (\Throwable $e) {
                }
            } catch (\Throwable $e) {
                return "❌ Error al imprimir ticket de tour: ".$e->getMessage();
            }
        }

        // GARAJE (un ticket por noche)
        if ((float)$d['garaje'] > 0) {
            $dias = max(1, (strtotime($d['fecha_salida']) - strtotime($d['fecha_entrada'])) / 86400);
            for ($i = 0; $i < $dias; $i++) {
                $fechaG = date('Y-m-d', strtotime("+$i days", strtotime($d['fecha_entrada'])));
                $hash   = strtoupper(substr(sha1("GARAJE".$d['habitacion'].$fechaG), 0, 10));
                try {
                    $p = new Printer(new WindowsPrintConnector(PRN_HOTEL));
                    $p->setJustification(Printer::JUSTIFY_CENTER);
                    $p->setEmphasis(true);
                    $p->text("TICKET DE GARAJE\n");
                    $p->setEmphasis(false);
                    $p->setJustification(Printer::JUSTIFY_LEFT);
                    $p->text("Habitación: ".$d['habitacion']."\n");
                    $p->text("Fecha:      $fechaG\n");
                    $p->text("Código:     #$hash\n");
                    $p->text("Horario:    18h00 - 09h00\n");
                    $p->text("Un (1) vehículo por habitación\n");
                    $p->cut();
                    try {
                        $p->close();
                    } catch (\Throwable $e) {
                    }
                } catch (\Throwable $e) {
                    return "❌ Error al imprimir ticket de garaje ($fechaG): ".$e->getMessage();
                }
            }
        }
        return true;
    }

    // ====== RESERVA ======
    $id = intval($idreserva);
    if ($id <= 0) {
        return "❌ ID de reserva inválido.";
    }

    $detalle = mysqli_query($conection, "
        SELECT h.numero AS habitacion, d.adultos, d.ninos, d.incluye_tour, d.lugar_tour,
               d.garaje, r.fecha_entrada, r.fecha_salida, d.id_habitacion
        FROM reservas_detalle d
        INNER JOIN habitaciones h ON h.idhabitacion = d.id_habitacion
        INNER JOIN reservas r     ON r.idreserva = d.idreserva
        WHERE d.idreserva = $id
        ORDER BY d.id_habitacion ASC
    ");
    if (!$detalle || mysqli_num_rows($detalle) === 0) {
        return "❌ La reserva no tiene detalles asociados.";
    }

    $impresosGaraje = [];
    while ($row = mysqli_fetch_assoc($detalle)) {
        $habitacion = $row['habitacion'];
        $personas   = (int)$row['adultos'] + (int)$row['ninos'];

        // TOUR
        if ((int)$row['incluye_tour']) {
            $lugares = 'Destino';
            if (!empty($row['lugar_tour'])) {
                $csv = mysqli_real_escape_string($conection, $row['lugar_tour']);
                $rs  = mysqli_query($conection, "
                    SELECT GROUP_CONCAT(nombre SEPARATOR ', ') AS lugares
                    FROM lugares_tour
                    WHERE FIND_IN_SET(id, '$csv')
                ");
                if ($rs && mysqli_num_rows($rs)) {
                    $lugares = mysqli_fetch_assoc($rs)['lugares'] ?? $lugares;
                }
            }
            $hash = strtoupper(substr(sha1("TOUR$habitacion".$row['fecha_entrada'].$lugares), 0, 10));
            try {
                $p = new Printer(new WindowsPrintConnector(PRN_HOTEL));
                $p->setJustification(Printer::JUSTIFY_CENTER);
                $p->setEmphasis(true);
                $p->text("TICKET DE TOUR\n");
                $p->setEmphasis(false);
                $p->setJustification(Printer::JUSTIFY_LEFT);
                $p->text("Habitación: $habitacion\n");
                $p->text("Fecha:      ".$row['fecha_entrada']."\n");
                $p->text("Destino:    $lugares\n");
                $p->text("Personas:   $personas\n");
                $p->text("Código:     #$hash\n");
                $p->cut();
                try {
                    $p->close();
                } catch (\Throwable $e) {
                }
            } catch (\Throwable $e) {
                return "❌ Error al imprimir ticket de tour (Hab. $habitacion): ".$e->getMessage();
            }
        }

        // GARAJE (evitar duplicar por habitación)
        if ((float)$row['garaje'] > 0 && !in_array($row['id_habitacion'], $impresosGaraje, true)) {
            $impresosGaraje[] = $row['id_habitacion'];
            $dias = max(1, (strtotime($row['fecha_salida']) - strtotime($row['fecha_entrada'])) / 86400);
            for ($d = 0; $d < $dias; $d++) {
                $fechaG = date('Y-m-d', strtotime("+$d days", strtotime($row['fecha_entrada'])));
                $hash = strtoupper(substr(sha1("GARAJE$habitacion$fechaG"), 0, 10));
                try {
                    $p = new Printer(new WindowsPrintConnector(PRN_HOTEL));
                    $p->setJustification(Printer::JUSTIFY_CENTER);
                    $p->setEmphasis(true);
                    $p->text("TICKET DE GARAJE\n");
                    $p->setEmphasis(false);
                    $p->setJustification(Printer::JUSTIFY_LEFT);
                    $p->text("Habitación: $habitacion\n");
                    $p->text("Fecha:      $fechaG\n");
                    $p->text("Código:     #$hash\n");
                    $p->text("Horario:    18h00 - 09h00\n");
                    $p->text("Un (1) vehículo por habitación\n");
                    $p->cut();
                    try {
                        $p->close();
                    } catch (\Throwable $e) {
                    }
                } catch (\Throwable $e) {
                    return "❌ Error al imprimir ticket de garaje (Hab. $habitacion - $fechaG): ".$e->getMessage();
                }
            }
        }
    }
    return true;
}

function imprimirTicketsTourHoy()
{
    try {
        include "../conexion.php";
        mysqli_set_charset($conection, 'utf8mb4');

        $hoy = date('Y-m-d');
        $cfg = mysqli_fetch_assoc(mysqli_query($conection, "SELECT razon_social, nit, direccion, telefono FROM configuracion LIMIT 1")) ?: [];
        $razon_social = $cfg['razon_social'] ?? '';
        $nit          = $cfg['nit'] ?? '';
        $direccion    = $cfg['direccion'] ?? '';
        $telefono     = $cfg['telefono'] ?? '';

        // Tours HOY: detalle en checkin, desde día siguiente a entrada
        $sql = "
            SELECT 
                h.numero AS habitacion,
                (d.adultos + d.ninos) AS total_personas,
                CONCAT(c.nombre, ' ', c.p_apellido) AS cliente,
                d.id AS id_detalle
            FROM reservas_detalle d
            INNER JOIN habitaciones h ON d.id_habitacion = h.idhabitacion
            INNER JOIN clientes c     ON c.usuario = d.idcliente
            WHERE d.incluye_tour = 1
              AND d.estado_detalle = 'checkin'
              AND ? BETWEEN DATE_ADD(d.fecha_entrada, INTERVAL 1 DAY) AND d.fecha_salida
            ORDER BY CAST(h.numero AS UNSIGNED)
        ";
        $stmt = mysqli_prepare($conection, $sql);
        mysqli_stmt_bind_param($stmt, "s", $hoy);
        mysqli_stmt_execute($stmt);
        $query = mysqli_stmt_get_result($stmt);

        if (!$query || mysqli_num_rows($query) == 0) {
            throw new Exception("No hay tours programados hoy.");
        }

        $printer = new Printer(new WindowsPrintConnector(PRN_HOTEL));

        while ($row = mysqli_fetch_assoc($query)) {
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text(mb_strtoupper($razon_social) . "\n");
            $printer->setEmphasis(false);
            $printer->text("RUC: $nit\nTel: $telefono\n$direccion\n");
            $printer->text(str_repeat("-", 42) . "\n");
            $printer->setEmphasis(true);
            $printer->text("TICKET DE TOUR\n");
            $printer->setEmphasis(false);
            $printer->text("Fecha: " . date('d/m/Y') . "\n");
            $printer->text("Hab: {$row['habitacion']}\n");
            $printer->text("Cliente: " . mb_strtoupper($row['cliente']) . "\n");
            $printer->text("Cantidad: {$row['total_personas']} persona(s)\n");
            $printer->text(str_repeat("-", 42) . "\n");
            $printer->text("Presentar este ticket al abordar\n");
            $printer->text("Multa por pérdida: $2\n");

            $codigo = md5($row['id_detalle'] . $row['habitacion'] . $hoy);
            $printer->text("Código: {$codigo}\n");
            $printer->text("Impreso: " . date('d/m/Y H:i') . "\n");
            $printer->cut();
        }

        try {
            $printer->close();
        } catch (\Throwable $e) {
        }
        return true;

    } catch (\Throwable $e) {
        return "Error al imprimir tour: " . $e->getMessage();
    }
}

function imprimirTicketsGarajeHoy()
{
    try {
        include "../conexion.php";
        mysqli_set_charset($conection, 'utf8mb4');

        $hoy = date('Y-m-d');
        $cfg = mysqli_fetch_assoc(mysqli_query($conection, "SELECT razon_social, nit, direccion, telefono FROM configuracion LIMIT 1")) ?: [];
        $razon_social = $cfg['razon_social'] ?? '';
        $nit          = $cfg['nit'] ?? '';
        $direccion    = $cfg['direccion'] ?? '';
        $telefono     = $cfg['telefono'] ?? '';

        // Garaje HOY: por noche -> fecha_entrada ≤ HOY < fecha_salida
        $sql = "
            SELECT 
                h.numero AS habitacion,
                d.garaje,
                CONCAT(c.nombre, ' ', c.p_apellido) AS cliente,
                d.id AS id_detalle
            FROM reservas_detalle d
            INNER JOIN habitaciones h ON d.id_habitacion = h.idhabitacion
            INNER JOIN clientes c     ON c.usuario = d.idcliente
            WHERE d.garaje > 0
              AND d.estado_detalle = 'checkin'
              AND ? >= DATE(d.fecha_entrada)
              AND ? <  DATE(d.fecha_salida)
            ORDER BY CAST(h.numero AS UNSIGNED)
        ";
        $stmt = mysqli_prepare($conection, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $hoy, $hoy);
        mysqli_stmt_execute($stmt);
        $query = mysqli_stmt_get_result($stmt);

        if (!$query || mysqli_num_rows($query) == 0) {
            throw new Exception("No hay garajes registrados hoy.");
        }

        $printer = new Printer(new WindowsPrintConnector(PRN_HOTEL));

        while ($row = mysqli_fetch_assoc($query)) {
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text(mb_strtoupper($razon_social) . "\n");
            $printer->setEmphasis(false);
            $printer->text("RUC: $nit\nTel: $telefono\n$direccion\n");
            $printer->text(str_repeat("-", 42) . "\n");
            $printer->setEmphasis(true);
            $printer->text("TICKET DE GARAJE\n");
            $printer->setEmphasis(false);
            $printer->text("Fecha: " . date('d/m/Y') . "\n");
            $printer->text("Hab: {$row['habitacion']}\n");
            $printer->text("Cliente: " . mb_strtoupper($row['cliente']) . "\n");
            $printer->text("Vehículos: {$row['garaje']}\n");
            $printer->text(str_repeat("-", 42) . "\n");
            $printer->text("Presentar al ingreso o salida\n");
            $printer->text("Multa por pérdida: $2\n");

            $codigo = md5($row['id_detalle'] . $row['habitacion'] . $hoy);
            $printer->text("Código: {$codigo}\n");
            $printer->text("Impreso: " . date('d/m/Y H:i') . "\n");
            $printer->cut();
        }

        try {
            $printer->close();
        } catch (\Throwable $e) {
        }
        return true;

    } catch (\Throwable $e) {
        return "Error al imprimir garaje: " . $e->getMessage();
    }
}

function formatearFechaEspanol($fechaStr): string
{
    $dias  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $t     = strtotime($fechaStr);
    return ucfirst($dias[(int)date('w', $t)]) . " " . date('d', $t) . " de " . $meses[(int)date('n', $t) - 1] . " de " . date('Y', $t);
}

function enviarComprobante(?int $idreserva, ?int $id_detalle): array
{
    include "../conexion.php";

    // ─── Autoload Dompdf/PHPMailer ───────────────────────────────────────────────
    (function () {
        $candidatos = [
            __DIR__ . '/../libreries/vendor/autoload.php',
            __DIR__ . '/../vendor/autoload.php',
            __DIR__ . '/../../vendor/autoload.php',
        ];
        foreach ($candidatos as $p) {
            if (is_file($p)) {
                require_once $p;
                break;
            }
        }
    })();

    // ─── Cargar email.php con enviarCorreo() ────────────────────────────────────
    if (!function_exists('enviarCorreo')) {
        $candidatos = [
            __DIR__ . '/../includes/email.php',
            __DIR__ . '/../email.php',
            __DIR__ . '/../../includes/email.php',
            __DIR__ . '/../../email.php',
        ];
        foreach ($candidatos as $p) {
            if (is_file($p)) {
                require_once $p;
                break;
            }
        }
    }
    if (!function_exists('enviarCorreo')) {
        return ['success' => false, 'message' => 'No se encontró enviarCorreo() en email.php', 'error' => null];
    }

    mysqli_set_charset($conection, 'utf8mb4');

    // ─── Determinar tipo y SQL ─────────────────────────────────────────────────
    if ($idreserva !== null && $id_detalle === null) {
        $tipo       = 'reserva';
        $id         = intval($idreserva);
        $sql        = "
            SELECT r.idreserva,
                   r.estado,
                   CONCAT(c.nombre,' ',c.p_apellido) AS cliente,
                   c.correo_c
            FROM reservas r
            JOIN clientes c ON c.usuario = r.id_cliente
            WHERE r.idreserva = {$id}
            LIMIT 1
        ";
        $vistaPDF   = __DIR__ . '/../pdf/reservas/verReservaPDF.php';
        $getParams  = ['id' => $id];
        $permitidos = ['confirmada','checkin','checkout','finalizada'];

    } elseif ($id_detalle !== null && $idreserva === null) {
        $tipo       = 'detalle';
        $id         = intval($id_detalle);
        $sql        = "
            SELECT rd.id                 AS id_detalle,
                   rd.estado_detalle     AS estado_detalle,
                   r.idreserva,
                   r.estado              AS estado_reserva,
                   CONCAT(c.nombre,' ',c.p_apellido) AS cliente,
                   c.correo_c
            FROM reservas_detalle rd
            JOIN reservas r ON r.idreserva = rd.idreserva
            JOIN clientes c ON c.usuario   = r.id_cliente
            WHERE rd.id = {$id}
            LIMIT 1
        ";
        $vistaDetalle = __DIR__ . '/../pdf/reservas/verDetallePDF.php';
        $vistaPDF   = is_file($vistaDetalle) ? $vistaDetalle : (__DIR__ . '/../pdf/reservas/verReservaPDF.php');
        // Pasamos ambos por compatibilidad (algunas vistas usan idreserva)
        $getParams  = ['id_detalle' => $id];
        $permitidos = ['reservada','checkin','checkout','finalizada'];

    } else {
        return ['success' => false, 'message' => 'Debe indicar solo idreserva o solo id_detalle', 'error' => null];
    }

    // ─── Ejecutar consulta ─────────────────────────────────────────────────────
    $q = mysqli_query($conection, $sql);
    if (!$q || mysqli_num_rows($q) === 0) {
        return ['success' => false, 'message' => ucfirst($tipo) . ' no encontrada', 'error' => null];
    }
    $row = mysqli_fetch_assoc($q);

    // ─── Resolver estado/cliente/correo y preparar vista ───────────────────────
    if ($tipo === 'reserva') {
        $estado        = strtolower((string)$row['estado']);
        $correoCliente = trim((string)$row['correo_c']);
        $nombreCliente = trim((string)$row['cliente']);
        $idReservaPDF  = intval($row['idreserva']);
        $getParams['id'] = $idReservaPDF; // asegurar para la vista
    } else {
        $estadoDet     = strtolower((string)$row['estado_detalle']);
        $estadoRes     = strtolower((string)$row['estado_reserva']);
        $estado        = $estadoDet ?: $estadoRes;
        $correoCliente = trim((string)$row['correo_c']);
        $nombreCliente = trim((string)$row['cliente']);
        $idReservaPDF  = intval($row['idreserva']);
        $getParams['id'] = $idReservaPDF; // muchas vistas requieren idreserva
    }

    if (!filter_var($correoCliente, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => "Correo del cliente inválido: {$correoCliente}", 'error' => null];
    }
    if (!in_array($estado, $permitidos, true)) {
        return ['success' => false, 'message' => "No se puede enviar comprobante en estado: {$estado}", 'error' => null];
    }

    // ─── Título (reserva vs flujo de estadía) ──────────────────────────────────
    $esFlujoEstadia = in_array($estado, ['checkin','checkout','finalizada'], true);
    $titulo = $esFlujoEstadia ? 'Comprobante de estadía' : 'Comprobante de reserva';

    // ─── Renderizar HTML para PDF (vista existente) ────────────────────────────
    $_GET_bk = $_GET ?? [];
    $_GET    = array_merge($_GET_bk, $getParams, ['modoCorreo' => true]);

    ob_start();
    include $vistaPDF;           // IMPORTANTE: que la vista no haga exit()
    $pdf_html = ob_get_clean();

    $_GET = $_GET_bk;

    if (!$pdf_html || trim($pdf_html) === '') {
        return ['success' => false, 'message' => 'No se pudo generar el HTML del PDF', 'error' => null];
    }

    // ─── Generar PDF en memoria ────────────────────────────────────────────────
    if (!class_exists(Dompdf::class)) {
        return ['success' => false, 'message' => 'Dompdf no disponible (autoload)', 'error' => null];
    }
    $dompdf = new Dompdf();
    $opts = $dompdf->getOptions();
    $opts->set('isRemoteEnabled', true);
    $dompdf->setOptions($opts);
    $dompdf->loadHtml($pdf_html);
    $dompdf->setPaper('A5', 'portrait');
    $dompdf->render();
    $pdfBinary = $dompdf->output();

    if (!$pdfBinary) {
        return ['success' => false, 'message' => 'No se pudo generar el PDF', 'error' => null];
    }

    // ─── Guardar PDF temporal (enviarCorreo() solo acepta rutas) ───────────────
    $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR .
               (($tipo === 'reserva') ? "comprobante_reserva_{$id}.pdf" : "comprobante_detalle_{$id}.pdf");
    file_put_contents($tmpFile, $pdfBinary);

    // ─── Plantilla correo + logo CID ───────────────────────────────────────────
    $plantillaPath = __DIR__ . '/../plantillas/plantillaComprobante.html';
    if (!is_file($plantillaPath)) {
        @unlink($tmpFile);
        return ['success' => false, 'message' => "Plantilla no encontrada: {$plantillaPath}", 'error' => null];
    }
    $plantilla = file_get_contents($plantillaPath);
    $plantilla = str_replace('{{NOMBRE}}', $nombreCliente, $plantilla);
    $plantilla = str_replace('{{TITULO}}', $titulo, $plantilla);

    $logoPath = __DIR__ . '/../img/logo.jpg';
    $imagenesCID = [];
    if (is_file($logoPath)) {
        $imagenesCID[] = ['ruta' => $logoPath, 'cid' => 'logoGrupo'];
    }

    // ─── Enviar correo (SOLO enviarCorreo()) ───────────────────────────────────
    $asunto = "{$titulo} – Grupo Cañalimeña";
    $ok = enviarCorreo(
        $correoCliente,
        $nombreCliente,
        $asunto,
        $plantilla,
        [$tmpFile],
        $imagenesCID
    );

    @unlink($tmpFile);

    return [
        'success' => (bool)$ok,
        'message' => $ok ? "Comprobante enviado a {$correoCliente}" : "No se pudo enviar",
        'error'   => $ok ? null : ($GLOBALS['lastPHPMailerError'] ?? null)
    ];
}
