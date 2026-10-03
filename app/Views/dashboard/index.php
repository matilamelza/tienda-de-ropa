<?php
$pesos = fn($n) => '$' . number_format((float) $n, 0, ',', '.');

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

$maxGrafico = max(array_merge(array_column($grafico, 'total'), array_filter(array_column($grafico, 'anterior'))) ?: [0]);

$totalAlertas = $alertasPedidos['pendientes_contacto'] + $alertasPedidos['pagos_vencidos'] + $alertasPedidos['para_entregar']
              + $alertasCatalogo['variantes_sin_stock'] + $alertasCatalogo['productos_sin_foto']
              + $alertasCatalogo['productos_sin_costo'] + count($agotados) + $sinGuia;

$saldoTotal = array_sum(array_column($cajas, 'saldo'));

// Meta del mes
$diasMes   = (int) date('t');
$diaHoy    = (int) date('j');
$pctMeta   = $meta > 0 ? min(100, round($ventasMes / $meta * 100)) : 0;
$pctTiempo = round($diaHoy / $diasMes * 100);

// Canales
$nombresCanal = ['web' => '🌐 Tienda online'] + GestionPedido::ORIGENES;
$totalCanales = array_sum(array_column($canales, 'total')) ?: 1;

// Stock bajo agrupado por producto
$stockAgrupado = [];
if ($stockBajo) {
    while ($s = $stockBajo->fetch_assoc()) {
        $clave = $s['producto'];
        $stockAgrupado[$clave]['id']       = $s['id_producto'] ?? null;
        $stockAgrupado[$clave]['talles'][] = $s;
    }
}

/** WhatsApp al cliente */
$wa = function (array $p): ?string {
    $tel = preg_replace('/\D/', '', $p['telefono'] ?? '');
    if ($tel === '') return null;
    if (substr($tel, 0, 2) !== '54') $tel = '549' . ltrim($tel, '0');
    return 'https://wa.me/' . $tel . '?text=' . rawurlencode('Hola ' . ($p['nombre'] ?? '') . ', tu pedido #' . (int) $p['id_pedido'] . ' ya está listo 😊');
};
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

<!-- ── Hoy ──────────────────────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow px-5 py-4 mb-6 flex flex-wrap items-center gap-x-8 gap-y-3 text-sm">
    <span class="font-semibold text-gray-900">Hoy</span>
    <span><span class="text-gray-500">Pedidos</span> <strong class="ml-1"><?= (int) $hoy['pedidos'] ?></strong></span>
    <span><span class="text-gray-500">Vendido</span> <strong class="ml-1"><?= $pesos($hoy['ventas']) ?></strong></span>
    <span><span class="text-gray-500">Cobrado</span> <strong class="ml-1 text-green-700"><?= $pesos($hoy['cobrado']) ?></strong></span>
    <span><span class="text-gray-500">Visitas</span> <strong class="ml-1"><?= number_format($hoy['visitas'], 0, ',', '.') ?></strong></span>
</div>

<!-- ── Meta del mes ─────────────────────────────────────────────── -->
<?php if ($meta > 0): ?>
    <div class="bg-white rounded-lg shadow p-5 mb-6">
        <div class="flex flex-wrap items-end justify-between gap-2 mb-3">
            <div>
                <p class="text-sm text-gray-500">Meta de <?= strtolower(['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'][(int) date('n')]) ?></p>
                <p class="text-xl font-bold text-gray-900">
                    <?= $pesos($ventasMes) ?> <span class="text-sm font-normal text-gray-400">de <?= $pesos($meta) ?></span>
                </p>
            </div>
            <p class="text-sm <?= $pctMeta >= $pctTiempo ? 'text-green-700' : 'text-gray-500' ?>">
                <?php if ($pctMeta >= 100): ?>
                    🎉 ¡Meta cumplida!
                <?php else: ?>
                    Faltan <strong><?= $pesos($meta - $ventasMes) ?></strong> · quedan <?= $diasMes - $diaHoy ?> día(s)
                    <?= $pctMeta >= $pctTiempo ? '· vas bien' : '' ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="relative h-3 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full <?= $pctMeta >= 100 ? 'bg-green-600' : 'bg-gray-900' ?>" style="width: <?= $pctMeta ?>%"></div>
            <div class="absolute top-0 h-full w-0.5 bg-gray-400" style="left: <?= $pctTiempo ?>%" title="Día <?= $diaHoy ?> de <?= $diasMes ?>"></div>
        </div>
        <div class="flex justify-between mt-1 text-xs text-gray-400">
            <span><?= $pctMeta ?>% de la meta</span>
            <details class="relative">
                <summary class="cursor-pointer hover:text-gray-700 list-none">Cambiar meta</summary>
                <form method="POST" action="<?= BASE_URL ?>/admin/meta" class="absolute right-0 mt-2 z-10 bg-white border rounded-lg shadow-lg p-3 flex gap-2">
                    <?= csrf_field() ?>
                    <input type="number" name="meta" step="1000" min="0" value="<?= (int) $meta ?>" class="w-36 border rounded-lg px-2 py-1.5 text-sm text-gray-900">
                    <button class="px-3 py-1.5 rounded-lg bg-gray-900 text-white text-sm">Guardar</button>
                </form>
            </details>
        </div>
        <p class="text-[11px] text-gray-400 mt-1">La rayita gris marca cuánto del mes ya pasó: si la barra la supera, venís adelantada.</p>
    </div>
