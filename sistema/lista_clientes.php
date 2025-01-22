<?php  
session_start();
if(($_SESSION['rol']) != 3)
{
	


include '../conexion.php';

?>


<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Lista de Usuarios</title>
</head>
<body>
	<?php include "includes/header.php"; ?>

	<section id="container"> 

		<h1><i class="fas fa-user-friends"></i> Lista de Clientes</h1>
		<?php
					if($_SESSION['rol'] == 1){
 					?>
	<button type="button" class="anadirForm btn_new" ac="formCliente"><i class="fas fa-user-plus"></i> Crear Cliente</button>
		<?php } ?>
		<table id="myTable">
			<thead>
			<tr>
				<th>Cédula</th>
				<th>Nombre</th>
				<th>Correo</th>
				<th>Teléfono</th>
				<th>Dirección</th>
				<th>Acciones</th>
			</tr>

			</thead>
			<?php
			

			$query = mysqli_query($conection,"SELECT usuario, nombre, p_apellido,correo_c, direccion, telefono FROM clientes");
			//mysqli_close($conection);
			$result = mysqli_num_rows($query);
			if($result > 0){

				while ($data= mysqli_fetch_assoc($query)){

			?>
			<tbody>
			<tr>
				<td><?php echo $data["usuario"]?></td>
				<td><?php echo $data["nombre"]?> <?php echo $data["p_apellido"]?> </td>
				<td><?php echo $data["correo_c"]?></td>
				<td><?php echo $data["telefono"]?></td>
				<td><?php echo $data["direccion"]?></td>

				<td align="center">
					<button class="btn_view anadirForm" value="<?php echo $data["usuario"]; ?>"><i class="far fa-edit"></i></button>
				
					<button class="btn_anular formElminar" value="<?php echo $data["usuario"]; ?>"><i class="far fa-trash-alt"></i></button>
			
				
				</td>
			</tr>
			<?php 
				}
			}

			?>
		</tbody>



		</table>

	</section>

<?php include "includes/footer.php"; ?>
</body>
</html>
<?php
}else{


header('location: ../index2.php');
session_destroy();
echo 'acceso restrigido';
}
?>