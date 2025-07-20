<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] == 3) {
    header('location: ../index2.php');
    session_destroy();
    exit('Acceso restringido');
}
include '../conexion.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="UTF-8">
	<title>Reservas</title>
	<?php include "includes/scripts.php"; ?>

	<!-- FullCalendar + Plugins -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" />
	<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales-all.global.min.js"></script>

	<link rel="stylesheet" href="https://unpkg.com/tippy.js@6/themes/light-border.css" />
	<script src="https://unpkg.com/@popperjs/core@2"></script>
	<script src="https://unpkg.com/tippy.js@6"></script>


	<!-- Estilo adicional -->
	<style>
		.calendar {
			height: 75vh;
			border: 1px solid #ddd;
			padding: 10px;
			border-radius: 10px;
			background: #fff;
			box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
			overflow-y: auto;
			width: 100%;
		}

		.filtroEstado {
			margin: 10px 0;
			padding: 5px;
			border-radius: 4px;
			border: 1px solid #ccc;
		}

		.divContainer {
			display: flex;
			flex-direction: row;
			justify-content: space-between;
			align-items: flex-start;
			width: 100%;
			gap: 20px;
		}

		.calendar-container {
			flex: 1 1 60%;
			min-width: 300px;
		}

		.table-container {
			flex: 1 1 40%;
			min-width: 300px;
			height: 75vh;
			overflow-y: auto;
			background: #fff;
			border: 1px solid #ccc;
			border-radius: 8px;
			padding: 10px;
		}

		#myTable th,
		#myTable td {
			font-size: 10px;
			padding: 4px;
		}
	</style>
</head>

<body>
	<?php include "includes/header.php"; ?>

	<section id="container">
		<h1><i class="fas fa-calendar-check"></i> Lista de Reservas</h1>

		<?php if ($_SESSION['rol'] == 1) { ?>
		<button type="button" class="anadirForm btn_new" ac="formReserva">
			<i class="fas fa-calendar-plus"></i> Nueva Reserva
		</button>
		<?php } ?>

		<!-- Filtro de estado -->
		<select id="filtroEstado" class="filtroEstado">
			<option value="">Todos</option>
			<option value="pendiente">Pendiente</option>
			<option value="confirmada">Confirmada</option>
			<option value="checkin">Check-in</option>
		</select>

		<div class="divContainer">

			<!-- Calendario -->
			<div class="calendar-container">
				<div id="calendar" class="calendar"></div>
			</div>

			<!-- Tabla de reservas -->
			<div class="table-container">
				<table id="myTable" class="display nowrap" style="width:100%;">
					<thead>
						<tr>
							<th>Fecha</th>
							<th>Cliente</th>
							<th>Hab.</th>
							<th>A</th>
							<th>N</th>
							<th>D</th>
							<th>T</th>
							<th>Estado</th>

						</tr>
					</thead>
					<tbody>
						<!-- ... tu loop PHP va aquí sin cambios ... -->

						<?php
                $query = mysqli_query($conection, "
					SELECT 
						r.idreserva,
						CONCAT(c.nombre, ' ', c.p_apellido) AS cliente,
						r.fecha_entrada,
						r.fecha_salida,
						r.total,
						r.abono,
						r.estado,
						r.estado_pago,
						GROUP_CONCAT(h.numero ORDER BY h.numero SEPARATOR ', ') AS habitaciones,
						SUM(rd.adultos) AS adultos,
						SUM(rd.ninos) AS ninos,
						SUM(rd.incluye_desayuno) AS desayuno,
						SUM(rd.incluye_tour) AS tour,
						r.total - r.abono AS saldo
					FROM reservas r
					INNER JOIN clientes c ON r.id_cliente = c.usuario
					INNER JOIN reservas_detalle rd ON rd.idreserva = r.idreserva
					INNER JOIN habitaciones h ON rd.id_habitacion = h.idhabitacion
					WHERE r.estado != 'cancelada' AND r.estado != 'checkout' AND r.estado != 'checkin'
					GROUP BY r.idreserva
					ORDER BY r.fecha_entrada DESC
				");

if ($query && mysqli_num_rows($query) > 0) {
    while ($data = mysqli_fetch_assoc($query)) {
        ?>
						<tr>
							<td><?= htmlspecialchars($data["fecha_entrada"]) ?>
							</td>
							<td><?= htmlspecialchars($data["cliente"]) ?>
							</td>
							<td><?= htmlspecialchars($data["habitaciones"]) ?>
							</td>

							<td><?= intval($data["adultos"]) ?>
							</td>
							<td><?= intval($data["ninos"]) ?>
							</td>
							<td><?= ($data["desayuno"] > 0) ? 'Sí' : 'No' ?>
							</td>
							<td><?= ($data["tour"] > 0) ? 'Sí' : 'No' ?>
							</td>
							<td><span
									class="estado <?= $data["estado"] ?>"><?= ucfirst($data["estado"]) ?></span>
							</td>

						</tr>
						<?php
    }
} else {
    echo "<tr><td colspan='14'>No hay reservas registradas.</td></tr>";
}
?>
					</tbody>
				</table>

			</div>
	</section>

	<?php include "includes/footer.php"; ?>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const calendarEl = document.getElementById('calendar');
			const filtroEstado = document.getElementById('filtroEstado');

			const calendar = new FullCalendar.Calendar(calendarEl, {
				initialView: 'dayGridMonth',
				locale: 'es', // <- idioma
				headerToolbar: {
					left: 'prev,next today',
					center: 'title',
					right: 'dayGridMonth,listMonth'
				},
				events: function(fetchInfo, successCallback, failureCallback) {
					fetch('ajax.php', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded'
							},
							body: new URLSearchParams({
								action: 'calendarioHabitaciones_reservas',
								estado: filtroEstado.value
							})
						})
						.then(res => res.json())
						.then(data => {
							console.log('📅 Eventos recibidos desde el servidor:', data); // <- Aquí verás el array de eventos
							successCallback(data);
						})
						.catch(error => {
							console.error('❌ Error al cargar eventos:', error); // <- En caso de error
							failureCallback(error);
						});

				},
				eventClick: function(info) {
					const idReserva = info.event.id.split('_')[0];

					// Abrir directamente el PDF en nueva pestaña
					const url = `pdf/reservas/verReservaPDF.php?id=${idReserva}`;
					window.open(url, '_blank');
				},
				eventDidMount: function(info) {
					if (info.event.extendedProps.description) {
						tippy(info.el, {
							content: info.event.extendedProps.description,
							placement: 'top',
							theme: 'light-border',
						});
					}
				},


			});

			calendar.render();

			filtroEstado.addEventListener('change', function() {
				calendar.refetchEvents();
			});
		});
	</script>
</body>

</html>