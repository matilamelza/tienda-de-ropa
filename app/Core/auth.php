<?php

/**
 * Verifica que haya una sesión de admin activa.
 * Si no, redirige al login.
 * Llamar al inicio de cada acción de controlador admin.
 */
function requireAdmin()
{
    if (!isset($_SESSION['admin'])) {
        header('Location: ' . BASE_URL . '/admin/login');
        exit;
    }
}