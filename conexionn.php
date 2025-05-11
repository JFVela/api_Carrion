<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

class conexionn
{

    public static function obtenerConexion()
    {
        $servername = $_ENV['DB_HOST'];
        $database = $_ENV['DB_NAME'];
        $username = $_ENV['DB_USER'];
        $password = $_ENV['DB_PASS'];


        $con = new mysqli($servername, $username, $password, $database);

        if ($con->connect_error) {
            die("Error al conectar con la bd");
        }
        return $con;
    }



}
