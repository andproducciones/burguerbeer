<?php
session_start();
date_default_timezone_set('America/Guayaquil'); // o tu zona real

include '../conexion.php';

if ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2) {
    echo "No autorizado";
    exit;
}

$hoy = date('Y-m-d');

$query = mysqli_query($conection, "
    SELECT 
        h.idhabitacion, 
        h.numero, 
        h.estado,
        
        -- Reserva confirmada para hoy
        (SELECT r.idreserva 
         FROM reservas r
         INNER JOIN reservas_detalle rd ON r.idreserva = rd.idreserva
         WHERE r.fecha_entrada = '$hoy' AND r.estado = 'confirmada' 
           AND rd.id_habitacion = h.idhabitacion 
         LIMIT 1) AS reservada_hoy,

        -- Ocupada actualmente (checkin activo)
        (SELECT r2.idreserva 
         FROM reservas r2
         INNER JOIN reservas_detalle rd2 ON r2.idreserva = rd2.idreserva
         WHERE r2.fecha_entrada <= '$hoy' AND r2.fecha_salida > '$hoy' 
           AND rd2.id_habitacion = h.idhabitacion AND r2.estado = 'checkin' 
         LIMIT 1) AS ocupada,

        -- Salida programada para hoy
        (SELECT r3.idreserva 
         FROM reservas r3
         INNER JOIN reservas_detalle rd3 ON r3.idreserva = rd3.idreserva
         WHERE r3.fecha_salida = '$hoy' AND rd3.id_habitacion = h.idhabitacion 
           AND r3.estado = 'checkin' 
         LIMIT 1) AS salida_hoy,

        -- Total de esa reserva para mostrar en check-out
        (SELECT r3.total 
         FROM reservas r3
         INNER JOIN reservas_detalle rd3 ON r3.idreserva = rd3.idreserva
         WHERE r3.fecha_salida = '$hoy' AND r3.estado = 'checkin'
           AND rd3.id_habitacion = h.idhabitacion 
         LIMIT 1) AS total_salida,

        -- Abonos hechos
        (SELECT IFNULL(SUM(p.monto), 0)
         FROM reservas_pagos p
         WHERE p.idreserva = (
           SELECT r3.idreserva 
           FROM reservas r3
           INNER JOIN reservas_detalle rd3 ON r3.idreserva = rd3.idreserva
           WHERE r3.fecha_salida = '$hoy' AND r3.estado = 'checkin' 
             AND rd3.id_habitacion = h.idhabitacion 
           LIMIT 1
         )) AS abono_salida,

        -- ¿Tiene abono registrado hoy?
        (SELECT COUNT(*) 
         FROM reservas_pagos p
         INNER JOIN reservas r ON r.idreserva = p.idreserva
         INNER JOIN reservas_detalle rd ON r.idreserva = rd.idreserva
         WHERE r.fecha_entrada = '$hoy' AND r.estado = 'confirmada'
           AND rd.id_habitacion = h.idhabitacion
         LIMIT 1) AS abono_registrado,

        -- ¿Incluye desayuno?
        (SELECT COUNT(*) 
         FROM reservas_detalle rd
         INNER JOIN reservas r ON r.idreserva = rd.idreserva
         WHERE r.fecha_entrada = '$hoy' 
           AND rd.id_habitacion = h.idhabitacion 
           AND rd.incluye_desayuno = 1
         LIMIT 1) AS incluye_desayuno,

        -- ¿Incluye tour?
        (SELECT COUNT(*) 
         FROM reservas_detalle rd
         INNER JOIN reservas r ON r.idreserva = rd.idreserva
         WHERE r.fecha_entrada = '$hoy' 
           AND rd.id_habitacion = h.idhabitacion 
           AND rd.incluye_tour = 1
         LIMIT 1) AS incluye_tour,

        -- ¿Incluye limpieza especial?
        (SELECT COUNT(*) 
         FROM reservas_detalle rd
         INNER JOIN reservas r ON r.idreserva = rd.idreserva
         WHERE r.fecha_entrada = '$hoy' 
           AND rd.id_habitacion = h.idhabitacion 
          
         LIMIT 1) AS incluye_limpieza

    FROM habitaciones h
    WHERE h.habilitada = 1 ORDER BY CAST(h.numero AS UNSIGNED) ASC

");




$reservasProximas = mysqli_query($conection, "
  SELECT r.idreserva, r.fecha_entrada, r.fecha_salida, r.estado,
         CONCAT(c.nombre, ' ', c.p_apellido) AS cliente,
         GROUP_CONCAT(h.numero ORDER BY h.numero SEPARATOR ', ') AS habitaciones
  FROM reservas r
  INNER JOIN clientes c ON c.usuario = r.id_cliente
  INNER JOIN reservas_detalle rd ON rd.idreserva = r.idreserva
  INNER JOIN habitaciones h ON h.idhabitacion = rd.id_habitacion
  WHERE r.fecha_entrada >= CURDATE()
    AND r.estado IN ('confirmada')
  GROUP BY r.idreserva
  ORDER BY r.fecha_entrada ASC
");


$desayunosQuery = mysqli_query($conection, "
    SELECT h.numero AS habitacion, (rd.adultos + rd.ninos) AS total_desayunos
    FROM reservas_detalle rd
    INNER JOIN reservas r ON rd.idreserva = r.idreserva
    INNER JOIN habitaciones h ON rd.id_habitacion = h.idhabitacion
    WHERE 
        rd.incluye_desayuno = 1
        AND r.estado = 'checkin'
        AND CURDATE() BETWEEN r.fecha_entrada AND DATE_SUB(r.fecha_salida, INTERVAL 1 DAY)
    ORDER BY h.numero
");


$desayunos =  '';

$desayunos .= '<div id="bloqueDesayunos" style=" padding: 10px 15px; background: #fff8e1; border: 1px solid #ffe082; border-radius: 6px; font-size: 15px;">
    <div style="display:flex; justify-content: space-between; align-items: center;">
        <strong>🍽️ Desayunos programados para hoy:</strong>
        <button onclick="imprimirDesayunos()" style="padding: 4px 10px; background: #fbc02d; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">🖨️ Imprimir</button>
    </div><br>';

if ($desayunosQuery && mysqli_num_rows($desayunosQuery) > 0) {
    $desayunos .= '<ul style="margin: 5px 0 0 20px; padding: 0;">';
    while ($row = mysqli_fetch_assoc($desayunosQuery)) {
        $desayunos .= "<li><strong>Hab. {$row['habitacion']}:</strong> {$row['total_desayunos']} desayuno(s)</li>";
    }
    $desayunos .= '</ul>';
} else {
    $desayunos .= '<p style="margin: 5px 0 0;">Ninguna habitación tiene desayuno hoy.</p>';
}
$desayunos .= '</div>';



?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Panel de Habitaciones</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <?php
include "includes/scripts.php";?>

  <style>
   html, body {
  margin: 0;
  padding: 0;
  height: 100%;
  font-family: sans-serif;
  background: #f0f2f5;
  overflow: hidden;
  font-size: 13px;
}

.container {
  display: flex;
  flex-direction: row;
  height: 100vh;
  padding: 10px;
  box-sizing: border-box;
  gap: 10px;
}

/* Bloque de habitaciones (70%) */
.panel-habitaciones {
  flex: 6;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  background: #fff;
  border-radius: 6px;
  padding: 10px;
}

/* Bloque lateral derecho (30%) */
.panel-secundario {
  flex: 4;
  display: flex;
  flex-direction: column;
  gap: 10px;
  overflow-y: auto;
}

/* Habitaciones grid */
.grid-habitaciones {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 10px;
  margin-top: 10px;
}

.habitacion-card {
  border-radius: 10px;
  padding: 10px;
  text-align: center;
  transition: all 0.3s ease;
  color: white;
  font-size: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.habitacion-card h4 {
  margin: 4px 0;
  font-size: 14px;
}

.habitacion-card .badge {
  background: rgba(255, 255, 255, 0.2);
  padding: 4px 10px;
  border-radius: 12px;
  font-weight: bold;
  font-size: 12px;
}

.btn-habitacion {
  padding: 4px 6px;
  font-size: 12px;
  border-radius: 4px;
  border: none;
  cursor: pointer;
  background: white;
  color: #333;
  font-weight: bold;
}

.btn-habitacion:hover {
  opacity: 0.85;
}

/* Colores */
.verde { background: #28a745; color: white; }
.amarillo { background: #ffc107; color: black; }
.rojo { background: #dc3545; color: white; }
.naranja { background: #fd7e14; color: white; }
.gris { background: #6c757d; color: white; }

/* Toolbar */
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.btn-refresh {
  background: #007bff;
  color: white;
  border: none;
  padding: 5px 10px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: bold;
  cursor: pointer;
}

/* Resumen superior */
.resumen-superior {
  display: flex;
  gap: 10px;
  font-size: 12px;
  font-weight: bold;
  justify-content: space-between;
  background: #fff;
  padding: 6px;
  border-radius: 6px;
  margin-bottom: 10px;
}

.resumen-box {
  padding: 6px;
  border-radius: 6px;
  text-align: center;
  min-width: 100px;
}

.resumen-box.total { background: #e9ecef; }
.resumen-box.ocupadas { background: #f8d7da; }
.resumen-box.disponibles { background: #d4edda; }
.resumen-box.mantenimiento { background: #dee2e6; }
.resumen-box.porcentaje { background: #d1ecf1; }

/* Tabla */
.seccion-reservas table {
  font-size: 12px;
}

/* Leyenda */
.leyenda {
  margin-top: 10px;
  font-size: 12px;
}

.leyenda span {
  display: inline-block;
  margin-right: 6px;
  padding: 4px 8px;
  border-radius: 4px;
  font-weight: bold;
}

/* Bloque de desayunos */
#bloqueDesayunos {
  margin: 0;
  padding: 10px;
  background: #fff8e1;
  border: 1px solid #ffe082;
  border-radius: 6px;
  font-size: 13px;
}

#bloqueDesayunos ul {
  margin: 5px 0 0 20px;
  padding: 0;
  font-size: 12px;
}

#bloqueDesayunos button {
  padding: 4px 8px;
  font-size: 12px;
}

/* Modal */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100vh;
  background: rgba(0, 0, 0, 0.81);
  display: none;
  z-index: 10;
  overflow: auto;
}

.bodyModal {
  width: 100%;
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
  box-sizing: border-box;
}

  </style>
</head>

<div class="modal">
  <div class="bodyModal">
  </div>
</div>


<body>
  <div class="container">
  <div class="panel-habitaciones">
    <div class="toolbar">
      <h2>Habitaciones</h2>
      <div style="display:flex; gap:10px;">
        <button class="btn-refresh" onclick="location.reload()">🔄 Actualizar</button>
        <button class="btn-refresh" style="background:#28a745;" onclick="anadirForm('formReserva')">➕ Añadir
          Reserva</button>
      </div>
    </div>

    <div class="resumen-superior">
      <div class="resumen-box total">Total: <span id="countTotal">0</span></div>
      <div class="resumen-box ocupadas">Ocupadas: <span id="countOcupadas">0</span></div>
      <div class="resumen-box disponibles">Disponibles: <span id="countDisponibles">0</span></div>
      <div class="resumen-box mantenimiento">Mantenimiento: <span id="countMantenimiento">0</span></div>
      <div class="resumen-box porcentaje">
        Ocupación Global: <span id="porcentajeOcupacion">0%</span>
      </div>
    </div>
    <div class="leyenda">
        <span class="verde">Disponible</span>
        <span class="amarillo">Reservada hoy</span>
        <span class="rojo">Ocupada</span>
        <span class="naranja">Salida hoy</span>
        <span class="gris">Mantenimiento</span>
      </div>



    <div class="seccion-habitaciones">
      <div class="grid-habitaciones">
        <?php
while ($hab = mysqli_fetch_assoc($query)):
    $estado = 'disponible';
    $colorClass = 'verde';
    $boton = '<button class="btn-habitacion" onclick="anadirForm(\'formCheckinDirecto\','.$hab['idhabitacion'].')">Check-In</button>';
    $esMediodiaPasado = date('H') >= 12;
    $alerta_checkout = ($hab['salida_hoy'] && $esMediodiaPasado);

    if ($hab['salida_hoy']) {
        $estado = 'salida';
        $colorClass = 'naranja';
        $total = floatval($hab['total_salida']);
        $abono = floatval($hab['abono_salida']);
        $boton = '<button class="btn-habitacion" onclick="confirmarCheckout(' . $hab['salida_hoy'] . ', ' . $total . ', ' . $abono . ')">Check-Out</button>';
    } elseif ($hab['ocupada']) {
        $estado = 'ocupada';
        $colorClass = 'rojo';
        $boton = '<button class="btn-habitacion" onclick="window.open(\'pdf/reservas/verReservaPDF.php?id=' . $hab['ocupada'] . '\', \'_blank\')">Ver</button>';
    } elseif ($hab['reservada_hoy']) {
        $estado = 'reservada';
        $colorClass = 'amarillo';
        $boton = '<button class="btn-habitacion" onclick="cambiarEstadoReserva(' . $hab['reservada_hoy'] . ', \'checkin\')">Check-In</button>';
    } elseif ($hab['estado'] == 'mantenimiento') {
        $estado = 'mantenimiento';
        $colorClass = 'gris';
        $boton = '<span class="badge gris">Mantenimiento</span>';
    }
    ?>
        <div class="habitacion-card <?= $colorClass ?>">
  <span class="badge"><?= ucfirst($estado) ?></span>
  <h4>Hab. <?= $hab['numero'] ?></h4>
  <?= $boton ?>

  <?php if ($alerta_checkout): ?>
    <span title="Check-out pendiente" style="font-size:20px;">⚠️</span>
  <?php endif; ?>

  <?php if (!$hab['abono_registrado'] && $hab['reservada_hoy']): ?>
    <span title="Sin abono registrado" style="font-size:18px;">🔴</span>
  <?php endif; ?>

  <div style="margin-top:10px; font-size:18px;">
    <?php if ($hab['reservada_hoy'] || $hab['ocupada'] || $hab['salida_hoy']): ?>
      <?php if ($hab['incluye_desayuno']) {
        echo '<span title="Incluye desayuno">🥐</span>';
      } ?>
      <?php if ($hab['incluye_tour']) {
        echo '<span title="Incluye tour">🗺️</span>';
      } ?>
      <?php if ($hab['incluye_limpieza']) {
        echo '<span title="Incluye limpieza especial">🧼</span>';
      } ?>
    <?php endif; ?>
  </div>
</div>

        <?php endwhile; ?>


      </div>

      
    </div>
    </div>

    <div class="panel-secundario">


      <?php echo $desayunos; ?>

    <div class="seccion-reservas">
      <h3>📅 Reservas próximas</h3>
      <table border="1" cellpadding="8" cellspacing="0" style="width:100%;border-collapse:collapse;" id="tablaFuturas">
        <thead style="background:#eee;">
          <tr>

            <th>Cliente</th>
            <th>IN</th>
            <th>OUT</th>
            <th>Hab.</th>
            <th>Estado</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($r = mysqli_fetch_assoc($reservasProximas)): ?>
          <tr>

            <td>
              <?= htmlspecialchars($r['cliente']) ?>
            </td>

            <td><?= $r['fecha_entrada'] ?></td>
            <td><?= $r['fecha_salida'] ?></td>
            <td>
              <?= htmlspecialchars($r['habitaciones']) ?>
            </td>
            <td><?= ucfirst($r['estado']) ?></td>
            <td>
              <button class="btn btn-habitacion"
                onclick="window.open('pdf/reservas/verReservaPDF.php?id=<?= $r['idreserva'] ?>', '_blank')">Ver</button>
              <button class="btn btn-habitacion"
                onclick="anadirForm('modalAbono', <?= $r['idreserva'] ?>)">Abonar</button>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

   </div>
    
  </div>

</body>

</html>

<script>
  // Auto-recarga cada 60 segundos
  setInterval(() => {
    location.reload();
  }, 60000);

  $(document).ready(function() {
    $('#tablaFuturas').DataTable({
      pageLength: 5,
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
      }
    });
  });

  function anadirForm(action, co) {
    $.ajax({
      url: 'ajax.php',
      type: 'POST',
      async: true,
      data: {
        action: action,
        co: co
      },
      success: function(response) {
        $('.bodyModal').html(response);
        $('.modal').fadeIn();
      }
    });
  }

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
          language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
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
              language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
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
        $('#myTable').DataTable().clear().destroy();
      }

      tbody.innerHTML = contenidoOriginal;

      $('#myTable').DataTable({
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

  document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.habitacion-card');
    let total = cards.length;
    let ocupadas = 0,
      disponibles = 0,
      mantenimiento = 0;

    cards.forEach(card => {
      if (card.classList.contains('rojo')) ocupadas++;
      else if (card.classList.contains('verde')) disponibles++;
      else if (card.classList.contains('gris')) mantenimiento++;

      // Animación suave
      card.style.opacity = 0;
      card.style.transform = 'scale(0.95)';
      setTimeout(() => {
        card.style.transition = 'all 0.3s ease';
        card.style.opacity = 1;
        card.style.transform = 'scale(1)';
      }, 100);
    });

    // Mostrar resumen
    document.getElementById('countTotal').textContent = total;
    document.getElementById('countOcupadas').textContent = ocupadas;
    document.getElementById('countDisponibles').textContent = disponibles;
    document.getElementById('countMantenimiento').textContent = mantenimiento;

    const porcentaje = total > 0 ? Math.round((ocupadas / total) * 100) : 0;
    document.getElementById('porcentajeOcupacion').textContent = `${porcentaje}%`;
  });

  function imprimirDesayunos() {
    fetch("ajax.php", {
        method: "POST",
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: "action=imprimirDesayunos"
      })
      .then(res => res.text())
      .then(resp => {
        if (resp.trim() === "1" || resp.includes("true")) {
          Swal.fire("✅ Imprimido", "Lista de desayunos enviada a la impresora", "success");
        } else {
          Swal.fire("⚠️ Error", resp, "error");
        }
      });
  }
</script>