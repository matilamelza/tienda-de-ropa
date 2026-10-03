<?php
$msg = $_SESSION['caja_msg'] ?? null;
unset($_SESSION['caja_msg']);

$pesos  = fn($n) => '$' . number_format((float) $n, 2, ',', '.');
$pesos0 = fn($n) => '$' . number_format((float) $n, 0, ',', '.');
$input  = 'w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-200';

$saldoTotal = array_sum(array_column($cajas, 'saldo'));
$t          = $totales;

/** URL conservando período y filtros */
$url = function (array $cambios = []) use ($periodo, $desde, $hasta, $filtros): string {
    $q = [
        'periodo'  => $periodo === 'personalizado' ? null : $periodo,
        'desde'    => $periodo === 'personalizado' ? $desde : null,
        'hasta'    => $periodo === 'personalizado' ? $hasta : null,
        'caja'     => $filtros['caja'] ?: null,
        'tipo'     => $filtros['tipo'] ?: null,
        'q'        => $filtros['q'] ?: null,
        'anulados' => $filtros['anulados'] ? 1 : null,
    ];
    $q = array_filter(array_merge($q, $cambios), fn($v) => $v !== null && $v !== '');
    return BASE_URL . '/admin/caja' . ($q ? '?' . http_build_query($q) : '');
};

$periodos = ['hoy' => 'Hoy', 'semana' => 'Esta semana', 'mes' => 'Este mes', 'mes_pasado' => 'Mes pasado'];

$tipos = [
    'cobro'                 => ['💵', 'bg-green-100 text-green-800'],
    'ingreso'               => ['➕', 'bg-green-100 text-green-800'],
    'gasto'                 => ['➖', 'bg-red-100 text-red-700'],
    'devolucion'            => ['↩️', 'bg-red-100 text-red-700'],
    'pago_proveedor'        => ['📦', 'bg-orange-100 text-orange-800'],
    'transferencia_salida'  => ['🔁', 'bg-blue-100 text-blue-800'],
    'transferencia_entrada' => ['🔁', 'bg-blue-100 text-blue-800'],
    'ajuste'                => ['⚖️', 'bg-gray-200 text-gray-700'],
];

/** "Hoy", "Ayer" o "Lunes 28/09" */
$dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$nombreDia = function (string $fecha) use ($dias): string {
    if ($fecha === date('Y-m-d')) return 'Hoy';
    if ($fecha === date('Y-m-d', strtotime('-1 day'))) return 'Ayer';
    $ts = strtotime($fecha);
    return $dias[(int) date('w', $ts)] . ' ' . date('d/m', $ts) . (date('Y', $ts) !== date('Y') ? '/' . date('Y', $ts) : '');
};

// Movimientos agrupados por día
$porDia = [];
foreach ($movimientos as $m) {
    $porDia[substr($m['fecha'], 0, 10)][] = $m;
}

$maxCat   = max(array_column($porCategoria, 'total') ?: [0]) ?: 1;
$maxMedio = max(array_column($porMedio, 'total') ?: [0]) ?: 1;
$hayFiltrosMov = $filtros['caja'] || $filtros['tipo'] || $filtros['q'] || $filtros['anulados'];
?>

<!-- ══ ENCABEZADO ══════════════════════════════════════════════════════ -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Caja</h2>
        <p class="text-gray-500 text-sm">Cuánta plata hay, dónde está y en qué se mueve.</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= BASE_URL ?>/admin/caja/config" class="px-3 py-2 rounded-lg border bg-white text-gray-700 text-sm hover:bg-gray-50">⚙️ Configurar</a>
        <button type="button" onclick="abrirMovimiento('gasto')" class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">+ Movimiento</button>
    </div>
</div>

<?php if ($msg): ?>
    <div class="mb-6 px-4 py-3 rounded-xl text-sm border
        <?= $msg['tipo'] === 'ok' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
        <?= htmlspecialchars($msg['texto']) ?>
    </div>
<?php endif; ?>

