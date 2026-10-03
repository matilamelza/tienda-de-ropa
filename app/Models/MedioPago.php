<?php

require_once __DIR__ . '/Conexion.php';

class MedioPago extends Conexion
{
    public function listarTodos(): array
    {
        $sql = "SELECT mp.*, c.nombre AS caja
                FROM medios_pago mp
                INNER JOIN cajas c ON c.id_caja = mp.id_caja
                ORDER BY mp.orden ASC, mp.nombre ASC";

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function listarActivos(): array
    {
        $sql = "SELECT mp.*, c.nombre AS caja
                FROM medios_pago mp
                INNER JOIN cajas c ON c.id_caja = mp.id_caja
                WHERE mp.activo = 1 AND c.activo = 1
                ORDER BY mp.orden ASC, mp.nombre ASC";

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM medios_pago WHERE id_medio = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function crear(array $d): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO medios_pago (nombre, id_caja, comision_pct, ajuste_pct, orden, activo)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("siddii", $d['nombre'], $d['id_caja'], $d['comision_pct'], $d['ajuste_pct'], $d['orden'], $d['activo']);

        return $stmt->execute() ? (int) $this->db->insert_id : 0;
    }

    public function actualizar(int $id, array $d): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE medios_pago
             SET nombre = ?, id_caja = ?, comision_pct = ?, ajuste_pct = ?, orden = ?, activo = ?
             WHERE id_medio = ?"
        );
        $stmt->bind_param("siddiii", $d['nombre'], $d['id_caja'], $d['comision_pct'], $d['ajuste_pct'], $d['orden'], $d['activo'], $id);

        return $stmt->execute();
    }

        /** Guarda el orden según la posición en el array de ids. */
    public function ordenar(array $ids): void
    {
        $stmt = $this->db->prepare("UPDATE medios_pago SET orden = ? WHERE id_medio = ?");
        foreach (array_values($ids) as $pos => $id) {
            $orden = $pos + 1;
            $id    = (int) $id;
            $stmt->bind_param("ii", $orden, $id);
            $stmt->execute();
        }
    }
}