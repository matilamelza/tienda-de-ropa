<?php

require_once __DIR__ . '/Conexion.php';

class Caja extends Conexion
{
    /** Todas las cajas con su saldo actual (suma de movimientos no anulados). */
    public function listarConSaldo(bool $soloActivas = false): array
    {
        $where = $soloActivas ? 'WHERE c.activo = 1' : '';

        $sql = "SELECT c.*,
                       COALESCE(SUM(CASE WHEN m.anulado_at IS NULL THEN m.neto_caja END), 0) AS saldo,
                       COUNT(m.id_movimiento) AS cant_movimientos
                FROM cajas c
                LEFT JOIN movimientos m ON m.id_caja = c.id_caja
                $where
                GROUP BY c.id_caja
                ORDER BY c.orden ASC, c.nombre ASC";

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function listarActivas(): array
    {
        return $this->db->query("SELECT * FROM cajas WHERE activo = 1 ORDER BY orden ASC, nombre ASC")
                        ->fetch_all(MYSQLI_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM cajas WHERE id_caja = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function crear(array $d): int
    {
        $stmt = $this->db->prepare("INSERT INTO cajas (nombre, orden, activo) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $d['nombre'], $d['orden'], $d['activo']);

        return $stmt->execute() ? (int) $this->db->insert_id : 0;
    }

    public function actualizar(int $id, array $d): bool
    {
        $stmt = $this->db->prepare("UPDATE cajas SET nombre = ?, orden = ?, activo = ? WHERE id_caja = ?");
        $stmt->bind_param("siii", $d['nombre'], $d['orden'], $d['activo'], $id);

        return $stmt->execute();
    }

        /** Guarda el orden según la posición en el array de ids. */
    public function ordenar(array $ids): void
    {
        $stmt = $this->db->prepare("UPDATE cajas SET orden = ? WHERE id_caja = ?");
        foreach (array_values($ids) as $pos => $id) {
            $orden = $pos + 1;
            $id    = (int) $id;
            $stmt->bind_param("ii", $orden, $id);
            $stmt->execute();
        }
    }
}