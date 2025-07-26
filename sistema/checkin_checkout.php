<?php
session_start();
require_once '../conexion.php';

// Validar sesión y permisos si es necesario
if (!isset($_SESSION['rol']) || $_SESSION['rol'] > 2) {
    header('Location: index.php');
    exit;
}

$query = mysqli_query($conection, "
    SELECT 
        r.idreserva, 
        r.total, 
        (SELECT IFNULL(SUM(monto), 0) FROM reservas_pagos p WHERE p.idreserva = r.idreserva) AS abono,
        CONCAT(c.nombre, ' ', c.p_apellido) AS cliente,
        r.fecha_entrada, 
        r.fecha_salida, 
        r.estado, 
        r.facturada,
        GROUP_CONCAT(h.numero ORDER BY h.numero SEPARATOR ', ') AS habitaciones,
        SUM(rd.adultos) AS adultos,
        SUM(rd.ninos) AS ninos
    FROM reservas r
    INNER JOIN clientes c ON r.id_cliente = c.usuario
    INNER JOIN reservas_detalle rd ON r.idreserva = rd.idreserva
    INNER JOIN habitaciones h ON rd.id_habitacion = h.idhabitacion
    WHERE r.estado IN ('confirmada', 'checkin')
      AND DATE(r.fecha_entrada) = CURDATE()
    GROUP BY r.idreserva
    ORDER BY r.fecha_entrada ASC
");



$query_futuras = mysqli_query($conection, "
    SELECT 
        r.idreserva,
        r.fecha_entrada,
        r.fecha_salida,
        CONCAT(c.nombre, ' ', c.p_apellido) AS cliente
    FROM reservas r
    INNER JOIN clientes c ON r.id_cliente = c.usuario
    WHERE r.estado IN ('pendiente', 'confirmada')
      AND DATE(r.fecha_entrada) > CURDATE()
    ORDER BY r.fecha_entrada ASC
");



?>

<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="UTF-8">
	<title>Check-in / Check-out</title>
	<?php include 'includes/scripts.php'; ?>

	<style>
		.flex-tablas {
			display: flex;

			gap: 2%;
			margin-top: 30px;
		}

		.tabla-hoy {
			flex: 0 0 65%;
		}

		.tabla-futuras {

			width: 100%;
		}

		.tabla-hoy h1,
		.tabla-futuras h1 {
			font-size: 18pt;
			margin-bottom: 10px;
		}

		@media (max-width: 768px) {
			.flex-tablas {
				flex-direction: column;
			}

			.tabla-hoy,
			.tabla-futuras {
				flex: 0 0 100%;
			}
		}


		/* Personalización para formulario de Checkout (SweetAlert2) */

		.swal2-html-container {
			text-align: left !important;
			max-width: 100% !important;
			padding: 10px 5px;
		}

		.swal2-html-container p {
			margin: 5px 0;
			font-size: 15px;
		}

		.swal2-html-container strong {
			font-weight: bold;
		}

		.swal2-select,
		.swal2-input {
			width: 50% !important;
			box-sizing: border-box;
			margin-top: 5px;
		}

		.swal2-input {
			margin-bottom: 10px;
		}

		@media (max-width: 480px) {
			.swal2-html-container p {
				font-size: 14px;
			}
		}
	</style>

</head>

<body>
	<?php include 'includes/header.php'; ?>

	<section id="container">



		<div class="flex-tablas">
			<!-- Tabla de hoy -->
			<div class="tabla-hoy">

				<div style="display: flex; justify-content: space-between;">
					<h1><i class="fas fa-door-open"></i> Check-In / Check-Out</h1>
					<button class="anadirForm btn_new" ac="formCheckinDirecto" style="margin: 5px;">
						<i class="fas fa-user-plus"></i> Ingreso sin Reserva
					</button>

				</div>

				<table class="tableData" id="myTable">
					<thead>
						<tr>
							<th>Cliente</th>
							<th>Hab.</th>
							<th>Entrada</th>
							<th>Salida</th>
							<th>A</th>
							<th>N</th>
							<th>Total</th>
							<th>Abono</th>
							<th>Faltante</th>
							<th>Estado</th>
							<th>Acciones</th>
						</tr>
					</thead>
					<tbody>
						<?php if ($query && mysqli_num_rows($query) > 0): ?>
						<?php while ($row = mysqli_fetch_assoc($query)) { ?>
						<tr>
							<td><?= $row['cliente'] ?>
							</td>
							<td><?= $row['habitaciones'] ?>
							</td>
							<td><?= date('d-m-Y', strtotime($row['fecha_entrada'])) ?>
							</td>
							<td><?= date('d-m-Y', strtotime($row['fecha_salida'])) ?>
							</td>
							<td><?= $row['adultos'] ?>
							</td>
							<td><?= $row['ninos'] ?>
							</td>
							<td>$<?= number_format($row['total'], 2) ?>
							</td>
							<td>$<?= number_format($row['abono'], 2) ?>
							</td>
							<td>$<?= number_format($row['total'] - $row['abono'], 2) ?>
							</td>
							<td>
								<span
									class="estado <?= $row['estado'] ?>"><?= ucfirst($row['estado']) ?></span>
								<?php if ($row['facturada'] == 1): ?>
								<span class="badge bg-success">Facturada</span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ($row['estado'] == 'confirmada'): ?>
								<button class="btn btn_checkin"
									onclick="cambiarEstadoReserva(<?= $row['idreserva'] ?>, 'checkin')">
									<i class="fas fa-sign-in-alt"></i> Check-In
								</button>
								<?php elseif ($row['estado'] == 'checkin'): ?>
								<button class="btn btn_checkout"
									onclick="confirmarCheckout(<?= $row['idreserva'] ?>, <?= $row['total'] ?>, <?= $row['abono'] ?>)">
									<i class="fas fa-sign-out-alt"></i> Check-Out
								</button>
								<?php elseif ($row['estado'] == 'checkout' && $row['facturada'] == 0): ?>
								<button class="btn btn_facturar"
									onclick="facturarReserva(<?= $row['idreserva'] ?>)">
									<i class="fas fa-file-invoice-dollar"></i> Facturar
								</button>
								<?php else: ?>
								<span style="color:gray;">Sin acciones</span>
								<?php endif; ?>
							</td>

						</tr>
						<?php } ?>
						<?php else: ?>
						<tr>
							<td colspan="11" align="center">No hay reservas programadas para hoy.</td>
						</tr>
						<?php endif; ?>
					</tbody>

				</table>
			</div>
			<div class="tabla-futuras">
				<h1><i class="fas fa-clock"></i> Próximos</h1>
				<table class="tableData" id="tablaFuturas">
					<thead>
						<tr>
							<th>Cliente</th>
							<th>Entrada</th>
							<th>Salida</th>
							<th>Estado</th>
						</tr>
					</thead>
					<tbody>
						<?php if ($query_futuras && mysqli_num_rows($query_futuras) > 0): ?>
						<?php while ($f = mysqli_fetch_assoc($query_futuras)): ?>
						<tr>
							<td><?= htmlspecialchars($f['cliente']) ?>
							</td>
							<td><?= date('d-m-Y', strtotime($f['fecha_entrada'])) ?>
							</td>
							<td><?= date('d-m-Y', strtotime($f['fecha_salida'])) ?>
							</td>
							<td><span
									class="estado"><?= ucfirst($f['estado'] ?? 'confirmada') ?></span>
							</td>
						</tr>
						<?php endwhile; ?>
						<?php else: ?>
						<tr>
							<td colspan="4" align="center">No hay futuros ingresos programados.</td>
						</tr>
						<?php endif; ?>
					</tbody>

				</table>

			</div>
		</div>

	</section>

	<?php include 'includes/footer.php'; ?>

	<script>
		$(document).ready(function() {
			$('#tablaFuturas').DataTable({
				pageLength: 5,
				language: {
					url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
				}
			});
		});


		function cambiarEstadoReserva(id, nuevoEstado) {
			if (nuevoEstado === 'checkin') {
				Swal.fire({
					title: 'Confirmar Check-In',
					text: '¿Deseas registrar el ingreso de este cliente ahora?',
					icon: 'question',
					showCancelButton: true,
					confirmButtonText: 'Sí, hacer Check-In',
					cancelButtonText: 'Cancelar'
				}).then((result) => {
					if (result.isConfirmed) {
						Swal.fire({
							title: 'Procesando...',
							allowOutsideClick: false,
							didOpen: () => Swal.showLoading()
						});

						$.post('ajax.php', {
							action: 'cambiarEstadoReserva',
							idreserva: id,
							estado: 'checkin'
						}, function(response) {
							if (response.trim() === 'OK') {
								Swal.fire({
									icon: 'success',
									title: 'Check-In realizado con éxito',
									timer: 2000,
									showConfirmButton: false
								}).then(() => location.reload());
							} else {
								Swal.fire('Error', response, 'error');
							}
						}).fail(() => {
							Swal.fire('Error', 'Error de conexión con el servidor.', 'error');
						});
					}
				});
			} else {
				// Para otros estados como 'checkout' u otros
				Swal.fire({
					title: `¿Confirmar ${nuevoEstado.toUpperCase()}?`,
					icon: 'question',
					showCancelButton: true,
					confirmButtonText: 'Sí, continuar',
					cancelButtonText: 'Cancelar'
				}).then((result) => {
					if (result.isConfirmed) {
						$.post('ajax.php', {
							action: 'cambiarEstadoReserva',
							idreserva: id,
							estado: nuevoEstado
						}, function(response) {
							if (response.trim() === 'OK') {
								Swal.fire('Actualizado', `La reserva fue marcada como ${nuevoEstado}`,
										'success')
									.then(() => location.reload());
							} else {
								Swal.fire('Error', response, 'error');
							}
						});
					}
				});
			}
		}


		function confirmarCheckout(id, total, abono) {
			const faltante = parseFloat(total - abono).toFixed(2);

			let htmlForm = `
	<div style="text-align:left">
		<p><strong>Total:</strong> $${parseFloat(total).toFixed(2)}</p>
		<p><strong>Abonado:</strong> $${parseFloat(abono).toFixed(2)}</p>
		<p style="color:${faltante > 0 ? 'red' : 'green'};"><strong>${faltante > 0 ? 'Faltante a pagar:' : 'Saldo completo:'}</strong> $${faltante}</p>
	`;

			if (faltante > 0) {
				htmlForm += `
		<hr>
		<label for="metodo_pago">Método de Pago:</label><br>
		<select name="metodo_pago" id="metodo_pago" required>
			<option value="">Seleccione</option>
			<option value="1">Efectivo</option>
			<option value="2">Tarjeta</option>
			<option value="3">Transferencia</option>
		</select>
		<input type="text" id="referencia_pago_final" class="swal2-input" placeholder="Referencia o Documento (si aplica)">
		`;
			}

			htmlForm += `</div>`;

			Swal.fire({
				title: 'Confirmar Check-Out',
				html: htmlForm,
				showCancelButton: true,
				confirmButtonText: faltante > 0 ? 'Cobrar y Finalizar' : 'Solo Finalizar',
				cancelButtonText: 'Cancelar',
				confirmButtonColor: '#28a745',
				cancelButtonColor: '#d33',
				preConfirm: () => {
					if (faltante > 0) {
						const metodo = document.getElementById('metodo_pago').value;
						const referencia = document.getElementById('referencia_pago_final').value.trim();

						if (!metodo) {
							Swal.showValidationMessage('Debes seleccionar un método de pago');
							return false;
						}

						if ((metodo === "2" || metodo === "3") && referencia === "") {
							Swal.showValidationMessage(
								'Debes ingresar una referencia para tarjeta o transferencia');
							return false;
						}

						return {
							metodo,
							referencia
						};
					} else {
						return {
							metodo: 'N/A',
							referencia: ''
						};
					}
				}
			}).then((result) => {
				if (result.isConfirmed) {
					Swal.fire({
						title: 'Procesando...',
						text: 'Registrando el Check-Out...',
						allowOutsideClick: false,
						didOpen: () => {
							Swal.showLoading();
						}
					});

					$.post('ajax.php', {
						action: 'realizarCheckout',
						idreserva: id,
						faltante: faltante,
						metodo_pago: result.value.metodo,
						referencia: result.value.referencia
					}, function(response) {
						if (response.trim() === 'OK') {
							Swal.fire({
								icon: 'success',
								title: 'Check-Out realizado correctamente',
								timer: 2000,
								showConfirmButton: false
							}).then(() => location.reload());
						} else {
							Swal.fire('Error', response, 'error');
						}
					}).fail(() => {
						Swal.fire('Error', 'Error de conexión con el servidor.', 'error');
					});
				}
			});
		}
	</script>
</body>

</html>