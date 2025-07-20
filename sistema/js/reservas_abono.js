document.addEventListener("DOMContentLoaded", function () {
	const form = document.getElementById("form_filtro_reservas");
	const tbody = document.querySelector("#myTable tbody");
	const tabla = $('#myTable');
	let contenidoOriginal = tbody.innerHTML;

	form.addEventListener("submit", function (e) {
		e.preventDefault();

		const desde = document.getElementById("fecha_inicio").value;
		const hasta = document.getElementById("fecha_fin").value;
		const estado = document.getElementById("estado_reserva").value;

		if (!desde && !hasta && (!estado || estado === 'todos')) {
			Swal.fire("Mostrando todas", "No se aplicaron filtros. Se muestra la lista completa.", "info");
			if ($.fn.DataTable.isDataTable("#myTable")) {
				tabla.DataTable().clear().destroy();
			}
			tbody.innerHTML = contenidoOriginal;
			tabla.DataTable({
				pageLength: 10,
				language: {
					url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
				}
			});
			return;
		}

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

		tbody.innerHTML = `<tr><td colspan="15" align="center">Cargando...</td></tr>`;

		fetch("ajax.php", {
			method: "POST",
			headers: { "Content-Type": "application/x-www-form-urlencoded" },
			body: params.toString()
		})
			.then(res => res.text())
			.then(data => {
				if ($.fn.DataTable.isDataTable("#myTable")) {
					tabla.DataTable().clear().destroy();
				}
				tbody.innerHTML = data.trim() ? data : `<tr><td colspan="15" align="center">No se encontraron resultados.</td></tr>`;
				if (tbody.querySelectorAll("tr").length > 0 && !tbody.querySelector("tr td[colspan]")) {
					tabla.DataTable({
						pageLength: 10,
						language: {
							url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
						}
					});
				}
			})
			.catch(err => {
				console.error("Error al filtrar reservas:", err);
				tbody.innerHTML = `<tr><td colspan="15" align="center" style="color:red;">Error al obtener resultados.</td></tr>`;
			});
	});

	document.getElementById("btn_limpiar_filtros").addEventListener("click", function () {
		document.getElementById("fecha_inicio").value = "";
		document.getElementById("fecha_fin").value = "";
		document.getElementById("estado_reserva").value = "todos";

		if ($.fn.DataTable.isDataTable("#myTable")) {
			tabla.DataTable().clear().destroy();
		}
		tbody.innerHTML = contenidoOriginal;
		tabla.DataTable({
			pageLength: 10,
			language: {
				url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
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

			const modal = document.querySelector(".modal2");
			const body = document.querySelector(".bodyModal2");

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
				</form>`;

			modal.style.display = "block";

			document.getElementById("metodo_pago").addEventListener("change", function () {
				const refGroup = document.getElementById("referencia_group");
				if (this.value === "2" || this.value === "3") {
					refGroup.style.display = "block";
					document.getElementById("referencia_pago").required = true;
				} else {
					refGroup.style.display = "none";
					document.getElementById("referencia_pago").required = false;
					document.getElementById("referencia_pago").value = "";
				}
			});

			document.getElementById("form_abono").addEventListener("submit", function (e) {
				e.preventDefault();
				const idreserva = document.getElementById("idreserva_abono").value;
				const monto = parseFloat(document.getElementById("monto_abono").value);
				const metodo_pago = document.getElementById("metodo_pago").value;
				const referencia = document.getElementById("referencia_pago").value.trim();

				if (isNaN(monto) || monto <= 0) {
					Swal.fire("Error", "El monto debe ser mayor a 0.", "error");
					return;
				}
				if (!metodo_pago) {
					Swal.fire("Error", "Debe seleccionar un método de pago.", "error");
					return;
				}
				if ((metodo_pago === "2" || metodo_pago === "3") && referencia === "") {
					Swal.fire("Error", "Debe ingresar una referencia de pago.", "error");
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
					headers: { "Content-Type": "application/x-www-form-urlencoded" },
					body: params.toString()
				})
					.then(res => res.json())
					.then(data => {
						if (data.ok) {
							Swal.fire("Éxito", "Abono registrado correctamente.", "success")
								.then(() => location.reload());
						} else {
							Swal.fire("Error", data.msg || "No se pudo registrar el abono.", "error");
						}
					})
					.catch(() => {
						Swal.fire("Error", "Error al conectar con el servidor.", "error");
					});
			});
		});
	});
});

