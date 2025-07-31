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
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar/index.global.min.css" />
	<script src="https://cdn.jsdelivr.net/npm/fullcalendar/index.global.min.js"></script>

	<style>
		.modal-content {
			background: #fff;
			padding: 20px;
			border-radius: 10px;
			max-width: 400px;
			margin: 60px auto;
			box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
		}

		.modal-content p {
			margin: 4px 0;
		}

		.btns {
			display: flex;
			justify-content: space-between;
			gap: 10px;
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


		<form class="form_search_date" id="form_filtro_reservas" style="margin-bottom:10px;">
			<label for="fecha_inicio">Desde:</label>
			<input type="date" id="fecha_inicio" name="fecha_inicio">
			<label for="fecha_fin">Hasta:</label>
			<input type="date" id="fecha_fin" name="fecha_fin">
			<label for="estado_reserva">Estado:</label>
			<select id="estado_reserva" name="estado">
				<option value="todos">Todos</option>
				<option value="pendiente">Pendiente</option>
				<option value="confirmada">Confirmada</option>
				<option value="checkin">Check-in</option>
				<option value="checkout">Check-out</option>
				<option value="cancelada">Cancelada</option>
			</select>
			<button type="submit" class="btn_view">Buscar</button>
			<button type="button" id="btn_limpiar_filtros" class="btn_view" style="margin-left:10px;">Limpiar
				Filtros</button>

		</form>





		<table id="myTable">
			<thead>
				<tr>
					<th>ID</th>
					<th>Cliente</th>
					<th>Hab.</th>
					<th>Entrada</th>
					<th>Salida</th>
					<th>Adultos</th>
					<th>Niños</th>
					<th>Desayuno</th>
					<th>Tour</th>
					<th>Garaje</th>
					<th>Total</th>
					<th>Abono</th>
					<th>Saldo</th>
					<th>Estado</th>
					<th>Pago</th>
					<th>Mail</th>
					<th>Tipo Mail</th>
					<th>Acciones</th>
				</tr>
			</thead>
			<tbody>
				<?php
$query = mysqli_query($conection, "
    SELECT 
        r.fecha_creacion,
        r.idreserva,
        CONCAT(c.nombre, ' ', c.p_apellido) AS cliente,
        r.fecha_entrada,
        r.fecha_salida,
        r.total,
        IFNULL(p.abono, 0) AS abono,
        r.estado,
        r.estado_pago,
        h.habitaciones,
        d.adultos,
        d.ninos,
        d.desayuno,
        d.tour,
        r.total - IFNULL(p.abono, 0) AS saldo,
        r.mail,
        r.tipo_mail,
        IF(d.garaje > 0.00, 'Sí', 'No') AS garaje
    FROM reservas r
    INNER JOIN clientes c ON r.id_cliente = c.usuario

    LEFT JOIN (
        SELECT idreserva, GROUP_CONCAT(h.numero ORDER BY h.numero SEPARATOR ', ') AS habitaciones
        FROM reservas_detalle rd
        INNER JOIN habitaciones h ON rd.id_habitacion = h.idhabitacion
        GROUP BY idreserva
    ) h ON r.idreserva = h.idreserva

    LEFT JOIN (
        SELECT idreserva, 
               SUM(adultos) AS adultos, 
               SUM(ninos) AS ninos,
               SUM(incluye_desayuno) AS desayuno, 
               SUM(incluye_tour) AS tour,
               SUM(garaje) AS garaje
        FROM reservas_detalle
        GROUP BY idreserva
    ) d ON r.idreserva = d.idreserva

    LEFT JOIN (
        SELECT idreserva, SUM(monto) AS abono
        FROM reservas_pagos
        GROUP BY idreserva
    ) p ON r.idreserva = p.idreserva

    ORDER BY r.fecha_entrada DESC
    LIMIT 100
");





if ($query && mysqli_num_rows($query) > 0) {
    while ($data = mysqli_fetch_assoc($query)) {
        ?>
				<tr>
					<td><?= $data["idreserva"]; ?>
					</td>
					<td><?= htmlspecialchars($data["cliente"]) ?>
					</td>
					<td><?= htmlspecialchars($data["habitaciones"]) ?>
					</td>
					<td><?= date('d-m', strtotime($data["fecha_entrada"])) ?>
					</td>
					<td><?= date('d-m', strtotime($data["fecha_salida"])) ?>
					</td>
					<td><?= intval($data["adultos"]) ?>
					</td>
					<td><?= intval($data["ninos"]) ?>
					</td>
					<td><?= ($data["desayuno"] > 0) ? 'Sí' : 'No' ?>
					</td>
					<td><?= ($data["tour"] > 0) ? 'Sí' : 'No' ?>
					</td>
					<td><?= ($data["garaje"] > 0) ? 'Sí' : 'No' ?>
					</td>
					<td>$<?= number_format($data["total"], 2) ?>
					</td>
					<td>$<?= number_format($data["abono"], 2) ?>
					</td>

					<td>$<?= number_format($data["saldo"], 2) ?>
					</td>


					<td><span
							class="estado <?= $data["estado"] ?>"><?= ucfirst($data["estado"]) ?></span>
					</td>
					<td><span
							class="estado <?= $data["estado_pago"] ?>"><?= ucfirst($data["estado_pago"]) ?></span>
					</td>
					<td><?= htmlspecialchars($data["mail"]) ?>
					</td>
					<td><?= htmlspecialchars($data["tipo_mail"]) ?>
					</td>

					<td align="center">
						<a class="btn" style="background: blue;"
							href="pdf/reservas/verReservaPDF.php?id=<?= $data['idreserva']; ?>"
							target="_blank" title="Ver reserva PDF"><i class="fas fa-file-pdf"></i></a>
						<?php if ($data['estado'] == 'pendiente' || $data['estado'] == 'confirmada') { ?>
						<button class="btn btn_editar anadirForm"
							co="<?= $data["idreserva"]; ?>"
							ac="formEditarReserva" title="Editar reserva">
							<i class="fas fa-pen"></i>
						</button>
						<button class="btn anadirForm btn_cancelar" ac="formCancelarReserva"
							co="<?= $data["idreserva"]; ?>">
							<i class="fa fa-exclamation-triangle" aria-hidden="true"></i>

						</button>
						<button class="btn btn_abono btn_ver"
							data-id="<?= $data["idreserva"]; ?>"
							data-cliente="<?= htmlspecialchars($data["cliente"]); ?>"
							data-total="<?= number_format($data["total"], 2); ?>"
							data-abono="<?= number_format($data["abono"], 2); ?>"
							data-saldo="<?= number_format($data["saldo"], 2); ?>">
							<i class="fas fa-dollar-sign"></i>
						</button>

						<?php } ?>
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
	</section>

	<?php include "includes/footer.php"; ?>

	<!-- ✅ Script del calendario -->

</body>


</html>

<script>
	let contenidoOriginal = "";

	document.addEventListener("DOMContentLoaded", function() {
		const form = document.getElementById("form_filtro_reservas");
		const tbody = document.querySelector("#myTable tbody");
		const tabla = $('#myTable');

		// 🔁 Guardamos contenido original al cargar
		contenidoOriginal = tbody.innerHTML;

		form.addEventListener("submit", function(e) {
			e.preventDefault();

			const desde = document.getElementById("fecha_inicio").value;
			const hasta = document.getElementById("fecha_fin").value;
			const estado = document.getElementById("estado_reserva").value;

			// ✅ Si no hay filtros, restaurar tabla original
			if (!desde && !hasta && (!estado || estado === 'todos')) {
				Swal.fire("Mostrando todas", "No se aplicaron filtros. Se muestra la lista completa.",
					"info");

				if ($.fn.DataTable.isDataTable("#myTable")) {
					tabla.DataTable().clear().destroy();
				}

				tbody.innerHTML = contenidoOriginal;

				tabla.DataTable({
					pageLength: 10,
					dom: 'Bfrtip',
					buttons: [{
							extend: 'excelHtml5',
							title: function() {
								const desde = document.getElementById("fecha_inicio")
									.value;
								const hasta = document.getElementById("fecha_fin").value;
								if (desde && hasta) {
									return `Reservas del ${desde} al ${hasta}`;
								}
								return "Reservas";
							},
							exportOptions: {
								columns: ':not(:last-child)'
							}
						},
						{
							extend: 'pdfHtml5',
							title: function() {
								const desde = document.getElementById("fecha_inicio")
									.value;
								const hasta = document.getElementById("fecha_fin").value;
								if (desde && hasta) {
									return `Reservas del ${desde} al ${hasta}`;
								}
								return "Reservas";
							},
							orientation: 'landscape',
							pageSize: 'A4',
							exportOptions: {
								columns: ':not(:last-child)'
							},
							customize: function(doc) {
								doc.defaultStyle.fontSize = 8;
								doc.styles.tableHeader.fontSize = 10;
							}
						}
					],
					language: {
						url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
					}
				});



				return;
			}

			// 🚫 Validaciones
			if ((desde && !hasta) || (!desde && hasta)) {
				Swal.fire("Atención", "Debe completar ambas fechas: Desde y Hasta.", "warning");
				return;
			}

			if (desde && hasta && desde > hasta) {
				Swal.fire("Error", "'Desde' no puede ser mayor que 'Hasta'.", "error");
				return;
			}

			const params = new URLSearchParams();
			params.append("action", "filtroReservasPorFecha");

			if (desde && hasta) {
				params.append("desde", desde);
				params.append("hasta", hasta);
			}

			if (estado && estado !== 'todos') {
				params.append("estado", estado);
			}

			// ⏳ Mostrar cargando
			tbody.innerHTML = `<tr><td colspan="15" align="center">Cargando...</td></tr>`;

			fetch("ajax.php", {
					method: "POST",
					headers: {
						"Content-Type": "application/x-www-form-urlencoded"
					},
					body: params.toString()
				})
				.then(res => res.text())
				.then(data => {
					if ($.fn.DataTable.isDataTable("#myTable")) {
						tabla.DataTable().clear().destroy();
					}

					if (data.trim()) {
						tbody.innerHTML = data;
					} else {
						tbody.innerHTML =
							`<tr><td colspan="15" align="center">No se encontraron resultados.</td></tr>`;
					}

					const hayResultados = tbody.querySelectorAll("tr").length > 0 &&
						!tbody.querySelector("tr td[colspan]");

					if (hayResultados) {
						tabla.DataTable({
							pageLength: 10,
							dom: 'Bfrtip',
							buttons: [{
									extend: 'excelHtml5',
									title: function() {
										const desde = document.getElementById(
											"fecha_inicio").value;
										const hasta = document.getElementById(
											"fecha_fin").value;
										if (desde && hasta) {
											return `Reservas del ${desde} al ${hasta}`;
										}
										return "Reservas";
									},
									exportOptions: {
										columns: ':not(:last-child)' // Excluir acciones
									}
								},
								{
									extend: 'pdfHtml5',
									title: function() {
										const desde = document.getElementById(
											"fecha_inicio").value;
										const hasta = document.getElementById(
											"fecha_fin").value;
										if (desde && hasta) {
											return `Reservas del ${desde} al ${hasta}`;
										}
										return "Reservas";
									},
									orientation: 'landscape',
									pageSize: 'A4',
									exportOptions: {
										columns: ':not(:last-child)'
									},
									customize: function(doc) {
										doc.defaultStyle.fontSize = 8;
										doc.styles.tableHeader.fontSize = 10;
									}
								}
							],
							language: {
								url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
							}
						});


					}
				})
				.catch(err => {
					console.error("Error al filtrar reservas:", err);
					tbody.innerHTML =
						`<tr><td colspan="15" align="center" style="color:red;">Error al obtener resultados.</td></tr>`;
				});
		});

		document.getElementById("btn_limpiar_filtros").addEventListener("click", function() {
			document.getElementById("fecha_inicio").value = "";
			document.getElementById("fecha_fin").value = "";
			document.getElementById("estado_reserva").value = "todos";

			if ($.fn.DataTable.isDataTable("#myTable")) {
				tabla.DataTable().clear().destroy();
			}

			tbody.innerHTML = contenidoOriginal;

			tabla.DataTable({
				pageLength: 10,
				dom: 'Bfrtip',
				buttons: [{
						extend: 'excelHtml5',
						title: function() {
							const desde = document.getElementById("fecha_inicio").value;
							const hasta = document.getElementById("fecha_fin").value;
							if (desde && hasta) {
								return `Reservas del ${desde} al ${hasta}`;
							}
							return "Reservas";
						},
						exportOptions: {
							columns: ':not(:last-child)'
						}
					},
					{
						extend: 'pdfHtml5',
						title: function() {
							const desde = document.getElementById("fecha_inicio").value;
							const hasta = document.getElementById("fecha_fin").value;
							if (desde && hasta) {
								return `Reservas del ${desde} al ${hasta}`;
							}
							return "Reservas";
						},
						orientation: 'landscape',
						pageSize: 'A4',
						exportOptions: {
							columns: ':not(:last-child)'
						},
						customize: function(doc) {
							doc.defaultStyle.fontSize = 8;
							doc.styles.tableHeader.fontSize = 10;
						}
					}
				],
				language: {
					url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
				}
			});

			Swal.fire("Filtros limpiados", "Se muestra la lista completa de reservas.", "success");
		});

		document.querySelectorAll(".btn_abono").forEach(btn => {
			btn.addEventListener("click", () => {
				const id = btn.dataset.id;
				const cliente = btn.dataset.cliente;
				const total = btn.dataset.total;
				const abono = btn.dataset.abono;
				const saldo = btn.dataset.saldo;



				const modal = document.querySelector(".modal");
				const body = document.querySelector(".bodyModal");

				body.innerHTML = `
			<form id="form_abono">
				<h2>Agregar Abono</h2>
				<p><strong>Reserva ID:</strong> ${id}</p>
				<p><strong>Cliente:</strong> ${cliente}</p>
				<p><strong>Total:</strong> $${total}</p>
				<p><strong>Abonado:</strong> $${abono}</p>
				<p><strong>Saldo Pendiente:</strong> $${saldo}</p>
				<hr>

				<input type="hidden" id="idreserva_abono" name="idreserva" value="${id}">

				<label for="monto_abono">Monto a abonar:</label>
				<input type="number" step="0.01" min="0" max="${saldo}" id="monto_abono" name="monto" required>
				<small>El monto no debe exceder el saldo pendiente.</small>

				<label for="metodo_pago" style="margin-top:10px;">Método de pago:</label>
				<select id="metodo_pago" name="metodo_pago" required>
					<option value="">-- Seleccione --</option>
					<option value="1">Efectivo</option>
					<option value="2">Tarjeta</option>
					<option value="3">Transferencia</option>
				</select>

				<div id="referencia_group" style="display: none; margin-top: 10px;">
					<label for="referencia_pago">Referencia o número de transacción:</label>
					<input type="text" id="referencia_pago" name="referencia" placeholder="Referencia bancaria o código">
				</div>

				<div class="btns" style="margin-top: 15px;">
					<button type="submit" class="btn_save"><i class="fas fa-check"></i> Guardar</button>
					<button type="button" class="btn_cancel" onclick="closeModal();"><i class="fas fa-ban"></i> Cancelar</button>
				</div>
			</form>
			`;

				modal.style.display = "block";

				// Mostrar campo de referencia según método
				document.getElementById("metodo_pago").addEventListener("change", function() {
					const metodo = this.value;
					const refGroup = document.getElementById("referencia_group");
					if (metodo === "2" || metodo === "3") {
						refGroup.style.display = "block";
						document.getElementById("referencia_pago").required = true;
					} else {
						refGroup.style.display = "none";
						document.getElementById("referencia_pago").required = false;
						document.getElementById("referencia_pago").value = "";
					}
				});

				// Enviar formulario
				document.getElementById("form_abono").addEventListener("submit", function(e) {
					e.preventDefault();

					const idreserva = document.getElementById("idreserva_abono").value;
					const monto = parseFloat(document.getElementById("monto_abono").value);
					const metodo_pago = document.getElementById("metodo_pago").value;
					const referencia = document.getElementById("referencia_pago").value
						.trim();

					if (isNaN(monto) || monto <= 0) {
						Swal.fire("Error", "El monto debe ser mayor a 0.", "error");
						return;
					}

					if (!metodo_pago) {
						Swal.fire("Error", "Debe seleccionar un método de pago.", "error");
						return;
					}

					if ((metodo_pago === "2" || metodo_pago === "3") && referencia ===
						"") {
						Swal.fire("Error", "Debe ingresar una referencia de pago.",
							"error");
						return;
					}

					const params = new URLSearchParams();
					params.append("action", "agregarAbono");
					params.append("idreserva", idreserva);
					params.append("monto", monto);
					params.append("metodo_pago", metodo_pago);
					params.append("referencia", referencia);

					fetch("ajax.php", {
							method: "POST",
							headers: {
								"Content-Type": "application/x-www-form-urlencoded"
							},
							body: params.toString()
						})
						.then(res => res.json())
						.then(data => {
							console.log(data);
							if (data.ok) {
								Swal.fire("Éxito", "Abono registrado correctamente.",
										"success")
									.then(() => location.reload());
							} else {
								Swal.fire("Error", data.msg ||
									"No se pudo registrar el abono.", "error");
							}
						})
						.catch(() => {
							Swal.fire("Error", "Error al conectar con el servidor.",
								"error");
						});
				});
			});
		});


	});
</script>