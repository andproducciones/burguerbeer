<?php
session_start();
date_default_timezone_set('America/Guayaquil');
include '../conexion.php';
mysqli_set_charset($conection, 'utf8mb4');

if ($_SESSION['rol'] != 1 && $_SESSION['rol'] != 2) {
    echo "No autorizado";
    exit;
}

$hoy = date('Y-m-d');

$query = mysqli_query($conection, "
WITH
pay_reserva AS (
  SELECT idreserva, SUM(monto) AS abono
  FROM reservas_pagos
  WHERE idreserva IS NOT NULL
  GROUP BY idreserva
),
pay_detalle AS (
  SELECT id_detalle, SUM(monto) AS abono
  FROM reservas_pagos
  WHERE id_detalle IS NOT NULL
  GROUP BY id_detalle
),

-- Fuente: SIEMPRE reservas_detalle; sólo estados visibles
d_all AS (
  SELECT
    d.id_habitacion,
    d.id                         AS id_detalle,
    d.idreserva,
    d.estado_detalle             AS estado,
    d.fecha_entrada,
    d.fecha_salida,
    COALESCE(r.id_cliente, d.idcliente) AS id_cliente,
    CASE WHEN d.idreserva IS NULL THEN d.subtotal ELSE r.total END                                   AS total_doc,
    CASE WHEN d.idreserva IS NULL THEN IFNULL(pd.abono,0) ELSE IFNULL(pr.abono,0) END                AS abono_doc
  FROM reservas_detalle d
  LEFT JOIN reservas r     ON r.idreserva = d.idreserva
  LEFT JOIN pay_reserva pr ON pr.idreserva = r.idreserva
  LEFT JOIN pay_detalle pd ON pd.id_detalle = d.id
  WHERE d.estado_detalle IN ('confirmada','checkin')
),

ranked AS (
  SELECT
    o.*,
    ROW_NUMBER() OVER (
      PARTITION BY o.id_habitacion
      ORDER BY
        CASE
          WHEN (o.idreserva IS NULL AND o.estado = 'checkin') THEN 1
          WHEN (o.idreserva IS NOT NULL AND o.estado = 'checkin' AND CURDATE() BETWEEN o.fecha_entrada AND o.fecha_salida) THEN 2
          WHEN (o.estado = 'confirmada' AND o.fecha_entrada = CURDATE()) THEN 3
          WHEN (o.estado = 'confirmada' AND CURDATE() BETWEEN o.fecha_entrada AND o.fecha_salida) THEN 4
          ELSE 9
        END,
        o.fecha_entrada ASC
    ) AS prioridad
  FROM d_all o
)

SELECT
  h.idhabitacion,
  h.numero,
  h.estado AS estado_habitacion,

  -- Documento activo (detalle/reserva)
  rnk.id_detalle   AS id_detalle_activo,
  rnk.idreserva    AS idreserva_activa,
  rnk.estado       AS estado_activo,
  rnk.total_doc    AS total_salida,
  rnk.abono_doc    AS abono_salida,

  -- Flags visuales
  EXISTS (
    SELECT 1
    FROM d_all x
    WHERE x.id_habitacion = h.idhabitacion
      AND x.estado = 'checkin'
      AND (
           x.idreserva IS NULL
        OR (x.idreserva IS NOT NULL AND CURDATE() BETWEEN x.fecha_entrada AND x.fecha_salida)
      )
  ) AS flag_ocupada,

  EXISTS (
    SELECT 1
    FROM reservas_detalle d
    JOIN reservas r ON r.idreserva = d.idreserva
    WHERE d.id_habitacion = h.idhabitacion
      AND d.estado_detalle = 'confirmada'
      AND r.estado = 'confirmada'
      AND d.fecha_entrada = CURDATE()
  ) AS flag_reservada_hoy,

  EXISTS (
    SELECT 1
    FROM d_all x
    WHERE x.id_habitacion = h.idhabitacion
      AND x.estado = 'checkin'
      AND x.fecha_salida = CURDATE()
  ) AS flag_salida_hoy,

  -- IDs útiles
  (
    SELECT r.idreserva
    FROM reservas_detalle d
    JOIN reservas r ON r.idreserva = d.idreserva
    WHERE d.id_habitacion = h.idhabitacion
      AND d.estado_detalle = 'checkin'
      AND CURDATE() BETWEEN d.fecha_entrada AND d.fecha_salida
    ORDER BY d.fecha_entrada DESC
    LIMIT 1
  ) AS idreserva_checkout,

  (
    SELECT d.id
    FROM reservas_detalle d
    WHERE d.id_habitacion = h.idhabitacion
      AND d.idreserva IS NULL
      AND d.estado_detalle = 'checkin'
    ORDER BY d.fecha_entrada DESC
    LIMIT 1
  ) AS iddetalle_checkin_directo,

  -- Iconos (desayuno/tour/garaje)
  (
    SELECT COUNT(*)
    FROM reservas_detalle d
    WHERE d.id_habitacion = h.idhabitacion
      AND d.incluye_desayuno = 1
      AND (
          (d.estado_detalle = 'checkin'    AND CURDATE() BETWEEN d.fecha_entrada AND d.fecha_salida)
       OR (d.estado_detalle = 'confirmada' AND d.fecha_entrada = CURDATE())
      )
  ) AS incluye_desayuno,

  (
    SELECT COUNT(*)
    FROM reservas_detalle d
    WHERE d.id_habitacion = h.idhabitacion
      AND d.incluye_tour = 1
      AND (
          (d.estado_detalle = 'checkin'    AND CURDATE() BETWEEN d.fecha_entrada AND d.fecha_salida)
       OR (d.estado_detalle = 'confirmada' AND d.fecha_entrada = CURDATE())
      )
  ) AS incluye_tour,

  (
    SELECT COUNT(*)
    FROM reservas_detalle d
    WHERE d.id_habitacion = h.idhabitacion
      AND d.garaje > 0
      AND (
          (d.estado_detalle = 'checkin'    AND CURDATE() BETWEEN d.fecha_entrada AND d.fecha_salida)
       OR (d.estado_detalle = 'confirmada' AND d.fecha_entrada = CURDATE())
      )
  ) AS incluye_garaje,

  -- Cliente de hoy (prioridad checkin)
  (
    SELECT CONCAT(c.nombre,' ',c.p_apellido)
    FROM (
      SELECT d.idcliente AS usuario_cli, d.estado_detalle, d.fecha_entrada, d.idreserva
      FROM reservas_detalle d
      WHERE d.id_habitacion = h.idhabitacion
        AND (
             (d.estado_detalle = 'checkin'    AND CURDATE() BETWEEN d.fecha_entrada AND d.fecha_salida)
          OR (d.estado_detalle = 'confirmada' AND d.fecha_entrada = CURDATE())
        )
      ORDER BY CASE d.estado_detalle WHEN 'checkin' THEN 1 ELSE 2 END, d.fecha_entrada ASC
      LIMIT 1
    ) z
    LEFT JOIN reservas r ON r.idreserva = z.idreserva
    LEFT JOIN clientes c ON c.usuario = COALESCE(r.id_cliente, z.usuario_cli)
  ) AS cliente_actual

FROM habitaciones h
LEFT JOIN ranked rnk
  ON rnk.id_habitacion = h.idhabitacion
 AND rnk.prioridad = 1
WHERE h.habilitada = 1
ORDER BY CAST(h.numero AS UNSIGNED)
");

/* =============================== *
 *  RESERVAS PRÓXIMAS (calendario)
 * =============================== */
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

/* =============================== *
 *  DESAYUNOS / TOURS / GARAJE HOY
 * =============================== */
$desayunosQuery = mysqli_query($conection, "
  SELECT 
    h.numero AS habitacion,
    SUM(x.personas) AS total_desayunos
  FROM (
    SELECT d.id_habitacion, (d.adultos + d.ninos) AS personas
    FROM reservas_detalle d
    WHERE d.incluye_desayuno = 1
      AND d.idreserva IS NULL
      AND d.estado_detalle = 'checkin'
      AND CURDATE() > d.fecha_entrada
      AND CURDATE() <= d.fecha_salida
    UNION ALL
    SELECT d.id_habitacion, (d.adultos + d.ninos) AS personas
    FROM reservas_detalle d
    JOIN reservas r ON r.idreserva = d.idreserva
    WHERE d.incluye_desayuno = 1
      AND r.estado = 'checkin'
      AND CURDATE() > r.fecha_entrada
      AND CURDATE() <= r.fecha_salida
  ) x
  JOIN habitaciones h ON h.idhabitacion = x.id_habitacion
  GROUP BY h.numero
  ORDER BY CAST(h.numero AS UNSIGNED)
");

$toursQuery = mysqli_query($conection, "
  SELECT
  h.numero AS habitacion,
  COALESCE(NULLIF(TRIM(dl.lugares_nombres), ''), 'SIN_LUGAR') AS lugares,
  SUM(d.adultos + d.ninos) AS total_personas
FROM reservas_detalle AS d
JOIN habitaciones AS h
  ON h.idhabitacion = d.id_habitacion
LEFT JOIN (
  SELECT
    d2.id,
    GROUP_CONCAT(DISTINCT lt.nombre ORDER BY lt.nombre SEPARATOR ', ') AS lugares_nombres
  FROM reservas_detalle AS d2
  LEFT JOIN lugares_tour AS lt
    ON lt.id IS NOT NULL AND FIND_IN_SET(lt.id, d2.lugar_tour)
  WHERE d2.incluye_tour = 1
  GROUP BY d2.id
) AS dl
  ON dl.id = d.id
WHERE
  d.incluye_tour   = 1
  AND d.estado_detalle = 'checkin'
  AND CURDATE() BETWEEN d.fecha_entrada AND d.fecha_salida
GROUP BY
  h.numero, COALESCE(NULLIF(dl.lugares_nombres, ''), 'SIN_LUGAR')
ORDER BY
  CAST(h.numero AS UNSIGNED),
  lugares;
");

$garajeQuery = mysqli_query($conection, "
  SELECT
    h.numero AS habitacion
  FROM reservas_detalle d
  JOIN habitaciones h ON h.idhabitacion = d.id_habitacion
  WHERE
    d.estado_detalle = 'checkin'
    AND d.garaje > 0
    AND CURDATE() >= DATE(d.fecha_entrada)
    AND CURDATE() <  DATE(d.fecha_salida)
  GROUP BY h.numero
  ORDER BY CAST(h.numero AS UNSIGNED)
");

// Fecha mínima (mañana)
$minDateDes = date('Y-m-d', strtotime('+1 day'));

// Construir el listado de HOY
$listaHoyDes = '';
if (isset($desayunosQuery) && $desayunosQuery && mysqli_num_rows($desayunosQuery) > 0) {
    $listaHoyDes .= '<ul style="margin-left:20px;">';
    while ($row = mysqli_fetch_assoc($desayunosQuery)) {
        $hab  = htmlspecialchars($row['habitacion'] ?? '', ENT_QUOTES, 'UTF-8');
        $cant = (int)($row['total_desayunos'] ?? 0);
        $listaHoyDes .= "<li><strong>Hab. {$hab}:</strong> {$cant} desayuno(s)</li>";
    }
    $listaHoyDes .= '</ul>';
    // Liberar el result set si no se usará más
    @mysqli_free_result($desayunosQuery);
} else {
    $listaHoyDes = '<p style="margin-left:10px;">No hay desayunos programados para hoy.</p>';
}

// BLOQUE FINAL (no agregues nada más después)
$desayunos = '
<div id="bloqueDesayunos">
  <div class="desayuno-header">
    <strong>🍽️ Desayunos hoy:</strong>
    <div class="desayuno-controles">
      <button class="btn-imprimir-hoy" onclick="imprimirDesayunos()">🖨️ Hoy</button>
      <input type="date" id="fecha_desayuno" min="' . $minDateDes . '">
      <button class="btn-ver-fecha" onclick="verDesayunosPorFecha()">📅 Ver</button>
    </div>
  </div>
  <br>

  <!-- Contenedor de HOY: se vacía desde verDesayunosPorFecha() -->
  <div id="desayunos_hoy_lista">
    ' . $listaHoyDes . '
  </div>

  <!-- Resultado por FECHA (reemplaza al de HOY) -->
  <div id="resultado_desayunos_fecha" style="margin-top:10px;"></div>
</div>
';



$reservas_array = [];
while ($r = mysqli_fetch_assoc($reservasProximas)) {
    $reservas_array[] = $r;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <title>Panel de Habitaciones</title>
  <?php
  include "includes/functions.php";
verificarSesionPOS();
?>

  <!-- ✅ CSS -->
  <link rel="stylesheet" href="./css/style.css">
  <link rel="stylesheet" href="./css/responsive.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">

  <!-- DataTables core + Buttons (versiones compatibles) -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

  <!-- Select2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

  <!-- FullCalendar CSS (core) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css">

  <!-- Tippy CSS (para theme 'light-border') -->
  <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css">
  <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/themes/light-border.css">

  <!-- Font Awesome (para <i class="fas ...">) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- (Opcional) Favicon -->
  <link rel="icon" href="img/ala.ico" type="image/x-icon">


  <!-- ✅ JS base (en orden) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    /* Aliases para markup v5 (fas/far/fab) con FA6 */
    .fas {
      font-family: "Font Awesome 6 Free";
      font-weight: 900;
    }

    .far {
      font-family: "Font Awesome 6 Free";
      font-weight: 400;
    }

    .fab {
      font-family: "Font Awesome 6 Brands";
      font-weight: 400;
    }
  </style>

  <!-- DataTables + Buttons (versiones compatibles con 1.13.6) -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

  <!-- SweetAlert2 + Select2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <!-- ✅ Tippy (tooltips) -->
  <script src="https://unpkg.com/@popperjs/core@2"></script>
  <script src="https://unpkg.com/tippy.js@6"></script>

  <!-- ✅ FullCalendar Core + Plugins (mismas versiones) -->
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.10/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/list@6.1.10/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales-all.global.min.js"></script>

  <!-- Scripts propios del proyecto -->
  <script src="./js/functions.js"></script>

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

    .container {
      display: flex;
      flex-direction: row;
      height: 100vh;
      padding: 10px;
      box-sizing: border-box;
      gap: 10px;
    }

    .panel-habitaciones {
      flex: 5;
      display: flex;
      flex-direction: column;
      overflow-y: auto;
      background: #fff;
      border-radius: 6px;
      padding: 10px;
    }

    .panel-secundario {
      flex: 5;
      display: flex;
      flex-direction: column;
      gap: 10px;
      overflow-y: auto;
    }

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
      transition: all .3s ease;
      color: #fff;
      font-size: 12px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, .1);
    }

    .habitacion-card h4 {
      margin: 4px 0;
      font-size: 14px;
    }

    .habitacion-card .badge {
      background: rgba(255, 255, 255, .2);
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
      background: #fff;
      color: #333;
      font-weight: bold;
    }

    .btn-habitacion:hover {
      opacity: .85;
    }

    .verde {
      background: #28a745;
    }

    .amarillo {
      background: #ffc107;
      color: #000;
    }

    .rojo {
      background: #dc3545;
    }

    .naranja {
      background: #fd7e14;
    }

    .gris {
      background: #6c757d;
    }

    .toolbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }

    .btn-refresh {
      background: #007bff;
      color: #fff;
      border: none;
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: bold;
      cursor: pointer;
    }

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

    /* ======= Bloques de servicios (Desayunos/Tours/Garaje) ======= */
    /* Contenedores con color propio */
    #bloqueDesayunos {
      padding: 5px 10px;
      background: #fff8e1;
      border: 1px solid #ffe082;
      border-radius: 6px;
      font-size: 12px;

    }

    #bloqueTours {
      padding: 5px 10px;
      background: #e8f5e9;
      border: 1px solid #c8e6c9;
      border-radius: 6px;
      font-size: 12px;

    }

    #bloqueGaraje {
      padding: 5px 10px;
      background: #f3e5f5;
      border: 1px solid #ce93d8;
      border-radius: 6px;
      font-size: 12px;

    }

    /* Cabecera y controles (GENÉRICO) */
    .bloque-servicio .desayuno-header,
    #bloqueDesayunos .desayuno-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 5px;
    }

    .bloque-servicio .desayuno-controles,
    #bloqueDesayunos .desayuno-controles {
      display: flex;
      align-items: center;
      gap: 5px;
    }

    /* Botones y fecha (GENÉRICO) */
    .bloque-servicio button,
    #bloqueDesayunos button {
      padding: 4px 10px;
      font-size: 13px;
      border: none;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color .2s, opacity .2s;
    }

    .bloque-servicio .btn-imprimir-hoy,
    #bloqueDesayunos .btn-imprimir-hoy {
      background: #fbc02d;
      color: #000;
    }

    .bloque-servicio .btn-ver-fecha,
    #bloqueDesayunos .btn-ver-fecha {
      background: #03a9f4;
      color: #fff;
    }

    .bloque-servicio input[type="date"],
    #bloqueDesayunos input[type="date"] {
      height: 28px;
      padding: 2px 6px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 13px;
      background: #fff;
    }

    /* Modal */
    .modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100vh;
      background: rgba(0, 0, 0, .81);
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
      font-weight: normal;
      text-shadow: 0 0 1px rgba(0, 0, 0, .5);
    }

    /* ======= Calendario ======= */
    #calendar {
      height: 600px;
      background: #fff;
      border-radius: 6px;
      padding: 6px;
    }

    /* ======= Layout fila de servicios ======= */
    .fila-servicios {
      display: flex;
      gap: 5px;
      align-items: stretch;
      margin-bottom: 5px;
      max-width: 100%;
    }

    .fila-servicios .bloque-servicio {
      flex: 1 1 0;

    }

    /* Responsive */
    @media (max-width:1100px) {
      .fila-servicios {
        flex-wrap: wrap;
      }
    }

    @media (max-width:700px) {
      .fila-servicios {
        flex-direction: column;
      }
    }
  </style>

