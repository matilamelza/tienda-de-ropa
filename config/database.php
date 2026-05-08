<?php

$mysqli = new mysqli('localhost', 'root', '', 'tienda_ropa');
$mysqli->set_charset('utf8mb4');

// ── BASE_URL dinámico ────────────────────────────────────────────────────────
if (!defined('BASE_URL')) {
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $base      = rtrim($scriptDir, '/');
    define('BASE_URL', $protocolo . '://' . $host . $base);
}

return [
    'host'            => 'localhost',
    'user'            => 'root',
    'pass'            => '',
    'name'            => 'tienda_ropa',
    'conexion'        => $mysqli,
    'whatsapp_tienda' => '5492364123456',

    // ── SMTP para recuperación de contraseña ─────────────────────────────────
    // Dejá smtp_host vacío ('') para desactivar el envío automático.
    // Cuando esté configurado, el email se envía al cliente automáticamente.
    // Para Gmail: usá una "Contraseña de aplicación" (no tu contraseña normal).
    // ─────────────────────────────────────────────────────────────────────────
    'smtp_host'     => '',               // Ej: 'smtp.gmail.com'
    'smtp_port'     => 587,              // 587 (TLS) o 465 (SSL)
    'smtp_usuario'  => '',               // tu-email@gmail.com
    'smtp_password' => '',               // contraseña de aplicación
    'smtp_from'     => '',               // email remitente
    'smtp_nombre'   => 'Mi Tienda',      // nombre que ve el destinatario
];