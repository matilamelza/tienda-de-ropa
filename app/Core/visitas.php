<?php

// ═══════════════════════════════════════════════════════════════════════════
// Estadísticas de la tienda: visitas, eventos y campañas.
// Todo es anónimo (código aleatorio en una cookie) y nunca rompe la página.
// ═══════════════════════════════════════════════════════════════════════════

/** ¿Esta visita se tiene que ignorar? (admin logueado o robot) */
function visita_ignorada(): bool
{
    if (isset($_SESSION['admin'])) {
        return true;
    }

    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    return $ua === ''
        || (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|preview|curl|wget|python|headless|lighthouse/i', $ua);
}

/** Código anónimo del visitante (cookie de 1 año). Lo crea si no existe. */
function visitante_id(): string
{
    static $id = null;
    if ($id !== null) {
        return $id;
    }

    $id = $_COOKIE['vid'] ?? '';

    if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
        $id = bin2hex(random_bytes(16));
        setcookie('vid', $id, [
            'expires'  => time() + 365 * 24 * 3600,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    return $id;
}

/** Código de campaña que viene en ESTE link (?c=historia-lunes), o null. */
function campania_del_link(): ?string
{
    $c = strtolower(trim($_GET['c'] ?? ''));
    return preg_match('/^[a-z0-9-]{1,40}$/', $c) ? $c : null;
}

/**
 * Campaña activa del visitante: la del link actual, o la que trajo en los últimos 7 días.
 * Si viene en el link, se recuerda en una cookie.
 */
function campania_actual(): ?string
{
    static $campania = false;
    if ($campania !== false) {
        return $campania;
    }

    $delLink = campania_del_link();

    if ($delLink !== null) {
        setcookie('camp', $delLink, [
            'expires'  => time() + 7 * 24 * 3600,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        return $campania = $delLink;
    }

    $cookie = strtolower($_COOKIE['camp'] ?? '');
    return $campania = preg_match('/^[a-z0-9-]{1,40}$/', $cookie) ? $cookie : null;
}

/**
 * Registra una visita de la tienda.
 *
 * @param string      $tipo        inicio|producto|categoria|marca|busqueda|carrito|checkout|otra
 * @param int|null    $idRef       id del producto / categoría / marca
 * @param string|null $termino     lo que buscó (solo para 'busqueda')
 * @param int|null    $resultados  cuántos productos encontró (solo para 'busqueda')
 */
function registrar_visita(string $tipo, ?int $idRef = null, ?string $termino = null, ?int $resultados = null): void
{
    try {
        if (visita_ignorada() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }

        // Guardar la campaña ya (setea la cookie aunque la visita sea una recarga)
        campania_actual();

        // No contar recargas de la misma página en menos de 30 segundos
        $clave = $tipo . '|' . $idRef . '|' . $termino;
        if (($_SESSION['ultima_visita']['clave'] ?? '') === $clave
            && time() - ($_SESSION['ultima_visita']['hora'] ?? 0) < 30) {
            return;
        }
        $_SESSION['ultima_visita'] = ['clave' => $clave, 'hora' => time()];

        (new Visita())->registrar([
            'visitante'   => visitante_id(),
            'tipo'        => $tipo,
            'id_ref'      => $idRef,
            'termino'     => $termino !== null ? mb_substr(mb_strtolower(trim($termino)), 0, 100) : null,
            'resultados'  => $resultados,
            'origen'      => visita_origen(),
            'campania'    => campania_del_link(),   // solo la visita que entró por el link
            'dispositivo' => preg_match('/Mobi|Android|iPhone|iPad/i', $_SERVER['HTTP_USER_AGENT'] ?? '') ? 'mobile' : 'desktop',
        ] + ubicacion_visitante());                 // país, provincia y ciudad (aproximados)

        // Limpieza ocasional de visitas y eventos de más de un año (1 de cada 500)
        if (random_int(1, 500) === 1) {
            (new Visita())->limpiarViejas(365);
        }

    } catch (Throwable $e) {
        error_log('registrar_visita: ' . $e->getMessage());
        if (defined('MOSTRAR_ERRORES') && MOSTRAR_ERRORES) {
            throw $e;
        }
    }
}

/**
 * Ubicación aproximada del visitante por su IP (GeoLite2 / DB-IP). La IP no se guarda.
 * Se calcula una vez por sesión. Devuelve ['pais' =>, 'provincia' =>, 'ciudad' =>] (null si no se sabe).
 */
function ubicacion_visitante(): array
{
    $vacio = ['pais' => null, 'provincia' => null, 'ciudad' => null];

    if (isset($_SESSION['geo'])) {
        return $_SESSION['geo'];
    }

    try {
        $config = require __DIR__ . '/../../config/database.php';
        $ruta   = $config['geoip_path'] ?? '';
        $ip     = $_SERVER['REMOTE_ADDR'] ?? '';

        // En la compu la IP es local (127.0.0.1): para probar, se puede definir una IP en el config
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $ip = $config['geoip_ip_prueba'] ?? '';
        }

        if ($ruta === '' || !is_file($ruta) || !class_exists('MaxMind\Db\Reader') || $ip === '') {
            return $_SESSION['geo'] = $vacio;
        }

        $lector = new MaxMind\Db\Reader($ruta);
        $datos  = $lector->get($ip);
        $lector->close();

        if (!$datos) {
            return $_SESSION['geo'] = $vacio;   // IP que no está en la base
        }

        $nombre = fn($x) => $x['names']['es'] ?? $x['names']['en'] ?? null;

        $pais      = $datos['country']['iso_code'] ?? null;
        $provincia = isset($datos['subdivisions'][0]) ? trim((string) $nombre($datos['subdivisions'][0])) : '';
        $ciudad    = isset($datos['city']) ? trim((string) $nombre($datos['city'])) : '';

        // CABA: viene como "provincia" (ej: "Buenos Aires C.F.") y sin ciudad
        if ($ciudad === '' && preg_match('/C\.?F\.?|Ciudad Aut|Capital Federal/i', $provincia)) {
            $ciudad    = 'Ciudad de Buenos Aires';
            $provincia = 'CABA';
        }

        return $_SESSION['geo'] = [
            'pais'      => $pais,
            'provincia' => $provincia !== '' ? mb_substr($provincia, 0, 80) : null,
            'ciudad'    => $ciudad !== '' ? mb_substr($ciudad, 0, 80) : null,
        ];

    } catch (Throwable $e) {
        error_log('ubicacion_visitante: ' . $e->getMessage());
        return $_SESSION['geo'] = $vacio;
    }
}

/**
 * Registra un evento: 'talle' (eligió un talle) o 'agregar' (agregó al carrito).
 * $datos: id_producto, id_variante, talle, con_stock, cantidad (los que correspondan)
 */
function registrar_evento(string $tipo, array $datos): void
{
    try {
        if (visita_ignorada()) {
            return;
        }

        (new Evento())->registrar([
            'visitante'   => visitante_id(),
            'tipo'        => $tipo,
            'id_producto' => (int) $datos['id_producto'],
            'id_variante' => isset($datos['id_variante']) ? (int) $datos['id_variante'] : null,
            'talle'       => isset($datos['talle']) ? mb_substr((string) $datos['talle'], 0, 20) : null,
            'con_stock'   => isset($datos['con_stock']) ? (int) (bool) $datos['con_stock'] : null,
            'cantidad'    => isset($datos['cantidad']) ? (int) $datos['cantidad'] : null,
            'campania'    => campania_actual(),
        ]);

    } catch (Throwable $e) {
        error_log('registrar_evento: ' . $e->getMessage());
        if (defined('MOSTRAR_ERRORES') && MOSTRAR_ERRORES) {
            throw $e;
        }
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

    $host   = strtolower(parse_url($referer, PHP_URL_HOST) ?? '');
    $propio = strtolower($_SERVER['HTTP_HOST'] ?? '');

    if ($host === '') {
        return 'directo';
    }
    if ($host === $propio) {
        return null;
    }

    $conocidos = [
        'instagram'    => 'instagram',
        'facebook'     => 'facebook',
        'fb.'          => 'facebook',
        'google'       => 'google',
        'whatsapp'     => 'whatsapp',
        'wa.me'        => 'whatsapp',
        'tiktok'       => 'tiktok',
        'bing'         => 'bing',
        'mercadolibre' => 'mercadolibre',
    ];

    foreach ($conocidos as $buscar => $nombre) {
        if (strpos($host, $buscar) !== false) {
            return $nombre;
        }
    }

    return mb_substr(preg_replace('/^www\./', '', $host), 0, 30);
}