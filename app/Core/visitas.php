<?php

/**
 * Registra una visita de la tienda.
 * Nunca rompe la página: si algo falla, lo anota en el log y sigue.
 *
 * @param string      $tipo        inicio|producto|categoria|marca|busqueda|carrito|checkout|otra
 * @param int|null    $idRef       id del producto / categoría / marca
 * @param string|null $termino     lo que buscó (solo para 'busqueda')
 * @param int|null    $resultados  cuántos productos encontró (solo para 'busqueda')
 */
function registrar_visita(string $tipo, ?int $idRef = null, ?string $termino = null, ?int $resultados = null): void
{
    try {
        // No contar al admin ni pedidos que no sean GET
        if (isset($_SESSION['admin']) || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }

        // No contar robots ni vistas previas de links (Google, WhatsApp, Facebook, etc.)
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|preview|curl|wget|python|headless|lighthouse/i', $ua)) {
            return;
        }

        // No contar recargas de la misma página en menos de 30 segundos
        $clave = $tipo . '|' . $idRef . '|' . $termino;
        if (($_SESSION['ultima_visita']['clave'] ?? '') === $clave
            && time() - ($_SESSION['ultima_visita']['hora'] ?? 0) < 30) {
            return;
        }
        $_SESSION['ultima_visita'] = ['clave' => $clave, 'hora' => time()];

        // Identificador anónimo del visitante (cookie de 1 año, código aleatorio)
        $visitante = $_COOKIE['vid'] ?? '';
        if (!preg_match('/^[a-f0-9]{32}$/', $visitante)) {
            $visitante = bin2hex(random_bytes(16));
            setcookie('vid', $visitante, [
                'expires'  => time() + 365 * 24 * 3600,
                'path'     => '/',
                'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        (new Visita())->registrar([
            'visitante'   => $visitante,
            'tipo'        => $tipo,
            'id_ref'      => $idRef,
            'termino'     => $termino !== null ? mb_substr(mb_strtolower(trim($termino)), 0, 100) : null,
            'resultados'  => $resultados,
            'origen'      => visita_origen(),
            'dispositivo' => preg_match('/Mobi|Android|iPhone|iPad/i', $ua) ? 'mobile' : 'desktop',
        ]);

        // Limpieza ocasional de visitas de más de un año (1 de cada 500)
        if (random_int(1, 500) === 1) {
            (new Visita())->limpiarViejas(365);
        }

    } catch (Throwable $e) {
        error_log('registrar_visita: ' . $e->getMessage());
    }
}

/**
 * De dónde llegó el visitante.
 * null  = navegó desde otra página de la misma tienda (no es una "entrada")
 * 'directo' = escribió la URL, favorito, o app que no informa (ej: muchas veces WhatsApp)
 */
function visita_origen(): ?string
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referer === '') {
        return 'directo';
    }

    $host  = strtolower(parse_url($referer, PHP_URL_HOST) ?? '');
    $propio = strtolower($_SERVER['HTTP_HOST'] ?? '');

    if ($host === '' ) {
        return 'directo';
    }
    if ($host === $propio) {
        return null;
    }

    $conocidos = [
        'instagram' => 'instagram',
        'facebook'  => 'facebook',
        'fb.'       => 'facebook',
        'google'    => 'google',
        'whatsapp'  => 'whatsapp',
        'wa.me'     => 'whatsapp',
        'tiktok'    => 'tiktok',
        'bing'      => 'bing',
        'mercadolibre' => 'mercadolibre',
    ];

    foreach ($conocidos as $buscar => $nombre) {
        if (strpos($host, $buscar) !== false) {
            return $nombre;
        }
    }

    return mb_substr(preg_replace('/^www\./', '', $host), 0, 30);
}