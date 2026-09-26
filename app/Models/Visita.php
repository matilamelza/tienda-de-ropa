<?php

require_once __DIR__ . '/Conexion.php';

class Visita extends Conexion
{
    public function registrar(array $d): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO visitas (visitante, tipo, id_ref, termino, resultados, origen, dispositivo)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssisiss",
            $d['visitante'],
            $d['tipo'],
            $d['id_ref'],
            $d['termino'],
            $d['resultados'],
            $d['origen'],
            $d['dispositivo']
        );
        $stmt->execute();
    }

    /** Borra visitas de más de N días. */
    public function limpiarViejas(int $dias = 365): void
    {
        $stmt = $this->db->prepare("DELETE FROM visitas WHERE fecha < NOW() - INTERVAL ? DAY");
        $stmt->bind_param("i", $dias);
        $stmt->execute();
    }

        /** Visitantes únicos y páginas vistas del período. */
    public function resumen(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT visitante) AS visitantes, COUNT(*) AS vistas
             FROM visitas WHERE fecha >= ? AND fecha < ?"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return [
            'visitantes' => (int) $row['visitantes'],
            'vistas'     => (int) $row['vistas'],
        ];
    }

    /** Visitantes únicos y vistas por día. Clave: 'Y-m-d'. */
    public function porDia(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT DATE(fecha) AS dia, COUNT(DISTINCT visitante) AS visitantes, COUNT(*) AS vistas
             FROM visitas WHERE fecha >= ? AND fecha < ?
             GROUP BY DATE(fecha)"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $res = $stmt->get_result();

        $dias = [];
        while ($row = $res->fetch_assoc()) {
            $dias[$row['dia']] = [
                'total'   => (int) $row['visitantes'],  // 'total' para reutilizar el gráfico del dashboard
                'pedidos' => (int) $row['vistas'],
            ];
        }

        return $dias;
    }

    /** Productos más vistos, con las unidades que se vendieron en el mismo período. */
    public function topProductos(string $desde, string $hasta, int $limite = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                v.id_ref AS id_producto,
                p.nombre,
                COUNT(*) AS vistas,
                COUNT(DISTINCT v.visitante) AS visitantes,
                (SELECT COALESCE(SUM(pi.cantidad), 0)
                 FROM pedido_items pi
                 INNER JOIN producto_variantes pv ON pv.id_variante = pi.id_variante
                 INNER JOIN pedidos pe ON pe.id_pedido = pi.id_pedido
                 WHERE pv.id_producto = v.id_ref
                 AND pe.estado IN ('pagado', 'entregado')
                 AND pe.fecha >= ? AND pe.fecha < ?) AS vendidas
             FROM visitas v
             INNER JOIN productos p ON p.id_producto = v.id_ref
             WHERE v.tipo = 'producto' AND v.fecha >= ? AND v.fecha < ?
             GROUP BY v.id_ref, p.nombre
             ORDER BY vistas DESC
             LIMIT ?"
        );
        $stmt->bind_param("ssssi", $desde, $hasta, $desde, $hasta, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Categorías más vistas: suma las visitas a la página de la categoría
     * y las visitas a productos de esa categoría.
     */
    public function topCategorias(string $desde, string $hasta, int $limite = 8): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id_categoria, c.nombre, COUNT(*) AS vistas
             FROM visitas v
             LEFT JOIN productos p ON v.tipo = 'producto' AND p.id_producto = v.id_ref
             INNER JOIN categorias c ON c.id_categoria =
                   CASE WHEN v.tipo = 'categoria' THEN v.id_ref ELSE p.id_categoria END
             WHERE v.tipo IN ('categoria', 'producto')
             AND v.fecha >= ? AND v.fecha < ?
             GROUP BY c.id_categoria, c.nombre
             ORDER BY vistas DESC
             LIMIT ?"
        );
        $stmt->bind_param("ssi", $desde, $hasta, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Marcas más vistas: filtro por marca + visitas a productos de esa marca. */
    public function topMarcas(string $desde, string $hasta, int $limite = 8): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.id_marca, m.nombre, COUNT(*) AS vistas
             FROM visitas v
             LEFT JOIN productos p ON v.tipo = 'producto' AND p.id_producto = v.id_ref
             INNER JOIN marcas m ON m.id_marca =
                   CASE WHEN v.tipo = 'marca' THEN v.id_ref ELSE p.id_marca END
             WHERE v.tipo IN ('marca', 'producto')
             AND v.fecha >= ? AND v.fecha < ?
             GROUP BY m.id_marca, m.nombre
             ORDER BY vistas DESC
             LIMIT ?"
        );
        $stmt->bind_param("ssi", $desde, $hasta, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Lo más buscado. Con $soloSinResultados = true: lo que buscan y no encuentran. */
    public function busquedas(string $desde, string $hasta, bool $soloSinResultados = false, int $limite = 10): array
    {
        $extra = $soloSinResultados ? 'AND resultados = 0' : '';

        $stmt = $this->db->prepare(
            "SELECT termino, COUNT(*) AS veces, COUNT(DISTINCT visitante) AS personas
             FROM visitas
             WHERE tipo = 'busqueda' AND termino IS NOT NULL AND termino <> ''
             AND fecha >= ? AND fecha < ? $extra
             GROUP BY termino
             ORDER BY veces DESC
             LIMIT ?"
        );
        $stmt->bind_param("ssi", $desde, $hasta, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** De dónde llegan (solo entradas al sitio, no navegación interna). Por visitantes únicos. */
    public function origenes(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT origen, COUNT(DISTINCT visitante) AS visitantes
             FROM visitas
             WHERE origen IS NOT NULL AND fecha >= ? AND fecha < ?
             GROUP BY origen
             ORDER BY visitantes DESC"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Visitantes únicos por dispositivo: ['mobile' => n, 'desktop' => n] */
    public function dispositivos(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT dispositivo, COUNT(DISTINCT visitante) AS visitantes
             FROM visitas WHERE fecha >= ? AND fecha < ?
             GROUP BY dispositivo"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $res = $stmt->get_result();

        $datos = ['mobile' => 0, 'desktop' => 0];
        while ($row = $res->fetch_assoc()) {
            $datos[$row['dispositivo']] = (int) $row['visitantes'];
        }

        return $datos;
    }

    /**
     * Embudo de compra por visitantes únicos:
     * entraron → vieron un producto → llegaron al carrito → llegaron al checkout → hicieron un pedido
     */
    public function embudo(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(DISTINCT visitante) AS entraron,
                COUNT(DISTINCT CASE WHEN tipo = 'producto' THEN visitante END) AS vieron_producto,
                COUNT(DISTINCT CASE WHEN tipo = 'carrito'  THEN visitante END) AS carrito,
                COUNT(DISTINCT CASE WHEN tipo = 'checkout' THEN visitante END) AS checkout
             FROM visitas WHERE fecha >= ? AND fecha < ?"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $embudo = $stmt->get_result()->fetch_assoc();

        // Pedidos hechos en el período (cualquier estado salvo cancelado)
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS pedidos FROM pedidos
             WHERE estado <> 'cancelado' AND fecha >= ? AND fecha < ?"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $embudo['pedidos'] = (int) $stmt->get_result()->fetch_assoc()['pedidos'];

        return array_map('intval', $embudo);
    }
}