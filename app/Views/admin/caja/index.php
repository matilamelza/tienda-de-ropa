<?php
$msg = $_SESSION['caja_msg'] ?? null;
unset($_SESSION['caja_msg']);

$pesos = fn($n) => '$' . number_format((float) $n, 2, ',', '.');
$input = 'w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-gray-200';

$saldoTotal = array_sum(array_column($cajas, 'saldo'));
$resultado  = $totales['entradas'] - $totales['salidas'];

$estiloTipo = [
    'cobro'                 => 'bg-green-100 text-green-800',
    'ingreso'               => 'bg-green-100 text-green-800',
    'gasto'                 => 'bg-red-100 text-red-700',
    'devolucion'            => 'bg-red-100 text-red-700',
    'pago_proveedor'        => 'bg-orange-100 text-orange-800',
    'transferencia_salida'  => 'bg-blue-100 text-blue-800',
    'transferencia_entrada' => 'bg-blue-100 text-blue-800',
    'ajuste'                => 'bg-gray-200 text-gray-700',
];

$urlPagina = function (int $n): string {
    $q = $_GET;
    unset($q['route']);
    $q['pagina'] = $n;
    return BASE_URL . '/admin/caja?' . http_build_query($q);
};
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Caja</h2>
        <p class="text-gray-500 text-sm">Cuánta plata hay, dónde está y en qué se mueve.</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/caja/config"
       class="self-start px-4 py-2 rounded-lg border bg-white text-gray-700 text-sm">⚙️ Configurar cajas y medios</a>
</div>

<?php if ($msg): ?>
    <div class="mb-6 px-4 py-3 rounded-xl text-sm border
        <?= $msg['tipo'] === 'ok' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
        <?= htmlspecialchars($msg['texto']) ?>
    </div>
<?php endif; ?>

<!-- ══ SALDOS ═══════════════════════════════════════════════════ -->
<div class="grid grid-cols-2 lg:grid-cols-<?= min(5, count($cajas) + 1) ?> gap-4 mb-6">
    <?php foreach ($cajas as $c): ?>
        <a href="<?= BASE_URL ?>/admin/caja?caja=<?= (int) $c['id_caja'] ?>&desde=<?= $desde ?>&hasta=<?= $hasta ?>"
           class="bg-white rounded-lg shadow p-4 hover:ring-2 hover:ring-gray-200 <?= $filtros['caja'] === (int) $c['id_caja'] ? 'ring-2 ring-gray-900' : '' ?>">
            <p class="text-sm text-gray-500 truncate"><?= htmlspecialchars($c['nombre']) ?></p>
            <p class="text-xl md:text-2xl font-bold <?= $c['saldo'] < 0 ? 'text-red-600' : 'text-gray-900' ?>"><?= $pesos($c['saldo']) ?></p>
        </a>
    <?php endforeach; ?>
    <div class="bg-gray-900 text-white rounded-lg shadow p-4">
        <p class="text-sm text-gray-400">Total</p>
        <p class="text-xl md:text-2xl font-bold"><?= $pesos($saldoTotal) ?></p>
    </div>
</div>

