<?php

require_once __DIR__ . '/Conexion.php';

class Pedido extends Conexion
{
    /** Estados válidos (vienen de GestionPedido, la fuente única). */
    public const ESTADOS = ['pendiente_contacto', 'contactado', 'confirmado', 'listo', 'entregado', 'cancelado'];

    /** Un pedido cuenta como venta concretada en estos estados. */
    public const ESTADOS_VENDIDO = "('confirmado', 'listo', 'entregado')";

    /** Estados que significan "entró y nadie lo atendió todavía". */
    public const ESTADOS_SIN_ATENDER = ['pendiente_contacto', 'pendiente'];

    /** "Sin pagar +N días": confirmados o listos, con saldo pendiente, de hace más de N días. */
    public const DIAS_PAGO_VENCIDO = 3;

    /** Lo que pagó un pedido (alias p): cobros − devoluciones + saldo a favor usado. */
    public const SQL_COBRADO = "(
        (SELECT COALESCE(SUM(CASE WHEN m.tipo = 'cobro' THEN m.monto ELSE -m.monto END), 0)
         FROM movimientos m
         WHERE m.id_pedido = p.id_pedido AND m.tipo IN ('cobro', 'devolucion') AND m.anulado_at IS NULL)
        +
        (SELECT COALESCE(-SUM(s.monto), 0)
         FROM saldos_favor s
         WHERE s.id_pedido = p.id_pedido AND s.monto < 0)
    )";

    private static function sqlVencidos(): string
    {
        return "p.estado IN ('confirmado', 'listo')
                AND p.fecha < NOW() - INTERVAL ? DAY
                AND p.total - " . self::SQL_COBRADO . " > 0.009";
    }

    // ─── CLIENTES ──────────────────────────────────────────────────────────────

    public function buscarCliente($telefono, $email)
    {
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE telefono = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $telefono, $email);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function crearCliente($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO clientes (nombre, apellido, email, telefono, direccion, localidad) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssssss",
            $data['nombre'], $data['apellido'], $data['email'],
            $data['telefono'], $data['direccion'], $data['localidad']
        );
        $stmt->execute();

        return $this->db->insert_id;
    }

    // ─── PEDIDOS ───────────────────────────────────────────────────────────────

    public function crearPedido($id_cliente, $id_usuario_cliente, $total, $observaciones)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO pedidos (id_cliente, id_usuario_cliente, total, subtotal, estado, observaciones)
             VALUES (?, ?, ?, ?, 'pendiente_contacto', ?)"
        );
        $stmt->bind_param("iidds", $id_cliente, $id_usuario_cliente, $total, $total, $observaciones);
        $stmt->execute();

        return $this->db->insert_id;
    }

    public function agregarItem($id_pedido, $item)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO pedido_items
             (id_pedido, id_variante, producto, talle, color, cantidad,
              precio_unitario, precio_lista, id_promocion, costo_unitario, subtotal)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $precioLista = $item['precio_lista'] ?? $item['precio_unitario'];
        $idPromocion = $item['id_promocion'] ?? null;
        $costo       = $item['costo_unitario'] ?? null;

        $stmt->bind_param(
            "iisssiddidd",
            $id_pedido, $item['id_variante'], $item['producto'], $item['talle'], $item['color'],
            $item['cantidad'], $item['precio_unitario'], $precioLista, $idPromocion, $costo, $item['subtotal']
        );

        return $stmt->execute();
    }

    public function buscarPedido($id_pedido)
    {
        $stmt = $this->db->prepare("SELECT * FROM pedidos WHERE id_pedido = ?");
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function listarItems($id_pedido)
    {
        $stmt = $this->db->prepare("SELECT * FROM pedido_items WHERE id_pedido = ?");
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function buscarPedidoCompleto($id_pedido)
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.nombre, c.apellido, c.telefono, c.email, c.direccion, c.localidad,
                    " . self::SQL_COBRADO . " AS cobrado
             FROM pedidos p
             LEFT JOIN clientes c ON c.id_cliente = p.id_cliente
             WHERE p.id_pedido = ?
             LIMIT 1"
        );
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function listarPedidosPorUsuario($id_usuario_cliente)
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, " . self::SQL_COBRADO . " AS cobrado
             FROM pedidos p
             WHERE p.id_usuario_cliente = ?
             ORDER BY p.id_pedido DESC"
        );
        $stmt->bind_param("i", $id_usuario_cliente);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function ultimosPedidos($limite = 5)
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.nombre, c.apellido, c.telefono, " . self::SQL_COBRADO . " AS cobrado
             FROM pedidos p
             LEFT JOIN clientes c ON c.id_cliente = p.id_cliente
             ORDER BY p.id_pedido DESC
             LIMIT ?"
        );
        $stmt->bind_param("i", $limite);
        $stmt->execute();

        return $stmt->get_result();
    }

    /** Guarda qué visitante hizo el pedido y de qué campaña venía (para estadísticas). */
    public function marcarOrigen(int $id_pedido, ?string $visitante, ?string $campania): void
    {
        $stmt = $this->db->prepare("UPDATE pedidos SET visitante = ?, campania = ? WHERE id_pedido = ?");
        $stmt->bind_param("ssi", $visitante, $campania, $id_pedido);
        $stmt->execute();
    }

    public function contarSinAtender(): int
    {
        $marcas = implode(',', array_fill(0, count(self::ESTADOS_SIN_ATENDER), '?'));

        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM pedidos WHERE estado IN ($marcas)");
        $stmt->bind_param(str_repeat('s', count(self::ESTADOS_SIN_ATENDER)), ...self::ESTADOS_SIN_ATENDER);
        $stmt->execute();

        return (int) $stmt->get_result()->fetch_assoc()['total'];
    }

    // ─── LISTADO DEL ADMIN ─────────────────────────────────────────────────────

     /** Órdenes del listado: clave => [texto, ORDER BY] */
    public const ORDENES_LISTADO = [
        'recientes' => ['Más nuevos',   'p.id_pedido DESC'],
        'antiguos'  => ['Más viejos',   'p.id_pedido ASC'],
        'total'     => ['Mayor total',  'p.total DESC'],
        'deuda'     => ['Más deuda',    'falta DESC, p.id_pedido DESC'],
    ];

    /**
     * Filtros del listado del admin:
     *   q, estado ('' | uno de ESTADOS | 'vencidos' | 'por_entregar'),
     *   pago ('' | 'sin_pagar' | 'senado' | 'pagado' | 'debe'),
     *   entrega ('' | 'retiro' | 'envio' | 'sin'), desde, hasta ('Y-m-d')
     */
    private function filtroPedidos(array $f): array
    {
        $where  = [];
        $params = [];
        $types  = '';
        $cobrado = self::SQL_COBRADO;

        if (($f['q'] ?? '') !== '') {
            $like     = '%' . $f['q'] . '%';
            $where[]  = '(c.nombre LIKE ? OR c.apellido LIKE ? OR c.telefono LIKE ? OR c.email LIKE ? OR p.id_pedido = ?)';
            $params   = array_merge($params, [$like, $like, $like, $like, (int) ltrim($f['q'], '#')]);
            $types   .= 'ssssi';
        }

        $estado = $f['estado'] ?? '';
        if ($estado === 'vencidos') {
            $where[]  = self::sqlVencidos();
            $params[] = self::DIAS_PAGO_VENCIDO;
            $types   .= 'i';
        } elseif ($estado === 'por_entregar') {
            $where[] = "p.estado IN ('confirmado', 'listo')";
        } elseif (in_array($estado, self::ESTADOS, true)) {
            $where[]  = 'p.estado = ?';
            $params[] = $estado;
            $types   .= 's';
        }

        switch ($f['pago'] ?? '') {
            case 'sin_pagar':
                $where[] = "p.estado <> 'cancelado' AND $cobrado <= 0.009";
                break;
            case 'senado':
                $where[] = "p.estado <> 'cancelado' AND $cobrado > 0.009 AND $cobrado < p.total - 0.009";
                break;
            case 'pagado':
                $where[] = "p.estado <> 'cancelado' AND $cobrado >= p.total - 0.009";
                break;
            case 'debe':
                $where[] = "p.estado IN " . self::ESTADOS_VENDIDO . " AND p.total - $cobrado > 0.009";
                break;
        }

        switch ($f['entrega'] ?? '') {
            case 'retiro': $where[] = "p.entrega = 'retiro'"; break;
            case 'envio':  $where[] = "p.entrega = 'envio'";  break;
            case 'sin':    $where[] = "p.entrega IS NULL";    break;
        }

        if (!empty($f['desde'])) {
            $where[]  = 'p.fecha >= ?';
            $params[] = $f['desde'] . ' 00:00:00';
            $types   .= 's';
        }
        if (!empty($f['hasta'])) {
            $where[]  = 'p.fecha < ?';
            $params[] = date('Y-m-d', strtotime($f['hasta'] . ' +1 day')) . ' 00:00:00';
            $types   .= 's';
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params, $types];
    }

    public function listarPaginado(int $pagina, int $porPagina, array $f): array
    {
        [$where, $params, $types] = $this->filtroPedidos($f);

        $orden = self::ORDENES_LISTADO[$f['orden'] ?? ''][1] ?? self::ORDENES_LISTADO['recientes'][1];

        $params[] = $porPagina;
        $params[] = ($pagina - 1) * $porPagina;
        $types   .= 'ii';

        $stmt = $this->db->prepare(
            "SELECT p.*, c.nombre, c.apellido, c.telefono, c.email, c.localidad,
                    " . self::SQL_COBRADO . " AS cobrado,
                    p.total - " . self::SQL_COBRADO . " AS falta,
                    (SELECT GROUP_CONCAT(
                                CONCAT(pi.cantidad, '× ', pi.producto,
                                       IF(COALESCE(pi.talle, '') <> '', CONCAT(' · ', pi.talle), ''))
                                ORDER BY pi.id_item SEPARATOR '||')
                     FROM pedido_items pi WHERE pi.id_pedido = p.id_pedido) AS items_txt,
                    (SELECT COALESCE(SUM(pi.cantidad), 0) FROM pedido_items pi WHERE pi.id_pedido = p.id_pedido) AS unidades
             FROM pedidos p
             LEFT JOIN clientes c ON c.id_cliente = p.id_cliente
             $where
             ORDER BY $orden
             LIMIT ? OFFSET ?"
        );
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function contarPedidos(array $f): int
    {
        [$where, $params, $types] = $this->filtroPedidos($f);

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total FROM pedidos p LEFT JOIN clientes c ON c.id_cliente = p.id_cliente $where"
        );
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        return (int) $stmt->get_result()->fetch_assoc()['total'];
    }

    /** Las tarjetas de "qué hay que hacer" del listado. */
    public function resumenListado(): array
    {
        $cobrado = self::SQL_COBRADO;

        $r = $this->db->query(
            "SELECT
                COALESCE(SUM(p.estado = 'pendiente_contacto'), 0)                AS para_contactar,
                COALESCE(SUM(p.estado = 'contactado'), 0)                        AS contactados,
                COALESCE(SUM(p.estado IN ('confirmado', 'listo')), 0)            AS por_entregar,
                COALESCE(SUM(CASE WHEN p.estado IN " . self::ESTADOS_VENDIDO . " AND p.total - $cobrado > 0.009
                                  THEN p.total - $cobrado END), 0)               AS falta_cobrar,
                COALESCE(SUM(p.estado IN " . self::ESTADOS_VENDIDO . " AND p.total - $cobrado > 0.009), 0) AS con_deuda
             FROM pedidos p"
        )->fetch_assoc();

        return array_map('floatval', $r);
    }

    /** Cantidad por estado (para las pestañas), más 'vencidos' y 'todos'. */
    public function contarPorEstado(): array
    {
        $conteo = array_fill_keys(self::ESTADOS, 0);

        $res = $this->db->query("SELECT estado, COUNT(*) AS total FROM pedidos GROUP BY estado");
        while ($row = $res->fetch_assoc()) {
            $conteo[$row['estado']] = (int) $row['total'];
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM pedidos p WHERE " . self::sqlVencidos());
        $dias = self::DIAS_PAGO_VENCIDO;
        $stmt->bind_param("i", $dias);
        $stmt->execute();
        $conteo['vencidos'] = (int) $stmt->get_result()->fetch_assoc()['total'];

        $conteo['todos'] = array_sum(array_intersect_key($conteo, array_flip(self::ESTADOS)));

        return $conteo;
    }

    // ─── DASHBOARD ─────────────────────────────────────────────────────────────

    /** Ventas, pedidos y ganancia de un período (ganancia solo con ítems que tienen costo). */
    public function resumenPeriodo(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS pedidos, COALESCE(SUM(p.total), 0) AS ventas
             FROM pedidos p
             WHERE p.estado IN " . self::ESTADOS_VENDIDO . " AND p.fecha >= ? AND p.fecha < ?"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $ventas = $stmt->get_result()->fetch_assoc();

        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM((pi.precio_unitario - pi.costo_unitario) * pi.cantidad), 0) AS ganancia,
                COALESCE(SUM(CASE WHEN pi.costo_unitario IS NOT NULL THEN pi.subtotal END), 0) AS ventas_con_costo,
                COALESCE(SUM(pi.costo_unitario IS NULL), 0) AS items_sin_costo
             FROM pedido_items pi
             INNER JOIN pedidos p ON p.id_pedido = pi.id_pedido
             WHERE p.estado IN " . self::ESTADOS_VENDIDO . " AND p.fecha >= ? AND p.fecha < ?"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $ganancia = $stmt->get_result()->fetch_assoc();

        $pedidos        = (int) $ventas['pedidos'];
        $totalVentas    = (float) $ventas['ventas'];
        $ventasConCosto = (float) $ganancia['ventas_con_costo'];

        return [
            'ventas'          => $totalVentas,
            'pedidos'         => $pedidos,
            'ticket_promedio' => $pedidos > 0 ? $totalVentas / $pedidos : 0,
            'ganancia'        => (float) $ganancia['ganancia'],
            'margen'          => $ventasConCosto > 0 ? (float) $ganancia['ganancia'] / $ventasConCosto * 100 : null,
            'items_sin_costo' => (int) $ganancia['items_sin_costo'],
        ];
    }

    /** Ventas agrupadas por día. Clave: 'Y-m-d'. */
    public function ventasPorDia(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT DATE(p.fecha) AS dia, COALESCE(SUM(p.total), 0) AS total, COUNT(*) AS pedidos
             FROM pedidos p
             WHERE p.estado IN " . self::ESTADOS_VENDIDO . " AND p.fecha >= ? AND p.fecha < ?
             GROUP BY DATE(p.fecha)"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $res = $stmt->get_result();

        $dias = [];
        while ($row = $res->fetch_assoc()) {
            $dias[$row['dia']] = ['total' => (float) $row['total'], 'pedidos' => (int) $row['pedidos']];
        }
        return $dias;
    }

    /** Productos más vendidos del período (por unidades). */
    public function topProductos(string $desde, string $hasta, int $limite = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT pi.producto,
                    SUM(pi.cantidad) AS unidades,
                    SUM(pi.subtotal) AS ventas,
                    SUM(CASE WHEN pi.costo_unitario IS NOT NULL
                             THEN (pi.precio_unitario - pi.costo_unitario) * pi.cantidad END) AS ganancia
             FROM pedido_items pi
             INNER JOIN pedidos p ON p.id_pedido = pi.id_pedido
             WHERE p.estado IN " . self::ESTADOS_VENDIDO . " AND p.fecha >= ? AND p.fecha < ?
             GROUP BY pi.producto
             ORDER BY unidades DESC, ventas DESC
             LIMIT ?"
        );
        $stmt->bind_param("ssi", $desde, $hasta, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Pedidos que requieren acción. */
    public function alertasPedidos(int $diasPagoVencido = self::DIAS_PAGO_VENCIDO): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(p.estado = 'pendiente_contacto'), 0) AS pendientes_contacto,
                COALESCE(SUM(p.estado = 'listo'), 0)              AS para_entregar,
                COALESCE(SUM(" . self::sqlVencidos() . "), 0)     AS pagos_vencidos
             FROM pedidos p"
        );
        $stmt->bind_param("i", $diasPagoVencido);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return [
            'pendientes_contacto' => (int) $row['pendientes_contacto'],
            'para_entregar'       => (int) $row['para_entregar'],
            'pagos_vencidos'      => (int) $row['pagos_vencidos'],
        ];
    }

    // ─── STOCK (métodos sueltos; la lógica de estados está en GestionPedido) ───

    public function reservarStock($id_variante, $cantidad): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE producto_variantes SET stock_reservado = stock_reservado + ?
             WHERE id_variante = ? AND (stock - stock_reservado) >= ?"
        );
        $stmt->bind_param("iii", $cantidad, $id_variante, $cantidad);
        $stmt->execute();

        return $stmt->affected_rows === 1;
    }

    public function liberarStock($id_variante, $cantidad): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE producto_variantes SET stock_reservado = GREATEST(stock_reservado - ?, 0) WHERE id_variante = ?"
        );
        $stmt->bind_param("ii", $cantidad, $id_variante);
        $stmt->execute();

        return true;
    }

        /** Lo cobrado de verdad (cobros − devoluciones, sin anulados) en un período. */
    public function cobradoPeriodo(string $desde, string $hasta): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN tipo = 'cobro' THEN monto ELSE -monto END), 0) AS c
             FROM movimientos
             WHERE tipo IN ('cobro', 'devolucion') AND anulado_at IS NULL AND fecha >= ? AND fecha < ?"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();

        return (float) $stmt->get_result()->fetch_assoc()['c'];
    }

    /** Cobrado por día. Clave: 'Y-m-d'. */
    public function cobradoPorDia(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT DATE(fecha) AS dia, SUM(CASE WHEN tipo = 'cobro' THEN monto ELSE -monto END) AS total
             FROM movimientos
             WHERE tipo IN ('cobro', 'devolucion') AND anulado_at IS NULL AND fecha >= ? AND fecha < ?
             GROUP BY DATE(fecha)"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $res = $stmt->get_result();

        $dias = [];
        while ($row = $res->fetch_assoc()) {
            $dias[$row['dia']] = ['total' => (float) $row['total'], 'pedidos' => 0];
        }
        return $dias;
    }

    /** Lo del día: pedidos, vendido y cobrado. */
    public function resumenHoy(): array
    {
        $hoy    = date('Y-m-d') . ' 00:00:00';
        $manana = date('Y-m-d', strtotime('+1 day')) . ' 00:00:00';

        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(p.estado <> 'cancelado'), 0) AS pedidos,
                COALESCE(SUM(CASE WHEN p.estado IN " . self::ESTADOS_VENDIDO . " THEN p.total END), 0) AS ventas
             FROM pedidos p
             WHERE p.fecha >= ? AND p.fecha < ?"
        );
        $stmt->bind_param("ss", $hoy, $manana);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();

        return [
            'pedidos' => (int) $r['pedidos'],
            'ventas'  => (float) $r['ventas'],
            'cobrado' => $this->cobradoPeriodo($hoy, $manana),
        ];
    }

    /** Ventas por canal (web, local, instagram…) en el período. */
    public function ventasPorOrigen(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(p.origen, 'web') AS origen, COUNT(*) AS pedidos, SUM(p.total) AS total
             FROM pedidos p
             WHERE p.estado IN " . self::ESTADOS_VENDIDO . " AND p.fecha >= ? AND p.fecha < ?
             GROUP BY origen
             ORDER BY total DESC"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Pedidos listos para entregar, los más viejos primero. */
    public function listosParaEntregar(int $limite = 6): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id_pedido, p.fecha, p.total, p.entrega, c.nombre, c.apellido, c.telefono,
                    " . self::SQL_COBRADO . " AS cobrado
             FROM pedidos p
             LEFT JOIN clientes c ON c.id_cliente = p.id_cliente
             WHERE p.estado = 'listo'
             ORDER BY p.fecha ASC
             LIMIT ?"
        );
        $stmt->bind_param("i", $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    

    // ─── TRANSACCIONES ─────────────────────────────────────────────────────────

    public function begin()    { $this->db->begin_transaction(); }
    public function commit()   { $this->db->commit(); }
    public function rollback() { $this->db->rollback(); }
}