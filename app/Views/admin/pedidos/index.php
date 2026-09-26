<?php
$estados = [
    'pendiente_contacto' => ['Esperando contacto', 'bg-yellow-100 text-yellow-800'],
    'contactado'         => ['Contactado',         'bg-blue-100 text-blue-800'],
    'pendiente_pago'     => ['Pendiente de pago',  'bg-orange-100 text-orange-800'],
    'pagado'             => ['Pagado',             'bg-green-100 text-green-800'],
    'entregado'          => ['Entregado',          'bg-gray-200 text-gray-800'],
    'cancelado'          => ['Cancelado',          'bg-red-100 text-red-700'],
];

$pestanas = ['' => ['Todos', $conteo['todos']]];
foreach ($estados as $clave => [$texto]) {
    $pestanas[$clave] = [$texto, $conteo[$clave] ?? 0];
}
$pestanas['vencidos'] = ['Sin pagar +' . Pedido::DIAS_PAGO_VENCIDO . ' días', $conteo['vencidos']];

/** URL conservando búsqueda/estado */
$url = function (array $cambios = []) use ($busqueda, $estado): string {
    $q = array_filter(array_merge(['q' => $busqueda, 'estado' => $estado], $cambios), fn($v) => $v !== '' && $v !== null);
    return BASE_URL . '/admin/pedidos' . ($q ? '?' . http_build_query($q) : '');
};
?>

<div class="mb-4">
    <h2 class="text-2xl font-bold text-gray-800">Pedidos</h2>
    <p class="text-gray-500 text-sm"><?= (int) $total ?> pedido<?= $total !== 1 ? 's' : '' ?></p>
</div>

<!-- Pestañas por estado -->
<div class="-mx-4 md:mx-0 px-4 md:px-0 mb-4 overflow-x-auto">
    <div class="inline-flex gap-2 pb-1">
        <?php foreach ($pestanas as $clave => [$texto, $cant]): ?>
            <?php
            $activa   = $estado === $clave;
            $alerta   = in_array($clave, ['pendiente_contacto', 'vencidos'], true) && $cant > 0;
            ?>
            <a href="<?= $url(['estado' => $clave, 'pagina' => null]) ?>"
               class="whitespace-nowrap px-3 py-1.5 rounded-full text-sm border transition
                      <?= $activa ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 hover:bg-gray-50' ?>">
                <?= htmlspecialchars($texto) ?>
                <span class="ml-1 text-xs <?= $activa ? 'text-white/70' : ($alerta ? 'text-red-600 font-bold' : 'text-gray-400') ?>">
                    <?= (int) $cant ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Buscador (conserva el estado elegido) -->
<form method="GET" action="<?= BASE_URL ?>/admin/pedidos" class="mb-4 flex flex-wrap gap-2">
    <?php if ($estado !== ''): ?>
        <input type="hidden" name="estado" value="<?= htmlspecialchars($estado) ?>">
    <?php endif; ?>
    <input type="text" name="q" value="<?= htmlspecialchars($busqueda) ?>"
           placeholder="Buscar por cliente, teléfono, email o #pedido…"
           class="border rounded-lg px-4 py-2 text-sm flex-1 min-w-0 focus:outline-none focus:ring-2 focus:ring-gray-300">
    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700">Buscar</button>
    <?php if ($busqueda): ?>
        <a href="<?= $url(['q' => null, 'pagina' => null]) ?>"
           class="border px-4 py-2 rounded-lg text-sm hover:bg-gray-50 text-gray-600 bg-white">Limpiar</a>
    <?php endif; ?>
</form>

<?php if (empty($pedidos)): ?>
    <div class="bg-white rounded-lg shadow px-4 py-10 text-center text-gray-400 text-sm">
        <?php if ($busqueda): ?>
            No se encontraron pedidos para "<?= htmlspecialchars($busqueda) ?>".
        <?php elseif ($estado !== ''): ?>
            ✅ No hay pedidos en "<?= htmlspecialchars($pestanas[$estado][0]) ?>".
        <?php else: ?>
            Todavía no hay pedidos.
        <?php endif; ?>
    </div>
<?php else: ?>

    <!-- Mobile: tarjetas -->
    <div class="md:hidden space-y-3">
        <?php foreach ($pedidos as $p): ?>
            <?php [$txt, $cls] = $estados[$p['estado']] ?? [$p['estado'], 'bg-gray-100 text-gray-700']; ?>
            <a href="<?= BASE_URL ?>/admin/pedido/<?= (int) $p['id_pedido'] ?>"
               class="block bg-white rounded-lg shadow p-4 active:bg-gray-50">
                <div class="flex items-center justify-between">
                    <span class="font-semibold">#<?= (int) $p['id_pedido'] ?></span>
                    <span class="px-2 py-0.5 rounded text-xs <?= $cls ?>"><?= $txt ?></span>
                </div>
                <p class="text-sm text-gray-800 mt-2 truncate">
                    <?= htmlspecialchars(trim(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? ''))) ?: '—' ?>
                </p>
                <div class="flex items-center justify-between mt-1 text-sm">
                    <span class="text-gray-400"><?= date('d/m H:i', strtotime($p['fecha'])) ?></span>
                    <span class="font-bold">$<?= number_format($p['total'], 2, ',', '.') ?></span>
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
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Cliente</th>
                        <th class="px-4 py-3 text-left">Teléfono</th>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $p): ?>
                        <?php [$txt, $cls] = $estados[$p['estado']] ?? [$p['estado'], 'bg-gray-100 text-gray-700']; ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">#<?= (int) $p['id_pedido'] ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars(trim(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? ''))) ?: '—' ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($p['telefono'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap"><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
                            <td class="px-4 py-3 text-right font-semibold whitespace-nowrap">$<?= number_format($p['total'], 2, ',', '.') ?></td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 text-xs rounded whitespace-nowrap <?= $cls ?>"><?= $txt ?></span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="<?= BASE_URL ?>/admin/pedido/<?= (int) $p['id_pedido'] ?>"
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
            <a href="<?= $url(['pagina' => $pagina - 1]) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">←</a>
        <?php endif; ?>

        <?php if ($inicio > 1): ?>
            <a href="<?= $url(['pagina' => 1]) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">1</a>
            <?php if ($inicio > 2): ?><span class="px-2 text-gray-400">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
            <a href="<?= $url(['pagina' => $i]) ?>"
               class="px-3 py-2 rounded-lg border <?= $i === $pagina ? 'bg-gray-800 text-white border-gray-800' : 'bg-white hover:bg-gray-50' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($fin < $totalPaginas): ?>
            <?php if ($fin < $totalPaginas - 1): ?><span class="px-2 text-gray-400">…</span><?php endif; ?>
            <a href="<?= $url(['pagina' => $totalPaginas]) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50"><?= $totalPaginas ?></a>
        <?php endif; ?>

        <?php if ($pagina < $totalPaginas): ?>
            <a href="<?= $url(['pagina' => $pagina + 1]) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">→</a>
        <?php endif; ?>
    </div>
    <p class="text-center text-xs text-gray-400 mt-2">Página <?= $pagina ?> de <?= $totalPaginas ?></p>
<?php endif; ?>