<?php
$urlPagina = function (int $n) use ($busqueda): string {
    return BASE_URL . '/admin/clientes?pagina=' . $n . ($busqueda !== '' ? '&q=' . urlencode($busqueda) : '');
};
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Clientes</h2>
    <p class="text-gray-500 text-sm"><?= (int) $total ?> cliente<?= $total !== 1 ? 's' : '' ?> en total</p>
</div>

<!-- Buscador -->
<form method="GET" action="<?= BASE_URL ?>/admin/clientes" class="mb-4 flex flex-wrap gap-2">
    <input type="text" name="q" value="<?= htmlspecialchars($busqueda) ?>"
           placeholder="Buscar por nombre, email o teléfono…"
           class="border rounded-lg px-4 py-2 text-sm flex-1 min-w-0 focus:outline-none focus:ring-2 focus:ring-gray-300">
    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700">
        Buscar
    </button>
    <?php if ($busqueda): ?>
        <a href="<?= BASE_URL ?>/admin/clientes" class="border px-4 py-2 rounded-lg text-sm hover:bg-gray-50 text-gray-600">
            Limpiar
        </a>
    <?php endif; ?>
</form>

<?php if (empty($clientes)): ?>
    <div class="bg-white rounded-lg shadow px-4 py-10 text-center text-gray-400 text-sm">
        <?= $busqueda ? 'No se encontraron clientes para "' . htmlspecialchars($busqueda) . '"' : 'Todavía no hay clientes.' ?>
    </div>
<?php else: ?>

    <!-- Mobile: tarjetas -->
    <div class="md:hidden space-y-3">
        <?php foreach ($clientes as $c): ?>
            <a href="<?= BASE_URL ?>/admin/cliente?id=<?= (int) $c['id_cliente'] ?>"
               class="block bg-white rounded-lg shadow p-4 active:bg-gray-50">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 truncate">
                            <?= htmlspecialchars(trim($c['nombre'] . ' ' . $c['apellido'])) ?>
                        </p>
                        <p class="text-sm text-gray-500 truncate"><?= htmlspecialchars($c['telefono'] ?: '-') ?></p>
                        <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($c['email'] ?: '-') ?></p>
                    </div>
                    <?php if (!empty($c['id_usuario_cliente'])): ?>
                        <span class="shrink-0 px-2 py-1 text-xs rounded bg-green-100 text-green-700">Registrado</span>
                    <?php else: ?>
                        <span class="shrink-0 px-2 py-1 text-xs rounded bg-gray-100 text-gray-700">Invitado</span>
                    <?php endif; ?>
                </div>
                <div class="flex justify-between items-center mt-3 pt-3 border-t text-sm">
                    <span class="text-gray-500"><?= (int) $c['cantidad_pedidos'] ?> pedido(s)</span>
                    <span class="font-bold">$<?= number_format($c['total_pedidos'], 2, ',', '.') ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Desktop: tabla -->
    <div class="hidden md:block bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3">Cliente</th>
                        <th class="text-left px-4 py-3">Contacto</th>
                        <th class="text-center px-4 py-3">Tipo</th>
                        <th class="text-center px-4 py-3">Pedidos</th>
                        <th class="text-right px-4 py-3">Total</th>
                        <th class="text-right px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $c): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900"><?= htmlspecialchars(trim($c['nombre'] . ' ' . $c['apellido'])) ?></p>
                                <p class="text-xs text-gray-400">ID: <?= (int) $c['id_cliente'] ?></p>
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
                            <td class="px-4 py-3 text-center"><?= (int) $c['cantidad_pedidos'] ?></td>
                            <td class="px-4 py-3 text-right font-bold whitespace-nowrap">
                                $<?= number_format($c['total_pedidos'], 2, ',', '.') ?>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="<?= BASE_URL ?>/admin/cliente?id=<?= (int) $c['id_cliente'] ?>"
                                   class="text-gray-700 hover:underline font-medium">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<!-- Paginación -->
<?php if ($totalPaginas > 1): ?>
    <?php
    $inicio = max(1, $pagina - 2);
    $fin    = min($totalPaginas, $pagina + 2);
    ?>
    <div class="mt-6 flex flex-wrap items-center justify-center gap-1 text-sm">

        <?php if ($pagina > 1): ?>
            <a href="<?= $urlPagina($pagina - 1) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">←</a>
        <?php endif; ?>

        <?php if ($inicio > 1): ?>
            <a href="<?= $urlPagina(1) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">1</a>
            <?php if ($inicio > 2): ?><span class="px-2 text-gray-400">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
            <a href="<?= $urlPagina($i) ?>"
               class="px-3 py-2 rounded-lg border <?= $i === $pagina ? 'bg-gray-800 text-white border-gray-800' : 'bg-white hover:bg-gray-50' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($fin < $totalPaginas): ?>
            <?php if ($fin < $totalPaginas - 1): ?><span class="px-2 text-gray-400">…</span><?php endif; ?>
            <a href="<?= $urlPagina($totalPaginas) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50"><?= $totalPaginas ?></a>
        <?php endif; ?>

        <?php if ($pagina < $totalPaginas): ?>
            <a href="<?= $urlPagina($pagina + 1) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">→</a>
        <?php endif; ?>
    </div>
    <p class="text-center text-xs text-gray-400 mt-2">Página <?= $pagina ?> de <?= $totalPaginas ?></p>
<?php endif; ?>