<!-- ══ SALDOS ══════════════════════════════════════════════════════════ -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-gray-900 text-white rounded-xl shadow p-5 flex flex-col justify-between">
        <p class="text-sm text-gray-400">Plata total hoy</p>
        <p class="text-3xl font-bold mt-1 <?= $saldoTotal < 0 ? 'text-red-300' : '' ?>"><?= $pesos($saldoTotal) ?></p>
        <p class="text-xs text-gray-400 mt-2">En <?= count($cajas) ?> caja<?= count($cajas) !== 1 ? 's' : '' ?></p>
    </div>

    <div class="lg:col-span-3 grid grid-cols-2 md:grid-cols-3 gap-3">
        <?php foreach ($cajas as $c): ?>
            <?php
            $activa = $filtros['caja'] === (int) $c['id_caja'];
            $parte  = $saldoTotal > 0 && $c['saldo'] > 0 ? round($c['saldo'] / $saldoTotal * 100) : 0;
            ?>
            <a href="<?= htmlspecialchars($url(['caja' => $activa ? null : (int) $c['id_caja'], 'pagina' => null])) ?>"
               class="bg-white rounded-xl shadow p-4 hover:ring-2 hover:ring-gray-200 transition <?= $activa ? 'ring-2 ring-gray-900' : '' ?>">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm text-gray-500 truncate"><?= htmlspecialchars($c['nombre']) ?></p>
                    <?php if ($activa): ?><span class="text-xs text-gray-400">✕</span><?php endif; ?>
                </div>
                <p class="text-xl font-bold mt-1 <?= $c['saldo'] < 0 ? 'text-red-600' : 'text-gray-900' ?>"><?= $pesos($c['saldo']) ?></p>
                <div class="h-1.5 bg-gray-100 rounded-full mt-2 overflow-hidden">
                    <div class="h-full bg-gray-900 rounded-full" style="width: <?= $parte ?>%"></div>
                </div>
                <p class="text-[11px] text-gray-400 mt-1"><?= $parte ?>% del total</p>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- ══ PERÍODO ═════════════════════════════════════════════════════════ -->
<div class="bg-white rounded-xl shadow p-3 mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div class="flex flex-wrap gap-1 text-sm">
        <?php foreach ($periodos as $clave => $txt): ?>
            <a href="<?= htmlspecialchars($url(['periodo' => $clave, 'desde' => null, 'hasta' => null, 'pagina' => null])) ?>"
               class="px-3 py-1.5 rounded-md whitespace-nowrap <?= $periodo === $clave ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                <?= $txt ?>
            </a>
        <?php endforeach; ?>
    </div>

    <form method="GET" action="<?= BASE_URL ?>/admin/caja" class="flex items-center gap-2 text-sm">
        <?php foreach (['caja', 'tipo', 'q'] as $k): ?>
            <?php if (!empty($filtros[$k])): ?><input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars((string) $filtros[$k]) ?>"><?php endif; ?>
        <?php endforeach; ?>
        <input type="date" name="desde" value="<?= $desde ?>" class="border rounded-lg px-2 py-1.5">
        <span class="text-gray-400">a</span>
        <input type="date" name="hasta" value="<?= $hasta ?>" max="<?= date('Y-m-d') ?>" class="border rounded-lg px-2 py-1.5">
        <button class="px-3 py-1.5 rounded-lg <?= $periodo === 'personalizado' ? 'bg-gray-900 text-white' : 'border hover:bg-gray-50' ?>">Ver</button>
    </form>
</div>

