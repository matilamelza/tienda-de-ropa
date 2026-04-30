<?php

require_once __DIR__ . '/Conexion.php';

class Categoria extends Conexion
{
    public function listarActivas()
    {
        return $this->db->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre ASC");
    }

    public function listarMenu()
    {
        $sql = "SELECT id_categoria, nombre, slug 
                FROM categorias 
                WHERE activo = 1 
                ORDER BY nombre ASC";

        return $this->db->query($sql);
    }

    public function buscarPorSlug($slug)
{
    $sql = "SELECT * FROM categorias WHERE slug = ? AND activo = 1 LIMIT 1";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("s", $slug);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}
}