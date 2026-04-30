<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Clientes</h2>
        <p class="text-gray-500">Clientes registrados e invitados que hicieron pedidos</p>
    </div>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">

    <table class="w-full text-sm">
        <thead class="bg-gray-100 text-gray-700">
            <tr>
                <th class="text-left px-4 py-3">Cliente</th>
                <th class="text-left px-4 py-3">Contacto</th>
                <th class="text-center px-4 py-3">Tipo</th>
                <th class="text-center px-4 py-3">Pedidos</th>
                <th class="text-right px-4 py-3">Total</th>
                <th class="text-right px-4 py-3">Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php if ($clientes && $clientes->num_rows > 0): ?>
                <?php while ($c = $clientes->fetch_assoc()): ?>

                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900">
                                <?php echo htmlspecialchars(trim($c['nombre'] . ' ' . $c['apellido'])); ?>
                            </p>

                            <p class="text-xs text-gray-400">
                                ID Cliente: <?php echo $c['id_cliente']; ?>
                            </p>
                        </td>

                        <td class="px-4 py-3">
                            <p><?php echo htmlspecialchars($c['telefono'] ?: '-'); ?></p>
                            <p class="text-xs text-gray-500">
                                <?php echo htmlspecialchars($c['email'] ?: '-'); ?>
                            </p>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <?php if (!empty($c['id_usuario_cliente'])): ?>
                                <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                                    Registrado
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700">
                                    Invitado
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <?php echo $c['cantidad_pedidos']; ?>
                        </td>

                        <td class="px-4 py-3 text-right font-bold">
                            $<?php echo number_format($c['total_pedidos'], 2, ',', '.'); ?>
                        </td>

                        <td class="px-4 py-3 text-right">
                            <a href="/tienda_ropa/admin/cliente?id=<?php echo $c['id_cliente']; ?>"
                               class="text-gray-700 hover:underline">
                                Ver detalle
                            </a>
                        </td>
                    </tr>

                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                        Todavía no hay clientes.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>