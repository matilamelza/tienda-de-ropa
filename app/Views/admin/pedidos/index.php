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
$pestanas['vencidos'] = ['⏰ Sin pagar +' . Pedido::DIAS_PAGO_VENCIDO . ' días', $conteo['vencidos']];

$hayFiltros = $f['q'] !== '' || $f['pago'] !== '' || $f['entrega'] !== '' || $f['desde'] !== '' || $f['hasta'] !== '' || $f['orden'] !== '';

/** "hace 5 min", "hace 3 h", "hace 2 días" + color de urgencia */
$antiguedad = function (array $p): array {
    $seg = time() - strtotime($p['fecha']);
    if ($seg < 3600)       $txt = 'hace ' . max(1, intdiv($seg, 60)) . ' min';
    elseif ($seg < 86400)  $txt = 'hace ' . intdiv($seg, 3600) . ' h';
    elseif ($seg < 172800) $txt = 'ayer';
    else                   $txt = 'hace ' . intdiv($seg, 86400) . ' días';

    $cls = 'text-gray-400';
    if ($p['estado'] === 'pendiente_contacto') {
        $cls = $seg > 86400 ? 'text-red-600 font-semibold' : ($seg > 7200 ? 'text-amber-600 font-medium' : 'text-gray-400');
    }
    return [$txt, $cls];
};

/** Link de WhatsApp al cliente */
$wa = function (array $p): ?string {
    $tel = preg_replace('/\D/', '', $p['telefono'] ?? '');
    if ($tel === '') return null;
    if (substr($tel, 0, 2) !== '54') $tel = '549' . ltrim($tel, '0');
    $texto = 'Hola ' . ($p['nombre'] ?? '') . ', te escribimos por tu pedido #' . (int) $p['id_pedido'] . '.';
    return 'https://wa.me/' . $tel . '?text=' . rawurlencode($texto);
};

$tarjetas = [
    ['📞', 'Para contactar',       (int) $resumen['para_contactar'], 'nadie les escribió',  ['estado' => 'pendiente_contacto', 'pago' => null], 'bg-yellow-50 ring-yellow-200', $f['estado'] === 'pendiente_contacto'],
    ['💬', 'Esperando respuesta',  (int) $resumen['contactados'],    'ya les escribiste',   ['estado' => 'contactado', 'pago' => null],         'bg-blue-50 ring-blue-200',     $f['estado'] === 'contactado'],
    ['📦', 'Para entregar',        (int) $resumen['por_entregar'],   'confirmados o listos', ['estado' => 'por_entregar', 'pago' => null],      'bg-amber-50 ring-amber-200',   $f['estado'] === 'por_entregar'],
];
?>

<!-- ══ ENCABEZADO ══════════════════════════════════════════════════════ -->
<div class="flex items-end justify-between gap-3 mb-5">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Pedidos</h2>
        <p class="text-gray-500 text-sm"><?= (int) $total ?> pedido<?= $total !== 1 ? 's' : '' ?><?= $hayFiltros || $f['estado'] ? ' con estos filtros' : '' ?></p>
    </div>
    <a href="<?= BASE_URL ?>/admin/venta/nueva"
       class="shrink-0 px-4 py-2 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800">+ Venta manual</a>
</div>

