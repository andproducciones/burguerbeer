<?php
session_start();
if ($_SESSION['rol'] != 1) {

    header("location: ./");
}
include '../conexion.php';

?>


<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<?php include "includes/scripts.php"; ?>
	<title>Lista de Usuarios</title>
</head>

<body>
	<?php include "includes/header.php"; ?>

	<section id="container">

		<h1><i class="fas fa-users"></i> Lista de Usuarios</h1>
		<button type="button" class="anadirForm btn_new" ac="formUsuario"><i class="fas fa-user-plus"></i> Crear
			Usuario</button>

		<table id="myTable">
			<thead>
				<tr>
					<th>Cédula</th>
					<th>Nombre</th>
					<th>Lugar</th>
					<th>Tipo de Usuario</th>
					<th>Acciones</th>
				</tr>
			</thead>
			<tbody>
				<?php

            $query = mysqli_query($conection, "SELECT u.usuario, u.nombre, u.apellido, u.correo, r.rol,l.lugar, u.estatus FROM usuario u INNER JOIN rol r ON u.rol = r.idrol INNER JOIN lugar l ON u.lugar = l.id
				");
mysqli_close($conection);
$result = mysqli_num_rows($query);
if ($result > 0) {

    while ($data = mysqli_fetch_array($query)) {

        ?>

				<tr>
					<td><?php echo $data["usuario"]?>
					</td>
					<td><?php echo $data["nombre"]?>
						<?php echo $data["apellido"]?>
					</td>
					<td><?php echo $data["lugar"]?>
					</td>
					<td><?php echo $data["rol"]?>
					</td>
					<td align="center">
						<?php if ($data["estatus"] != 2): ?>
						<!-- Botón Editar -->
						<button class="btn_view anadirForm" ac="formEditarUsuario"
							co="<?php echo $data["usuario"]; ?>">
							<i class="far fa-edit"></i>
						</button>

						<!-- Botón Eliminar -->
						<button class="btn_anular btn_eliminar_usuario"
							data-cedula="<?php echo $data["usuario"]; ?>">
							<i class="far fa-trash-alt"></i>
						</button>
						<?php else: ?>
						<!-- Botón Activar -->
						<button class="btn_activar_usuario btn_view"
							data-cedula="<?php echo $data["usuario"]; ?>">
							<i class="fas fa-user-check"></i>
						</button>
						<?php endif; ?>
					</td>

				</tr>
				<?php
    }
}

?>
			</tbody>

		</table>

	</section>

	<?php include "includes/footer.php"; ?>
</body>

</html>