<?php

	//print_r($_REQUEST);
	//exit;
	//echo base64_encode('2');
	//exit;
	session_start();
	if(empty($_SESSION['active']))
	{
		header('location: ../');
	}

	include "../../conexion.php";

	if(empty($_REQUEST['cl']) || empty($_REQUEST['f']))
	{
		echo "No es posible generar la factura.";
	}else{


		$codCliente = $_REQUEST['cl'];
		$noFactura = $_REQUEST['f'];
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

			if($factura['estatus'] == 2){
				$anulada = '<img class="anulada" src="img/anulado.png" alt="Anulada">';
			}

			$query_productos = mysqli_query($conection,"SELECT p.producto,dt.cantidad,dt.precio_venta,(dt.cantidad * dt.precio_venta) as precio_total
														FROM factura f
														INNER JOIN detalle_factura dt
														ON f.nofactura = dt.nofactura
														INNER JOIN producto p
														ON dt.codproducto = p.codproducto
														WHERE f.nofactura = $no_factura ");
			$result_detalle = mysqli_num_rows($query_productos);

			ob_start();
		    include(dirname('__FILE__').'/factura.php');
		    

		    $html = ob_get_clean();

			require_once'../libreries/dompdf/autoload.inc.php';
			use Dompdf\Dompdf;


			$dompdf = new Dompdf();

			$options = $dompdf ->getOptions();
			$options -> set(array('isRemoteEnabled' => true));
			$dompdf -> setOptions($options);

			$dompdf->loadHtml("$html");

			$dompdf->setPaper(array(0, 0, 140, $tamaño), 'landscape');
			//$dompdf->setPaper('A4', 'landscape');

			$dompdf->render();

			$nombreArchivo = $co."-".$mesa.".pdf";
			$rutaGuardado = "pdf/"; 
			file_put_contents( $rutaGuardado.$nombreArchivo, $dompdf->output());

			$dompdf->stream($nombreArchivo, array("Attachment" => false));
			mysqli_close($conection);



		}else{
			echo "no";
		}
	}

?>