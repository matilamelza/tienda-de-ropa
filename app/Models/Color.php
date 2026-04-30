<?php

require_once __DIR__ . '/Conexion.php';

class Color extends Conexion
{
    public function listarActivos()
    {
        return $this->db->query("SELECT * FROM colores ORDER BY nombre ASC");
    }
}