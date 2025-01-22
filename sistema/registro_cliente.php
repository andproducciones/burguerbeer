<?php
	session_start();
	if($_SESSION['rol'] != 1)
	{

		header("location: ./");
	}

	//dejo el rol de administrador para que el solo puede crear usuarios desde el administrador
	include "../conexion.php";

	if(!empty($_POST))
	{
		$alert='';
		if(empty($_POST['usuario_c']) || empty($_POST['nombre']) || empty($_POST['p_apellido']) || empty($_POST['correo_c']) || empty($_POST['direccion']) || empty($_POST['telefono']))
		{
			$alert='<p class="msg_error">Todos los campos son obligatorios.</p>';
		}else{
			
			

			$usuario 		= $_POST['usuario_c'];
			$nombre 		= $_POST['nombre'];
			$p_apellido 	= $_POST['p_apellido'];
			$correo_c 		= $_POST['correo_c'];
			$direccion 		= $_POST['direccion'];
			$telefono 		= $_POST['telefono'];


				$query = mysqli_query($conection,"SELECT * FROM clientes WHERE usuario = '$usuario'");
				
				$result = mysqli_fetch_array($query);

				
				if($result > 0){
					$alert = '<p class="msg_error">El Cliente ya existe.</p>';
				}else{

					$query_insert = mysqli_query($conection,"INSERT INTO clientes(usuario,nombre,p_apellido,correo_c,direccion,telefono) VALUES('$usuario','$nombre','$p_apellido','$correo_c','$direccion','$telefono')");
					
					if($query_insert){
						$alert='<p class="msg_save">Cliente agregado correctamente.</p>';
					}else{
						$alert='<p class="msg_error">Error al crear añadir cliente</p>';

					}
				}

 
		}

	}

/****no le puse que el rol sea Dinamico por el cliente proximo a creaar en otra tabla*****/

?>




<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	
	<?php include "includes/scripts.php"; ?>
	
	<title>Registro Cliente</title>
</head>
<body>
	<?php include "includes/header.php"; ?>

	<section id="container"> 

		<div class="form_register">
			<h1> Registro Cliente</h1>
			<hr>
			<div class="alert"><?php echo isset($alert) ? $alert : ''; ?></div>

			<form action="" method="post">
				<label for="usuario_c">Cédula</label>
				<input type="text" name="usuario_c" id="usuario_c" placeholder="Cédula">
				<label for="nombre">Nombre</label>
				<input type="text" name="nombre" id="nombre" placeholder="Nombre">
				<label for="p_apellidos">Apellidos</label>
				<input type="text" name="p_apellido" id="p_apellido" placeholder="Primer Apellido">
				<label for="correo_c">Correo</label>
				<input type="email" name="correo_c" id="correo_c" placeholder="Correo Electrónico">
				<label for="direccion">Dirección</label>
				<input type="text" name="direccion" id="direccion" placeholder="Dirección">
				<label for="telefono">Teléfono</label>
				<input type="text" name="telefono" id="telefono" placeholder="Teléfono">
				<input type="submit" value="Crear Usuario" class="btn_save">

			</form>



		</div>

	</section>

<?php include "includes/footer.php"; ?>
</body>
</html>