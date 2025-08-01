<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Prueba de Impresión QZ Tray</title>
  <script src="assets/qz-tray.js"></script>
</head>
<body>
  <h2>Test de impresión QZ Tray</h2>
  <button onclick="probarImpresion()">🖨️ Probar impresión térmica</button>

  <script>
    // 1. Desactivar certificados (modo local)
    qz.security.setCertificatePromise(() => Promise.resolve());
    qz.security.setSignaturePromise(() => Promise.resolve());

    // 2. Conexión automática
    qz.websocket.connect().then(() => {
      console.log("✅ QZ Tray conectado");
    }).catch(err => {
      alert("❌ Error al conectar con QZ Tray: " + err);
    });

    // 3. Función de prueba
    async function probarImpresion() {
      try {
        const printerName = "comandas"; // 👈 Cambia por el nombre exacto de tu impresora
        const config = qz.configs.create(printerName);

        const ticket = [
          "\x1B\x40", // Reset
          "\x1B\x61\x01", // Centrar
          "GRUPO CAÑALIMEÑA\n",
          "\x1B\x45\x01", // Negrita on
          "PRUEBA DE IMPRESIÓN\n",
          "\x1B\x45\x00", // Negrita off
          "\x1B\x61\x00", // Alineado a la izquierda
          "Fecha: " + new Date().toLocaleString() + "\n",
          "------------------------------------------\n",
          "Hab. 103 - 🥐 2 desayunos\n",
          "Cliente: JUAN PÉREZ\n",
          "\nGracias por preferirnos\n",
          "\x1D\x56\x00" // Corte total
        ];

        await qz.print(config, ticket);
        alert("✅ Ticket enviado a imprimir");
      } catch (err) {
        console.error(err);
        alert("❌ Error al imprimir: " + err.message);
      }
    }
  </script>
</body>
</html>
