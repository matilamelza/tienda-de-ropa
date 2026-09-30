<?php
$nombreTiendaAdmin = (new ConfiguracionTienda())->get('tienda_nombre', 'Tienda');
$pedidosSinAtender = (new Pedido())->contarSinAtender();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
     <title><?= $pedidosSinAtender ? '(' . $pedidosSinAtender . ') ' : '' ?>Admin · <?= htmlspecialchars($nombreTiendaAdmin) ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">