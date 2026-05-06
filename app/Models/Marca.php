<?php

require_once __DIR__ . '/Conexion.php';

class Marca extends Conexion
{
    public function listarActivas()
    {
        return $this->db->query("SELECT * FROM marcas WHERE activo = 1 ORDER BY nombre ASC");
    }

    public function listarTodas()
    {
        $sql = "SELECT 
                    m.*,
                    COUNT(p.id_producto) AS cantidad_productos
                FROM marcas m
                LEFT JOIN productos p ON p.id_marca = m.id_marca
                GROUP BY m.id_marca
                ORDER BY m.nombre ASC";

        return $this->db->query($sql);
    }

    public function buscarPorId($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM marcas WHERE id_marca = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function crear($data)
    {
        $sql = "INSERT INTO marcas (nombre, activo) VALUES (?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $data['nombre'], $data['activo']);

        return $stmt->execute();
    }

    public function actualizar($id, $data)
    {
        $sql = "UPDATE marcas SET nombre = ?, activo = ? WHERE id_marca = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sii", $data['nombre'], $data['activo'], $id);

        return $stmt->execute();
    }

    public function eliminar($id)
    {
        $stmt = $this->db->prepare("DELETE FROM marcas WHERE id_marca = ?");
        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }

    public function tieneProductos($id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM productos WHERE id_marca = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        return $row['total'] > 0;
    }
}