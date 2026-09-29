<?php

/**
 * Lee un CSV exportado desde Productos, lo valida y calcula qué cambia.
 * No guarda nada: devuelve los cambios para mostrarlos y confirmarlos.
 */
class ImportacionProductos
{
    public const MAX_FILAS = 5000;

    /** Nombres de columna reconocidos (ya normalizados) → clave interna */
    private const COLUMNAS = [
        'id_variante'     => 'id_variante',
        'id_producto'     => 'id_producto',
        'sku'             => 'sku',
        'precio'          => 'precio',
        'precio_especial' => 'precio_especial',
        'costo'           => 'costo',
        'stock'           => 'stock',
        'variante_activa' => 'activo',
        'activa'          => 'activo',
    ];

    private array $errores = [];

    /**
     * @return array [
     *   'productos' => [id_producto => ['precio_base' => , 'precio_costo' => ]],   // solo lo que cambia
     *   'variantes' => [id_variante => ['sku' => , 'precio' => , 'stock' => , 'activo' => ]],
     *   'detalle'   => para mostrar, agrupado por producto,
     *   'errores'   => ['Fila 12: ...', ...],
     *   'filas'     => cantidad de filas leídas,
     * ]
     */
    public function analizar(string $ruta): array
    {
        $filas = $this->leerCsv($ruta);

        if ($filas === null) {
            return $this->resultado([], [], [], 0);
        }

        [$encabezado, $filas] = [array_shift($filas), $filas];
        $col = $this->mapearColumnas($encabezado);

        if (!isset($col['id_variante'])) {
            $this->errores[] = 'El archivo no tiene la columna "id_variante". Usá el archivo que se descarga con "Exportar".';
            return $this->resultado([], [], [], 0);
        }

        if (count($filas) > self::MAX_FILAS) {
            $this->errores[] = 'El archivo tiene más de ' . self::MAX_FILAS . ' filas. Dividilo en partes.';
            return $this->resultado([], [], [], 0);
        }

        // Datos actuales de todas las variantes del archivo (una sola consulta)
        $ids    = array_filter(array_map(fn($f) => (int) ($f[$col['id_variante']] ?? 0), $filas));
        $actual = (new Producto())->variantesParaImportar($ids);

        $cambiosVar  = [];
        $precioProd  = [];   // id_producto => [valores leídos], para detectar filas que no coinciden
        $costoProd   = [];
        $detalle     = [];

        foreach ($filas as $i => $f) {
            $nro = $i + 2;   // +1 por el encabezado, +1 porque Excel cuenta desde 1

            // Filas totalmente vacías: se ignoran
            if (implode('', array_map('trim', $f)) === '') {
                continue;
            }

            $idVar = (int) ($f[$col['id_variante']] ?? 0);
            $v     = $actual[$idVar] ?? null;

            if (!$v) {
                $this->errores[] = "Fila $nro: la variante $idVar no existe (¿se eliminó o se editó el id?).";
                continue;
            }

            if (isset($col['id_producto']) && (int) $f[$col['id_producto']] !== (int) $v['id_producto']) {
                $this->errores[] = "Fila $nro: el id_producto no corresponde a esa variante. No edites las columnas de id.";
                continue;
            }

            $idProd = (int) $v['id_producto'];
            $nombre = $v['producto'];
            $varTxt = variante_texto($v['talle'], $v['color']) ?: 'Variante #' . $idVar;
            $cambio = [];

            // SKU
            if (isset($col['sku'])) {
                $sku = mb_substr(trim((string) $f[$col['sku']]), 0, 60);
                if ($sku !== (string) ($v['sku'] ?? '')) {
                    $cambio['sku'] = $sku;
                    $this->agregarDetalle($detalle, $idProd, $nombre, $idVar, $varTxt, 'SKU', $v['sku'] ?: '—', $sku ?: '—');
                }
            }

            // Precio especial de la variante (vacío = usa el del producto)
            if (isset($col['precio_especial'])) {
                $pe = leer_numero($f[$col['precio_especial']]);
                if ($pe === false || ($pe !== null && $pe < 0)) {
                    $this->errores[] = "Fila $nro ($nombre · $varTxt): el precio especial no es válido.";
                } else {
                    $antes = $v['precio_especial'] !== null ? round((float) $v['precio_especial'], 2) : null;
                    $pe    = $pe !== null ? round($pe, 2) : null;
                    if ($pe !== $antes) {
                        $cambio['precio'] = $pe;
                        $this->agregarDetalle($detalle, $idProd, $nombre, $idVar, $varTxt, 'Precio especial',
                            $this->pesos($antes, 'base'), $this->pesos($pe, 'base'));
                    }
                }
            }

            // Stock (entero, no menor a lo reservado)
            if (isset($col['stock'])) {
                $st = leer_numero($f[$col['stock']]);
                if ($st === null) {
                    // vacío: no se toca
                } elseif ($st === false || $st < 0 || floor($st) != $st) {
                    $this->errores[] = "Fila $nro ($nombre · $varTxt): el stock tiene que ser un número entero.";
                } elseif ((int) $st < (int) $v['stock_reservado']) {
                    $this->errores[] = "Fila $nro ($nombre · $varTxt): el stock ($st) es menor a lo reservado por pedidos ({$v['stock_reservado']}).";
                } elseif ((int) $st !== (int) $v['stock']) {
                    $cambio['stock'] = (int) $st;
                    $this->agregarDetalle($detalle, $idProd, $nombre, $idVar, $varTxt, 'Stock', (int) $v['stock'], (int) $st);
                }
            }

            // Activa
            if (isset($col['activo'])) {
                $ac = $this->leerSiNo($f[$col['activo']]);
                if ($ac === false) {
                    $this->errores[] = "Fila $nro ($nombre · $varTxt): en \"Variante activa\" poné SI o NO.";
                } elseif ($ac !== null && $ac !== (int) $v['activo']) {
                    $cambio['activo'] = $ac;
                    $this->agregarDetalle($detalle, $idProd, $nombre, $idVar, $varTxt, 'Activa',
                        (int) $v['activo'] ? 'SI' : 'NO', $ac ? 'SI' : 'NO');
                }
            }

            if ($cambio) {
                $cambiosVar[$idVar] = $cambio;
            }

            // Precio y costo del producto: se juntan y se revisan al final
            if (isset($col['precio'])) {
                $precioProd[$idProd]['valores'][] = leer_numero($f[$col['precio']]);
                $precioProd[$idProd]['v']         = $v;
                $precioProd[$idProd]['filas'][]   = $nro;
            }
            if (isset($col['costo'])) {
                $costoProd[$idProd]['valores'][] = leer_numero($f[$col['costo']]);
                $costoProd[$idProd]['v']         = $v;
                $costoProd[$idProd]['filas'][]   = $nro;
            }
        }

        // ── Precio y costo por producto ──────────────────────────────────────
        $cambiosProd = [];

        foreach ($precioProd as $idProd => $d) {
            $valor = $this->valorUnico($d['valores'], $d['filas'], $d['v']['producto'], 'precio', true);
            if ($valor === null) continue;

            if ($valor <= 0) {
                $this->errores[] = "{$d['v']['producto']}: el precio tiene que ser mayor a 0.";
                continue;
            }
            if (round($valor, 2) !== round((float) $d['v']['precio_base'], 2)) {
                $cambiosProd[$idProd]['precio_base'] = round($valor, 2);
                $this->agregarDetalle($detalle, $idProd, $d['v']['producto'], null, null, 'Precio',
                    $this->pesos($d['v']['precio_base']), $this->pesos($valor));
            }
        }

        foreach ($costoProd as $idProd => $d) {
            $valor = $this->valorUnico($d['valores'], $d['filas'], $d['v']['producto'], 'costo', false);
            if ($valor === false) continue;

            $antes = $d['v']['precio_costo'] !== null ? round((float) $d['v']['precio_costo'], 2) : null;
            $nuevo = $valor !== null ? round($valor, 2) : null;

            if ($nuevo !== null && $nuevo < 0) {
                $this->errores[] = "{$d['v']['producto']}: el costo no puede ser negativo.";
                continue;
            }
            if ($nuevo !== $antes) {
                $cambiosProd[$idProd]['precio_costo'] = $nuevo;
                $this->agregarDetalle($detalle, $idProd, $d['v']['producto'], null, null, 'Costo',
                    $this->pesos($antes, 'sin costo'), $this->pesos($nuevo, 'sin costo'));
            }
        }

        return $this->resultado($cambiosProd, $cambiosVar, $detalle, count($filas));
    }

