<?php
ini_set('display_errors', 0); // No mostrar errores en producción
error_reporting(0);

session_start();

// Cabeceras de seguridad
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: no-referrer");
header("Content-Security-Policy: frame-ancestors 'self'");

$alert = '';

// Redirige si ya tiene sesión activa
if (!empty($_SESSION['active']) && isset($_SESSION['idUser'], $_SESSION['rol'])) {
    header('Location: sistema/');
    exit;
}

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Limitar intentos de login
$ip = $_SERVER['REMOTE_ADDR'];
$intentos = $_SESSION['intentos'][$ip] ?? 0;

// Función para sanitizar entradas
function sanearPost(array $post): array
{
    $limpio = [];
    foreach ($post as $key => $valor) {
        if (is_array($valor)) {
            $limpio[$key] = sanearPost($valor);
        } else {
            $valor = strip_tags($valor);
            $valor = preg_replace('/[<>{}"\'()%;$&#*!=\\\\[\]{}]/', '', $valor);
            $valor = trim($valor);
            $valor = htmlspecialchars($valor, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $limpio[$key] = $valor;
        }
    }
    return $limpio;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $_POST = sanearPost($_POST);

    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $alert = 'Token inválido.';
    } elseif ($intentos >= 5) {
        $alert = 'Demasiados intentos fallidos. Intente más tarde.';
    } elseif (empty($_POST['usuario']) || empty($_POST['clave'])) {
        $alert = 'Ingrese su Cédula y su Contraseña';
    } else {
        require_once "conexion.php";

        $user = mysqli_real_escape_string($conection, $_POST['usuario']);
        $pass = md5(mysqli_real_escape_string($conection, $_POST['clave']));

        $query = mysqli_query($conection, "
            SELECT u.usuario, u.nombre, u.apellido, u.correo, r.idrol, r.rol  
            FROM usuario u 
            INNER JOIN rol r ON u.rol = r.idrol
            WHERE u.usuario = '$user' AND u.clave = '$pass'
        ");

        mysqli_close($conection);
        $result = mysqli_num_rows($query);

        if ($result > 0) {
            $data = mysqli_fetch_assoc($query);

            $_SESSION['active']        = true;
            $_SESSION['idUser']        = $data['usuario'];
            $_SESSION['nombre']        = $data['nombre'];
            $_SESSION['apellido']      = $data['apellido'];
            $_SESSION['correo']        = $data['correo'];
            $_SESSION['rol']           = $data['idrol'];
            $_SESSION['rol_name']      = $data['rol'];
            $_SESSION['caja']          = gethostbyaddr($_SERVER["REMOTE_ADDR"]);
            $_SESSION['ip']            = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $_SESSION['ua']            = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $_SESSION['last_activity'] = time();

            unset($_SESSION['intentos'][$ip]); // Reset intentos

            header('Location: sistema/');
            exit;
        } else {
            $_SESSION['intentos'][$ip] = $intentos + 1;
            $alert = 'Usuario o clave incorrecto';
            // Eliminar solo datos sensibles de sesión, pero conservar el token y control de intentos
            unset(
                $_SESSION['active'],
                $_SESSION['idUser'],
                $_SESSION['nombre'],
                $_SESSION['apellido'],
                $_SESSION['correo'],
                $_SESSION['rol'],
                $_SESSION['rol_name'],
                $_SESSION['caja'],
                $_SESSION['ip'],
                $_SESSION['ua'],
                $_SESSION['last_activity']
            );

        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Login | Cañalimeña</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>

<body>
    <section id="container">
        <form action="" method="post" autocomplete="off">
            <h3>Iniciar Sesión</h3>
            <img src="img/login.png" alt="login" width="100%" height="100%" align="center">
            <input type="text" name="usuario" placeholder="Cédula" autocomplete="off" required>
            <input type="password" name="clave" placeholder="Contraseña" autocomplete="off" required>
            <input type="hidden" name="csrf_token"
                value="<?php echo $_SESSION['csrf_token']; ?>">
            <?php if (!empty($alert)): ?>
            <div class="alert" align="center"><?php echo $alert; ?>
            </div>
            <?php endif; ?>

            <input type="submit" name="INGRESAR" value="Ingresar">
        </form>
    </section>
</body>

</html>