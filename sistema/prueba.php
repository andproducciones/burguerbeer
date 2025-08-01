<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Impresión con QZ Tray</title>
    <script src="assets/qz.tray.js"></script>
</head>
<body>

    <button onclick="imprimirDesayunosHoyQZ()">🖨️ Imprimir Desayunos Hoy</button>

    <script>
        qz.security.setCertificatePromise(() => Promise.resolve());
        qz.security.setSignaturePromise(() => Promise.resolve());

        qz.websocket.connect().then(() => {
            console.log("🔌 QZ Tray conectado");
        }).catch(err => {
            alert("Error al conectar con QZ Tray: " + err);
        });

        // Función de impresión
        async function imprimirDesayunosHoyQZ() {
            try {
                const res = await fetch("impresiones/get/imprimirDesayunosHoy.php");
                const data = await res.json();

                if (data.error) {
                    return alert("⚠️ " + data.error);
                }

                const config = qz.configs.create("HOTEL"); // Nombre exacto de la impresora
                const contenido = data.contenido.map(linea => linea + "\n");

                await qz.print(config, contenido);
                alert("✅ Impresión enviada a 'HOTEL'");
            } catch (err) {
                console.error("❌ Error al imprimir:", err);
                alert("❌ Error al imprimir: " + err.message);
            }
        }
    </script>
</body>
</html>
