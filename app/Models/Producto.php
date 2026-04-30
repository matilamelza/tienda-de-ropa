<?php

require_once __DIR__ . '/Conexion.php';

class Producto extends Conexion
{
    public function listar()
{
    $sql = "SELECT 
                p.*,
                c.nombre AS categoria,
                m.nombre AS marca,
                (
                    SELECT pf.imagen 
                    FROM producto_fotos pf 
                    WHERE pf.id_producto = p.id_producto 
                    ORDER BY pf.principal DESC, pf.id_foto ASC 
                    LIMIT 1
                ) AS foto_principal
            FROM productos p
            INNER JOIN categorias c ON c.id_categoria = p.id_categoria
            LEFT JOIN marcas m ON m.id_marca = p.id_marca
            WHERE p.activo = 1
            ORDER BY p.id_producto DESC";

    return $this->db->query($sql);
}

    public function guardar($data)
    {
        $sql = "INSERT INTO productos 
                (id_categoria, id_marca, nombre, slug, descripcion, precio_base, activo, destacado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "iisssdii",
            $data['id_categoria'],
            $data['id_marca'],
            $data['nombre'],
            $data['slug'],
            $data['descripcion'],
            $data['precio_base'],
            $data['activo'],
            $data['destacado']
        );

        return $stmt->execute();
    }

    public function buscarPorId($id_producto)
    {
        $sql = "SELECT 
                    p.*,
                    c.nombre AS categoria,
                    m.nombre AS marca
                FROM productos p
                INNER JOIN categorias c ON c.id_categoria = p.id_categoria
                LEFT JOIN marcas m ON m.id_marca = p.id_marca
                WHERE p.id_producto = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function listarVariantes($id_producto)
    {
        $sql = "SELECT 
                    pv.*,
                    t.nombre AS talle,
                    c.nombre AS color,
                    c.codigo_hex,
                    (pv.stock - pv.stock_reservado) AS stock_disponible
                FROM producto_variantes pv
                LEFT JOIN talles t ON t.id_talle = pv.id_talle
                LEFT JOIN colores c ON c.id_color = pv.id_color
                WHERE pv.id_producto = ?
                ORDER BY t.orden ASC, c.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function guardarVariante($data)
    {
        $sql = "INSERT INTO producto_variantes
                (id_producto, id_talle, id_color, sku, precio, stock, activo)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param(
            "iiisdii",
            $data['id_producto'],
            $data['id_talle'],
            $data['id_color'],
            $data['sku'],
            $data['precio'],
            $data['stock'],
            $data['activo']
        );

        return $stmt->execute();
    }

    public function guardarFoto($id_producto, $nombreArchivo, $principal = 0)
    {
        $sql = "INSERT INTO producto_fotos (id_producto, imagen, principal)
                VALUES (?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isi", $id_producto, $nombreArchivo, $principal);

        return $stmt->execute();
    }

    public function listarFotos($id_producto)
    {
        $stmt = $this->db->prepare("SELECT * FROM producto_fotos WHERE id_producto = ? ORDER BY principal DESC, id_foto ASC");
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function listarPorCategoriaSlug($slug)
    {
        $sql = "SELECT 
                    p.*,
                    c.nombre AS categoria,
                    m.nombre AS marca,
                    (
                        SELECT pf.imagen 
                        FROM producto_fotos pf 
                        WHERE pf.id_producto = p.id_producto 
                        ORDER BY pf.principal DESC, pf.id_foto ASC 
                        LIMIT 1
                    ) AS foto_principal
                FROM productos p
                INNER JOIN categorias c ON c.id_categoria = p.id_categoria
                LEFT JOIN marcas m ON m.id_marca = p.id_marca
                WHERE p.activo = 1
                AND c.slug = ?
                ORDER BY p.id_producto DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $slug);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function productosStockBajo($limiteStock = 3)
    {
        $sql = "SELECT 
                    p.nombre AS producto,
                    pv.id_variante,
                    pv.stock,
                    pv.stock_reservado,
                    (pv.stock - pv.stock_reservado) AS stock_disponible,
                    t.nombre AS talle,
                    c.nombre AS color
                FROM producto_variantes pv
                INNER JOIN productos p ON p.id_producto = pv.id_producto
                LEFT JOIN talles t ON t.id_talle = pv.id_talle
                LEFT JOIN colores c ON c.id_color = pv.id_color
                WHERE (pv.stock - pv.stock_reservado) <= ?
                AND pv.activo = 1
                ORDER BY stock_disponible ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $limiteStock);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function buscarPorSlug($slug)
{
    $sql = "SELECT 
                p.*,
                c.nombre AS categoria,
                m.nombre AS marca
            FROM productos p
            INNER JOIN categorias c ON c.id_categoria = p.id_categoria
            LEFT JOIN marcas m ON m.id_marca = p.id_marca
            WHERE p.slug = ?
            AND p.activo = 1
            LIMIT 1";

    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("s", $slug);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}
}