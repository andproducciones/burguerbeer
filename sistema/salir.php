<?php

session_start();

// Limpiar todas las variables de sesión
$_SESSION = [];

// Borrar la cookie de sesión (si aplica)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),        // Nombre de la cookie
        '',                    // Valor vacío
        time() - 42000,        // Fecha expirada
        $params["path"],       // Mismo path
        $params["domain"],     // Mismo dominio
        $params["secure"],     // Mismo valor de seguridad
        $params["httponly"]    // HTTPOnly
    );
}

// Destruir la sesión completamente
session_destroy();

// Redirigir al login
header("Location: ../index.php");
exit;