<!-- ══ NUEVO MOVIMIENTO ═════════════════════════════════════════ -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="flex border-b overflow-x-auto text-sm" id="tabsMov">
        <button type="button" data-tab="gasto"         class="tab-mov px-4 py-3 whitespace-nowrap">➖ Gasto</button>
        <button type="button" data-tab="ingreso"       class="tab-mov px-4 py-3 whitespace-nowrap">➕ Ingreso</button>
        <button type="button" data-tab="transferencia" class="tab-mov px-4 py-3 whitespace-nowrap">🔁 Transferir</button>
        <button type="button" data-tab="ajuste"        class="tab-mov px-4 py-3 whitespace-nowrap">⚖️ Ajustar saldo</button>
    </div>

    <div class="p-4">
        <?php
        $selectCajas = function (string $name = 'id_caja', string $placeholder = 'Caja') use ($cajas, $input): string {
            $h = '<select name="' . $name . '" required class="' . $input . ' bg-white"><option value="">' . $placeholder . '</option>';
            foreach ($cajas as $c) {
                $h .= '<option value="' . (int) $c['id_caja'] . '">' . htmlspecialchars($c['nombre']) . '</option>';
            }
            return $h . '</select>';
        };
        $selectCategorias = function (array $cats) use ($input): string {
            $h = '<select name="id_categoria" required class="' . $input . ' bg-white"><option value="">Categoría</option>';
            foreach ($cats as $c) {
                $h .= '<option value="' . (int) $c['id_categoria_mov'] . '">' . htmlspecialchars($c['nombre']) . '</option>';
            }
            return $h . '</select>';
        };
        $campoFecha = '<input type="date" name="fecha" value="' . date('Y-m-d') . '" max="' . date('Y-m-d') . '" class="' . $input . '">';
        $campoMonto = '<input type="number" name="monto" step="0.01" min="0.01" required placeholder="Monto $" class="' . $input . '">';
        ?>

        <!-- Gasto -->
        <form method="POST" action="<?= BASE_URL ?>/admin/caja/gasto" data-panel="gasto"
              class="panel-mov grid grid-cols-2 md:grid-cols-6 gap-2">
            <?= csrf_field() ?>
            <div class="col-span-2 md:col-span-1"><?= $campoMonto ?></div>
            <div class="col-span-1"><?= $selectCajas('id_caja', 'Sale de…') ?></div>
            <div class="col-span-1"><?= $selectCategorias($catGastos) ?></div>
            <input name="concepto" maxlength="150" placeholder="Detalle (opcional)" class="col-span-2 md:col-span-1 <?= $input ?>">
            <div class="col-span-1"><?= $campoFecha ?></div>
            <button class="col-span-1 bg-gray-900 text-white rounded-lg text-sm px-3 py-2 hover:bg-gray-800">Registrar gasto</button>
        </form>

        <!-- Ingreso -->
        <form method="POST" action="<?= BASE_URL ?>/admin/caja/ingreso" data-panel="ingreso"
              class="panel-mov hidden grid-cols-2 md:grid-cols-6 gap-2">
            <?= csrf_field() ?>
            <div class="col-span-2 md:col-span-1"><?= $campoMonto ?></div>
            <div class="col-span-1"><?= $selectCajas('id_caja', 'Entra a…') ?></div>
            <div class="col-span-1"><?= $selectCategorias($catIngresos) ?></div>
            <input name="concepto" maxlength="150" placeholder="Detalle (opcional)" class="col-span-2 md:col-span-1 <?= $input ?>">
            <div class="col-span-1"><?= $campoFecha ?></div>
            <button class="col-span-1 bg-gray-900 text-white rounded-lg text-sm px-3 py-2 hover:bg-gray-800">Registrar ingreso</button>
        </form>

        <!-- Transferencia -->
        <form method="POST" action="<?= BASE_URL ?>/admin/caja/transferir" data-panel="transferencia"
              class="panel-mov hidden grid-cols-2 md:grid-cols-6 gap-2">
            <?= csrf_field() ?>
            <div class="col-span-2 md:col-span-1"><?= $campoMonto ?></div>
            <div class="col-span-1"><?= $selectCajas('id_caja', 'Desde…') ?></div>
            <div class="col-span-1"><?= $selectCajas('id_caja_destino', 'Hacia…') ?></div>
            <input name="concepto" maxlength="150" placeholder="Ej: retiro del banco" class="col-span-2 md:col-span-1 <?= $input ?>">
            <div class="col-span-1"><?= $campoFecha ?></div>
            <button class="col-span-1 bg-gray-900 text-white rounded-lg text-sm px-3 py-2 hover:bg-gray-800">Transferir</button>
        </form>

        <!-- Ajuste -->
        <form method="POST" action="<?= BASE_URL ?>/admin/caja/ajustar" data-panel="ajuste"
              class="panel-mov hidden grid-cols-2 md:grid-cols-6 gap-2">
            <?= csrf_field() ?>
            <input type="number" name="saldo_real" step="0.01" required placeholder="¿Cuánto hay? $"
                   class="col-span-2 md:col-span-1 <?= $input ?>">
            <div class="col-span-1"><?= $selectCajas('id_caja', 'En qué caja') ?></div>
            <input name="concepto" maxlength="150" placeholder="Ej: saldo inicial / conteo" class="col-span-1 md:col-span-2 <?= $input ?>">
            <div class="col-span-1"><?= $campoFecha ?></div>
            <button class="col-span-1 bg-gray-900 text-white rounded-lg text-sm px-3 py-2 hover:bg-gray-800">Ajustar</button>
            <p class="col-span-2 md:col-span-6 text-xs text-gray-400">
                Escribí cuánta plata <strong>hay de verdad</strong> en esa caja y el sistema registra la diferencia.
                Usalo para cargar el saldo inicial o cuando contás el efectivo y no coincide.
            </p>
        </form>
    </div>
</div>

