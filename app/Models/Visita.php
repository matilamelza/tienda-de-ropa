<?php

require_once __DIR__ . '/Conexion.php';

class Visita extends Conexion
{
    public function registrar(array $d): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO visitas (visitante, tipo, id_ref, termino, resultados, origen, campania, pais, provincia, ciudad, dispositivo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $campania  = $d['campania']  ?? null;
        $pais      = $d['pais']      ?? null;
        $provincia = $d['provincia'] ?? null;
        $ciudad    = $d['ciudad']    ?? null;

        $stmt->bind_param(
            "ssisissssss",
            $d['visitante'],
            $d['tipo'],
            $d['id_ref'],
            $d['termino'],
            $d['resultados'],
            $d['origen'],
            $campania,
            $pais,
            $provincia,
            $ciudad,
            $d['dispositivo']
        );
        $stmt->execute();
    }

        /** Visitantes únicos por ciudad (con su provincia) o por provincia. Aproximado. */
        /** Visitantes únicos por ciudad (con su provincia) o por provincia, solo de un país. Aproximado. */
    public function ubicaciones(string $desde, string $hasta, string $nivel = 'ciudad', int $limite = 15, string $pais = 'AR'): array
    {
        $campos = $nivel === 'provincia'
            ? "provincia AS nombre"
            : "CONCAT(ciudad, IF(provincia IS NULL OR provincia = ciudad, '', CONCAT(', ', provincia))) AS nombre";
        $filtro = $nivel === 'provincia' ? "provincia IS NOT NULL" : "ciudad IS NOT NULL";

        $stmt = $this->db->prepare(
            "SELECT $campos, COUNT(DISTINCT visitante) AS visitantes
             FROM visitas
             WHERE $filtro AND pais = ? AND fecha >= ? AND fecha < ?
             GROUP BY nombre
             ORDER BY visitantes DESC
             LIMIT ?"
        );
        $stmt->bind_param("sssi", $pais, $desde, $hasta, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Visitantes de otros países (en general, robots o VPN), con los países más comunes. */
    public function fueraDelPais(string $desde, string $hasta, string $pais = 'AR'): array
    {
        $stmt = $this->db->prepare(
            "SELECT pais, COUNT(DISTINCT visitante) AS visitantes
             FROM visitas
             WHERE pais IS NOT NULL AND pais <> ? AND fecha >= ? AND fecha < ?
             GROUP BY pais
             ORDER BY visitantes DESC"
        );
        $stmt->bind_param("sss", $pais, $desde, $hasta);
        $stmt->execute();
        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'total'  => array_sum(array_column($filas, 'visitantes')),
            'paises' => array_slice($filas, 0, 5),
            'afuera'       => $visitaModel->fueraDelPais($d, $h),
        ];
    }

    /** Borra visitas de más de N días. */
    public function limpiarViejas(int $dias = 365): void
    {
        foreach (['visitas', 'eventos'] as $tabla) {
            $stmt = $this->db->prepare("DELETE FROM $tabla WHERE fecha < NOW() - INTERVAL ? DAY");
            $stmt->bind_param("i", $dias);
            $stmt->execute();
        }
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
             WHERE v.tipo = 'producto'
             AND p.eliminado_at IS NULL
             AND v.fecha >= ? AND v.fecha < ?
             GROUP BY v.id_ref, p.nombre
             ORDER BY vistas DESC
             LIMIT ?"
        );
        $stmt->bind_param("ssssi", $desde, $hasta, $desde, $hasta, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Categorías más vistas: visitas a la página de la categoría + visitas a productos de esa categoría.
     * No cuenta productos eliminados ni la categoría interna de archivo.
     */
    public function topCategorias(string $desde, string $hasta, int $limite = 8): array
    {
        $archivo = Categoria::SLUG_ARCHIVO;

        $stmt = $this->db->prepare(
            "SELECT c.id_categoria, c.nombre, COUNT(*) AS vistas
             FROM visitas v
             LEFT JOIN productos p ON v.tipo = 'producto' AND p.id_producto = v.id_ref
             INNER JOIN categorias c ON c.id_categoria =
                   CASE WHEN v.tipo = 'categoria' THEN v.id_ref ELSE p.id_categoria END
             WHERE v.tipo IN ('categoria', 'producto')
             AND (v.tipo = 'categoria' OR p.eliminado_at IS NULL)
             AND c.slug <> ?
             AND v.fecha >= ? AND v.fecha < ?
             GROUP BY c.id_categoria, c.nombre
             ORDER BY vistas DESC
             LIMIT ?"
        );
        $stmt->bind_param("sssi", $archivo, $desde, $hasta, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Marcas más vistas: filtro por marca + visitas a productos de esa marca (sin eliminados). */
    public function topMarcas(string $desde, string $hasta, int $limite = 8): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.id_marca, m.nombre, COUNT(*) AS vistas
             FROM visitas v
             LEFT JOIN productos p ON v.tipo = 'producto' AND p.id_producto = v.id_ref
             INNER JOIN marcas m ON m.id_marca =
                   CASE WHEN v.tipo = 'marca' THEN v.id_ref ELSE p.id_marca END
             WHERE v.tipo IN ('marca', 'producto')
             AND (v.tipo = 'marca' OR p.eliminado_at IS NULL)
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

        /**
     * Diagnóstico temporal de estadísticas: revisa la base e intenta guardar
     * una visita y un evento de prueba (y los borra). Devuelve lo que encontró.
     */
    public function diagnostico(): array
    {
        $r = [];

        $r['Base de datos en uso'] = $this->db->query("SELECT DATABASE() AS b")->fetch_assoc()['b'];
        $r['Hora de MySQL']        = $this->db->query("SELECT NOW() AS n")->fetch_assoc()['n'];
        $r['Hora de PHP']          = date('Y-m-d H:i:s');

        $r['Columna visitas.campania'] = $this->db->query("SHOW COLUMNS FROM visitas LIKE 'campania'")->num_rows ? 'OK' : 'FALTA';
        $r['Tabla eventos']            = $this->db->query("SHOW TABLES LIKE 'eventos'")->num_rows ? 'OK' : 'FALTA';
        $r['Columna pedidos.visitante'] = $this->db->query("SHOW COLUMNS FROM pedidos LIKE 'visitante'")->num_rows ? 'OK' : 'FALTA';

        $r['Última visita guardada'] = $this->db->query("SELECT MAX(fecha) AS f FROM visitas")->fetch_assoc()['f'] ?? '(ninguna)';

        // Prueba real: guardar una visita
        try {
            $this->registrar([
                'visitante' => str_repeat('0', 32), 'tipo' => 'otra', 'id_ref' => null, 'termino' => 'diagnostico',
                'resultados' => null, 'origen' => 'directo', 'campania' => 'diagnostico', 'dispositivo' => 'desktop',
            ]);
            $this->db->query("DELETE FROM visitas WHERE termino = 'diagnostico' AND visitante = '" . str_repeat('0', 32) . "'");
            $r['Guardar una visita'] = 'OK';
        } catch (Throwable $e) {
            $r['Guardar una visita'] = 'ERROR: ' . $e->getMessage();
        }

        // Prueba real: guardar un evento
        try {
            $idProd = (int) ($this->db->query("SELECT id_producto FROM productos LIMIT 1")->fetch_assoc()['id_producto'] ?? 0);
            (new Evento())->registrar([
                'visitante' => str_repeat('0', 32), 'tipo' => 'talle', 'id_producto' => $idProd, 'id_variante' => null,
                'talle' => 'diag', 'con_stock' => 1, 'cantidad' => null, 'campania' => 'diagnostico',
            ]);
            $this->db->query("DELETE FROM eventos WHERE talle = 'diag' AND visitante = '" . str_repeat('0', 32) . "'");
            $r['Guardar un evento'] = 'OK';
        } catch (Throwable $e) {
            $r['Guardar un evento'] = 'ERROR: ' . $e->getMessage();
        }

        $r['Últimos 5 eventos'] = $this->db->query(
            "SELECT fecha, tipo, id_producto, talle, con_stock, cantidad, campania FROM eventos ORDER BY id_evento DESC LIMIT 5"
        )->fetch_all(MYSQLI_ASSOC);

        return $r;
    }

        // ─── TRÁFICO ───────────────────────────────────────────────────────────────

    /** Visitantes únicos y vistas por mes. Clave: 'Y-m'. */
    public function porMes(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, COUNT(DISTINCT visitante) AS visitantes, COUNT(*) AS vistas
             FROM visitas WHERE fecha >= ? AND fecha < ?
             GROUP BY mes"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $res = $stmt->get_result();

        $meses = [];
        while ($row = $res->fetch_assoc()) {
            $meses[$row['mes']] = ['total' => (int) $row['visitantes'], 'pedidos' => (int) $row['vistas']];
        }
        return $meses;
    }

    /** Mapa de calor: visitantes únicos por día de la semana (1=domingo … 7=sábado) y hora. */
    public function mapaCalor(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT DAYOFWEEK(fecha) AS dia, HOUR(fecha) AS hora, COUNT(DISTINCT visitante) AS visitantes
             FROM visitas WHERE fecha >= ? AND fecha < ?
             GROUP BY dia, hora"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $res = $stmt->get_result();

        $mapa = [];
        while ($row = $res->fetch_assoc()) {
            $mapa[(int) $row['dia']][(int) $row['hora']] = (int) $row['visitantes'];
        }
        return $mapa;
    }

    /** Visitantes nuevos (primera visita dentro del período) y los que ya habían venido antes. */
    public function nuevosVsRecurrentes(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(t.primera >= ?), 0) AS nuevos
             FROM (
                SELECT v.visitante,
                       (SELECT MIN(v2.fecha) FROM visitas v2 WHERE v2.visitante = v.visitante) AS primera
                FROM (SELECT DISTINCT visitante FROM visitas WHERE fecha >= ? AND fecha < ?) v
             ) t"
        );
        $stmt->bind_param("sss", $desde, $desde, $hasta);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();

        $total  = (int) $r['total'];
        $nuevos = (int) $r['nuevos'];

        return ['total' => $total, 'nuevos' => $nuevos, 'recurrentes' => $total - $nuevos];
    }

    /**
     * Sesiones: una sesión termina si pasan más de 30 minutos sin actividad.
     * Devuelve sesiones, páginas por sesión, rebote (sesiones de 1 página) y duración promedio.
     */
    public function sesiones(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "WITH v AS (
                SELECT visitante, fecha,
                       CASE WHEN LAG(fecha) OVER w IS NULL
                                 OR TIMESTAMPDIFF(MINUTE, LAG(fecha) OVER w, fecha) > 30
                            THEN 1 ELSE 0 END AS nueva
                FROM visitas
                WHERE fecha >= ? AND fecha < ?
                WINDOW w AS (PARTITION BY visitante ORDER BY fecha)
             ),
             s AS (
                SELECT visitante, fecha,
                       SUM(nueva) OVER (PARTITION BY visitante ORDER BY fecha ROWS UNBOUNDED PRECEDING) AS nro
                FROM v
             ),
             ses AS (
                SELECT COUNT(*) AS paginas, TIMESTAMPDIFF(SECOND, MIN(fecha), MAX(fecha)) AS duracion
                FROM s GROUP BY visitante, nro
             )
             SELECT COUNT(*)                                       AS sesiones,
                    COALESCE(AVG(paginas), 0)                      AS paginas_por_sesion,
                    COALESCE(SUM(paginas = 1), 0)                  AS rebotes,
                    COALESCE(AVG(CASE WHEN paginas > 1 THEN duracion END), 0) AS duracion
             FROM ses"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();

        $sesiones = (int) $r['sesiones'];

        return [
            'sesiones'           => $sesiones,
            'paginas_por_sesion' => (float) $r['paginas_por_sesion'],
            'rebote_pct'         => $sesiones > 0 ? (int) $r['rebotes'] / $sesiones * 100 : 0,
            'duracion'           => (int) round($r['duracion']),
        ];
    }

    /** Primera página de cada sesión: por dónde entra la gente. */
    public function paginasEntrada(string $desde, string $hasta, int $limite = 10): array
    {
        $stmt = $this->db->prepare(
            "WITH v AS (
                SELECT visitante, fecha, tipo, id_ref, termino,
                       CASE WHEN LAG(fecha) OVER w IS NULL
                                 OR TIMESTAMPDIFF(MINUTE, LAG(fecha) OVER w, fecha) > 30
                            THEN 1 ELSE 0 END AS nueva
                FROM visitas
                WHERE fecha >= ? AND fecha < ?
                WINDOW w AS (PARTITION BY visitante ORDER BY fecha)
             )
             SELECT v.tipo, v.id_ref, COUNT(*) AS entradas,
                    COALESCE(p.nombre, c.nombre, m.nombre, v.termino) AS nombre
             FROM v
             LEFT JOIN productos  p ON v.tipo = 'producto'  AND p.id_producto  = v.id_ref
             LEFT JOIN categorias c ON v.tipo = 'categoria' AND c.id_categoria = v.id_ref
             LEFT JOIN marcas     m ON v.tipo = 'marca'     AND m.id_marca     = v.id_ref
             WHERE v.nueva = 1
             GROUP BY v.tipo, v.id_ref, nombre
             ORDER BY entradas DESC
             LIMIT ?"
        );
        $stmt->bind_param("ssi", $desde, $hasta, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}