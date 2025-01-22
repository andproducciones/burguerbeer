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
	require_once '../pdf/vendor/autoload.php';
	use Dompdf\Dompdf;

	if(empty($_REQUEST['cl']) || empty($_REQUEST['f']))
	{
		echo "No es posible generar la factura.";
	}else{


		$codCliente = $_REQUEST['cl'];
		$noFactura = $_REQUEST['f'];
		$anulada = '';

		$query = mysqli_query($conection,"SELECT f.nofactura,
			f.mesa,u.nombre,u.apellido,f.estatus,f.fecha
			FROM factura f
			INNER JOIN usuario u ON f.usuario = u.usuario 
			WHERE f.nofactura = $noFactura AND f.estatus != 10");

		$result = mysqli_num_rows($query);
		if($result > 0){

			$factura = mysqli_fetch_assoc($query);
			
			$no_factura = $factura['nofactura'];

			if($factura['estatus'] == 2){
				$anulada = '<img class="anulada" src="img/anulado.png" alt="Anulada">';
			}

			$query_productos = mysqli_query($conection,"SELECT p.producto,dt.cantidad,dt.atributos
														FROM detalle_factura dt
														INNER JOIN producto p
														ON dt.codproducto = p.codproducto
														WHERE dt.nofactura = $no_factura ");
			$result_detalle = mysqli_num_rows($query_productos);


			ob_start();
		    include(dirname('__FILE__').'/ticket.php');
		    $html = ob_get_clean();

		    echo $html;

			// instantiate and use the dompdf class
			$dompdf = new Dompdf();

			$dompdf->loadHtml($html);
			// (Optional) Setup the paper size and orientation
			$dompdf->setPaper('A4', 'portrait');
			// Render the HTML as PDF
			$dompdf->render();
			// Output the generated PDF to Browser
			$dompdf->stream('factura_'.$noFactura.'.pdf',array('Attachment'=>0));
			exit;
		}else{
			echo "no";
		}
	}

?>