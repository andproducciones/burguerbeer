// caja_hotel.js

$(document).ready(function () {
	// Si no hay habitación seleccionada, abrir el modal automáticamente
	if (!$('#id_habitacion').val()) {
		abrirModalHabitaciones();
	}

	// Botón manual para abrir el modal
	$('#btnSeleccionarHabitacion').on('click', function () {
		abrirModalHabitaciones();
	});

	// Buscar categorías
	$('#buscarCategoriasGrid').on('input', function () {
		let filtro = $(this).val().toLowerCase();
		$('.gridCategorias .producto').each(function () {
			let texto = $(this).text().toLowerCase();
			$(this).toggle(texto.includes(filtro));
		});
	});

	// Buscar servicios
	$('#buscarProductosGrid').on('input', function () {
		let filtro = $(this).val().toLowerCase();
		$('.gridProductos .producto').each(function () {
			let texto = $(this).text().toLowerCase();
			$(this).toggle(texto.includes(filtro));
		});
	});
});

function abrirModalHabitaciones() {
	$.ajax({
		type: 'POST',
		url: 'ajax.php',
		data: { action: 'getHabitacionesDisponibles' },
		dataType: 'json',
		success: function (habitaciones) {
			let contenedor = $('.gridHabitaciones');
			contenedor.empty();

			if (habitaciones.length === 0) {
				Swal.fire('Sin habitaciones', 'No hay habitaciones disponibles.', 'info');
				return;
			}

			habitaciones.forEach(h => {
				let clase = h.ocupada ? 'ocupada' : 'libre';
				let btn = $('<button>')
					.text(h.numero)
					.addClass(clase)
					.attr('data-id', h.id)
					.on('click', function () {
						let idHab = $(this).data('id');
						$('#id_habitacion').val(idHab);
						cargarDatosReserva(idHab);
						$('#modalHabitaciones').hide();
					});
				contenedor.append(btn);
			});

			$('#modalHabitaciones').show();
		},
		error: function () {
			Swal.fire('Error', 'No se pudo cargar habitaciones.', 'error');
		}
	});
}

function cargarDatosReserva(idHabitacion) {
	$.ajax({
		type: 'POST',
		url: 'ajax.php',
		data: { action: 'getDatosReserva', id: idHabitacion },
		dataType: 'json',
		success: function (res) {
			if (!res || !res.reserva) {
				Swal.fire('Error', 'No hay datos de reserva.', 'warning');
				return;
			}

			$('#id_reserva').val(res.reserva.id);
			$('#nombre_cliente').val(res.cliente.nombre);
			$('#telefono_cliente').val(res.cliente.telefono);
			$('#correo_cliente').val(res.cliente.correo);

			cargarDetalleConsumos(res.reserva.id);
		},
		error: function () {
			Swal.fire('Error', 'Error al cargar datos de reserva.', 'error');
		}
	});
}

function cargarDetalleConsumos(idReserva) {
	$.ajax({
		type: 'POST',
		url: 'ajax.php',
		data: { action: 'getDetalleConsumos', id_reserva: idReserva },
		success: function (data) {
			$('#detalle_consumo').html(data.detalle);
			$('#detalle_totales').html(data.totales);
		},
		dataType: 'json'
	});
}

function addServicio(idServicio) {
	let idReserva = $('#id_reserva').val();
	if (!idReserva) return;

	$.ajax({
		type: 'POST',
		url: 'ajax.php',
		data: {
			action: 'addServicioHabitacion',
			id_reserva: idReserva,
			id_servicio: idServicio
		},
		success: function (data) {
			cargarDetalleConsumos(idReserva);
		},
		dataType: 'json'
	});
}
