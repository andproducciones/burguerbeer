<?php  
session_start();
	if($_SESSION['rol'] != 1)
	{

		header("location: ./");
	}
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

		<h1><i class="fas fa-users"></i> Lista de Usuarios</h1>
		<button type="button" class="anadirForm btn_new" ac="formUsuario"><i class="fas fa-user-plus"></i> Crear Usuario</button>

		<table id="myTable">
			<thead>	
			<tr>
				<th>Cédula</th>
				<th>Nombre</th>
				<th>Lugar</th>
				<th>Tipo de Usuario</th>
				<th>Acciones</th>
			</tr>
			</thead>
			<tbody>
			<?php
	
			$query = mysqli_query($conection,"SELECT u.usuario, u.nombre, u.apellido, u.correo, r.rol,l.lugar FROM usuario u INNER JOIN rol r ON u.rol = r.idrol INNER JOIN lugar l ON u.lugar = l.id WHERE estatus = 1
				");
			mysqli_close($conection);
			$result = mysqli_num_rows($query);
			if($result > 0){

				while ($data= mysqli_fetch_array($query)){

			?>
			
			<tr>
				<td><?php echo $data["usuario"]?></td>
				<td><?php echo $data["nombre"]?> <?php echo $data["apellido"]?></td>
				<td><?php echo $data["lugar"]?></td>
				<td><?php echo $data["rol"]?></td>
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
