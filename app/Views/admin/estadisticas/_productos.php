<?php
$pct = fn($a, $b) => $b > 0 ? round($a / $b * 100) : 0;

$maxTalle     = max(array_column($talles, 'personas') ?: [0]) ?: 1;
$abandonoPct  = $carritos['agregaron'] > 0
    ? round(($carritos['agregaron'] - $carritos['compraron']) / $carritos['agregaron'] * 100)
    : 0;
?>

<?php if (empty($embudoProd) && empty($talles) && $carritos['agregaron'] === 0): ?>
    <div class="bg-white rounded-lg shadow p-10 text-center text-gray-500 mb-6">
        <p class="text-4xl mb-3">👟</p>
        <p class="font-medium">Todavía no hay datos de productos en este período.</p>
        <p class="text-sm text-gray-400 mt-1">Los talles elegidos y los agregados al carrito se registran desde que se activó esta función.</p>
    </div>
<?php else: ?>

<!-- ── Carritos ──────────────────────────────────────────────────── -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500 mb-2">Agregaron al carrito</p>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $num($carritos['agregaron']) ?></p>
        <p class="text-xs text-gray-400 mt-1">personas</p>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500 mb-2">Hicieron el pedido</p>
        <p class="text-2xl md:text-3xl font-bold text-green-700"><?= $num($carritos['compraron']) ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= $pct($carritos['compraron'], $carritos['agregaron']) ?>% de los que agregaron</p>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500 mb-2">🛒 Carritos abandonados</p>
        <p class="text-2xl md:text-3xl font-bold <?= $abandonoPct > 70 ? 'text-orange-600' : 'text-gray-900' ?>"><?= $abandonoPct ?>%</p>
        <p class="text-xs text-gray-400 mt-1"><?= $num($carritos['agregaron'] - $carritos['compraron']) ?> agregaron y no terminaron</p>
    </div>
</div>

<!-- ── Embudo por producto ───────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow overflow-hidden mb-6">
    <div class="px-5 py-4 border-b">
        <h3 class="font-bold text-gray-800">Embudo por producto</h3>
        <p class="text-xs text-gray-400">
            Si muchos lo ven y pocos lo agregan: revisá precio o fotos. Si lo agregan y no lo compran: algo frena en el checkout.
        </p>
    </div>
    <?php if (empty($embudoProd)): ?>
        <p class="px-5 py-8 text-sm text-gray-400 text-center">Sin visitas a productos en este período.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-5 py-3">Producto</th>
                        <th class="text-right px-5 py-3">Lo vieron</th>
                        <th class="text-right px-5 py-3">Agregaron</th>
                        <th class="text-right px-5 py-3">Vendidas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($embudoProd as $p): ?>
                        <?php $pAgr = $pct($p['agregaron'], $p['vieron']); ?>
                        <tr class="border-t">
                            <td class="px-5 py-3">
                                <a href="<?= BASE_URL ?>/admin/productos/editar?id=<?= (int) $p['id_producto'] ?>" class="hover:underline">
                                    <?= htmlspecialchars($p['nombre']) ?>
                                </a>
                            </td>
                            <td class="px-5 py-3 text-right"><?= $num($p['vieron']) ?></td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <?= $num($p['agregaron']) ?>
                                <span class="text-xs <?= $p['vieron'] >= 10 && $pAgr < 5 ? 'text-orange-600 font-semibold' : 'text-gray-400' ?>">(<?= $pAgr ?>%)</span>
                            </td>
                            <td class="px-5 py-3 text-right <?= (int) $p['vendidas'] > 0 ? 'text-green-700 font-semibold' : 'text-gray-300' ?>">
                                <?= $num($p['vendidas']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    <!-- ── Talles elegidos ──────────────────────────────────────── -->
    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-bold text-gray-800 mb-1">👟 Talles más elegidos</h3>
        <p class="text-xs text-gray-400 mb-4">Personas que tocaron cada talle en las fichas. En rojo, las que lo eligieron cuando estaba agotado.</p>

        <?php if (empty($talles)): ?>
            <p class="text-sm text-gray-400 py-4 text-center">Sin datos en este período.</p>
        <?php else: ?>
            <ul class="space-y-2">
                <?php foreach ($talles as $t): ?>
                    <?php
                    $ancho   = round($t['personas'] / $maxTalle * 100);
                    $anchoSS = $t['personas'] > 0 ? round($t['sin_stock'] / $t['personas'] * $ancho) : 0;
                    ?>
                    <li class="flex items-center gap-3 text-sm">
                        <span class="w-12 shrink-0 font-medium text-right"><?= htmlspecialchars($t['talle']) ?></span>
                        <div class="flex-1 h-4 bg-gray-100 rounded overflow-hidden flex">
                            <div class="h-full bg-gray-900" style="width: <?= $ancho - $anchoSS ?>%"></div>
                            <div class="h-full bg-red-500" style="width: <?= $anchoSS ?>%"></div>
                        </div>
                        <span class="w-24 shrink-0 text-xs text-gray-500 text-right">
                            <?= $num($t['personas']) ?><?= $t['sin_stock'] > 0 ? ' · <span class="text-red-600">' . $num($t['sin_stock']) . ' agot.</span>' : '' ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- ── Agotados buscados ────────────────────────────────────── -->
    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-bold text-gray-800 mb-1">🚨 Agotados que la gente busca</h3>
        <p class="text-xs text-gray-400 mb-4">Talles sin stock que más intentaron elegir. Tu lista de reposición.</p>

        <?php if (empty($agotados)): ?>
            <p class="text-sm text-gray-500">✅ Nadie intentó elegir talles agotados.</p>
        <?php else: ?>
            <ul class="divide-y text-sm">
                <?php foreach ($agotados as $a): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/productos/variantes?id=<?= (int) $a['id_producto'] ?>"
                           class="flex items-center justify-between gap-3 py-2 hover:bg-gray-50">
                            <span class="min-w-0 truncate">
                                <?= htmlspecialchars($a['nombre']) ?>
                                <span class="ml-1 px-1.5 py-0.5 rounded bg-red-50 text-red-700 text-xs"><?= htmlspecialchars($a['talle']) ?></span>
                            </span>
                            <span class="shrink-0 text-red-600 font-semibold"><?= $num($a['personas']) ?> persona(s)</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- ── Productos abandonados ─────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow p-5">
    <h3 class="font-bold text-gray-800 mb-1">🛒 Lo que queda en los carritos</h3>
    <p class="text-xs text-gray-400 mb-4">Productos que se agregaron al carrito y no terminaron en un pedido.</p>
    <?= $listaConBarras($abandonados, 'nombre', 'personas', ' persona(s)') ?>
</div>

<?php endif; ?>