<!-- ══ RESUMEN DEL PERÍODO ═════════════════════════════════════════════ -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Entró</p>
        <p class="text-2xl font-bold text-green-700 mt-1"><?= $pesos0($t['entradas']) ?></p>
        <div class="text-xs text-gray-400 mt-2 space-y-0.5">
            <p>💵 Cobros <?= $pesos0($t['cobros_netos']) ?><?= $t['pedidos_cobrados'] ? ' · ' . (int) $t['pedidos_cobrados'] . ' pedido(s)' : '' ?></p>
            <?php if ($t['ingresos'] > 0): ?><p>➕ Otros ingresos <?= $pesos0($t['ingresos']) ?></p><?php endif; ?>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Salió</p>
        <p class="text-2xl font-bold text-red-600 mt-1"><?= $pesos0($t['salidas']) ?></p>
        <div class="text-xs text-gray-400 mt-2 space-y-0.5">
            <?php if ($t['gastos'] > 0): ?><p>➖ Gastos <?= $pesos0($t['gastos']) ?></p><?php endif; ?>
            <?php if ($t['devoluciones'] > 0): ?><p>↩️ Devoluciones <?= $pesos0($t['devoluciones']) ?></p><?php endif; ?>
            <?php if ($t['proveedores'] > 0): ?><p>📦 Proveedores <?= $pesos0($t['proveedores']) ?></p><?php endif; ?>
            <?php if ($t['salidas'] == 0): ?><p>Nada en este período</p><?php endif; ?>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-sm text-gray-500">Comisiones</p>
        <p class="text-2xl font-bold text-orange-600 mt-1"><?= $pesos0($t['comisiones']) ?></p>
        <p class="text-xs text-gray-400 mt-2">
            <?= $t['cobros'] > 0 ? number_format($t['comisiones'] / $t['cobros'] * 100, 1, ',', '.') . '% de lo cobrado' : 'Sin cobros' ?>
        </p>
    </div>
    <div class="rounded-xl shadow p-5 <?= $t['resultado'] < 0 ? 'bg-red-50' : 'bg-green-50' ?>">
        <p class="text-sm text-gray-600">Resultado</p>
        <p class="text-2xl font-bold mt-1 <?= $t['resultado'] < 0 ? 'text-red-700' : 'text-green-800' ?>">
            <?= $t['resultado'] < 0 ? '−' : '' ?><?= $pesos0(abs($t['resultado'])) ?>
        </p>
        <p class="text-xs text-gray-500 mt-2">Entró − salió (sin transferencias ni ajustes)</p>
    </div>
</div>

<!-- ══ EN QUÉ SE FUE / CÓMO TE PAGARON ═════════════════════════════════ -->
<?php if ($porCategoria || $porMedio): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-bold text-gray-800 mb-4">¿En qué se fue la plata?</h3>
        <?php if (empty($porCategoria)): ?>
            <p class="text-sm text-gray-400">Sin gastos en este período.</p>
        <?php else: ?>
            <ul class="space-y-3">
                <?php foreach ($porCategoria as $c): ?>
                    <li>
                        <div class="flex justify-between text-sm mb-1 gap-3">
                            <span class="truncate text-gray-800"><?= htmlspecialchars($c['nombre']) ?> <span class="text-xs text-gray-400">(<?= (int) $c['cantidad'] ?>)</span></span>
                            <span class="shrink-0 font-medium"><?= $pesos0($c['total']) ?></span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-red-400 rounded-full" style="width: <?= round($c['total'] / $maxCat * 100) ?>%"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="font-bold text-gray-800 mb-4">¿Cómo te pagaron?</h3>
        <?php if (empty($porMedio)): ?>
            <p class="text-sm text-gray-400">Sin cobros en este período.</p>
        <?php else: ?>
            <ul class="space-y-3">
                <?php foreach ($porMedio as $m): ?>
                    <li>
                        <div class="flex justify-between text-sm mb-1 gap-3">
                            <span class="truncate text-gray-800"><?= htmlspecialchars($m['nombre']) ?> <span class="text-xs text-gray-400">(<?= (int) $m['cantidad'] ?>)</span></span>
                            <span class="shrink-0 font-medium">
                                <?= $pesos0($m['total']) ?>
                                <?php if ((float) $m['comision'] > 0): ?>
                                    <span class="text-xs font-normal text-orange-600">−<?= $pesos0($m['comision']) ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500 rounded-full" style="width: <?= round($m['total'] / $maxMedio * 100) ?>%"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ══ MOVIMIENTOS ═════════════════════════════════════════════════════ -->
