<?php
session_start();
include "../conexion.php";

if (empty($_SESSION['active']) || empty($_SESSION['idUser'])) {
    header('location: salir.php');
}

$id = $_SESSION['idUser'];
$query = mysqli_query($conection, "SELECT a.id_caja,c.lugar FROM arqueo_caja a INNER JOIN cajas c ON a.id_caja = c.id WHERE a.id_usuario = $id AND a.estatus = 1 ");

if (!$query || mysqli_num_rows($query) != 1) {
    header('location: ../');
    exit;
}

$data = mysqli_fetch_assoc($query);
$id_caja = $data['id_caja'];
$lugar = $data['lugar'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="UTF-8">
	<title>Central Hotelera</title>
	<?php include "includes/scripts.php"; ?>
	<link rel="stylesheet" href="css/style.css">
</head>

<body>
	<?php include "includes/header.php"; ?>

	<div id="modalHabitaciones" class="modal" style="display:none;">
		<div class="modal-content">
			<h2>Seleccione una Habitación</h2>
			<div class="gridHabitaciones"></div>
		</div>
	</div>

	<section id="container" class="containerVentas">
		<div class="divVentas">
			<div class="gridVentas">

				<div class="ventasItems cliente">
					<div class="">
						<div class="action_cliente cliente2">
							<h2>Datos del Cliente</h2>
						</div>

						<form name="form_reserva_activa" id="form_reserva_activa" class="datos2">
							<input type="hidden" id="id_habitacion" name="id_habitacion">
							<input type="hidden" id="id_reserva" name="id_reserva">
							<input type="hidden" id="id_caja" name="id_caja"
								value="<?= $id_caja ?>">

							<div class="wd25 mesaResponsive margin">
								<input type="text" id="nombre_cliente" placeholder="Cliente" disabled>
							</div>
							<div class="wd25 nombreResponsive margin">
								<input type="text" id="telefono_cliente" placeholder="Teléfono" disabled>
							</div>
							<div class="wd25 mesaResponsive margin">
								<input type="text" id="correo_cliente" placeholder="Correo" disabled>
							</div>
							<div class="wd10 mesaResponsive margin">
								<button type="button" class="btn1" id="btnSeleccionarHabitacion">
									Seleccionar Habitación
								</button>
							</div>
						</form>
					</div>
				</div>

				<h4 style="text-align:center; display: none;" class="tituloBlock">Categorías</h4>
				<div class="ventasItems nav sombras">
					<h4 class="tituloResponsive">Categorías</h4>
					<input type="text" placeholder="Buscar" id="buscarCategoriasGrid">
					<div class="gridCategorias flexCategorias"></div>
				</div>

				<h4 style="text-align:center; display: none;" class="tituloBlock">Servicios</h4>
				<div class="ventasItems productos sombras">
					<h4 class="tituloResponsive">Servicios</h4>
					<input type="text" placeholder="Buscar" id="buscarProductosGrid">
					<div class="gridProductos categoriaProd"></div>
				</div>

				<div class="ventasItems tablaProductos sombras">
					<table class="tbl_venta tablaventacelular">
						<thead>
							<tr>
								<th>#</th>
								<th colspan="2">Servicio</th>
								<th class="">Precio</th>
								<th class="">Total</th>
								<th>Acciones</th>
							</tr>
						</thead>
						<tbody id="detalle_consumo"></tbody>
					</table>
				</div>

				<div class="ventasItems total sombras">
					<table id="detalle_totales"></table>
				</div>

				<div class="ventasItems footer sombras">
					<div class="">
						<h4>Acciones</h4>
						<div class="gridBotones" id="accionesVenta">
							<button class="textcenter boton btn_new_cliente" id="btnNuevaReserva">Nueva Reserva</button>
							<button class="textcenter boton btn_new_cliente" id="btnEntradaSalida">Entrada / Salida
								Dinero</button>
							<button class="textcenter boton rojo" id="btn_anular_consumo"
								style="display: none;">Anular</button>
							<button class="textcenter boton verde" id="btn_facturar_estadia"
								style="display: none;">Facturar y Cerrar</button>
							<button class="textcenter boton amarillo" id="btn_imprimir_precuenta"
								style="display: none;">Pre-Cuenta</button>
						</div>
					</div>
				</div>

				<div class="ventasItems vendedor sombras">
					<div class="">
						<h4>Operador</h4>
						<p><?= $_SESSION['nombre'] . ' ' . $_SESSION['apellido']; ?>
						</p>
					</div>
					<div class="">
						<h4>Caja</h4>
						<p><?= 'Caja # ' . $id_caja . ' - ' . $lugar; ?>
						</p>
					</div>
				</div>

			</div>
		</div>
	</section>

	<?php include "includes/footer.php"; ?>
	<script src="js/caja_hotel.js"></script>
</body>

</html>