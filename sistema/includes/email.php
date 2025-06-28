<?php
require_once __DIR__ . '/../libreries/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envía un correo usando Gmail SMTP.
 *
 * @param string $destinoCorreo  Email del destinatario
 * @param string $destinoNombre  Nombre del destinatario
 * @param string $asunto         Asunto del correo
 * @param string $cuerpoHTML     Cuerpo HTML del correo
 * @param array $adjuntos        Array de rutas de archivos a adjuntar (opcional)
 * @param array $imagenesEmbed   Array de imágenes para embebidas en HTML (opcional)
 *
 * @return bool true si se envió, false si hubo error
 */
function enviarCorreo($destinoCorreo, $destinoNombre, $asunto, $cuerpoHTML, $adjuntos = [], $imagenesEmbed = [])
{
    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP para Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'burguerbeerbanios@gmail.com';         
        $mail->Password   = 'uqcf svsc cbfy ixga';         
        $mail->SMTPSecure = 'tls';                         
        $mail->Port       = 587;                           

        // Remitente
        $mail->setFrom('burguerbeerbanios@gmail.com', 'Grupo Cañalimeña');

        // Destinatario
        $mail->addAddress($destinoCorreo, $destinoNombre);

        // Adjuntar archivos si los hay
        if (!empty($adjuntos)) {
            foreach ($adjuntos as $archivo) {
                if (file_exists($archivo)) {
                    $mail->addAttachment($archivo);
                }
            }
        }

        // Incrustar imágenes si se especificaron
        if (!empty($imagenesEmbed)) {
            foreach ($imagenesEmbed as $img) {
                if (file_exists($img['ruta'])) {
                    $mail->addEmbeddedImage($img['ruta'], $img['cid']);
                }
            }
        }

        // Contenido HTML
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHTML;

        $mail->send();

        // Limpiar posible error anterior
        $GLOBALS['lastPHPMailerError'] = null;

        return true;

    } catch (Exception $e) {
        $GLOBALS['lastPHPMailerError'] = $mail->ErrorInfo;
        error_log("Error enviando correo: {$mail->ErrorInfo}");
        return false;
    }
}
