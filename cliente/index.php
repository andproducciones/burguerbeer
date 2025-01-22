<?php  
session_start();

include '../conexion.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts2.php"; ?>
	<title>VENTA DE ALIMENTOS</title>
</head>
<body>

	<?php 
	include "includes/header2.php"; 
	//print_r ($data_dash);
	?>

	<section id="container"> 
		<div class="divContainer">
			<div class="dashboard">
					<a href="nueva_venta.php">
					<i class="fas fa-shopping-cart fa-2x"></i>
					<p>
						<strong>Realizar</strong><br>
						<span>Compra</span>
					</p>
				</a>
			<a href="index.php">
				<i class="fas fa-dollar-sign fa-2x"></i>
				<p>
					<strong>Saldo Cuenta</strong><br>
					<span><?php 

						$codcliente = $_SESSION['idUser'];

						$query = mysqli_query($conection, "SELECT credito FROM cliente WHERE usuario_c = $codcliente");

						$data = mysqli_fetch_array($query);

						?>$ <?php echo $data["credito"];?></span>
				</p>
			</a>
			
		</div>	
	</div>



<div class="divreservaHoy">
		
			<h4>RANCHO RESERVADO PARA HOY</h4>
	</div>
		<div class="containerTable">
			<table>
				<tr>
				<th>Producto</th>
				<th>Cant.</th>
				<th>Estado</th>
				<th>Revisar Ticket</th>
				</tr>
			
			<?php


			$idCliente = $_SESSION['idUser'];

			$query = mysqli_query($conection,"SELECT f.nofactura,f.fecha,p.producto,dt.correlativo,dt.cantidad,dt.estatus_dt,(dt.cantidad * dt.precio_venta) as precio_total FROM factura f INNER JOIN detalle_factura dt ON f.nofactura = dt.nofactura INNER JOIN producto p ON dt.codproducto = p.codproducto WHERE f.codcliente = $idCliente AND dt.fecha = CURDATE() ORDER BY fecha DESC");			
			
			mysqli_close($conection);
			
			$result = mysqli_num_rows($query);

				//print_r($result);exit;

			if($result > 0){

				//$data = mysqli_fetch_array($query);
				
				while ($data= mysqli_fetch_array($query)){
					//print_r($data);exit;
					if($data["estatus_dt"]== 1){
							$estado = '<span class="pagada">Reservado</span>';

					}else if($data["estatus_dt"] == 2){
							$estado = '<span class="anulada">Consumido</span>';
					}else{

							$estado = '<span class="eliminado">anulado</span>';
					}

				?>
					



				<tr id="row_<?php echo $data["nofactura"];?>">
				<td style="text-align:center"><?php echo $data["producto"];?></td>
				<td style="text-align:center"><?php echo $data["cantidad"];?></td>
				<td style="text-align:center"><?php echo $estado?></td>
				<td >
					<div class="div_acciones">
						<div>
							<button class="btn_view view_ticket" type="button" co="<?php echo $data["correlativo"];?>"><i class="fas fa-eye"></i></button>
							<button class="btn_view view_ticketcel1" type="button" co="<?php echo $data["correlativo"];?>"><i class="fas fa-qrcode"></i></button>
							</div>
						
					</div>
					
				</td>
				</tr>
				<?php 
				

			//}else{
				
					//Echo "no a realizado compras";
			
			}
			}else{
				
					Echo '<div style="text-align:center">
					<span>NO SE HA RESERVADO RANCHO</span><br>
					<br>
					</div>';
			
			}

			?>

			</table>
			</div>


	<div class="divMenu">
		
			<h4>Menu del Día</h4>
	</div>

		<div class="containerTable">
			<table>
				<?php 
				include '../conexion.php';

				$query_menu_o = mysqli_query($conection,"SELECT id,desayuno,almuerzo,merienda FROM menu_oficiales WHERE fecha = CURDATE()");

			
			
				$result = mysqli_num_rows($query_menu_o);
			
			
					if($result > 0){
						
						$dataMenuO = mysqli_fetch_array($query_menu_o);
						
						$desayunoO = $dataMenuO["desayuno"];
						$almuerzoO = $dataMenuO["almuerzo"];
						$meriendaO = $dataMenuO["merienda"];

						mysqli_close($conection);
			

 				?>				
 				
 				<tr>
				
				<th>Desayuno</th>
				<th>Almuerzo</th>
				<th>Merienda</th>
				<th>Postre*</th>
				
				</tr>

 				<tr>
					<td style="text-align:center"><?php echo $desayunoO;?></td>
					<td style="text-align:center"><?php echo $almuerzoO;?></td>
					<td style="text-align:center"><?php echo $meriendaO;?> </td>
					
				</tr>
				<tr>
					<td colspan="4" style="text-align:right;">*Solo en Comedor Halcones</td>
				</tr>
				
				<?php 
				}else{
				echo '<div style="text-align:center;">
					<span>NO SE DISPONE DE MENU POR EL MOMENTO</span><br>
					<br>
					</div>';
				}

				?>

			</table>
			</div>
		



	<div class="divInfoSistema">
		<div>
			<h1 class="titlePanelControl">Configuración</h1>
		</div>
		
		<div class="containerPerfil">
			<div class="containerDataUser">
				<div class="divDataUser">
					
					<img src="img/logo_user.png" class="logoUser">
					<h4>Informacion Personal</h4>
				
					<div>
						<label>Nombre:</label> <span><?= $_SESSION['nombre'];?> <?= $_SESSION['p_apellido'];?> <?= $_SESSION['s_apellido'];?></span>
					</div>
					<div>
						<label>Correo:</label> <span><?= $_SESSION['correo_c'];?></span>
					</div>
					<div>
						<label>Dirección:</label> <span><?= $_SESSION['direccion'];?></span>
					</div>
					<div>
						<label>Teléfono:</label> 0<span><?= $_SESSION['telefono'];?></span>
					</div>
					<div>
						<label>Usuario:</label> <span><?= $_SESSION['idUser'];?></span>
					</div>
								
					<h4>Cambiar contraseña</h4>
					<form action="" method="post"  name="frmChangePass" id="frmChangePass">
						<div>
							<input type="password" name="txtPassUser" id="txtPassUser" placeholder="Contraseña Actual" required>
						</div>
						<div>
							<input class="newPass" type="password" name="txtNewPassUser" id="txtNewPassUser" placeholder="Nueva Contraseña" required>
						</div>
						<div>
							<input class="newPass" type="password" name="txtPassConfirm" id="txtPassConfirm" placeholder="Confirmar COntraseña" required>
						</div>
						<div class="alertChangePass" style="display: none;">
							
						</div>

						<div>
							<button type="submit" class="btn_save btnChangePass"><i class="fas fa-key"></i> Cambiar Contraseña</button>
						</div>						

					</form>

				</div>
						
		</div>
	</div>
	</section>

<?php include "includes/footer2.php"; ?>
</body>
</html>
<!--<script type="text/javascript">
	
	document.querySelectorAll(".modal-container img").forEach(el=>{el.addEventListener("click",function(ev){
	ev.stopPropagation();
		this.parentNode.classList.add("active");
})
});
	document.querySelectorAll(".modal-container").forEach(el=>{el.addEventListener("click",function(ev){this.classList.remove("active");
		console.log("Click");
})
})
</script>  -->