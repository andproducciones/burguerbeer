<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" />

<!-- (Opcional) CSS si usarás Resource Timeline -->
<!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fullcalendar/resource-timeline@6.1.10/main.min.css" /> -->

<!-- Tippy CSS (para theme 'light-border') -->
<link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css">
<link rel="stylesheet" href="https://unpkg.com/tippy.js@6/themes/light-border.css">

<!-- Font Awesome (para <i class="fas ...">) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<!-- ✅ Favicon -->
<link rel="icon" href="img/ala.ico" type="image/x-icon">


<!-- ✅ JS base -->
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
<!-- <script src="./js/icons.js"></script> -->

<!-- DataTables + Buttons (orden correcto) -->
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

<!-- ✅ FullCalendar Core + Plugins (mismas versiones y orden correcto) -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/list@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales-all.global.min.js"></script>

<!-- (Opcional) Timeline/Resources SOLO si los usarás -->
<!--
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/timeline@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/resource@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/resource-timeline@6.1.10/index.global.min.js"></script>
-->

<!-- Scripts propios del proyecto -->
<script src="./js/functions.js"></script>

<?php
  include "functions.php";
verificarSesionPOS();
?>