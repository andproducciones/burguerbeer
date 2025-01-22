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
		if(empty($_POST['nombre']) || empty($_POST['apellido']) || empty($_POST['correo']) || empty($_POST['rol']))
		{
			$alert='<p class="msg_error">Todos los campos son obligatorios.</p>';
		}else{
			
			$usuario	= $_POST['usuario'];
			$nombre 	= $_POST['nombre'];
			$apellido 	= $_POST['apellido'];
			$correo 	= $_POST['correo'];
			$clave 		= md5($_POST['clave']);
			$rol 		= $_POST['rol'];

			//********************************no se por que no funciona el codigo**********************************//
				//$query = mysqli_query($conection,"SELECT * FROM usuario WHERE (usuario != '$usuario')");
				
				//$result = mysqli_fetch_array($query);
				//$result=count($result);
				//if($result > 0){
					//$alert = '<p class="msg_error">El correo o el Usuario ya existe.</p>';
				//}else{
			//********************************se redirecciona directo a lista de usuario ya actualizado**********************************//
					if (empty($_POST['clave']))
					{
						$sql_update = mysqli_query($conection,"UPDATE usuario
															SET nombre='$nombre', apellido='$apellido', correo='$correo',
																rol='$rol' WHERE usuario= $usuario");

					}else{
						$sql_update = mysqli_query($conection,"UPDATE usuario
															SET nombre='$nombre', apellido='$apellido', correo='$correo', clave='$clave',
																rol='$rol' WHERE usuario= $usuario");
					}
					
					if($sql_update){
						$alert='<p class="msg_save">Usuario Actualizado correctamente</p>';
					}else{
						$alert='<p class="msg_error">Error al actulizar el usuario</p>';
					}
					}
					mysqli_close($conection);
					}


// Mostrar datos //
if(empty($_REQUEST['usuario']))
{
	header('Location: lista_usuarios.php');
}

$usuario = $_REQUEST['usuario'];
$sql=mysqli_query($conection,"SELECT u.usuario, u.nombre, u.apellido, u.correo, (u.rol) as idrol, (r.rol) as rol
								FROM usuario u
								INNER JOIN rol r 
								ON u.rol = r.idrol
								WHERE usuario= $usuario");

$result_sql = mysqli_num_rows($sql);

if ($result_sql == 0){
	header('Location: lista_usuarios.php');
}else{
	$option= '';
	while ( $data = mysqli_fetch_array($sql)) {
	
	$usuario	= $data['usuario'];
	$nombre 	= $data['nombre'];
	$apellido 	= $data['apellido']; 
	$correo 	= $data['correo']; 
	$idrol 		= $data['idrol'];
	$rol 		= $data['rol']; 


	if($idrol== 1) {
		$option	= '<option value="'.$idrol.'" select>'.$rol.'</option>';
	}else if ($idrol == 2) {
		$option	= '<option value="'.$idrol.'" select>'.$rol.'</option>';	
	}else if ($idrol == 3) {
		$option	= '<option value="'.$idrol.'" select>'.$rol.'</option>';
	}
	}
	}
		
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	
	<?php include "includes/scripts.php"; ?>
	
	<title>Actualizar Usuario</title>
</head>
<body>
	<?php include "includes/header.php"; ?>

	<section id="container"> 

		<div class="form_register">
			<h1><i class="far fa-edit"></i> Actualizar Usuario</h1>
			<hr>
			<div class="alert"><?php echo isset($alert) ? $alert : ''; ?></div>

			<form action="" method="post">
				<label for="cedula">Cédula</label>
				<p class="cedula"><?php echo $usuario; ?></p>
				<input type="hidden" name="usuario" id="usuario" placeholder="Cédula" value="<?php echo $usuario; ?>">
				<label for="nombre">Nombre</label>
				<input type="text" name="nombre" id="nombre" placeholder="Nombre" value="<?php echo $nombre; ?>">
				<label for="apellido">Apellido</label>
				<input type="text" name="apellido" id="apellido" placeholder="Apellido" value="<?php echo $apellido; ?>">
				<label for="correo">Correo</label>
				<input type="email" name="correo" id="correo" placeholder="Correo Electrónico" value="<?php echo $correo; ?>">
				<label for="clave">Contraseña</label>
				<input type="password" name="clave" id="clave" placeholder="Contraseña">
				<label for="rol">Tipo de Usuario</label>
				<?php 
					include "../conexion.php";
					$query_rol = mysqli_query($conection,"SELECT * FROM rol");
					mysqli_close($conection);
					$result_rol = mysqli_num_rows($query_rol);

				 ?>
				<select name="rol" id="rol" class="notItemOne">
					<?php
					echo $option;
					if($result_rol > 0)
					{

						while($rol=mysqli_fetch_array($query_rol)){
							?>
							<option value="<?php echo $rol["idrol"];?>"><?php echo $rol["rol"];?></option>
						<?php
						}
					}
						?>

				</select>
				
				<button type="submit" class="btn_save"><i class="far fa-edit"></i> Actualizar Usuario</button>

			</form>



		</div>

	</section>

<?php include "includes/footer.php"; ?>
</body>
</html>