<!-- ══ QUÉ HAY QUE HACER ═══════════════════════════════════════════════ -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <?php foreach ($tarjetas as [$icono, $titulo, $cant, $sub, $link, $color, $activa]): ?>
        <a href="<?= htmlspecialchars($url($activa ? ['estado' => null] : $link)) ?>"
           class="rounded-xl p-4 transition hover:ring-2 <?= $cant > 0 ? $color : 'bg-white ring-gray-200' ?> <?= $activa ? 'ring-2' : '' ?> shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xl"><?= $icono ?></span>
                <span class="text-2xl font-bold <?= $cant > 0 ? 'text-gray-900' : 'text-gray-300' ?>"><?= $cant ?></span>
            </div>
            <p class="text-sm font-medium text-gray-800 mt-2"><?= $titulo ?></p>
            <p class="text-xs text-gray-500"><?= $sub ?></p>
        </a>
    <?php endforeach; ?>

    <?php $activaDeuda = $f['pago'] === 'debe'; ?>
    <a href="<?= htmlspecialchars($url($activaDeuda ? ['pago' => null] : ['pago' => 'debe', 'estado' => null, 'orden' => 'deuda'])) ?>"
       class="rounded-xl p-4 transition hover:ring-2 shadow-sm <?= $resumen['falta_cobrar'] > 0 ? 'bg-red-50 ring-red-200' : 'bg-white ring-gray-200' ?> <?= $activaDeuda ? 'ring-2' : '' ?>">
        <div class="flex items-center justify-between">
            <span class="text-xl">💰</span>
            <span class="text-lg font-bold <?= $resumen['falta_cobrar'] > 0 ? 'text-red-700' : 'text-gray-300' ?>"><?= $pesos0($resumen['falta_cobrar']) ?></span>
        </div>
        <p class="text-sm font-medium text-gray-800 mt-2">Falta cobrar</p>
        <p class="text-xs text-gray-500">en <?= (int) $resumen['con_deuda'] ?> pedido<?= (int) $resumen['con_deuda'] !== 1 ? 's' : '' ?> confirmado<?= (int) $resumen['con_deuda'] !== 1 ? 's' : '' ?></p>
    </a>
</div>

<!-- ══ PESTAÑAS ════════════════════════════════════════════════════════ -->
<div class="-mx-4 md:mx-0 px-4 md:px-0 mb-3 overflow-x-auto">
    <div class="inline-flex gap-1.5 pb-1">
        <?php foreach ($pestanas as $clave => [$texto, $cant]): ?>
            <?php
            $activa = $f['estado'] === $clave;
            $alerta = in_array($clave, ['pendiente_contacto', 'vencidos'], true) && $cant > 0;
            ?>
            <a href="<?= htmlspecialchars($url(['estado' => $clave ?: null])) ?>"
               class="whitespace-nowrap px-3 py-1.5 rounded-full text-sm border transition
                      <?= $activa ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 hover:bg-gray-50' ?>">
                <?= htmlspecialchars($texto) ?>
                <span class="ml-0.5 text-xs <?= $activa ? 'text-white/70' : ($alerta ? 'text-red-600 font-bold' : 'text-gray-400') ?>"><?= (int) $cant ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- ══ FILTROS ═════════════════════════════════════════════════════════ -->
<form method="GET" action="<?= BASE_URL ?>/admin/pedidos" id="formFiltros"
      class="bg-white rounded-xl shadow-sm p-3 mb-4 grid grid-cols-2 md:grid-cols-12 gap-2 text-sm">
    <?php if ($f['estado'] !== ''): ?><input type="hidden" name="estado" value="<?= htmlspecialchars($f['estado']) ?>"><?php endif; ?>

    <input type="search" name="q" value="<?= htmlspecialchars($f['q']) ?>" placeholder="🔎 Cliente, teléfono, email o #pedido"
           class="col-span-2 md:col-span-4 border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-200">

    <select name="pago" class="auto md:col-span-2 border rounded-lg px-2 py-2 bg-white">
        <option value="">Cualquier pago</option>
        <option value="sin_pagar" <?= $f['pago'] === 'sin_pagar' ? 'selected' : '' ?>>Sin pagar</option>
        <option value="senado"    <?= $f['pago'] === 'senado'    ? 'selected' : '' ?>>Señados</option>
        <option value="pagado"    <?= $f['pago'] === 'pagado'    ? 'selected' : '' ?>>Pagados</option>
        <option value="debe"      <?= $f['pago'] === 'debe'      ? 'selected' : '' ?>>Deben plata</option>
    </select>

    <select name="entrega" class="auto md:col-span-2 border rounded-lg px-2 py-2 bg-white">
        <option value="">Retiro o envío</option>
        <option value="retiro" <?= $f['entrega'] === 'retiro' ? 'selected' : '' ?>>🏪 Retira</option>
        <option value="envio"  <?= $f['entrega'] === 'envio'  ? 'selected' : '' ?>>🚚 Envío</option>
        <option value="sin"    <?= $f['entrega'] === 'sin'    ? 'selected' : '' ?>>Sin definir</option>
    </select>

    <select name="orden" class="auto col-span-2 md:col-span-2 border rounded-lg px-2 py-2 bg-white">
        <?php foreach (Pedido::ORDENES_LISTADO as $clave => [$texto]): ?>
            <option value="<?= $clave === 'recientes' ? '' : $clave ?>" <?= ($f['orden'] ?: 'recientes') === $clave ? 'selected' : '' ?>><?= $texto ?></option>
        <?php endforeach; ?>
    </select>

    <div class="col-span-2 md:col-span-2 flex gap-2">
        <button class="flex-1 bg-gray-900 text-white rounded-lg px-3 py-2 hover:bg-gray-800">Buscar</button>
        <?php if ($hayFiltros || $f['estado']): ?>
            <a href="<?= BASE_URL ?>/admin/pedidos" class="px-2 py-2 text-gray-400 hover:text-red-600" title="Limpiar todo">✕</a>
        <?php endif; ?>
    </div>

    <details class="col-span-2 md:col-span-12" <?= $f['desde'] || $f['hasta'] ? 'open' : '' ?>>
        <summary class="cursor-pointer text-xs text-gray-500 select-none">📅 Filtrar por fecha</summary>
        <div class="flex flex-wrap items-center gap-2 mt-2">
            <input type="date" name="desde" value="<?= htmlspecialchars($f['desde']) ?>" class="border rounded-lg px-2 py-1.5">
            <span class="text-gray-400">a</span>
            <input type="date" name="hasta" value="<?= htmlspecialchars($f['hasta']) ?>" max="<?= date('Y-m-d') ?>" class="border rounded-lg px-2 py-1.5">
            <button class="px-3 py-1.5 rounded-lg border hover:bg-gray-50">Aplicar</button>
        </div>
    </details>
