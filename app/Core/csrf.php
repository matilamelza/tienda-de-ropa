<?php

/**
 * Protección CSRF: un token por sesión que viaja en cada formulario POST.
 * Un sitio externo no puede leer el token, así que no puede falsificar el envío.
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/** Input hidden para pegar dentro de cada <form method="POST">. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/** true si el POST trae el token correcto. */
function csrf_valido(): bool
{
    $enviado = $_POST['csrf_token'] ?? '';

    return is_string($enviado)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $enviado);
}

/**
 * Botón "eliminar" como mini-formulario POST con token y confirmación.
 * Reemplaza a los <a href=".../eliminar?id=X">.
 *
 * @param string $action        URL destino (ej: BASE_URL . '/admin/marcas/eliminar')
 * @param array  $campos        Campos hidden (ej: ['id' => 5])
 * @param string $confirmacion  Texto del confirm()
 * @param string $texto         Texto del botón
 * @param string $clases        Clases del botón
 */
function boton_eliminar(
    string $action,
    array $campos,
    string $confirmacion,
    string $texto = 'Eliminar',
    string $clases = 'text-red-600 hover:underline'
): string {
    $html  = '<form method="POST" action="' . htmlspecialchars($action) . '" class="inline"'
           . ' onsubmit="return confirm(' . htmlspecialchars(json_encode($confirmacion), ENT_QUOTES) . ')">';
    $html .= csrf_field();

    foreach ($campos as $nombre => $valor) {
        $html .= '<input type="hidden" name="' . htmlspecialchars($nombre) . '" value="' . htmlspecialchars((string) $valor) . '">';
    }

    $html .= '<button type="submit" class="' . htmlspecialchars($clases) . '">' . htmlspecialchars($texto) . '</button>';
    $html .= '</form>';

    return $html;
}