<?php

/**
 * Manejo global de errores:
 * - Siempre se registran en el log del servidor.
 * - El visitante ve una página prolija en vez del "Error 500" del navegador.
 * - En local, o con admin logueado + ?debug=1, además se muestra el detalle.
 */

function errores_mostrar_detalle(): bool
{
    return defined('MOSTRAR_ERRORES') && MOSTRAR_ERRORES === true;
}

function errores_pagina_500(string $detalle = ''): void
{
    // Descartar lo que se haya empezado a imprimir (una vista a medias, etc.)
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    $inicio = defined('BASE_URL') ? BASE_URL . '/tienda' : '/';

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <title>Algo salió mal</title>
          <script src="https://cdn.tailwindcss.com"></script></head>
          <body class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
          <div class="text-center p-10 bg-white rounded-3xl shadow-lg max-w-lg w-full">
            <p class="text-5xl mb-4">😕</p>
            <h1 class="text-2xl font-bold mb-2">Algo salió mal</h1>
            <p class="text-gray-500 mb-8">Tuvimos un problema al cargar esta página. Probá de nuevo en unos minutos.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
              <a href="javascript:location.reload()" class="bg-gray-900 text-white px-6 py-3 rounded-full">Reintentar</a>
              <a href="' . htmlspecialchars($inicio) . '" class="border px-6 py-3 rounded-full text-gray-700">Ir a la tienda</a>
            </div>';

    if ($detalle !== '' && errores_mostrar_detalle()) {
        echo '<div class="mt-8 text-left">
                <p class="text-xs font-semibold text-red-600 uppercase tracking-wider mb-2">Detalle (solo visible para vos)</p>
                <pre class="bg-red-50 text-red-800 text-xs p-4 rounded-xl overflow-x-auto whitespace-pre-wrap">'
                . htmlspecialchars($detalle) .
              '</pre>
              </div>';
    }

    echo '</div></body></html>';
    exit;
}

// Excepciones no atrapadas (errores de MySQL, etc.)
set_exception_handler(function (Throwable $e) {
    $detalle = get_class($e) . ': ' . $e->getMessage()
             . "\nen " . $e->getFile() . ':' . $e->getLine()
             . "\n\n" . $e->getTraceAsString();

    error_log($detalle);
    errores_pagina_500($detalle);
});

// Errores fatales de PHP (archivo inexistente, sintaxis, etc.)
register_shutdown_function(function () {
    $error = error_get_last();
    $fatales = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    if ($error && in_array($error['type'], $fatales, true)) {
        $detalle = $error['message'] . "\nen " . $error['file'] . ':' . $error['line'];
        errores_pagina_500($detalle);
    }
});