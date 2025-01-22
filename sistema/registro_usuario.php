<?php
	session_start();
	if($_SESSION['rol'] != 1)
	{

		header("location: ./");
	}
	include "../conexion.php";

	if(!empty($_POST))
	{
		$alert='';
		if(empty($_POST['usuario']) || empty($_POST['nombre']) || empty($_POST['apellido']) || empty($_POST['correo']) || empty($_POST['clave']) || empty($_POST['rol']))
		{
			$alert='<p class="msg_error">Todos los campos son obligatorios.</p>';
		}else{
			
			$usuario 	= $_POST['usuario'];
			$nombre 	= $_POST['nombre'];
			$apellido 	= $_POST['apellido'];
			$correo 	= $_POST['correo'];
			$clave 		= md5($_POST['clave']);
			$rol 		= $_POST['rol'];

				$query = mysqli_query($conection,"SELECT * FROM usuario WHERE usuario = '$usuario' OR correo = '$correo'");
				$result = mysqli_fetch_array($query);

				if($result > 0){
					$alert = '<p class="msg_error">El correo o el Usuario ya existe.</p>';
				}else{

					$query_insert = mysqli_query($conection,"INSERT INTO usuario(usuario,nombre,apellido,correo,clave,rol) VALUES('$usuario','$nombre','$apellido','$correo','$clave','$rol')");

					if($query_insert){
						$alert='<p class="msg_save">Usuario creado correctamente.</p>';
					}else{
						$alert='<p class="msg_error">Error al crear el usuario</p>';

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
	
	<title>Registro Usuario</title>
</head>
<body>
	<?php include "includes/header.php"; ?>

	<section id="container"> 

		<div class="form_register">
			<h1><i class="fas fa-user-plus"></i> Registro Usuario</h1>
			<hr>
			<div class="alert"><?php echo isset($alert) ? $alert : ''; ?></div>

			<form action="" method="post">
				<label for="usuario">Cédula</label>
				<input type="number" name="usuario" id="usuario" placeholder="Cédula">
				<label for="nombre">Nombre</label>
				<input type="text" name="nombre" id="nombre" placeholder="Nombre">
				<label for="apellido">Apellido</label>
				<input type="text" name="apellido" id="apellido" placeholder="Apellido">
				<label for="correo">Correo</label>
				<input type="email" name="correo" id="correo" placeholder="Correo Electrónico">
				<label for="clave">Contraseña</label>
				<input type="password" name="clave" id="clave" placeholder="Contraseña">
				<label for="rol">Tipo de Usuario</label>
				<select name="rol" id="rol">
					<option value="1">Administrador</option>
					<option value="2">Vendedor</option>

					<?php mysqli_close($conection); ?>
				</select>
				<button type="submit" class="btn_save"><i class="far fa-save"></i> Crear Usuario</button>

			</form>



		</div>

	</section>

<?php include "includes/footer.php"; ?>
</body>
</html>