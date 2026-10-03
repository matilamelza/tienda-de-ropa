<?php
$f      = $filtros;
$pesos0 = fn($n) => '$' . number_format((float) $n, 0, ',', '.');

/** URL conservando filtros; null en $cambios = sacar ese filtro */
$url = function (array $cambios = []) use ($f): string {
    $q = array_filter(array_merge($f, $cambios), fn($v) => $v !== '' && $v !== null);
    unset($q['pagina']);
    if (isset($cambios['pagina']) && $cambios['pagina'] > 1) {
        $q['pagina'] = $cambios['pagina'];
    }
    return BASE_URL . '/admin/pedidos' . ($q ? '?' . http_build_query($q) : '');
};

$pestanas = ['' => ['Todos', $conteo['todos']]];
foreach (GestionPedido::ESTADOS as $clave => [$texto]) {
    $pestanas[$clave] = [$texto, $conteo[$clave] ?? 0];
}
$pestanas['vencidos'] = ['Sin pagar +' . Pedido::DIAS_PAGO_VENCIDO . ' días', $conteo['vencidos']];

$hayFiltros = $f['q'] !== '' || $f['pago'] !== '' || $f['entrega'] !== '' || $f['desde'] !== '' || $f['hasta'] !== '' || $f['orden'] !== '';

/** Puntitos de color: lo único con color en la lista */
$puntoEstado = [
    'pendiente_contacto' => 'bg-yellow-400',
    'contactado'         => 'bg-blue-400',
    'confirmado'         => 'bg-indigo-500',
    'listo'              => 'bg-amber-500',
    'entregado'          => 'bg-green-500',
    'cancelado'          => 'bg-gray-300',
];
$puntoPago = [
    'sin_pagar' => 'bg-gray-300',
    'senado'    => 'bg-yellow-400',
    'pagado'    => 'bg-green-500',
    'de_mas'    => 'bg-purple-400',
];

$estadoHtml = fn(string $e) => '<span class="inline-flex items-center gap-1.5 text-xs text-gray-700 whitespace-nowrap">'
    . '<span class="w-1.5 h-1.5 rounded-full ' . ($puntoEstado[$e] ?? 'bg-gray-300') . '"></span>'
    . GestionPedido::etiqueta($e) . '</span>';

/** "hace 5 min" / "hace 3 h" / "ayer" / "hace 4 días" — rojo solo si está sin contactar hace más de un día */
$antiguedad = function (array $p): array {
    $seg = time() - strtotime($p['fecha']);
    if ($seg < 3600)       $txt = 'hace ' . max(1, intdiv($seg, 60)) . ' min';
    elseif ($seg < 86400)  $txt = 'hace ' . intdiv($seg, 3600) . ' h';
    elseif ($seg < 172800) $txt = 'ayer';
    else                   $txt = 'hace ' . intdiv($seg, 86400) . ' días';

    $urgente = $p['estado'] === 'pendiente_contacto' && $seg > 86400;
    return [$txt, $urgente ? 'text-red-600' : 'text-gray-400'];
};

/** Link de WhatsApp al cliente */
$wa = function (array $p): ?string {
    $tel = preg_replace('/\D/', '', $p['telefono'] ?? '');
    if ($tel === '') return null;
    if (substr($tel, 0, 2) !== '54') $tel = '549' . ltrim($tel, '0');
    $texto = 'Hola ' . ($p['nombre'] ?? '') . ', te escribimos por tu pedido #' . (int) $p['id_pedido'] . '.';
    return 'https://wa.me/' . $tel . '?text=' . rawurlencode($texto);
};

