<?php
 session_start();
 date_default_timezone_set('America/Guayaquil');
	if(empty($_SESSION['active']))
	{
		header('location: ../');
	}

	include "../../conexion.php";
	include "../includes/functions.php";

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

		}
		}


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
					<td class="producto"><?= $factura['nombre']; ?> <?= $factura['apellido']; ?></td>
				</tr>

				<tr>
					<td class="info"><b>FECHA</b></td>
					<td class="producto"><?= $factura['fecha']; ?></td>
				</tr>

				<tr>
					<th colspan="2" class="font16">MESA #<?= $factura['mesa']; ?></th>
				</tr>
			</tbody>

		</table>
		<p class="centrado">==========================================</p>
		<table>
			</thead>

			<tbody>
				<?php

				if($result_detalle > 0){

					$data = array();

					while ($row = mysqli_fetch_assoc($query_productos)){

						if (!empty($row['atributos'])) {
							$atributos = '<tr>
								<td colspan="2" class="centrado font10">'.$row['atributos'].'</td>
							</tr>';
						}else{
							$atributos = '';
						}

						array_push($data,$row['producto'].' '.$atributos);
						array_push($data,$row['cantidad']);
			 ?>
				<tr>
					<td class="cantidad centrado"><?php echo $row['cantidad']; ?></td>
					<td class="producto"><?php echo $row['producto']; ?></td>
				</tr>

				<?php
				echo $atributos;					
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

$mesa = $factura['mesa'];
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

// Nombre del archivo PDF
$nombreArchivo = "ticket.pdf";

// Mostrar el PDF en el navegador para su descarga
$dompdf->stream($nombreArchivo, array("Attachment" => false));

mysqli_close($conection);


exit();

 ?>
