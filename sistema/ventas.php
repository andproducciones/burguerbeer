<?php  
session_start();

include '../conexion.php';

?>


<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Lista de Ventas</title>
</head>
<body>
	<?php include "includes/header.php"; ?>

	<section id="container"> 

		<h1><i class="fas fa-newspaper"></i> Lista de Ventas</h1>
		<?php
					if($_SESSION['rol'] == 1){
 					?>
		<a href="nueva_venta.php" class="btn_new"> <i class="fas fa-plus"></i> Nueva Venta</a>
		<?php } ?>

		<table id="myTable">
			<thead>
			<tr>
				<th class="wd5">No.</th>
				<th>Fecha / Hora</th>
				<th>Cliente</th>
				<th>Vendedor</th>
				<th>Estado</th>
				<th class="textright">Total Factura</th>
				<th class="textright wd10">Accciones</th>
			</tr>
			</thead>
			<?php
			
			$query = mysqli_query($conection,"SELECT f.nofactura,f.fecha,f.totalfactura,f.codcliente,f.estatus, u.nombre as vendedorn, u.apellido as vendedora, cl.nombre as clienten,
				cl.p_apellido as clientea1
				 
				FROM factura f 
				INNER JOIN usuario u 
				ON f.usuario = u.usuario 
				INNER JOIN clientes cl 
				ON f.codcliente = cl.usuario 
				WHERE f.estatus !=10 
				ORDER BY f.fecha DESC ");			
			
			mysqli_close($conection);
			$result = mysqli_num_rows($query);
			if($result > 0){

				while ($data= mysqli_fetch_array($query)){
					if($data ["estatus"]== 1){
							$estado = '<span class="pagada">Pagada</span>';

					}else if($data ["estatus"]== 2){
							
							$estado = '<span class="anulada">Anulada</span>';
					
					}else{
							$estado = '<span class="pagada inactive">Cerrada</span>';
					}

			?>
			
			<tr id="row_<?php echo $data["nofactura"];?>">
				<td><?php echo $data["nofactura"];?></td>
				<td><?php echo $data["fecha"];?></td>
				<td><?php echo $data["clienten"];?> <?php echo $data["clientea1"];?> </td>
				<td><?php echo $data["vendedorn"];?> <?php echo $data["vendedora"];?></td>
				<td><?php echo $estado?> </td>
				<td class="textright totalfactura"><span>$ </span><?php echo $data["totalfactura"];?></td>
				<td >
					<div class="div_acciones">
						<div>
							<button class="btn_view view_factura" type="button" cl="<?php echo $data["codcliente"];?>" f="<?php echo $data["nofactura"];?>"><i class="fas fa-eye"></i></button>
						</div>
						
					
					
					<?php
					if($_SESSION['rol'] == 1 || $_SESSION['rol'] == 2){
						if($data['estatus'] == 1 OR $data['estatus'] == 2 )
						{
						?>
					<div class="div_factura">
						
							<button class="btn_anular anular_factura" fac="<?php echo $data["nofactura"];?>"><i class="fas fa-ban"></i></button>
						
						</div>
					<?php }else{ ?>
						<div class="div_factura">
							<button type="button" class="btn_anular inactive" ><i class="fas fa-ban"></i></button>
						</div>
						
					
					<?php }
					} ?>
				</div>
				</td>
			</tr>
			<?php 
				}
			}

			?>




		</table>
	
	</section>

<?php include "includes/footer.php"; ?>
</body>
</html>
