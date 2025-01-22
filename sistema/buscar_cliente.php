<?php 
	session_start(); //poner siempre el puto SESSION_STAR verga
	include "../conexion.php";	

 ?>


<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Lista de Clientes</title>
</head>
<body>

<?php include "includes/header.php"; ?>
	<section id="container">
		<?php 

			$busqueda = strtolower($_REQUEST['busqueda']);
			if(empty($busqueda))
			{
				header("location: lista_clientes.php");
				

			}
			mysqli_close($conection);

		 ?>
		
		<h1>Lista de clientes</h1>
		<a href="registro_cliente.php" class="btn_new">Crear Cliente</a>
		
		<form action="buscar_cliente.php" method="get" class="form_search">
			<input type="text" name="busqueda" id="busqueda" placeholder="Buscar" value="<?php echo $busqueda; ?>">
			<input type="submit" value="Buscar" class="btn_search">
		</form>

		<table>
			<tr>
				<th>Cédula</th>
				<th>Nombre y Apellidos</th>
				<th>Correo</th>
				<th>Teléfono</th>
				<th>Dirección</th>
				<th>Tipo de Cliente</th>
				<th>Credito</th>
				<th>Acciones</th>
			</tr>
		<?php 
			//Paginador
			$tipo_user = '';
			if($busqueda == 'Oficial')
			{
				$tipo_user = " OR tipo_user LIKE '%1%' ";

			}else if($busqueda == 'Aerotécnico'){

				$tipo_user = " OR tipo_user LIKE '%2%' ";

			}else if($busqueda == 'Servidor Público'){

				$tipo_user = " OR tipo_user LIKE '%3%' ";
			}

			include "../conexion.php";
			$sql_registe = mysqli_query($conection,"SELECT COUNT(*) as total_registro FROM cliente 
																WHERE ( usuario_c LIKE '%$busqueda%' OR 
																		nombre LIKE '%$busqueda%' OR 
																		p_apellido LIKE '%$busqueda%' OR 
																		s_apellido LIKE '%$busqueda%' OR
																		correo_c LIKE '%$busqueda%' OR 
																		telefono LIKE '%$busqueda%' OR
																		direccion LIKE '%$busqueda%' 
																		$tipo_user  ) 
																AND estatus = 1  ");

		
			$result_register = mysqli_fetch_array($sql_registe);
				mysqli_close($conection);
			$total_registro = $result_register['total_registro'];

			$por_pagina = 10;

			if(empty($_GET['pagina']))
			{
				$pagina = 1;
			}else{
				$pagina = $_GET['pagina'];
			}

			$desde = ($pagina-1) * $por_pagina;
			$total_paginas = ceil($total_registro / $por_pagina);

			
			include "../conexion.php";
			
			$query = mysqli_query($conection,"SELECT c.usuario_c, c.nombre, c.p_apellido, c.s_apellido, c.telefono, c.direccion, c.correo_c, t.tipo_user, c.credito FROM cliente c INNER JOIN tipo_usuario t ON c.tipo_user = t.id_tipouser 
										WHERE 
										( c.usuario_c LIKE '%$busqueda%' OR 
											c.nombre LIKE '%$busqueda%' OR 
											c.p_apellido LIKE '%$busqueda%' OR
											c.s_apellido LIKE '%$busqueda%' OR  
											c.direccion LIKE '%$busqueda%' OR
											c.correo_c LIKE '%$busqueda%' OR
											t.tipo_user  LIKE  '%$busqueda%'
											 ) 
										AND
										estatus = 1 LIMIT $desde,$por_pagina 
				");
			
			mysqli_close($conection);
			$result = mysqli_num_rows($query);
			if($result > 0){

				while ($data = mysqli_fetch_array($query)) {
					
			?>
				<tr>
					<td><?php echo $data["usuario_c"]; ?></td>
					<td><?php echo $data["nombre"];?> <?php echo $data["p_apellido"]?> <?php echo $data["s_apellido"];?></td>
					<td><?php echo $data["correo_c"]; ?></td>
					<td><?php echo $data["telefono"]; ?></td>
					<td><?php echo $data["direccion"]; ?></td>
					<td><?php echo $data["tipo_user"]; ?></td>
					<td><?php echo $data["credito"]; ?></t>


					
					<td>
						<a class="link_edit" href="editar_cliente.php?usuario_c=<?php echo $data["usuario_c"]; ?>">Editar</a>

					<?php if($_SESSION["rol"] == 1){ ?>
						|
						<a class="link_delete" href="eliminar_confirmar_cliente.php?usuario_c=<?php echo $data["usuario_c"]; ?>">Eliminar</a>
					<?php } ?>
						
					</td>
				</tr>
			
		<?php 
				}

			}
		 ?>


		</table>
<?php 
	
	if($total_registro != 0)
	{
 ?>
		<div class="paginador">
			<ul>
			<?php 
				if($pagina != 1)
				{
			 ?>
				<li><a href="?pagina=<?php echo 1; ?>&busqueda=<?php echo $busqueda; ?>">|<</a></li>
				<li><a href="?pagina=<?php echo $pagina-1; ?>&busqueda=<?php echo $busqueda; ?>"><<</a></li>
			<?php 
				}
				for ($i=1; $i <= $total_paginas; $i++) { 
					# code...
					if($i == $pagina)
					{
						echo '<li class="pageSelected">'.$i.'</li>';
					}else{
						echo '<li><a href="?pagina='.$i.'&busqueda='.$busqueda.'">'.$i.'</a></li>';
					}
				}

				if($pagina != $total_paginas)
				{
			 ?>
				<li><a href="?pagina=<?php echo $pagina + 1; ?>&busqueda=<?php echo $busqueda; ?>">>></a></li>
				<li><a href="?pagina=<?php echo $total_paginas; ?>&busqueda=<?php echo $busqueda; ?> ">>|</a></li>
			<?php } ?>
			</ul>
		</div>
<?php } ?>


	</section>
	<?php include "includes/footer.php"; ?>
</body>
</html>