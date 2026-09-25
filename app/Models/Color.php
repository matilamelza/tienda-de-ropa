<?php

require_once __DIR__ . '/Conexion.php';

class Color extends Conexion
{
    /** Listado para el admin, con cuántas variantes usan cada color. */
    public function listarTodos()
    {
        $sql = "SELECT 
                    c.*,
                    COUNT(pv.id_variante) AS cantidad_variantes
                FROM colores c
                LEFT JOIN producto_variantes pv ON pv.id_color = c.id_color
                GROUP BY c.id_color
                ORDER BY c.nombre ASC";

        return $this->db->query($sql);
    }

    /** Colores activos, para cargar variantes nuevas. */
    public function listarActivos()
    {
        return $this->db->query("SELECT * FROM colores WHERE activo = 1 ORDER BY nombre ASC");
    }

    /**
     * Colores activos + el color actual de la variante aunque esté inactivo.
     * Así, al editar una variante, no se pierde su color.
     */
    public function listarParaVariante(?int $idActual)
    {
        $idActual = (int) $idActual;

        $stmt = $this->db->prepare(
            "SELECT * FROM colores
             WHERE activo = 1 OR id_color = ?
             ORDER BY nombre ASC"
        );
        $stmt->bind_param("i", $idActual);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function buscarPorId(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM colores WHERE id_color = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    /** true si ya existe otro color con ese nombre. */
    public function existeNombre(string $nombre, int $excluirId = 0): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM colores WHERE nombre = ? AND id_color <> ? LIMIT 1");
        $stmt->bind_param("si", $nombre, $excluirId);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    public function crear(array $data): int
    {
        $stmt = $this->db->prepare("INSERT INTO colores (nombre, codigo_hex, activo) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $data['nombre'], $data['codigo_hex'], $data['activo']);

        return $stmt->execute() ? (int) $this->db->insert_id : 0;
    }

    public function actualizar(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("UPDATE colores SET nombre = ?, codigo_hex = ?, activo = ? WHERE id_color = ?");
        $stmt->bind_param("ssii", $data['nombre'], $data['codigo_hex'], $data['activo'], $id);

        return $stmt->execute();
    }

    /** true si alguna variante usa este color. */
    public function enUso(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM producto_variantes WHERE id_color = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM colores WHERE id_color = ?");
        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }
}