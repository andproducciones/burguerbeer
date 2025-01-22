<?php

$alert='';
session_start();
if(!empty($_SESSION['active']))
{
	header('location: sistema/');
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

			$query = mysqli_query($conection,"SELECT u.usuario,u.nombre,u.apellido,u.correo,r.idrol,r.rol 	FROM usuario u 
								 INNER JOIN rol r
								 ON u.rol = r.idrol
								WHERE u.usuario ='$user' AND u.clave = '$pass'");
			mysqli_close($conection);
			$result = mysqli_num_rows($query);

			if($result > 0)
			{

				$data = mysqli_fetch_assoc($query);

				$_SESSION['active'] 	= true;
				$_SESSION['idUser'] 	= $data['usuario'];
				$_SESSION['nombre'] 	= $data['nombre'];
				$_SESSION['apellido'] 	= $data['apellido'];
				$_SESSION['correo'] 	= $data['correo'];
				$_SESSION['rol'] 		= $data['idrol'];
				$_SESSION['rol_name'] 	= $data['rol'];
				$_SESSION['caja'] 		= gethostbyaddr($_SERVER["REMOTE_ADDR"]);


				//print_r($_SESSION);
				//exit;

				header('location:sistema/');
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
	<meta name="viewport" content="width-divice-width, user-scalable=no, initial-scale=1.0,maximun-scale=1.0, minimun-scale=1.0">

	<title>Login | Sistema de Rancho</title>
	<link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
	<section id="container">
		<form action="" method="post">
			<h3>Iniciar Sesión</h3>
			<img src="img/login.png" alt="login" width="100%" height="100%" align="center">

			<input type="text" name="usuario" placeholder="Cédula">
			<input type="password" name="clave" placeholder="Contraseña">
			<div class="alert" align="center"><?php echo isset($alert) ? $alert : ''; ?></div>
			<input type="submit" name="INGRESAR" value="Ingresar">
		</form>
	</section>
</body>
</html>
