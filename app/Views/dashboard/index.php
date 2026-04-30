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

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
    <p class="text-gray-500">Resumen general de la tienda</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">

    <div class="bg-white rounded-lg shadow p-5 border-l-4 border-gray-900">
        <p class="text-sm text-gray-500">Pedidos</p>
        <h3 class="text-3xl font-bold text-gray-900">
            <?php echo $resumen['total_pedidos'] ?? 0; ?>
        </h3>
    </div>

    <div class="bg-white rounded-lg shadow p-5 border-l-4 border-yellow-500">
        <p class="text-sm text-gray-500">Pendientes contacto</p>
        <h3 class="text-3xl font-bold text-gray-900">
            <?php echo $resumen['pendientes_contacto'] ?? 0; ?>
        </h3>
    </div>

    <div class="bg-white rounded-lg shadow p-5 border-l-4 border-blue-500">
        <p class="text-sm text-gray-500">Pendientes pago</p>
        <h3 class="text-3xl font-bold text-gray-900">
            <?php echo $resumen['pendientes_pago'] ?? 0; ?>
        </h3>
    </div>

    <div class="bg-white rounded-lg shadow p-5 border-l-4 border-green-500">
        <p class="text-sm text-gray-500">Pagados</p>
        <h3 class="text-3xl font-bold text-gray-900">
            <?php echo $resumen['pagados'] ?? 0; ?>
        </h3>
    </div>

</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500">Total vendido</p>
        <h3 class="text-3xl font-bold text-green-700">
            $<?php echo number_format($resumen['total_vendido'] ?? 0, 2, ',', '.'); ?>
        </h3>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500">Total pendiente</p>
        <h3 class="text-3xl font-bold text-yellow-700">
            $<?php echo number_format($resumen['total_pendiente'] ?? 0, 2, ',', '.'); ?>
        </h3>
    </div>

</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <div class="bg-white rounded-lg shadow overflow-hidden">

        <div class="px-5 py-4 border-b flex justify-between items-center">
            <div>
                <h3 class="font-bold text-gray-800">Últimos pedidos</h3>
                <p class="text-sm text-gray-500">Movimientos recientes</p>
            </div>

            <a href="index.php?route=admin_pedidos" class="text-sm text-gray-600 hover:text-gray-900">
                Ver todos
            </a>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="text-left px-4 py-3">Pedido</th>
                    <th class="text-left px-4 py-3">Cliente</th>
                    <th class="text-center px-4 py-3">Estado</th>
                    <th class="text-right px-4 py-3">Total</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($ultimosPedidos && $ultimosPedidos->num_rows > 0): ?>
                    <?php while ($p = $ultimosPedidos->fetch_assoc()): ?>
                        <tr class="border-t">
                            <td class="px-4 py-3">
                                <a href="index.php?route=admin_pedido_detalle&id=<?php echo $p['id_pedido']; ?>"
                                   class="font-medium text-gray-900 hover:underline">
                                    #<?php echo $p['id_pedido']; ?>
                                </a>
                            </td>

                            <td class="px-4 py-3">
                                <?php echo htmlspecialchars(trim(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? ''))); ?>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded text-xs bg-gray-100 text-gray-700">
                                    <?php echo $estados[$p['estado']] ?? $p['estado']; ?>
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right font-bold">
                                $<?php echo number_format($p['total'], 2, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                            Todavía no hay pedidos.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">

        <div class="px-5 py-4 border-b">
            <h3 class="font-bold text-gray-800">Stock bajo</h3>
            <p class="text-sm text-gray-500">Variantes con 3 unidades o menos disponibles</p>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="text-left px-4 py-3">Producto</th>
                    <th class="text-left px-4 py-3">Variante</th>
                    <th class="text-center px-4 py-3">Disponible</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($stockBajo && $stockBajo->num_rows > 0): ?>
                    <?php while ($s = $stockBajo->fetch_assoc()): ?>
                        <tr class="border-t">
                            <td class="px-4 py-3 font-medium">
                                <?php echo htmlspecialchars($s['producto']); ?>
                            </td>

                            <td class="px-4 py-3">
                                <?php echo htmlspecialchars(($s['talle'] ?? '-') . ' / ' . ($s['color'] ?? '-')); ?>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <?php if ($s['stock_disponible'] <= 0): ?>
                                    <span class="px-2 py-1 rounded text-xs bg-red-100 text-red-700">
                                        Sin stock
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 rounded text-xs bg-yellow-100 text-yellow-700">
                                        <?php echo $s['stock_disponible']; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-gray-500">
                            No hay productos con stock bajo.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

</div>