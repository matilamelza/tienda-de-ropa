<?php

require_once __DIR__ . '/Conexion.php';

class Talle extends Conexion
{
    public function listarActivos()
    {
        return $this->db->query("SELECT * FROM talles ORDER BY orden ASC");
    }
}