    // ─── LECTURA ───────────────────────────────────────────────────────────────

    /** Lee el CSV: arregla la codificación, detecta el separador y devuelve las filas. */
    private function leerCsv(string $ruta): ?array
    {
        $contenido = file_get_contents($ruta);

        if ($contenido === false || trim($contenido) === '') {
            $this->errores[] = 'El archivo está vacío.';
            return null;
        }

        // Sacar el BOM y pasar a UTF-8 si Excel lo guardó en otra codificación
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
        if (!mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
        }

        // Separador: el que más aparezca en la primera línea
        $primera = strtok($contenido, "\n");
        $sep = ';';
        $max = 0;
        foreach ([';', ',', "\t"] as $s) {
            if (substr_count($primera, $s) > $max) {
                $max = substr_count($primera, $s);
                $sep = $s;
            }
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contenido);
        rewind($stream);

        $filas = [];
        while (($f = fgetcsv($stream, 0, $sep)) !== false) {
            $filas[] = $f;
        }
        fclose($stream);

        if (count($filas) < 2) {
            $this->errores[] = 'El archivo no tiene filas de datos.';
            return null;
        }

        return $filas;
    }

    /** Encabezados → posición de cada columna conocida. */
    private function mapearColumnas(array $encabezado): array
    {
        $col = [];
        foreach ($encabezado as $pos => $titulo) {
            $clave = $this->normalizar((string) $titulo);
            if (isset(self::COLUMNAS[$clave])) {
                $col[self::COLUMNAS[$clave]] = $pos;
            }
        }
        return $col;
    }

