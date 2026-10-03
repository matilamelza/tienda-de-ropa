<?php

require_once __DIR__ . '/Conexion.php';

class GuiaTalles extends Conexion
{
    public const TIPOS = [
        'talle'  => 'Talle',
        'medida' => 'Medida (sirve para recomendar)',
        'info'   => 'Información',
    ];

    public const MAX_COLUMNAS = 8;
    public const MAX_FILAS    = 60;

    /**
     * Plantillas para arrancar rápido. Son valores de referencia: cada tienda los ajusta.
     * columnas: [nombre, tipo]; filas: valores en el mismo orden.
     */
    public const PLANTILLAS = [
        'calzado_adulto' => [
            'nombre'   => 'Calzado adulto',
            'columnas' => [['Talle', 'talle'], ['Pie (cm)', 'medida'], ['US', 'info'], ['EU', 'info']],
            'filas'    => [
                ['35', '22,5', '4,5', '36'], ['36', '23', '5', '37'], ['37', '23,7', '6', '38'],
                ['38', '24,5', '7', '39'], ['39', '25', '7,5', '40'], ['40', '25,7', '8', '41'],
                ['41', '26,5', '9', '42'], ['42', '27', '9,5', '43'], ['43', '27,7', '10', '44'],
                ['44', '28,5', '11', '45'], ['45', '29', '11,5', '46'],
            ],
            'consejos' => "Apoyá el pie sobre una hoja, marcá el talón y la punta del dedo más largo, y medí la distancia.\nMedite a la tarde, que es cuando el pie está más grande.\nSi estás entre dos talles, elegí el más grande.",
        ],
        'calzado_ninos' => [
            'nombre'   => 'Calzado niños',
            'columnas' => [['Talle', 'talle'], ['Pie (cm)', 'medida']],
            'filas'    => [
                ['20', '12,5'], ['21', '13,2'], ['22', '13,8'], ['23', '14,5'], ['24', '15,2'],
                ['25', '15,8'], ['26', '16,5'], ['27', '17,2'], ['28', '17,8'], ['29', '18,5'],
                ['30', '19,2'], ['31', '19,8'], ['32', '20,5'], ['33', '21,2'], ['34', '21,8'],
            ],
            'consejos' => "Medí el pie parado, del talón a la punta del dedo más largo.\nSumale 1 cm de margen para que tenga lugar para crecer.",
        ],
        'remeras' => [
            'nombre'   => 'Remeras y buzos',
            'columnas' => [['Talle', 'talle'], ['Pecho (cm)', 'medida'], ['Largo (cm)', 'info']],
            'filas'    => [
                ['S', '88–94', '68'], ['M', '95–101', '70'], ['L', '102–108', '72'],
                ['XL', '109–115', '74'], ['XXL', '116–122', '76'],
            ],
            'consejos' => "Pecho: pasá el centímetro por la parte más ancha, debajo de las axilas.\nSi querés que te quede holgada, elegí un talle más.",
        ],
        'pantalones' => [
            'nombre'   => 'Pantalones',
            'columnas' => [['Talle', 'talle'], ['Cintura (cm)', 'medida'], ['Cadera (cm)', 'medida']],
            'filas'    => [
                ['36', '70–74', '92–96'], ['38', '74–78', '96–100'], ['40', '79–83', '101–105'],
                ['42', '84–88', '106–110'], ['44', '89–93', '111–115'], ['46', '94–98', '116–120'],
                ['48', '99–103', '121–125'],
            ],
            'consejos' => "Cintura: medí a la altura del ombligo, sin apretar.\nCadera: por la parte más ancha.\nSi tus medidas dan dos talles distintos, guiate por la cadera.",
        ],
        'jeans_cintura' => [
            'nombre'   => 'Jeans (talle de cintura)',
            'columnas' => [['Talle', 'talle'], ['Cintura (cm)', 'medida']],
            'filas'    => [
                ['26', '66'], ['27', '68,5'], ['28', '71'], ['29', '73,5'], ['30', '76'],
                ['31', '78,5'], ['32', '81'], ['33', '84'], ['34', '86'],
            ],
            'consejos' => "Medí la cintura a la altura donde usás el jean, sin apretar.",
        ],
        'ninos' => [
            'nombre'   => 'Ropa de niños',
            'columnas' => [['Talle', 'talle'], ['Edad', 'info'], ['Altura (cm)', 'medida']],
            'filas'    => [
                ['2', '2 años', '86–92'], ['4', '4 años', '98–104'], ['6', '6 años', '110–116'],
                ['8', '8 años', '122–128'], ['10', '10 años', '134–140'], ['12', '12 años', '146–152'],
                ['14', '14 años', '158–164'], ['16', '16 años', '170–176'],
            ],
            'consejos' => "Guiate por la altura más que por la edad.\nSi está por pegar el estirón, elegí un talle más.",
        ],
    ];

