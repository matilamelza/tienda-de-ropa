<?php

$mysqli = new mysqli('localhost', 'root', '', 'tienda_ropa');
$mysqli->set_charset('utf8mb4');

return [
    'host'            => 'localhost',
    'user'            => 'root',
    'pass'            => '',
    'name'            => 'tienda_ropa',
    'conexion'        => $mysqli,
    'whatsapp_tienda' => '5492364123456'
];