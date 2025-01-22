<?php
	$subtotal 	= 0;
	$iva 	 	= 0;
	$impuesto 	= 0;
	$tl_sniva   = 0;
	$total 		= 0;
 
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
	include "../includes/functions.php";
	
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
		}
		}
ob_start();


 ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<link rel="icon" href="ruta_del_icono.ico" type="image/x-icon">
	<title>Factura</title>
    <style type="text/css">
    	@import url('fonts/BrixSansRegular.css');
@import url('fonts/BrixSansBlack.css');

*{
	margin: 0;
	padding: 0;
	box-sizing: border-box;
}
p, label, span, table{
	font-family: 'BrixSansRegular';
	font-size: 9pt;
}
.h2{
	font-family: 'BrixSansBlack';
	font-size: 16pt;
}
.h3{
	font-family: 'BrixSansBlack';
	font-size: 12pt;
	display: block;
	background: #0a4661;
	color: #FFF;
	text-align: center;
	padding: 3px;
	margin-bottom: 5px;
}
#page_pdf{
	width: 95%;
	margin: 15px auto 10px auto;
}

#factura_head, #factura_cliente, #factura_detalle{
	width: 100%;
	margin-bottom: 10px;
}
.logo_factura{
	width: 20%;
}
.info_empresa{
	width: 40%;
	text-align: center;
}
.info_factura{
	width: 35%;
}
.info_cliente{
	width: 100%;
}
.datos_cliente{
	width: 100%;
}
.datos_cliente tr td{
	width: 50%;
}
.datos_cliente{
	padding: 10px 10px 0 10px;
}
.datos_cliente label{
	width: 75px;
	display: inline-block;
}
.datos_cliente p{
	display: inline-block;
}

.textright{
	text-align: right;
}
.textleft{
	text-align: left;
}
.textcenter{
	text-align: center;
}
.round{
	border-radius: 10px;
	border: 1px solid #0a4661;
	overflow: hidden;
	padding-bottom: 15px;
}
.round p{
	padding: 0 15px;
}

#factura_detalle{
	border-collapse: collapse;
}
#factura_detalle thead th{
	background: #058167;
	color: #FFF;
	padding: 5px;
}
#detalle_productos tr:nth-child(even) {
    background: #ededed;
}
#detalle_totales span{
	font-family: 'BrixSansBlack';
}
.nota{
	font-size: 8pt;
}
.label_gracias{
	font-family: verdana;
	font-weight: bold;
	font-style: italic;
	text-align: center;
	margin-top: 20px;
}
.anulada{
	position: absolute;
	left: 50%;
	top: 50%;
	transform: translateX(-50%) translateY(-50%);
}
    </style>
</head>
<body>
<?php echo $anulada; 


