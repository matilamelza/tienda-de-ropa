<?php
// ── helper estados ────────────────────────────────────────────
$estados = [
    'pendiente_contacto' => 'Pendiente contacto',
    'pendiente_pago'     => 'Pendiente pago',
    'pagado'             => 'Pagado',
    'enviado'            => 'Enviado',
    'entregado'          => 'Entregado',
    'cancelado'          => 'Cancelado',
];
$colores = [
    'pendiente_contacto' => 'bg-yellow-100 text-yellow-700',
    'pendiente_pago'     => 'bg-orange-100 text-orange-700',
    'pagado'             => 'bg-green-100 text-green-700',
    'enviado'            => 'bg-blue-100 text-blue-700',
    'entregado'          => 'bg-green-200 text-green-800',
    'cancelado'          => 'bg-red-100 text-red-700',
];
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Pedidos</h2>
        <p class="text-gray-500 text-sm"><?= $total ?> pedido<?= $total !== 1 ? 's' : '' ?> en total</p>
    </div>
</div>

<!-- Buscador -->
<form method="GET" action="<?= BASE_URL ?>/admin/pedidos" class="mb-4 flex gap-2">
    <input type="hidden" name="route" value="admin_pedidos">
    <input type="text" name="q" value="<?= htmlspecialchars($busqueda) ?>"
           placeholder="Buscar por cliente, teléfono, email o #pedido…"
           class="border rounded-lg px-4 py-2 text-sm flex-1 focus:outline-none focus:ring-2 focus:ring-gray-300">
    <button type="submit"
            class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700">
        Buscar
    </button>
    <?php if ($busqueda): ?>
        <a href="<?= BASE_URL ?>/admin/pedidos"
           class="border px-4 py-2 rounded-lg text-sm hover:bg-gray-50 text-gray-600">
            Limpiar
        </a>
    <?php endif; ?>
</form>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-3 text-left">#</th>
                <th class="px-4 py-3 text-left">Cliente</th>
                <th class="px-4 py-3 text-left">Teléfono</th>
                <th class="px-4 py-3 text-right">Total</th>
                <th class="px-4 py-3 text-center">Estado</th>
                <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($pedidos)): ?>
                <?php foreach ($pedidos as $p): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">#<?= $p['id_pedido'] ?></td>
                        <td class="px-4 py-3">
                            <?= htmlspecialchars(trim(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? ''))) ?>
                        </td>
                        <td class="px-4 py-3"><?= htmlspecialchars($p['telefono'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-right font-semibold">
                            $<?= number_format($p['total'], 2, ',', '.') ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-xs rounded <?= $colores[$p['estado']] ?? 'bg-gray-100 text-gray-700' ?>">
                                <?= $estados[$p['estado']] ?? $p['estado'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="<?= BASE_URL ?>/admin/pedido/<?= $p['id_pedido'] ?>"
                               class="text-gray-700 hover:underline font-medium">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                        <?= $busqueda ? 'No se encontraron pedidos para "' . htmlspecialchars($busqueda) . '"' : 'Todavía no hay pedidos.' ?>
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
            <a href="?route=admin_pedidos&pagina=<?= $pagina - 1 ?><?= $busqueda ? '&q=' . urlencode($busqueda) : '' ?>"
               class="px-3 py-2 rounded-lg border hover:bg-gray-50">← Anterior</a>
        <?php endif; ?>

        <?php
        $inicio = max(1, $pagina - 2);
        $fin    = min($totalPaginas, $pagina + 2);
        ?>

        <?php if ($inicio > 1): ?>
            <a href="?route=admin_pedidos&pagina=1<?= $busqueda ? '&q=' . urlencode($busqueda) : '' ?>"
               class="px-3 py-2 rounded-lg border hover:bg-gray-50">1</a>
            <?php if ($inicio > 2): ?>
                <span class="px-2 text-gray-400">…</span>
            <?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
            <a href="?route=admin_pedidos&pagina=<?= $i ?><?= $busqueda ? '&q=' . urlencode($busqueda) : '' ?>"
               class="px-3 py-2 rounded-lg border <?= $i === $pagina ? 'bg-gray-800 text-white border-gray-800' : 'hover:bg-gray-50' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($fin < $totalPaginas): ?>
            <?php if ($fin < $totalPaginas - 1): ?>
                <span class="px-2 text-gray-400">…</span>
            <?php endif; ?>
            <a href="?route=admin_pedidos&pagina=<?= $totalPaginas ?><?= $busqueda ? '&q=' . urlencode($busqueda) : '' ?>"
               class="px-3 py-2 rounded-lg border hover:bg-gray-50"><?= $totalPaginas ?></a>
        <?php endif; ?>

        <?php if ($pagina < $totalPaginas): ?>
            <a href="?route=admin_pedidos&pagina=<?= $pagina + 1 ?><?= $busqueda ? '&q=' . urlencode($busqueda) : '' ?>"
               class="px-3 py-2 rounded-lg border hover:bg-gray-50">Siguiente →</a>
        <?php endif; ?>

    </div>
    <p class="text-center text-xs text-gray-400 mt-2">
        Página <?= $pagina ?> de <?= $totalPaginas ?>
    </p>
<?php endif; ?>