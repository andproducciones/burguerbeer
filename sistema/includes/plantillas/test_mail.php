<?php
// Definir tus variables dinámicas:
$nombreCliente = "Juan Pérez";
$codigoPromo   = "PROMO-ABC123";

// Capturar la salida
ob_start();
include 'bienvenida.php';
$html = ob_get_clean();

// Mostrar en el navegador:
echo $html;