/** Datos de cada pedido que usan la tabla y las tarjetas del celular */
$preparar = function (array $p) use ($antiguedad, $wa): array {
    $total   = (float) $p['total'];
    $cobrado = (float) $p['cobrado'];
    $items   = $p['items_txt'] ? explode('||', $p['items_txt']) : [];
    [$hace, $clsHace] = $antiguedad($p);

    return [
        'id'        => (int) $p['id_pedido'],
        'cancelado' => $p['estado'] === 'cancelado',
        'total'     => $total,
        'cobrado'   => $cobrado,
        'falta'     => max(0, (float) $p['falta']),
        'pct'       => $total > 0 ? min(100, round($cobrado / $total * 100)) : 0,
        'pago'      => GestionPedido::estadoPago($total, $cobrado),
        'hace'      => $hace,
        'clsHace'   => $clsHace,
        'items'     => implode(', ', array_slice($items, 0, 2)),
        'resto'     => count($items) - 2,
        'nombre'    => trim(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? '')) ?: 'Sin nombre',
        'wa'        => $p['estado'] === 'cancelado' ? null : $wa($p),
        'origen'    => ($p['origen'] ?? 'web') !== 'web' ? (GestionPedido::ORIGENES[$p['origen']] ?? $p['origen']) : null,
    ];
};

/** Texto del pago, en gris salvo cuando falta plata */
$pagoHtml = function (array $d) use ($puntoPago, $pesos0): string {
    if ($d['cancelado']) return '<span class="text-xs text-gray-400">—</span>';
    [$txt] = GestionPedido::ESTADOS_PAGO[$d['pago']];
    $html = '<span class="inline-flex items-center gap-1.5 text-xs text-gray-700 whitespace-nowrap">'
          . '<span class="w-1.5 h-1.5 rounded-full ' . $puntoPago[$d['pago']] . '"></span>' . $txt . '</span>';
    if ($d['pago'] === 'senado') {
        $html .= '<span class="block text-[11px] text-red-600 mt-0.5">falta ' . $pesos0($d['falta']) . '</span>';
    }
    return $html;
};

$tarjetas = [
    ['Para contactar',      (int) $resumen['para_contactar'], 'nadie les escribió',     ['estado' => 'pendiente_contacto', 'pago' => null], $f['estado'] === 'pendiente_contacto'],
    ['Esperando respuesta', (int) $resumen['contactados'],    'ya les escribiste',      ['estado' => 'contactado', 'pago' => null],         $f['estado'] === 'contactado'],
    ['Para entregar',       (int) $resumen['por_entregar'],   'confirmados o listos',   ['estado' => 'por_entregar', 'pago' => null],       $f['estado'] === 'por_entregar'],
];
$activaDeuda = $f['pago'] === 'debe';
?>

<!-- ══ ENCABEZADO ══════════════════════════════════════════════════════ -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Pedidos</h2>
        <p class="text-gray-500 text-sm"><?= (int) $total ?> pedido<?= $total !== 1 ? 's' : '' ?><?= $hayFiltros || $f['estado'] ? ' con estos filtros' : '' ?></p>
    </div>
    <a href="<?= BASE_URL ?>/admin/venta/nueva"
       class="self-start sm:self-auto bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800">+ Venta manual</a>
</div>

<!-- ══ RESUMEN ═════════════════════════════════════════════════════════ -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php foreach ($tarjetas as [$titulo, $cant, $sub, $link, $activa]): ?>
        <a href="<?= htmlspecialchars($url($activa ? ['estado' => null] : $link)) ?>"
           class="bg-white rounded-lg shadow p-5 transition hover:ring-2 hover:ring-gray-200 <?= $activa ? 'ring-2 ring-gray-900' : '' ?>">
            <p class="text-sm text-gray-500"><?= $titulo ?></p>
            <p class="text-2xl md:text-3xl font-bold mt-1 <?= $cant > 0 ? 'text-gray-900' : 'text-gray-300' ?>"><?= $cant ?></p>
            <p class="text-xs text-gray-400 mt-1"><?= $sub ?></p>
        </a>
    <?php endforeach; ?>

    <a href="<?= htmlspecialchars($url($activaDeuda ? ['pago' => null, 'orden' => null] : ['pago' => 'debe', 'estado' => null, 'orden' => 'deuda'])) ?>"
       class="bg-white rounded-lg shadow p-5 transition hover:ring-2 hover:ring-gray-200 <?= $activaDeuda ? 'ring-2 ring-gray-900' : '' ?>">
        <p class="text-sm text-gray-500">Falta cobrar</p>
        <p class="text-2xl md:text-3xl font-bold mt-1 <?= $resumen['falta_cobrar'] > 0 ? 'text-red-600' : 'text-gray-300' ?>"><?= $pesos0($resumen['falta_cobrar']) ?></p>
        <p class="text-xs text-gray-400 mt-1">en <?= (int) $resumen['con_deuda'] ?> pedido<?= (int) $resumen['con_deuda'] !== 1 ? 's' : '' ?> confirmado<?= (int) $resumen['con_deuda'] !== 1 ? 's' : '' ?></p>
    </a>