</head>



<body>
  <div class="modal">
    <div class="bodyModal"></div>
  </div>
  <div class="container">
    <div class="panel-habitaciones">
      <div class="toolbar">
        <h2>Habitaciones</h2>
        <div style="display:flex; gap:10px;">
          <button class="btn-refresh" onclick="location.href='index.php'"><i class="fas fa-home"></i> Home</button>
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
        <div class="resumen-box porcentaje">Ocupación Global: <span id="porcentajeOcupacion">0%</span></div>
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
          <?php while ($hab = mysqli_fetch_assoc($query)):
              $estado     = 'disponible';
              $colorClass = 'verde';

              $ocupada        = !empty($hab['flag_ocupada']);
              $reservada_hoy  = !empty($hab['flag_reservada_hoy']);
              $salida_hoy     = !empty($hab['flag_salida_hoy']);

              $id_detalle_activo      = !empty($hab['id_detalle_activo']) ? (int)$hab['id_detalle_activo'] : 0;
              $idreserva_activa       = !empty($hab['idreserva_activa']) ? (int)$hab['idreserva_activa'] : 0;
              $idreserva_checkout     = !empty($hab['idreserva_checkout']) ? (int)$hab['idreserva_checkout'] : null;
              $iddetalle_checkin_dir  = !empty($hab['iddetalle_checkin_directo']) ? (int)$hab['iddetalle_checkin_directo'] : null;

              $total    = isset($hab['total_salida']) ? (float)$hab['total_salida'] : 0.0;
              $abono    = isset($hab['abono_salida']) ? (float)$hab['abono_salida'] : 0.0;
              $faltante = $total - $abono;

              if ($ocupada) {
                  $estado = 'ocupada';
                  // Naranja si sale hoy, rojo de lo contrario
                  $colorClass = $salida_hoy ? 'naranja' : 'rojo';
              } elseif ($reservada_hoy) {
                  $estado = 'reservada';
                  $colorClass = 'amarillo';
              } elseif (($hab['estado_habitacion'] ?? '') === 'mantenimiento') {
                  $estado = 'mantenimiento';
                  $colorClass = 'gris';
              } else {
                  $estado = 'disponible';
                  $colorClass = 'verde';
              }

              // Botón por defecto (habitación libre → check-in directo)
              $boton = '<button class="btn-habitacion btn-accion-principal" onclick="anadirForm(\'formCheckinDirecto\','.(int)$hab['idhabitacion'].')" title="Registrar nuevo ingreso">Check-In</button>';

              if ($estado === 'ocupada') {
                  if ($idreserva_checkout) {
                      // Ocupada por reserva
                      $boton = '
          <button class="btn-habitacion btn-accion-principal"
            onclick="confirmarCheckout('.$id_detalle_activo.')"
            title="Finalizar estadía y registrar salida">Check-Out</button>
          <div class="grupo-botones-secundarios" style="display:flex; flex-wrap:wrap; gap:4px; margin-top:4px;">'.
                        ($id_detalle_activo ? '<button class="btn-habitacion btn-ver" onclick="window.open(\'pdf/reservas/verReservaPDF.php?detalle='.$id_detalle_activo.'\', \'_blank\')" title="Ver contrato PDF">🧾 Ver</button>' : '').
                       '<button class="btn-habitacion btn-print" onclick="reimprimirComprobante(\'reserva\','.$id_detalle_activo.')" title="Reimprimir comprobante">🖨️ Comprobante</button>
            <button class="btn-habitacion btn-print" onclick="reimprimirTickets(\'reserva\','.$id_detalle_activo.')" title="Reimprimir tickets">🖨️ Tickets</button>
          </div>';
                  } elseif ($iddetalle_checkin_dir) {
                      // Ocupada por check-in directo
                      $boton = '
          <button class="btn-habitacion btn-accion-principal"
            onclick="confirmarCheckout('.$id_detalle_activo.')"
            title="Finalizar estadía y registrar salida">Check-Out</button>
          <div class="grupo-botones-secundarios" style="display:flex; flex-wrap:wrap; gap:4px; margin-top:4px;">'.
                        ($id_detalle_activo ? '<button class="btn-habitacion btn-ver" onclick="window.open(\'pdf/reservas/verReservaPDF.php?detalle='.$id_detalle_activo.'\', \'_blank\')" title="Ver contrato PDF">🧾 Ver</button>' : '').
                       '<button class="btn-habitacion btn-print" onclick="reimprimirComprobante(\'detalle\','.$id_detalle_activo.')" title="Reimprimir comprobante">🖨️ Comprobante</button>
            <button class="btn-habitacion btn-print" onclick="reimprimirTickets(\'detalle\','.$id_detalle_activo.')" title="Reimprimir tickets">🖨️ Tickets</button>
          </div>';
                  } else {
                      // Fallback: mostrar "Ver" si al menos tenemos detalle
                      $boton = $id_detalle_activo
                          ? '<button class="btn-habitacion btn-ver" onclick="window.open(\'pdf/reservas/verReservaPDF.php?detalle='.$id_detalle_activo.'\', \'_blank\')" title="Ver contrato PDF">🧾 Ver</button>'
                          : '';
                  }
              } elseif ($estado === 'reservada') {
                  // Check-In de reserva HOY → siempre por id_detalle
                  if ($id_detalle_activo) {
                      $boton = '<button class="btn-habitacion btn-accion-principal"
                      onclick="checkinPorDetalle('.$id_detalle_activo.')"
                      title="Confirmar ingreso de esta reserva">Check-In</button>
                    <div class="grupo-botones-secundarios" style="display:flex; flex-wrap:wrap; gap:4px; margin-top:4px;">
                      <button class="btn-habitacion btn-ver" onclick="window.open(\'pdf/reservas/verReservaPDF.php?detalle='.$id_detalle_activo.'\', \'_blank\')" title="Ver contrato PDF">🧾 Ver</button>
                    </div>';
                  } else {
                      $boton = '';
                  }
              } elseif ($estado === 'mantenimiento') {

                  $boton = '';
              }

              // Abono visible en reservada si falta dinero y tenemos una reserva asociada
              $idreserva_para_abono = $idreserva_activa ?: ($idreserva_checkout ?: 0);
              $mostrarBotonFaltante = ($estado === 'reservada' && $faltante > 0.01 && $idreserva_para_abono);
              ?>
          <div class="habitacion-card <?= $colorClass ?>">
            <span class="badge"><?= ucfirst($estado) ?></span>
            <h4>Hab.
              <?= htmlspecialchars($hab['numero']) ?>
            </h4>

            <?php if (!empty($hab['cliente_actual'])): ?>
            <div class="habitacion-cliente">
              <?= htmlspecialchars($hab['cliente_actual']) ?>
            </div>
            <?php endif; ?>

            <div class="habitacion-botones" style="margin-top:5px; display:flex; flex-direction:column; gap:4px;">
              <?= $boton ?>

              <?php if ($mostrarBotonFaltante): ?>
              <button class="btn-habitacion btn_abono"
                data-id="<?= (int)$idreserva_para_abono ?>"
                data-detalle="<?= (int)$id_detalle_activo ?>"
                data-cliente="<?= htmlspecialchars($hab['cliente_actual'] ?? '') ?>"
                data-total="<?= number_format($total, 2, '.', '') ?>"
                data-abono="<?= number_format($abono, 2, '.', '') ?>"
                data-saldo="<?= number_format($faltante, 2, '.', '') ?>"
                style="background:#ffc107; color:black;" title="Registrar abono pendiente">
                💰 Falta $<?= number_format($faltante, 2) ?>
              </button>

              <?php endif; ?>
            </div>

            <div class="habitacion-servicios" style="margin-top:10px; font-size:18px;">
              <?php if ($estado !== 'disponible' && $estado !== 'mantenimiento'): ?>
              <?php if (!empty($hab['incluye_desayuno'])) {
                  echo '<span title="Incluye desayuno">🥐</span>';
              } ?>
              <?php if (!empty($hab['incluye_tour'])) {
                  echo '<span title="Incluye tour">🗺️</span>';
              } ?>
              <?php if (!empty($hab['incluye_garaje'])) {
                  echo '<span title="Incluye garaje">🚗</span>';
              } ?>
              <?php endif; ?>
            </div>
          </div>
          <?php endwhile; ?>

        </div>
      </div>
    </div>

    <div class="panel-secundario">
      <!-- Pon todo dentro de un solo row -->
      <div class="fila-servicios">

        <?= $desayunos; /* Este ya incluye <div id="bloqueDesayunos" ...> ... </div> */ ?>

        <!-- TOURS HOY -->
        <div id="bloqueTours" class="bloque-servicio">
          <div class="desayuno-header">
            <strong>🗺️ Tours hoy:</strong>
            <div class="desayuno-controles">
              <button class="btn-imprimir-hoy" onclick="imprimirTours()">🖨️ Hoy</button>
              <input type="date" id="fecha_tour"
                min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
              <button class="btn-ver-fecha" onclick="verToursPorFecha()">📅 Ver</button>
            </div>
          </div>
          <br>

          <!-- 👉 Contenedor de HOY para poder vaciarlo desde JS -->
          <div id="tours_hoy_lista">
            <?php if ($toursQuery && mysqli_num_rows($toursQuery) > 0): ?>
            <ul style="margin-left:20px;">
              <?php while ($row = mysqli_fetch_assoc($toursQuery)):
                  $habitacion = $row['habitacion'];
                  $personas   = (int)$row['total_personas'];
                  // IMPORTANTE: que tu SELECT ya traiga "COALESCE(NULLIF(dl.lugares_nombres,''), 'SIN_LUGAR') AS lugares"
                  $lugares    = $row['lugares'] ?? 'SIN_LUGAR';
                  ?>
              <li>
                <strong>Hab.
                  <?= htmlspecialchars($habitacion) ?>:</strong>
                <?= $personas ?> persona(s) –
                <?= htmlspecialchars($lugares) ?>
              </li>
              <?php endwhile; ?>
            </ul>
            <?php else: ?>
            <p style="margin-left:10px;">No hay tours programados para hoy.</p>
            <?php endif; ?>
          </div>

          <!-- Resultado por FECHA -->
          <div id="resultado_tours_fecha"></div>
        </div>



        <!-- GARAJE HOY -->
        <div id="bloqueGaraje" class="bloque-servicio">
          <div class="desayuno-header">
            <strong>🚗 Garajes hoy:</strong>
            <div class="desayuno-controles">
              <button class="btn-imprimir-hoy" onclick="imprimirGaraje()">🖨️ Hoy</button>
              <input type="date" id="fecha_garaje"
                min="<?= date('Y-m-d') ?>">
              <button class="btn-ver-fecha" onclick="verGarajePorFecha()">📅 Ver</button>
            </div>
          </div>
          <br>

          <!-- 👉 Contenedor de HOY para poder vaciarlo desde JS -->
          <div id="garaje_hoy_lista">
            <?php if ($garajeQuery && mysqli_num_rows($garajeQuery) > 0): ?>
            <ul style="margin-left:20px;">
              <?php while ($row = mysqli_fetch_assoc($garajeQuery)): ?>
              <li><strong>Hab.
                  <?= htmlspecialchars($row['habitacion']) ?></strong>
              </li>
              <?php endwhile; ?>
            </ul>
            <?php else: ?>
            <p style="margin-left:10px;">No hay garajes registrados para hoy.</p>
            <?php endif; ?>
          </div>

          <!-- Resultado por FECHA -->
          <div id="resultado_garaje_fecha"></div>
        </div>


      </div>


      <!-- CALENDARIO -->
      <div class="seccion-calendario">
        <h3>📆 Calendario de reservas</h3>
        <div id="calendar"></div>
      </div>


      <!-- RESERVAS PRÓXIMAS -->
      <div class="seccion-reservas">
        <h3>📅 Reservas próximas</h3>
        <table border="1" cellpadding="8" cellspacing="0"
          style="width:100%; border-collapse:collapse; padding: 5px !important;" id="tablaFuturas">
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
            <?php foreach ($reservas_array as $r): ?>
            <tr>
              <td>
                <?= htmlspecialchars($r['idreserva']) ?>
              </td>
              <td>
                <?= htmlspecialchars($r['cliente']) ?>
              </td>
              <td>
                <?= htmlspecialchars($r['fecha_entrada']) ?>
              </td>
              <td>
                <?= htmlspecialchars($r['fecha_salida']) ?>
              </td>
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
                  data-id="<?= (int)$r['idreserva']; ?>"
                  data-cliente="<?= htmlspecialchars($r['cliente']); ?>"
                  data-total="<?= number_format((float)$r['total'], 2, '.', '') ?>"
                  data-abono="<?= number_format((float)$r['abono'], 2, '.', '') ?>"
                  data-saldo="<?= number_format((float)$r['saldo'], 2, '.', '') ?>">
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

  <script>
    // Auto refresh cada 10 min si no hay modal/alerta
    setInterval(() => {
      const modalAbierto = document.querySelector('.modal')?.style.display === 'block';
      const swalVisible = !!document.querySelector('.swal2-container');
      if (!modalAbierto && !swalVisible) location.reload();
    }, 600000);

    // DataTable
    $(document).ready(function() {
      $('#tablaFuturas').DataTable({
        pageLength: 5,
        language: {
          url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        }
      });
    });

    // Cargar formularios en modal
    function anadirForm(action, co) {
      $.ajax({
        url: 'ajax.php',
        type: 'POST',
        async: true,
        data: {
          action,
          co
        },
        success: function(response) {
          $('.modal .bodyModal').html(response);
          $('.modal').fadeIn('fast', function() {
            if ($('.js-example-basic-single').length) {
              $('.js-example-basic-single').select2({
                width: '100%',
                dropdownParent: $('.modal')
              });
            }
          });
        },
        error: function(xhr) {
          const txt = xhr?.responseText ? xhr.responseText : 'No se pudo conectar con el servidor.';
          Swal.fire('Error', txt, 'error');
        }
      });
    }

    function checkinPorDetalle(id_detalle) {
      const money = (n) => Number.parseFloat(n || 0).toFixed(2);

      Swal.fire({
        title: 'Confirmar Check-In',
        text: '¿Deseas registrar el ingreso de esta habitación ahora?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar'
      }).then((res) => {
        if (!res.isConfirmed) return;

        // 2) Consultamos saldo del DETALLE
        mostrarProcesando('Verificando saldo...', 'Espere por favor...');
        $.post('ajax.php', {
          action: 'infoSaldoDetalle',
          id_detalle: id_detalle
        }, function(resp) {
          let data;
          try {
            data = typeof resp === 'string' ? JSON.parse(resp) : resp;
          } catch (e) {
            Swal.fire('Error', resp || 'Respuesta inválida del servidor.', 'error');
            return;
          }

          if (!data || !data.ok) {
            const msg = (data && data.msg) ? data.msg : 'No fue posible obtener el saldo del detalle.';
            Swal.fire('Error', msg, 'error');
            return;
          }

          const cliente = data.cliente || '';
          const subtotal = Number(data.subtotal || 0);
          const abonado = Number(data.abonado || 0);
          const saldo = Number(data.saldo || 0);

          // 3) Si no hay deuda -> check-in directo
          if (saldo <= 0.00001) {
            $.post('ajax.php', {
              action: 'checkinPorDetalle',
              id_detalle: id_detalle
            }, function(r2) {
              console.log('[checkinPorDetalle] respuesta:', r2);
              const txt = (r2 || '').trim().toLowerCase();
              if (txt === 'ok' || txt === '{"ok":true}' || txt === 'true') {
                Swal.fire({
                  icon: 'success',
                  title: 'Check-In realizado con éxito',
                  timer: 1800,
                  showConfirmButton: false
                }).then(() => location.reload());
              } else {
                try {
                  const j = JSON.parse(r2);
                  return Swal.fire('Error', j.msg || 'No se pudo completar el Check-In.', 'error');
                } catch (_) {}
                Swal.fire('Error', r2 || 'No se pudo completar el Check-In.', 'error');
              }
            }).fail(() => Swal.fire('Error', 'Error de conexión con el servidor.', 'error'));
            return;
          }

          // 4) Hay deuda -> cobrar + check-in
          Swal.close();
          const htmlForm = `
        <div style="text-align:left">
          <p><strong>Cliente:</strong> ${cliente ? cliente : '—'}</p>
          <p><strong>Subtotal hab.:</strong> $${money(subtotal)}</p>
          <p><strong>Abonado:</strong> $${money(abonado)}</p>
          <p><strong>Saldo pendiente:</strong> <span style="color:#c0392b;">$${money(saldo)}</span></p>
          <hr>
          <label for="monto_cobro">Monto a cobrar (máx. $${money(saldo)}):</label>
          <input type="number" step="0.01" min="0.01" max="${money(saldo)}" id="monto_cobro" class="swal2-input" value="${money(saldo)}" required>
          <label for="metodo_pago" style="display:block; margin-top:6px;">Método de pago:</label>
          <select id="metodo_pago" class="swal2-select" required>
            <option value="">-- Seleccione --</option>
            <option value="1">Efectivo</option>
            <option value="2">Tarjeta</option>
            <option value="3">Transferencia</option>
          </select>
          <div id="ref_wrap" style="display:none; margin-top:10px;">
            <label for="ref_pago">Referencia:</label>
            <input type="text" id="ref_pago" class="swal2-input" placeholder="Código/No. transacción">
          </div>
          <small>Se registrará el abono a esta habitación y se efectuará el Check-In en un solo paso.</small>
        </div>
      `;

          Swal.fire({
            title: 'Cobrar saldo y Check-In',
            html: htmlForm,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Cobrar y Check-In',
            cancelButtonText: 'Cancelar',
            didOpen: () => {
              const sel = document.getElementById('metodo_pago');
              const refWrap = document.getElementById('ref_wrap');
              const refIn = document.getElementById('ref_pago');
              sel.addEventListener('change', () => {
                if (sel.value === '2' || sel.value === '3') {
                  refWrap.style.display = 'block';
                  refIn.required = true;
                } else {
                  refWrap.style.display = 'none';
                  refIn.required = false;
                  refIn.value = '';
                }
              });
            },
            preConfirm: () => {
              const montoStr = (document.getElementById('monto_cobro').value || '').trim();
              const monto = Number(montoStr);
              const metodo = (document.getElementById('metodo_pago').value || '').trim();
              const ref = (document.getElementById('ref_pago').value || '').trim();

              if (!monto || isNaN(monto) || monto <= 0) {
                Swal.showValidationMessage('Ingrese un monto válido.');
                return false;
              }
              if (monto - saldo > 0.00001) {
                Swal.showValidationMessage(`El monto no puede ser mayor a $${money(saldo)}.`);
                return false;
              }
              if (!metodo) {
                Swal.showValidationMessage('Seleccione un método de pago.');
                return false;
              }
              if ((metodo === '2' || metodo === '3') && ref === '') {
                Swal.showValidationMessage('Debe ingresar una referencia para tarjeta o transferencia.');
                return false;
              }
              return {
                monto,
                metodo,
                ref
              };
            }
          }).then((resCobro) => {
            if (!resCobro.isConfirmed) return;

            mostrarProcesando('Procesando pago...', 'Espere por favor...');
            $.post('ajax.php', {
              action: 'cobrarYCheckinDetalle',
              id_detalle: id_detalle,
              monto: resCobro.value.monto,
              metodo_pago: resCobro.value.metodo,
              referencia: resCobro.value.ref
            }, function(r3, textStatus, jqXHR) { // <-- AÑADIDOS textStatus y jqXHR
              // Logs para depuración (ya no rompe)
              try {
                console.log('[cobrarYCheckinDetalle] textStatus:', textStatus);
              } catch (_) {}
              try {
                console.log('[cobrarYCheckinDetalle] headers:', jqXHR && jqXHR.getAllResponseHeaders ?
                  jqXHR.getAllResponseHeaders() : '(no headers)');
              } catch (_) {}
              try {
                console.log('[cobrarYCheckinDetalle] respuesta cruda:', r3);
              } catch (_) {}

              const txt = (typeof r3 === 'string' ? r3 : String(r3 || '')).trim().toLowerCase();
              if (txt === 'ok' || txt === '{"ok":true}' || txt === 'true') {
                Swal.fire({
                  icon: 'success',
                  title: 'Pago registrado y Check-In realizado',
                  timer: 1800,
                  showConfirmButton: false
                }).then(() => location.reload());
              } else {
                try {
                  const j = JSON.parse(r3);
                  console.log('[cobrarYCheckinDetalle] JSON parseado:', j);
                  return Swal.fire('Error', j.msg || 'No se pudo completar el proceso.', 'error');
                } catch (_) {}
                Swal.fire('Error', r3 || 'No se pudo completar el proceso.', 'error');
              }
            }).fail(() => Swal.fire('Error', 'Error de conexión con el servidor.', 'error'));
          });
        }).fail(() => Swal.fire('Error', 'Error de conexión con el servidor.', 'error'));
      });
    }

    function confirmarCheckout(id_detalle) {
      const money = (n) => Number.parseFloat(n || 0).toFixed(2);

      // 1) Traer saldo del DETALLE
      mostrarProcesando('Verificando saldo...', 'Espere por favor...');
      $.post('ajax.php', {
        action: 'infoSaldoDetalle',
        id_detalle
      }, function(resp) {
        let data;
        try {
          data = typeof resp === 'string' ? JSON.parse(resp) : resp;
        } catch {
          Swal.fire('Error', resp || 'Respuesta inválida del servidor.', 'error');
          return;
        }

        if (!data || !data.ok) {
          return Swal.fire('Error', (data && data.msg) || 'No fue posible obtener el saldo del detalle.', 'error');
        }

        const cliente = data.cliente || '';
        const subtotal = Number(data.subtotal || 0);
        const abonado = Number(data.abonado || 0);
        const saldo = Number(data.saldo || 0);
        Swal.close();

        const renderYEnviarCheckout = (pago) => {
          // pago = {monto, metodo_pago, referencia} o null si no hay deuda
          mostrarProcesando('Registrando Check-Out...', 'Espere por favor...');
          const payload = {
            action: 'checkoutDetalle',
            id_detalle
          };
          if (pago) {
            payload.monto = pago.monto;
            payload.metodo_pago = pago.metodo_pago;
            payload.referencia = pago.referencia || '';
          }
          $.post('ajax.php', payload, function(r3, textStatus, jqXHR) {
            try {
              console.log('[checkoutDetalle] textStatus:', textStatus);
            } catch (_) {}
            try {
              console.log('[checkoutDetalle] respuesta:', r3);
            } catch (_) {}

            const txt = (typeof r3 === 'string' ? r3 : String(r3 || '')).trim().toLowerCase();
            if (txt === 'ok' || txt === '{"ok":true}' || txt === 'true') {
              Swal.fire({
                icon: 'success',
                title: 'Check-Out realizado correctamente',
                timer: 1800,
                showConfirmButton: false
              }).then(() => location.reload());
            } else {
              try {
                const j = JSON.parse(r3);
                return Swal.fire('Error', j.msg || 'No se pudo completar el Check-Out.', 'error');
              } catch (_) {}
              Swal.fire('Error', r3 || 'No se pudo completar el Check-Out.', 'error');
            }
          }).fail(() => Swal.fire('Error', 'Error de conexión con el servidor.', 'error'));
        };

        // 2) Si NO hay deuda -> checkout directo
        if (saldo <= 0.00001) {
          Swal.fire({
            title: 'Confirmar Check-Out',
            text: 'No hay saldo pendiente. ¿Deseas finalizar la estadía?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, finalizar',
            cancelButtonText: 'Cancelar'
          }).then((r) => {
            if (r.isConfirmed) renderYEnviarCheckout(null);
          });
          return;
        }

        // 3) Hay deuda -> cobrar y checkout
        const htmlForm = `
      <div style="text-align:left">
        <p><strong>Cliente:</strong> ${cliente || '—'}</p>
        <p><strong>Subtotal hab.:</strong> $${money(subtotal)}</p>
        <p><strong>Abonado:</strong> $${money(abonado)}</p>
        <p><strong>Saldo pendiente:</strong> <span style="color:#c0392b;">$${money(saldo)}</span></p>
        <hr>
        <label for="monto_cobro_out">Monto a cobrar (máx. $${money(saldo)}):</label>
        <input type="number" step="0.01" min="0.01" max="${money(saldo)}" id="monto_cobro_out" class="swal2-input" value="${money(saldo)}" required>
        <label for="metodo_pago_out" style="display:block; margin-top:6px;">Método de pago:</label>
        <select id="metodo_pago_out" class="swal2-select" required>
          <option value="">-- Seleccione --</option>
          <option value="1">Efectivo</option>
          <option value="2">Tarjeta</option>
          <option value="3">Transferencia</option>
        </select>
        <div id="ref_wrap_out" style="display:none; margin-top:10px;">
          <label for="ref_pago_out">Referencia:</label>
          <input type="text" id="ref_pago_out" class="swal2-input" placeholder="Código/No. transacción">
        </div>
        <small>Se registrará el abono a esta habitación y se realizará el Check-Out.</small>
      </div>
      `;

        Swal.fire({
          title: 'Cobrar saldo y Check-Out',
          html: htmlForm,
          focusConfirm: false,
          showCancelButton: true,
          confirmButtonText: 'Cobrar y Finalizar',
          cancelButtonText: 'Cancelar',
          didOpen: () => {
            const sel = document.getElementById('metodo_pago_out');
            const refWrap = document.getElementById('ref_wrap_out');
            const refIn = document.getElementById('ref_pago_out');
            sel.addEventListener('change', () => {
              if (sel.value === '2' || sel.value === '3') {
                refWrap.style.display = 'block';
                refIn.required = true;
              } else {
                refWrap.style.display = 'none';
                refIn.required = false;
                refIn.value = '';
              }
            });
          },
          preConfirm: () => {
            const montoStr = (document.getElementById('monto_cobro_out').value || '').trim();
            const monto = Number(montoStr);
            const metodo = (document.getElementById('metodo_pago_out').value || '').trim();
            const ref = (document.getElementById('ref_pago_out').value || '').trim();

            if (!monto || isNaN(monto) || monto <= 0) {
              Swal.showValidationMessage('Ingrese un monto válido.');
              return false;
            }
            if (monto - saldo > 0.00001) {
              Swal.showValidationMessage(`El monto no puede ser mayor a $${money(saldo)}.`);
              return false;
            }
            if (!metodo) {
              Swal.showValidationMessage('Seleccione un método de pago.');
              return false;
            }
            if ((metodo === '2' || metodo === '3') && ref === '') {
              Swal.showValidationMessage('Debe ingresar una referencia para tarjeta o transferencia.');
              return false;
            }
            return {
              monto,
              metodo_pago: metodo,
              referencia: ref
            };
          }
        }).then((rCobro) => {
          if (!rCobro.isConfirmed) return;
          renderYEnviarCheckout(rCobro.value);
        });
      }).fail(() => Swal.fire('Error', 'Error de conexión con el servidor.', 'error'));
    }

    function mostrarProcesando(titulo = 'Procesando...', mensaje = 'Por favor espere...') {
      Swal.fire({
        title: titulo,
        html: mensaje,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
      });
    }

    document.addEventListener('click', (ev) => {
      const btn = ev.target.closest('.btn_abono');
      if (!btn) return;

      const idreserva = btn.dataset.id || '';
      const id_detalle = parseInt(btn.dataset.detalle || '0', 10); // viene si el botón lo incluye
      const cliente = btn.dataset.cliente || '';
      const total = parseFloat(btn.dataset.total || '0') || 0;
      const abono = parseFloat(btn.dataset.abono || '0') || 0;
      const saldo = parseFloat(btn.dataset.saldo || '0') || 0;

      const esPorDetalle = id_detalle > 0;
      const tituloPara = esPorDetalle ?
        `Habitación (detalle #${id_detalle})` :
        'Reserva completa';

      const modal = document.querySelector('.modal');
      const body = document.querySelector('.bodyModal');

      body.innerHTML = `
      <form id="form_abono">
        <h2>Agregar Abono</h2>
        <p><strong>Aplicar a:</strong> ${tituloPara}</p>
        <p><strong>Reserva ID:</strong> ${idreserva}</p>
        <p><strong>Cliente:</strong> ${cliente}</p>
        <p><strong>Total:</strong> $${total.toFixed(2)}</p>
        <p><strong>Abonado:</strong> $${abono.toFixed(2)}</p>
        <p><strong>Saldo Pendiente:</strong> $${saldo.toFixed(2)}</p>
        <hr>
        <input type="hidden" id="idreserva_abono" name="idreserva" value="${idreserva}">
        ${esPorDetalle ? `<input type="hidden" id="id_detalle_abono" name="id_detalle" value="${id_detalle}">` : ''}
        <label for="monto_abono">Monto a abonar:</label>
        <input type="number" step="0.01" min="0.01" max="${saldo}" id="monto_abono" name="monto" value="${saldo.toFixed(2)}" required>
        <small>El monto no debe exceder el saldo pendiente.</small>

        <label for="metodo_pago" style="margin-top:10px;">Método de pago:</label>
        <select id="metodo_pago" name="metodo_pago" required>
          <option value="">-- Seleccione --</option>
          <option value="1">Efectivo</option>
          <option value="2">Tarjeta</option>
          <option value="3">Transferencia</option>
        </select>

        <div id="referencia_group" style="display:none; margin-top:10px;">
          <label for="referencia_pago">Referencia o número de transacción:</label>
          <input type="text" id="referencia_pago" name="referencia" placeholder="Referencia bancaria o código">
        </div>

        <div class="btns" style="margin-top: 15px;">
          <button type="submit" class="btn_save"><i class="fas fa-check"></i> Guardar</button>
          <button type="button" class="btn_cancel" onclick="closeModal();"><i class="fas fa-ban"></i> Cancelar</button>
        </div>
      </form>`;

      modal.style.display = 'block';

      // Mostrar/ocultar referencia según método
      document.getElementById('metodo_pago').addEventListener('change', function() {
        const refGroup = document.getElementById('referencia_group');
        const refInput = document.getElementById('referencia_pago');
        if (this.value === '2' || this.value === '3') {
          refGroup.style.display = 'block';
          refInput.required = true;
        } else {
          refGroup.style.display = 'none';
          refInput.required = false;
          refInput.value = '';
        }
      });

      // Envío del abono
      document.getElementById('form_abono').addEventListener('submit', function(e) {
        e.preventDefault();

        const idreserva = document.getElementById('idreserva_abono').value;
        const id_detalle_inp = document.getElementById('id_detalle_abono');
        const id_detalle = id_detalle_inp ? parseInt(id_detalle_inp.value, 10) : 0;

        let monto = parseFloat(document.getElementById('monto_abono').value);
        const max = parseFloat(document.getElementById('monto_abono').getAttribute('max')) || saldo;

        const metodo_pago = document.getElementById('metodo_pago').value;
        const referencia = (document.getElementById('referencia_pago').value || '').trim();

        if (isNaN(monto) || monto <= 0) {
          return Swal.fire('Error', 'El monto debe ser mayor a 0.', 'error');
        }
        if (monto > max + 0.00001) {
          return Swal.fire('Error', `El monto no puede superar $${max.toFixed(2)}.`, 'error');
        }
        if (!metodo_pago) {
          return Swal.fire('Error', 'Debe seleccionar un método de pago.', 'error');
        }
        if ((metodo_pago === '2' || metodo_pago === '3') && referencia === '') {
          return Swal.fire('Error', 'Debe ingresar una referencia de pago.', 'error');
        }

        const params = new URLSearchParams();
        params.append('action', 'agregarAbono');
        params.append('idreserva', idreserva);
        if (id_detalle > 0) params.append('id_detalle', String(id_detalle)); // solo si es por habitación
        params.append('monto', monto.toFixed(2));
        params.append('metodo_pago', metodo_pago);
        params.append('referencia', referencia);

        fetch('ajax.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: params.toString()
          })
          .then(async (res) => {
            const raw = await res.text();
            let json;
            try {
              json = JSON.parse(raw);
            } catch (e) {
              throw new Error(raw || `Respuesta inválida (HTTP ${res.status})`);
            }
            if (!res.ok) throw new Error(json.msg || `HTTP ${res.status} ${res.statusText}`);
            return json;
          })
          .then(data => {
            if (data.ok) {
              Swal.fire('Éxito', 'Abono registrado correctamente.', 'success')
                .then(() => location.reload());
            } else {
              Swal.fire('Error', data.msg || 'No se pudo registrar el abono.', 'error');
            }
          })
          .catch(err => Swal.fire('Error', err.message || String(err), 'error'));
      });
    });

    function imprimirDesayunos() {
      fetch('ajax.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'action=imprimirDesayunos'
        })
        .then(res => res.text())
        .then(resp => {
          if (resp.trim() === '1' || resp.includes('true')) Swal.fire('✅ Imprimido',
            'Lista de desayunos enviada a la impresora', 'success');
          else Swal.fire('⚠️ Error', resp, 'error');
        });
    }

    function imprimirTours() {
      fetch('ajax.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'action=imprimirTours'
        })
        .then(res => res.text()).then(resp => Swal.fire(resp.trim() === 'ok' ? '✅ Tours impresos' : '❌ Error', resp,
          resp.trim() === 'ok' ? 'success' : 'error'));
    }

    function imprimirGaraje() {
      fetch('ajax.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'action=imprimirGaraje'
        })
        .then(res => res.text()).then(resp => Swal.fire(resp.trim() === 'ok' ? '✅ Tickets de garaje impresos' :
          '❌ Error', resp, resp.trim() === 'ok' ? 'success' : 'error'));
    }

    async function verDesayunosPorFecha() {
      const fecha = document.getElementById('fecha_desayuno').value;
      if (!fecha) {
        return Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'warning',
          title: '⚠️ Fecha requerida',
          timer: 1800,
          showConfirmButton: false
        });
      }

      // Toast de cargando
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'info',
        title: 'Consultando desayunos…',
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      const hoyEl = document.getElementById('desayunos_hoy_lista');
      const resultEl = document.getElementById('resultado_desayunos_fecha');

      // Limpia ambos contenedores
      if (hoyEl) hoyEl.innerHTML = '';
      if (resultEl) resultEl.innerHTML = '';

      try {
        const body = new URLSearchParams();
        body.append('action', 'verDesayunosPorFecha');
        body.append('fecha', fecha);

        const res = await fetch('ajax.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: body.toString()
        });
        const html = (await res.text()).trim();

        Swal.close(); // cierra "cargando"

        if (resultEl) {
          resultEl.innerHTML = html || `<p style="margin-left:10px;">No hay desayunos para ${fecha}.</p>`;
        }

        return Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: 'Desayunos cargados',
          timer: 1400,
          showConfirmButton: false
        });
      } catch (e) {
        Swal.close();
        return Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'error',
          title: 'Error al consultar desayunos',
          timer: 1800,
          showConfirmButton: false
        });
      }
    }

    /* ========= TOURS ========= */
    async function verToursPorFecha() {
      const fecha = document.getElementById('fecha_tour').value;
      if (!fecha) {
        return Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'warning',
          title: 'Fecha requerida',
          timer: 1800,
          showConfirmButton: false
        });
      }

      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'info',
        title: 'Consultando tours…',
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      const hoyEl = document.getElementById('tours_hoy_lista');
      const resultEl = document.getElementById('resultado_tours_fecha');

      if (hoyEl) hoyEl.innerHTML = '';
      if (resultEl) resultEl.innerHTML = '';

      try {
        const body = new URLSearchParams();
        body.append('action', 'verToursPorFecha');
        body.append('fecha', fecha);

        const res = await fetch('ajax.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: body.toString()
        });
        const html = (await res.text()).trim();

        Swal.close();

        if (resultEl) {
          resultEl.innerHTML = html || `<p style="margin-left:10px;">No hay tours programados para ${fecha}.</p>`;
        }

        return Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: 'Tours cargados',
          timer: 1400,
          showConfirmButton: false
        });
      } catch (e) {
        Swal.close();
        return Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'error',
          title: 'Error al consultar tours',
          timer: 1800,
          showConfirmButton: false
        });
      }
    }

    /* ========= GARAJE ========= */
    async function verGarajePorFecha() {
      const fecha = document.getElementById('fecha_garaje').value;
      if (!fecha) {
        return Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'warning',
          title: 'Fecha requerida',
          timer: 1800,
          showConfirmButton: false
        });
      }

      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'info',
        title: 'Consultando garajes…',
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      const hoyEl = document.getElementById('garaje_hoy_lista');
      const resultEl = document.getElementById('resultado_garaje_fecha');

      if (hoyEl) hoyEl.innerHTML = '';
      if (resultEl) resultEl.innerHTML = '';

      try {
        const body = new URLSearchParams();
        body.append('action', 'verGarajePorFecha');
        body.append('fecha', fecha);

        const res = await fetch('ajax.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: body.toString()
        });
        const html = (await res.text()).trim();

        Swal.close();

        if (resultEl) {
          resultEl.innerHTML = html || `<p style="margin-left:10px;">No hay garajes registrados para ${fecha}.</p>`;
        }

        return Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: 'Garajes cargados',
          timer: 1400,
          showConfirmButton: false
        });
      } catch (e) {
        Swal.close();
        return Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'error',
          title: 'Error al consultar garajes',
          timer: 1800,
          showConfirmButton: false
        });
      }
    }
    // Reimpresiones genéricas (reserva/detalle)
    function reimprimirComprobante(docTipo, idDoc) {
      $.post('ajax.php', {
        action: 'reimprimirComprobante',
        doc_tipo: docTipo,
        id_doc: idDoc
      }, function(resp) {
        if (resp.trim() === 'ok') Swal.fire('✅ Éxito', 'Comprobante enviado a impresión.', 'success');
        else Swal.fire('⚠️ Error', resp, 'error');
      }).fail(() => Swal.fire('⚠️ Error', 'No se pudo conectar con el servidor', 'error'));
    }

    function reimprimirTickets(docTipo, idDoc) {
      $.post('ajax.php', {
        action: 'reimprimirTickets',
        doc_tipo: docTipo,
        id_doc: idDoc
      }, function(resp) {
        if (resp.trim() === 'ok') Swal.fire('✅ Éxito', 'Tickets enviados a impresión.', 'success');
        else Swal.fire('⚠️ Error', resp, 'error');
      }).fail(() => Swal.fire('⚠️ Error', 'No se pudo conectar con el servidor', 'error'));
    }
    // Modal utils
    function closeModal() {
      const modal = document.querySelector('.modal');
      if (!modal) return;
      const body = modal.querySelector('.bodyModal');
      if (body) body.innerHTML = '';
      modal.style.display = 'none';
    }
    document.addEventListener('click', (ev) => {
      const modal = document.querySelector('.modal');
      if (!modal || modal.style.display !== 'block') return;
      if (ev.target === modal) closeModal();
    });
    document.addEventListener('keydown', (ev) => {
      if (ev.key === 'Escape') closeModal();
    });
    // Resumen superior
    document.addEventListener('DOMContentLoaded', () => {
      const cards = document.querySelectorAll('.habitacion-card');
      let total = cards.length,
        ocupadas = 0,
        disponibles = 0,
        mantenimiento = 0;
      cards.forEach(card => {
        if (card.classList.contains('rojo')) ocupadas++;
        else if (card.classList.contains('verde')) disponibles++;
        else if (card.classList.contains('gris')) mantenimiento++;
        card.style.opacity = 0;
        card.style.transform = 'scale(0.95)';
        setTimeout(() => {
          card.style.transition = 'all .3s ease';
          card.style.opacity = 1;
          card.style.transform = 'scale(1)';
        }, 100);
      });
      document.getElementById('countTotal').textContent = total;
      document.getElementById('countOcupadas').textContent = ocupadas;
      document.getElementById('countDisponibles').textContent = disponibles;
      document.getElementById('countMantenimiento').textContent = mantenimiento;
      const porcentaje = total > 0 ? Math.round((ocupadas / total) * 100) : 0;
      document.getElementById('porcentajeOcupacion').textContent = `${porcentaje}%`;
    });
    document.addEventListener("DOMContentLoaded", function() {
      const el = document.getElementById("calendar");
      if (!el) return;

      // Eventos desde PHP
      const eventos = [
        <?php foreach ($reservas_array as $r):
            $color = '#17a2b8'; // pendiente/default
            if ($r['estado'] === 'confirmada') {
                $color = '#ffc107';
            }
            if ($r['estado'] === 'checkin') {
                $color = '#28a745';
            }
            echo json_encode([
              'id'    => $r['idreserva'].'_cal',
              'title' => $r['cliente'].' - '.$r['habitaciones'],
              'start' => $r['fecha_entrada'],
              // end exclusivo (sumar 1 día para que abarque el OUT)
              'end'   => date('Y-m-d', strtotime($r['fecha_salida'].' +1 day')),
              'url'   => "pdf/reservas/verReservaPDF.php?id={$r['idreserva']}",
              'color' => $color,
              'extendedProps' => [
                'estado' => ucfirst($r['estado']),
                'total'  => number_format((float)$r['total'], 2, '.', ''),
                'abono'  => number_format((float)$r['abono'], 2, '.', ''),
                'saldo'  => number_format((float)$r['saldo'], 2, '.', '')
              ]
            ], JSON_UNESCAPED_UNICODE) . ",";
        endforeach; ?>
      ];

      const cal = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,listMonth' // si no tienes el plugin list, cámbialo a solo 'dayGridMonth'
        },
        events: eventos,
        eventClick(info) {
          info.jsEvent.preventDefault();
          const p = info.event.extendedProps || {};
          const fmt = (d) => FullCalendar.formatDate(d, {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
          });
          const startStr = info.event.start ? fmt(info.event.start) : '—';
          // end es exclusivo; restamos 1 día para mostrar OUT real
          const endReal = info.event.end ? new Date(info.event.end.getTime() - 86400000) : null;
          const endStr = endReal ? fmt(endReal) : '—';

          Swal.fire({
            title: info.event.title,
            html: `
          <div style="text-align:left">
            <p><b>Estado:</b> ${p.estado || '—'}</p>
            <p><b>IN:</b> ${startStr} &nbsp; <b>OUT:</b> ${endStr}</p>
            <p><b>Total:</b> $${p.total || '0.00'} &nbsp; <b>Abono:</b> $${p.abono || '0.00'} &nbsp; <b>Saldo:</b> $${p.saldo || '0.00'}</p>
          </div>`,
            showCancelButton: true,
            confirmButtonText: 'Ver PDF',
            cancelButtonText: 'Cerrar'
          }).then(r => {
            if (r.isConfirmed && info.event.url) window.open(info.event.url, '_blank');
          });
        },
        eventDidMount(info) {
          const p = info.event.extendedProps || {};
          if (typeof tippy === 'function') {
            tippy(info.el, {
              content: p.estado || '',
              placement: 'top',
              theme: 'light-border'
            });
          }
        }
      });

      cal.render();
    });
  </script>
</body>

</html>