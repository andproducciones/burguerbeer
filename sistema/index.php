<?php 

session_start();
if(($_SESSION['rol']) != 3)
{
	

?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Sisteme Ventas</title>
</head>
<body>

	<?php 
	include "includes/header.php"; 
	include '../conexion.php';

	$query_dash =  mysqli_query($conection,"CALL dataDashboard();");
	$result_das = mysqli_num_rows($query_dash);
	if($result_das > 0){
		$data_dash = mysqli_fetch_assoc($query_dash);
		mysqli_close($conection);
		
	}
	print_r ($data_dash);
	?>

	<section id="container"> 
		<div class="divContainer">
			<div>
				<h1 class="titlePanelControl">Panel de Control</h1>
			</div>
		
			<div class="dashboard">
				<?php
					if($_SESSION['rol'] == 1){
 					?>
				<a href="lista_usuarios.php">
					<i class="fas fa-users  fa-2x"></i>
					<p>
						<strong>Usuarios</strong><br>
						<span><?= $data_dash['usuarios'];?></span>
				</p>
			</a>
			<?php } ?>
			<a href="lista_clientes.php">
				<i class="fas fa-user fa-2x"></i>
				<p>
					<strong>Clientes</strong><br>
					<span><?= $data_dash['clientes'];?></span>
				</p>
			</a>
			<a href="lista_producto.php">
				<i class="fas fa-cubes fa-2x"></i>
				<p>
					<strong>Productos</strong><br>
					<span><?= $data_dash['productos'];?></span>
				</p>
			</a>
			<a href="ventas.php">
				<i class="fas fa-file-alt fa-2x"></i>
				<p>
					<strong>Ventas del Día</strong><br>
					<span><?= $data_dash['ventas'];?></span>
				</p>
			</a>
		</div>
		</div>		
	
	<div class="divInfoSistema">
		<div>
			<h1 class="titlePanelControl">Configuración</h1>
		</div>
		
		<div class="containerPerfil">
			<div class="containerDataUser">
				<div class="divDataUser">
					
					<img src="img/logo_user.png" class="logoUser">
					<h4>Informacion Personal</h4>
				
					<div>
						<label>Nombre:</label> <span>Francis<?= $_SESSION['nombre'];?> <?= $_SESSION['apellido'];?></span>
					</div>
					<div>
						<label>Correo:</label> <span><?= $_SESSION['correo'];?></span>
					</div>
					<h4>Datos de Usuario</h4>
					<div>
						<label>Rol:</label> <span><?= $_SESSION['rol_name'];?></span>
					</div>
					
					<h4>Cambiar contraseña</h4>
					<form action="" method="post"  name="frmChangePass" id="frmChangePass">
						<div>
							<input type="password" name="txtPassUser" id="txtPassUser" placeholder="Contraseña Actual" required>
						</div>
						<div>
							<input class="newPass" type="password" name="txtNewPassUser" id="txtNewPassUser" placeholder="Nueva Contraseña" required>
						</div>
						<div>
							<input class="newPass" type="password" name="txtPassConfirm" id="txtPassConfirm" placeholder="Confirmar COntraseña" required>
						</div>
						<div class="alertChangePass" style="display: none;">
							
						</div>

						<div>
							<button type="submit" class="btn_save btnChangePass"><i class="fas fa-key"></i> Cambiar Contraseña</button>
						</div>						

					</form>
				</div>
						
		</div>
	</div>
	</div>
	</section>

<?php include "includes/footer.php"; ?>
</body>
</html>

<?php
}else{


header('location: ../index2.php');
session_destroy();
}
?>