</div>

<!-- ══ PESTAÑAS ════════════════════════════════════════════════════════ -->
<div class="flex gap-1 mb-4 border-b overflow-x-auto">
    <?php foreach ($pestanas as $clave => [$texto, $cant]): ?>
        <?php $activa = $f['estado'] === $clave; ?>
        <a href="<?= htmlspecialchars($url(['estado' => $clave ?: null])) ?>"
           class="px-3 py-2 text-sm whitespace-nowrap border-b-2 -mb-px
                  <?= $activa ? 'border-gray-900 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-900' ?>">
            <?= htmlspecialchars($texto) ?>
            <span class="text-xs <?= $activa ? 'text-gray-500' : 'text-gray-400' ?>"><?= (int) $cant ?></span>
        </a>
    <?php endforeach; ?>
</div>

<!-- ══ FILTROS ═════════════════════════════════════════════════════════ -->
<form method="GET" action="<?= BASE_URL ?>/admin/pedidos" id="formFiltros"
      class="bg-white rounded-lg shadow p-3 mb-4 grid grid-cols-2 md:grid-cols-6 gap-2 text-sm">
    <?php if ($f['estado'] !== ''): ?><input type="hidden" name="estado" value="<?= htmlspecialchars($f['estado']) ?>"><?php endif; ?>

    <input type="search" name="q" value="<?= htmlspecialchars($f['q']) ?>" placeholder="Buscar por cliente, teléfono o #pedido…"
           class="col-span-2 border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-200">

    <select name="pago" class="auto border rounded-lg px-3 py-2 bg-white">
        <option value="">Cualquier pago</option>
        <option value="sin_pagar" <?= $f['pago'] === 'sin_pagar' ? 'selected' : '' ?>>Sin pagar</option>
        <option value="senado"    <?= $f['pago'] === 'senado'    ? 'selected' : '' ?>>Señados</option>
        <option value="pagado"    <?= $f['pago'] === 'pagado'    ? 'selected' : '' ?>>Pagados</option>
        <option value="debe"      <?= $f['pago'] === 'debe'      ? 'selected' : '' ?>>Deben plata</option>
    </select>

    <select name="entrega" class="auto border rounded-lg px-3 py-2 bg-white">
        <option value="">Retiro o envío</option>
        <option value="retiro" <?= $f['entrega'] === 'retiro' ? 'selected' : '' ?>>Retira</option>
        <option value="envio"  <?= $f['entrega'] === 'envio'  ? 'selected' : '' ?>>Envío</option>
        <option value="sin"    <?= $f['entrega'] === 'sin'    ? 'selected' : '' ?>>Sin definir</option>
    </select>

    <select name="orden" class="auto border rounded-lg px-3 py-2 bg-white">
        <?php foreach (Pedido::ORDENES_LISTADO as $clave => [$texto]): ?>
            <option value="<?= $clave === 'recientes' ? '' : $clave ?>" <?= ($f['orden'] ?: 'recientes') === $clave ? 'selected' : '' ?>><?= $texto ?></option>
        <?php endforeach; ?>
    </select>

    <div class="flex items-center gap-2">
        <input type="date" name="desde" value="<?= htmlspecialchars($f['desde']) ?>" title="Desde" class="auto w-full border rounded-lg px-2 py-2 bg-white">
    </div>

    <?php if ($hayFiltros || $f['estado']): ?>
        <a href="<?= BASE_URL ?>/admin/pedidos" class="col-span-2 md:col-span-6 text-center text-sm text-gray-500 hover:text-red-600">✕ Limpiar filtros</a>
    <?php endif; ?>
