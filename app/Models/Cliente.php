<?php

require_once __DIR__ . '/Conexion.php';

class Cliente extends Conexion
{
    public function listarClientes()
    {
        $sql = "SELECT 
                    c.*,
                    uc.id_usuario_cliente,
                    uc.email AS email_cuenta,
                    COUNT(p.id_pedido) AS cantidad_pedidos,
                    COALESCE(SUM(p.total), 0) AS total_pedidos
                FROM clientes c
                LEFT JOIN pedidos p ON p.id_cliente = c.id_cliente
                LEFT JOIN usuarios_clientes uc ON uc.email = c.email
                GROUP BY c.id_cliente
                ORDER BY c.id_cliente DESC";

        return $this->db->query($sql);
    }

    public function buscarCliente($id_cliente)
    {
        $sql = "SELECT 
                    c.*,
                    uc.id_usuario_cliente,
                    uc.email AS email_cuenta
                FROM clientes c
                LEFT JOIN usuarios_clientes uc ON uc.email = c.email
                WHERE c.id_cliente = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function pedidosCliente($id_cliente)
    {
        $sql = "SELECT *
                FROM pedidos
                WHERE id_cliente = ?
                ORDER BY id_pedido DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();

        return $stmt->get_result();
    }

       public function listarPaginado(int $pagina = 1, int $porPagina = 20, string $busqueda = ''): array
    {
        $offset = ($pagina - 1) * $porPagina;
 
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $sql = "SELECT 
                        c.*,
                        uc.id_usuario_cliente,
                        uc.email AS email_cuenta,
                        COUNT(p.id_pedido) AS cantidad_pedidos,
                        COALESCE(SUM(p.total), 0) AS total_pedidos
                    FROM clientes c
                    LEFT JOIN pedidos p ON p.id_cliente = c.id_cliente
                    LEFT JOIN usuarios_clientes uc ON uc.email = c.email
                    WHERE c.nombre LIKE ? OR c.apellido LIKE ?
                       OR c.email LIKE ? OR c.telefono LIKE ?
                    GROUP BY c.id_cliente
                    ORDER BY c.id_cliente DESC
                    LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ssssii', $like, $like, $like, $like, $porPagina, $offset);
        } else {
            $sql = "SELECT 
                        c.*,
                        uc.id_usuario_cliente,
                        uc.email AS email_cuenta,
                        COUNT(p.id_pedido) AS cantidad_pedidos,
                        COALESCE(SUM(p.total), 0) AS total_pedidos
                    FROM clientes c
                    LEFT JOIN pedidos p ON p.id_cliente = c.id_cliente
                    LEFT JOIN usuarios_clientes uc ON uc.email = c.email
                    GROUP BY c.id_cliente
                    ORDER BY c.id_cliente DESC
                    LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ii', $porPagina, $offset);
        }
 
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
 
    /**
     * Cuenta el total de clientes (con búsqueda opcional).
     */
    public function contarClientes(string $busqueda = ''): int
    {
        if ($busqueda !== '') {
            $like = '%' . $busqueda . '%';
            $sql = "SELECT COUNT(*) AS total FROM clientes
                    WHERE nombre LIKE ? OR apellido LIKE ?
                       OR email LIKE ? OR telefono LIKE ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ssss', $like, $like, $like, $like);
        } else {
            $sql  = "SELECT COUNT(*) AS total FROM clientes";
            $stmt = $this->db->prepare($sql);
        }
 
        $stmt->execute();
        return (int) $stmt->get_result()->fetch_assoc()['total'];
    }
}