<div class="bg-white rounded-xl shadow overflow-hidden">

    <!-- Filtros -->
    <form method="GET" action="<?= BASE_URL ?>/admin/caja" class="p-3 border-b bg-gray-50 flex flex-wrap items-center gap-2 text-sm">
        <?php if ($periodo === 'personalizado'): ?>
            <input type="hidden" name="desde" value="<?= $desde ?>"><input type="hidden" name="hasta" value="<?= $hasta ?>">
        <?php else: ?>
            <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo) ?>">
        <?php endif; ?>

        <h3 class="font-bold text-gray-800 mr-auto">Movimientos <span class="font-normal text-gray-400">(<?= (int) $total ?>)</span></h3>

        <input type="search" name="q" value="<?= htmlspecialchars($filtros['q']) ?>" placeholder="Buscar… o #pedido"
               class="border rounded-lg px-3 py-1.5 w-40 md:w-48 bg-white">
        <select name="caja" onchange="this.form.submit()" class="border rounded-lg px-2 py-1.5 bg-white">
            <option value="">Todas las cajas</option>
            <?php foreach ($cajas as $c): ?>
                <option value="<?= (int) $c['id_caja'] ?>" <?= $filtros['caja'] === (int) $c['id_caja'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="tipo" onchange="this.form.submit()" class="border rounded-lg px-2 py-1.5 bg-white">
            <option value="">Todos los tipos</option>
            <?php foreach (Movimiento::TIPOS as $clave => $txt): ?>
                <option value="<?= $clave ?>" <?= $filtros['tipo'] === $clave ? 'selected' : '' ?>><?= $txt ?></option>
            <?php endforeach; ?>
        </select>
        <label class="flex items-center gap-1.5 text-gray-600">
            <input type="checkbox" name="anulados" value="1" onchange="this.form.submit()" <?= $filtros['anulados'] ? 'checked' : '' ?>> Anulados
        </label>
        <?php if ($hayFiltrosMov): ?>
            <a href="<?= htmlspecialchars($url(['caja' => null, 'tipo' => null, 'q' => null, 'anulados' => null, 'pagina' => null])) ?>"
               class="text-gray-400 hover:text-red-600 px-1" title="Limpiar filtros">✕</a>
        <?php endif; ?>
    </form>

    <?php if (empty($movimientos)): ?>
        <div class="px-4 py-12 text-center">
            <p class="text-3xl mb-2">🗒️</p>
            <p class="text-gray-500 text-sm"><?= $hayFiltrosMov ? 'No hay movimientos con estos filtros.' : 'No hay movimientos en este período.' ?></p>
            <button type="button" onclick="abrirMovimiento('gasto')" class="mt-3 text-sm text-blue-600 hover:underline">+ Cargar un movimiento</button>
        </div>
    <?php else: ?>
        <?php foreach ($porDia as $dia => $lista): ?>
            <?php $netoDia = array_sum(array_map(fn($m) => $m['anulado_at'] === null ? (float) $m['neto_caja'] : 0, $lista)); ?>
            <div class="px-4 py-2 bg-gray-50 border-y flex items-center justify-between text-xs">
                <span class="font-semibold text-gray-600 uppercase tracking-wide"><?= $nombreDia($dia) ?></span>
                <span class="font-semibold <?= $netoDia < 0 ? 'text-red-600' : 'text-green-700' ?>"><?= $netoDia < 0 ? '−' : '+' ?><?= $pesos(abs($netoDia)) ?></span>
            </div>

            <ul class="divide-y">
                <?php foreach ($lista as $m): ?>
                    <?php
                    $anulado = $m['anulado_at'] !== null;
                    [$icono, $clsTipo] = $tipos[$m['tipo']] ?? ['•', 'bg-gray-100'];
                    $detalle = $m['concepto'] ?: ($m['categoria'] ?? '');

                    if (in_array($m['tipo'], ['transferencia_salida', 'transferencia_entrada'], true) && $m['caja_relacionada']) {
                        $detalle = ($m['tipo'] === 'transferencia_salida' ? 'A ' : 'Desde ') . $m['caja_relacionada']
                                 . ($m['concepto'] ? ' · ' . $m['concepto'] : '');
                    }
                    ?>
                    <li class="px-4 py-3 flex items-center gap-3 <?= $anulado ? 'opacity-50' : '' ?>">
                        <span class="w-9 h-9 shrink-0 rounded-full flex items-center justify-center text-base <?= $clsTipo ?>"><?= $icono ?></span>

                        <div class="flex-1 min-w-0 <?= $anulado ? 'line-through' : '' ?>">
                            <p class="text-sm text-gray-900 truncate">
                                <?= htmlspecialchars($detalle ?: (Movimiento::TIPOS[$m['tipo']] ?? $m['tipo'])) ?>
                                <?php if ($m['id_pedido']): ?>
                                    <a href="<?= BASE_URL ?>/admin/pedido/<?= (int) $m['id_pedido'] ?>" class="text-blue-600 hover:underline no-underline">#<?= (int) $m['id_pedido'] ?></a>
                                <?php endif; ?>
                            </p>
                            <p class="text-xs text-gray-400 truncate">
                                <?= date('H:i', strtotime($m['fecha'])) ?>
                                · <?= Movimiento::TIPOS[$m['tipo']] ?? $m['tipo'] ?>
                                · <?= htmlspecialchars($m['caja']) ?>
                                <?= $m['medio'] ? ' · ' . htmlspecialchars($m['medio']) : '' ?>
                                <?= $m['categoria'] && $m['concepto'] ? ' · ' . htmlspecialchars($m['categoria']) : '' ?>
                                <?= !empty($m['comprobante']) ? ' · ' . htmlspecialchars($m['comprobante']) : '' ?>
                            </p>
                        </div>

                        <div class="text-right shrink-0">
                            <p class="font-semibold <?= $m['neto_caja'] < 0 ? 'text-red-600' : 'text-green-700' ?> <?= $anulado ? 'line-through' : '' ?>">
                                <?= $m['neto_caja'] < 0 ? '−' : '+' ?><?= $pesos(abs($m['neto_caja'])) ?>
                            </p>
                            <?php if ($m['comision'] > 0): ?>
                                <p class="text-[11px] text-orange-600"><?= $pesos0($m['monto']) ?> − <?= $pesos0($m['comision']) ?> com.</p>
                            <?php endif; ?>
                        </div>

                        <div class="w-16 text-right shrink-0">
                            <?php if ($anulado): ?>
                                <span class="text-xs text-gray-400">Anulado</span>
                            <?php elseif ($m['id_pedido']): ?>
                                <a href="<?= BASE_URL ?>/admin/pedido/<?= (int) $m['id_pedido'] ?>" class="text-xs text-gray-400 hover:text-gray-900"
                                   title="Los cobros se anulan desde el pedido">Pedido →</a>
                            <?php else: ?>
                                <?= boton_eliminar(
                                    BASE_URL . '/admin/caja/anular',
                                    ['id' => $m['id_movimiento']],
                                    str_starts_with($m['tipo'], 'transferencia')
                                        ? '¿Anular esta transferencia? Se anulan las dos puntas.'
                                        : '¿Anular este movimiento? El saldo se recalcula.',
                                    'Anular',
                                    'text-xs text-gray-400 hover:text-red-600'
                                ) ?>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ══ PAGINACIÓN ══════════════════════════════════════════════════════ -->
<?php if ($totalPaginas > 1): ?>
    <div class="mt-6 flex flex-wrap items-center justify-center gap-1 text-sm">
        <?php if ($pagina > 1): ?>
            <a href="<?= htmlspecialchars($url(['pagina' => $pagina - 1])) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">←</a>
        <?php endif; ?>
        <?php for ($i = max(1, $pagina - 2); $i <= min($totalPaginas, $pagina + 2); $i++): ?>
            <a href="<?= htmlspecialchars($url(['pagina' => $i])) ?>"
               class="px-3 py-2 rounded-lg border <?= $i === $pagina ? 'bg-gray-800 text-white border-gray-800' : 'bg-white hover:bg-gray-50' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($pagina < $totalPaginas): ?>
            <a href="<?= htmlspecialchars($url(['pagina' => $pagina + 1])) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">→</a>
        <?php endif; ?>
    </div>
    <p class="text-center text-xs text-gray-400 mt-2">Página <?= $pagina ?> de <?= $totalPaginas ?></p>
<?php endif; ?>

<!-- ══ VENTANA: NUEVO MOVIMIENTO ═══════════════════════════════════════ -->
<div id="modalMov" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/40" onclick="cerrarMovimiento()"></div>
    <div class="absolute inset-x-0 bottom-0 md:inset-0 md:flex md:items-center md:justify-center md:p-6 pointer-events-none">
        <div class="pointer-events-auto bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-xl max-h-[92vh] overflow-y-auto">

            <div class="p-5 pb-0">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Nuevo movimiento</h3>
                    <button type="button" onclick="cerrarMovimiento()" class="text-gray-400 hover:text-gray-900 text-xl leading-none">✕</button>
                </div>
                <div class="grid grid-cols-4 gap-1 bg-gray-100 rounded-lg p-1 text-xs sm:text-sm">
                    <button type="button" data-tab="gasto"         class="tab-mov py-2 rounded-md">➖ Gasto</button>
                    <button type="button" data-tab="ingreso"       class="tab-mov py-2 rounded-md">➕ Ingreso</button>
                    <button type="button" data-tab="transferencia" class="tab-mov py-2 rounded-md">🔁 Pasar</button>
                    <button type="button" data-tab="ajuste"        class="tab-mov py-2 rounded-md">⚖️ Ajustar</button>
                </div>
                <p id="ayudaMov" class="text-xs text-gray-500 mt-3"></p>
            </div>

            <?php
            $optCajas = '';
            foreach ($cajas as $c) {
                $optCajas .= '<option value="' . (int) $c['id_caja'] . '">' . htmlspecialchars($c['nombre']) . ' (' . $pesos0($c['saldo']) . ')</option>';
            }
            $optCat = function (array $cats): string {
                $h = '';
                foreach ($cats as $c) {
                    $h .= '<option value="' . (int) $c['id_categoria_mov'] . '">' . htmlspecialchars($c['nombre']) . '</option>';
                }
                return $h;
            };
            $campoMonto = function (string $name = 'monto', string $label = 'Monto') use ($input): string {
                return '<label class="block"><span class="block text-sm font-medium text-gray-700 mb-1">' . $label . '</span>'
                     . '<span class="relative block"><span class="absolute left-3 top-2 text-gray-400">$</span>'
                     . '<input type="number" name="' . $name . '" step="0.01" ' . ($name === 'monto' ? 'min="0.01"' : '') . ' required inputmode="decimal" class="' . $input . ' pl-7 text-lg font-semibold"></span></label>';
            };
            $campoFecha = '<label class="block"><span class="block text-sm font-medium text-gray-700 mb-1">Fecha</span>'
                        . '<input type="date" name="fecha" value="' . date('Y-m-d') . '" max="' . date('Y-m-d') . '" class="' . $input . '"></label>';
            $campoDetalle = fn(string $ph) => '<label class="block"><span class="block text-sm font-medium text-gray-700 mb-1">Detalle <span class="font-normal text-gray-400">(opcional)</span></span>'
                        . '<input name="concepto" maxlength="150" placeholder="' . $ph . '" class="' . $input . '"></label>';
            $botones = fn(string $txt) => '<div class="flex gap-2 pt-2"><button type="button" onclick="cerrarMovimiento()" class="flex-1 px-4 py-2.5 rounded-lg border text-sm">Cancelar</button>'
                        . '<button class="flex-1 px-4 py-2.5 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">' . $txt . '</button></div>';
            ?>

            <!-- Gasto -->
            <form method="POST" action="<?= BASE_URL ?>/admin/caja/gasto" data-panel="gasto" class="panel-mov p-5 space-y-3">
                <?= csrf_field() ?>
                <?= $campoMonto() ?>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block"><span class="block text-sm font-medium text-gray-700 mb-1">Sale de</span>
                        <select name="id_caja" required class="<?= $input ?> bg-white"><?= $optCajas ?></select></label>
                    <label class="block"><span class="block text-sm font-medium text-gray-700 mb-1">Categoría</span>
                        <select name="id_categoria" required class="<?= $input ?> bg-white"><option value="">Elegir…</option><?= $optCat($catGastos) ?></select></label>
                </div>
                <?= $campoDetalle('Ej: envío a Pergamino') ?>
                <?= $campoFecha ?>
                <?= $botones('Registrar gasto') ?>
            </form>

            <!-- Ingreso -->
            <form method="POST" action="<?= BASE_URL ?>/admin/caja/ingreso" data-panel="ingreso" class="panel-mov hidden p-5 space-y-3">
                <?= csrf_field() ?>
                <?= $campoMonto() ?>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block"><span class="block text-sm font-medium text-gray-700 mb-1">Entra a</span>
                        <select name="id_caja" required class="<?= $input ?> bg-white"><?= $optCajas ?></select></label>
                    <label class="block"><span class="block text-sm font-medium text-gray-700 mb-1">Categoría</span>
                        <select name="id_categoria" required class="<?= $input ?> bg-white"><option value="">Elegir…</option><?= $optCat($catIngresos) ?></select></label>
                </div>
                <?= $campoDetalle('Ej: aporte de capital') ?>
                <?= $campoFecha ?>
                <?= $botones('Registrar ingreso') ?>
            </form>

            <!-- Transferencia -->
            <form method="POST" action="<?= BASE_URL ?>/admin/caja/transferir" data-panel="transferencia" class="panel-mov hidden p-5 space-y-3">
                <?= csrf_field() ?>
                <?= $campoMonto() ?>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block"><span class="block text-sm font-medium text-gray-700 mb-1">Desde</span>
                        <select name="id_caja" required class="<?= $input ?> bg-white"><?= $optCajas ?></select></label>
                    <label class="block"><span class="block text-sm font-medium text-gray-700 mb-1">Hacia</span>
                        <select name="id_caja_destino" required class="<?= $input ?> bg-white"><?= $optCajas ?></select></label>
                </div>
                <?= $campoDetalle('Ej: retiro del banco') ?>
                <?= $campoFecha ?>
                <?= $botones('Pasar plata') ?>
            </form>

            <!-- Ajuste -->
            <form method="POST" action="<?= BASE_URL ?>/admin/caja/ajustar" data-panel="ajuste" class="panel-mov hidden p-5 space-y-3">
                <?= csrf_field() ?>
                <label class="block"><span class="block text-sm font-medium text-gray-700 mb-1">¿En qué caja?</span>
                    <select name="id_caja" required class="<?= $input ?> bg-white"><?= $optCajas ?></select></label>
                <?= $campoMonto('saldo_real', '¿Cuánto hay de verdad?') ?>
                <?= $campoDetalle('Ej: saldo inicial, conteo del cajón') ?>
                <?= $campoFecha ?>
                <?= $botones('Ajustar saldo') ?>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('modalMov');
    const ayudas = {
        gasto:         'Plata que sale: envíos, alquiler, publicidad, bolsas…',
        ingreso:       'Plata que entra y no es una venta: un aporte, un reintegro… (los cobros de pedidos se cargan desde el pedido).',
        transferencia: 'Mover plata de una caja a otra, por ejemplo del banco al cajón. No cuenta como gasto ni como ingreso.',
        ajuste:        'Escribí cuánto hay de verdad y el sistema registra la diferencia. Sirve para el saldo inicial o cuando contás y no coincide.',
    };

    function mostrar(nombre) {
        document.querySelectorAll('.tab-mov').forEach(t => {
            const activa = t.dataset.tab === nombre;
            t.className = 'tab-mov py-2 rounded-md ' + (activa ? 'bg-white shadow-sm font-semibold text-gray-900' : 'text-gray-500 hover:text-gray-900');
        });
        document.querySelectorAll('.panel-mov').forEach(p => p.classList.toggle('hidden', p.dataset.panel !== nombre));
        document.getElementById('ayudaMov').textContent = ayudas[nombre];

        const panel = document.querySelector(`.panel-mov[data-panel="${nombre}"]`);
        const foco  = panel.querySelector('input[type="number"]');
        if (foco) setTimeout(() => foco.focus(), 50);

        // Transferir: que "Hacia" no arranque igual a "Desde"
        if (nombre === 'transferencia') {
            const hacia = panel.querySelector('[name="id_caja_destino"]');
            if (hacia.selectedIndex === 0 && hacia.options.length > 1) hacia.selectedIndex = 1;
        }
    }

    document.querySelectorAll('.tab-mov').forEach(t => t.addEventListener('click', () => mostrar(t.dataset.tab)));

    window.abrirMovimiento = function (tipo) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        mostrar(tipo || 'gasto');
    };

    window.cerrarMovimiento = function () {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarMovimiento(); });

    // Evitar doble envío
    document.querySelectorAll('.panel-mov').forEach(f => f.addEventListener('submit', () => {
        const b = f.querySelector('button:not([type="button"])');
        if (b) { b.disabled = true; b.textContent = 'Guardando…'; }
    }));
})();
</script>