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

	if(empty($_REQUEST['co']))
	{
	echo "No es posible generar la factura.";
	}else{
		$correlativoP = $_REQUEST['co'];
		
		//print_r($correlativoP);exit;

		$anulada = '';
	


		$query = mysqli_query($conection,"SELECT dt.correlativo,dt.nofactura,p.producto,dt.cantidad, dt.comedor,dt.fecha,dt.estatus_dt FROM detalle_factura dt INNER JOIN producto p ON dt.codproducto = p.codproducto WHERE correlativo = $correlativoP AND estatus_dt != 10");

 		//$data = mysqli_fetch_assoc($query);
		



		$result = mysqli_num_rows($query);
		
		//print_r($data);

		if($result > 0){

			$data = mysqli_fetch_assoc($query);
			
			//$no_data = $data['nofactura'];

			if($data['estatus_dt'] == 2){
				$anulada = '<img class="anulada" src="img/consumido.png" >';
				//echo $anulada;
			}
			ob_start();
		    include(dirname('__FILE__').'/ticket.php');
		    $html = ob_get_clean();

			// instantiate and use the dompdf class
			$dompdf = new Dompdf();

			$dompdf->loadHtml($html);
			// (Optional) Setup the paper size and orientation
			//$dompdf->setPaper('A4', 'portrait');
			$dompdf->set_paper(array(0,0,550,279));
			// Render the HTML as PDF
			$dompdf->render();
			// Output the generated PDF to Browser
			$dompdf->stream('factura_'.$correlativoP.'.pdf',array('Attachment'=>0));
			exit;
		}
	
		
	}

?>