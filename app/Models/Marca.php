<?php

require_once __DIR__ . '/Conexion.php';

class Marca extends Conexion
{
    public function listarActivas()
    {
        return $this->db->query("SELECT * FROM marcas WHERE activo = 1 ORDER BY nombre ASC");
    }
}