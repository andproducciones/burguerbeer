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
		if(empty($_POST['producto']) || empty($_POST['descripcion']) || empty($_POST['precio']) || ($_POST['precio'] <=0))		{
			
			$alert='<p class="msg_error">Todos los campos son obligatorios.</p>';
		}else{
			
			
			$producto		= $_POST['producto'];
			$descripcion	= $_POST['descripcion'];
			$precio 		= $_POST['precio'];
			$usuario_id	    = $_SESSION['idUser'];

			//echo $producto,$descripcion,$precio,$usuario_id;exit;
			
			$query_insert = mysqli_query($conection,"INSERT INTO producto(producto,descripcion,precio,usuario_id) VALUES('$producto','$descripcion','$precio','$usuario_id')");
					
					if($query_insert){
						$alert='<p class="msg_save">Producto creado correctamente.</p>';
					}else{
						$alert='<p class="msg_error">Error al crear el Producto</p>';

					}
				}
		}
?>




<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	
	<?php include "includes/scripts.php"; ?>
	
	<title>Registro Productos</title>
</head>
<body>
	<?php include "includes/header.php"; ?>

	<section id="container"> 

		<div class="form_register">
			<h1> Registro de Productos</h1>
			<hr>
			<div class="alert"><?php echo isset($alert) ? $alert : ''; ?></div>

			<form action="" method="post">
								
				<label for="producto">Nombre del Producto</label>
				<input type="text" name="producto" id="producto" placeholder="Nombre del Producto">

				<label for="descripcion">Descripción</label>
				<input type="text" name="descripcion" id="descripcion" placeholder="Descripcion">
				
				<label for="precio">Precio</label>
				<input type="number" step="0.01" name="precio" id="precio" placeholder="Precio del Producto">
				
					
				<button type="submit" class="btn_save"><i class="far fa-save fa-lg"></i> Guardar Producto</button>
				

			</form>


		</div>

	</section>

<?php include "includes/footer.php"; ?>
</body>
</html>