<?php 
session_start();

include "../conexion.php";

//echo ($_SESSION['idUser']);exit;


	$idUser = $_SESSION['idUser'];

	$query_credito = mysqli_query($conection, "SELECT credito FROM cliente WHERE usuario_c = $idUser");
	//$query_comedor = mysqli_query($conection,"SELECT * FROM tipo_comedor WHERE estatus = 1");
	//mysqli_close($conection);
	
	//print_r($query_credito);

	$data = mysqli_fetch_array($query_credito);

	$credito = $data['credito'];

	//print_r($credito);
  ?>
<a href=""></a>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<?php include "includes/scripts2.php"; ?>
	<title></title>
</head>
<body>
<?php include "includes/header2.php"; ?>
<section id="container">
		
		<div class="title_page">
			<h1><i class="fas fa-shopping-cart"></i> Realizar Compra</h1>
			<a href="index.php" class="btn_new"><i class="fas fa-arrow-circle-left"></i> Regresar</a>
		</div>
		

		<!--<div class="datos_cliente">
			<div class="action_cliente">
			<a href="../index.php" class="btn_new"><i class="fas fa-plus"></i> Nuevo Cliente</a>
			</div>
		
		<form name="form_new_cliente" id="form_new_cliente_venta" class="datos">
			<input type="hidden" name="action" value="addCliente">
			<input type="hidden" name="id_cliente" id="id_cliente" value="" required>
			<div class="wd30">
				<label>Cedula</label>
				<input type="text" name="cl_usuario" id="cl_usuario" maxlength="10" minlength="10">
			</div>
			<div class="wd30">
				<label>Nombre</label>
				<input type="text" name="nom_cliente" id="nom_cliente" disabled required>
			</div>
			<div class="wd30">
				<label>Apellido</label>
				<input type="text" name="ap_cliente" id="ap_cliente" disabled required>
			</div>
			<div id="div_registro_cliente" class="wd100">
				<button type="submit" class="btn_save"><i class="far fa-save fa-lg"></i> 
				Guardar</button>
			</div>
		</form>
 </div> -->
  <div class="containerTable">
	<table class="tbl_venta">
		<thead>
			<tr>
				<th>Comedor</th>
				<th>Producto</th>
				<th>Fecha</th>
				<th>Existencia</th>
				<th width="100px">Cantidad</th>
				<th class="textright">Precio Unitario</th>
				<th class="textright">Precio Total</th>
				<th>Accion</th>
			</tr>
			<tr>
				<td>
					<?php 
						
						$query_comedor = mysqli_query($conection,"SELECT * FROM tipo_comedor WHERE estatus = 1");

						$result_comedor = mysqli_num_rows($query_comedor);
					?>
					<select type="text" name="txt_comedor" id="txt_comedor">
						<option id="option_0" value="0">Seleccione una opción</option>
						<?php 
						if($result_comedor > 0){
						while($fila_comedor = mysqli_fetch_array($query_comedor)){
							
						?>
						<option value="<?php echo $fila_comedor["id"]; ?>"><?php echo $fila_comedor["comedor"]; ?></option>;

						<?php 
						}
						}
						 ?>
					</select>
				</td>
				<td><input type="hidden" name="action" value="addCliente">
					
					<select type="text" name="txt_producto" id="txt_producto">
					<option value="0">Seleccione una opción</option>
						
					</select>
				</td>
				<td><input type="date" id="txt_fecha" min="<?php echo date("Y-m-d"); ?>" value="<?php echo date("Y-m-d"); ?>" requiered></td>
				<td id="txt_existencia">-</td>
				<td><input type="number" step="1" name="txt_cant_producto" id="txt_cant_producto" value="0" min="1" disabled></td>
				<td id="txt_precio" class="textright">0.00</td>
				<td id="txt_precio_total" class="textright">0.00</td>
				<td><a href="#" id="add_product_detalle" class="link_add"><i class="fas fa-plus"></i> Agregar</a>
					<!-- <a href="#" id="no_hay_productos" style="display: none;" class="link_add">no hay prodctos</a></td> -->
				
			</tr>
			<tr> 
				<th >Comedor</th>
				<th colspan="2">Producto</th>
				<th>Fecha</th>
				<th>Cantidad</th>
				<th class="textright">Precio</th>
				<th class="textright">Precio Total</th>
				<th>Accion</th>
			</tr>
		</thead>
		<tbody id="detalle_venta">
			<!---contenido ajax--->

		</tbody>
		<tfoot id="detalle_totales">
			<!---ontenid ajax--->
		</tfoot>
	</table>
</div>
<div class="datos_venta">
		
		<h4 class="textcenter">Acciones</h4>
		
		<div class="datos">
			<!--<div class="wd50">
				<label>Vendedor</label>
				<p> <?php //echo $_SESSION["nombre"]; ?> <?php //echo $_SESSION["p_apellido"]; ?></p>
			</div>-->
			<div class="div_acciones" >
				<input type="hidden" name="id_cliente" id="id_cliente" value="<?= $_SESSION['idUser']; ?>" required>
				<input type="hidden" name="credito" id="credito" value="<?= $credito; ?>" required>
				<div id="acciones_venta">
					<a href="#" class="btn_ok textcenter" id="btn_anular_venta"><i class="fas fa-ban"></i> Anular</a>
					<a href="#" class="btn_new textcenter" id="btn_facturar_compra" style="display: none;"><i class="fas fa-shopping-cart"></i></i> Comprar</a>
				</div>
			</div>
		</div>
	</div>
</section>

<?php include "includes/footer2.php"; ?>
<script type="text/javascript">
	$(document).ready(function(){
  	var usuarioid = '<?php echo $_SESSION['idUser'];?>';
  	searchForDetalle(usuarioid);

  });

</script>

</body>
</html>