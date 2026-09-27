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