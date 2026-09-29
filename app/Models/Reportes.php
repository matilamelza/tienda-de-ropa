<?php

require_once __DIR__ . '/Conexion.php';

/** Consultas de Estadísticas → Ventas y Clientes. */
class Reportes extends Conexion
{
    /**
     * Qué estados cuentan como venta. Cuando se mergee la etapa 2 (cobros),
     * se ajusta solo acá.
     */
    public const ESTADOS_VENDIDO = "('pagado', 'entregado')";

    private static function vendido(string $alias = 'pe'): string
    {
        return "$alias.estado IN " . self::ESTADOS_VENDIDO;
    }

    // ─── VENTAS ────────────────────────────────────────────────────────────────

    public function resumenVentas(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(DISTINCT pe.id_pedido) AS pedidos,
                COALESCE(SUM(pi.subtotal), 0) AS facturado,
                COALESCE(SUM(pi.cantidad), 0) AS unidades,
                COALESCE(SUM(CASE WHEN pi.costo_unitario IS NOT NULL
                                  THEN pi.subtotal - pi.costo_unitario * pi.cantidad END), 0) AS ganancia,
                COALESCE(SUM(CASE WHEN pi.costo_unitario IS NULL THEN pi.cantidad END), 0) AS unidades_sin_costo,
                COALESCE(SUM(CASE WHEN pi.precio_lista IS NOT NULL
                                  THEN (pi.precio_lista - pi.precio_unitario) * pi.cantidad END), 0) AS descuento
             FROM pedidos pe
             INNER JOIN pedido_items pi ON pi.id_pedido = pe.id_pedido
             WHERE " . self::vendido() . " AND pe.fecha >= ? AND pe.fecha < ?"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();

        $pedidos = (int) $r['pedidos'];

        return [
            'pedidos'            => $pedidos,
            'facturado'          => (float) $r['facturado'],
            'unidades'           => (int) $r['unidades'],
            'ganancia'           => (float) $r['ganancia'],
            'unidades_sin_costo' => (int) $r['unidades_sin_costo'],
            'descuento'          => (float) $r['descuento'],
            'ticket'             => $pedidos > 0 ? (float) $r['facturado'] / $pedidos : 0,
            'por_pedido'         => $pedidos > 0 ? (int) $r['unidades'] / $pedidos : 0,
        ];
    }

    /** Facturado y pedidos por día ('Y-m-d') o por mes ('Y-m'). */
    public function ventasSerie(string $desde, string $hasta, bool $porMes): array
    {
        $formato = $porMes ? '%Y-%m' : '%Y-%m-%d';

        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(pe.fecha, '$formato') AS clave, SUM(pe.total) AS total, COUNT(*) AS pedidos
             FROM pedidos pe
             WHERE " . self::vendido() . " AND pe.fecha >= ? AND pe.fecha < ?
             GROUP BY clave"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $res = $stmt->get_result();

        $datos = [];
        while ($row = $res->fetch_assoc()) {
            $datos[$row['clave']] = ['total' => (float) $row['total'], 'pedidos' => (int) $row['pedidos']];
        }
        return $datos;
    }

    /** Ventas agrupadas por categoría o por marca, con ganancia y margen. */
    public function ventasPor(string $agrupar, string $desde, string $hasta): array
    {
        $join = $agrupar === 'marca'
            ? "LEFT JOIN marcas x ON x.id_marca = p.id_marca"
            : "LEFT JOIN categorias x ON x.id_categoria = p.id_categoria";
        $vacio = $agrupar === 'marca' ? 'Sin marca' : 'Sin categoría';

        $stmt = $this->db->prepare(
            "SELECT COALESCE(x.nombre, '$vacio') AS nombre,
                    SUM(pi.cantidad) AS unidades,
                    SUM(pi.subtotal) AS facturado,
                    SUM(CASE WHEN pi.costo_unitario IS NOT NULL
                             THEN pi.subtotal - pi.costo_unitario * pi.cantidad END) AS ganancia,
                    SUM(CASE WHEN pi.costo_unitario IS NOT NULL THEN pi.subtotal END) AS facturado_con_costo
             FROM pedido_items pi
             INNER JOIN pedidos pe ON pe.id_pedido = pi.id_pedido
             LEFT JOIN producto_variantes pv ON pv.id_variante = pi.id_variante
             LEFT JOIN productos p ON p.id_producto = pv.id_producto
             $join
             WHERE " . self::vendido() . " AND pe.fecha >= ? AND pe.fecha < ?
             GROUP BY nombre
             ORDER BY facturado DESC"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($filas as &$f) {
            $f['margen'] = (float) $f['facturado_con_costo'] > 0
                ? (float) $f['ganancia'] / (float) $f['facturado_con_costo'] * 100
                : null;
        }
        return $filas;
    }

