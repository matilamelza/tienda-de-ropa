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