<!-- ══ FILTROS ══════════════════════════════════════════════════ -->
<form method="GET" action="<?= BASE_URL ?>/admin/caja" class="bg-white rounded-lg shadow p-3 mb-4 grid grid-cols-2 md:grid-cols-6 gap-2 text-sm">
    <label class="col-span-1">
        <span class="block text-xs text-gray-400 mb-1">Desde</span>
        <input type="date" name="desde" value="<?= $desde ?>" class="<?= $input ?>">
    </label>
    <label class="col-span-1">
        <span class="block text-xs text-gray-400 mb-1">Hasta</span>
        <input type="date" name="hasta" value="<?= $hasta ?>" class="<?= $input ?>">
    </label>
    <label class="col-span-1">
        <span class="block text-xs text-gray-400 mb-1">Caja</span>
        <select name="caja" class="<?= $input ?> bg-white">
            <option value="">Todas</option>
            <?php foreach ($cajas as $c): ?>
                <option value="<?= (int) $c['id_caja'] ?>" <?= $filtros['caja'] === (int) $c['id_caja'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="col-span-1">
        <span class="block text-xs text-gray-400 mb-1">Tipo</span>
        <select name="tipo" class="<?= $input ?> bg-white">
            <option value="">Todos</option>
            <?php foreach (Movimiento::TIPOS as $clave => $txt): ?>
                <option value="<?= $clave ?>" <?= $filtros['tipo'] === $clave ? 'selected' : '' ?>><?= $txt ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="col-span-1 flex items-end gap-2 pb-2">
        <input type="checkbox" name="anulados" value="1" <?= $filtros['anulados'] ? 'checked' : '' ?>>
        <span class="text-gray-600">Ver anulados</span>
    </label>
    <div class="col-span-1 flex items-end gap-2">
        <button class="flex-1 bg-gray-800 text-white rounded-lg px-3 py-2 hover:bg-gray-700">Filtrar</button>
        <a href="<?= BASE_URL ?>/admin/caja" class="px-3 py-2 text-gray-400 hover:text-gray-700" title="Limpiar">✕</a>
    </div>
</form>

<!-- ══ TOTALES DEL FILTRO ═══════════════════════════════════════ -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4 text-sm">
    <div class="bg-white rounded-lg shadow px-4 py-3">
        <p class="text-gray-500">Entró</p>
        <p class="font-bold text-green-700"><?= $pesos($totales['entradas']) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow px-4 py-3">
        <p class="text-gray-500">Salió</p>
        <p class="font-bold text-red-600"><?= $pesos($totales['salidas']) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow px-4 py-3">
        <p class="text-gray-500">Comisiones</p>
        <p class="font-bold text-orange-600"><?= $pesos($totales['comisiones']) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow px-4 py-3">
        <p class="text-gray-500">Resultado</p>
        <p class="font-bold <?= $resultado < 0 ? 'text-red-600' : 'text-gray-900' ?>"><?= $pesos($resultado) ?></p>
    </div>
</div>

<!-- ══ MOVIMIENTOS ══════════════════════════════════════════════ -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <?php if (empty($movimientos)): ?>
        <p class="px-4 py-10 text-center text-gray-400 text-sm">No hay movimientos con estos filtros.</p>
    <?php else: ?>
        <ul class="divide-y">
            <?php foreach ($movimientos as $m): ?>
                <?php
                $anulado = $m['anulado_at'] !== null;
                $detalle = $m['concepto'] ?: ($m['categoria'] ?? '');

                if (in_array($m['tipo'], ['transferencia_salida', 'transferencia_entrada'], true) && $m['caja_relacionada']) {
                    $detalle = ($m['tipo'] === 'transferencia_salida' ? '→ ' : '← ') . $m['caja_relacionada']
                             . ($m['concepto'] ? ' · ' . $m['concepto'] : '');
                }
                ?>
                <li class="px-4 py-3 flex flex-col md:flex-row md:items-center gap-2 md:gap-4 <?= $anulado ? 'opacity-50' : '' ?>">

                    <div class="flex items-center gap-3 md:w-56 shrink-0">
                        <span class="text-xs text-gray-400 w-20 shrink-0"><?= date('d/m/y H:i', strtotime($m['fecha'])) ?></span>
                        <span class="px-2 py-0.5 rounded text-xs whitespace-nowrap <?= $estiloTipo[$m['tipo']] ?? 'bg-gray-100' ?>">
                            <?= Movimiento::TIPOS[$m['tipo']] ?? $m['tipo'] ?>
                        </span>
                    </div>

                    <div class="flex-1 min-w-0 text-sm <?= $anulado ? 'line-through' : '' ?>">
                        <p class="text-gray-900 truncate">
                            <?= htmlspecialchars($detalle ?: '—') ?>
                            <?php if ($m['id_pedido']): ?>
                                <a href="<?= BASE_URL ?>/admin/pedido/<?= (int) $m['id_pedido'] ?>" class="text-blue-600 hover:underline">#<?= (int) $m['id_pedido'] ?></a>
                            <?php endif; ?>
                        </p>
                        <p class="text-xs text-gray-400">
                            <?= htmlspecialchars($m['caja']) ?>
                            <?= $m['medio'] ? ' · ' . htmlspecialchars($m['medio']) : '' ?>
                            <?= $m['categoria'] && $m['concepto'] ? ' · ' . htmlspecialchars($m['categoria']) : '' ?>
                        </p>
                    </div>

                    <div class="flex items-center justify-between md:justify-end gap-4 md:w-64 shrink-0">
                        <div class="text-right">
                            <p class="font-semibold <?= $m['neto_caja'] < 0 ? 'text-red-600' : 'text-green-700' ?> <?= $anulado ? 'line-through' : '' ?>">
                                <?= $m['neto_caja'] < 0 ? '−' : '+' ?><?= $pesos(abs($m['neto_caja'])) ?>
                            </p>
                            <?php if ($m['comision'] > 0): ?>
                                <p class="text-xs text-orange-600"><?= $pesos($m['monto']) ?> − <?= $pesos($m['comision']) ?> com.</p>
                            <?php endif; ?>
                        </div>

                        <?php if ($anulado): ?>
                            <span class="text-xs text-gray-400">Anulado</span>
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
    <?php