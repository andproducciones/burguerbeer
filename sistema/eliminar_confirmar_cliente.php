<?php 
session_start();
	if($_SESSION['rol'] != 1)
	{

		header("location: ./");
	}
include "../conexion.php";
	
if(!empty($_POST))
{
	
	if(empty($_POST['usuario_c']))
	{
	header("location: lista_clientes.php");
	mysqli_close($conection); 
	}
	
	$idusuario = $_REQUEST['usuario_c'];

//$query_delete =mysqli_query($conection,"DELETE FROM usuario WHERE usuario = $idusuario");
	include "../conexion.php";
	$query_delete = mysqli_query ($conection,"UPDATE cliente SET estatus = 0 WHERE usuario_c = $idusuario");
	 
	
	if($query_delete){
		
		echo "Cliente eliminado correctamente";
		
		header("location: lista_clientes.php");
	
	}else{
		echo "Error al Eliminar";
		
	}

}

	if(empty($_REQUEST['usuario_c'])) 
	{

		header("location: lista_clientes.php");
		mysqli_close($conection); 
		# code...
	}else{
		

		$idusuario = $_REQUEST['usuario_c'];

		$query = mysqli_query($conection, "SELECT c.usuario_c, c.nombre, c.p_apellido, c.s_apellido, t.tipo_user
		FROM cliente c
		INNER JOIN
		tipo_usuario t
		ON c.tipo_user = t.id_tipouser
		WHERE c.usuario_c = $idusuario ");

		mysqli_close($conection); 
		$result= mysqli_num_rows($query);
		
		if($result > 0){
			
			while ($data = mysqli_fetch_array($query)){

				$idusuario 	=$data['usuario_c'];
				$nombre		=$data['nombre'];
				$p_apellido	=$data['p_apellido'];
				$s_apellido	=$data['s_apellido'];
				$tipo_user	=$data['tipo_user'];
				}
			}else{
				header("location: lista_clientes.php");
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
			<h2>Seguro desea eliminar el siguiente Cliente?</h2>
			<p>Nombre: <span><?php echo $nombre; ?> <span><?php echo $p_apellido; ?> <span><?php echo $s_apellido; ?></span></p>
			<p>Cédula: <span><?php echo $idusuario; ?></span></p>
			<p>Tipo de Usuario: <span><?php echo $tipo_user; ?></span></p>

			<form method="post" action="">
				
				<input type="hidden" name="idusuario" value="<?php echo $idusuario; ?>">
				<a href="lista_clientes.php" class="btn_cancel">Cancelar</a>
				<input type="submit" value="Aceptar" class="btn_ok">

			</form>
		
		</div>
		
	</section>

<?php include "includes/footer.php"; ?>
</body>
</html>
