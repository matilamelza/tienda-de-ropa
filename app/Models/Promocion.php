<?php

require_once __DIR__ . '/Conexion.php';

class Promocion extends Conexion
{
    /** Condición de "promoción vigente" (alias pr = promociones). Se usa en todas las consultas. */
    public const SQL_VIGENTE = "pr.activa = 1
                                AND (pr.desde IS NULL OR pr.desde <= NOW())
                                AND (pr.hasta IS NULL OR pr.hasta > NOW())";

    /**
     * Subconsultas para agregar a un SELECT de productos: el mayor descuento vigente
     * y la etiqueta de esa promoción. $alias es el alias de la tabla productos en esa consulta.
     */
    public static function columnasDescuento(string $alias = 'p'): string
    {
        $vigente = self::SQL_VIGENTE;

        return "(SELECT MAX(COALESCE(pp.pct, pr.pct))
                 FROM promocion_productos pp
                 INNER JOIN promociones pr ON pr.id_promocion = pp.id_promocion
                 WHERE pp.id_producto = $alias.id_producto AND $vigente) AS descuento_pct,
                (SELECT pr.etiqueta
                 FROM promocion_productos pp
                 INNER JOIN promociones pr ON pr.id_promocion = pp.id_promocion
                 WHERE pp.id_producto = $alias.id_producto AND $vigente
                 ORDER BY COALESCE(pp.pct, pr.pct) DESC, pr.id_promocion DESC
                 LIMIT 1) AS descuento_etiqueta,
                (SELECT pr.id_promocion
                 FROM promocion_productos pp
                 INNER JOIN promociones pr ON pr.id_promocion = pp.id_promocion
                 WHERE pp.id_producto = $alias.id_producto AND $vigente
                 ORDER BY COALESCE(pp.pct, pr.pct) DESC, pr.id_promocion DESC
                 LIMIT 1) AS descuento_id_promocion";
    }

    /** Estado de una promoción, para mostrar en el admin. */
    public static function estado(array $p): string
    {
        $ahora = date('Y-m-d H:i:s');

        if ((int) $p['activa'] !== 1)                      return 'pausada';
        if ($p['desde'] !== null && $p['desde'] > $ahora)  return 'programada';
        if ($p['hasta'] !== null && $p['hasta'] <= $ahora) return 'vencida';

        return 'vigente';
    }

    // ─── ABM ───────────────────────────────────────────────────────────────────

    public function listar(): array
    {
        $sql = "SELECT pr.*, COUNT(pp.id_producto) AS cant_productos
                FROM promociones pr
                LEFT JOIN promocion_productos pp ON pp.id_promocion = pr.id_promocion
                GROUP BY pr.id_promocion
                ORDER BY pr.creado_at DESC";

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM promociones WHERE id_promocion = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function crear(array $d): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO promociones (nombre, etiqueta, pct, desde, hasta, activa) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssdssi", $d['nombre'], $d['etiqueta'], $d['pct'], $d['desde'], $d['hasta'], $d['activa']);

        return $stmt->execute() ? (int) $this->db->insert_id : 0;
    }

    public function actualizar(int $id, array $d): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE promociones SET nombre = ?, etiqueta = ?, pct = ?, desde = ?, hasta = ?, activa = ?
             WHERE id_promocion = ?"
        );
        $stmt->bind_param("ssdssii", $d['nombre'], $d['etiqueta'], $d['pct'], $d['desde'], $d['hasta'], $d['activa'], $id);

        return $stmt->execute();
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM promociones WHERE id_promocion = ?");
        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }

    /** Pausa o reanuda. Devuelve el valor nuevo de "activa". */
    public function toggleActiva(int $id): ?int
    {
        $stmt = $this->db->prepare("UPDATE promociones SET activa = 1 - activa WHERE id_promocion = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $p = $this->buscarPorId($id);
        return $p ? (int) $p['activa'] : null;
    }

    // ─── PRODUCTOS DE UNA PROMOCIÓN ────────────────────────────────────────────

    public function productos(int $id_promocion): array
    {
        $stmt = $this->db->prepare(
            "SELECT pp.id_producto, pp.pct AS pct_propio,
                    p.nombre, p.precio_base, p.activo, p.slug,
                    m.nombre AS marca,
                    (SELECT pf.imagen FROM producto_fotos pf
                     WHERE pf.id_producto = p.id_producto
                     ORDER BY pf.principal DESC, pf.orden ASC, pf.id_foto ASC LIMIT 1) AS foto_principal
             FROM promocion_productos pp
             INNER JOIN productos p ON p.id_producto = pp.id_producto
             LEFT JOIN marcas m ON m.id_marca = p.id_marca
             WHERE pp.id_promocion = ? AND p.eliminado_at IS NULL
             ORDER BY p.nombre ASC"
        );
        $stmt->bind_param("i", $id_promocion);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Suma productos a la promoción. Los que ya estaban no se duplican. Devuelve cuántos se sumaron. */
    public function agregarProductos(int $id_promocion, array $ids): int
    {
        $agregados = 0;
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO promocion_productos (id_promocion, id_producto) VALUES (?, ?)"
        );

        foreach ($ids as $id) {
            $id = (int) $id;
            $stmt->bind_param("ii", $id_promocion, $id);
            $stmt->execute();
            $agregados += $stmt->affected_rows;
        }

        return $agregados;
    }

    public function quitarProductos(int $id_promocion, array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt   = $this->db->prepare(
            "DELETE FROM promocion_productos WHERE id_promocion = ? AND id_producto IN ($marcas)"
        );
        $stmt->bind_param('i' . str_repeat('i', count($ids)), $id_promocion, ...array_map('intval', $ids));
        $stmt->execute();

        return $stmt->affected_rows;
    }

    /** % propio de un producto dentro de la promoción (null = usa el de la promoción). */
    public function actualizarPctProducto(int $id_promocion, int $id_producto, ?float $pct): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE promocion_productos SET pct = ? WHERE id_promocion = ? AND id_producto = ?"
        );
        $stmt->bind_param("dii", $pct, $id_promocion, $id_producto);

        return $stmt->execute();
    }

    // ─── DESCUENTOS VIGENTES ───────────────────────────────────────────────────

    /**
     * Mejor descuento vigente de un producto.
     * Devuelve ['pct' => 25.0, 'etiqueta' => 'HOT SALE', 'id_promocion' => 3] o null si no tiene.
     */
    public function descuentoProducto(int $id_producto): ?array
    {
        $vigente = self::SQL_VIGENTE;

        $stmt = $this->db->prepare(
            "SELECT COALESCE(pp.pct, pr.pct) AS pct, pr.etiqueta, pr.id_promocion, pr.hasta
             FROM promocion_productos pp
             INNER JOIN promociones pr ON pr.id_promocion = pp.id_promocion
             WHERE pp.id_producto = ? AND $vigente
             ORDER BY COALESCE(pp.pct, pr.pct) DESC, pr.id_promocion DESC
             LIMIT 1"
        );
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();

        if (!$fila || (float) $fila['pct'] <= 0) {
            return null;
        }

        return [
            'pct'          => (float) $fila['pct'],
            'etiqueta'     => $fila['etiqueta'],
            'id_promocion' => (int) $fila['id_promocion'],
            'hasta'        => $fila['hasta'],
        ];
    }

    /** Promociones en las que está un producto (todas, vigentes o no). Para el admin. */
    public function promocionesDeProducto(int $id_producto): array
    {
        $stmt = $this->db->prepare(
            "SELECT pr.*, pp.pct AS pct_propio
             FROM promocion_productos pp
             INNER JOIN promociones pr ON pr.id_promocion = pp.id_promocion
             WHERE pp.id_producto = ?
             ORDER BY pr.creado_at DESC"
        );
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}