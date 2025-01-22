<?php  
session_start();

include '../conexion.php';

?>


<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Lista de Productos</title>
</head>
<body>
	<?php include "includes/header.php"; ?>

	<section id="container"> 

		<h1>Lista de Productos</h1>
		<?php
					if($_SESSION['rol'] == 1){
 					?>
		<button type="button" class="btn_new anadirForm" ac="formCategoria"><i class="fas fa-plus"></i> Crear Producto</button>
		<?php } ?>

		<table id="myTable">
			<thead>
			<tr>
				<th class="wd5">Código</th>
				<th>Categoria</th>
				<th>Estado</th>
	
				<?php if($_SESSION["rol"] == 1){?>
				<th class="wd10">Acciones</th>
				<?php } ?>
			</tr>
			</thead>
			<?php
		
			
			$query = mysqli_query($conection,"SELECT * FROM categorias
				");
			mysqli_close($conection);
			
			$result = mysqli_num_rows($query);
			
			if($result > 0){

				while ($data= mysqli_fetch_array($query)){

					if($data ["estatus"]== 1){
							$estado = '<span class="pagada">Activo</span>';

					}else{
							
							$estado = '<span class="anulada">Desctivada</span>';
					
					}

			?>
			
			<tr class="row<?php echo $data["id"]?>">
				<td><?php echo $data["id"]?></td>
				<td><?php echo $data["categoria"]?></td>
				<td><?php echo $estado;?></td>				
				<td align="center">
					<button class="btn_view anadirForm" value="<?php echo $data["id"]; ?>"><i class="far fa-edit"></i></button>
				
					<button class="btn_anular formElminar" value="<?php echo $data["id"]; ?>"><i class="far fa-trash-alt"></i></button>
				
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