</form>

<?php if ($f['estado'] === 'vencidos'): ?>
    <p class="mb-4 text-sm text-orange-700 bg-orange-50 border border-orange-200 rounded-lg px-4 py-2">
        Confirmados o listos hace más de <?= Pedido::DIAS_PAGO_VENCIDO ?> días que todavía no terminaron de pagar. Tienen stock reservado.
    </p>
<?php endif; ?>

<!-- ══ PEDIDOS ═════════════════════════════════════════════════════════ -->
<?php if (empty($pedidos)): ?>
    <div class="bg-white rounded-xl shadow-sm px-4 py-14 text-center">
        <p class="text-4xl mb-2"><?= $hayFiltros || $f['estado'] ? '✅' : '🧾' ?></p>
        <p class="text-gray-600 font-medium">
            <?php if ($f['q'] !== ''): ?>
                No hay pedidos para "<?= htmlspecialchars($f['q']) ?>".
            <?php elseif ($hayFiltros || $f['estado']): ?>
                No hay pedidos con estos filtros.
            <?php else: ?>
                Todavía no hay pedidos.
            <?php endif; ?>
        </p>
        <?php if ($hayFiltros || $f['estado']): ?>
            <a href="<?= BASE_URL ?>/admin/pedidos" class="inline-block mt-3 text-sm text-blue-600 hover:underline">Ver todos</a>
        <?php endif; ?>
    </div>
