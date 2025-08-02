<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Prueba QZ Tray - Impresión directa</title>
  <script src="assets/qz.tray.js"></script> <!-- Ajusta si lo moviste -->
</head>
<body>
  <h2>🧪 Test de Impresión Térmica con QZ Tray</h2>
  <button onclick="imprimirPrueba()">🖨️ Imprimir Ticket de Prueba</button>

  <script>
    // Configuración sin certificados (modo local)
    qz.security.setCertificatePromise(() => Promise.resolve());
    qz.security.setSignaturePromise(() => Promise.resolve());

    // Conexión automática a QZ Tray
    qz.websocket.connect().then(() => {
      console.log("✅ Conectado a QZ Tray");
    }).catch(err => {
      alert("❌ Error al conectar con QZ Tray: " + err);
    });

    async function imprimirPrueba() {
      try {
        const impresora = "comandas"; // Cambia por el nombre de tu impresora térmica
        const config = qz.configs.create(impresora);

        const fecha = new Date().toLocaleString("es-EC");

        const contenido = [
          "\x1B\x40",                  // Reset
          "\x1B\x61\x01",              // Centrar
          "GRUPO CAÑALIMEÑA\n",
          "HOSTAL & SUITES\n",
          "------------------------------------------\n",
          "\x1B\x45\x01",              // Negrita ON
          "COMPROBANTE DE PRUEBA\n",
          "\x1B\x45\x00",              // Negrita OFF
          "\x1B\x61\x00",              // Alineado izquierda
          "Cliente: JUAN PÉREZ\n",
          "Hab.: 103 - Piso 1\n",
          "Entrada: lunes 01 de julio de 2025\n",
          "Salida: miércoles 03 de julio de 2025\n",
          "Adultos: 2   Niños: 1\n",
          "------------------------------------------\n",
          "Servicios incluidos:\n",
          "🥐 Desayuno\n",
          "🗺️ Tour Chiva - Malecón\n",
          "🚗 Garaje 1 vehículo\n",
          "------------------------------------------\n",
          "\x1B\x61\x01",              // Centrar
          "Total pagado: $79.99\n",
          "Código verificación: #ABC123XYZ\n",
          "Fecha: " + fecha + "\n",
          "Gracias por preferirnos\n",
          "\n\n\n",
          "\x1D\x56\x00"               // Corte total
        ];

        await qz.print(config, contenido);
        alert("✅ Impreso con éxito");
      } catch (err) {
        console.error("❌ Error al imprimir:", err);
        alert("❌ Error al imprimir: " + err.message);
      }
    }
  </script>
</body>
</html>
