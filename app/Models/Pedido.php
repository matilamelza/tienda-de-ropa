<?php

require_once __DIR__ . '/Conexion.php';

class Pedido extends Conexion
{

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
                (id_pedido, id_variante, producto, talle, color, cantidad, precio_unitario, subtotal)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "iisssidd",
            $id_pedido,
            $item['id_variante'],
            $item['producto'],
            $item['talle'],
            $item['color'],
            $item['cantidad'],
            $item['precio_unitario'],
            $item['subtotal']
        );

        return $stmt->execute();
    }

    public function descontarStock($id_variante, $cantidad)
    {
        $sql = "UPDATE producto_variantes
                SET stock = stock - ?
                WHERE id_variante = ?
                AND stock >= ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iii", $cantidad, $id_variante, $cantidad);

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

    public function reservarStock($id_variante, $cantidad)
    {
        $sql = "UPDATE producto_variantes
                SET stock_reservado = stock_reservado + ?
                WHERE id_variante = ?
                AND (stock - stock_reservado) >= ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iii", $cantidad, $id_variante, $cantidad);

        return $stmt->execute();
    }

    public function liberarStock($id_variante, $cantidad)
    {
        $sql = "UPDATE producto_variantes
                SET stock_reservado = stock_reservado - ?
                WHERE id_variante = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $cantidad, $id_variante);

        return $stmt->execute();
    }

    public function resumenDashboard()
    {
        $sql = "SELECT
            COUNT(*) AS total_pedidos,
            SUM(CASE WHEN estado = 'pendiente_contacto' THEN 1 ELSE 0 END) AS pendientes_contacto,
            SUM(CASE WHEN estado = 'pendiente_pago' THEN 1 ELSE 0 END) AS pendientes_pago,
            SUM(CASE WHEN estado = 'pagado' THEN 1 ELSE 0 END) AS pagados,
            SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) AS cancelados,
            COALESCE(SUM(CASE WHEN estado = 'pagado' THEN total ELSE 0 END), 0) AS total_vendido,
            COALESCE(SUM(CASE WHEN estado IN ('pendiente_contacto','pendiente_pago') THEN total ELSE 0 END), 0) AS total_pendiente
        FROM pedidos";

        return $this->db->query($sql)->fetch_assoc();
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