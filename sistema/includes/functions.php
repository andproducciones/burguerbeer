<?php

date_default_timezone_set('America/Guayaquil');

use Dompdf\Dompdf;

function fechaC()
{
    $mes = array("","Enero",
                  "Febrero",
                  "Marzo",
                  "Abril",
                  "Mayo",
                  "Junio",
                  "Julio",
                  "Agosto",
                  "Septiembre",
                  "Octubre",
                  "Noviembre",
                  "Diciembre");
    return date('d')." de ". $mes[date('n')] . " de " . date('Y');
}


function buscarCliente()
{

    include "../conexion.php";
    // Simulación de conexión a base de datos
    // $conection es el objeto de conexión a la base de datos
    $query_2 = mysqli_query($conection, "SELECT usuario, nombre, p_apellido FROM clientes WHERE estatus = 1 AND usuario != 1");
    mysqli_close($conection);
    $result = mysqli_num_rows($query_2);

    $options = ''; // Inicializamos la variable

    if ($result > 0) {
        while ($data = mysqli_fetch_assoc($query_2)) {
            $options .= '<option value="'.$data['usuario'].'">'.$data['usuario'].' | '.$data['nombre'].' '.$data['p_apellido'].'</option>';
        }
    } else {
        $options = '<option value="">No se encontraron clientes</option>';
    }

    return $options;
}


require 'C:\wamp64\www\burguerbeer\sistema\libreries\mike42\autoload.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

//use Mike42\Escpos\Printer;
//use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

