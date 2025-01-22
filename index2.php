<?php

$alert='';
session_start();
if(!empty($_SESSION['active']))
{
	header('location: ../index2.php');
}else{
	

	if(!empty($_POST)) 
	{	
		if(empty($_POST['usuario']) || empty($_POST['clave']))
		{
	
			$alert = 'Ingrese su Cédula y su Contraseña';
		}else{

			require_once "conexion.php";
	
			$user = mysqli_real_escape_string($conection,$_POST['usuario']);
			$pass = md5(mysqli_real_escape_string($conection,$_POST['clave']));
			//$pass = mysqli_real_escape_string($conection,$_POST['clave']);
			//print_r($pass); exit;
			$query = mysqli_query($conection,"SELECT * FROM cliente WHERE usuario_c ='$user' AND clave = '$pass'");
			mysqli_close($conection);
			$result = mysqli_num_rows($query);

			if($result > 0)
			{

				$data = mysqli_fetch_array($query);
				
				//print_r($data); exit;

				$_SESSION['active'] = true;
				$_SESSION['idUser'] = $data['usuario_c'];
				$_SESSION['nombre'] = $data['nombre'];
				$_SESSION['p_apellido'] = $data['p_apellido'];
				$_SESSION['s_apellido'] = $data['s_apellido'];
				$_SESSION['correo_c'] = $data['correo_c'];
				$_SESSION['direccion'] = $data['direccion'];
				$_SESSION['telefono'] = $data['telefono'];
				$_SESSION['credito'] = $data['credito'];
				$_SESSION['rol'] = $data['idrol'];
				$_SESSION['tipo_user'] = $data['tipo_user'];
				
				//$_SESSION['rol'] = $data['rol'];

				header('location:cliente/index.php');
			}else{
				$alert = 'El Usuario o clave es incorrecto';
				session_destroy();

			}

		}
	
	}
}

 ?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width-divice-width, user-scalable=no, initial-scale=1.0, maximun-scale=1.0, minimun-scale=1.0">
	<title>Login | Sistema de Rancho</title>
	<link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
	<section id="container">
		<form action="" method="post">
			<h3>Iniciar Sesión</h3>
			<img src="img/login.png" alt="login" width="150" height="150" align="center">

			<input type="text" name="usuario" placeholder="Cédula">
			<input type="password" name="clave" placeholder="Contraseña">
			<div class="alert" align="center"><?php echo isset($alert) ? $alert : ''; ?></div>
			<input type="submit" name="INGRESAR" value="Ingresar">
		</form>
	</section>
</body>
</html>