<?php else: ?>
    <details class="mb-6 text-sm">
        <summary class="cursor-pointer text-gray-500 hover:text-gray-900 list-none">🎯 Ponerte una meta de ventas para el mes</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/meta" class="mt-2 flex gap-2">
            <?= csrf_field() ?>
            <input type="number" name="meta" step="1000" min="0" placeholder="Ej: 3000000" class="w-44 border rounded-lg px-3 py-2">
            <button class="px-4 py-2 rounded-lg bg-gray-900 text-white">Guardar meta</button>
        </form>
    </details>
<?php endif; ?>

<!-- ── Números principales ──────────────────────────────────────── -->
<div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">

    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Ventas</p>
            <?= $badgeVariacion($variaciones['ventas']) ?>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $pesos($resumen['ventas']) ?></p>
        <p class="text-xs text-gray-400 mt-1">pedidos confirmados, listos o entregados</p>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Cobrado</p>
            <?= $badgeVariacion($variaciones['cobrado']) ?>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-green-700"><?= $pesos($resumen['cobrado']) ?></p>
        <p class="text-xs text-gray-400 mt-1">lo que entró de verdad a la caja</p>
    </div>

    <a href="<?= BASE_URL ?>/admin/pedidos?pago=debe&orden=deuda" class="bg-white rounded-lg shadow p-5 hover:ring-2 hover:ring-gray-200 transition">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Falta cobrar</p>
            <span class="text-gray-400 text-sm">→</span>
        </div>
        <p class="text-2xl md:text-3xl font-bold <?= $deuda['falta_cobrar'] > 0 ? 'text-red-600' : 'text-gray-300' ?>"><?= $pesos($deuda['falta_cobrar']) ?></p>
        <p class="text-xs text-gray-400 mt-1">en <?= (int) $deuda['con_deuda'] ?> pedido(s) confirmado(s), de cualquier fecha</p>
    </a>

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
            <p class="text-sm text-gray-500" title="Confirmados, listos o entregados">Pedidos concretados</p>
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

<!-- ── La plata ─────────────────────────────────────────────────── -->
<?php if (!empty($cajas)): ?>
    <a href="<?= BASE_URL ?>/admin/caja" class="flex flex-wrap items-center gap-x-6 gap-y-2 bg-white rounded-lg shadow px-5 py-4 mb-6 hover:bg-gray-50 text-sm">
        <span class="flex items-center gap-2">
            <span class="text-2xl">💰</span>
            <span>
                <span class="block text-gray-500 text-xs">Plata hoy</span>
                <strong class="text-lg text-gray-900"><?= $pesos($saldoTotal) ?></strong>
            </span>
        </span>
        <?php foreach ($cajas as $c): ?>
            <span>
                <span class="block text-gray-500 text-xs"><?= htmlspecialchars($c['nombre']) ?></span>
                <strong class="<?= $c['saldo'] < 0 ? 'text-red-600' : 'text-gray-900' ?>"><?= $pesos($c['saldo']) ?></strong>
            </span>
        <?php endforeach; ?>
        <span class="ml-auto text-gray-400">→</span>
    </a>
<?php endif; ?>

