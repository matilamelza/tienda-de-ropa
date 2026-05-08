<?php

$mysqli = new mysqli('localhost', 'root', '', 'tienda_ropa');
$mysqli->set_charset('utf8mb4');

// ── BASE_URL dinámico ────────────────────────────────────────────────────────
// Detecta automáticamente el protocolo, host y subcarpeta del proyecto.
// Funciona tanto en localhost (Laragon, XAMPP) como en producción.
if (!defined('BASE_URL')) {
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Detecta si el proyecto está en una subcarpeta (ej: /tienda_ropa)
    // Toma el directorio del index.php público como base
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $base      = rtrim($scriptDir, '/');

    define('BASE_URL', $protocolo . '://' . $host . $base);
}
// ─────────────────────────────────────────────────────────────────────────────

return [
    'host'            => 'localhost',
    'user'            => 'root',
    'pass'            => '',
    'name'            => 'tienda_ropa',
    'conexion'        => $mysqli,
    'whatsapp_tienda' => '5492364123456'
];