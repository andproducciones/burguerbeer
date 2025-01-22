<?php 
 session_start();
 date_default_timezone_set('America/Guayaquil');


if(!empty($_POST)){

	include "../../conexion.php";
	include "../includes/functions.php";

	if($_POST['action'] == 'imprimirPrecuenta2'){

			//print_r($_POST);
			//exit;


			if(empty($_REQUEST['mesa']) || empty($_REQUEST['nombre']))
	{
		echo "Error | No es posible generar la ticket.1";
		exit;
	}else{

		$co 		= md5($_SESSION['idUser']);
		$mesa 		= $_POST['mesa'];

		$nombreCliente = $_POST['nombre'];

		$query 	= mysqli_query($conection,"SELECT * FROM detalle_temp WHERE token_user = '$co' AND mesa = $mesa ");

			$result = mysqli_num_rows($query);

			if ($result > 0) {
				
				

			$query_mesa = mysqli_query($conection,"SELECT numero FROM mesas WHERE id = $mesa");

		$mesas = mysqli_fetch_assoc($query_mesa);
		$mesa2 = $mesas['numero'];


		
		$query_update 	= mysqli_query($conection,"UPDATE detalle_temp SET preparar = 2 WHERE token_user = '$co' AND mesa= $mesa");

		if ($query_update){

		$query_productos = mysqli_query($conection,"SELECT p.producto,dt.cantidad,dt.mesa,dt.atributos,dt.precio_venta,(dt.cantidad * dt.precio_venta) as precio_total
			FROM detalle_temp dt
			INNER JOIN producto p
			ON dt.codproducto = p.codproducto
			WHERE dt.token_user = '$co' AND dt.mesa = $mesa" );

			$result_detalle = mysqli_num_rows($query_productos);

		}else{

			echo "Error | No es posible generar la ticket.3";
			exit;

		}

		if($result_detalle == 0){

			echo "Error | No es posible generar la ticket.2";
			exit;
		
		}else{

			$fecha = date('Y-m-d G:i:s');
			$nombreMesero = $_SESSION['nombre'].' '.$_SESSION['apellido'];


					$data = array();
					$subtotal = 0;
					$iva = 0;

					while($row = mysqli_fetch_assoc($query_productos)){

						array_push($data,$row['producto']);
						array_push($data,$row['cantidad']);
						array_push($data,$row['precio_venta']);
						array_push($data,$row['precio_total']);


						$precio_total = $row['precio_total'];
						$subtotal = round($subtotal + $precio_total, 2);
					}
				}

				$impuesto 	= number_format(round($subtotal * ($iva / 100),2),2);
				$tl_sniva 	= number_format(round($subtotal - $impuesto,2),2);
				$total 		= number_format(round($tl_sniva + $impuesto,2),2);


				$imprimir = imprimirPrecuenta($mesa2, $nombreCliente, $tl_sniva, $total, $data);

				//echo $imprimir;
				if ($imprimir) {
					$data3 = array();
        			$data3['code'] = 1; 
        			$data3['user'] = $_SESSION['idUser']; 
        			echo JSON_encode($data3,JSON_UNESCAPED_UNICODE);
				}else{
					echo "error";
				}

					}else{
						echo "error";
					}
				
				}
		

		}


		if($_POST['action'] == 'imprimirTodo'){


			if(empty($_REQUEST['cl']) || empty($_REQUEST['f']) || empty($_REQUEST['nC']))
			{
				echo "No es posible generar la factura.";
				
			}else{

				$codCliente = $_REQUEST['cl'];
				$noFactura = $_REQUEST['f'];
				$nombreCliente = $_REQUEST['nC'];
				$anulada = '';

				$query_config   = mysqli_query($conection,"SELECT * FROM configuracion");
				$result_config  = mysqli_num_rows($query_config);

				if($result_config > 0){
					$configuracion = mysqli_fetch_assoc($query_config);
				}


				$query = mysqli_query($conection,"SELECT 
					f.nofactura, 
					DATE_FORMAT(f.fecha, '%d/%m/%Y') as fecha, 
					DATE_FORMAT(f.fecha,'%H:%i:%s') as  hora, 
					f.codcliente,
					f.mesa, 
					f.estatus,
					v.nombre as vendedor1,
					v.apellido as vendedor2,
					cl.usuario,
					cl.nombre,
					cl.p_apellido,
					cl.correo_c,
					cl.direccion,
					cl.telefono

					FROM factura f
					INNER JOIN usuario v ON f.usuario = v.usuario 
					INNER JOIN clientes cl ON f.codcliente = cl.usuario


					WHERE f.nofactura = $noFactura AND f.codcliente = '$codCliente'  AND f.estatus != 10");

				$result = mysqli_num_rows($query);
				if($result > 0){

					$factura = mysqli_fetch_assoc($query);



					$no_factura = $factura['nofactura'];
					$query_productos = mysqli_query($conection,"SELECT p.producto,dt.cantidad,dt.precio_venta,(dt.cantidad * dt.precio_venta) as precio_total,dt.observaciones
						FROM factura f
						INNER JOIN detalle_factura dt ON f.nofactura = dt.nofactura
						INNER JOIN producto p ON dt.codproducto = p.codproducto
						WHERE f.nofactura = $no_factura ");
					$result_detalle = mysqli_num_rows($query_productos);
				}
			}


			if($result_detalle > 0){

					$data = array();
					$data2 = array();
					$subtotal = 0;
					$iva = 0;

					while ($row = mysqli_fetch_assoc($query_productos)){

						array_push($data,$row['producto']);
						array_push($data,$row['cantidad']);
						array_push($data,$row['precio_venta']);
						array_push($data,$row['precio_total']);


						array_push($data2,$row['producto']);
						array_push($data2,$row['cantidad']);

						if (!empty($row['observaciones'])) {
							
							$array = json_decode($row['observaciones'], JSON_UNESCAPED_UNICODE);

							$seleccionado = '';
							$seleccionado2 = '';
							$counter = 0;
							$count = count($array);

							foreach ($array as $clave => $valor) {
							    $seleccionado .= $valor;

							    if ($counter < $count - 2) {
					                    $seleccionado .= ', ';
					                }

					                $counter++;					                
							}

							$seleccionado2 = $seleccionado;

						}else{
							$seleccionado2 = '';
						}


						array_push($data2,$seleccionado2);


						//print_r($data2);

						//exit();

						$precio_total = $row['precio_total'];
						$subtotal = round($subtotal + $precio_total, 2);
					}
				}

				$impuesto 	= number_format(round($subtotal * ($iva / 100),2),2);
				$tl_sniva 	= number_format(round($subtotal - $impuesto,2),2);
				$total 		= number_format(round($tl_sniva + $impuesto,2),2);


				$mesera = $_SESSION['nombre'].' '.$_SESSION['apellido'];
				$mesa 	= $factura['mesa'];
				$fecha2 	= $factura['fecha'].' '.$factura['hora'] ;


				imprimirComanda($mesa, $nombreCliente, $mesera, $data2, $fecha2);
				
				imprimirComanda($mesa, $nombreCliente, $mesera, $data2, $fecha2);

				$imprimir = imprimirPrecuenta($mesa, $nombreCliente, $tl_sniva, $total, $data);

				
				if ($imprimir){
				
				echo 1;	
				
				}else{
				
				echo $imprimir;

				}

		}

		if($_POST['action'] == 'imprimirComanda'){


			if(empty($_REQUEST['cl']) || empty($_REQUEST['f']) || empty($_REQUEST['nC']))
			{
				echo "No es posible generar la factura.";
				
			}else{

				$codCliente = $_REQUEST['cl'];
				$noFactura = $_REQUEST['f'];
				$nombreCliente = $_REQUEST['nC'];
				$anulada = '';

				$query_config   = mysqli_query($conection,"SELECT * FROM configuracion");
				$result_config  = mysqli_num_rows($query_config);

				if($result_config > 0){
					$configuracion = mysqli_fetch_assoc($query_config);
				}


				$query = mysqli_query($conection,"SELECT 
					f.nofactura, 
					DATE_FORMAT(f.fecha, '%d/%m/%Y') as fecha, 
					DATE_FORMAT(f.fecha,'%H:%i:%s') as  hora, 
					f.codcliente,
					f.mesa, 
					f.estatus,
					v.nombre as vendedor1,
					v.apellido as vendedor2,
					cl.usuario,
					cl.nombre,
					cl.p_apellido,
					cl.correo_c,
					cl.direccion,
					cl.telefono

					FROM factura f
					INNER JOIN usuario v ON f.usuario = v.usuario 
					INNER JOIN clientes cl ON f.codcliente = cl.usuario


					WHERE f.nofactura = $noFactura AND f.codcliente = '$codCliente'  AND f.estatus != 10");

				$result = mysqli_num_rows($query);
				if($result > 0){

					$factura = mysqli_fetch_assoc($query);



					$no_factura = $factura['nofactura'];
					$query_productos = mysqli_query($conection,"SELECT p.producto,dt.cantidad,dt.precio_venta,(dt.cantidad * dt.precio_venta) as precio_total,dt.observaciones
						FROM factura f
						INNER JOIN detalle_factura dt ON f.nofactura = dt.nofactura
						INNER JOIN producto p ON dt.codproducto = p.codproducto
						WHERE f.nofactura = $no_factura ");
					$result_detalle = mysqli_num_rows($query_productos);
				}
			}


			if($result_detalle > 0){

					$data = array();
					$data2 = array();
					$subtotal = 0;
					$iva = 0;

					while ($row = mysqli_fetch_assoc($query_productos)){

						array_push($data,$row['producto']);
						array_push($data,$row['cantidad']);
						array_push($data,$row['precio_venta']);
						array_push($data,$row['precio_total']);


						array_push($data2,$row['producto']);
						array_push($data2,$row['cantidad']);

						if (!empty($row['observaciones'])) {
							
							$array = json_decode($row['observaciones'], JSON_UNESCAPED_UNICODE);

							$seleccionado = '';
							$seleccionado2 = '';
							$counter = 0;
							$count = count($array);

							foreach ($array as $clave => $valor) {
							    $seleccionado .= $valor;

							    if ($counter < $count - 2) {
					                    $seleccionado .= ', ';
					                }

					                $counter++;					                
							}

							$seleccionado2 = $seleccionado;

						}else{
							$seleccionado2 = '';
						}


						array_push($data2,$seleccionado2);


						//print_r($data2);

						//exit();

						$precio_total = $row['precio_total'];
						$subtotal = round($subtotal + $precio_total, 2);
					}
				}

				$impuesto 	= number_format(round($subtotal * ($iva / 100),2),2);
				$tl_sniva 	= number_format(round($subtotal - $impuesto,2),2);
				$total 		= number_format(round($tl_sniva + $impuesto,2),2);


				$mesera = $_SESSION['nombre'].' '.$_SESSION['apellido'];
				$mesa 	= $factura['mesa'];
				$fecha2 	= $factura['fecha'].' '.$factura['hora'] ;


				imprimirComanda($mesa, $nombreCliente, $mesera, $data2, $fecha2);
				
				$imprimir = imprimirComanda($mesa, $nombreCliente, $mesera, $data2, $fecha2);

				//$imprimir = imprimirPrecuenta($mesa, $nombreCliente, $tl_sniva, $total, $data);

				
				if ($imprimir){
				
				echo 1;	
				
				}else{
				
				echo $imprimir;

				}

		}

		if($_POST['action'] == 'imprimirFactura'){


			if(empty($_REQUEST['cl']) || empty($_REQUEST['f']) || empty($_REQUEST['nC']))
			{
				echo "No es posible generar la factura.";
				
			}else{

				$codCliente = $_REQUEST['cl'];
				$noFactura = $_REQUEST['f'];
				$nombreCliente = $_REQUEST['nC'];
				$anulada = '';

				$query_config   = mysqli_query($conection,"SELECT * FROM configuracion");
				$result_config  = mysqli_num_rows($query_config);

				if($result_config > 0){
					$configuracion = mysqli_fetch_assoc($query_config);
				}


				$query = mysqli_query($conection,"SELECT 
					f.nofactura, 
					DATE_FORMAT(f.fecha, '%d/%m/%Y') as fecha, 
					DATE_FORMAT(f.fecha,'%H:%i:%s') as  hora, 
					f.codcliente,
					f.mesa, 
					f.estatus,
					v.nombre as vendedor1,
					v.apellido as vendedor2,
					cl.usuario,
					cl.nombre,
					cl.p_apellido,
					cl.correo_c,
					cl.direccion,
					cl.telefono

					FROM factura f
					INNER JOIN usuario v ON f.usuario = v.usuario 
					INNER JOIN clientes cl ON f.codcliente = cl.usuario


					WHERE f.nofactura = $noFactura AND f.codcliente = '$codCliente'  AND f.estatus != 10");

				$result = mysqli_num_rows($query);
				if($result > 0){

					$factura = mysqli_fetch_assoc($query);



					$no_factura = $factura['nofactura'];
					$query_productos = mysqli_query($conection,"SELECT p.producto,dt.cantidad,dt.precio_venta,(dt.cantidad * dt.precio_venta) as precio_total,dt.observaciones
						FROM factura f
						INNER JOIN detalle_factura dt ON f.nofactura = dt.nofactura
						INNER JOIN producto p ON dt.codproducto = p.codproducto
						WHERE f.nofactura = $no_factura ");
					$result_detalle = mysqli_num_rows($query_productos);
				}
			}


			if($result_detalle > 0){

					$data = array();
					$data2 = array();
					$subtotal = 0;
					$iva = 0;

					while ($row = mysqli_fetch_assoc($query_productos)){

						array_push($data,$row['producto']);
						array_push($data,$row['cantidad']);
						array_push($data,$row['precio_venta']);
						array_push($data,$row['precio_total']);


						array_push($data2,$row['producto']);
						array_push($data2,$row['cantidad']);

						if (!empty($row['observaciones'])) {
							
							$array = json_decode($row['observaciones'], JSON_UNESCAPED_UNICODE);

							$seleccionado = '';
							$seleccionado2 = '';
							$counter = 0;
							$count = count($array);

							foreach ($array as $clave => $valor) {
							    $seleccionado .= $valor;

							    if ($counter < $count - 2) {
					                    $seleccionado .= ', ';
					                }

					                $counter++;					                
							}

							$seleccionado2 = $seleccionado;

						}else{
							$seleccionado2 = '';
						}


						array_push($data2,$seleccionado2);


						//print_r($data2);

						//exit();

						$precio_total = $row['precio_total'];
						$subtotal = round($subtotal + $precio_total, 2);
					}
				}

				$impuesto 	= number_format(round($subtotal * ($iva / 100),2),2);
				$tl_sniva 	= number_format(round($subtotal - $impuesto,2),2);
				$total 		= number_format(round($tl_sniva + $impuesto,2),2);


				$mesera = $_SESSION['nombre'].' '.$_SESSION['apellido'];
				$mesa 	= $factura['mesa'];
				$fecha2 	= $factura['fecha'].' '.$factura['hora'] ;


				//imprimirComanda($mesa, $nombreCliente, $mesera, $data2, $fecha2);
				
				//$imprimir = imprimirComanda($mesa, $nombreCliente, $mesera, $data2, $fecha2);

				$imprimir = imprimirPrecuenta($mesa, $nombreCliente, $tl_sniva, $total, $data);

				
				if ($imprimir){
				
				echo 12;	
				
				}else{
				
				echo $imprimir;

				}

		}



	if ($_POST['action'] == 'imprimirComanda2') {

		//print_r($_POST);
    try {
        // Iniciar una transacción
        mysqli_begin_transaction($conection);

        if (empty($_REQUEST['mesa']) || empty($_REQUEST['nombre'])) {
            echo "Error | No es posible generar el ticket.12";
            exit;
        } else {
            $co = md5($_SESSION['idUser']);
            $mesa = $_REQUEST['mesa'];
            $nombreCliente = $_REQUEST['nombre'];

            $query_mesa = mysqli_query($conection, "SELECT numero FROM mesas WHERE id = $mesa");
            $mesas = mysqli_fetch_assoc($query_mesa);
            $mesa2 = $mesas['numero'];

            $query_update = mysqli_query($conection, "UPDATE detalle_temp SET preparar = 2 WHERE token_user = '$co' AND mesa = $mesa");

            if (!$query_update) {
                throw new Exception("Error | No es posible actualizar el detalle de la mesa.");
            }

            $query_productos = mysqli_query($conection, "SELECT p.producto, dt.cantidad, dt.mesa, dt.atributos, dt.observaciones
                FROM detalle_temp dt
                INNER JOIN producto p ON dt.codproducto = p.codproducto
                WHERE dt.token_user = '$co' AND dt.mesa = $mesa");

            if (!$query_productos) {
                throw new Exception("Error | No es posible obtener los productos de la mesa.");
            }

            $result_detalle = mysqli_num_rows($query_productos);
        }

        // Inicializamos $data2 como un arreglo vacío
        $data2 = array();

        if ($result_detalle > 0) {
    $subtotal = 0;
    $iva = 0;
    $data2 = []; // Inicializamos el array para almacenar los datos

    while ($row = mysqli_fetch_assoc($query_productos)) {
        // Añadimos producto y cantidad a $data2
        $data2[] = $row['producto'];
        $data2[] = $row['cantidad'];

        // Procesamos las observaciones si están presentes
        if (!empty($row['observaciones'])) {
            $array = json_decode($row['observaciones'], true); // JSON ya se decodifica en un array asociativo
            if (is_array($array)) {
                $seleccionado = implode(', ', $array); // Convierte el array en una cadena con ", " como separador
            } else {
                $seleccionado = ''; // En caso de error en el formato JSON, dejamos vacío
            }
            $data2[] = $seleccionado;
        } else {
            $data2[] = ''; // Si no hay observaciones, añadimos un valor vacío
        }
    }

    //print_r($data2);
}


        $fecha = date('Y-m-d G:i:s');
        $nombreMesero = $_SESSION['nombre'] . ' ' . $_SESSION['apellido'];

        // Comprobamos que $data2 no esté vacío antes de llamar a imprimirComanda
        if (!empty($data2)) {
            $imprimir = imprimirComanda($mesa2, $nombreCliente, $nombreMesero, $data2, $fecha);

            if (!$imprimir) {
                throw new Exception("Error | No es posible imprimir la comanda.");
            }
        } else {
            throw new Exception("Error | No hay productos para generar el ticket.");
        }

        // Si todo salió bien, hacemos commit
        mysqli_commit($conection);
        
        $data3 = array();
        $data3['code'] = 1; 
        $data3['user'] = $_SESSION['idUser']; 

        echo JSON_encode($data3,JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        // En caso de error, revertimos la transacción
        mysqli_rollback($conection);
        echo $e->getMessage();
    }
}



} 

?>