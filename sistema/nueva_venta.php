<?php 
session_start();
include "../conexion.php";


if(empty($_SESSION['active']) OR empty($_SESSION['idUser'])){
	header('location: salir.php');
}

$id = $_SESSION['idUser'];

$query = mysqli_query($conection,"SELECT a.id_caja,c.lugar FROM arqueo_caja a INNER JOIN cajas c ON a.id_caja=c.id WHERE a.id_usuario = $id AND a.estatus = 1 ");
$result = mysqli_num_rows($query);

//print_r($query);

$data = mysqli_fetch_assoc($query);
$caja 	= $data['id_caja'];
$lugar 	= $data['lugar'];

//print_r($data);

if($result != 1){
	header('location: ../');
	}


?>



<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<?php include "includes/scripts.php"; ?>
	<style>nav{ display:none} #container{padding: 50px 15px 15px;}</style>
	<title></title>
</head>
<body>
	<?php include "includes/header.php"; ?>
	<section id="container" class="containerVentas">
		<div class="divVentas">
			<div class="gridVentas">



				<div class="ventasItems cliente">
					<div class="">
						<div class="action_cliente cliente2">
							<h2>Datos del Cliente</h2>
							
						</div>

						<form name="form_new_cliente" id="form_new_cliente_venta" class="datos2">
							<input type="hidden" name="action" value="addCliente">
							<input type="hidden" name="id_cliente" id="id_cliente" value="" required>
							<input type="hidden" name="id_mesa" id="id_mesa" value="" required>
							<input type="hidden" name="id_precioFinal" id="id_precioFinal" value="" required>
							<input type="hidden" name="id_caja" id="id_caja" value="<?php echo $caja;?>" required>


							<div class="wd25 mesaResponsive margin">
								<select class="js-example-basic-single notItemOne" name="cl_usuario" id="cl_usuario" >
									<option value="">Seleccione</option>
									<?php echo buscarCliente(); ?>
								</select>
							</div>
							<div class="wd25 nombreResponsive margin">
								
								<input type="text" name="ap_cliente" id="direccion" placeholder="Dirección" disabled required>
							</div>
							<div class="wd10 nombreResponsive margin">
								
								<input type="text" name="ap_cliente" id="telefono" placeholder="Telefono" disabled required>
							</div>
							<div class="wd25 mesaResponsive margin">
								
								<input type="text" name="ap_cliente" id="correo" placeholder="Correo" disabled required>
							</div>
							<div class="wd10 mesaResponsive margin">
								<select class="notItemOne" name="mesa" id="mesa" onchange="searchForDetalle('<?php echo $_SESSION['idUser'];?>');">
									<option value="">Mesa</option>
									<?php
										$query = mysqli_query($conection,"SELECT * FROM mesas WHERE estatus = 1");
										$result = mysqli_num_rows($query);
										$data = '';

										if($result > 0){
											while($data = mysqli_fetch_assoc($query)){

												$mesaId = $data['id'];

												// Consulta para verificar si la mesa tiene productos en detalle_temp
												$queryProductos = mysqli_query($conection, "
													SELECT COUNT(*) as total_productos 
													FROM detalle_temp 
													WHERE mesa = $mesaId
												");
												$productos = mysqli_fetch_assoc($queryProductos);
										
												// Determinar si la mesa tiene productos
												$tieneProductos = ($productos['total_productos'] > 0) ? true : false;
												
												?>
												
												<option value="<?= $data['id']; ?>"><?= $data['numero']; ?>
												<?php if ($tieneProductos): ?>
													<span style="color: red; font-size: 14px;">●</span>
												<?php endif; ?>
											</option>
												
											<?php }} ?>
								</select>
							</div>
						</form>
					</div>
				</div>

				<h4 style="text-align:center; display: none;" class="tituloBlock">Categorías</h4>

				<div class="ventasItems nav sombras">
					
					<h4 class="tituloResponsive">Categorías</h4>
					<input type="text" placeholder="Buscar" id="buscarCategoriasGrid">
					

					<div class="gridCategorias flexCategorias">

					<?php
						$query = mysqli_query($conection,"SELECT * FROM categorias WHERE estatus = 1");
						$result = mysqli_num_rows($query);
						$data = '';

						if($result > 0){

							?>

								<div class="producto btnCategoria">
									<button type="button" class="btn1 btnCategoria"  onclick="todasCategorias();">
										<img src="img/productos/hamburguesa.jpg">
										<p>Todos</p>
									</button>
								</div>
								<?php
							while($data = mysqli_fetch_assoc($query)){
										
								?>

								<div class="producto btnCategoria categoriaG">
									<button type="button" class="btn1 btnCategoria"  onclick="selectCategorias(<?= $data['id']; ?>);">
										<img src="img/productos/<?php if (!empty($data['foto'])) {
											echo $data['foto'];
										}else{
											echo 'hamburguesa.jpg';
										} ?>">
										<p><?= strtoupper($data['categoria']); ?></p>
									</button>
								</div>
							<?php }
						} ?>
				
				</div>
				</div>

				<h4 style="text-align:center; display: none;" class="tituloBlock">Productos</h4>

				<div class="ventasItems productos sombras">
						<h4 class="tituloResponsive">Productos</h4>
						<input type="text" placeholder="Buscar" id="buscarProductosGrid">
					<div class="gridProductos categoriaProd">
						
						<?php
						$query = mysqli_query($conection,"SELECT * FROM producto WHERE estatus = 1");
						$result = mysqli_num_rows($query);
						$data = '';

						if($result > 0){
							while($data = mysqli_fetch_assoc($query)){;
								?>
								<div class="producto productoG">
									<button type="button" class="btn1 "  onclick="addproduct(<?= $data['codproducto']; ?>);">
										<img src="img/productos/<?= $data['foto']; ?>">
										<p><?= $data['producto']; ?></p>
									</button>
								</div>
							<?php }} ?>
						</div>
					</div>

					<div class="ventasItems tablaCodigos sombras">
						<table class="tbl_venta">
							<thead>
								<tr>
									<th width="100px">ID</th>
									<th>Producto</th>	  						
									<th width="100px">#</th>
									<th class="textright">Precio</th>
									<th class="textright">Total</th>
									<th>Agregar</th>
								</tr>
								<tr>
									<td><input type="text" name="txt_cod_producto" id="txt_cod_producto"></td>
									<td colspan="1" id="txt_producto">-</td>
									<td style="display: flex; align-items: center;
									justify-content: center;"><input type="text" name="txt_cant_producto" id="txt_cant_producto"  class="wd50" value="0" min="1" disabled ></td>
									<td id="txt_precio" class="textright">$ 0.00</td>
									<td id="txt_precio_total" class="textright">$ 0.00</td>
									<td><a href="#" id="add_product_venta" class="link_add"><i class="fas fa-plus"></i> Agregar</a></td>

								</tr>
							</table>
						</div>

						<div class="ventasItems tablaProductos sombras">
							<table class="tbl_venta tablaventacelular">
								<thead>

									<tr>
										<th>#</th>
										<th colspan="2">Producto</th>
										<th class="">Precio</th>
										<th class="">Total</th>
										<th>Acciones</th>
									</tr>
								</thead>
								<tbody id="detalle_venta">
									<!---contenido ajax--->

								</tbody>

							</table>
						</div>

						<div class="ventasItems total sombras">
							<table id="detalle_totales">
								<!---ontenid ajax--->
							</table>
						</div>


						<div class="ventasItems footer sombras">
							<div class="">
								<h4>Acciones</h4>
									<div class="gridBotones" id="accionesVenta">
										
										<button class="textcenter boton btn_new_cliente anadirForm" ac="formCliente" style="display: block;"><i class="fas fa-plus fa-2x"></i><br><br> Nuevo Cliente</button>
										<button class="textcenter boton btn_new_cliente anadirForm" ac="formSalidaDinero" style="display: block;"><i class="fas fa-plus fa-2x"></i><br><br> Entrada / Salida Dinero</button>
										<button class="textcenter boton rojo" id="btn_anular_venta" style="display: none;"><i class="fas fa-ban fa-2x"></i><br><br> Anular</button>
										<button class="textcenter boton anadirForm verde" id="btn_facturar_venta_1"  ac="facturarVenta" style="display: none;"><i class="fas fa-cash-register fa-2x"></i><br><br> Facturar</button>
										<button class="textcenter boton anadirForm amarillo imprimir_todo" style="display: none;" ac="formClienteComanda"><i class="fas fa-print fa-2x"></i><br><br> Preparar Orden</button>
										<button class="textcenter boton anadirForm amarillo imprimir_todo" style="display: none;" ac="formClientePre"><i class="fas fa-print fa-2x"></i><br><br> Pre-Cuenta</button>
																			
									</div>
							</div>
						</div>

						<div class="ventasItems vendedor sombras">
							<div class="">
								<h4>Vendedor</h4>
										<p><?php echo $_SESSION["nombre"]; ?> <?php echo $_SESSION["apellido"]; ?></p>
							</div>
							<div class="">
								<h4>Caja</h4>
										<p><?php echo 'Caja # '.$caja.' de '.$lugar; ?></p>
							</div>
						</div>

					</div>
				</div>
				
				</section>

				<?php include "includes/footer.php"; ?>

			</body>
			</html>