</form>

<?php if ($f['estado'] === 'vencidos'): ?>
    <p class="mb-4 text-sm text-gray-600 bg-white border rounded-lg px-4 py-2">
        Confirmados o listos hace más de <?= Pedido::DIAS_PAGO_VENCIDO ?> días que todavía no terminaron de pagar. Tienen stock reservado.
    </p>
<?php endif; ?>

<!-- ══ PEDIDOS ═════════════════════════════════════════════════════════ -->
<?php if (empty($pedidos)): ?>
    <div class="bg-white rounded-lg shadow px-4 py-10 text-center text-gray-400 text-sm">
        <?php if ($f['q'] !== ''): ?>
            No hay pedidos para "<?= htmlspecialchars($f['q']) ?>".
        <?php elseif ($hayFiltros || $f['estado']): ?>
            ✅ No hay pedidos con estos filtros.
        <?php else: ?>
            Todavía no hay pedidos.
        <?php endif; ?>
    </div>
<?php else: ?>

    <!-- Celular: tarjetas -->
    <div class="md:hidden space-y-3">
        <?php foreach ($pedidos as $p): ?>
            <?php $d = $preparar($p); ?>
            <a href="<?= BASE_URL ?>/admin/pedido/<?= $d['id'] ?>" class="block bg-white rounded-lg shadow p-4 <?= $d['cancelado'] ? 'opacity-60' : '' ?>">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold text-gray-900">#<?= $d['id'] ?></span>
                    <?= $estadoHtml($p['estado']) ?>
                </div>
                <p class="text-sm text-gray-900 mt-1 truncate"><?= htmlspecialchars($d['nombre']) ?></p>
                <p class="text-xs text-gray-500 truncate">
                    <?= htmlspecialchars($d['items']) ?><?= $d['resto'] > 0 ? ' +' . $d['resto'] . ' más' : '' ?>
                </p>
                <div class="flex items-end justify-between mt-3">
                    <span class="text-xs <?= $d['clsHace'] ?>">
                        <?= $d['hace'] ?><?= $d['origen'] ? ' · ' . htmlspecialchars(strip_tags($d['origen'])) : '' ?>
                    </span>
                    <span class="text-right">
                        <span class="block font-bold text-gray-900"><?= $pesos0($d['total']) ?></span>
                        <?= $pagoHtml($d) ?>
                    </span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Compu: tabla -->
    <div class="hidden md:block bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3">Pedido</th>
                        <th class="text-left px-4 py-3">Cliente</th>
                        <th class="text-left px-4 py-3">Productos</th>
                        <th class="text-right px-4 py-3">Total</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-left px-4 py-3">Pago</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $p): ?>
                        <?php $d = $preparar($p); ?>
                        <tr class="border-t hover:bg-gray-50 align-top cursor-pointer <?= $d['cancelado'] ? 'opacity-60' : '' ?>"
                            onclick="if (!event.target.closest('a')) location.href='<?= BASE_URL ?>/admin/pedido/<?= $d['id'] ?>'">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="<?= BASE_URL ?>/admin/pedido/<?= $d['id'] ?>" class="font-semibold text-gray-900 hover:underline">#<?= $d['id'] ?></a>
                                <span class="block text-xs <?= $d['clsHace'] ?>" title="<?= date('d/m/Y H:i', strtotime($p['fecha'])) ?>"><?= $d['hace'] ?></span>
                                <?php if ($d['origen']): ?>
                                    <span class="block text-xs text-gray-400"><?= htmlspecialchars(strip_tags($d['origen'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-gray-900"><?= htmlspecialchars($d['nombre']) ?></p>
                                <p class="text-xs text-gray-400">
                                    <?= htmlspecialchars($p['localidad'] ?? '') ?>
                                    <?= $p['entrega'] === 'envio' ? ($p['localidad'] ? ' · ' : '') . 'envío' : '' ?>
                                    <?= !empty($p['notas_internas']) ? ' <span title="' . htmlspecialchars(mb_substr($p['notas_internas'], 0, 120)) . '">· 📝</span>' : '' ?>
                                </p>
                            </td>
                            <td class="px-4 py-3 max-w-xs">
                                <p class="text-gray-700 truncate"><?= htmlspecialchars($d['items']) ?></p>
                                <p class="text-xs text-gray-400"><?= (int) $p['unidades'] ?> u.<?= $d['resto'] > 0 ? ' · +' . $d['resto'] . ' más' : '' ?></p>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <span class="font-semibold text-gray-900"><?= $pesos0($d['total']) ?></span>
                                <?php if (!$d['cancelado'] && $d['cobrado'] > 0 && $d['pct'] < 100): ?>
                                    <div class="h-1 bg-gray-100 rounded-full overflow-hidden mt-1.5 w-20 ml-auto">
                                        <div class="h-full bg-gray-900 rounded-full" style="width: <?= $d['pct'] ?>%"></div>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3"><?= $estadoHtml($p['estado']) ?></td>
                            <td class="px-4 py-3"><?= $pagoHtml($d) ?></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <?php if ($d['wa']): ?>
                                    <a href="<?= htmlspecialchars($d['wa']) ?>" target="_blank" rel="noopener"
                                       onclick="avisarContactado(<?= $d['id'] ?>, '<?= $p['estado'] ?>')"
                                       class="text-gray-400 hover:text-green-600 mr-3" title="WhatsApp a <?= htmlspecialchars($d['nombre']) ?>">💬</a>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>/admin/pedido/<?= $d['id'] ?>" class="text-gray-600 hover:text-gray-900">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<!-- ══ PAGINACIÓN ══════════════════════════════════════════════════════ -->
<?php if ($totalPaginas > 1): ?>
    <?php $inicio = max(1, $pagina - 2); $fin = min($totalPaginas, $pagina + 2); ?>
    <div class="mt-6 flex flex-wrap items-center justify-center gap-1 text-sm">
        <?php if ($pagina > 1): ?>
            <a href="<?= htmlspecialchars($url(['pagina' => $pagina - 1])) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">←</a>
        <?php endif; ?>
        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
            <a href="<?= htmlspecialchars($url(['pagina' => $i])) ?>"
               class="px-3 py-2 rounded-lg border <?= $i === $pagina ? 'bg-gray-800 text-white border-gray-800' : 'bg-white hover:bg-gray-50' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($pagina < $totalPaginas): ?>
            <a href="<?= htmlspecialchars($url(['pagina' => $pagina + 1])) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">→</a>
        <?php endif; ?>
    </div>
    <p class="text-center text-xs text-gray-400 mt-2">Página <?= $pagina ?> de <?= $totalPaginas ?></p>
<?php endif; ?>

<script>
// Los selects y la fecha filtran apenas cambian
document.querySelectorAll('#formFiltros .auto').forEach(s => s.addEventListener('change', () => s.form.submit()));

// WhatsApp desde el listado: si estaba pendiente, pasa a Contactado
function avisarContactado(id, estado) {
    try {
        const datos = new FormData();
        datos.append('csrf_token', <?= json_encode(csrf_token()) ?>);
        datos.append('id_pedido', id);
        navigator.sendBeacon('<?= BASE_URL ?>/admin/pedido/contactado', datos);
        if (estado === 'pendiente_contacto') setTimeout(() => location.reload(), 1500);
    } catch (e) {}
}
</script>