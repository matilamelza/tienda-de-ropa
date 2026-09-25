<?php

require_once __DIR__ . '/Conexion.php';

class Talle extends Conexion
{
    /** Listado para el admin, con cuántas variantes usan cada talle. */
    public function listarTodos()
    {
        $sql = "SELECT 
                    t.*,
                    COUNT(pv.id_variante) AS cantidad_variantes
                FROM talles t
                LEFT JOIN producto_variantes pv ON pv.id_talle = t.id_talle
                GROUP BY t.id_talle
                ORDER BY t.orden ASC, t.nombre ASC";

        return $this->db->query($sql);
    }

    /** Talles activos, para cargar variantes nuevas. */
    public function listarActivos()
    {
        return $this->db->query("SELECT * FROM talles WHERE activo = 1 ORDER BY orden ASC, nombre ASC");
    }

    /**
     * Talles activos + el talle actual de la variante aunque esté inactivo.
     * Así, al editar una variante, no se pierde su talle.
     */
    public function listarParaVariante(?int $idActual)
    {
        $idActual = (int) $idActual;

        $stmt = $this->db->prepare(
            "SELECT * FROM talles
             WHERE activo = 1 OR id_talle = ?
             ORDER BY orden ASC, nombre ASC"
        );
        $stmt->bind_param("i", $idActual);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function buscarPorId(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM talles WHERE id_talle = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    /** true si ya existe otro talle con ese nombre. */
    public function existeNombre(string $nombre, int $excluirId = 0): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM talles WHERE nombre = ? AND id_talle <> ? LIMIT 1");
        $stmt->bind_param("si", $nombre, $excluirId);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    /** Próximo número de orden (para que el nuevo quede al final). */
    public function siguienteOrden(): int
    {
        $row = $this->db->query("SELECT COALESCE(MAX(orden), 0) + 1 AS siguiente FROM talles")->fetch_assoc();
        return (int) $row['siguiente'];
    }

    public function crear(array $data): int
    {
        $stmt = $this->db->prepare("INSERT INTO talles (nombre, orden, activo) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $data['nombre'], $data['orden'], $data['activo']);

        return $stmt->execute() ? (int) $this->db->insert_id : 0;
    }

    public function actualizar(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("UPDATE talles SET nombre = ?, orden = ?, activo = ? WHERE id_talle = ?");
        $stmt->bind_param("siii", $data['nombre'], $data['orden'], $data['activo'], $id);

        return $stmt->execute();
    }

    /** true si alguna variante usa este talle. */
    public function enUso(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM producto_variantes WHERE id_talle = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM talles WHERE id_talle = ?");
        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }
}