function imprimirComandaMatricial($numeroMesa, $nombreMesera, $productos)
{
    try {
        // Nombre de la impresora
        $nombreImpresora = "matricial";

        // Conectar con la impresora matricial (ajusta según tu configuración)
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);

        // Configurar la impresora matricial (ajusta según tu impresora)
        $printer->initialize();

        // Establecer un tamaño de letra intermedio para el encabezado (ajusta según tu preferencia)
        //$printer->setFontSize(1, 1);

        // Poner en negrita y centrar el encabezado
        $printer->setEmphasis(true);
        $printer->setJustification(Printer::JUSTIFY_CENTER);

        // Establecer el ancho de impresión para papel de 76 mm
        $printer->setPrintWidth(512);  // Ajusta según tus necesidades y prueba

        // Imprimir encabezado
        $printer->text("BURGUER BBER\n");
        $printer->text("Mesa: $numeroMesa\n");
        $printer->text("Mesera: $nombreMesera\n");

        // Desactivar la negrita y volver a justificar a la izquierda después del encabezado
        $printer->setEmphasis(false);
        $printer->setJustification(Printer::JUSTIFY_LEFT);

        $printer->text("---------------------------------------\n");

        // Establecer un tamaño de letra normal para los productos
        //$printer->setFontSize(1, 1);

        // Imprimir productos
        for ($i = 0; $i < count($productos); $i += 2) {
            $nombreProducto = $productos[$i];
            $cantidad = $productos[$i + 1];
            $printer->text("$nombreProducto x $cantidad\n");
        }

        // Cortar el papel y cerrar la conexión
        $printer->cut();
        $printer->close();

        //echo "Impresión exitosa.";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
function imprimirFacturaMatricial($factura, $tl_sniva, $total, $productos)
{
    try {

        include "../../conexion.php";

        $query_config   = mysqli_query($conection, "SELECT * FROM configuracion");
        $result_config  = mysqli_num_rows($query_config);

        if ($result_config > 0) {
            $configuracion = mysqli_fetch_assoc($query_config);

            $razon_social   = $configuracion['razon_social'];
            $nombre         = $configuracion['nombre'];
            $nit            = $configuracion['nit'];
            $direccion      = $configuracion['direccion'];
            $telefono       = $configuracion['telefono'];
        }

        $nombre2         = $factura['nombre'];
        $p_apellido      = $factura['p_apellido'];
        $direccion2      = $factura['direccion'];
        $telefono2       = $factura['telefono'];


        // Nombre de la impresora
        $nombreImpresora = "matricial";

        // Conectar con la impresora matricial (ajusta según tu configuración)
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);

        // Configurar la impresora matricial (ajusta según tu impresora)
        $printer->initialize();

        // Establecer un tamaño de letra intermedio para el encabezado (ajusta según tu preferencia)
        //$printer->setFontSize(1, 1);

        // Poner en negrita y centrar el encabezado
        $printer->setEmphasis(true);
        $printer->setJustification(Printer::JUSTIFY_CENTER);

        // Establecer el ancho de impresión para papel de 76 mm
        $printer->setPrintWidth(512);  // Ajusta según tus necesidades y prueba

        // Imprimir encabezado
        $printer->text("$razon_social\n");
        $printer->text("$nombre\n");
        $printer->text("$nit\n");
        $printer->text("$telefono\n");
        $printer->text("$direccion\n");
        $printer->text("---------------------------------------\n");
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Cliente: $nombre2 $p_apellido\n");
        $printer->text("RUC: \n");
        $printer->text("Direccion: $direccion2\n");
        $printer->text("Telefono: $telefono2\n");
        $printer->text("---------------------------------------\n");

        // Desactivar la negrita y volver a justificar a la izquierda después del encabezado
        $printer->setEmphasis(false);
        $printer->setJustification(Printer::JUSTIFY_LEFT);

        $printer->text("---------------------------------------\n");

        // Establecer un tamaño de letra normal para los productos
        //$printer->setFontSize(1, 1);

        // Imprimir productos
        for ($i = 0; $i < count($productos); $i += 2) {
            $nombreProducto = $productos[$i];
            $cantidad = $productos[$i + 1];
            $printer->text("$nombreProducto x $cantidad\n");
        }
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("SUBTOTAL: $tl_sniva\n");
        $printer->text("IVA: $total\n");

        // Cortar el papel y cerrar la conexión
        $printer->cut();
        $printer->close();
        return true;

        //echo "Impresión exitosa.";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
function imprimirFactura($factura, $nombreCliente, $tl_sniva, $total, $productos)
{
    try {
        include "../../conexion.php";

        $query_config   = mysqli_query($conection, "SELECT * FROM configuracion");
        $result_config  = mysqli_num_rows($query_config);

        if ($result_config > 0) {
            $configuracion = mysqli_fetch_assoc($query_config);
            $razon_social   = $configuracion['razon_social'];
            $nombre         = $configuracion['nombre'];
            $nit            = $configuracion['nit'];
            $direccion      = $configuracion['direccion'];
            $telefono       = $configuracion['telefono'];
        }

        $nombre2         = $factura['nombre'];
        $p_apellido      = $factura['p_apellido'];
        $direccion2      = $factura['direccion'];
        $telefono2       = $factura['telefono'];

        // Nombre de la impresora
        $nombreImpresora = "comandas";

        // Conectar con la impresora
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);


        $printer->setJustification(Printer::JUSTIFY_CENTER);

        // Establecer el ancho de impresión para papel de 76 mm
        $printer->setPrintWidth(576);  // Ajusta según tus necesidades y prueba

        // Imprimir encabezado
        $printer->setEmphasis(true);
        $printer->text("$razon_social\n");
        $printer->setEmphasis(false);
        $printer->text("RUC: $nit\n");
        $printer->text("Telefono: $telefono\n");
        $printer->text("$direccion\n");

        $printer->text("-----------------------------------------------\n\n");
        $printer->text("$nombreCliente\n");

        $printer->setJustification(Printer::JUSTIFY_LEFT);


        $printer->text("-----------------------------------------------\n");

        // Establecer un tamaño de letra normal para los productos
        //$printer->setFontSize(1, 1);

        $printer->setEmphasis(true);

        $nombreProducto2 = str_pad('Descripcion', 28);
        $cantidad2       = str_pad('Cant', 5);
        $precio2         = str_pad('Precio', 6);
        $preciototal2    = str_pad('Total', 6);

        $printer->text("$cantidad2 $nombreProducto2 $precio2 $preciototal2\n");
        $printer->setEmphasis(false);

        // Imprimir productos
        for ($i = 0; $i < count($productos); $i += 4) {
            $nombreProducto = $productos[$i];
            $cantidad       = $productos[$i + 1];
            $precio         = $productos[$i + 2];
            $preciototal    = $productos[$i + 3];

            $nombreProducto = str_pad($nombreProducto, 28);
            $cantidad       = str_pad($cantidad, 5);
            $precio         = str_pad($precio, 6);
            $preciototal    = str_pad($preciototal, 6);

            $printer->text("$cantidad $nombreProducto $precio $preciototal\n");
        }
        $nombreProducto3 = str_pad('', 24);
        $cantidad3       = str_pad('', 5);
        $precio3         = str_pad('Subtotal', 9);
        $preciototal3    = str_pad($tl_sniva, 5);

        $printer->text("$cantidad3 $nombreProducto3 $precio3 $preciototal3\n");

        $nombreProducto5 = str_pad('', 24);
        $cantidad5       = str_pad('', 5);
        $precio5         = str_pad('IVA 0%', 9);
        $preciototal5    = str_pad('00.00', 5);

        $printer->text("$cantidad5 $nombreProducto5 $precio5 $preciototal5\n");

        $nombreProducto4 = str_pad('', 24);
        $cantidad4       = str_pad('', 5);
        $precio4         = str_pad('Total', 9);
        $preciototal4    = str_pad($total, 5);

        $printer->setEmphasis(true);
        $printer->text("$cantidad4 $nombreProducto4 $precio4 $preciototal4\n");
        $printer->setEmphasis(false);

        $printer->text("-----------------------------------------------\n");


        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("!Gracias por su compra¡\n");

        // Cortar el papel y cerrar la conexión
        $printer->cut();
        $printer->close();
        return true;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}


function imprimirPrecuenta($mesa, $nombreCliente, $tl_sniva, $total, $productos)
{

    try {
        include "../../conexion.php";

        $query_config   = mysqli_query($conection, "SELECT * FROM configuracion");
        $result_config  = mysqli_num_rows($query_config);

        if ($result_config > 0) {
            $configuracion = mysqli_fetch_assoc($query_config);
            $razon_social   = $configuracion['razon_social'];
            $nombre         = $configuracion['nombre'];
            $nit            = $configuracion['nit'];
            $direccion      = $configuracion['direccion'];
            $telefono       = $configuracion['telefono'];
        }

        $fecha = date('Y-m-d G:i:s');
        $nombreMesero = $_SESSION['nombre'].' '.$_SESSION['apellido'];


        // Nombre de la impresora
        $nombreImpresora = "comandas";

        // Conectar con la impresora
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);


        $printer->setJustification(Printer::JUSTIFY_CENTER);

        // Establecer el ancho de impresión para papel de 76 mm
        $printer->setPrintWidth(576);  // Ajusta según tus necesidades y prueba

        $printer->setEmphasis(true);
        $printer->text("BURGUER BEER\n\n");
        $printer->text("PRE-CUENTA\n");
        $printer->setEmphasis(false);
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->setEmphasis(true);
        $printer->text("A nombre de: $nombreCliente\n");
        $printer->setEmphasis(false);
        $printer->text("Mesa: $mesa\n");
        $printer->text("Mesero: $nombreMesero\n");
        $printer->text("Fecha: $fecha\n");
        $printer->text("-----------------------------------------------\n");

        $nombreProducto2 = str_pad('Descripcion', 28);
        $cantidad2       = str_pad('Cant', 5);
        $precio2         = str_pad('Precio', 6);
        $preciototal2    = str_pad('Total', 6);

        $printer->setEmphasis(true);
        $printer->text("$cantidad2 $nombreProducto2 $precio2 $preciototal2\n");
        $printer->setEmphasis(false);

        // Imprimir productos
        for ($i = 0; $i < count($productos); $i += 4) {
            $nombreProducto = $productos[$i];
            $cantidad       = $productos[$i + 1];
            $precio         = $productos[$i + 2];
            $preciototal    = $productos[$i + 3];

            $nombreProducto = str_pad($nombreProducto, 28);
            $cantidad       = str_pad($cantidad, 5);
            $precio         = str_pad($precio, 6);
            $preciototal    = str_pad($preciototal, 6);

            $printer->text("$cantidad $nombreProducto $precio $preciototal\n");
        }
        $nombreProducto3 = str_pad('', 24);
        $cantidad3       = str_pad('', 5);
        $precio3         = str_pad('Subtotal', 9);
        $preciototal3    = str_pad($tl_sniva, 5);

        $printer->text("$cantidad3 $nombreProducto3 $precio3 $preciototal3\n");

        $nombreProducto5 = str_pad('', 24);
        $cantidad5       = str_pad('', 5);
        $precio5         = str_pad('IVA 0%', 9);
        $preciototal5    = str_pad('00.00', 5);

        $printer->text("$cantidad5 $nombreProducto5 $precio5 $preciototal5\n");

        $nombreProducto4 = str_pad('', 24);
        $cantidad4       = str_pad('', 5);
        $precio4         = str_pad('Total', 9);
        $preciototal4    = str_pad($total, 5);

        $printer->setEmphasis(true);
        $printer->text("$cantidad4 $nombreProducto4 $precio4 $preciototal4\n");
        $printer->setEmphasis(false);

        $printer->text("-----------------------------------------------\n");

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text("¿DESEA NOTA DE VENTA?\n");
        $printer->text("DEJE SUS DATOS EN CAJA\n");
        $printer->setEmphasis(false);

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->setEmphasis(true);
        $printer->text("Propina\n");
        $printer->setEmphasis(false);
        $printer->text("_______________________________________________\n");
        $printer->text("Nombre\n");
        $printer->text("_______________________________________________\n");
        $printer->text("RUC\n");
        $printer->text("_______________________________________________\n");
        $printer->text("Dirección\n");
        $printer->text("_______________________________________________\n");
        $printer->text("Teléfono\n");
        $printer->text("_______________________________________________\n");
        $printer->text("Correo\n");
        $printer->text("_______________________________________________\n");


        // Cortar el papel y cerrar la conexión
        $printer->cut();
        $printer->close();

        return true;
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
function imprimirComanda($numeroMesa, $nombreCliente, $nombreMesera, $productos, $fecha)
{
    try {
        // Nombre de la impresora
        $nombreImpresora = "comandas";

        // Conectar con la impresora
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);
        $printer->setPrintWidth(576);
        $printer->setTextSize(1, 1);

        // Imprimir encabezado
        $printer->setEmphasis(true);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("BURGER BEER\n\n");
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("A nombre de: $nombreCliente\n");
        $printer->setEmphasis(false);
        $printer->text("Mesa: $numeroMesa\n");
        $printer->text("Fecha: $fecha\n");
        $printer->text("Mesero: $nombreMesera\n");
        $printer->text("------------------------------------------------\n");

        // Imprimir productos
        for ($i = 0; $i < count($productos); $i += 3) {
            $nombreProducto     = $productos[$i];
            $cantidad           = $productos[$i + 1];
            $observaciones      = $productos[$i + 2];
            $printer->text("$cantidad $nombreProducto\n");
            if (!empty($observaciones)) {
                $printer->text("   $observaciones\n");
            }
        }

        // Cortar el papel y cerrar la conexión
        $printer->cut();
        $printer->close();

        return true;

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
function imprimirCierreCaja($data)
{
    include "../conexion.php";
    $nombreImpresora = "comandas";

    // Extraer datos
    $fecha_inicio       = $data['fecha_inicio'];
    $fecha_fin          = $data['fecha_fin'];
    $id_cierre          = $data['idArqueo'];
    $user               = $data['idUser'];
    $nombre             = $data['nombre'];
    $apellido           = $data['apellido'];
    $monto_inicial      = $data['monto_inicial'];
    $monto_final        = $data['monto_final'];
    $total_ventas       = $data['total_ventas'];
    $total_cash         = $data['total_cash'];
    $efectivo           = $data['efectivo'];
    $transferencia      = $data['transferencia'];
    $tarjeta            = $data['tarjeta'];
    $deuna              = $data['deuna'];
    $total_salidas      = $data['total_movimientos'];
    $salidas            = $data['salidas'];
    $salarios           = $data['salarios'];

    // Valores calculados del sistema
    $totalEfectivo      = $data['total_efectivo'];
    $totalTarjeta       = $data['total_tarjeta'];
    $totalTransferencia = $data['total_transferencia'];
    $totalDeUna         = $data['total_deuna'];

    $total_venta = $monto_inicial + $total_cash;
    $observaciones = $data['observaciones'] ?? '';
    $compras = $data['compras'] ?? '';

    $monto_final_final = $totalEfectivo - $salarios;

    try {
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);
    } catch (Exception $e) {
        return false;
    }

    try {
        $printer->setPrintWidth(576);
        $printer->setTextSize(1, 1);

        $printer->setEmphasis(true);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("CIERRE DE CAJA # $id_cierre\n");
        $printer->setEmphasis(false);
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Fecha Inicio: $fecha_inicio\n");
        $printer->text("Fecha Final:  $fecha_fin\n");
        $printer->text("Cajero:       $nombre $apellido\n");
        $printer->text("------------------------------------------------\n");
        $printer->text("Monto Inicial:          $ " . number_format($monto_inicial, 2) . "\n");
        $printer->text("------------------------------------------------\n");


        $printer->setEmphasis(true);
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("VENTAS DEL DIA\n");
        $printer->setEmphasis(false);
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Cantidad de Ventas:     $total_ventas\n");
        $printer->text("TOTAL EN VENTAS:       $ " . number_format($total_cash, 2) . "\n");
        $printer->text("------------------------------------------------\n");

        $printer->setEmphasis(true);
        $printer->text("MOVIMIENTOS DE CAJA\n");
        $printer->setEmphasis(false);
        foreach ($salidas as $salida) {
            $nombre_usuario = $salida['nombre_usuario'];
            $motivo = $salida['motivo'];
            $valor = $salida['valor'];
            $tipo_moneda = $salida['tipo_moneda'] == 1 ? 'EF' : 'TR';

            // Si tipo_transaccion es 2, se resta
            if (isset($salida['tipo_transaccion']) && $salida['tipo_transaccion'] == 1) {
                $valor = -abs($valor);
            }

            $printer->text("$nombre_usuario ($tipo_moneda): $motivo - $ " . number_format($valor, 2) . "\n");
        }
        //$printer->setEmphasis(true);
        //$printer->text("Total Salidas:          $ " . number_format($total_salidas, 2) . "\n");
        //$printer->setEmphasis(false);
        $printer->text("------------------------------------------------\n");

        $printer->setEmphasis(true);
        $printer->text("MONTOS A ENTREGAR\n");
        $printer->setEmphasis(false);
        $printer->text("Efectivo:        $ " . number_format($totalEfectivo, 2) . "\n");
        $printer->text("Tarjeta:         $ " . number_format($totalTarjeta, 2) . "\n");
        $printer->text("Transferencia:   $ " . number_format($totalTransferencia, 2) . "\n");
        $printer->text("DeUna:           $ " . number_format($totalDeUna, 2) . "\n");
        $printer->text("------------------------------------------------\n");



        $printer->setEmphasis(true);
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("MONTOS ENTREGADOS\n");
        $printer->setEmphasis(false);
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Efectivo:               $ " . number_format($efectivo, 2) . "\n");
        $printer->text("Tarjeta:                $ " . number_format($tarjeta, 2) . "\n");
        $printer->text("Transferencia:          $ " . number_format($transferencia, 2) . "\n");
        $printer->text("DeUna:                  $ " . number_format($deuna, 2) . "\n");
        $printer->setEmphasis(true);
        $printer->text("ENTREGA TOTAL:          $ " . number_format($monto_final, 2) . "\n");
        $printer->setEmphasis(false);
        $printer->text("------------------------------------------------\n");

        $q_auditoria = mysqli_query(
            $conection,
            "SELECT tipo_pago, estado, diferencia 
            FROM auditoria_cierre_caja 
            WHERE id_cierre = $id_cierre"
        );

        $novedad_encontrada = false;
        $auditoria_detalle = [];

        if (mysqli_num_rows($q_auditoria) > 0) {
            while ($row = mysqli_fetch_assoc($q_auditoria)) {
                $auditoria_detalle[] = $row;

                // Detecta si hay alguna novedad (estado distinto de "OK")
                if (strtoupper($row['estado']) !== 'OK') {
                    $novedad_encontrada = true;
                }
            }
        }

        if ($novedad_encontrada) {
            $printer->setEmphasis(true);
            $printer->text("RESULTADO DE AUDITORÍA\n");
            $printer->setEmphasis(false);

            foreach ($auditoria_detalle as $row) {
                $printer->text("{$row['tipo_pago']}: {$row['estado']} - $ " . number_format($row['diferencia'], 2) . "\n");
            }
            $printer->text("------------------------------------------------\n");
        }

        if (!empty($data['pagos_codigos'])) {
            $printer->setEmphasis(true);
            $printer->text("DETALLE CÓDIGOS DE PAGO\n");
            $printer->setEmphasis(false);

            foreach ($data['pagos_codigos'] as $tipo => $codigos) {
                $printer->text(strtoupper($tipo) . "\n");
                foreach ($codigos as $c) {
                    $printer->text("  Cod: " . $c['codigo'] ."   $ " . $c['total'] . "\n");
                }
            }
            $printer->text("------------------------------------------------\n");
        }



        if (!empty($observaciones)) {
            $printer->setEmphasis(true);
            $printer->text("OBSERVACIONES\n");
            $printer->setEmphasis(false);
            $printer->text("$observaciones\n");
            $printer->text("------------------------------------------------\n");
        }
        if (!empty($compras)) {
            $printer->setEmphasis(true);
            $printer->text("COMPRAS\n");
            $printer->setEmphasis(false);
            $printer->text("$compras\n");
            $printer->text("------------------------------------------------\n");
        }

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("CIERRE FINAL EN EFECTIVO $ $monto_final_final\n");
        $printer->cut();

        try {
            $printer->close();
        } catch (Exception $e) {
            // Silenciar error de cierre
        }

        return true;

    } catch (Exception $e) {
        try {
            $printer->close();
        } catch (Exception $e2) {
            // Silenciar cualquier otro error
        }
        return false;
    }
}
function mostrarTicketCierre($data)
{
    // Cálculos previos
    $monto_inicial      = $data['monto_inicial'];
    $monto_final        = $data['monto_final'];
    $total_ventas       = $data['total_ventas'];
    $total_cash         = $data['total_cash'];
    $total_venta        = $monto_inicial + $total_cash;

    $totalEfectivo      = $data['total_efectivo'];
    $totalTarjeta       = $data['total_tarjeta'];
    $totalTransferencia = $data['total_transferencia'];
    $totalDeUna         = $data['total_deuna'];

    $efectivo           = $data['efectivo'];
    $transferencia      = $data['transferencia'];
    $tarjeta            = $data['tarjeta'];
    $deuna              = $data['deuna'];

    $total_salidas      = $data['total_movimientos'];
    $salidas            = $data['salidas'];

    // HTML del ticket
    echo "<pre style='font-family:monospace; font-size:13px'>";
    echo "CIERRE DE CAJA\n";
    echo "Fecha Inicio: {$data['fecha_inicio']}\n";
    echo "Fecha Fin:    {$data['fecha_fin']}\n";
    echo "Código:       {$data['idArqueo']}\n";
    echo "Cajero:       {$data['nombre']} {$data['apellido']}\n";
    echo str_repeat("-", 48) . "\n";

    echo "Monto Inicial:         $" . number_format($monto_inicial, 2) . "\n";
    echo "Cantidad de Ventas:    $total_ventas\n";
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
            $tipo = $s['tipo_moneda'] == 1 ? 'EF' : 'TR';
            echo "{$s['nombre_usuario']} ($tipo): {$s['motivo']} - $" . number_format($s['valor'], 2) . "\n";
        }
        echo "Total Salidas:         $" . number_format($total_salidas, 2) . "\n";
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
function imprimirSalidaDinero($data)
{
    try {
        // Nombre de la impresora
        $nombreImpresora = "comandas";

        $fecha              = $data['fecha'];
        $id                 = $data['id'];
        $nombre2            = $_SESSION['nombre'];
        $apellido2          = $_SESSION['apellido'];
        $nombre             = $data['nombre'];
        $monto              = $data['monto'];
        $motivo             = $data['motivo'];
        $moneda             = $data['moneda'];
        $tipo               = $data['tipo'];

        if ($tipo == 2) {
            $tipoN = "ENTRADA";
        } else {
            $tipoN = "SALIDA";
        }

        // Conectar con la impresora
        $connector = new WindowsPrintConnector($nombreImpresora);
        $printer = new Printer($connector);
        $printer->setPrintWidth(576);
        $printer->setTextSize(1, 1);

        // Imprimir encabezado
        $printer->setEmphasis(true);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("$tipoN DE DINERO\n");
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->setEmphasis(false);
        $printer->text("Fecha: $fecha\n");
        $printer->text("ID: $id\n");
        $printer->text("Nombre: $nombre\n");
        $printer->text("------------------------------------------------\n");
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
        $printer->text("------------------------------------------------\n\n\n\n\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("-----------------------\n");
        $printer->text("$nombre\n\n");



        // Cortar el papel y cerrar la conexión
        $printer->cut();
        $printer->close();

        return true;

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
function compararMontosEntregadosVsCalculados($calculado, $entregado)
{
    $diferencias = [];
    $faltanteGeneral = 0;
    $sobranteGeneral = 0;

    foreach ($calculado as $tipo => $valorCalculado) {
        $valorEntregado = isset($entregado[$tipo]) ? floatval($entregado[$tipo]) : 0;
        $diferencia = round($valorEntregado - $valorCalculado, 2);

        if ($diferencia < 0) {
            $diferencias[$tipo] = ['estado' => 'FALTANTE', 'diferencia' => abs($diferencia)];
            $faltanteGeneral += abs($diferencia);
        } elseif ($diferencia > 0) {
            $diferencias[$tipo] = ['estado' => 'SOBRANTE', 'diferencia' => $diferencia];
            $sobranteGeneral += $diferencia;
        } else {
            $diferencias[$tipo] = ['estado' => 'OK', 'diferencia' => 0];
        }
    }

    $diferencias['TOTAL'] = [
        'faltante' => $faltanteGeneral,
        'sobrante' => $sobranteGeneral,
        'estado' => ($faltanteGeneral == 0 && $sobranteGeneral == 0) ? 'CUADRA' : 'DESCUADRE'
    ];

    return $diferencias;
}



function imprimirDesayunosHoy()
{
    try {
        //include "../../conexion.php";
        include "../conexion.php";

        $nombreImpresora = "comandas";

        // Datos del hotel
        $query_config = mysqli_query($conection, "SELECT * FROM configuracion LIMIT 1");
        $config       = mysqli_fetch_assoc($query_config);

        $razon_social = $config['razon_social'] ?? '';
        $nit          = $config['nit'] ?? '';
        $direccion    = $config['direccion'] ?? '';
        $telefono     = $config['telefono'] ?? '';

        // Consulta de desayunos para hoy
        $query = mysqli_query($conection, "
            SELECT h.numero AS habitacion, (rd.adultos + rd.ninos) AS total_desayunos
            FROM reservas_detalle rd
            INNER JOIN reservas r ON rd.idreserva = r.idreserva
            INNER JOIN habitaciones h ON rd.id_habitacion = h.idhabitacion
            WHERE 
                rd.incluye_desayuno = 1
                AND r.estado = 'checkin'
                AND CURDATE() BETWEEN r.fecha_entrada AND DATE_SUB(r.fecha_salida, INTERVAL 1 DAY)
            ORDER BY h.numero
        ");

        if (!$query || mysqli_num_rows($query) == 0) {
            throw new Exception("No hay desayunos programados hoy.");
        }

        // Conexión con la impresora
        try {
            $connector = new WindowsPrintConnector($nombreImpresora);
        } catch (Exception $e) {
            die("No se pudo conectar a la impresora: " . $e->getMessage());
            exit;
        }

        $printer = new Printer($connector);

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setEmphasis(true);
        $printer->text(strtoupper($razon_social) . "\n");
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
            $hab = str_pad("Hab. " . $row['habitacion'], 15);
            $cant = str_pad("🟢 {$row['total_desayunos']} desayuno(s)", 25);
            $printer->text("$hab $cant\n");
        }

        $printer->text(str_repeat("-", 42) . "\n");
        $printer->text("Preparación por cocina\n");
        $printer->feed(1);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Impreso: " . date('d/m/Y H:i') . "\n");

        $printer->cut();
        $printer->close();

        return true;
    } catch (Exception $e) {
        return "Error al imprimir: " . $e->getMessage();
    }


}

function verificarSesionPOS()
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

    $ip_actual = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua_actual = $_SERVER['HTTP_USER_AGENT'] ?? '';

    if (
        isset($_SESSION['ip'], $_SESSION['ua']) &&
        ($_SESSION['ip'] !== $ip_actual || $_SESSION['ua'] !== $ua_actual)
    ) {
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
            $limpio[$key] = sanearPost($valor); // Recursivo si hay arrays
        } else {
            // 1. Eliminar etiquetas HTML y JS
            $valor = strip_tags($valor);

            // 2. Eliminar caracteres comunes en ataques (SQL, XSS, etc.)
            $valor = preg_replace('/[<>{}"\'()%;$&#*!=\\\\[\]{}]/', '', $valor);

            // 3. Quitar espacios extremos y codificar caracteres especiales
            $valor = trim($valor);
            $valor = htmlspecialchars($valor, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $limpio[$key] = $valor;
        }
    }

    return $limpio;
}

function enviarComprobanteReserva($idreserva)
{
    include "../conexion.php";
    require_once __DIR__ . '/email.php';
    require_once __DIR__ . '/../pdf/vendor/autoload.php';


    $id = intval($idreserva);
    if ($id <= 0) {
        return "ID de reserva inválido";
    }

    // CONSULTA
    $query = mysqli_query($conection, "
        SELECT r.*, CONCAT(c.nombre, ' ', c.p_apellido) AS cliente, c.usuario, c.telefono, c.correo_c
        FROM reservas r
        INNER JOIN clientes c ON r.id_cliente = c.usuario
        WHERE r.idreserva = $id
    ");
    if (!$query || mysqli_num_rows($query) == 0) {
        return "Reserva no encontrada";
    }
    $reserva = mysqli_fetch_assoc($query);

    // VALIDAR ESTADO
    $estadoValido = in_array(strtolower($reserva['estado']), ['confirmada', 'checkin', 'checkout']);
    if (!$estadoValido) {
        return true;
    }

    // GENERAR PDF
    $_GET['modoCorreo'] = true;
    $_GET['id'] = $id; // ← Esto es lo que necesitas

    ob_start();
    include __DIR__ . '/../pdf/reservas/verReservaPDF.php';
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

    // ENVIAR CORREO
    $nombreCliente = $reserva['cliente'];
    $correoCliente = $reserva['correo_c'];
    $titulo = in_array(strtolower($reserva['estado']), ['checkin', 'checkout', 'finalizada']) ? 'Comprobante de estadía' : 'Comprobante de reserva';

    $logoCID = 'logoGrupo';
    $plantillaHTML = file_get_contents(__DIR__ . '/plantillas/plantilla_comprobante.php');
    $plantillaHTML = str_replace('{{NOMBRE}}', $nombreCliente, $plantillaHTML);
    $plantillaHTML = str_replace('{{TITULO}}', $titulo, $plantillaHTML);

    $enviado = enviarCorreo(
        $correoCliente,
        $nombreCliente,
        "$titulo – Grupo Cañalimeña",
        $plantillaHTML,
        [$archivo_temp],
        [['ruta' => __DIR__ . '/../../img/logo.jpg', 'cid' => $logoCID]]
    );

    unlink($archivo_temp);

    if ($enviado) {
        return true;
    } else {
        $error = $GLOBALS['lastPHPMailerError'] ?? 'Sin detalle técnico';
        return "Error al enviar correo a $correoCliente – Detalle: $error";
    }
}


function imprimirComprobanteEstadia($idreserva)
{
    include "../../conexion.php";
    $query = mysqli_query($conection, "
        SELECT r.*, CONCAT(c.nombre, ' ', c.p_apellido) AS cliente,
               c.direccion, c.telefono, c.usuario AS usuario_cliente
        FROM reservas r
        INNER JOIN clientes c ON r.id_cliente = c.usuario
        WHERE r.idreserva = $idreserva
        LIMIT 1
    ");
    if (!$query || mysqli_num_rows($query) == 0) {
        return;
    }

    $reserva = mysqli_fetch_assoc($query);
    $cliente = $reserva['cliente'];
    $entrada = date('l d \d\e F \d\e Y', strtotime($reserva['fecha_entrada']));
    $salida  = date('l d \d\e F \d\e Y', strtotime($reserva['fecha_salida']));
    $total   = number_format($reserva['total'], 2);
    $usuario_cliente = $reserva['usuario_cliente'];

    $numeroContrato = "01-" . date('Y') . "-" . str_pad($idreserva, 4, '0', STR_PAD_LEFT);
    $hash = strtoupper(substr(sha1("ESTADIA$idreserva" . $reserva['fecha_entrada']), 0, 10));

    $detalle = mysqli_query($conection, "
        SELECT h.numero, d.adultos, d.ninos, d.incluye_desayuno, d.incluye_tour, lt.nombre AS lugar_tour
        FROM reservas_detalle d
        INNER JOIN habitaciones h ON h.idhabitacion = d.id_habitacion
        LEFT JOIN lugares_tour lt ON lt.id = d.lugar_tour
        WHERE d.idreserva = $idreserva
    ");

    $habitaciones = [];
    $adultos = $ninos = 0;
    $servicios = [];

    while ($row = mysqli_fetch_assoc($detalle)) {
        $habitaciones[] = $row['numero'];
        $adultos += $row['adultos'];
        $ninos   += $row['ninos'];
        if ($row['incluye_desayuno']) {
            $servicios[] = "🥐 Desayuno";
        }
        if ($row['incluye_tour']) {
            $servicios[] = "🗺️ Tour: " . ($row['lugar_tour'] ?? 'Destino');
        }
    }

    $printer = new Printer(new WindowsPrintConnector("comandas"));
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->setEmphasis(true);
    $printer->text("GRUPO CAÑALIMEÑA\n");
    $printer->setEmphasis(false);
    $printer->text("1801096106001\n");
    $printer->text("Espejo y 16 de Diciembre\n");
    $printer->text("hostalcanalimena.wixsite.com/hostalpage\n");
    $printer->text("0985385025\n");
    $printer->text("----------------------------------------\n");
    $printer->setEmphasis(true);
    $printer->text("COMPROBANTE DE ESTADÍA\n");
    $printer->setEmphasis(false);
    $printer->text("Contrato N°: $numeroContrato\n");
    $printer->text("Verificación: #$hash\n");
    $printer->text("----------------------------------------\n");

    $printer->setJustification(Printer::JUSTIFY_LEFT);
    $printer->text("Cliente: $cliente ($usuario_cliente)\n");
    $printer->text("Entrada: $entrada\n");
    $printer->text("Salida:  $salida\n");
    $printer->text("Hab(s): " . implode(", ", $habitaciones) . "\n");
    $printer->text("Adultos: $adultos  Niños: $ninos\n");
    if (!empty($servicios)) {
        $printer->text("Servicios:\n");
        foreach ($servicios as $s) {
            $printer->text(" - $s\n");
        }
    }

    $printer->text("----------------------------------------\n");
    $printer->setEmphasis(true);
    $printer->text("Total pagado: $ $total\n");
    $printer->setEmphasis(false);
    $printer->text("----------------------------------------\n\n\n\n");

    // Firma
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("________________________________________\n");
    $printer->text("$cliente ($usuario_cliente)\n");

    $printer->text("----------------------------------------\n");
    $printer->text("Al firmar, el cliente declara que:\n");
    $printer->text("- Ha leído y acepta todos los términos\n");
    $printer->text("  y condiciones del servicio.\n");
    $printer->text("- Se compromete a pagar el valor total\n");
    $printer->text("  de la estadía y servicios contratados.\n");
    $printer->text("----------------------------------------\n");
    $printer->text("Yolanda Silva – Gerente General\n");
    $printer->text("Grupo Cañalimeña\n");
    $printer->text("----------------------------------------\n");
    $printer->cut();
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("TÉRMINOS Y CONDICIONES\n");
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    // --- HORARIOS Y CONDICIONES DE INGRESO ---
    $printer->text("- Check-in disponible desde las 12:00 PM.\n");
    $printer->text("- Check-out debe realizarse hasta las\n");
    $printer->text("  12:00 PM del día de salida.\n");
    $printer->text("- Early o late check-in están sujetos a\n");
    $printer->text("  disponibilidad del establecimiento.\n");
    $printer->text("- Late check-out genera un recargo\n");
    $printer->text("  de $10 por habitación (máx. 6 horas).\n");

    // --- USO DE HABITACIÓN Y CONDUCTA ---
    $printer->text("- No se permite reingresar después del\n");
    $printer->text("  check-out o entrega de llaves.\n");
    $printer->text("- Está prohibido fumar dentro de las\n");
    $printer->text("  habitaciones o realizar fiestas.\n");
    $printer->text("- Actividades sociales solo están\n");
    $printer->text("  permitidas en zonas comunes designadas.\n");
    $printer->text("- El hotel se reserva el derecho de\n");
    $printer->text("  admisión y permanencia en caso de\n");
    $printer->text("  incumplimiento o eventualidad.\n");
    $printer->text("- Se exige trato respetuoso hacia\n");
    $printer->text("  el personal y otros huéspedes.\n");
    $printer->text("- Comportamientos agresivos serán\n");
    $printer->text("  causa de desalojo inmediato.\n");


    // --- RESPONSABILIDAD POR DAÑOS ---
    $printer->text("- El huésped será responsable por todo\n");
    $printer->text("  daño causado dentro de la habitación o\n");
    $printer->text("  áreas comunes del hotel.\n");
    $printer->text("- Se cobrará el 100% del valor del bien\n");
    $printer->text("  dañado más $10 diarios si queda\n");
    $printer->text("  inhabilitado.\n");

    // --- VISITAS EXTERNAS ---
    $printer->text("- Solo se permiten visitas con permiso\n");
    $printer->text("  previo y registro en recepción.\n");
    $printer->text("- De detectarse visita no autorizada,\n");
    $printer->text("  se cobrará como persona adicional.\n");


    // --- SERVICIOS ADICIONALES ---
    $printer->text("- El desayuno continental ($2.99) está\n");
    $printer->text("  disponible únicamente si fue incluido\n");
    $printer->text("  en la reserva o contratado al ingresar.\n");
    $printer->text("- Los tours son organizados por terceros\n");
    $printer->text("  aliados. Grupo Cañalimeña solo garantiza\n");
    $printer->text("  hora y destino, no su ejecución.\n");

    // --- PARQUEADERO ---
    $printer->text("- El parqueadero es un servicio externo\n");
    $printer->text("  no operado por el hotel.\n");
    $printer->text("- El cliente es responsable de su uso y\n");
    $printer->text("  Grupo Cañalimeña no asume responsabilidad\n");
    $printer->text("  por robos, accidentes o daños.\n");

    // --- FACTURACIÓN ---
    $printer->text("- Cañalimeña solo emite nota de venta\n");
    $printer->text("  física sin IVA.\n");
    $printer->text("- Si el huésped desea comprobante fiscal,\n");
    $printer->text("  debe solicitarlo de forma expresa y\n");
    $printer->text("  anticipada durante su estadía.\n");
    $printer->text("- El hotel no emite facturas posteriores\n");
    $printer->text("  al check-out por omisión del huésped.\n");
    $printer->text("- Solo se emitirán notas de venta por montos ya\n");
    $printer->text("  efectivamente abonados o pagados.\n");

    // --- OBJETOS PERDIDOS ---
    $printer->text("- Objetos olvidados se guardan máx. 7\n");
    $printer->text("  días. No garantizamos recuperación ni\n");
    $printer->text("  envío. Cliente asume responsabilidad.\n");

    $printer->text("- Grupo Cañalimeña no se hace\n");
    $printer->text("  responsable por fallos de terceros\n");
    $printer->text("  (tours, taxis, parqueo, etc.) ni por\n");
    $printer->text("  eventos fortuitos como apagones,\n");
    $printer->text("  lluvias o cortes de servicio público.\n");

    // --- POLÍTICA PARA MASCOTAS ---
    $printer->text("- Se permiten mascotas bajo solicitud\n");
    $printer->text("  previa, sujetas a disponibilidad.\n");
    $printer->text("- Se aplicará un recargo diario por\n");
    $printer->text("  limpieza de acuerdo al tamaño:\n");
    $printer->text("   * Pequeña: $5\n");
    $printer->text("   * Mediana: $7\n");
    $printer->text("   * Grande: $10\n");
    $printer->text("- El cargo es por mascota, por día.\n");
    $printer->text("- Toda mancha, daño o deterioro a\n");
    $printer->text("  mobiliario será cobrado según la\n");
    $printer->text("  cláusula de responsabilidad por daños.\n");



    // --- PROTECCIÓN DE DATOS ---
    $printer->text("- Todos los datos personales registrados\n");
    $printer->text("  están protegidos conforme a la Ley\n");
    $printer->text("  Orgánica de Protección de Datos (LOPDP).\n");

    // --- AVISO LEGAL ---
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->setEmphasis(true);
    $printer->text("AVISO LEGAL\n");
    $printer->setEmphasis(false);
    $printer->text("La permanencia en el establecimiento\n");
    $printer->text("implica la aceptación total e irrevocable\n");
    $printer->text("de todos los términos y condiciones aquí\n");
    $printer->text("expuestos.\n");
    $printer->text("Reservas realizadas en línea, vía telefónica\n");
    $printer->text("o mediante plataformas digitales, así como\n");
    $printer->text("los abonos electrónicos, constituyen\n");
    $printer->text("aceptación electrónica válida según ley.\n");
    $printer->text("Toda permanencia, uso del cuarto,\n");
    $printer->text("pago, abono o check-in implica un\n");
    $printer->text("contrato verbal de hospedaje con\n");
    $printer->text("aceptación expresa de estas normas.\n");


    $printer->setEmphasis(true);
    $printer->text("¡Gracias por su estadía!\n");
    $printer->setEmphasis(false);
    $printer->cut();
    $printer->close();
}
