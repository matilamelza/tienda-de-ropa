<?php

function generarSlug($texto)
{
    $texto = strtolower(trim($texto));
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    $texto = trim($texto, '-');

    return $texto;
}