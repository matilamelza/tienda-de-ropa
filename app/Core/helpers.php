<?php

function generarSlug($texto)
{
    $texto = strtolower(trim($texto));
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    $texto = trim($texto, '-');

    return $texto;
}

/** "42 / Negro", o solo "42" si no tiene color (o solo el color si no tiene talle). */
function variante_texto(?string $talle, ?string $color): string
{
    return implode(' / ', array_filter([trim((string) $talle), trim((string) $color)], 'strlen'));
}