    /** Pedidos y facturado por día de la semana (1=domingo…7=sábado) y por hora. */
    public function ventasPorMomento(string $desde, string $hasta): array
    {
        $resultado = ['dia' => [], 'hora' => []];

        foreach (['dia' => 'DAYOFWEEK', 'hora' => 'HOUR'] as $clave => $funcion) {
            $stmt = $this->db->prepare(
                "SELECT $funcion(pe.fecha) AS k, COUNT(*) AS pedidos, SUM(pe.total) AS total
                 FROM pedidos pe
                 WHERE " . self::vendido() . " AND pe.fecha >= ? AND pe.fecha < ?
                 GROUP BY k"
            );
            $stmt->bind_param("ss", $desde, $hasta);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $resultado[$clave][(int) $row['k']] = ['pedidos' => (int) $row['pedidos'], 'total' => (float) $row['total']];
            }
        }

        return $resultado;
    }

    // ─── CLIENTES ──────────────────────────────────────────────────────────────

    /** Clientes que compraron en el período: nuevos (primera compra acá) y los que ya habían comprado. */
    public function clientesPeriodo(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total, COALESCE(SUM(t.primera >= ?), 0) AS nuevos
             FROM (
                SELECT pe.id_cliente,
                       (SELECT MIN(p2.fecha) FROM pedidos p2
                        WHERE p2.id_cliente = pe.id_cliente AND " . self::vendido('p2') . ") AS primera
                FROM pedidos pe
                WHERE " . self::vendido() . " AND pe.id_cliente IS NOT NULL
                AND pe.fecha >= ? AND pe.fecha < ?
                GROUP BY pe.id_cliente
             ) t"
        );
        $stmt->bind_param("sss", $desde, $desde, $hasta);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();

        return [
            'total'       => (int) $r['total'],
            'nuevos'      => (int) $r['nuevos'],
            'recurrentes' => (int) $r['total'] - (int) $r['nuevos'],
        ];
    }

    /** Histórico: de todos los que compraron alguna vez, cuántos compraron más de una vez. */
    public function recompra(): array
    {
        $r = $this->db->query(
            "SELECT COUNT(*) AS clientes, COALESCE(SUM(n > 1), 0) AS repiten
             FROM (SELECT id_cliente, COUNT(*) AS n FROM pedidos pe
                   WHERE " . self::vendido() . " AND id_cliente IS NOT NULL
                   GROUP BY id_cliente) t"
        )->fetch_assoc();

        return ['clientes' => (int) $r['clientes'], 'repiten' => (int) $r['repiten']];
    }

    /** Los que más gastaron en el período. */
    public function mejoresClientes(string $desde, string $hasta, int $limite = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id_cliente, c.nombre, c.apellido, c.telefono,
                    COUNT(*) AS pedidos, SUM(pe.total) AS total, MAX(pe.fecha) AS ultima
             FROM pedidos pe
             INNER JOIN clientes c ON c.id_cliente = pe.id_cliente
             WHERE " . self::vendido() . " AND pe.fecha >= ? AND pe.fecha < ?
             GROUP BY c.id_cliente, c.nombre, c.apellido, c.telefono
             ORDER BY total DESC
             LIMIT ?"
        );
        $stmt->bind_param("ssi", $desde, $hasta, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Compraron hace entre 3 meses y 1 año y no volvieron. No depende del período elegido. */
    public function paraReactivar(int $limite = 20): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id_cliente, c.nombre, c.apellido, c.telefono,
                    COUNT(*) AS pedidos, SUM(pe.total) AS total, MAX(pe.fecha) AS ultima
             FROM pedidos pe
             INNER JOIN clientes c ON c.id_cliente = pe.id_cliente
             WHERE " . self::vendido() . "
             GROUP BY c.id_cliente, c.nombre, c.apellido, c.telefono
             HAVING ultima < NOW() - INTERVAL 90 DAY AND ultima >= NOW() - INTERVAL 365 DAY
             ORDER BY total DESC
             LIMIT ?"
        );
        $stmt->bind_param("i", $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}