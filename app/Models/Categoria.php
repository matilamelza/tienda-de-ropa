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

    public function listarTodas()
    {
        $sql = "SELECT 
                    c.*,
                    COUNT(p.id_producto) AS cantidad_productos
                FROM categorias c
                LEFT JOIN productos p ON p.id_categoria = c.id_categoria
                GROUP BY c.id_categoria
                ORDER BY c.nombre ASC";

        return $this->db->query($sql);
    }

    public function buscarPorId($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM categorias WHERE id_categoria = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function buscarPorSlug($slug)
    {
        $sql = "SELECT * FROM categorias WHERE slug = ? AND activo = 1 LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $slug);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function crear($data)
    {
        $sql = "INSERT INTO categorias (nombre, slug, activo) VALUES (?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssi", $data['nombre'], $data['slug'], $data['activo']);

        return $stmt->execute();
    }

    public function actualizar($id, $data)
    {
        $sql = "UPDATE categorias SET nombre = ?, slug = ?, activo = ? WHERE id_categoria = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssii", $data['nombre'], $data['slug'], $data['activo'], $id);

        return $stmt->execute();
    }

    public function eliminar($id)
    {
        $stmt = $this->db->prepare("DELETE FROM categorias WHERE id_categoria = ?");
        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }

    public function tieneProdutos($id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM productos WHERE id_categoria = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        return $row['total'] > 0;
    }
}