    /** Todas, con cuántos productos (no eliminados) usan cada una. */
    public function listar(): array
    {
        $res = $this->db->query(
            "SELECT g.*,
                    (SELECT COUNT(*) FROM productos p WHERE p.id_guia = g.id_guia AND p.eliminado_at IS NULL) AS productos
             FROM guias_talles g
             ORDER BY g.nombre ASC"
        );

        return array_map([$this, 'decodificar'], $res->fetch_all(MYSQLI_ASSOC));
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM guias_talles WHERE id_guia = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $g = $stmt->get_result()->fetch_assoc();

        return $g ? $this->decodificar($g) : null;
    }

    /** Arma una guía nueva (sin guardar) a partir de una plantilla. */
    public static function desdePlantilla(string $clave): ?array
    {
        $p = self::PLANTILLAS[$clave] ?? null;
        if (!$p) {
            return null;
        }
        return [
            'id_guia'  => 0,
            'nombre'   => $p['nombre'],
            'columnas' => array_map(fn($c) => ['nombre' => $c[0], 'tipo' => $c[1]], $p['columnas']),
            'filas'    => $p['filas'],
            'consejos' => $p['consejos'],
            'imagen'   => null,
        ];
    }

    /** Crea (id = 0) o actualiza. Devuelve el id. */
    public function guardar(int $id, string $nombre, array $columnas, array $filas, ?string $consejos, ?string $imagen): int
    {
        $jsonCols  = json_encode($columnas, JSON_UNESCAPED_UNICODE);
        $jsonFilas = json_encode($filas, JSON_UNESCAPED_UNICODE);

        if ($id > 0) {
            $stmt = $this->db->prepare(
                "UPDATE guias_talles SET nombre = ?, columnas = ?, filas = ?, consejos = ?, imagen = ? WHERE id_guia = ?"
            );
            $stmt->bind_param("sssssi", $nombre, $jsonCols, $jsonFilas, $consejos, $imagen, $id);
            $stmt->execute();
            return $id;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO guias_talles (nombre, columnas, filas, consejos, imagen) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssss", $nombre, $jsonCols, $jsonFilas, $consejos, $imagen);
        $stmt->execute();

        return (int) $this->db->insert_id;
    }

    /** Copia una guía (sin la imagen, para no compartir el archivo). */
    public function duplicar(int $id): ?int
    {
        $g = $this->buscar($id);
        if (!$g) {
            return null;
        }
        return $this->guardar(0, mb_substr($g['nombre'] . ' (copia)', 0, 80), $g['columnas'], $g['filas'], $g['consejos'], null);
    }

    /** Borra la guía. Los productos que la usaban quedan sin guía (lo hace la base). */
    public function eliminar(int $id): ?string
    {
        $g = $this->buscar($id);
        if (!$g) {
            return null;
        }
        $stmt = $this->db->prepare("DELETE FROM guias_talles WHERE id_guia = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $g['imagen'];   // para borrar el archivo
    }

    /**
     * Limpia lo que viene del editor: columnas válidas (exactamente una de tipo 'talle'),
     * filas con la misma cantidad de celdas, sin filas vacías.
     */
    public static function limpiar(array $columnas, array $filas): array
    {
        $cols = [];
        foreach (array_slice($columnas, 0, self::MAX_COLUMNAS) as $c) {
            $nombre = mb_substr(trim((string) ($c['nombre'] ?? '')), 0, 30);
            $tipo   = isset(self::TIPOS[$c['tipo'] ?? '']) ? $c['tipo'] : 'info';
            $cols[] = ['nombre' => $nombre !== '' ? $nombre : 'Columna', 'tipo' => $tipo];
        }
        if (!$cols) {
            $cols[] = ['nombre' => 'Talle', 'tipo' => 'talle'];
        }

        // Exactamente una columna de talle: la primera marcada, o la primera columna
        $idxTalle = null;
        foreach ($cols as $i => $c) {
            if ($c['tipo'] === 'talle') {
                if ($idxTalle === null) { $idxTalle = $i; } else { $cols[$i]['tipo'] = 'info'; }
            }
        }
        if ($idxTalle === null) {
            $cols[0]['tipo'] = 'talle';
        }

        $n     = count($cols);
        $limpio = [];
        foreach (array_slice($filas, 0, self::MAX_FILAS) as $fila) {
            $celdas = [];
            for ($i = 0; $i < $n; $i++) {
                $celdas[] = mb_substr(trim((string) ($fila[$i] ?? '')), 0, 30);
            }
            if (implode('', $celdas) !== '') {
                $limpio[] = $celdas;
            }
        }

        return [$cols, $limpio];
    }

    private function decodificar(array $g): array
    {
        $g['columnas'] = json_decode($g['columnas'] ?: '[]', true) ?: [];
        $g['filas']    = json_decode($g['filas'] ?: '[]', true) ?: [];
        return $g;
    }

        // ─── PRODUCTOS ─────────────────────────────────────────────────────────────

    /** Para los selects: id y nombre. */
    public function opciones(): array
    {
        return $this->db->query("SELECT id_guia, nombre FROM guias_talles ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);
    }

    /** Asigna la guía y la nota de calce a un producto. */
    public function asignar(int $idProducto, ?int $idGuia, ?string $notaCalce): void
    {
        $notaCalce = $notaCalce !== null && trim($notaCalce) !== '' ? mb_substr(trim($notaCalce), 0, 150) : null;

        $stmt = $this->db->prepare("UPDATE productos SET id_guia = ?, nota_calce = ? WHERE id_producto = ?");
        $stmt->bind_param("isi", $idGuia, $notaCalce, $idProducto);
        $stmt->execute();
    }

    /** Asigna una guía a varios productos (o la quita con null). Devuelve cuántos cambió. */
    public function asignarVarios(array $ids, ?int $idGuia): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return 0;
        }
        $marcas = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $this->db->prepare("UPDATE productos SET id_guia = ? WHERE id_producto IN ($marcas)");
        $stmt->bind_param('i' . str_repeat('i', count($ids)), $idGuia, ...$ids);
        $stmt->execute();

        return $stmt->affected_rows;
    }

    /** Producto nuevo: hereda la guía del último producto de su misma categoría (si no tiene una). */
    public function heredarDeCategoria(int $idProducto): void
    {
        $stmt = $this->db->prepare(
            "UPDATE productos p
             JOIN (SELECT p2.id_guia
                   FROM productos p2
                   JOIN productos actual ON actual.id_producto = ?
                   WHERE p2.id_categoria = actual.id_categoria
                   AND p2.id_producto <> actual.id_producto
                   AND p2.id_guia IS NOT NULL AND p2.eliminado_at IS NULL
                   ORDER BY p2.id_producto DESC LIMIT 1) ultima
             SET p.id_guia = ultima.id_guia
             WHERE p.id_producto = ? AND p.id_guia IS NULL"
        );
        $stmt->bind_param("ii", $idProducto, $idProducto);
        $stmt->execute();
    }

    /** La guía de un producto, lista para la tienda (o null). */
    public function deProducto(array $producto): ?array
    {
        return !empty($producto['id_guia']) ? $this->buscar((int) $producto['id_guia']) : null;
    }

        /** Productos activos sin guía. 0 si todavía no hay guías armadas (no tiene sentido avisar). */
    public function productosSinGuia(): int
    {
        $hayGuias = (int) $this->db->query("SELECT COUNT(*) AS n FROM guias_talles")->fetch_assoc()['n'];
        if ($hayGuias === 0) {
            return 0;
        }
        return (int) $this->db->query(
            "SELECT COUNT(*) AS n FROM productos WHERE activo = 1 AND eliminado_at IS NULL AND id_guia IS NULL"
        )->fetch_assoc()['n'];
    }
}