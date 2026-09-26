<?php
$pesos = fn($n) => '$' . number_format((float) $n, 0, ',', '.');

$estados = [
    'pendiente_contacto' => ['Pendiente de contacto', 'bg-yellow-100 text-yellow-800'],
    'contactado'         => ['Contactado',            'bg-blue-100 text-blue-800'],
    'pendiente_pago'     => ['Pendiente de pago',     'bg-orange-100 text-orange-800'],
    'pagado'             => ['Pagado',                'bg-green-100 text-green-800'],
    'entregado'          => ['Entregado',             'bg-gray-200 text-gray-800'],
    'cancelado'          => ['Cancelado',             'bg-red-100 text-red-700'],
];

/** Badge ▲/▼ con el % de variación. */
$badgeVariacion = function (?float $v): string {
    if ($v === null) {
        return '<span class="text-xs text-gray-400">—</span>';
    }
    $sube  = $v >= 0;
    $clase = $sube ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50';
    return '<span class="text-xs font-semibold px-2 py-0.5 rounded-full ' . $clase . '">'
         . ($sube ? '▲' : '▼') . ' ' . number_format(abs($v), 0, ',', '.') . '%</span>';
};

$maxGrafico = max(array_column($grafico, 'total') ?: [0]);

$totalAlertas = $alertasPedidos['pendientes_contacto'] + $alertasPedidos['pagos_vencidos']
              + $alertasCatalogo['variantes_sin_stock'] + $alertasCatalogo['productos_sin_foto']
              + $alertasCatalogo['productos_sin_costo'];
?>

<!-- ── Encabezado + selector de período ─────────────────────────── -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Dashboard</h2>
        <p class="text-gray-500">Cómo viene la tienda</p>
    </div>

    <div class="inline-flex bg-white border rounded-lg p-1 text-sm overflow-x-auto">
        <?php foreach ($periodos as $clave => $etiqueta): ?>
            <a href="<?= BASE_URL ?>/admin?periodo=<?= $clave ?>"
               class="px-3 py-1.5 rounded-md whitespace-nowrap <?= $periodo === $clave ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                <?= $etiqueta ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── Números principales ──────────────────────────────────────── -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Ventas</p>
            <?= $badgeVariacion($variaciones['ventas']) ?>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $pesos($resumen['ventas']) ?></p>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Ganancia</p>
            <?= $badgeVariacion($variaciones['ganancia']) ?>
        </div>
        <p class="text-2xl md:text-3xl font-bold <?= $resumen['ganancia'] < 0 ? 'text-red-600' : 'text-green-700' ?>">
            <?= $pesos($resumen['ganancia']) ?>
        </p>
        <p class="text-xs text-gray-400 mt-1">
            <?= $resumen['margen'] !== null ? number_format($resumen['margen'], 1, ',', '.') . '% de margen' : 'Sin costos cargados' ?>
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Pedidos concretados</p>
            <?= $badgeVariacion($variaciones['pedidos']) ?>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= (int) $resumen['pedidos'] ?></p>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Ticket promedio</p>
            <?= $badgeVariacion($variaciones['ticket_promedio']) ?>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $pesos($resumen['ticket_promedio']) ?></p>
    </div>

</div>

<?php if ($resumen['items_sin_costo'] > 0): ?>
    <p class="-mt-3 mb-6 text-xs text-gray-400">
        ⓘ La ganancia no incluye <?= (int) $resumen['items_sin_costo'] ?> producto(s) vendido(s) sin precio de costo cargado.
    </p>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <!-- ── Gráfico de ventas ────────────────────────────────────── -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow p-5">
        <h3 class="font-bold text-gray-800 mb-4"><?= htmlspecialchars($graficoTitulo) ?></h3>

        <?php if ($maxGrafico > 0): ?>
            <div class="flex items-end gap-1 h-48">
                <?php foreach ($grafico as $i => $d): ?>
                    <?php $alto = $d['total'] > 0 ? max(4, round($d['total'] / $maxGrafico * 100)) : 0; ?>
                    <div class="flex-1 h-full flex flex-col justify-end group relative">
                        <div class="w-full rounded-t <?= $d['total'] > 0 ? 'bg-gray-900 group-hover:bg-gray-700' : 'bg-gray-100' ?>"
                             style="height: <?= $d['total'] > 0 ? $alto : 2 ?>%"></div>

                        <!-- Tooltip -->
                        <div class="hidden group-hover:block absolute bottom-full left-1/2 -translate-x-1/2 mb-2 z-10
                                    bg-gray-900 text-white text-xs rounded px-2 py-1 whitespace-nowrap">
                            <?= $d['label'] ?>: <?= $pesos($d['total']) ?> · <?= (int) $d['pedidos'] ?> pedido(s)
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Etiquetas de días (espaciadas para que no se amontonen) -->
            <?php $paso = count($grafico) > 15 ? 5 : 1; ?>
            <div class="flex gap-1 mt-2">
                <?php foreach ($grafico as $i => $d): ?>
                    <div class="flex-1 text-center text-[10px] text-gray-400">
                        <?= ($i % $paso === 0) ? $d['label'] : '' ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="h-48 flex items-center justify-center text-gray-400 text-sm">
                Todavía no hay ventas en este período.
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Para hacer hoy ───────────────────────────────────────── -->
    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-bold text-gray-800 mb-4">Para hacer hoy</h3>

        <?php if ($totalAlertas === 0): ?>
            <p class="text-sm text-gray-500">✅ Todo al día. No hay nada pendiente.</p>
        <?php else: ?>
            <ul class="space-y-2 text-sm">
                <?php if ($alertasPedidos['pendientes_contacto'] > 0): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/pedidos?estado=pendiente_contacto" class="flex items-center justify-between p-3 rounded-lg bg-yellow-50 hover:bg-yellow-100">
                            <span>📞 Pedidos esperando contacto</span>
                            <strong><?= $alertasPedidos['pendientes_contacto'] ?></strong>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($alertasPedidos['pagos_vencidos'] > 0): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/pedidos?estado=vencidos" class="flex items-center justify-between p-3 rounded-lg bg-orange-50 hover:bg-orange-100">
                            <span>⏰ Sin pagar hace +3 días <span class="block text-xs text-orange-700">Tienen stock reservado</span></span>
                            <strong><?= $alertasPedidos['pagos_vencidos'] ?></strong>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($alertasCatalogo['variantes_sin_stock'] > 0): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/productos?estado=activos&problema=faltantes" class="flex items-center justify-between p-3 rounded-lg bg-red-50 hover:bg-red-100">
                            <span>📦 Variantes sin stock</span>
                            <strong><?= $alertasCatalogo['variantes_sin_stock'] ?></strong>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($alertasCatalogo['productos_sin_foto'] > 0): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/productos?estado=activos&problema=sin_foto" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 hover:bg-gray-100">
                            <span>🖼️ Productos sin foto</span>
                            <strong><?= $alertasCatalogo['productos_sin_foto'] ?></strong>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($alertasCatalogo['productos_sin_costo'] > 0): ?>
                    <li>
                       <a href="<?= BASE_URL ?>/admin/productos?estado=activos&problema=sin_costo" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 hover:bg-gray-100">
                            <span>💲 Productos sin costo cargado</span>
                            <strong><?= $alertasCatalogo['productos_sin_costo'] ?></strong>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </div>

