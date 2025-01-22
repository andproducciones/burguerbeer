<?php
 session_start();
 //date_default_timezone_set('America/Guayaquil');
	if(empty($_SESSION['active']))
	{
		header('location: ../');
	}

	include "../../conexion.php";
	include "../includes/functions.php";

if(empty($_REQUEST['co']) || empty($_REQUEST['m']) || empty($_REQUEST['u']))
	{
		echo "Error | No es posible generar la ticket.1";
		exit;
	}else{


		$co 		= $_REQUEST['co'];
		$user 		= $_REQUEST['u'];
		$mesa 		= $_REQUEST['m'];
		$anulada 	= '';


		$query_productos = mysqli_query($conection,"SELECT p.producto,dt.cantidad,dt.mesa,dt.atributos
			FROM detalle_temp dt
			INNER JOIN producto p
			ON dt.codproducto = p.codproducto
			WHERE dt.token_user = '$co' AND dt.mesa = $mesa" );

			$result_detalle = mysqli_num_rows($query_productos);

		}

		if($result_detalle == 0){

			echo "Error | No es posible generar la ticket.2";
			exit;
		}else{


ob_start();
 ?>
<!DOCTYPE html>
<html>
<head>
	<style type="text/css">
		* {
			font-size: 14px;
			font-family: 'Arial';
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		td,
		th,
		tr,
		table {

			border-collapse: collapse;
			width: 100%;
		}


		td.info,
		th.info {
			width: 80px;
			max-width: 80px;
		}
		td.producto,
		th.producto {
			width: 220px;
			max-width: 220px;
		}

		td.cantidad,
		th.cantidad {
			width: 40px;
			max-width: 40px;
			word-break: break-all;
		}

		.centrado {
			text-align: center;
			align-content: center;
		}

		.ticket {
			width: 290px;
			max-width: 285px;
			/*border: 1px solid;
			margin: 5px;*/
		}

		p{
			padding: 0px;
			margin: 3px;
		}

		.font10{
			font-size: 10px;

		}
		.font16{
			font-size: 16px;

		}

	</style>
</head>
<body id="ticket">
	<div style="margin-left:20px" class="ticket" >
		<p class="centrado">
		<b>BURGUERBEER</b></p>
		<table class="informacion">
			<tbody>
				<tr>
					<td class="info"><b>MESERO</b></td>
					<td class="producto"><?= $_SESSION['nombre']; ?> <?= $_SESSION['apellido']; ?></td>
				</tr>

				<tr>
					<td class="info"><b>FECHA</b></td>
					<td class="producto"><?= date('Y-m-d G:i:s'); ?></td>
				</tr>

				<tr>
					<th colspan="2" class="font16">MESA #<?= $mesa; ?></th>
				</tr>
			</tbody>

		</table>
		<p class="centrado">==========================================</p>
		<table>
			</thead>

			<tbody>
				<?php

				$data = array();

				

					while ($row = mysqli_fetch_assoc($query_productos)){

						if (!empty($row['atributos'])) {
							$atrubutos = '<tr>
								<td colspan="2" class="centrado font10">'.$row['atributos'].'</td>
							</tr>';
						}else{
							$atrubutos = '';
						}

						array_push($data,$row['producto'].' '.$atrubutos);
						array_push($data,$row['cantidad']);


			 ?>
				<tr>
					<td class="cantidad centrado"><?php echo $row['cantidad']; ?></td>
					<td class="producto"><?php echo $row['producto']; ?></td>
				</tr>

				<?php
				echo $atrubutos;					
						}
					}

				?>
			</tbody>
		</table>
		<p class="centrado">==========================================</p>
	</div>
</body>
</html>

<script> 
	setTimeout(function(){
		window.close();
	}, 1000);
</script>


<?php 

$fecha = date('Y-m-d G:i:s');

$nombreMesero = $_SESSION['nombre'].' '.$_SESSION['apellido'];
//imprimirTicket($mesa,$nombreMesero,$data,$fecha);
$tamaño='190';
$html = ob_get_clean();

//echo $html;

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
echo "<script languaje='javascript' type='text/javascript'>window.close();</script>";
exit();

 ?>
