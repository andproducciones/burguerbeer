<?php 
session_start();
	if($_SESSION['rol'] != 1)
	{

		header("location: ./");
	}
include "../conexion.php";
	
if(!empty($_POST))
{

	$idusuario = $_REQUEST['usuario'];

//$query_delete =mysqli_query($conection,"DELETE FROM usuario WHERE usuario = $idusuario");
	$query_delete =mysqli_query($conection,"UPDATE usuario SET estatus = 0 WHERE usuario = $idusuario");
	mysqli_close($conection); 
	if($query_delete){
		header("location: lista_usuarios.php");

	}else{
		echo "Error al Eliminar";
	}

}

	if(empty($_REQUEST['usuario'])) 
	{

		header("location: lista_usuarios.php");
		mysqli_close($conection); 
		# code...
	}else{
		

		$idusuario = $_REQUEST['usuario'];

		$query = mysqli_query($conection, "SELECT u.usuario, u.nombre, u.apellido, r.rol 
		FROM usuario u
		INNER JOIN
		rol r
		ON u.rol = r.idrol
		WHERE u.usuario = $idusuario ");

		mysqli_close($conection); 
		$result= mysqli_num_rows($query);
		
		if($result > 0){
			
			while ($data = mysqli_fetch_array($query)){

				$idusuario 	=$data['usuario'];
				$nombre		=$data['nombre'];
				$apellido	=$data['apellido'];
				$rol		=$data['rol'];
				}
			}else{
				header("location: lista_usuarios.php");
}
}

 ?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Eliminar Usuario</title>
</head>
<body>
	<?php include "includes/header.php"; ?>

	<section id="container"> 
		<div class="data_delete">
			<i class="fas fa-user-times fa-7x" style="color: #e66262"></i>
			<br>
			<br>
			<h2>¿Seguro desea eliminar el siguiente Usuario?</h2>
			<p>Nombre: <span><?php echo $nombre; ?> <span><?php echo $apellido; ?></span></p>
			<p>Cédula: <span><?php echo $idusuario; ?></span></p>
			<p>Tipo de Usuario: <span><?php echo $rol; ?></span></p>

			<form method="post" action="">
				
				<input type="hidden" name="idusuario" value="<?php echo $idusuario; ?>">
				<a href="lista_usuarios.php" class="btn_cancel"><i class="fas fa-ban"></i> Cancelar</a>
				
				<button type="submit" class="btn_ok"><i class="far fa-trash-alt"></i> Eliminar</button>

			</form>
		
		</div>
		
	</section>

<?php include "includes/footer.php"; ?>
</body>
</html>
