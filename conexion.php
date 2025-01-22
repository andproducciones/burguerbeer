<?php

$host = 'localhost';
$user = 'root';
$password = '';
$db = 'burguerbeer';

$conection = @mysqli_connect($host, $user, $password, $db);

if (!$conection) {
    die("Error al conectar: " . mysqli_connect_error());
}
?>