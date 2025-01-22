<?php 
session_start();
	if($_SESSION['rol'] != 1)
	{

		header("location: ./");
	}
include "../conexion.php";
	
if(!empty($_POST))
{
	
	if(empty($_POST['codproducto']))
	{
	header("location: lista_producto.php");
	mysqli_close($conection); 
	}
	
	$codproducto = $_REQUEST['codproducto'];

//$query_delete =mysqli_query($conection,"DELETE FROM usuario WHERE usuario = $idusuario");
	include "../conexion.php";
	

	$query_delete =mysqli_query($conection,"UPDATE producto SET estatus = 0 WHERE codproducto = $codproducto");
	mysqli_close($conection); 
	if($query_delete){
		
		echo "Producto eliminado correctamente";
		
		header("location: lista_producto.php");

	}else{
		echo "Error al Eliminar";
	}

}

	if(empty($_REQUEST['codproducto'])) 
	{

		header("location: lista_producto.php");
		mysqli_close($conection); 
		# code...
	}else{
		

		$codproducto = $_REQUEST['codproducto'];

		$query = mysqli_query($conection, "SELECT producto, descripcion 
		FROM producto
		
		WHERE codproducto = $codproducto");

		mysqli_close($conection); 
		$result= mysqli_num_rows($query);
		
		if($result > 0){
			
			while ($data = mysqli_fetch_array($query)){

				$producto	=$data['producto'];
				$detalle	=$data['descripcion'];
				}
			}else{
				header("location: lista_producto.php");
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
			<h2>Seguro desea eliminar el siguiente Prodcuto?</h2>
			<p>Prodructo: <span><?php echo $producto; ?></p>
			<p>Descripcion: <span><?php echo $detalle; ?></span></p>

			<form method="post" action="">
				
				<input type="hidden" name="codproducto" value="<?php echo $codproducto; ?>">
				<a href="lista_producto.php" class="btn_cancel">Cancelar</a>
				<input type="submit" value="Aceptar" class="btn_ok">

			</form>
		
		</div>
		
	</section>

<?php include "includes/footer.php"; ?>
</body>
</html>