    private function normalizar(string $t): string
    {
        $t = mb_strtolower(trim($t), 'UTF-8');
        $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
        return trim(preg_replace('/[^a-z0-9]+/', '_', $t), '_');
    }

    // ─── HELPERS ───────────────────────────────────────────────────────────────

    /** SI/NO → 1/0. null si está vacío, false si no se entiende. */
    private function leerSiNo($valor)
    {
        $v = mb_strtoupper(trim((string) $valor), 'UTF-8');
        if ($v === '') return null;
        if (in_array($v, ['SI', 'SÍ', 'S', '1', 'TRUE', 'VERDADERO', 'ACTIVA', 'ACTIVO'], true)) return 1;
        if (in_array($v, ['NO', 'N', '0', 'FALSE', 'FALSO', 'INACTIVA', 'INACTIVO'], true)) return 0;
        return false;
    }

    /**
     * Todas las filas de un producto tienen que traer el mismo valor.
     * Devuelve el valor, null si vino vacío (y se permite), o false si hay un error.
     */
    private function valorUnico(array $valores, array $filas, string $producto, string $campo, bool $obligatorio)
    {
        if (in_array(false, $valores, true)) {
            $this->errores[] = "$producto: el $campo no es un número válido (filas " . implode(', ', $filas) . ").";
            return false;
        }

        $unicos = array_unique(array_map(fn($x) => $x === null ? 'vacío' : (string) round($x, 2), $valores));

        if (count($unicos) > 1) {
            $this->errores[] = "$producto: el $campo es el mismo para todas sus variantes, pero en el archivo vienen valores distintos ("
                             . implode(' / ', $unicos) . "). No se cambió.";
            return false;
        }

        $valor = $valores[0];

        if ($valor === null && $obligatorio) {
            return null;   // vacío: no se toca
        }

        return $valor;
    }

    private function agregarDetalle(array &$detalle, int $idProd, string $nombre, ?int $idVar, ?string $varTxt, string $campo, $antes, $despues): void
    {
        $detalle[$idProd]['nombre'] = $nombre;

        if ($idVar === null) {
            $detalle[$idProd]['producto'][] = [$campo, $antes, $despues];
        } else {
            $detalle[$idProd]['variantes'][$idVar]['nombre']    = $varTxt;
            $detalle[$idProd]['variantes'][$idVar]['cambios'][] = [$campo, $antes, $despues];
        }
    }

    private function pesos($n, string $siVacio = '—'): string
    {
        return $n === null ? $siVacio : '$' . number_format((float) $n, 2, ',', '.');
    }

    private function resultado(array $productos, array $variantes, array $detalle, int $filas): array
    {
        return [
            'productos' => $productos,
            'variantes' => $variantes,
            'detalle'   => $detalle,
            'errores'   => $this->errores,
            'filas'     => $filas,
        ];
    }
}