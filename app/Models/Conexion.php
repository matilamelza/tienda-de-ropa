<?php

class Conexion
{
    protected $db;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';

        $this->db = new mysqli(
            $config['host'],
            $config['user'],
            $config['pass'],
            $config['name']
        );

        if ($this->db->connect_error) {
            die('Error de conexión: ' . $this->db->connect_error);
        }

        $this->db->set_charset('utf8mb4');
    }
}