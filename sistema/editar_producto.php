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
			
			
			$codproducto	= $_POST['codproducto'];
			$producto		= $_POST['producto'];
			$descripcion	= $_POST['descripcion'];
			$precio 		= $_POST['precio'];
			

			//echo $codproducto,$producto,$descripcion,$precio;exit;
			
			$query_update = mysqli_query($conection,"UPDATE producto SET producto = '$producto',
				descripcion = '$descripcion',
				precio = $precio WHERE codproducto = $codproducto");
					
					if($query_update){
						$alert='<p class="msg_save">Producto Actualizado correctamente.</p>';
					}else{
						$alert='<p class="msg_error">Error al Actualizar el Producto</p>';

					}
				}
		}

		//validar producto
		if(empty($_REQUEST['codproducto'])){

			header ("location: lista_producto.php");

		}else{

			$id_producto = $_REQUEST['codproducto'];
			if(!is_numeric($id_producto)){

		header ("location: lista_producto.php");
		}
		
		$query_producto = mysqli_query($conection,"SELECT * FROM producto WHERE codproducto = $id_producto AND estatus = 1");
		$result_preducto= mysqli_num_rows($query_producto);

			if($result_preducto > 0){
				$data_producto =mysqli_fetch_assoc($query_producto);
			
				print_r($data_producto);

				}else{
				header ("location: lista_producto.php");
			}

		}

?>




<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	
	<?php include "includes/scripts.php"; ?>
	
	<title>Actualizar Producto</title>
</head>
<body>
	<?php include "includes/header.php"; ?>

	<section id="container"> 

		<div class="form_register">
			<h1> Actualizar Producto</h1>
			<hr>
			<div class="alert"><?php echo isset($alert) ? $alert : ''; ?></div>

			<form action="" method="post">
								
				<input type="hidden" name="codproducto" id="codproducto" value="<?php echo $data_producto['codproducto']; ?>">
				<label for="producto">Nombre del Producto</label>
				
				<input type="text" name="producto" id="producto"  value="<?php echo $data_producto['producto']; ?>" placeholder="Nombre del Producto">

				<label for="descripcion">Descripción</label>
				<input type="text" name="descripcion" id="descripcion" value="<?php echo $data_producto['descripcion']; ?>" placeholder="Descripción">
				
				<label for="precio">Precio</label>
				<input type="number" step="0.01" name="precio" id="precio" value="<?php echo $data_producto['precio']; ?>" placeholder="Precio del Producto">
				<br>
				<p>Nota: Solo un Administrador puede cambiar el precio de los Productos</p>

				
					
				<button type="submit" class="btn_save"><i class="far fa-save fa-lg"></i> Actualizar Producto</button>
				

			</form>


		</div>

	</section>

<?php include "includes/footer.php"; ?>
</body>
</html>