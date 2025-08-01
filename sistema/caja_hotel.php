<?php
session_start();
date_default_timezone_set('America/Guayaquil'); // o tu zona real


include '../conexion.php';


mysqli_set_charset($conection, 'utf8mb4');


if ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2) {
    echo "No autorizado";
    exit;
}

$hoy = date('Y-m-d');

$query = mysqli_query($conection, "
WITH reservas_activas AS (
  SELECT 
    r.idreserva,
    r.estado,
    r.total,
    rd.id_habitacion,
    r.id_cliente,
    ROW_NUMBER() OVER (
      PARTITION BY rd.id_habitacion
      ORDER BY 
        CASE r.estado 
          WHEN 'checkin' THEN 1 
          WHEN 'confirmada' THEN 2 
          ELSE 3 
        END,
        r.fecha_entrada ASC
    ) AS prioridad
  FROM reservas r
  INNER JOIN reservas_detalle rd ON r.idreserva = rd.idreserva
  WHERE CURDATE() BETWEEN r.fecha_entrada AND r.fecha_salida
    AND r.estado IN ('checkin', 'confirmada')
)

SELECT 
  h.idhabitacion, 
  h.numero, 
  h.estado AS estado_habitacion,

  ra.idreserva AS reserva_activa,
  ra.estado AS estado_reserva,
  ra.total AS total_salida,

  -- Abonos de la reserva activa
  IFNULL((
    SELECT SUM(p.monto)
    FROM reservas_pagos p
    WHERE p.idreserva = ra.idreserva
  ), 0) AS abono_salida,

  -- Reserva confirmada para hoy (para ícono 🔴 y botón check-in)
  (
    SELECT r.idreserva 
    FROM reservas r
    INNER JOIN reservas_detalle rd ON r.idreserva = rd.idreserva
    WHERE r.fecha_entrada = CURDATE() 
      AND r.estado = 'confirmada' 
      AND rd.id_habitacion = h.idhabitacion 
    LIMIT 1
  ) AS reservada_hoy,

  -- Ocupada actualmente (checkin activo y salida no vencida)
  (
    SELECT r.idreserva 
    FROM reservas r
    INNER JOIN reservas_detalle rd ON r.idreserva = rd.idreserva
    WHERE r.estado = 'checkin'
      AND rd.id_habitacion = h.idhabitacion
      AND r.fecha_entrada <= CURDATE()
      AND (
        r.fecha_salida > CURDATE() OR 
        (r.fecha_salida = CURDATE() AND CURTIME() < '12:00:00')
      )
    LIMIT 1
  ) AS ocupada,

  -- Salida programada hoy
  (
    SELECT r.idreserva
    FROM reservas r
    INNER JOIN reservas_detalle rd ON r.idreserva = rd.idreserva
    WHERE r.estado = 'checkin'
      AND r.fecha_salida = CURDATE()
      AND rd.id_habitacion = h.idhabitacion
    LIMIT 1
  ) AS salida_hoy,

  -- ¿Tiene abono registrado hoy?
  (
    SELECT COUNT(*) 
    FROM reservas_pagos p
    INNER JOIN reservas r ON p.idreserva = r.idreserva
    INNER JOIN reservas_detalle rd ON r.idreserva = rd.idreserva
    WHERE r.fecha_entrada = CURDATE()
      AND r.estado = 'confirmada'
      AND rd.id_habitacion = h.idhabitacion
    LIMIT 1
  ) AS abono_registrado,

  -- ¿Incluye desayuno?
  (
    SELECT COUNT(*) 
    FROM reservas_detalle rd
    INNER JOIN reservas r ON r.idreserva = rd.idreserva
    WHERE r.fecha_entrada = CURDATE()
      AND rd.id_habitacion = h.idhabitacion 
      AND rd.incluye_desayuno = 1
    LIMIT 1
  ) AS incluye_desayuno,

  -- ¿Incluye tour?
  (
    SELECT COUNT(*) 
    FROM reservas_detalle rd
    INNER JOIN reservas r ON r.idreserva = rd.idreserva
    WHERE r.fecha_entrada = CURDATE()
      AND rd.id_habitacion = h.idhabitacion 
      AND rd.incluye_tour = 1
    LIMIT 1
  ) AS incluye_tour,

  -- ¿Incluye limpieza?
  (
    SELECT COUNT(*) 
    FROM reservas_detalle rd
    INNER JOIN reservas r ON r.idreserva = rd.idreserva
    WHERE r.fecha_entrada = CURDATE()
      AND rd.id_habitacion = h.idhabitacion 
    LIMIT 1
  ) AS incluye_limpieza,

  -- Cliente actual
  (
    SELECT CONCAT(c.nombre, ' ', c.p_apellido)
    FROM reservas r
    INNER JOIN reservas_detalle rd ON r.idreserva = rd.idreserva
    INNER JOIN clientes c ON c.usuario = r.id_cliente
    WHERE r.estado IN ('confirmada', 'checkin')
      AND CURDATE() BETWEEN r.fecha_entrada AND r.fecha_salida
      AND rd.id_habitacion = h.idhabitacion
    ORDER BY r.estado DESC, r.fecha_entrada ASC
    LIMIT 1
  ) AS cliente_actual

FROM habitaciones h
LEFT JOIN reservas_activas ra ON ra.id_habitacion = h.idhabitacion AND ra.prioridad = 1
WHERE h.habilitada = 1
ORDER BY CAST(h.numero AS UNSIGNED)
");




$reservasProximas = mysqli_query($conection, "
SELECT 
    r.idreserva, 
    r.fecha_entrada, 
    r.fecha_salida, 
    r.estado,
    r.total,
    IFNULL(SUM(p.monto), 0) AS abono,
    (r.total - IFNULL(SUM(p.monto), 0)) AS saldo,
    CONCAT(c.nombre, ' ', c.p_apellido) AS cliente,
    (
        SELECT GROUP_CONCAT(DISTINCT h.numero ORDER BY h.numero SEPARATOR ', ')
        FROM reservas_detalle rd
        INNER JOIN habitaciones h ON h.idhabitacion = rd.id_habitacion
        WHERE rd.idreserva = r.idreserva
    ) AS habitaciones
FROM reservas r
INNER JOIN clientes c ON c.usuario = r.id_cliente
LEFT JOIN reservas_pagos p ON r.idreserva = p.idreserva
WHERE r.fecha_entrada >= CURDATE()
  AND r.estado IN ('pendiente','confirmada')
GROUP BY r.idreserva
ORDER BY r.fecha_entrada ASC
");


$desayunosQuery = mysqli_query($conection, "
SELECT 
    h.numero AS habitacion,
    SUM(rd.adultos + rd.ninos) AS total_desayunos
FROM reservas_detalle rd
INNER JOIN reservas r ON rd.idreserva = r.idreserva
INNER JOIN habitaciones h ON rd.id_habitacion = h.idhabitacion
WHERE 
    rd.incluye_desayuno = 1
    AND r.estado = 'checkin'
    AND CURDATE() BETWEEN DATE_ADD(r.fecha_entrada, INTERVAL 1 DAY) AND r.fecha_salida
GROUP BY h.numero
ORDER BY h.numero
");


// TICKETS DE TOUR HOY
$toursQuery = mysqli_query($conection, "
  SELECT 
    h.numero AS habitacion, 
    rd.lugar_tour,
    SUM(rd.adultos + rd.ninos) AS total_personas
  FROM reservas_detalle rd
  INNER JOIN reservas r ON rd.idreserva = r.idreserva
  INNER JOIN habitaciones h ON rd.id_habitacion = h.idhabitacion
  WHERE rd.incluye_tour = 1
    AND (
      (r.estado = 'checkin' AND CURDATE() BETWEEN r.fecha_entrada AND r.fecha_salida)
      OR (r.estado = 'confirmada' AND r.fecha_entrada = CURDATE())
    )
  GROUP BY h.numero, rd.lugar_tour
");


$garajeQuery = mysqli_query($conection, "
  SELECT h.numero AS habitacion, rd.garaje
  FROM reservas_detalle rd
  INNER JOIN reservas r ON rd.idreserva = r.idreserva
  INNER JOIN habitaciones h ON rd.id_habitacion = h.idhabitacion
  WHERE rd.garaje > 0
    AND (
        (r.estado = 'checkin' AND CURDATE() >= r.fecha_entrada AND CURDATE() < r.fecha_salida)
        OR (r.estado = 'confirmada' AND r.fecha_entrada = CURDATE())
    )
");






$desayunos =  '';

$desayunos .= '
<div id="bloqueDesayunos">
  <div class="desayuno-header">
    <strong>🍽️ Desayunos programados para hoy:</strong>
    <div class="desayuno-controles">
      <button class="btn-imprimir-hoy" onclick="imprimirDesayunos()">🖨️ Hoy</button>
      <input type="date" id="fecha_desayuno" min="' . date('Y-m-d', strtotime('+1 day')) . '">
      <button class="btn-ver-fecha" onclick="verDesayunosPorFecha()">📅 Ver</button>
    </div>
  </div>
  <br>
  <div id="resultado_desayunos_fecha" style="margin-top:10px;"></div>
';




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

  <!-- FullCalendar + Plugins -->



  <?php
include "includes/scripts.php";
verificarSesionPOS();?>

  <style>
    html,
    body {
      margin: 0;
      padding: 0;
      height: 100%;
      font-family: sans-serif;
      background: #f0f2f5;
      overflow: hidden;
      font-size: 12px;
    }

    #calendar {

      max-height: 300px;
      background: white;
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
      flex: 5;
      display: flex;
      flex-direction: column;
      overflow-y: auto;
      background: #fff;
      border-radius: 6px;
      padding: 10px;
    }

    /* Bloque lateral derecho (30%) */
    .panel-secundario {
      flex: 5;
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
    .verde {
      background: #28a745;
      color: white;
    }

    .amarillo {
      background: #ffc107;
      color: black;
    }

    .rojo {
      background: #dc3545;
      color: white;
    }

    .naranja {
      background: #fd7e14;
      color: white;
    }

    .gris {
      background: #6c757d;
      color: white;
    }

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

    .resumen-box.total {
      background: #e9ecef;
    }

    .resumen-box.ocupadas {
      background: #f8d7da;
    }

    .resumen-box.disponibles {
      background: #d4edda;
    }

    .resumen-box.mantenimiento {
      background: #dee2e6;
    }

    .resumen-box.porcentaje {
      background: #d1ecf1;
    }

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
      padding: 10px 15px;
      background: #fff8e1;
      border: 1px solid #ffe082;
      border-radius: 6px;
      font-size: 15px;
      margin-bottom: 10px;
    }

    #bloqueDesayunos strong {
      font-size: 16px;
    }

    #bloqueDesayunos .desayuno-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 8px;
    }

    #bloqueDesayunos .desayuno-controles {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    #bloqueDesayunos button {
      padding: 4px 10px;
      font-size: 13px;
      border: none;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.2s ease, opacity 0.2s ease;
    }

    #bloqueDesayunos button:hover {
      opacity: 0.9;
    }

    #bloqueDesayunos .btn-imprimir-hoy {
      background: #fbc02d;
      color: black;
    }

    #bloqueDesayunos .btn-ver-fecha {
      background: #03a9f4;
      color: white;
    }

    #bloqueDesayunos input[type="date"] {
      padding: 4px 8px;
      font-size: 13px;
      border: 1px solid #ccc;
      border-radius: 4px;
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

    .habitacion-cliente {
      font-size: 11px;
      margin-top: -2px;
      line-height: 1.2;
      color: #f0f0f0;
      /* o blanco claro si fondo oscuro */
      font-weight: normal;
      text-shadow: 0 0 1px rgba(0, 0, 0, 0.5);
    }

    /* Bloques de tours y garaje (estructura igual a desayunos) */
    #bloqueTours,
    #bloqueGaraje {
      padding: 10px 15px;
      background: #e8f5e9;
      /* verde claro para tours */
      border: 1px solid #c8e6c9;
      border-radius: 6px;
      font-size: 15px;
      margin-bottom: 10px;
    }

    #bloqueGaraje {
      background: #f3e5f5;
      /* lila claro para garaje */
      border: 1px solid #ce93d8;
    }

    #bloqueTours strong,
    #bloqueGaraje strong {
      font-size: 16px;
    }

    #bloqueTours .desayuno-header,
    #bloqueGaraje .desayuno-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 8px;
    }

    #bloqueTours .desayuno-controles,
    #bloqueGaraje .desayuno-controles {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    #bloqueTours button,
    #bloqueGaraje button {
      padding: 4px 10px;
      font-size: 13px;
      border: none;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.2s ease, opacity 0.2s ease;
    }

    #bloqueTours button:hover,
    #bloqueGaraje button:hover {
      opacity: 0.9;
    }

    #bloqueTours .btn-imprimir-hoy {
      background: #66bb6a;
      color: white;
    }

    #bloqueGaraje .btn-imprimir-hoy {
      background: #ab47bc;
      color: white;
    }

    #bloqueTours .btn-ver-fecha,
    #bloqueGaraje .btn-ver-fecha {
      background: #03a9f4;
      color: white;
    }

    #bloqueTours input[type="date"],
    #bloqueGaraje input[type="date"] {
      padding: 4px 8px;
      font-size: 13px;
      border: 1px solid #ccc;
      border-radius: 4px;
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
          <button class="btn-refresh" onclick="location.href='index.php'">
            <i class="fas fa-home"></i> Home
          </button>

          <button class="btn-refresh" onclick="location.reload()">🔄 Actualizar</button>
          <button class="btn-refresh" style="background:#28a745;" onclick="anadirForm('formReserva')">➕ Añadir
            Reserva</button>
          <button class="btn-refresh" style="background:#17a2b8;" onclick="anadirForm('formCliente')">👥 Añadir
            Persona</button>
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
    $esMediodiaPasado = (int)date('H') >= 12;
    $alerta_checkout = ($hab['salida_hoy'] && $esMediodiaPasado);

    $mostrarBotonFaltante = false;
    $faltante = 0;
    $reserva_id = null;

    $reserva_activa = $hab['reserva_activa'];
    $estado_reserva = $hab['estado_reserva'];
    $total = 0;
    $abono = 0;

    if ($reserva_activa) {
        $reserva_id = $reserva_activa;

        // Obtener total y abono
        $res = mysqli_query($conection, "
            SELECT r.total, IFNULL(SUM(p.monto), 0) AS abono
            FROM reservas r
            LEFT JOIN reservas_pagos p ON r.idreserva = p.idreserva
            WHERE r.idreserva = $reserva_activa
            GROUP BY r.idreserva
        ");
        if ($res && mysqli_num_rows($res)) {
            $data = mysqli_fetch_assoc($res);
            $total = floatval($data['total']);
            $abono = floatval($data['abono']);
            $faltante = $total - $abono;
        }

        // Determinar estado visual y botones según estado real
        if ($estado_reserva === 'checkin') {
            if ($hab['salida_hoy']) {
                $estado = 'salida';
                $colorClass = 'naranja';
                $alerta_checkout = $esMediodiaPasado;
            } else {
                $estado = 'ocupada';
                $colorClass = 'rojo';
            }

            $boton = '
                <button class="btn-habitacion" onclick="confirmarCheckout(' . $reserva_id . ', ' . $total . ', ' . $abono . ')">Check-Out</button>
                <button class="btn-habitacion" style="margin-top: 5px;" onclick="window.open(\'pdf/reservas/verReservaPDF.php?id=' . $reserva_id . '\', \'_blank\')">🧾 Ver</button>
                <button class="btn-habitacion" style="margin-top: 5px;" onclick="reimprimirComprobanteEstadia(' . $reserva_id . ')">🖨️ Contrato</button>
                <button class="btn-habitacion" style="margin-top: 5px;" onclick="reimprimirComprobanteEstadiaCLiente(' . $reserva_id . ')">🖨️ Nota de Venta</button>
                <button class="btn-habitacion" style="margin-top: 5px;" onclick="reimprimirTicketsTourYGaraje(' . $reserva_id . ')">🖨️ Tours o Garaje</button>';
        } elseif ($estado_reserva === 'confirmada') {
            $estado = 'reservada';
            $colorClass = 'amarillo';
            $boton = '<button class="btn-habitacion" onclick="cambiarEstadoReserva(' . $reserva_id . ', \'checkin\')">Check-In</button>';
        }

        if ($faltante > 0.01) {
            $mostrarBotonFaltante = true;
        }
    } elseif ($hab['estado_habitacion'] === 'mantenimiento') {
        $estado = 'mantenimiento';
        $colorClass = 'gris';
        $boton = '<span class="badge gris">Mantenimiento</span>';
    }

    // Renderizar HTML de tarjeta:
    ?>
          <div class="habitacion-card <?= $colorClass ?>">
            <span class="badge"><?= ucfirst($estado) ?></span>
            <h4>Hab. <?= $hab['numero'] ?></h4>

            <?php if (!empty($hab['cliente_actual'])): ?>
            <div class="habitacion-cliente">
              <?= htmlspecialchars($hab['cliente_actual']) ?>
            </div>
            <?php endif; ?>

            <?= $boton ?>

            <?php if ($mostrarBotonFaltante): ?>
            <button class="btn-habitacion btn_abono"
              data-id="<?= $reserva_id ?>"
              data-cliente="<?= htmlspecialchars($hab['cliente_actual'] ?? '') ?>"
              data-total="<?= number_format($total, 2) ?>"
              data-abono="<?= number_format($abono, 2) ?>"
              data-saldo="<?= number_format($faltante, 2) ?>"
              style="margin-top: 5px; background:#ffc107; color:black;">
              💰 Falta $<?= number_format($faltante, 2) ?>
            </button>
            <?php endif; ?>

            <?php if ($alerta_checkout): ?>
            <span title="Check-out pendiente" style="font-size:20px;">⚠️</span>
            <?php endif; ?>

            <?php if (!$hab['abono_registrado'] && $estado === 'reservada'): ?>
            <span title="Sin abono registrado" style="font-size:18px;">🔴</span>
            <?php endif; ?>

            <div style="margin-top:10px; font-size:18px;">
              <?php if ($estado !== 'disponible' && $estado !== 'mantenimiento'): ?>
              <?php if ($hab['incluye_desayuno']) {
                  echo '<span title="Incluye desayuno">🥐</span>';
              } ?>
              <?php if ($hab['incluye_tour']) {
                  echo '<span title="Incluye tour">🗺️</span>';
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

      <!-- 🗺️ Tours Programados -->
      <div id="bloqueTours" class="bloque-servicio">
        <div class="desayuno-header">
          <strong>🗺️ Tours programados para hoy:</strong>
          <div class="desayuno-controles">
            <button class="btn-imprimir-hoy" onclick="imprimirTours()">🖨️ Hoy</button>
            <input type="date" id="fecha_tour"
              min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            <button class="btn-ver-fecha" onclick="verToursPorFecha()">📅 Ver</button>
          </div>
        </div>
        <br>
        <?php if ($toursQuery && mysqli_num_rows($toursQuery) > 0): ?>
        <ul style="margin-left:20px;">
          <?php while ($row = mysqli_fetch_assoc($toursQuery)):
              $habitacion = $row['habitacion'];
              $personas = $row['total_personas'];
              $ids_lugares = explode(',', $row['lugar_tour']);
              $nombres_lugares = [];

              foreach ($ids_lugares as $id_lugar) {
                  $id_lugar = intval(trim($id_lugar));
                  if ($id_lugar > 0) {
                      $res = mysqli_query($conection, "SELECT nombre FROM lugares_tour WHERE id = $id_lugar LIMIT 1");
                      if ($res && mysqli_num_rows($res)) {
                          $nombres_lugares[] = mysqli_fetch_assoc($res)['nombre'];
                      }
                  }
              }

              $lugaresTexto = count($nombres_lugares) > 0 ? implode(', ', $nombres_lugares) : 'No definido';
              ?>
          <li>
            <strong>Hab. <?= $habitacion ?>:</strong>
            <?= $personas ?> persona(s) –
            <?= $lugaresTexto ?>
          </li>
          <?php endwhile; ?>
        </ul>
        <?php else: ?>
        <p style="margin-left:10px;">No hay tours programados para hoy.</p>
        <?php endif; ?>
        <div id="resultado_tours_fecha"></div>
      </div>


      <!-- 🚗 Garaje Programado -->
      <div id="bloqueGaraje" class="bloque-servicio">
        <div class="desayuno-header">
          <strong>🚗 Garajes registrados para hoy:</strong>
          <div class="desayuno-controles">
            <button class="btn-imprimir-hoy" onclick="imprimirGaraje()">🖨️ Hoy</button>
            <input type="date" id="fecha_garaje"
              min="<?= date('Y-m-d') ?>">
            <button class="btn-ver-fecha" onclick="verGarajePorFecha()">📅 Ver</button>
          </div>
        </div>
        <br>
        <?php if ($garajeQuery && mysqli_num_rows($garajeQuery) > 0): ?>
        <ul style="margin-left:20px;">
          <?php while ($row = mysqli_fetch_assoc($garajeQuery)): ?>
          <li><strong>Hab.
              <?= $row['habitacion'] ?></strong>

          </li>
          <?php endwhile; ?>
        </ul>
        <?php else: ?>
        <p style="margin-left:10px;">No hay garajes registrados para hoy.</p>
        <?php endif; ?>
        <div id="resultado_garaje_fecha"></div>
      </div>


      <div class="seccion-reservas">
        <h3>📅 Reservas próximas</h3>
        <table border="1" cellpadding="8" cellspacing="0" style="width:100%;border-collapse:collapse;"
          id="tablaFuturas">
          <thead style="background:#eee;">
            <tr>
              <th>ID</th>
              <th>Cliente</th>
              <th>IN</th>
              <th>OUT</th>
              <th>Hab.</th>
              <th>Estado</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php

                      $reservas_array = [];
while ($r = mysqli_fetch_assoc($reservasProximas)) {
    $reservas_array[] = $r;
}
?>
            <?php foreach ($reservas_array as $r): ?>
            <tr>

              <td>
                <?= htmlspecialchars($r['idreserva']) ?>
              </td>
              <td>
                <?= htmlspecialchars($r['cliente']) ?>
              </td>
              <td><?= $r['fecha_entrada'] ?></td>
              <td><?= $r['fecha_salida'] ?></td>
              <td>
                <?= htmlspecialchars($r['habitaciones']) ?>
              </td>
              <td><?= ucfirst($r['estado']) ?>
              </td>
              <td>
                <button class="btn btn_editar"
                  onclick="window.open('pdf/reservas/verReservaPDF.php?id=<?= $r['idreserva'] ?>', '_blank')"><i
                    class="fas fa-eye"></i></button>
                <button class="btn btn_abono btn_ver"
                  data-id="<?= $r["idreserva"]; ?>"
                  data-cliente="<?= htmlspecialchars($r["cliente"]); ?>"
                  data-total="<?= number_format($r["total"], 2); ?>"
                  data-abono="<?= number_format($r["abono"], 2); ?>"
                  data-saldo="<?= number_format($r["saldo"], 2); ?>">
                  <i class="fas fa-file-invoice-dollar"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>

          </tbody>
        </table>
      </div>


    </div>

  </div>

</body>

</html>


<script>
  document.addEventListener("DOMContentLoaded", function() {
    const calendarEl = document.getElementById("calendar");

    if (!calendarEl) return;

    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      locale: 'es',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,listMonth'
      },
      events: [
        <?php
        foreach ($reservas_array as $r) {
            $color = '#17a2b8'; // pendiente
            if ($r['estado'] == 'confirmada') {
                $color = '#ffc107';
            }
            if ($r['estado'] == 'checkin') {
                $color = '#28a745';
            }

            echo json_encode([
                'id' => $r['idreserva'] . '_cal',
                'title' => $r['cliente'] . ' - ' . $r['habitaciones'],
                'start' => $r['fecha_entrada'],
                'end' => date('Y-m-d', strtotime($r['fecha_salida'] . ' +1 day')),
                'url' => "pdf/reservas/verReservaPDF.php?id={$r['idreserva']}",
                'description' => ucfirst($r['estado']),
                'color' => $color
            ], JSON_UNESCAPED_UNICODE) . ',';
        }
?>
      ],
      eventClick: function(info) {
        info.jsEvent.preventDefault();
        if (info.event.url) {
          window.open(info.event.url, '_blank');
        }
      },
      eventDidMount: function(info) {
        if (info.event.extendedProps.description) {
          tippy(info.el, {
            content: info.event.extendedProps.description,
            placement: 'top',
            theme: 'light-border',
          });
        }
      }
    });

    setTimeout(() => {
      calendar.render(); // dentro del evento DOMContentLoaded
    }, 100);

  });

  setInterval(() => {
    const modalAbierto = document.querySelector('.modal')?.style.display === 'block';
    const swalVisible = !!document.querySelector('.swal2-container');

    if (!modalAbierto && !swalVisible) {
      location.reload();
    }
  }, 600000);

  $(document).ready(function() {
    $('#tablaFuturas').DataTable({
      pageLength: 5,
      language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
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
        console.log(response);

        $('.modal .bodyModal').html(response);
        $('.modal').fadeIn('fast', function() {
          // Activar select2 si existe
          if ($('.js-example-basic-single').length) {
            $('.js-example-basic-single').select2({
              width: '100%',
              dropdownParent: $('.modal')
            });
          }
        });
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
          mostrarProcesando('Actualizando Estado', 'Espere por favor...');
          $.post('ajax.php', {
            action: 'cambiarEstadoReserva',
            idreserva: id,
            estado: 'checkin'
          }, function(response) {
            if (response.trim() === 'ok') {
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
            if (response.trim() === 'ok') {
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
          if (response.trim() === 'ok') {
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

  function verDesayunosPorFecha() {
    const fecha = document.getElementById("fecha_desayuno").value;

    if (!fecha) {
      Swal.fire("⚠️ Fecha requerida", "Por favor selecciona una fecha.", "warning");
      return;
    }

    fetch("ajax.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `action=verDesayunosPorFecha&fecha=${fecha}`
      })
      .then(res => res.text())
      .then(html => {
        document.getElementById("resultado_desayunos_fecha").innerHTML = html;
      })
      .catch(() => {
        Swal.fire("Error", "No se pudo consultar los desayunos.", "error");
      });
  }

  function mostrarProcesando(titulo = 'Procesando...', mensaje = 'Por favor espere...') {
    Swal.fire({
      title: titulo,
      html: mensaje,
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
  }

  function reimprimirTicketHabitacion(idreserva) {
    mostrarProcesando('Reimprimiendo...', 'Por favor espere...');

    $.post('ajax.php', {
      action: 'reimprimirTicketReserva',
      idreserva: idreserva
    }, function(resp) {
      if (resp.trim() === 'ok') {
        Swal.fire('✅ Comprobante enviado', 'Se ha enviado el ticket a la impresora', 'success');
      } else {
        Swal.fire('❌ Error', resp, 'error');
      }
    }).fail(() => {
      Swal.fire('❌ Error', 'No se pudo conectar con el servidor', 'error');
    });
  }

  function imprimirTours() {
    fetch("ajax.php", {
        method: "POST",
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: "action=imprimirTours"
      })
      .then(res => res.text())
      .then(resp => {
        Swal.fire(resp.trim() === "ok" ? "✅ Tours impresos" : "❌ Error", resp, resp.trim() === "ok" ? "success" :
          "error");
      });
  }

  function verToursPorFecha() {
    const fecha = document.getElementById("fecha_tour").value;
    if (!fecha) return Swal.fire("Fecha requerida", "Selecciona una fecha", "warning");

    fetch("ajax.php", {
        method: "POST",
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=verToursPorFecha&fecha=${fecha}`
      })
      .then(res => res.text())
      .then(html => document.getElementById("resultado_tours_fecha").innerHTML = html);
  }

  function imprimirGaraje() {
    fetch("ajax.php", {
        method: "POST",
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: "action=imprimirGaraje"
      })
      .then(res => res.text())
      .then(resp => {
        Swal.fire(resp.trim() === "ok" ? "✅ Tickets de garaje impresos" : "❌ Error", resp, resp.trim() === "ok" ?
          "success" : "error");
      });
  }

  function verGarajePorFecha() {
    const fecha = document.getElementById("fecha_garaje").value;
    if (!fecha) return Swal.fire("Fecha requerida", "Selecciona una fecha", "warning");

    fetch("ajax.php", {
        method: "POST",
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `action=verGarajePorFecha&fecha=${fecha}`
      })
      .then(res => res.text())
      .then(html => document.getElementById("resultado_garaje_fecha").innerHTML = html);
  }

  function reimprimirComprobanteEstadia(idreserva) {
    $.ajax({
      url: 'ajax.php',
      type: 'POST',
      data: {
        id: idreserva,
        action: 'reimprimirComprobanteEstadia'
      },
      success: function(response) {
        if (response.trim() === 'ok') {
          Swal.fire('✅ Éxito', 'Comprobante reimpreso correctamente.', 'success');
        } else {
          Swal.fire('⚠️ Error', 'No se pudo imprimir el comprobante.', 'error');
          console.error(response);
        }
      },
      error: function() {
        Swal.fire('⚠️ Error', 'Error de conexión al servidor.', 'error');
      }
    });
  }

  function reimprimirComprobanteEstadiaCLiente(idreserva) {
    $.ajax({
      url: 'ajax.php',
      type: 'POST',
      data: {
        id: idreserva,
        action: 'reimprimirComprobanteEstadiaCliente'
      },
      success: function(response) {
        console.log(response);
        if (response.trim() === 'ok') {
          Swal.fire('✅ Éxito', 'Comprobante del cliente reimpreso correctamente.', 'success');
        } else {
          Swal.fire('⚠️ Error', 'No se pudo imprimir el comprobante del cliente.', 'error');
          console.error(response);
        }
      },
      error: function() {
        Swal.fire('⚠️ Error', 'Error de conexión al servidor.', 'error');
      }
    });
  }

  function reimprimirTicketsTourYGaraje(idreserva) {
    $.ajax({
      url: 'ajax.php',
      type: 'POST',
      data: {
        id: idreserva,
        action: 'reimprimirTicketsTourYGaraje'
      },
      success: function(response) {
        if (response.trim() === 'ok') {
          Swal.fire('✅ Éxito', 'Tickets de tour y garaje reimpresos correctamente.', 'success');
        } else {
          Swal.fire('⚠️ Error', 'No se pudieron imprimir los tickets.', 'error');
          console.error(response);
        }
      },
      error: function() {
        Swal.fire('⚠️ Error', 'Error de conexión al servidor.', 'error');
      }
    });
  }
</script>