<?php

require_once __DIR__ . '/Conexion.php';

class Producto extends Conexion
{

    public const ORDENES = [
        'recientes'   => ['Más nuevos',           'p.id_producto DESC'],
        'nombre'      => ['Nombre A-Z',           'p.nombre ASC'],
        'precio_asc'  => ['Precio: menor a mayor', 'p.precio_base ASC'],
        'precio_desc' => ['Precio: mayor a menor', 'p.precio_base DESC'],
        'stock_asc'   => ['Stock: menos primero',  'stock_disponible ASC, p.id_producto DESC'],
        'stock_desc'  => ['Stock: más primero',    'stock_disponible DESC, p.id_producto DESC'],
        'vendidos'    => ['Más vendidos (30 días)', 'vendidos_30d DESC, p.id_producto DESC'],
        'vistos'      => ['Más vistos (30 días)',   'vistas_30d DESC, p.id_producto DESC'],
    ];
    // ─── PRODUCTOS ─────────────────────────────────────────────────────────────

       /**
     * Arma el WHERE del listado del admin.
     * Filtros: q, categoria (id), estado ('activos'|'inactivos'),
     *          problema ('agotados'|'faltantes'|'sin_foto'|'sin_costo')
     */
    private function filtroAdmin(array $f): array
    {
        $where  = ['p.eliminado_at IS NULL'];
        $params = [];
        $types  = '';

        $q = trim($f['q'] ?? '');
        if ($q !== '') {
            $like     = '%' . $q . '%';
            $where[]  = '(p.nombre LIKE ? OR m.nombre LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $types   .= 'ss';
        }

        $categoria = (int) ($f['categoria'] ?? 0);
        if ($categoria > 0) {
            $where[]  = 'p.id_categoria = ?';
            $params[] = $categoria;
            $types   .= 'i';
        }

        switch ($f['estado'] ?? '') {
            case 'activos':   $where[] = 'p.activo = 1'; break;
            case 'inactivos': $where[] = 'p.activo = 0'; break;
        }

        switch ($f['problema'] ?? '') {
            case 'agotados':    // ningún talle con stock
                $where[] = 'NOT EXISTS (SELECT 1 FROM producto_variantes pv
                                        WHERE pv.id_producto = p.id_producto AND pv.activo = 1
                                        AND (pv.stock - pv.stock_reservado) > 0)';
                break;
            case 'faltantes':   // al menos un talle agotado
                $where[] = 'EXISTS (SELECT 1 FROM producto_variantes pv
                                    WHERE pv.id_producto = p.id_producto AND pv.activo = 1
                                    AND (pv.stock - pv.stock_reservado) <= 0)';
                break;
            case 'sin_foto':
                $where[] = 'NOT EXISTS (SELECT 1 FROM producto_fotos pf WHERE pf.id_producto = p.id_producto)';
                break;
            case 'sin_costo':
                $where[] = 'p.precio_costo IS NULL';
                break;
        }

        return ['WHERE ' . implode(' AND ', $where), $params, $types];
    }

    /** Listado del admin con filtros y paginación. */
        /** Listado del admin con filtros, orden y paginación. */
    public function listarAdmin(array $filtros, int $pagina = 1, int $porPagina = 25): array
    {
        [$where, $params, $types] = $this->filtroAdmin($filtros);

        $orden   = self::ORDENES[$filtros['orden'] ?? ''][1] ?? self::ORDENES['recientes'][1];
        $params[] = $porPagina;
        $params[] = ($pagina - 1) * $porPagina;
        $types   .= 'ii';

        $sql = "SELECT
                    p.*,
                    c.nombre AS categoria,
                    m.nombre AS marca,
                    (SELECT pf.imagen FROM producto_fotos pf
                     WHERE pf.id_producto = p.id_producto
                     ORDER BY pf.principal DESC, pf.orden ASC, pf.id_foto ASC
                     LIMIT 1) AS foto_principal,
                    (SELECT COALESCE(SUM(GREATEST(pv.stock - pv.stock_reservado, 0)), 0)
                     FROM producto_variantes pv
                     WHERE pv.id_producto = p.id_producto AND pv.activo = 1) AS stock_disponible,
                    (SELECT COUNT(*) FROM producto_variantes pv
                     WHERE pv.id_producto = p.id_producto AND pv.activo = 1) AS cant_variantes,
                    (SELECT COALESCE(SUM(pi.cantidad), 0)
                     FROM pedido_items pi
                     INNER JOIN producto_variantes pv ON pv.id_variante = pi.id_variante
                     INNER JOIN pedidos pe ON pe.id_pedido = pi.id_pedido
                     WHERE pv.id_producto = p.id_producto
                     AND pe.estado <> 'cancelado'
                     AND pe.fecha >= NOW() - INTERVAL 30 DAY) AS vendidos_30d,
                    (SELECT COUNT(*) FROM visitas v
                     WHERE v.tipo = 'producto' AND v.id_ref = p.id_producto
                     AND v.fecha >= NOW() - INTERVAL 30 DAY) AS vistas_30d
                FROM productos p
                INNER JOIN categorias c ON c.id_categoria = p.id_categoria
                LEFT JOIN marcas m ON m.id_marca = p.id_marca
                $where
                ORDER BY $orden
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

        /**
     * Talles de varios productos con su disponibilidad (sumando todos los colores).
     * Devuelve [id_producto => [['nombre' => '38', 'disponible' => 3], ...]] ordenado por talle.
     */
    public function tallesPorProducto(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $marcas = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $this->db->prepare(
            "SELECT pv.id_producto, t.nombre, t.orden,
                    SUM(GREATEST(pv.stock - pv.stock_reservado, 0)) AS disponible
             FROM producto_variantes pv
             INNER JOIN talles t ON t.id_talle = pv.id_talle
             WHERE pv.activo = 1 AND pv.id_producto IN ($marcas)
             GROUP BY pv.id_producto, t.id_talle, t.nombre, t.orden
             ORDER BY t.orden ASC, t.nombre ASC"
        );
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $res = $stmt->get_result();

        $talles = [];
        while ($row = $res->fetch_assoc()) {
            $talles[(int) $row['id_producto']][] = [
                'nombre'     => $row['nombre'],
                'disponible' => (int) $row['disponible'],
            ];
        }

        return $talles;
    }

    /** Invierte activo o destacado de un producto. Devuelve el valor nuevo (0/1) o null si falla. */
    public function toggleCampo(int $id, string $campo): ?int
    {
        if (!in_array($campo, ['activo', 'destacado'], true)) {
            return null;
        }

        $stmt = $this->db->prepare(
            "UPDATE productos SET $campo = 1 - $campo WHERE id_producto = ? AND eliminado_at IS NULL"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $stmt = $this->db->prepare("SELECT $campo FROM productos WHERE id_producto = ? AND eliminado_at IS NULL");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();

        return $fila ? (int) $fila[$campo] : null;
    }

    public function contarAdmin(array $filtros): int
    {
        [$where, $params, $types] = $this->filtroAdmin($filtros);

        $sql = "SELECT COUNT(*) AS total
                FROM productos p
                LEFT JOIN marcas m ON m.id_marca = p.id_marca
                $where";

        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        return (int) $stmt->get_result()->fetch_assoc()['total'];
    }

    public function guardar($data)
    {

    $data['slug'] = $this->slugUnico($data['slug']);


        $sql = "INSERT INTO productos 
                (id_categoria, id_marca, nombre, slug, descripcion, precio_base, precio_costo, activo, destacado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "iisssddii",
            $data['id_categoria'],
            $data['id_marca'],
            $data['nombre'],
            $data['slug'],
            $data['descripcion'],
            $data['precio_base'],
            $data['precio_costo'],
            $data['activo'],
            $data['destacado']
        );

        $stmt->execute();
        return $this->db->insert_id;
    }

    public function actualizar($id, $data)
    {

    
    $data['slug'] = $this->slugUnico($data['slug'], (int) $id);
        $sql = "UPDATE productos SET
                    id_categoria = ?,
                    id_marca     = ?,
                    nombre       = ?,
                    slug         = ?,
                    descripcion  = ?,
                    precio_base  = ?,
                    precio_costo = ?,
                    activo       = ?,
                    destacado    = ?
                WHERE id_producto = ?
                AND eliminado_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "iisssddiii",
            $data['id_categoria'],
            $data['id_marca'],
            $data['nombre'],
            $data['slug'],
            $data['descripcion'],
            $data['precio_base'],
            $data['precio_costo'],
            $data['activo'],
            $data['destacado'],
            $id
        );

        return $stmt->execute();
    }

    /**
     * Borrado lógico: marca la fecha, lo desactiva y libera el slug.
     * Variantes, fotos y pedidos quedan intactos.
     */
    public function eliminar(int $id_producto): bool
    {
        $sql = "UPDATE productos
                SET eliminado_at = NOW(),
                    activo       = 0,
                    slug         = CONCAT(slug, '--eliminado-', id_producto)
                WHERE id_producto = ?
                AND eliminado_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();

        return $stmt->affected_rows === 1;
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
                WHERE p.id_producto = ?
                AND p.eliminado_at IS NULL";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
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
                AND p.eliminado_at IS NULL
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $slug);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // ─── VARIANTES ─────────────────────────────────────────────────────────────

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

    public function buscarVariantePorId($id_variante)
    {
        $sql = "SELECT pv.*, t.nombre AS talle, c.nombre AS color
                FROM producto_variantes pv
                LEFT JOIN talles t ON t.id_talle = pv.id_talle
                LEFT JOIN colores c ON c.id_color = pv.id_color
                WHERE pv.id_variante = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_variante);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
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

    public function actualizarVariante($id, $data)
    {
        $sql = "UPDATE producto_variantes SET
                    id_talle  = ?,
                    id_color  = ?,
                    sku       = ?,
                    precio    = ?,
                    stock     = ?,
                    activo    = ?
                WHERE id_variante = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "iisdiii",
            $data['id_talle'],
            $data['id_color'],
            $data['sku'],
            $data['precio'],
            $data['stock'],
            $data['activo'],
            $id
        );

        return $stmt->execute();
    }

    public function eliminarVariante($id_variante)
    {
        $stmt = $this->db->prepare("DELETE FROM producto_variantes WHERE id_variante = ?");
        $stmt->bind_param("i", $id_variante);
        return $stmt->execute();
    }

    // ─── FOTOS ─────────────────────────────────────────────────────────────────

    /** Agrega una foto al final. Si es la primera del producto, queda como principal. */
    public function guardarFoto($id_producto, $nombreArchivo)
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(orden), 0) + 1 AS siguiente, COUNT(*) AS total
             FROM producto_fotos WHERE id_producto = ?"
        );
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        $orden     = (int) $row['siguiente'];
        $principal = ((int) $row['total'] === 0) ? 1 : 0;

        $stmt = $this->db->prepare(
            "INSERT INTO producto_fotos (id_producto, imagen, principal, orden)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("isii", $id_producto, $nombreArchivo, $principal, $orden);

        return $stmt->execute() ? (int) $this->db->insert_id : 0;
    }

    public function eliminarFoto($id_foto)
    {
        // Primero obtenemos el nombre del archivo para borrarlo del disco
        $stmt = $this->db->prepare("SELECT imagen, id_producto FROM producto_fotos WHERE id_foto = ? LIMIT 1");
        $stmt->bind_param("i", $id_foto);
        $stmt->execute();
        $foto = $stmt->get_result()->fetch_assoc();

        if (!$foto) return false;

        // Borrar archivo físico
        $ruta = __DIR__ . '/../../public/uploads/productos/' . basename($foto['imagen']);
        if (is_file($ruta)) {
            unlink($ruta);
        }

        // Borrar registro
        $stmt = $this->db->prepare("DELETE FROM producto_fotos WHERE id_foto = ?");
        $stmt->bind_param("i", $id_foto);
        $stmt->execute();

        // Si se borró la principal, la siguiente pasa a serlo
        $this->sincronizarPrincipal((int) $foto['id_producto']);

        return $foto['id_producto'];
    }

    public function listarFotos($id_producto)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM producto_fotos
             WHERE id_producto = ?
             ORDER BY orden ASC, id_foto ASC"
        );
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();

        return $stmt->get_result();
    }

    /**
     * Guarda el orden nuevo de las fotos de un producto.
     * $ids: ids de foto en el orden deseado (el primero queda como principal).
     * Solo toca fotos que pertenezcan a ese producto.
     */
    public function guardarOrdenFotos(int $id_producto, array $ids): bool
    {
        $ids = array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 0));

        if (empty($ids)) {
            return false;
        }

        try {
            $this->db->begin_transaction();

            $stmt = $this->db->prepare(
                "UPDATE producto_fotos
                 SET orden = ?, principal = ?
                 WHERE id_foto = ? AND id_producto = ?"
            );

            foreach ($ids as $i => $id_foto) {
                $orden     = $i + 1;
                $principal = ($i === 0) ? 1 : 0;

                $stmt->bind_param("iiii", $orden, $principal, $id_foto, $id_producto);
                $stmt->execute();
            }

            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollback();
            error_log('guardarOrdenFotos: ' . $e->getMessage());
            return false;
        }

        return true;
    }

    /** Deja como principal la primera foto según el orden (y ninguna otra). */
    private function sincronizarPrincipal(int $id_producto): void
    {
        $stmt = $this->db->prepare("UPDATE producto_fotos SET principal = 0 WHERE id_producto = ?");
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();

        $stmt = $this->db->prepare(
            "UPDATE producto_fotos SET principal = 1
             WHERE id_producto = ?
             ORDER BY orden ASC, id_foto ASC
             LIMIT 1"
        );
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
    }

    public function contarFotos(int $id_producto): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM producto_fotos WHERE id_producto = ?");
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();

        return (int) $stmt->get_result()->fetch_assoc()['total'];
    }

    // ─── TIENDA ────────────────────────────────────────────────────────────────

        /** ¿Hay al menos un producto activo con una promoción vigente? (para mostrar el filtro "En oferta") */
    public function hayOfertasVigentes(): bool
    {
        $sql = "SELECT 1
                FROM promocion_productos pp
                INNER JOIN promociones pr ON pr.id_promocion = pp.id_promocion
                INNER JOIN productos p ON p.id_producto = pp.id_producto
                WHERE p.activo = 1 AND p.eliminado_at IS NULL AND " . Promocion::SQL_VIGENTE . "
                LIMIT 1";

        return $this->db->query($sql)->num_rows > 0;
    }

        /**
     * Productos relacionados: primero misma categoría y marca, después misma categoría,
     * después misma marca. Activos, con stock y sin el producto actual.
     */
    public function relacionados(array $producto, int $limite = 4): array
    {
        $id       = (int) $producto['id_producto'];
        $idCat    = (int) $producto['id_categoria'];
        $idMarca  = $producto['id_marca'] !== null ? (int) $producto['id_marca'] : 0;

        $stmt = $this->db->prepare(
            "SELECT
                p.*,
                c.nombre AS categoria,
                m.nombre AS marca,
                (SELECT pf.imagen FROM producto_fotos pf
                 WHERE pf.id_producto = p.id_producto
                 ORDER BY pf.principal DESC, pf.orden ASC, pf.id_foto ASC
                 LIMIT 1) AS foto_principal,
                " . Promocion::columnasDescuento('p') . ",
                (CASE
                    WHEN p.id_categoria = ? AND p.id_marca = ? THEN 1
                    WHEN p.id_categoria = ?                    THEN 2
                    ELSE 3
                 END) AS cercania
             FROM productos p
             INNER JOIN categorias c ON c.id_categoria = p.id_categoria
             LEFT JOIN marcas m ON m.id_marca = p.id_marca
             WHERE p.id_producto <> ?
             AND p.activo = 1
             AND p.eliminado_at IS NULL
             AND (p.id_categoria = ? OR (p.id_marca = ? AND ? > 0))
             AND EXISTS (SELECT 1 FROM producto_variantes pv
                         WHERE pv.id_producto = p.id_producto AND pv.activo = 1
                         AND (pv.stock - pv.stock_reservado) > 0)
             ORDER BY cercania ASC, RAND()
             LIMIT ?"
        );
        $stmt->bind_param("iiiiiiii", $idCat, $idMarca, $idCat, $id, $idCat, $idMarca, $idMarca, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
                        ORDER BY pf.principal DESC, pf.orden ASC, pf.id_foto ASC 
                        LIMIT 1
                    ) AS foto_principal
                FROM productos p
                INNER JOIN categorias c ON c.id_categoria = p.id_categoria
                LEFT JOIN marcas m ON m.id_marca = p.id_marca
                WHERE p.activo = 1
                AND p.eliminado_at IS NULL
                AND c.slug = ?
                ORDER BY p.id_producto DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $slug);
        $stmt->execute();

        return $stmt->get_result();
    }

        /** Productos destacados para el inicio: activos y con stock. */
        /** Productos destacados para el inicio: activos y con stock. */
    public function listarDestacados(int $limite = 8): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                p.*,
                c.nombre AS categoria,
                m.nombre AS marca,
                (SELECT pf.imagen FROM producto_fotos pf
                 WHERE pf.id_producto = p.id_producto
                 ORDER BY pf.principal DESC, pf.orden ASC, pf.id_foto ASC
                 LIMIT 1) AS foto_principal,
                " . Promocion::columnasDescuento('p') . "
             FROM productos p
             INNER JOIN categorias c ON c.id_categoria = p.id_categoria
             LEFT JOIN marcas m ON m.id_marca = p.id_marca
             WHERE p.destacado = 1
             AND p.activo = 1
             AND p.eliminado_at IS NULL
             AND EXISTS (SELECT 1 FROM producto_variantes pv
                         WHERE pv.id_producto = p.id_producto AND pv.activo = 1
                         AND (pv.stock - pv.stock_reservado) > 0)
             ORDER BY p.id_producto DESC
             LIMIT ?"
        );
        $stmt->bind_param("i", $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
                AND p.eliminado_at IS NULL
                ORDER BY stock_disponible ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $limiteStock);
        $stmt->execute();

        return $stmt->get_result();
    }

        /** Números de ventas y visitas de un producto (histórico y últimos 30 días). */
    public function resumenProducto(int $id_producto): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(pi.cantidad), 0) AS vendidos_total,
                COALESCE(SUM(pi.subtotal), 0) AS facturado_total,
                COALESCE(SUM(CASE WHEN pe.fecha >= NOW() - INTERVAL 30 DAY THEN pi.cantidad END), 0) AS vendidos_30d
             FROM pedido_items pi
             INNER JOIN producto_variantes pv ON pv.id_variante = pi.id_variante
             INNER JOIN pedidos pe ON pe.id_pedido = pi.id_pedido
             WHERE pv.id_producto = ? AND pe.estado <> 'cancelado'"
        );
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $ventas = $stmt->get_result()->fetch_assoc();

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS vistas, COUNT(DISTINCT visitante) AS personas
             FROM visitas
             WHERE tipo = 'producto' AND id_ref = ? AND fecha >= NOW() - INTERVAL 30 DAY"
        );
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $visitas = $stmt->get_result()->fetch_assoc();

        return [
            'vendidos_total'  => (int) $ventas['vendidos_total'],
            'facturado_total' => (float) $ventas['facturado_total'],
            'vendidos_30d'    => (int) $ventas['vendidos_30d'],
            'vistas_30d'      => (int) $visitas['vistas'],
            'personas_30d'    => (int) $visitas['personas'],
        ];
    }

    /** Últimos pedidos en los que aparece el producto. */
    public function ultimosPedidosProducto(int $id_producto, int $limite = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT pe.id_pedido, pe.fecha, pe.estado,
                    SUM(pi.cantidad) AS unidades,
                    SUM(pi.subtotal) AS importe,
                    c.nombre, c.apellido
             FROM pedido_items pi
             INNER JOIN producto_variantes pv ON pv.id_variante = pi.id_variante
             INNER JOIN pedidos pe ON pe.id_pedido = pi.id_pedido
             LEFT JOIN clientes c ON c.id_cliente = pe.id_cliente
             WHERE pv.id_producto = ?
             GROUP BY pe.id_pedido, pe.fecha, pe.estado, c.nombre, c.apellido
             ORDER BY pe.fecha DESC
             LIMIT ?"
        );
        $stmt->bind_param("ii", $id_producto, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

        /**
     * Duplica un producto: mismos datos y variantes activas (stock 0, sin SKU),
     * inactivo, sin fotos y sin destacar. Devuelve el id nuevo (0 si falla).
     */
    public function duplicar(int $id_producto): int
    {
        $original = $this->buscarPorId($id_producto);

        if (!$original) {
            return 0;
        }

        $nombre = mb_substr($original['nombre'] . ' (copia)', 0, 150);
        $slug   = $this->slugUnico(generarSlug($nombre));

        try {
            $this->db->begin_transaction();

            $stmt = $this->db->prepare(
                "INSERT INTO productos
                 (id_categoria, id_marca, nombre, slug, descripcion, precio_base, precio_costo, activo, destacado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0)"
            );
            $idMarca = $original['id_marca'] !== null ? (int) $original['id_marca'] : null;
            $costo   = $original['precio_costo'] !== null ? (float) $original['precio_costo'] : null;
            $idCat   = (int) $original['id_categoria'];
            $desc    = $original['descripcion'];
            $precio  = (float) $original['precio_base'];

            $stmt->bind_param("iisssdd", $idCat, $idMarca, $nombre, $slug, $desc, $precio, $costo);
            $stmt->execute();

            $idNuevo = (int) $this->db->insert_id;

            // Variantes activas: mismo talle, color y precio especial; stock 0 y sin SKU
            $stmt = $this->db->prepare(
                "INSERT INTO producto_variantes (id_producto, id_talle, id_color, sku, precio, stock, activo)
                 SELECT ?, id_talle, id_color, '', precio, 0, 1
                 FROM producto_variantes
                 WHERE id_producto = ? AND activo = 1"
            );
            $stmt->bind_param("ii", $idNuevo, $id_producto);
            $stmt->execute();

            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        return $idNuevo;
    }

    /** Problemas del catálogo a resolver (solo productos activos y no eliminados). */
    public function alertasCatalogo(): array
    {
        $row = $this->db->query(
            "SELECT
                (SELECT COUNT(*)
                 FROM producto_variantes pv
                 INNER JOIN productos p ON p.id_producto = pv.id_producto
                 WHERE pv.activo = 1 AND p.activo = 1 AND p.eliminado_at IS NULL
                 AND (pv.stock - pv.stock_reservado) <= 0) AS variantes_sin_stock,

                (SELECT COUNT(*)
                 FROM productos p
                 WHERE p.activo = 1 AND p.eliminado_at IS NULL
                 AND NOT EXISTS (SELECT 1 FROM producto_fotos pf WHERE pf.id_producto = p.id_producto)) AS productos_sin_foto,

                (SELECT COUNT(*)
                 FROM productos p
                 WHERE p.activo = 1 AND p.eliminado_at IS NULL
                 AND p.precio_costo IS NULL) AS productos_sin_costo"
        )->fetch_assoc();

        return [
            'variantes_sin_stock' => (int) $row['variantes_sin_stock'],
            'productos_sin_foto'  => (int) $row['productos_sin_foto'],
            'productos_sin_costo' => (int) $row['productos_sin_costo'],
        ];
    }

    /**
     * Busca y filtra productos para la tienda.
     * Soporta: búsqueda por texto, categoría, marca, precio min/max y orden.
     */
        public function buscarFiltrado(array $filtros = []): array
    {
        $q          = trim($filtros['q']          ?? '');
        $categoria  = trim($filtros['categoria']  ?? '');
        $marca      = (int)($filtros['marca']     ?? 0);
        $precioMin  = ($filtros['precio_min'] ?? '') !== '' ? (float) $filtros['precio_min'] : null;
        $precioMax  = ($filtros['precio_max'] ?? '') !== '' ? (float) $filtros['precio_max'] : null;
        $orden      = $filtros['orden'] ?? 'reciente';

        $where  = ['p.activo = 1', 'p.eliminado_at IS NULL'];
        $params = [];
        $types  = '';

        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[]  = '(p.nombre LIKE ? OR p.descripcion LIKE ? OR m.nombre LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $types   .= 'sss';
        }

        if ($categoria !== '') {
            $where[]  = 'c.slug = ?';
            $params[] = $categoria;
            $types   .= 's';
        }

        if ($marca > 0) {
            $where[]  = 'p.id_marca = ?';
            $params[] = $marca;
            $types   .= 'i';
        }

        if ($precioMin !== null) {
            $where[]  = 'p.precio_base >= ?';
            $params[] = $precioMin;
            $types   .= 'd';
        }

        if ($precioMax !== null && $precioMax > 0) {
            $where[]  = 'p.precio_base <= ?';
            $params[] = $precioMax;
            $types   .= 'd';
        }

        // Solo productos con una promoción vigente
        if (!empty($filtros['oferta'])) {
            $where[] = "EXISTS (SELECT 1 FROM promocion_productos pp
                                INNER JOIN promociones pr ON pr.id_promocion = pp.id_promocion
                                WHERE pp.id_producto = p.id_producto AND " . Promocion::SQL_VIGENTE . ")";
        }

        // El orden va sobre la consulta de afuera (t), así puede usar el descuento ya calculado
        switch ($orden) {
            case 'precio_asc':  $orderBy = 't.precio_base * (1 - COALESCE(t.descuento_pct, 0) / 100) ASC';  break;
            case 'precio_desc': $orderBy = 't.precio_base * (1 - COALESCE(t.descuento_pct, 0) / 100) DESC'; break;
            case 'nombre':      $orderBy = 't.nombre ASC'; break;
            default:            $orderBy = 't.destacado DESC, t.id_producto DESC'; break;
        }

        $sql = "SELECT t.* FROM (
                    SELECT
                        p.*,
                        c.nombre AS categoria,
                        c.slug   AS categoria_slug,
                        m.nombre AS marca,
                        (
                            SELECT pf.imagen
                            FROM producto_fotos pf
                            WHERE pf.id_producto = p.id_producto
                            ORDER BY pf.principal DESC, pf.orden ASC, pf.id_foto ASC
                            LIMIT 1
                        ) AS foto_principal,
                        " . Promocion::columnasDescuento('p') . "
                    FROM productos p
                    INNER JOIN categorias c ON c.id_categoria = p.id_categoria
                    LEFT JOIN marcas m ON m.id_marca = p.id_marca
                    WHERE " . implode(' AND ', $where) . "
                ) t
                ORDER BY " . $orderBy;

        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Devuelve todas las marcas que tienen al menos un producto activo.
     */
    public function listarMarcasActivas(): array
    {
        $sql = "SELECT DISTINCT m.id_marca, m.nombre
                FROM marcas m
                INNER JOIN productos p ON p.id_marca = m.id_marca
                WHERE p.activo = 1
                AND p.eliminado_at IS NULL
                AND m.activo = 1
                ORDER BY m.nombre ASC";

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Devuelve el precio mínimo y máximo de productos activos.
     */
    public function rangoPrecio(): array
    {
        $row = $this->db->query(
            "SELECT MIN(precio_base) AS minimo, MAX(precio_base) AS maximo
             FROM productos
             WHERE activo = 1
             AND eliminado_at IS NULL"
        )->fetch_assoc();

        return [
            'min' => (float)($row['minimo'] ?? 0),
            'max' => (float)($row['maximo'] ?? 0),
        ];
    }

        /**
     * Devuelve un slug que no esté usado por otro producto.
     * Si "new-balance-530" existe, prueba "new-balance-530-2", "-3", etc.
     */
    public function slugUnico(string $base, int $excluirId = 0): string
    {
        $slug = $base;
        $n    = 2;

        while ($this->existeSlug($slug, $excluirId)) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    private function existeSlug(string $slug, int $excluirId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM productos WHERE slug = ? AND id_producto <> ? LIMIT 1");
        $stmt->bind_param("si", $slug, $excluirId);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

        /** Combinaciones talle-color que ya tiene el producto. Claves: "idTalle-idColor". */
    public function combinacionesExistentes(int $id_producto): array
    {
        $stmt = $this->db->prepare("SELECT id_talle, id_color FROM producto_variantes WHERE id_producto = ?");
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $res = $stmt->get_result();

        $claves = [];
        while ($row = $res->fetch_assoc()) {
            $claves[(int) $row['id_talle'] . '-' . (int) $row['id_color']] = true;
        }

        return $claves;
    }

    /**
     * Crea varias variantes de una vez (en una transacción).
     * $combos: [['id_talle' => , 'id_color' => , 'stock' => ], ...]
     * Devuelve [creadas, omitidas] — se omiten las combinaciones que ya existían.
     */
    public function crearVariantesMasivo(int $id_producto, array $combos, ?float $precio, int $activo): array
    {
        $existentes = $this->combinacionesExistentes($id_producto);
        $creadas    = 0;
        $omitidas   = 0;
        $sku        = '';

        try {
            $this->db->begin_transaction();

            $stmt = $this->db->prepare(
                "INSERT INTO producto_variantes (id_producto, id_talle, id_color, sku, precio, stock, activo)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            foreach ($combos as $c) {
                $clave = $c['id_talle'] . '-' . (int) $c['id_color'];   // sin color → "42-0"

                if (isset($existentes[$clave])) {
                    $omitidas++;
                    continue;
                }

                $stmt->bind_param("iiisdii", $id_producto, $c['id_talle'], $c['id_color'], $sku, $precio, $c['stock'], $activo);
                $stmt->execute();

                $existentes[$clave] = true;   // por si vino repetida en el mismo envío
                $creadas++;
            }

            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        return [$creadas, $omitidas];
    }

        /**
     * Actualiza el stock de varias variantes de un producto a la vez.
     * $stocks: [id_variante => stock]
     * El stock nunca queda por debajo de lo reservado por pedidos.
     */
        /**
     * Actualiza varias variantes de un producto a la vez.
     * $filas: [id_variante => ['sku' => , 'precio' => (float|null), 'stock' => , 'activo' => ]]
     * El stock nunca queda por debajo de lo reservado.
     */
    public function actualizarVariantesMasivo(int $id_producto, array $filas): int
    {
        $cambiadas = 0;

        try {
            $this->db->begin_transaction();

            $stmt = $this->db->prepare(
                "UPDATE producto_variantes
                 SET sku = ?, precio = ?, stock = GREATEST(?, stock_reservado), activo = ?
                 WHERE id_variante = ? AND id_producto = ?"
            );

            foreach ($filas as $id_variante => $f) {
                $id_variante = (int) $id_variante;
                $stmt->bind_param("sdiiii", $f['sku'], $f['precio'], $f['stock'], $f['activo'], $id_variante, $id_producto);
                $stmt->execute();
                $cambiadas += $stmt->affected_rows;
            }

            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        return $cambiadas;
    }

    /**
     * Elimina varias variantes. Las que figuran en pedidos no se pueden borrar
     * (se perdería el historial): esas se DESACTIVAN.
     * Devuelve [eliminadas, desactivadas].
     */
    public function eliminarVariantesMasivo(int $id_producto, array $ids): array
    {
        $eliminadas   = 0;
        $desactivadas = 0;

        $borrar = $this->db->prepare(
            "DELETE FROM producto_variantes
             WHERE id_variante = ? AND id_producto = ?
             AND NOT EXISTS (SELECT 1 FROM pedido_items pi WHERE pi.id_variante = ?)"
        );
        $desactivar = $this->db->prepare(
            "UPDATE producto_variantes SET activo = 0 WHERE id_variante = ? AND id_producto = ?"
        );

        try {
            $this->db->begin_transaction();

            foreach ($ids as $id) {
                $id = (int) $id;

                $borrar->bind_param("iii", $id, $id_producto, $id);
                $borrar->execute();

                if ($borrar->affected_rows > 0) {
                    $eliminadas++;
                } else {
                    $desactivar->bind_param("ii", $id, $id_producto);
                    $desactivar->execute();
                    if ($desactivar->affected_rows > 0) {
                        $desactivadas++;
                    }
                }
            }

            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        return [$eliminadas, $desactivadas];
    }

        /** true si el producto ya tiene otra variante con ese talle y color. */
    public function existeCombinacion(int $id_producto, ?int $id_talle, ?int $id_color, int $excluirVariante = 0): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM producto_variantes
             WHERE id_producto = ?
             AND id_variante <> ?
             AND id_talle <=> ?
             AND id_color <=> ?
             LIMIT 1"
        );
        $stmt->bind_param("iiii", $id_producto, $excluirVariante, $id_talle, $id_color);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }

        /**
     * Cambia el color de varias variantes de un producto.
     * Saltea las que quedarían duplicadas (mismo talle y color que otra variante).
     * $id_color null = sin color. Devuelve [cambiadas, omitidas].
     */
    public function cambiarColorMasivo(int $id_producto, array $ids, ?int $id_color): array
    {
        $cambiadas = 0;
        $omitidas  = 0;

        $buscar = $this->db->prepare(
            "SELECT id_talle FROM producto_variantes WHERE id_variante = ? AND id_producto = ? LIMIT 1"
        );
        $actualizar = $this->db->prepare(
            "UPDATE producto_variantes SET id_color = ? WHERE id_variante = ? AND id_producto = ?"
        );

        try {
            $this->db->begin_transaction();

            foreach ($ids as $id) {
                $id = (int) $id;

                $buscar->bind_param("ii", $id, $id_producto);
                $buscar->execute();
                $fila = $buscar->get_result()->fetch_assoc();

                if (!$fila) {
                    continue;
                }

                $id_talle = $fila['id_talle'] !== null ? (int) $fila['id_talle'] : null;

                // Se chequea contra lo ya actualizado en esta misma vuelta
                if ($this->existeCombinacion($id_producto, $id_talle, $id_color, $id)) {
                    $omitidas++;
                    continue;
                }

                $actualizar->bind_param("iii", $id_color, $id, $id_producto);
                $actualizar->execute();
                $cambiadas++;
            }

            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        return [$cambiadas, $omitidas];
    }

        /** Ids de todos los productos que cumplen los filtros del admin (para "seleccionar todos los del filtro"). */
    public function idsAdmin(array $filtros): array
    {
        [$where, $params, $types] = $this->filtroAdmin($filtros);

        $sql = "SELECT p.id_producto
                FROM productos p
                LEFT JOIN marcas m ON m.id_marca = p.id_marca
                $where";

        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        return array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'id_producto'));
    }

    /**
     * Cambia un campo en varios productos. Solo campos permitidos.
     * $valor null = dejar vacío (ej: sin marca).
     */
    public function actualizarCampoMasivo(array $ids, string $campo, ?int $valor): int
    {
        $permitidos = ['activo', 'destacado', 'id_categoria', 'id_marca'];

        if (!in_array($campo, $permitidos, true) || empty($ids)) {
            return 0;
        }

        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $sql    = "UPDATE productos SET $campo = ? WHERE eliminado_at IS NULL AND id_producto IN ($marcas)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i' . str_repeat('i', count($ids)), $valor, ...$ids);
        $stmt->execute();

        return $stmt->affected_rows;
    }

    /**
     * Calcula un precio nuevo: aplica el % y redondea.
     * Al aumentar redondea hacia arriba; al bajar, hacia abajo. $redondeo 0 = sin redondear.
     * (La misma fórmula está en la vista previa, en JS.)
     */
    public static function calcularPrecio(float $precio, float $pct, int $redondeo): float
    {
        $nuevo = $precio * (1 + $pct / 100);

        if ($redondeo > 0) {
            $nuevo = $pct >= 0
                ? ceil($nuevo / $redondeo) * $redondeo
                : floor($nuevo / $redondeo) * $redondeo;
        }

        return max(0, round($nuevo, 2));
    }

    /**
     * Aumenta (o baja, con % negativo) los precios de varios productos.
     * Opcional: también el costo y los precios especiales de sus variantes.
     */
    public function ajustarPrecios(array $ids, float $pct, int $redondeo, bool $conCosto, bool $conVariantes): int
    {
        if (empty($ids)) {
            return 0;
        }

        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $tipos  = str_repeat('i', count($ids));

        $stmt = $this->db->prepare(
            "SELECT id_producto, precio_base, precio_costo FROM productos
             WHERE eliminado_at IS NULL AND id_producto IN ($marcas)"
        );
        $stmt->bind_param($tipos, ...$ids);
        $stmt->execute();
        $productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        try {
            $this->db->begin_transaction();

            $upd = $this->db->prepare("UPDATE productos SET precio_base = ?, precio_costo = ? WHERE id_producto = ?");

            foreach ($productos as $p) {
                $precio = self::calcularPrecio((float) $p['precio_base'], $pct, $redondeo);
                $costo  = $p['precio_costo'] !== null && $conCosto
                    ? self::calcularPrecio((float) $p['precio_costo'], $pct, 0)   // el costo no se redondea
                    : ($p['precio_costo'] !== null ? (float) $p['precio_costo'] : null);
                $id = (int) $p['id_producto'];

                $upd->bind_param("ddi", $precio, $costo, $id);
                $upd->execute();
            }

            if ($conVariantes) {
                $stmt = $this->db->prepare(
                    "SELECT id_variante, precio FROM producto_variantes
                     WHERE precio IS NOT NULL AND id_producto IN ($marcas)"
                );
                $stmt->bind_param($tipos, ...$ids);
                $stmt->execute();
                $variantes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                $updVar = $this->db->prepare("UPDATE producto_variantes SET precio = ? WHERE id_variante = ?");

                foreach ($variantes as $v) {
                    $precio = self::calcularPrecio((float) $v['precio'], $pct, $redondeo);
                    $id     = (int) $v['id_variante'];
                    $updVar->bind_param("di", $precio, $id);
                    $updVar->execute();
                }
            }

            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        return count($productos);
    }
}