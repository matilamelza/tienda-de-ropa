<?php

/**
 * Procesa una imagen subida: corrige rotación (fotos de celular), la achica
 * si supera $maxLado, y la guarda comprimida como WebP (o JPEG si no hay WebP).
 *
 * Devuelve el nombre del archivo final (sin carpeta) o null si falla.
 * Si GD no está disponible, copia el archivo tal cual (con su extensión real).
 */
function procesar_imagen(string $origen, string $carpeta, string $prefijo = 'img_', int $maxLado = 1600, int $calidad = 82): ?string
{
    $info = @getimagesize($origen);
    if (!$info) {
        return null;
    }

    $mime  = $info['mime'];
    $base  = $prefijo . bin2hex(random_bytes(10));
    $exts  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    if (!isset($exts[$mime])) {
        return null;
    }

    // Sin GD: se guarda sin procesar
    if (!function_exists('imagecreatetruecolor')) {
        $nombre = $base . '.' . $exts[$mime];
        return copy($origen, $carpeta . '/' . $nombre) ? $nombre : null;
    }

    switch ($mime) {
        case 'image/jpeg': $img = @imagecreatefromjpeg($origen); break;
        case 'image/png':  $img = @imagecreatefrompng($origen);  break;
        case 'image/webp': $img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($origen) : false; break;
        default:           $img = false;
    }

    if (!$img) {
        return null;
    }

    // Rotación según EXIF (las fotos de celular vienen "acostadas")
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($origen);
        $orientacion = $exif['Orientation'] ?? 1;
        $angulos = [3 => 180, 6 => -90, 8 => 90];
        if (isset($angulos[$orientacion])) {
            $rotada = imagerotate($img, $angulos[$orientacion], 0);
            if ($rotada) {
                imagedestroy($img);
                $img = $rotada;
            }
        }
    }

    // Achicar si hace falta (manteniendo proporción)
    $ancho = imagesx($img);
    $alto  = imagesy($img);
    $mayor = max($ancho, $alto);

    if ($mayor > $maxLado) {
        $escala  = $maxLado / $mayor;
        $nAncho  = (int) round($ancho * $escala);
        $nAlto   = (int) round($alto * $escala);
        $chica   = imagecreatetruecolor($nAncho, $nAlto);

        imagealphablending($chica, false);
        imagesavealpha($chica, true);
        imagecopyresampled($chica, $img, 0, 0, 0, 0, $nAncho, $nAlto, $ancho, $alto);

        imagedestroy($img);
        $img = $chica;
    }

    // Guardar: WebP si está disponible (pesa ~30% menos), si no JPEG
    if (function_exists('imagewebp')) {
        imagesavealpha($img, true);
        $nombre = $base . '.webp';
        $ok     = imagewebp($img, $carpeta . '/' . $nombre, $calidad);
    } else {
        // JPEG no tiene transparencia: fondo blanco
        $fondo = imagecreatetruecolor(imagesx($img), imagesy($img));
        imagefill($fondo, 0, 0, imagecolorallocate($fondo, 255, 255, 255));
        imagecopy($fondo, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
        imagedestroy($img);
        $img = $fondo;

        $nombre = $base . '.jpg';
        $ok     = imagejpeg($img, $carpeta . '/' . $nombre, $calidad);
    }

    imagedestroy($img);

    return $ok ? $nombre : null;
}