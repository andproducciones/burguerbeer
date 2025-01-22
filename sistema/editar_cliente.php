<?php
	session_start();

	include "../conexion.php";


	if(!empty($_POST))
	{
		$alert='';
		if(empty($_POST['nombre']) || empty($_POST['p_apellido']) || empty($_POST['s_apellido']) || empty($_POST['correo']) || empty($_POST['direccion']) || empty($_POST['telefono']) || empty($_POST['tipo_user']))
		{
			$alert='<p class="msg_error">Todos los campos son obligatorios.</p>';
		}else{
			
			$usuario	= $_POST['usuario'];
			$nombre 	= $_POST['nombre'];
			$p_apellido = $_POST['p_apellido'];
			$s_apellido = $_POST['s_apellido'];
			$correo 	= $_POST['correo'];
			$direccion 	= $_POST['direccion'];
			$telefono 	= $_POST['telefono'];
			$clave 		= md5($_POST['clave']);
			$tipo_user1	= $_POST['tipo_user'];

					if (empty($_POST['clave']))
					{
						$sql_update = mysqli_query($conection,"UPDATE cliente
															SET nombre ='$nombre', p_apellido ='$p_apellido', s_apellido = '$s_apellido', correo_c ='$correo', direccion = '$direccion', telefono = '$telefono', tipo_user='$tipo_user1'
																WHERE usuario_c = $usuario");

					}else{
						$sql_update = mysqli_query($conection,"UPDATE cliente
															SET nombre='$nombre', p_apellido='$p_apellido', s_apellido='$s_apellido', correo_c='$correo', direccion='$direccion', telefono='$telefono', clave='$clave', tipo_user='$tipo_user1'
																WHERE usuario_c = $usuario");
					}
					
					if($sql_update){
						$alert='<p class="msg_save">Usuario Actualizado correctamente</p>';
					}else{
						$alert='<p class="msg_error">Error al actulizar el usuario</p>';
					}
					}
					mysqli_close($conection);
					}


// ********************************* Mostrar datos ************************ //
if(empty($_REQUEST['usuario_c']))
{
	header('Location: lista_clientes.php');
}

$usuario = $_REQUEST['usuario_c'];
$sql = mysqli_query($conection,"SELECT c.usuario_c, c.nombre, c.p_apellido, c.s_apellido, c.correo_c, c.direccion, c.telefono, (c.tipo_user) as id_tipouser, (t.tipo_user) as tipo_user FROM cliente c INNER JOIN tipo_usuario t ON c.tipo_user = t.id_tipouser WHERE usuario_c = $usuario");

$result_sql = mysqli_num_rows($sql);

if ($result_sql == 0){


	header('Location: lista_clientes.php');
}else{
			$option= '';
			while ( $data = mysqli_fetch_array($sql)) {
				
			$usuario_c	= $data['usuario_c'];
			$nombre 	= $data['nombre'];
			$p_apellido = $data['p_apellido'];
			$s_apellido = $data['s_apellido'];
			$correo 	= $data['correo_c'];
			$direccion 	= $data['direccion'];
			$telefono 	= $data['telefono'];
			$id_tipouser= $data['id_tipouser']; 
			$tipo_user 	= $data['tipo_user']; 
	
			if($id_tipouser== 1) {
			$option	= '<option value="'.$id_tipouser.'" select>'.$tipo_user.'</option>';
		}		else if ($id_tipouser == 2) 
		{
			$option	= '<option value="'.$id_tipouser.'" select>'.$tipo_user.'</option>';	
		}		else if ($id_tipouser == 3) 
		{
			$option	= '<option value="'.$id_tipouser.'" select>'.$tipo_user.'</option>';
		}
	}
	}
//*****************************Mostar Datos********************************//
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	
	<?php include "includes/scripts.php"; ?>
	
	<title>Actualizar Cliente</title>
</head>
<body>
	<?php include "includes/header.php"; ?>

	<section id="container"> 

		<div class="form_register">
			<h1> Actualizar Cliente</h1>
			<hr>
			<div class="alert"><?php echo isset($alert) ? $alert : ''; ?></div>

			<form action="" method="post">
				<label for="cedula">Cédula</label>
				<p class="cedula"><?php echo $usuario; ?></p>
				<input type="hidden" name="usuario" id="usuario" value="<?php echo $usuario; ?>">
				<label for="nombre">Nombre</label>
				<input type="text" name="nombre" id="nombre" placeholder="Nombre" value="<?php echo $nombre; ?>">
				<label for="apellido">Primer Apellido</label>
				<input type="text" name="p_apellido" id="p_apellido" placeholder="Apellido" value="<?php echo $p_apellido; ?>">
				<label for="apellido">Segundo Apellido</label>
				<input type="text" name="s_apellido" id="s_apellido" placeholder="Apellido" value="<?php echo $s_apellido; ?>">
				<label for="apellido">Direcció</label>
				<input type="text" name="direccion" id="direccion" placeholder="Apellido" value="<?php echo $direccion; ?>">
				<label for="apellido">Teléfono</label>
				<input type="text" name="telefono" id="telefono" placeholder="Apellido" value="<?php echo $telefono; ?>">
				<label for="correo">Correo</label>
				<input type="email" name="correo" id="correo" placeholder="Correo Electrónico" value="<?php echo $correo; ?>">
				<?php if($_SESSION["rol"] == 1){?>
				<label for="clave">Contraseña</label>
				<input type="password" name="clave" id="clave" placeholder="Contraseña">
				<?php } ?>
				<label for="tipo_user">Tipo de Usuario</label>
				<?php 
					include "../conexion.php";
					$query_rol = mysqli_query($conection,"SELECT * FROM tipo_usuario");
					mysqli_close($conection);
					$result_rol = mysqli_num_rows($query_rol);

				 ?>
				<select name="tipo_user" id="tipo_user" class="notItemOne">
					<?php
					echo $option;
					if($result_rol > 0)
					{

						while($rol=mysqli_fetch_array($query_rol)){
							?>
							<option value="<?php echo $rol["id_tipouser"];?>"><?php echo $rol["tipo_user"];?></option>
						<?php
						}
					}
						?>

				</select>
				<input type="submit" value="Actualizar Usuario" class="btn_save">

			</form>



		</div>

	</section>

<?php include "includes/footer.php"; ?>
</body>
</html>