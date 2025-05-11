<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

class conexionn
{

    public static function obtenerConexion()
    {
        $servername = getenv('DB_HOST');
        $database = getenv('DB_NAME');
        $username = getenv('DB_USER');
        $password = getenv('DB_PASS');


        $con = new mysqli($servername, $username, $password, $database);

        if ($con->connect_error) {
            die("Error al conectar con la bd");
        }
        return $con;
    }



}
