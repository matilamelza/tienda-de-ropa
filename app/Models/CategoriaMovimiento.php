<?php

require_once __DIR__ . '/Conexion.php';

class CategoriaMovimiento extends Conexion
{
    public function listarTodas(): array
    {
        $sql = "SELECT cm.*, COUNT(m.id_movimiento) AS cant_movimientos
                FROM categorias_movimiento cm
                LEFT JOIN movimientos m ON m.id_categoria_mov = cm.id_categoria_mov
                GROUP BY cm.id_categoria_mov
                ORDER BY cm.tipo ASC, cm.nombre ASC";

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /** $tipo: 'gasto' | 'ingreso' */
    public function listarActivas(string $tipo): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM categorias_movimiento WHERE activo = 1 AND tipo = ? ORDER BY nombre ASC"
        );
        $stmt->bind_param("s", $tipo);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function crear(array $d): int
    {
        $stmt = $this->db->prepare("INSERT INTO categorias_movimiento (nombre, tipo, activo) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $d['nombre'], $d['tipo'], $d['activo']);

        return $stmt->execute() ? (int) $this->db->insert_id : 0;
    }

    public function actualizar(int $id, array $d): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE categorias_movimiento SET nombre = ?, tipo = ?, activo = ? WHERE id_categoria_mov = ?"
        );
        $stmt->bind_param("ssii", $d['nombre'], $d['tipo'], $d['activo'], $id);

        return $stmt->execute();
    }
}