<?php else: ?>

    <div class="space-y-2">
        <?php foreach ($pedidos as $p): ?>
            <?php
            $id        = (int) $p['id_pedido'];
            $cancelado = $p['estado'] === 'cancelado';
            $total     = (float) $p['total'];
            $cobrado   = (float) $p['cobrado'];
            $falta     = max(0, (float) $p['falta']);
            $pctPago   = $total > 0 ? min(100, round($cobrado / $total * 100)) : 0;
            [$txtPago, $clsPago] = GestionPedido::ESTADOS_PAGO[GestionPedido::estadoPago($total, $cobrado)];
            [$hace, $clsHace]    = $antiguedad($p);

            $items = $p['items_txt'] ? explode('||', $p['items_txt']) : [];
            $resto = count($items) - 2;
            $linkWa = $cancelado ? null : $wa($p);
            $nombre = trim(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? '')) ?: 'Sin nombre';
            ?>
            <div class="bg-white rounded-xl shadow-sm hover:shadow transition <?= $cancelado ? 'opacity-60' : '' ?>">
                <div class="flex flex-col md:flex-row md:items-center gap-3 p-4">

                    <!-- Cliente y productos -->
                    <a href="<?= BASE_URL ?>/admin/pedido/<?= $id ?>" class="flex-1 min-w-0 group">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-bold text-gray-900">#<?= $id ?></span>
                            <?php if (($p['origen'] ?? 'web') !== 'web'): ?>
                                <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600"><?= GestionPedido::ORIGENES[$p['origen']] ?? htmlspecialchars($p['origen']) ?></span>
                            <?php endif; ?>
                            <span class="<?= $clsHace ?> text-xs" title="<?= date('d/m/Y H:i', strtotime($p['fecha'])) ?>">· <?= $hace ?></span>
                            <?php if ($p['entrega'] === 'retiro'): ?>
                                <span class="text-xs text-gray-500" title="Retira en el local">· 🏪</span>
                            <?php elseif ($p['entrega'] === 'envio'): ?>
                                <span class="text-xs text-gray-500" title="Envío<?= $p['seguimiento'] ? ': ' . htmlspecialchars($p['seguimiento']) : '' ?>">· 🚚</span>
                            <?php endif; ?>
                            <?php if (!empty($p['notas_internas'])): ?>
                                <span class="text-xs" title="<?= htmlspecialchars(mb_substr($p['notas_internas'], 0, 120)) ?>">· 📝</span>
                            <?php endif; ?>
                        </div>
                        <p class="font-medium text-gray-900 truncate group-hover:underline">
                            <?= htmlspecialchars($nombre) ?>
                            <?php if (!empty($p['localidad'])): ?>
                                <span class="font-normal text-gray-400 text-sm">· <?= htmlspecialchars($p['localidad']) ?></span>
                            <?php endif; ?>
                        </p>
                        <p class="text-xs text-gray-500 truncate mt-0.5">
                            <?= htmlspecialchars(implode(', ', array_slice($items, 0, 2))) ?>
                            <?= $resto > 0 ? '<span class="text-gray-400">+' . $resto . ' más</span>' : '' ?>
                            <span class="text-gray-400">· <?= (int) $p['unidades'] ?> u.</span>
                        </p>
                    </a>

                    <!-- Plata -->
                    <div class="md:w-44 shrink-0">
                        <p class="font-bold text-gray-900 md:text-right"><?= $pesos0($total) ?></p>
                        <?php if (!$cancelado): ?>
                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden mt-1">
                                <div class="h-full rounded-full <?= $pctPago >= 100 ? 'bg-green-500' : 'bg-yellow-400' ?>" style="width: <?= $pctPago ?>%"></div>
                            </div>
                            <p class="text-[11px] mt-0.5 md:text-right <?= $falta > 0.009 && $cobrado > 0 ? 'text-red-600' : 'text-gray-400' ?>">
                                <?php if ($cobrado <= 0.009): ?>
                                    Sin cobros
                                <?php elseif ($falta > 0.009): ?>
                                    Pagó <?= $pesos0($cobrado) ?> · falta <?= $pesos0($falta) ?>
                                <?php else: ?>
                                    Pagado completo
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Estado -->
                    <div class="flex md:flex-col items-start md:items-end gap-1 md:w-40 shrink-0">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap <?= GestionPedido::clase($p['estado']) ?>"><?= GestionPedido::etiqueta($p['estado']) ?></span>
                        <?php if (!$cancelado): ?>
                            <span class="px-2 py-0.5 rounded-full text-xs whitespace-nowrap <?= $clsPago ?>"><?= $txtPago ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Acciones -->
                    <div class="flex items-center gap-2 shrink-0">
                        <?php if ($linkWa): ?>
                            <a href="<?= htmlspecialchars($linkWa) ?>" target="_blank" rel="noopener"
                               onclick="avisarContactado(<?= $id ?>, '<?= $p['estado'] ?>')"
                               class="w-9 h-9 flex items-center justify-center rounded-full bg-green-50 text-green-700 hover:bg-green-100"
                               title="WhatsApp a <?= htmlspecialchars($nombre) ?>">💬</a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/admin/pedido/<?= $id ?>"
                           class="px-3 py-2 rounded-lg border text-sm text-gray-700 hover:bg-gray-50">Ver</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
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
// Los selects filtran apenas cambian
document.querySelectorAll('#formFiltros select.auto').forEach(s => s.addEventListener('change', () => s.form.submit()));

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