$nombreImagen = "img/logo.jpg";
$imagenBase64 = "data:image/png;base64," . base64_encode(file_get_contents($nombreImagen));?>
<div id="page_pdf">
	<table id="factura_head">
		<tr>
			<td class="logo_factura">
				<div>
					<img src="<?= $imagenBase64 ?>" width="150" height="150">
				</div>
			</td>
			 <td class="info_empresa">
				<?php
					if($result_config > 0){
						$iva = $configuracion['iva'];
				 ?>
				<div>
					<span><b><?php echo strtoupper($configuracion['nombre']); ?></b></span>
					<p><?php echo $configuracion['razon_social']; ?></p>
					<p><?php echo $configuracion['direccion']; ?></p>
					<p>RUC: <?php echo $configuracion['nit']; ?></p>
					<p>Teléfono: <?php echo $configuracion['telefono']; ?></p>
					<p>Email: <?php echo $configuracion['email']; ?></p>
				</div>
				<?php
					}
				 ?>
			</td>
			<td class="info_factura">
				<div class="round">
					<span class="h3">Comprobante</span>
					<p>No.: <strong>00-000000<?php echo $factura['nofactura']; ?></strong></p>
					<p>Fecha: <?php echo $factura['fecha']; ?></p>
					<p>Hora: <?php echo $factura['hora']; ?></p>
					<p>Vendedor: <?php echo $factura['vendedor1']; ?> <?php echo $factura['vendedor2']; ?></p>
				</div>
			</td>
		</tr>
	</table>
	<table id="factura_cliente">
		<tr>
			<td class="info_cliente">
				<div class="round">
					<span class="h3">Cliente</span>
					<table class="datos_cliente">
						<tr>
							<td><label>Cedula:</label><p><?php echo $factura['usuario']; ?></p></td>
							<td><label>Teléfono:</label> <p><?php echo $factura['telefono']; ?></p></td>
						</tr>
						<tr>
							<td><label>Nombre:</label> <p><?php echo $factura['nombre']; ?> <?php echo $factura['p_apellido']; ?></p></td>
							<td><label>Dirección:</label> <p><?php echo $factura['direccion']; ?></p></td>
						</tr>
					</table>
				</div>
			</td>

		</tr>
	</table>

	<table id="factura_detalle">
			<thead>
				<tr>
					<th width="50px">Cant.</th>
					<th class="textleft">Descripción</th>
					<th class="textright" width="70px">P. Unitario.</th>
					<th class="textright" width="70px"> Precio Total</th>
				</tr>
			</thead>
			<tbody id="detalle_productos">

			<?php

				if($result_detalle > 0){

					$data = array();

					while ($row = mysqli_fetch_assoc($query_productos)){

						array_push($data,$row['producto']);
						array_push($data,$row['cantidad']);
						array_push($data,$row['precio_venta']);
						array_push($data,$row['precio_total']);
			 ?>
				<tr>
					<td class="textcenter"><?php echo $row['cantidad']; ?></td>
					<td><?php echo $row['producto']; ?></td>
					<td class="textright">$ <?php echo $row['precio_venta']; ?></td>
					<td class="textright">$ <?php echo $row['precio_total']; ?></td>
				</tr>
			<?php
						$precio_total = $row['precio_total'];
						$subtotal = round($subtotal + $precio_total, 2);
					}
				}

				$impuesto 	= number_format(round($subtotal * ($iva / 100),2),2);
				$tl_sniva 	= number_format(round($subtotal - $impuesto,2),2);
				$total 		= number_format(round($tl_sniva + $impuesto,2),2);
			?>
			</tbody>
			<tfoot id="detalle_totales">
				<tr>
					<td colspan="3" class="textright"><span>SUBTOTAL</span></td>
					<td class="textright"><span>$ <?php echo $tl_sniva; ?></span></td>
				</tr>
				<tr>
					<td colspan="3" class="textright"><span>IVA (<?php echo $iva; ?> %)</span></td>
					<td class="textright"><span><?php echo $impuesto; ?></span></td>
				</tr>
				<tr> 
					<td colspan="3" class="textright"><span><b>TOTAL</b></span></td>
					<td class="textright"><span><b>$ <?php echo $total; ?></b></span></td>
				</tr>
		</tfoot>
	</table>
	<div>
		<p class="nota">Si usted tiene preguntas sobre esta factura, <br>pongase en contacto con nombre, teléfono y Email</p>
		<h4 class="label_gracias">¡Gracias por su compra!</h4>
	</div>

</div>

</body>
</html>

<script> 
	setTimeout(function(){
		window.close();
	}, 1000);
</script>

<?php
//print_r($data);
//exit();

$tamaño='190';
$html = ob_get_clean();

//echo $html;

//imprimirFacturaComandas($factura, $tl_sniva, $total, $data);

require_once'../libreries/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
// Crear una instancia de Dompdf
$dompdf = new Dompdf();

// Habilitar la carga de recursos remotos (si es necesario)
$options = $dompdf->getOptions();
$options->set('isRemoteEnabled', true);
$dompdf->setOptions($options);

// Cargar el contenido HTML en Dompdf
$dompdf->loadHtml($html);

// Establecer el tamaño del papel
$dompdf->setPaper('A5');

// Renderizar el PDF
$dompdf->render();

// Nombre del archivo PDF
$nombreArchivo = "factura.pdf";

// Mostrar el PDF en el navegador para su descarga
$dompdf->stream($nombreArchivo, array("Attachment" => false));
mysqli_close($conection);
// Salir del script PHP
exit();

 ?>
