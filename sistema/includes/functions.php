<?php 
	date_default_timezone_set('America/Guayaquil'); 
	
	function fechaC(){
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


function buscarCliente(){

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

function imprimirComandaMatricial($numeroMesa, $nombreMesera, $productos) {
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
function imprimirFacturaMatricial($factura, $tl_sniva, $total, $productos) {
    try {

        include "../../conexion.php";

        $query_config   = mysqli_query($conection,"SELECT * FROM configuracion");
        $result_config  = mysqli_num_rows($query_config);

        if($result_config > 0){
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
function imprimirFactura($factura, $nombreCliente, $tl_sniva, $total, $productos) {
    try {
        include "../../conexion.php";

         $query_config   = mysqli_query($conection,"SELECT * FROM configuracion");
        $result_config  = mysqli_num_rows($query_config);

        if($result_config > 0){
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
function imprimirPrecuenta($mesa, $nombreCliente, $tl_sniva, $total, $productos) {
    
    try {
        include "../../conexion.php";

        $query_config   = mysqli_query($conection,"SELECT * FROM configuracion");
        $result_config  = mysqli_num_rows($query_config);

        if($result_config > 0){
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
function imprimirComanda($numeroMesa, $nombreCliente, $nombreMesera, $productos, $fecha) {
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
function imprimirCierreCaja($data) {
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

    // Valores calculados del sistema
    $totalEfectivo      = $data['total_efectivo'];
    $totalTarjeta       = $data['total_tarjeta'];
    $totalTransferencia = $data['total_transferencia'];
    $totalDeUna         = $data['total_deuna'];

    $total_venta = $monto_inicial + $total_cash;
    $observaciones = $data['observaciones'] ?? '';
    $compras = $data['compras'] ?? '';

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
        $printer->text("CIERRE DE CAJA\n");
        $printer->setEmphasis(false);
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Fecha Inicio: $fecha_inicio\n");
        $printer->text("Fecha Final:  $fecha_fin\n");
        $printer->text("Código:       $id_cierre\n");
        $printer->text("Cajero:       $nombre $apellido\n");
        $printer->text("------------------------------------------------\n");

        $printer->setEmphasis(true);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("VENTAS DEL DÍA\n");
        $printer->setEmphasis(false);
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Monto Inicial:          $ " . number_format($monto_inicial, 2) . "\n");
        $printer->text("Cantidad de Ventas:     $total_ventas\n");
        $printer->text("Monto por Ventas:       $ " . number_format($total_cash, 2) . "\n");
        $printer->text("TOTAL VENTAS:           $ " . number_format($total_venta, 2) . "\n");
        $printer->text("------------------------------------------------\n");

        $printer->setEmphasis(true);
        $printer->text("DETALLE DEL SISTEMA\n");
        $printer->setEmphasis(false);
        $printer->text("Efectivo (calc):        $ " . number_format($totalEfectivo, 2) . "\n");
        $printer->text("Tarjeta (calc):         $ " . number_format($totalTarjeta, 2) . "\n");
        $printer->text("Transferencia (calc):   $ " . number_format($totalTransferencia, 2) . "\n");
        $printer->text("DeUna (calc):           $ " . number_format($totalDeUna, 2) . "\n");
        $printer->text("------------------------------------------------\n");

        $printer->setEmphasis(true);
        $printer->text("SALIDAS DE CAJA\n");
        $printer->setEmphasis(false);
        foreach ($salidas as $salida) {
            $nombre_usuario = $salida['nombre_usuario'];
            $motivo = $salida['motivo'];
            $valor = $salida['valor'];
            $tipo_moneda = $salida['tipo_moneda'] == 1 ? 'EF' : 'TR';
            $printer->text("$nombre_usuario ($tipo_moneda): $motivo - $ " . number_format($valor, 2) . "\n");
        }
        $printer->setEmphasis(true);
        $printer->text("Total Salidas:          $ " . number_format($total_salidas, 2) . "\n");
        $printer->setEmphasis(false);
        $printer->text("------------------------------------------------\n");

        $printer->setEmphasis(true);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
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

        $printer->setEmphasis(true);
        $printer->text("DIFERENCIA CALC-ENTREGADO\n");
        $printer->setEmphasis(false);
        $printer->text("Efectivo:               $ " . number_format($efectivo - $totalEfectivo, 2) . "\n");
        $printer->text("Tarjeta:                $ " . number_format($tarjeta - $totalTarjeta, 2) . "\n");
        $printer->text("Transferencia:          $ " . number_format($transferencia - $totalTransferencia, 2) . "\n");
        $printer->text("DeUna:                  $ " . number_format($deuna - $totalDeUna, 2) . "\n");
        $printer->text("------------------------------------------------\n");

        $q_auditoria = mysqli_query($conection, "SELECT tipo_pago, estado, diferencia FROM auditoria_cierre_caja WHERE id_cierre = $id_cierre");
        if (mysqli_num_rows($q_auditoria) > 0) {
            $printer->setEmphasis(true);
            $printer->text("RESULTADO DE AUDITORÍA\n");
            $printer->setEmphasis(false);
            while ($row = mysqli_fetch_assoc($q_auditoria)) {
                $printer->text("{$row['tipo_pago']}: {$row['estado']} - $ " . number_format($row['diferencia'], 2) . "\n");
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
        $printer->text("CIERRE COMPLETO\n");
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





function mostrarTicketCierre($data) {
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


function imprimirSalidaDinero($data){
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

        if($tipo == 2){
            $tipoN = "ENTRADA";
        }else {
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

function compararMontosEntregadosVsCalculados($calculado, $entregado) {
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







 ?>