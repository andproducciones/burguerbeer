<script src="assets/qz.tray.js"></script>
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

        const config = qz.configs.create("HOTEL");
        const contenido = data.contenido.map(linea => linea + "\n");

        await qz.print(config, contenido);
        alert("✅ Impresión enviada a 'HOTEL'");
    } catch (err) {
        console.error("❌ Error al imprimir:", err);
        alert("❌ Error al imprimir: " + err.message);
    }
}
</script>