<!-- Visitantes → Estadísticas -->
<a href="<?= BASE_URL ?>/admin/estadisticas?periodo=<?= $periodo ?>"
   class="flex items-center justify-between bg-white rounded-lg shadow px-5 py-4 mb-6 hover:bg-gray-50">
    <div class="flex items-center gap-3">
        <span class="text-2xl">👥</span>
        <div>
            <p class="font-semibold text-gray-900">
                <?= number_format($visitas['visitantes'], 0, ',', '.') ?> visitantes
                <span class="text-sm font-normal text-gray-500">· <?= number_format($visitas['vistas'], 0, ',', '.') ?> páginas vistas</span>
            </p>
            <p class="text-xs text-gray-400">Ver qué miran, qué buscan y de dónde llegan</p>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <?= $badgeVariacion($variaciones['visitantes']) ?>
        <span class="text-gray-400">→</span>
    </div>
</a>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <!-- ── Gráfico de ventas ────────────────────────────────────── -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-800"><?= htmlspecialchars($graficoTitulo) ?></h3>
            <?php if ($hayAnterior): ?>
                <span class="flex items-center gap-3 text-xs text-gray-400">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-gray-900"></span> Este período</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-0.5 bg-orange-400"></span> Anterior</span>
                </span>
            <?php endif; ?>
        </div>

        <?php if ($maxGrafico > 0): ?>
            <div class="flex items-end gap-1 h-48">
                <?php foreach ($grafico as $i => $d): ?>
                    <?php
                    $alto    = $d['total'] > 0 ? max(4, round($d['total'] / $maxGrafico * 100)) : 0;
                    $altoAnt = $d['anterior'] !== null && $d['anterior'] > 0 ? round($d['anterior'] / $maxGrafico * 100) : null;
                    ?>
                    <div class="flex-1 h-full flex flex-col justify-end group relative">
                        <div class="w-full rounded-t <?= $d['total'] > 0 ? 'bg-gray-900 group-hover:bg-gray-700' : 'bg-gray-100' ?>"
                             style="height: <?= $d['total'] > 0 ? $alto : 2 ?>%"></div>

                        <?php if ($altoAnt !== null): ?>
                            <div class="absolute inset-x-0 h-0.5 bg-orange-400 pointer-events-none" style="bottom: <?= $altoAnt ?>%"></div>
                        <?php endif; ?>

                        <!-- Tooltip -->
                        <div class="hidden group-hover:block absolute bottom-full left-1/2 -translate-x-1/2 mb-2 z-10
                                    bg-gray-900 text-white text-xs rounded px-2 py-1 whitespace-nowrap">
                            <strong><?= $d['label'] ?></strong><br>
                            Vendido <?= $pesos($d['total']) ?> · <?= (int) $d['pedidos'] ?> pedido(s)<br>
                            Cobrado <?= $pesos($d['cobrado']) ?>
                            <?php if ($d['anterior'] !== null): ?><br>Período anterior <?= $pesos($d['anterior']) ?><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php $paso = count($grafico) > 15 ? 5 : 1; ?>
            <div class="flex gap-1 mt-2">
                <?php foreach ($grafico as $i => $d): ?>
                    <div class="flex-1 text-center text-[10px] text-gray-400"><?= ($i % $paso === 0) ? $d['label'] : '' ?></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="h-48 flex items-center justify-center text-gray-400 text-sm">Todavía no hay ventas en este período.</div>
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

                <?php if ($alertasPedidos['para_entregar'] > 0): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/pedidos?estado=listo" class="flex items-center justify-between p-3 rounded-lg bg-amber-50 hover:bg-amber-100">
                            <span>📦 Listos para entregar</span>
                            <strong><?= $alertasPedidos['para_entregar'] ?></strong>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($alertasPedidos['pagos_vencidos'] > 0): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/pedidos?estado=vencidos" class="flex items-center justify-between p-3 rounded-lg bg-orange-50 hover:bg-orange-100">
                            <span>⏰ Sin pagar hace +<?= Pedido::DIAS_PAGO_VENCIDO ?> días <span class="block text-xs text-orange-700">Confirmados con saldo pendiente</span></span>
                            <strong><?= $alertasPedidos['pagos_vencidos'] ?></strong>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (count($agotados) > 0): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/estadisticas?tab=productos&periodo=30d" class="flex items-center justify-between p-3 rounded-lg bg-red-50 hover:bg-red-100">
                            <span>🔎 Talles agotados que buscan <span class="block text-xs text-red-700">Para reponer · últimos 30 días</span></span>
                            <strong><?= count($agotados) ?></strong>
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

                <?php if ($sinGuia > 0): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/productos?estado=activos&problema=sin_guia" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 hover:bg-gray-100">
                            <span>📏 Productos sin guía de talles</span>
                            <strong><?= $sinGuia ?></strong>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <!-- ── Para entregar ────────────────────────────────────────── -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-5 py-4 border-b flex items-center justify-between">
            <h3 class="font-bold text-gray-800">Para entregar</h3>
            <a href="<?= BASE_URL ?>/admin/pedidos?estado=listo" class="text-sm text-gray-500 hover:text-gray-900">Ver →</a>
        </div>
        <?php if (empty($paraEntregar)): ?>
            <p class="px-5 py-8 text-sm text-gray-400 text-center">No hay pedidos listos esperando.</p>
        <?php else: ?>
            <ul class="divide-y text-sm">
                <?php foreach ($paraEntregar as $p): ?>
                    <?php
                    $falta  = max(0, (float) $p['total'] - (float) $p['cobrado']);
                    $nombre = trim(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? '')) ?: 'Sin nombre';
                    $link   = $wa($p);
                    ?>
                    <li class="px-5 py-3 flex items-center gap-3">
                        <a href="<?= BASE_URL ?>/admin/pedido/<?= (int) $p['id_pedido'] ?>" class="flex-1 min-w-0 hover:underline">
                            <span class="block font-medium text-gray-900 truncate">#<?= (int) $p['id_pedido'] ?> · <?= htmlspecialchars($nombre) ?></span>
                            <span class="block text-xs <?= $falta > 0.009 ? 'text-red-600' : 'text-gray-400' ?>">
                                <?= $falta > 0.009 ? 'Falta cobrar ' . $pesos($falta) : 'Pagado' ?>
                                <?= $p['entrega'] === 'envio' ? ' · envío' : ($p['entrega'] === 'retiro' ? ' · retira' : '') ?>
                            </span>
                        </a>
                        <?php if ($link): ?>
                            <a href="<?= htmlspecialchars($link) ?>" target="_blank" rel="noopener" class="text-green-600 hover:text-green-700" title="Avisarle por WhatsApp">💬</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- ── Por dónde vende ──────────────────────────────────────── -->
    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-bold text-gray-800 mb-4">Por dónde vendés</h3>
        <?php if (empty($canales)): ?>
            <p class="text-sm text-gray-400 py-4 text-center">Sin ventas en este período.</p>
        <?php else: ?>
            <ul class="space-y-3">
                <?php foreach ($canales as $c): ?>
                    <?php $pct = round($c['total'] / $totalCanales * 100); ?>
                    <li>
                        <div class="flex justify-between text-sm mb-1 gap-3">
                            <span class="text-gray-800"><?= $nombresCanal[$c['origen']] ?? htmlspecialchars($c['origen']) ?></span>
                            <span class="text-gray-500 whitespace-nowrap"><?= $pesos($c['total']) ?> · <?= $pct ?>%</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gray-900 rounded-full" style="width: <?= $pct ?>%"></div>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-0.5"><?= (int) $c['pedidos'] ?> pedido(s)</p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- ── Más vendidos ─────────────────────────────────────────── -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-5 py-4 border-b">
            <h3 class="font-bold text-gray-800">Más vendidos</h3>
        </div>

        <?php if (!empty($topProductos)): ?>
            <ul class="divide-y text-sm">
                <?php foreach ($topProductos as $pos => $t): ?>
                    <?php $margen = $t['ganancia'] !== null && (float) $t['ventas'] > 0 ? (float) $t['ganancia'] / (float) $t['ventas'] * 100 : null; ?>
                    <li class="px-5 py-3 flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full bg-gray-100 text-gray-600 text-xs font-bold flex items-center justify-center shrink-0"><?= $pos + 1 ?></span>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate"><?= htmlspecialchars($t['producto']) ?></p>
                            <p class="text-xs text-gray-400"><?= (int) $t['unidades'] ?> u. · <?= $pesos($t['ventas']) ?></p>
                        </div>
                        <?php if ($t['ganancia'] !== null): ?>
                            <span class="text-right whitespace-nowrap">
                                <span class="block text-xs font-semibold text-green-700">+<?= $pesos($t['ganancia']) ?></span>
                                <?php if ($margen !== null): ?><span class="block text-[11px] text-gray-400"><?= number_format($margen, 0, ',', '.') ?>% margen</span><?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="px-5 py-8 text-sm text-gray-400 text-center">Sin ventas en este período.</p>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

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
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <a href="<?= BASE_URL ?>/admin/pedido/<?= (int) $p['id_pedido'] ?>" class="font-medium text-gray-900 hover:underline">#<?= (int) $p['id_pedido'] ?></a>
                                    <?php if (($p['origen'] ?? 'web') !== 'web'): ?>
                                        <span class="block text-[11px] text-gray-400"><?= strip_tags(GestionPedido::ORIGENES[$p['origen']] ?? $p['origen']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3 text-gray-700"><?= htmlspecialchars(trim(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? ''))) ?: '—' ?></td>
                                <td class="px-5 py-3">
                                    <span class="px-2 py-1 rounded text-xs <?= GestionPedido::clase($p['estado']) ?>"><?= GestionPedido::etiqueta($p['estado']) ?></span>
                                </td>
                                <td class="px-5 py-3 text-right font-medium"><?= $pesos($p['total']) ?></td>
                                <td class="px-5 py-3 text-right text-gray-500 whitespace-nowrap"><?= date('d/m H:i', strtotime($p['fecha'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">Todavía no hay pedidos.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Agotados que buscan ──────────────────────────────────── -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-5 py-4 border-b">
            <h3 class="font-bold text-gray-800">Agotados que buscan</h3>
            <p class="text-xs text-gray-400">Talles sin stock que intentaron elegir (30 días)</p>
        </div>
        <?php if (empty($agotados)): ?>
            <p class="px-5 py-8 text-sm text-gray-400 text-center">✅ Nadie buscó talles agotados.</p>
        <?php else: ?>
            <ul class="divide-y text-sm">
                <?php foreach ($agotados as $a): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/productos/variantes?id=<?= (int) $a['id_producto'] ?>" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-gray-50">
                            <span class="min-w-0 truncate">
                                <?= htmlspecialchars($a['nombre']) ?>
                                <span class="ml-1 px-1.5 py-0.5 rounded bg-red-50 text-red-700 text-xs"><?= htmlspecialchars($a['talle']) ?></span>
                            </span>
                            <span class="shrink-0 text-xs text-gray-500"><?= (int) $a['personas'] ?> persona(s)</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- ── Stock bajo (agrupado por producto) ──────────────────────── -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-5 py-4 border-b">
        <h3 class="font-bold text-gray-800">Stock bajo <span class="text-sm font-normal text-gray-400">(3 o menos disponibles)</span></h3>
    </div>

    <?php if (empty($stockAgrupado)): ?>
        <p class="px-5 py-8 text-center text-gray-400 text-sm">✅ Todo con stock suficiente.</p>
    <?php else: ?>
        <ul class="divide-y text-sm">
            <?php foreach ($stockAgrupado as $nombre => $s): ?>
                <li class="px-5 py-3 flex flex-col sm:flex-row sm:items-center gap-2">
                    <span class="sm:w-1/3 font-medium text-gray-900 truncate">
                        <?php if ($s['id']): ?>
                            <a href="<?= BASE_URL ?>/admin/productos/variantes?id=<?= (int) $s['id'] ?>" class="hover:underline"><?= htmlspecialchars($nombre) ?></a>
                        <?php else: ?>
                            <?= htmlspecialchars($nombre) ?>
                        <?php endif; ?>
                    </span>
                    <span class="flex-1 flex flex-wrap gap-1.5">
                        <?php foreach ($s['talles'] as $t): ?>
                            <?php $disp = (int) $t['stock_disponible']; ?>
                            <span class="px-2 py-0.5 rounded text-xs <?= $disp <= 0 ? 'bg-red-50 text-red-600' : 'bg-orange-50 text-orange-700' ?>"
                                  title="<?= $disp ?> disponible(s)<?= (int) $t['stock_reservado'] ? ' · ' . (int) $t['stock_reservado'] . ' reservado(s)' : '' ?>">
                                <?= htmlspecialchars(variante_texto($t['talle'], $t['color']) ?: '—') ?> · <?= $disp ?>
                            </span>
                        <?php endforeach; ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>