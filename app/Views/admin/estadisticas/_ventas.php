<?php
$maxGrafico = max(array_column($grafico, 'total') ?: [0]);

$diasNombre = [2 => 'Lunes', 3 => 'Martes', 4 => 'Miércoles', 5 => 'Jueves', 6 => 'Viernes', 7 => 'Sábado', 1 => 'Domingo'];
$porDia     = [];
foreach ($diasNombre as $n => $nombre) {
    $porDia[] = ['nombre' => $nombre, 'total' => $momento['dia'][$n]['total'] ?? 0];
}

$maxHora = max(array_map(fn($x) => $x['total'], $momento['hora']) ?: [0]);

$pctTexto = fn($v) => $v === null ? '—' : number_format($v, 1, ',', '.') . '%';
?>

<?php if ($ventas['pedidos'] === 0): ?>
    <div class="bg-white rounded-lg shadow p-10 text-center text-gray-500">
        <p class="text-4xl mb-3">💰</p>
        <p class="font-medium">No hay ventas en este período.</p>
        <p class="text-sm text-gray-400 mt-1">Cuentan los pedidos pagados o entregados.</p>
    </div>
<?php else: ?>

<!-- ── Números ───────────────────────────────────────────────────── -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Facturado</p>
            <?= $badgeVariacion($varVentas['facturado']) ?>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $pesos($ventas['facturado']) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Ganancia</p>
            <?= $badgeVariacion($varVentas['ganancia']) ?>
        </div>
        <p class="text-2xl md:text-3xl font-bold <?= $ventas['ganancia'] < 0 ? 'text-red-600' : 'text-green-700' ?>"><?= $pesos($ventas['ganancia']) ?></p>
        <?php if ($ventas['unidades_sin_costo'] > 0): ?>
            <p class="text-[11px] text-gray-400 mt-1">sin contar <?= $num($ventas['unidades_sin_costo']) ?> u. sin costo cargado</p>
        <?php endif; ?>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Pedidos</p>
            <?= $badgeVariacion($varVentas['pedidos']) ?>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $num($ventas['pedidos']) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Ticket promedio</p>
            <?= $badgeVariacion($varVentas['ticket']) ?>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $pesos($ventas['ticket']) ?></p>
    </div>
</div>

<div class="grid grid-cols-3 gap-4 mb-6 text-sm">
    <div class="bg-white rounded-lg shadow px-4 py-3">
        <p class="text-gray-500">Unidades vendidas</p>
        <p class="font-bold text-lg"><?= $num($ventas['unidades']) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow px-4 py-3">
        <p class="text-gray-500">Productos por pedido</p>
        <p class="font-bold text-lg"><?= number_format($ventas['por_pedido'], 1, ',', '.') ?></p>
    </div>
    <div class="bg-white rounded-lg shadow px-4 py-3">
        <p class="text-gray-500">Descuento dado</p>
        <p class="font-bold text-lg text-orange-600"><?= $pesos($ventas['descuento']) ?></p>
    </div>
</div>

