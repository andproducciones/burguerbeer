<?php  
session_start();

include '../conexion.php';

?>


<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Cajas</title>
</head>
<body>
	<?php include "includes/header.php"; ?>

	<section id="container"> 

		<h1><i class="fas fa-cash-register"></i> Cajas</h1>
		<?php
					if($_SESSION['rol'] == 1){
 					?>
		<button type="button" class="btn_view anadirForm" ac="formCaja"><i class="fas fa-plus"></i> Crear Caja</button>
		<?php } ?>

		<button type="button" class="btn_view anadirForm" ac="formAbrirCaja"><i class="fas fa-lock-open"></i> Abrir Caja</button>



		<table id="myTable">
			<thead>
			<tr>
				<th style="text-align: center; width: 5%;"># Caja</th>
				<th>Lugar</th>
				<th style="text-align: center; width: 15%;">Estado</th>
	
				<?php if($_SESSION["rol"] == 1){?>
				<th style="text-align: center; width: 15%;" >Acciones</th>
				<?php } ?>
			</tr>
			</thead>
			<?php
		
			
			$query = mysqli_query($conection,"SELECT * FROM cajas ");
			$result = mysqli_num_rows($query);
			
			if($result > 0){

				while ($data= mysqli_fetch_assoc($query)){

					$id = $data['id'];
					$query_2 = mysqli_query($conection,"SELECT id FROM arqueo_caja WHERE id_caja = $id AND estatus = 1");
																
								$result_2 = mysqli_num_rows($query_2);

					if($result_2 == 1){
							$estado = '<span class="pagada">Abierto</span>';

					}else{
							$estado = '<span class="anulada">Cerrado</span>';
					}

			?>
			
			<tr class="row<?php echo $data["id"]?>">
				<td style="text-align: center; width: 5%;"><?php echo $data["id"]?></td>
				<td><?php echo $data["lugar"]?></td>
				<td align="center"><?php echo $estado ?></td>				
				<td align="center">
					<button class="btn_view anadirForm" co="<?php echo $data["id"];?>"  ac="arqueoCajas"><i class="fas fa-eye"></i></button>
					
				
					<button class="btn_anular anadirForm" value="<?php echo $data["id"];?> " ac="eliminarCaja"><i class="far fa-trash-alt"></i></button>
				
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