</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <!-- ── Más vendidos ─────────────────────────────────────────── -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-5 py-4 border-b">
            <h3 class="font-bold text-gray-800">Más vendidos</h3>
        </div>

        <?php if (!empty($topProductos)): ?>
            <ul class="divide-y text-sm">
                <?php foreach ($topProductos as $pos => $t): ?>
                    <li class="px-5 py-3 flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full bg-gray-100 text-gray-600 text-xs font-bold flex items-center justify-center shrink-0">
                            <?= $pos + 1 ?>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate"><?= htmlspecialchars($t['producto']) ?></p>
                            <p class="text-xs text-gray-400">
                                <?= (int) $t['unidades'] ?> u. · <?= $pesos($t['ventas']) ?>
                            </p>
                        </div>
                        <?php if ($t['ganancia'] !== null): ?>
                            <span class="text-xs font-semibold text-green-700 whitespace-nowrap">+<?= $pesos($t['ganancia']) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="px-5 py-8 text-sm text-gray-400 text-center">Sin ventas en este período.</p>
        <?php endif; ?>
    </div>

    <!-- ── Últimos pedidos ──────────────────────────────────────── -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow overflow-hidden">
        <div class="px-5 py-4 border-b flex items-center justify-between">
            <h3 class="font-bold text-gray-800">Últimos pedidos</h3>
            <a href="<?= BASE_URL ?>/admin/pedidos" class="text-sm text-gray-500 hover:text-gray-900">Ver todos →</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left px-5 py-3">#</th>
                        <th class="text-left px-5 py-3">Cliente</th>
                        <th class="text-left px-5 py-3">Estado</th>
                        <th class="text-right px-5 py-3">Total</th>
                        <th class="text-right px-5 py-3">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($ultimosPedidos && $ultimosPedidos->num_rows > 0): ?>
                        <?php while ($p = $ultimosPedidos->fetch_assoc()): ?>
                            <?php [$estadoTexto, $estadoClase] = $estados[$p['estado']] ?? [$p['estado'], 'bg-gray-100 text-gray-700']; ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <a href="<?= BASE_URL ?>/admin/pedido/<?= (int) $p['id_pedido'] ?>" class="font-medium text-gray-900 hover:underline">
                                        #<?= (int) $p['id_pedido'] ?>
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-gray-700">
                                    <?= htmlspecialchars(trim(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? ''))) ?: '—' ?>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="px-2 py-1 rounded text-xs <?= $estadoClase ?>"><?= $estadoTexto ?></span>
                                </td>
                                <td class="px-5 py-3 text-right font-medium"><?= $pesos($p['total']) ?></td>
                                <td class="px-5 py-3 text-right text-gray-500 whitespace-nowrap">
                                    <?= date('d/m H:i', strtotime($p['fecha'])) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-gray-400">Todavía no hay pedidos.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ── Stock bajo ──────────────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-5 py-4 border-b">
        <h3 class="font-bold text-gray-800">Stock bajo <span class="text-sm font-normal text-gray-400">(3 o menos disponibles)</span></h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="text-left px-5 py-3">Producto</th>
                    <th class="text-left px-5 py-3">Talle</th>
                    <th class="text-left px-5 py-3">Color</th>
                    <th class="text-right px-5 py-3">Disponible</th>
                    <th class="text-right px-5 py-3">Reservado</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($stockBajo && $stockBajo->num_rows > 0): ?>
                    <?php while ($s = $stockBajo->fetch_assoc()): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-900"><?= htmlspecialchars($s['producto']) ?></td>
                            <td class="px-5 py-3"><?= htmlspecialchars($s['talle'] ?? '—') ?></td>
                            <td class="px-5 py-3"><?= htmlspecialchars($s['color'] ?? '—') ?></td>
                            <td class="px-5 py-3 text-right font-semibold <?= $s['stock_disponible'] <= 0 ? 'text-red-600' : 'text-orange-600' ?>">
                                <?= (int) $s['stock_disponible'] ?>
                            </td>
                            <td class="px-5 py-3 text-right text-gray-500"><?= (int) $s['stock_reservado'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-gray-400">✅ Todo con stock suficiente.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>