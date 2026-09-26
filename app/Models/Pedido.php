<?php

require_once __DIR__ . '/Conexion.php';

class Pedido extends Conexion
{
    // ─── CLIENTES ──────────────────────────────────────────────────────────────

    public function buscarCliente($telefono, $email)
    {
        $sql = "SELECT * FROM clientes 
                WHERE telefono = ? OR email = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $telefono, $email);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function crearCliente($data)
    {
        $sql = "INSERT INTO clientes 
                (nombre, apellido, email, telefono, direccion, localidad)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "ssssss",
            $data['nombre'],
            $data['apellido'],
            $data['email'],
            $data['telefono'],
            $data['direccion'],
            $data['localidad']
        );

        $stmt->execute();

        return $this->db->insert_id;
    }

    // ─── PEDIDOS ───────────────────────────────────────────────────────────────

    public function crearPedido($id_cliente, $id_usuario_cliente, $total, $observaciones)
    {
        $sql = "INSERT INTO pedidos 
                (id_cliente, id_usuario_cliente, total, estado, observaciones)
                VALUES (?, ?, ?, 'pendiente_contacto', ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iids", $id_cliente, $id_usuario_cliente, $total, $observaciones);
        $stmt->execute();

        return $this->db->insert_id;
    }

    public function agregarItem($id_pedido, $item)
    {
        $sql = "INSERT INTO pedido_items
                (id_pedido, id_variante, producto, talle, color, cantidad, precio_unitario, costo_unitario, subtotal)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "iisssiddd",
            $id_pedido,
            $item['id_variante'],
            $item['producto'],
            $item['talle'],
            $item['color'],
            $item['cantidad'],
            $item['precio_unitario'],
            $item['costo_unitario'],
            $item['subtotal']
        );

        return $stmt->execute();
    }

    public function listarPedidos()
    {
        $sql = "SELECT 
                    p.*,
                    c.nombre,
                    c.apellido,
                    c.telefono,
                    c.email
                FROM pedidos p
                LEFT JOIN clientes c ON c.id_cliente = p.id_cliente
                ORDER BY p.id_pedido DESC";

        return $this->db->query($sql);
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

    public function actualizarEstado($id_pedido, $estado)
    {
        $sql = "UPDATE pedidos SET estado = ? WHERE id_pedido = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $estado, $id_pedido);

        return $stmt->execute();
    }

    public function buscarPedidoCompleto($id_pedido)
    {
        $sql = "SELECT 
                    p.*,
                    c.nombre,
                    c.apellido,
                    c.telefono,
                    c.email,
                    c.direccion,
                    c.localidad
                FROM pedidos p
                LEFT JOIN clientes c ON c.id_cliente = p.id_cliente
                WHERE p.id_pedido = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // ─── STOCK ─────────────────────────────────────────────────────────────────

    /** Resta del stock real, solo si hay disponible (stock - reservado) suficiente. */
    public function descontarStock($id_variante, $cantidad): bool
    {
        $sql = "UPDATE producto_variantes
                SET stock = stock - ?
                WHERE id_variante = ?
                AND (stock - stock_reservado) >= ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iii", $cantidad, $id_variante, $cantidad);
        $stmt->execute();

        return $stmt->affected_rows === 1;
    }

    /** Suma al stock real (pedido pagado/entregado que se cancela). */
    public function devolverStock($id_variante, $cantidad): bool
    {
        $sql = "UPDATE producto_variantes
                SET stock = stock + ?
                WHERE id_variante = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $cantidad, $id_variante);
        $stmt->execute();

        return $stmt->affected_rows === 1;
    }

    /** Reserva stock, solo si hay disponible suficiente. */
    public function reservarStock($id_variante, $cantidad): bool
    {
        $sql = "UPDATE producto_variantes
                SET stock_reservado = stock_reservado + ?
                WHERE id_variante = ?
                AND (stock - stock_reservado) >= ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iii", $cantidad, $id_variante, $cantidad);
        $stmt->execute();

        return $stmt->affected_rows === 1;
    }

    /** Libera una reserva (nunca deja stock_reservado negativo). */
    public function liberarStock($id_variante, $cantidad): bool
    {
        $sql = "UPDATE producto_variantes
                SET stock_reservado = IF(stock_reservado >= ?, stock_reservado - ?, 0)
                WHERE id_variante = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iii", $cantidad, $cantidad, $id_variante);
        $stmt->execute();

        return true;
    }

    /** Devuelve el grupo de stock de un estado: libre | reservado | descontado */
    public static function grupoStock(string $estado): string
    {
        if ($estado === 'pendiente_pago') {
            return 'reservado';
        }
        if (in_array($estado, ['pagado', 'entregado'], true)) {
            return 'descontado';
        }
        return 'libre';
    }

    /**
     * Aplica al stock el cambio de estado de un pedido.
     * Devuelve false si algún ítem no tiene stock suficiente.
     * Debe llamarse dentro de una transacción (si devuelve false, hacer rollback).
     */
    public function aplicarCambioStock(int $id_pedido, string $estadoAnterior, string $estadoNuevo): bool
    {
        $desde = self::grupoStock($estadoAnterior);
        $hasta = self::grupoStock($estadoNuevo);

        if ($desde === $hasta) {
            return true;
        }

        $res   = $this->listarItems($id_pedido);
        $items = [];
        while ($row = $res->fetch_assoc()) {
            $items[] = $row;
        }

        foreach ($items as $item) {
            $id   = (int) $item['id_variante'];
            $cant = (int) $item['cantidad'];

            // 1) Deshacer lo que hacía el estado anterior
            if ($desde === 'reservado') {
                $this->liberarStock($id, $cant);
            } elseif ($desde === 'descontado') {
                $this->devolverStock($id, $cant);
            }

            // 2) Aplicar lo que pide el estado nuevo
            if ($hasta === 'reservado' && !$this->reservarStock($id, $cant)) {
                return false;
            }
            if ($hasta === 'descontado' && !$this->descontarStock($id, $cant)) {
                return false;
            }
        }

        return true;
    }

    // ─── DASHBOARD ─────────────────────────────────────────────────────────────

    public function resumenDashboard()
    {
        $sql = "SELECT
            COUNT(*) AS total_pedidos,
            SUM(CASE WHEN estado = 'pendiente_contacto' THEN 1 ELSE 0 END) AS pendientes_contacto,
            SUM(CASE WHEN estado = 'pendiente_pago' THEN 1 ELSE 0 END) AS pendientes_pago,
            SUM(CASE WHEN estado = 'pagado' THEN 1 ELSE 0 END) AS pagados,
            SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) AS cancelados,
            COALESCE(SUM(CASE WHEN estado IN ('pagado','entregado') THEN total ELSE 0 END), 0) AS total_vendido,
            COALESCE(SUM(CASE WHEN estado IN ('pendiente_contacto','pendiente_pago') THEN total ELSE 0 END), 0) AS total_pendiente
        FROM pedidos";

        return $this->db->query($sql)->fetch_assoc();
    }

    /**
     * Ventas, pedidos y ganancia de un período.
     * La ganancia se calcula solo con los ítems que tienen costo cargado.
     */
    public function resumenPeriodo(string $desde, string $hasta): array
    {
        // Ventas y cantidad de pedidos
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS pedidos, COALESCE(SUM(total), 0) AS ventas
             FROM pedidos
             WHERE estado IN ('pagado', 'entregado')
             AND fecha >= ? AND fecha < ?"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $ventas = $stmt->get_result()->fetch_assoc();

        // Ganancia (sobre ítems con costo) y cuántos ítems no tienen costo
        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM((pi.precio_unitario - pi.costo_unitario) * pi.cantidad), 0) AS ganancia,
                COALESCE(SUM(CASE WHEN pi.costo_unitario IS NOT NULL THEN pi.subtotal END), 0) AS ventas_con_costo,
                COALESCE(SUM(pi.costo_unitario IS NULL), 0) AS items_sin_costo
             FROM pedido_items pi
             INNER JOIN pedidos p ON p.id_pedido = pi.id_pedido
             WHERE p.estado IN ('pagado', 'entregado')
             AND p.fecha >= ? AND p.fecha < ?"
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

    /** Ventas agrupadas por día (solo los días con ventas). Clave: 'Y-m-d'. */
    public function ventasPorDia(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT DATE(fecha) AS dia, COALESCE(SUM(total), 0) AS total, COUNT(*) AS pedidos
             FROM pedidos
             WHERE estado IN ('pagado', 'entregado')
             AND fecha >= ? AND fecha < ?
             GROUP BY DATE(fecha)"
        );
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $res = $stmt->get_result();

        $dias = [];
        while ($row = $res->fetch_assoc()) {
            $dias[$row['dia']] = [
                'total'   => (float) $row['total'],
                'pedidos' => (int) $row['pedidos'],
            ];
        }

        return $dias;
    }

    /** Productos más vendidos del período (por unidades). */
    public function topProductos(string $desde, string $hasta, int $limite = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                pi.producto,
                SUM(pi.cantidad) AS unidades,
                SUM(pi.subtotal) AS ventas,
                SUM(CASE WHEN pi.costo_unitario IS NOT NULL
                         THEN (pi.precio_unitario - pi.costo_unitario) * pi.cantidad END) AS ganancia
             FROM pedido_items pi
             INNER JOIN pedidos p ON p.id_pedido = pi.id_pedido
             WHERE p.estado IN ('pagado', 'entregado')
             AND p.fecha >= ? AND p.fecha < ?
             GROUP BY pi.producto
             ORDER BY unidades DESC, ventas DESC
             LIMIT ?"
        );
        $stmt->bind_param("ssi", $desde, $hasta, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Pedidos que requieren acción. */
    public function alertasPedidos(int $diasPagoVencido = 3): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(estado = 'pendiente_contacto'), 0) AS pendientes_contacto,
                COALESCE(SUM(estado = 'pendiente_pago' AND fecha < NOW() - INTERVAL ? DAY), 0) AS pagos_vencidos
             FROM pedidos"
        );
        $stmt->bind_param("i", $diasPagoVencido);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return [
            'pendientes_contacto' => (int) $row['pendientes_contacto'],
            'pagos_vencidos'      => (int) $row['pagos_vencidos'],
        ];
    }

    public function ultimosPedidos($limite = 5)
    {
        $sql = "SELECT 
                    p.*,
                    c.nombre,
                    c.apellido,
                    c.telefono
                FROM pedidos p
                LEFT JOIN clientes c ON c.id_cliente = p.id_cliente
                ORDER BY p.id_pedido DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $limite);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function listarPedidosPorUsuario($id_usuario_cliente)
    {
        $sql = "SELECT *
                FROM pedidos
                WHERE id_usuario_cliente = ?
                ORDER BY id_pedido DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_usuario_cliente);
        $stmt->execute();

        return $stmt->get_result();
    }

        public const ESTADOS = [
        'pendiente_contacto', 'contactado', 'pendiente_pago',
        'pagado', 'entregado', 'cancelado',
    ];

    /** Filtro especial: pendientes de pago con más de N días. */
    public const DIAS_PAGO_VENCIDO = 3;

    /**
     * Arma el WHERE según búsqueda y estado.
     * $estado: '' (todos), uno de ESTADOS, o 'vencidos'.
     */
    private function filtroPedidos(string $busqueda, string $estado): array
    {
        $where  = [];
        $params = [];
        $types  = '';

        if ($busqueda !== '') {
            $like     = '%' . $busqueda . '%';
            $where[]  = '(c.nombre LIKE ? OR c.apellido LIKE ? OR c.telefono LIKE ? OR c.email LIKE ? OR p.id_pedido LIKE ?)';
            $params   = array_merge($params, [$like, $like, $like, $like, $like]);
            $types   .= 'sssss';
        }

        if ($estado === 'vencidos') {
            $where[]  = "p.estado = 'pendiente_pago' AND p.fecha < NOW() - INTERVAL ? DAY";
            $params[] = self::DIAS_PAGO_VENCIDO;
            $types   .= 'i';
        } elseif (in_array($estado, self::ESTADOS, true)) {
            $where[]  = 'p.estado = ?';
            $params[] = $estado;
            $types   .= 's';
        }

        $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$sqlWhere, $params, $types];
    }

    public function listarPaginado(int $pagina = 1, int $porPagina = 20, string $busqueda = '', string $estado = ''): array
    {
        [$where, $params, $types] = $this->filtroPedidos($busqueda, $estado);

        $offset   = ($pagina - 1) * $porPagina;
        $params[] = $porPagina;
        $params[] = $offset;
        $types   .= 'ii';

        $sql = "SELECT p.*, c.nombre, c.apellido, c.telefono, c.email
                FROM pedidos p
                LEFT JOIN clientes c ON c.id_cliente = p.id_cliente
                $where
                ORDER BY p.id_pedido DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function contarPedidos(string $busqueda = '', string $estado = ''): int
    {
        [$where, $params, $types] = $this->filtroPedidos($busqueda, $estado);

        $sql = "SELECT COUNT(*) AS total
                FROM pedidos p
                LEFT JOIN clientes c ON c.id_cliente = p.id_cliente
                $where";

        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        return (int) $stmt->get_result()->fetch_assoc()['total'];
    }

    /** Cantidad de pedidos por estado (para las pestañas). Incluye 'vencidos'. */
    public function contarPorEstado(): array
    {
        $conteo = array_fill_keys(self::ESTADOS, 0);

        $res = $this->db->query("SELECT estado, COUNT(*) AS total FROM pedidos GROUP BY estado");
        while ($row = $res->fetch_assoc()) {
            $conteo[$row['estado']] = (int) $row['total'];
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS total FROM pedidos
             WHERE estado = 'pendiente_pago' AND fecha < NOW() - INTERVAL ? DAY"
        );
        $dias = self::DIAS_PAGO_VENCIDO;
        $stmt->bind_param("i", $dias);
        $stmt->execute();
        $conteo['vencidos'] = (int) $stmt->get_result()->fetch_assoc()['total'];

        $conteo['todos'] = array_sum(array_intersect_key($conteo, array_flip(self::ESTADOS)));

        return $conteo;
    }

    // ─── TRANSACCIONES ─────────────────────────────────────────────────────────

    public function begin()
    {
        $this->db->begin_transaction();
    }

    public function commit()
    {
        $this->db->commit();
    }

    public function rollback()
    {
        $this->db->rollback();
    }
}