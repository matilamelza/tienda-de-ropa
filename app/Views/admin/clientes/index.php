<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Clientes</h2>
        <p class="text-gray-500 text-sm"><?= $total ?> cliente<?= $total !== 1 ? 's' : '' ?> en total</p>
    </div>
</div>

<!-- Buscador -->
<form method="GET" action="<?= BASE_URL ?>/admin/clientes" class="mb-4 flex gap-2">
    <input type="hidden" name="route" value="admin_clientes">
    <input type="text" name="q" value="<?= htmlspecialchars($busqueda) ?>"
           placeholder="Buscar por nombre, email o teléfono…"
           class="border rounded-lg px-4 py-2 text-sm flex-1 focus:outline-none focus:ring-2 focus:ring-gray-300">
    <button type="submit"
            class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700">
        Buscar
    </button>
    <?php if ($busqueda): ?>
        <a href="<?= BASE_URL ?>/admin/clientes"
           class="border px-4 py-2 rounded-lg text-sm hover:bg-gray-50 text-gray-600">
            Limpiar
        </a>
    <?php endif; ?>
</form>

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
            <?php if (!empty($clientes)): ?>
                <?php foreach ($clientes as $c): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900">
                                <?= htmlspecialchars(trim($c['nombre'] . ' ' . $c['apellido'])) ?>
                            </p>
                            <p class="text-xs text-gray-400">ID: <?= $c['id_cliente'] ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <p><?= htmlspecialchars($c['telefono'] ?: '-') ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($c['email'] ?: '-') ?></p>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if (!empty($c['id_usuario_cliente'])): ?>
                                <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Registrado</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700">Invitado</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center"><?= $c['cantidad_pedidos'] ?></td>
                        <td class="px-4 py-3 text-right font-bold">
                            $<?= number_format($c['total_pedidos'], 2, ',', '.') ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?= BASE_URL ?>/admin/cliente?id=<?= $c['id_cliente'] ?>"
                               class="text-gray-700 hover:underline font-medium">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                        <?= $busqueda ? 'No se encontraron clientes para "' . htmlspecialchars($busqueda) . '"' : 'Todavía no hay clientes.' ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Paginación -->
<?php if ($totalPaginas > 1): ?>
    <div class="mt-6 flex items-center justify-center gap-1 text-sm">

        <?php if ($pagina > 1): ?>
            <a href="?route=admin_clientes&pagina=<?= $pagina - 1 ?><?= $busqueda ? '&q=' . urlencode($busqueda) : '' ?>"
               class="px-3 py-2 rounded-lg border hover:bg-gray-50">← Anterior</a>
        <?php endif; ?>

        <?php
        $inicio = max(1, $pagina - 2);
        $fin    = min($totalPaginas, $pagina + 2);
        ?>

        <?php if ($inicio > 1): ?>
            <a href="?route=admin_clientes&pagina=1<?= $busqueda ? '&q=' . urlencode($busqueda) : '' ?>"
               class="px-3 py-2 rounded-lg border hover:bg-gray-50">1</a>
            <?php if ($inicio > 2): ?>
                <span class="px-2 text-gray-400">…</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
            <a href="?route=admin_clientes&pagina=<?= $i ?><?= $busqueda ? '&q=' . urlencode($busqueda) : '' ?>"
               class="px-3 py-2 rounded-lg border <?= $i === $pagina ? 'bg-gray-800 text-white border-gray-800' : 'hover:bg-gray-50' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($fin < $totalPaginas): ?>
            <?php if ($fin < $totalPaginas - 1): ?>
                <span class="px-2 text-gray-400">…</span>
            <?php endif; ?>
            <a href="?route=admin_clientes&pagina=<?= $totalPaginas ?><?= $busqueda ? '&q=' . urlencode($busqueda) : '' ?>"
               class="px-3 py-2 rounded-lg border hover:bg-gray-50"><?= $totalPaginas ?></a>
        <?php endif; ?>

        <?php if ($pagina < $totalPaginas): ?>
            <a href="?route=admin_clientes&pagina=<?= $pagina + 1 ?><?= $busqueda ? '&q=' . urlencode($busqueda) : '' ?>"
               class="px-3 py-2 rounded-lg border hover:bg-gray-50">Siguiente →</a>
        <?php endif; ?>

    </div>
    <p class="text-center text-xs text-gray-400 mt-2">
        Página <?= $pagina ?> de <?= $totalPaginas ?>
    </p>
<?php endif; ?>