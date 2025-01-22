<?php
$subtotal 	= 0;
$iva 	 	= 0;
$impuesto 	= 0;
$tl_sniva   = 0;
$total 		= 0;
session_start();
date_default_timezone_set('America/Guayaquil');
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

	$query = mysqli_query($conection,"SELECT f.nofactura,
		f.mesa,c.nombre,c.p_apellido,c.usuario,c.telefono,c.direccion,c.correo_c,u.nombre as nombreU ,u.apellido as apellidoU,f.estatus,f.fecha
		FROM factura f
		INNER JOIN usuario u ON f.usuario = u.usuario
		INNER JOIN clientes c ON f.codcliente = c.usuario 
		WHERE f.nofactura = $noFactura AND f.estatus != 10");

	$result = mysqli_num_rows($query);
	if($result > 0){

		$factura = mysqli_fetch_assoc($query);

		$no_factura = $factura['nofactura'];

		if($factura['estatus'] == 2){
			$anulada = '<img class="anulada" src="img/anulado.png" alt="Anulada">';
		}

		$query_productos = mysqli_query($conection,"SELECT p.producto,dt.cantidad,dt.atributos,dt.precio_venta,(dt.cantidad * dt.precio_venta) as precio_total
			FROM detalle_factura dt
			INNER JOIN producto p
			ON dt.codproducto = p.codproducto
			WHERE dt.nofactura = $no_factura ");
		$result_detalle = mysqli_num_rows($query_productos);

	}
}


//ob_start();
?>
<!DOCTYPE html>
<html>
<head>
	<style type="text/css">
		* {
			font-size: 8px;
			font-family: 'Arial';
			margin: 0;
			box-sizing: border-box;
		}

		table {

			border-collapse: collapse;
			width: 100%;
			align-items: center;
		}


		.info {
			width: 80px;
			max-width: 80px;
		}

		.producto {
			width: 120px;
			max-width: 120px;
			word-break: break-all;
		}

		.cantidad {
			width: 30px;
			max-width: 30px;
			word-break: break-all;
		}


		.valor {
			width: 30px;
			max-width: 30px;
			word-break: break-all;
		}

		.centrado {
			text-align: center;
			align-content: center;
		}

		.ticket {
			width: 230px;
			max-width: 230px;
			border: 1px solid;
			/*margin: 5px;*/
		}

		p{
			padding: 0px;
			margin: 3px;
		}

		.font8{
			font-size: 8px;

		}
		.font16{
			font-size: 14px;

		}

		.wdtotal{
			width: 180px;
		}

	</style>
</head>
<body id="ticket">
	<div style="margin-left:5px" class="ticket" >

		<table class="informacion">


			<tbody>
				<tr>
					<td class="info"><b>CLIENTE</b></td>
					<td class="producto"><?= $factura['nombre']; ?> <?= $factura['p_apellido']; ?></td>
				</tr>

				<tr>
					<td class="info"><b>RUC/CI</b></td>
					<td class="producto"><?= $factura['usuario']; ?></td>
				</tr>

				<tr>
					<td class="info"><b>DIRECCION</b></td>
					<td class="producto"><?= $factura['direccion']; ?></td>
				</tr>
				<tr>
					<td class="info"><b>TELEFONO</b></td>
					<td class="producto"><?= $factura['telefono']; ?></td>
				</tr>
				<tr>
					<td class="info"><b>MAIL</b></td>
					<td class="producto"><?= $factura['correo_c']; ?></td>
				</tr>

			</tbody>

		</table>
		<p class="centrado">========================================================</p>
		<table>
			<thead>
				<tr>
				<th class="producto">DESCRIPCION</th>
				<th class="cantidad">CANT</th>
				<th class="valor">VALOR</th>
				<th class="valor">TOTAL</th>
				</tr>
			</thead>

			<tbody id="detalle_totales">
				<?php

				if($result_detalle > 0){

					while ($row = mysqli_fetch_assoc($query_productos)){

						$precio_final = number_format($row['cantidad'] * $row['precio_venta'],2);  

						?>
						<tr>
							<td class="producto"><?php echo $row['producto']; ?></td>
							<td class="cantidad centrado"><?php echo $row['cantidad']; ?></td>
							<td class="valor centrado"><?php echo $row['precio_venta']; ?></td>
							<td class="valor centrado"><?php echo $precio_final; ?></td>
						</tr>
						<?php
						$precio_total = $row['precio_total'];
						$subtotal = round($subtotal + $precio_total, 2);
					}
				}

				$impuesto 	= number_format($subtotal * ($iva / 100), 2);
				$tl_sniva 	= number_format($subtotal - $impuesto,2 );
				$total 		= number_format($tl_sniva + $impuesto,2);
				?>
			</tbody>
			<tfoot></tfoot>	
		</table>

			<p class="centrado">-----------------------------------------------------------------------------------</p>
	<table>
		<tfoot id="detalle_totales">
			<tr>
				<td class="textright wdtotal"><b><span>SUBTOTAL</span></b></td>
				<td class="centrado"><span><?php echo $tl_sniva; ?></span></td>
			</tr>
			<tr>
				<td class="textright wdtotal"><b><span>DCTO.</span></b></td>
				<td class="centrado"><span><?php echo $impuesto; ?></span></td>
			</tr>
			<tr>
				<td class="textright wdtotal"><b><span>IVA (<?php echo $iva; ?> %)</span></b></td>
				<td class="centrado"><span><?php echo $impuesto; ?></span></td>
			</tr>
			<tr>
				<td class="textright wdtotal"><b><span>TOTAL</span></b></td>
				<td class="centrado"><b><span><?php echo $total; ?></span></b></td>
			</tr>
		</tfoot>
	</table>
	<p class="centrado">-----------------------------------------------------------------------------------</p>

	<table class="informacion">

			<tbody>
				<tr>
					<td class="info"><b>Forma de pago</b></td>
					<td class="producto">Efectivo</td>
				</tr>

				<tr>
					<td class="info"><b>Cajero</b></td>
					<td class="producto"><?= $factura['nombreU']; ?> <?= $factura['apellidoU']; ?></td>
				</tr>
				<tr>
					<td colspan="2" class="info centrado"><br><b>SABOR DE EXCELENCIA EN CADA BOCADO</b></td>
				</tr>
			</tbody>

		</table>

</div>
</body>
</html>
<?php 
$tamaño='185';
$html = ob_get_clean();

//echo $html;

require_once'../libreries/dompdf/autoload.inc.php';
use Dompdf\Dompdf;


$dompdf = new Dompdf();

$options = $dompdf ->getOptions();
$options -> set(array('isRemoteEnabled' => true));
$dompdf -> setOptions($options);

$dompdf->loadHtml("$html");

$dompdf->setPaper(array(0, 0, 210, $tamaño), 'landscape');
//$dompdf->setPaper('A4', 'landscape');

$dompdf->render();

$nombreArchivo = $noFactura.".pdf";
$rutaGuardado = "pdf/"; 
file_put_contents( $rutaGuardado.$nombreArchivo, $dompdf->output());

$dompdf->stream($nombreArchivo, array("Attachment" => false));
mysqli_close($conection);


exit();

?>
