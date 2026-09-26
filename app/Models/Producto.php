<?php

require_once __DIR__ . '/Conexion.php';

class Producto extends Conexion
{
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
    public function listarAdmin(array $filtros, int $pagina = 1, int $porPagina = 25): array
    {
        [$where, $params, $types] = $this->filtroAdmin($filtros);

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
                     WHERE pv.id_producto = p.id_producto AND pv.activo = 1) AS cant_variantes
                FROM productos p
                INNER JOIN categorias c ON c.id_categoria = p.id_categoria
                LEFT JOIN marcas m ON m.id_marca = p.id_marca
                $where
                ORDER BY p.id_producto DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
        $marca      = (int)($filtros['marca']      ?? 0);
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

        switch ($orden) {
            case 'precio_asc':  $orderBy = 'p.precio_base ASC';  break;
            case 'precio_desc': $orderBy = 'p.precio_base DESC'; break;
            case 'nombre':      $orderBy = 'p.nombre ASC';       break;
            default:            $orderBy = 'p.id_producto DESC'; break;
        }

        $sql = "SELECT 
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
                    ) AS foto_principal
                FROM productos p
                INNER JOIN categorias c ON c.id_categoria = p.id_categoria
                LEFT JOIN marcas m ON m.id_marca = p.id_marca
                WHERE " . implode(' AND ', $where) . "
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
}