<!-- ── Gráfico ───────────────────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow p-5 mb-6">
    <h3 class="font-bold text-gray-800 mb-4"><?= htmlspecialchars($graficoTitulo) ?></h3>
    <?php if ($maxGrafico > 0): ?>
        <div class="flex items-end gap-1 h-48">
            <?php foreach ($grafico as $punto): ?>
                <?php $alto = $punto['total'] > 0 ? max(4, round($punto['total'] / $maxGrafico * 100)) : 0; ?>
                <div class="flex-1 h-full flex flex-col justify-end group relative">
                    <div class="w-full rounded-t <?= $punto['total'] > 0 ? 'bg-green-600 group-hover:bg-green-500' : 'bg-gray-100' ?>"
                         style="height: <?= $punto['total'] > 0 ? $alto : 2 ?>%"></div>
                    <div class="hidden group-hover:block absolute bottom-full left-1/2 -translate-x-1/2 mb-2 z-10
                                bg-gray-900 text-white text-xs rounded px-2 py-1 whitespace-nowrap">
                        <?= $punto['label'] ?>: <?= $pesos($punto['total']) ?> · <?= (int) $punto['pedidos'] ?> pedido(s)
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php $paso = count($grafico) > 15 ? (int) ceil(count($grafico) / 8) : 1; ?>
        <div class="flex gap-1 mt-2">
            <?php foreach ($grafico as $i => $punto): ?>
                <div class="flex-1 text-center text-[10px] text-gray-400"><?= ($i % $paso === 0) ? $punto['label'] : '' ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ── Por categoría y por marca ─────────────────────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <?php foreach (['Por categoría' => $porCategoria, 'Por marca' => $porMarca] as $titulo => $filas): ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <h3 class="font-bold text-gray-800 px-5 py-4 border-b"><?= $titulo ?></h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-4 py-2"></th>
                            <th class="text-right px-4 py-2">Unid.</th>
                            <th class="text-right px-4 py-2">Facturado</th>
                            <th class="text-right px-4 py-2">Ganancia</th>
                            <th class="text-right px-4 py-2">Margen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filas as $f): ?>
                            <tr class="border-t">
                                <td class="px-4 py-2 font-medium"><?= htmlspecialchars($f['nombre']) ?></td>
                                <td class="px-4 py-2 text-right text-gray-500"><?= $num($f['unidades']) ?></td>
                                <td class="px-4 py-2 text-right whitespace-nowrap"><?= $pesos($f['facturado']) ?></td>
                                <td class="px-4 py-2 text-right whitespace-nowrap text-green-700"><?= $f['ganancia'] !== null ? $pesos($f['ganancia']) : '—' ?></td>
                                <td class="px-4 py-2 text-right"><?= $pctTexto($f['margen']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ── Cuándo se vende ───────────────────────────────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-bold text-gray-800 mb-4">📅 Qué días se vende más</h3>
        <?php
        $porDiaTexto = array_map(fn($x) => ['nombre' => $x['nombre'], 'total' => $x['total']], $porDia);
        $maxDia = max(array_column($porDiaTexto, 'total')) ?: 1;
        ?>
        <ul class="space-y-3">
            <?php foreach ($porDiaTexto as $x): ?>
                <li>
                    <div class="flex justify-between text-sm mb-1">
                        <span><?= $x['nombre'] ?></span>
                        <span class="text-gray-500"><?= $pesos($x['total']) ?></span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-green-600 rounded-full" style="width: <?= round($x['total'] / $maxDia * 100) ?>%"></div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-bold text-gray-800 mb-4">🕐 A qué hora se vende más</h3>
        <div class="flex items-end gap-0.5 h-40">
            <?php for ($hr = 0; $hr < 24; $hr++): ?>
                <?php $t = $momento['hora'][$hr]['total'] ?? 0; ?>
                <div class="flex-1 h-full flex flex-col justify-end group relative">
                    <div class="w-full rounded-t <?= $t > 0 ? 'bg-green-600' : 'bg-gray-100' ?>"
                         style="height: <?= $t > 0 && $maxHora > 0 ? max(4, round($t / $maxHora * 100)) : 2 ?>%"></div>
                    <div class="hidden group-hover:block absolute bottom-full left-1/2 -translate-x-1/2 mb-2 z-10
                                bg-gray-900 text-white text-xs rounded px-2 py-1 whitespace-nowrap">
                        <?= $hr ?> h: <?= $pesos($t) ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
        <div class="flex gap-0.5 mt-1">
            <?php for ($hr = 0; $hr < 24; $hr++): ?>
                <div class="flex-1 text-center text-[10px] text-gray-400"><?= $hr % 3 === 0 ? $hr : '' ?></div>
            <?php endfor; ?>
        </div>
        <p class="text-xs text-gray-400 mt-3">Es la hora en que el cliente hizo el pedido en la web.</p>
    </div>
</div>

<?php endif; ?>