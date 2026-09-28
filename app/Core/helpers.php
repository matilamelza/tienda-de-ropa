<?php

function generarSlug($texto)
{
    $texto = mb_strtolower(trim((string) $texto), 'UTF-8');

    $texto = strtr($texto, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
        'ñ' => 'n', 'ç' => 'c',
    ]);

    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);

    return trim($texto, '-');
}

/** "42 / Negro", o solo "42" si no tiene color (o solo el color si no tiene talle). */
function variante_texto(?string $talle, ?string $color): string
{
    return implode(' / ', array_filter([trim((string) $talle), trim((string) $color)], 'strlen'));
}

/** URL del listado de productos tal como la dejó el admin (con filtros, orden y página). */
function url_listado_productos(): string
{
    $url = $_SESSION['productos_lista_url'] ?? '';

    // Solo se acepta una URL del propio listado
    return strpos($url, BASE_URL . '/admin/productos') === 0 ? $url : BASE_URL . '/admin/productos';
}

/** URL completa (con https y dominio) a partir de una ruta del sitio. Las redes la necesitan así. */
function url_absoluta(string $ruta = ''): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return ($https ? 'https' : 'http') . '://' . $host . BASE_URL . '/' . ltrim($ruta, '/');
}

/** Precio con un % de descuento aplicado (redondeado a centavos). */
function precio_con_descuento(float $precio, float $pct): float
{
    if ($pct <= 0) {
        return $precio;
    }

    return round($precio * (1 - min($pct, 100) / 100), 2);
}

/**
 * Precio real de una variante, con la promoción vigente aplicada.
 * $v tiene que traer: precio (especial o null), precio_base y las columnas de
 * Promocion::columnasDescuento() (descuento_pct, descuento_id_promocion).
 *
 * Devuelve: precio (el que se cobra), lista (sin descuento), pct e id_promocion (null si no hay).
 */
function precio_item(array $v): array
{
    $lista = ($v['precio'] ?? null) !== null && $v['precio'] !== ''
        ? (float) $v['precio']
        : (float) $v['precio_base'];

    $pct = (float) ($v['descuento_pct'] ?? 0);

    return [
        'precio'       => precio_con_descuento($lista, $pct),
        'lista'        => $lista,
        'pct'          => $pct,
        'id_promocion' => $pct > 0 ? (int) ($v['descuento_id_promocion'] ?? 0) ?: null : null,
    ];
}

/** "25" o "12,5" (sin decimales de más) */
function pct_texto(float $pct): string
{
    return rtrim(rtrim(number_format($pct, 2, ',', ''), '0'), ',');
}

/**
 * Precio para la tienda: si hay descuento, el final grande y el de lista tachado.
 * $claseFinal: clases del precio final (tamaño, peso).
 */
function html_precio(float $lista, float $pct = 0, string $claseFinal = 'font-bold'): string
{
    $fmt = fn($n) => '$' . number_format($n, 0, ',', '.');

    if ($pct <= 0) {
        return '<span class="' . $claseFinal . '">' . $fmt($lista) . '</span>';
    }

    return '<span class="' . $claseFinal . '" style="color: var(--color-acento)">' . $fmt(precio_con_descuento($lista, $pct)) . '</span>'
         . ' <span class="text-sm text-gray-400 line-through font-normal">' . $fmt($lista) . '</span>';
}

/** Etiqueta de oferta para poner sobre la foto (esquina superior derecha). */
function html_badge_oferta(float $pct, ?string $etiqueta = null): string
{
    if ($pct <= 0) {
        return '';
    }

    $texto = ($etiqueta ? htmlspecialchars($etiqueta) . ' ' : '') . '-' . pct_texto($pct) . '%';

    return '<span class="absolute top-3 right-3 bg-red-600 text-white text-xs font-bold px-2.5 py-1 rounded-full shadow-sm">'
         . $texto . '</span>';
}