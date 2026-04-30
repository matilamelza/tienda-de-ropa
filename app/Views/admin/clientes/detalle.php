<?php
$estados = [
    'pendiente_contacto' => 'Pendiente contacto',
    'contactado' => 'Contactado',
    'pendiente_pago' => 'Pendiente pago',
    'pagado' => 'Pagado',
    'cancelado' => 'Cancelado',
    'entregado' => 'Entregado'
];
?>

<div class="mb-6 flex justify-between items-start">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">
            <?php echo htmlspecialchars(trim($cliente['nombre'] . ' ' . $cliente['apellido'])); ?>
        </h2>

        <p class="text-gray-500">
            Detalle del cliente y pedidos realizados
        </p>
    </div>

    <a href="/tienda_ropa/admin/clientes"
       class="px-4 py-2 rounded-lg border bg-white text-gray-700">
        Volver
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-1 space-y-6">

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                Datos del cliente
            </h3>

            <div class="space-y-3 text-sm">

                <div>
                    <p class="text-gray-500">Nombre</p>
                    <p class="font-medium">
                        <?php echo htmlspecialchars(trim($cliente['nombre'] . ' ' . $cliente['apellido'])); ?>
                    </p>
                </div>

                <div>
                    <p class="text-gray-500">Teléfono</p>
                    <p class="font-medium">
                        <?php echo htmlspecialchars($cliente['telefono'] ?: '-'); ?>
                    </p>
                </div>

                <div>
                    <p class="text-gray-500">Email</p>
                    <p class="font-medium">
                        <?php echo htmlspecialchars($cliente['email'] ?: '-'); ?>
                    </p>
                </div>

                <div>
                    <p class="text-gray-500">Localidad</p>
                    <p class="font-medium">
                        <?php echo htmlspecialchars($cliente['localidad'] ?: '-'); ?>
                    </p>
                </div>

                <div>
                    <p class="text-gray-500">Dirección</p>
                    <p class="font-medium">
                        <?php echo htmlspecialchars($cliente['direccion'] ?: '-'); ?>
                    </p>
                </div>

                <div>
                    <p class="text-gray-500">Tipo</p>

                    <?php if (!empty($cliente['id_usuario_cliente'])): ?>
                        <span class="inline-block mt-1 px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                            Cliente registrado
                        </span>
                    <?php else: ?>
                        <span class="inline-block mt-1 px-2 py-1 text-xs rounded bg-gray-100 text-gray-700">
                            Invitado
                        </span>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <?php if (!empty($cliente['telefono'])): ?>
            <?php
            $telefono = preg_replace('/\D/', '', $cliente['telefono']);

            if (substr($telefono, 0, 2) !== '54') {
                $telefono = '54' . $telefono;
            }

            $mensaje = urlencode('Hola ' . $cliente['nombre'] . ', te escribimos de la tienda.');
            ?>

            <a href="https://wa.me/<?php echo $telefono; ?>?text=<?php echo $mensaje; ?>"
               target="_blank"
               class="block text-center bg-green-600 text-white py-3 rounded-lg hover:bg-green-700">
                Contactar por WhatsApp
            </a>
        <?php endif; ?>

    </div>

    <div class="lg:col-span-2">

        <div class="bg-white rounded-lg shadow overflow-hidden">

            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-bold text-gray-800">
                    Pedidos del cliente
                </h3>
            </div>

            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3">Pedido</th>
                        <th class="text-center px-4 py-3">Estado</th>
                        <th class="text-left px-4 py-3">Fecha</th>
                        <th class="text-right px-4 py-3">Total</th>
                        <th class="text-right px-4 py-3">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($pedidos && $pedidos->num_rows > 0): ?>
                        <?php while ($p = $pedidos->fetch_assoc()): ?>

                            <tr class="border-t">
                                <td class="px-4 py-3 font-medium">
                                    #<?php echo $p['id_pedido']; ?>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-700">
                                        <?php echo $estados[$p['estado']] ?? $p['estado']; ?>
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <?php echo date('d/m/Y H:i', strtotime($p['fecha'])); ?>
                                </td>

                                <td class="px-4 py-3 text-right font-bold">
                                    $<?php echo number_format($p['total'], 2, ',', '.'); ?>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <a href="/tienda_ropa/admin/pedido/<?php echo $p['id_pedido']; ?>"
                                       class="text-gray-700 hover:underline">
                                        Ver pedido
                                    </a>
                                </td>
                            </tr>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                Este cliente no tiene pedidos.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>

    </div>

</div>