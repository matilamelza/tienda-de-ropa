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
                    COUNT(CASE WHEN p.eliminado_at IS NULL THEN p.id_producto END)     AS cantidad_productos,
                    COUNT(CASE WHEN p.eliminado_at IS NOT NULL THEN p.id_producto END) AS en_papelera
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

        /** true si la categoría tiene productos NO eliminados. */
    public function tieneProductosVivos(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM productos WHERE id_categoria = ? AND eliminado_at IS NULL LIMIT 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    /**
     * Pasa los productos eliminados de esta categoría a la categoría interna "Archivo",
     * para poder borrar la categoría sin romper el historial.
     */
    public function archivarProductosEliminados(int $id): void
    {
        $slugArchivo = 'archivo-productos-eliminados';

        $stmt = $this->db->prepare("SELECT id_categoria FROM categorias WHERE slug = ? LIMIT 1");
        $stmt->bind_param("s", $slugArchivo);
        $stmt->execute();
        $archivo = $stmt->get_result()->fetch_assoc();

        if ($archivo) {
            $idArchivo = (int) $archivo['id_categoria'];
        } else {
            $nombre = 'Archivo (productos eliminados)';
            $stmt = $this->db->prepare("INSERT INTO categorias (nombre, slug, activo) VALUES (?, ?, 0)");
            $stmt->bind_param("ss", $nombre, $slugArchivo);
            $stmt->execute();
            $idArchivo = (int) $this->db->insert_id;
        }

        if ($idArchivo === $id) {
            return;   // no se archiva el archivo en sí mismo
        }

        $stmt = $this->db->prepare(
            "UPDATE productos SET id_categoria = ? WHERE id_categoria = ? AND eliminado_at IS NOT NULL"
        );
        $stmt->bind_param("ii", $idArchivo, $id);
        $stmt->execute();
    }
}