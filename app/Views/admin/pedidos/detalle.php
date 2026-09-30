<?php
$p       = $r['pedido'];
$id      = (int) $p['id_pedido'];
$final   = in_array($p['estado'], ['entregado', 'cancelado'], true);
$pesos   = fn($n) => '$' . number_format((float) $n, 2, ',', '.');
$input   = 'w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-gray-200';

$msg = $_SESSION['pedido_msg'] ?? null;
unset($_SESSION['pedido_msg']);

[$txtPago, $clsPago] = GestionPedido::ESTADOS_PAGO[$r['estado_pago']];

// Qué hace cada paso con el stock (para los botones de estado)
$efecto = function (string $destino) use ($p): string {
    $reservaba = in_array($p['estado'], GestionPedido::RESERVAN, true);
    if ($destino === 'entregado') return 'descuenta el stock';
    if (in_array($destino, GestionPedido::RESERVAN, true) && !$reservaba) return 'reserva el stock';
    if (!in_array($destino, GestionPedido::RESERVAN, true) && $reservaba) return 'libera el stock';
    return '';
};

// WhatsApp al cliente
$tel = preg_replace('/\D/', '', $p['telefono'] ?? '');
if ($tel !== '' && substr($tel, 0, 2) !== '54') {
    $tel = '549' . ltrim($tel, '0');
}
$textoWa = 'Hola ' . ($p['nombre'] ?? '') . ', te escribimos por tu pedido #' . $id . '. '
         . 'Total: ' . GestionPedido::pesos((float) $p['total'])
         . ($r['pendiente'] > 0 && $r['cobrado'] > 0 ? ' (resta ' . GestionPedido::pesos($r['pendiente']) . ')' : '') . '.';
$linkWa = $tel !== '' ? 'https://wa.me/' . $tel . '?text=' . rawurlencode($textoWa) : null;

$iconoHist = [
    'estado' => '🔄', 'cobro' => '💵', 'devolucion' => '↩️', 'condiciones' => '✏️',
    'cancelacion' => '✖️', 'contacto' => '💬', 'nota' => '📝',
];
?>

<!-- ══ ENCABEZADO ══════════════════════════════════════════════════════ -->
<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-6">
    <div>
        <a href="<?= BASE_URL ?>/admin/pedidos" class="text-sm text-gray-500 hover:text-gray-900">← Pedidos</a>
        <h2 class="text-2xl font-bold text-gray-800 mt-1">Pedido #<?= $id ?></h2>
        <div class="flex flex-wrap items-center gap-2 mt-2 text-sm">
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= GestionPedido::clase($p['estado']) ?>">
                <?= GestionPedido::etiqueta($p['estado']) ?>
            </span>
            <?php if ($p['estado'] !== 'cancelado'): ?>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $clsPago ?>"><?= $txtPago ?></span>
            <?php endif; ?>
            <span class="text-gray-400"><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></span>
            <?php if (($p['origen'] ?? 'web') !== 'web'): ?>
                <span class="text-gray-400">· <?= htmlspecialchars($p['origen']) ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($msg): ?>
    <div class="mb-6 px-4 py-3 rounded-xl text-sm border
        <?= $msg[0] === 'ok' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
        <?= htmlspecialchars($msg[1]) ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- ══ COLUMNA PRINCIPAL ═══════════════════════════════════════════ -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Productos + total -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-5 py-4 border-b"><h3 class="font-bold text-gray-800">Productos</h3></div>

            <ul class="divide-y text-sm">
                <?php foreach ($r['items'] as $it): ?>
                    <li class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 truncate">
                                <?php if (!empty($it['id_producto'])): ?>
                                    <a href="<?= BASE_URL ?>/admin/productos/editar?id=<?= (int) $it['id_producto'] ?>" class="hover:underline">
                                        <?= htmlspecialchars($it['producto']) ?>
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialchars($it['producto']) ?>
                                <?php endif; ?>
                            </p>
                            <p class="text-xs text-gray-400">
                                <?= htmlspecialchars(variante_texto($it['talle'], $it['color']) ?: '—') ?>
                                · <?= (int) $it['cantidad'] ?> × <?= $pesos($it['precio_unitario']) ?>
                                <?php if (!empty($it['precio_lista']) && (float) $it['precio_lista'] > (float) $it['precio_unitario']): ?>
                                    <span class="line-through"><?= $pesos($it['precio_lista']) ?></span> 🔥
                                <?php endif; ?>
                            </p>
                        </div>
                        <span class="font-semibold whitespace-nowrap"><?= $pesos($it['subtotal']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="px-5 py-4 border-t bg-gray-50 text-sm space-y-1.5">
                <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span><?= $pesos($p['subtotal']) ?></span></div>
                <?php if ((float) $p['descuento'] > 0): ?>
                    <div class="flex justify-between text-green-700">
                        <span>Descuento<?= $p['motivo_descuento'] ? ' · ' . htmlspecialchars($p['motivo_descuento']) : '' ?></span>
                        <span>−<?= $pesos($p['descuento']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ((float) $p['ajuste_monto'] != 0): ?>
                    <div class="flex justify-between <?= $p['ajuste_monto'] > 0 ? 'text-orange-600' : 'text-green-700' ?>">
                        <span><?= $p['ajuste_monto'] > 0 ? 'Recargo' : 'Descuento' ?> <?= htmlspecialchars($p['medio_acordado'] ?? '') ?> (<?= pct_texto(abs((float) $p['ajuste_pct'])) ?>%)</span>
                        <span><?= $p['ajuste_monto'] > 0 ? '+' : '−' ?><?= $pesos(abs($p['ajuste_monto'])) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ((float) $p['envio_cobrado'] > 0): ?>
                    <div class="flex justify-between"><span class="text-gray-500">Envío</span><span>+<?= $pesos($p['envio_cobrado']) ?></span></div>
                <?php endif; ?>
                <div class="flex justify-between pt-2 border-t text-base font-bold"><span>Total</span><span><?= $pesos($p['total']) ?></span></div>
            </div>
        </div>

        <!-- Cobros -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-5 py-4 border-b flex items-center justify-between gap-3">
                <h3 class="font-bold text-gray-800">Cobros</h3>
                <div class="text-sm text-right">
                    <span class="text-gray-500">Pagó</span> <strong><?= $pesos($r['cobrado']) ?></strong>
                    <?php if ($r['pendiente'] > 0 && $p['estado'] !== 'cancelado'): ?>
                        · <span class="text-red-600">Falta <strong><?= $pesos($r['pendiente']) ?></strong></span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($r['cobros']) && $r['saldo_aplicado'] <= 0): ?>
                <p class="px-5 py-4 text-sm text-gray-400">Todavía no se registró ningún cobro.</p>
            <?php else: ?>
                <ul class="divide-y text-sm">
                    <?php foreach ($r['cobros'] as $c): ?>
                        <?php $anulado = $c['anulado_at'] !== null; $esDev = $c['tipo'] === 'devolucion'; ?>
                        <li class="px-5 py-3 flex items-center justify-between gap-3 <?= $anulado ? 'opacity-50' : '' ?>">
                            <div class="min-w-0 <?= $anulado ? 'line-through' : '' ?>">
                                <p class="text-gray-900"><?= $esDev ? '↩️ Devolución' : '💵 Cobro' ?> · <?= htmlspecialchars($c['medio'] ?? '—') ?></p>
                                <p class="text-xs text-gray-400">
                                    <?= date('d/m/Y H:i', strtotime($c['fecha'])) ?> · a <?= htmlspecialchars($c['caja'] ?? '—') ?>
                                    <?= $c['comprobante'] ? ' · ' . htmlspecialchars($c['comprobante']) : '' ?>
                                    <?= (float) $c['comision'] > 0 ? ' · comisión ' . $pesos($c['comision']) : '' ?>
                                </p>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="font-semibold <?= $esDev ? 'text-red-600' : 'text-green-700' ?>"><?= $esDev ? '−' : '+' ?><?= $pesos($c['monto']) ?></span>
                                <?php if ($anulado): ?>
                                    <span class="text-xs text-gray-400">Anulado</span>
                                <?php elseif (!$final): ?>
                                    <?= boton_eliminar(
                                        BASE_URL . '/admin/pedido/anular-cobro',
                                        ['id_pedido' => $id, 'id_movimiento' => $c['id_movimiento']],
                                        '¿Anular este movimiento? Se descuenta de la caja.',
                                        'Anular',
                                        'text-xs text-gray-400 hover:text-red-600'
                                    ) ?>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <?php if ($r['saldo_aplicado'] > 0): ?>
                        <li class="px-5 py-3 flex justify-between text-sm">
                            <span class="text-gray-900">🎟️ Saldo a favor usado</span>
                            <span class="font-semibold text-green-700">+<?= $pesos($r['saldo_aplicado']) ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>

            <?php if (!$final): ?>
                <form method="POST" action="<?= BASE_URL ?>/admin/pedido/cobro"
                      class="px-5 py-4 border-t bg-gray-50 grid grid-cols-2 md:grid-cols-5 gap-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_pedido" value="<?= $id ?>">
                    <input type="number" name="monto" step="0.01" min="0.01" required
                           value="<?= $r['pendiente'] > 0 ? htmlspecialchars((string) $r['pendiente']) : '' ?>"
                           placeholder="Monto $" class="<?= $input ?>">
                    <select name="id_medio" required class="<?= $input ?> bg-white">
                        <option value="">Medio…</option>
                        <?php foreach ($medios as $m): ?>
                            <option value="<?= (int) $m['id_medio'] ?>" <?= (int) $p['id_medio_acordado'] === (int) $m['id_medio'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['nombre']) ?><?= (float) $m['comision_pct'] > 0 ? ' (' . pct_texto((float) $m['comision_pct']) . '% com.)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" class="<?= $input ?>">
                    <input type="text" name="comprobante" maxlength="100" placeholder="Comprobante (opc.)" class="<?= $input ?>">
                    <button class="col-span-2 md:col-span-1 bg-gray-900 text-white rounded-lg text-sm px-3 py-2 hover:bg-gray-800">Registrar cobro</button>
                </form>

                <?php if ($r['saldo_cliente'] > 0 && $r['pendiente'] > 0): ?>
                    <form method="POST" action="<?= BASE_URL ?>/admin/pedido/usar-saldo"
                          class="px-5 py-3 border-t flex flex-wrap items-center gap-2 text-sm bg-green-50"
                          onsubmit="return confirm('¿Usar saldo a favor del cliente para este pedido?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id_pedido" value="<?= $id ?>">
                        <span class="text-green-800">🎟️ El cliente tiene <strong><?= $pesos($r['saldo_cliente']) ?></strong> a favor.</span>
                        <input type="number" name="monto" step="0.01" min="0.01"
                               value="<?= htmlspecialchars((string) min($r['saldo_cliente'], $r['pendiente'])) ?>"
                               class="w-28 border rounded-lg px-2 py-1.5">
                        <button class="px-3 py-1.5 rounded-lg bg-green-600 text-white hover:bg-green-700">Usar saldo</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Condiciones -->
        <form method="POST" action="<?= BASE_URL ?>/admin/pedido/condiciones" class="bg-white rounded-lg shadow">
            <?= csrf_field() ?>
            <input type="hidden" name="id_pedido" value="<?= $id ?>">
            <fieldset <?= $final ? 'disabled' : '' ?>>
                <div class="px-5 py-4 border-b"><h3 class="font-bold text-gray-800">Condiciones</h3></div>

                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <label>
                        <span class="block text-gray-500 mb-1">Descuento $</span>
                        <input type="number" name="descuento" step="0.01" min="0" value="<?= htmlspecialchars((string) (float) $p['descuento']) ?>" class="<?= $input ?>">
                    </label>
                    <label>
                        <span class="block text-gray-500 mb-1">Motivo del descuento</span>
                        <input type="text" name="motivo_descuento" maxlength="150" value="<?= htmlspecialchars($p['motivo_descuento'] ?? '') ?>"
                               placeholder="Ej: cliente frecuente" class="<?= $input ?>">
                    </label>

                    <label class="md:col-span-2">
                        <span class="block text-gray-500 mb-1">Medio de pago acordado</span>
                        <select name="id_medio_acordado" class="<?= $input ?> bg-white">
                            <option value="">Sin definir</option>
                            <?php foreach ($medios as $m): ?>
                                <option value="<?= (int) $m['id_medio'] ?>" <?= (int) $p['id_medio_acordado'] === (int) $m['id_medio'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($m['nombre']) ?>
                                    <?php if ((float) $m['ajuste_pct'] != 0): ?>
                                        (<?= $m['ajuste_pct'] > 0 ? 'recargo' : 'descuento' ?> <?= pct_texto(abs((float) $m['ajuste_pct'])) ?>%)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="block text-xs text-gray-400 mt-1">Si el medio tiene recargo o descuento, se aplica solo al total.</span>
                    </label>

                    <label>
                        <span class="block text-gray-500 mb-1">Entrega</span>
                        <select name="entrega" class="<?= $input ?> bg-white">
                            <option value="">Sin definir</option>
                            <option value="retiro" <?= $p['entrega'] === 'retiro' ? 'selected' : '' ?>>Retira en el local</option>
                            <option value="envio"  <?= $p['entrega'] === 'envio'  ? 'selected' : '' ?>>Envío</option>
                        </select>
                    </label>
                    <label>
                        <span class="block text-gray-500 mb-1">Seguimiento</span>
                        <input type="text" name="seguimiento" maxlength="100" value="<?= htmlspecialchars($p['seguimiento'] ?? '') ?>"
                               placeholder="Nº de envío (opcional)" class="<?= $input ?>">
                    </label>
                    <label>
                        <span class="block text-gray-500 mb-1">Envío: le cobro al cliente $</span>
                        <input type="number" name="envio_cobrado" step="0.01" min="0" value="<?= htmlspecialchars((string) (float) $p['envio_cobrado']) ?>" class="<?= $input ?>">
                    </label>
                    <label>
                        <span class="block text-gray-500 mb-1">Envío: me cuesta $</span>
                        <input type="number" name="envio_costo" step="0.01" min="0" value="<?= htmlspecialchars((string) (float) $p['envio_costo']) ?>" class="<?= $input ?>">
                    </label>
                </div>

                <?php if (!$final): ?>
                    <div class="px-5 py-3 border-t flex justify-end">
                        <button class="bg-gray-900 text-white rounded-lg text-sm px-4 py-2 hover:bg-gray-800">Guardar condiciones</button>
                    </div>
                <?php endif; ?>
            </fieldset>
        </form>

        <!-- Historial -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-5 py-4 border-b"><h3 class="font-bold text-gray-800">Historial</h3></div>
            <?php if (empty($r['historial'])): ?>
                <p class="px-5 py-4 text-sm text-gray-400">Sin movimientos registrados.</p>
            <?php else: ?>
                <ul class="px-5 py-4 space-y-3 text-sm">
                    <?php foreach ($r['historial'] as $h): ?>
                        <li class="flex gap-3">
                            <span class="shrink-0 w-6 text-center"><?= $iconoHist[$h['tipo']] ?? '•' ?></span>
                            <div class="min-w-0">
                                <p class="text-gray-900"><?= htmlspecialchars($h['detalle']) ?></p>
                                <p class="text-xs text-gray-400">
                                    <?= date('d/m/Y H:i', strtotime($h['fecha'])) ?>
                                    <?= $h['usuario'] ? ' · ' . htmlspecialchars($h['usuario']) : '' ?>
                                </p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══ COLUMNA LATERAL ═════════════════════════════════════════════ -->
    <div class="space-y-6">

        <!-- Estado -->
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-bold text-gray-800 mb-3">Estado</h3>

            <?php if ($final): ?>
                <p class="text-sm text-gray-500">
                    Este pedido está <strong><?= strtolower(GestionPedido::etiqueta($p['estado'])) ?></strong> y ya no se puede cambiar.
                    <?php if ($p['estado'] === 'cancelado' && $p['cancelado_at']): ?>
                        <br><span class="text-xs text-gray-400">Cancelado el <?= date('d/m/Y H:i', strtotime($p['cancelado_at'])) ?></span>
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($r['transiciones'] as $destino): ?>
                        <form method="POST" action="<?= BASE_URL ?>/admin/pedido/estado"
                              onsubmit="return confirm('¿Pasar el pedido a &quot;<?= GestionPedido::etiqueta($destino) ?>&quot;?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id_pedido" value="<?= $id ?>">
                            <input type="hidden" name="estado" value="<?= $destino ?>">
                            <button class="w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-lg border text-sm hover:bg-gray-50">
                                <span class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full <?= GestionPedido::clase($destino) ?>"></span>
                                    <?= GestionPedido::etiqueta($destino) ?>
                                </span>
                                <?php if ($efecto($destino)): ?>
                                    <span class="text-xs text-gray-400"><?= $efecto($destino) ?></span>
                                <?php endif; ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>

                <!-- Cancelar -->
                <details class="mt-4 pt-4 border-t group">
                    <summary class="cursor-pointer list-none text-sm text-red-600 hover:text-red-700">✖ Cancelar pedido…</summary>

                    <form method="POST" action="<?= BASE_URL ?>/admin/pedido/cancelar" class="mt-3 space-y-3 text-sm"
                          onsubmit="return confirm('¿Cancelar el pedido #<?= $id ?>? No se puede deshacer.')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id_pedido" value="<?= $id ?>">

                        <input type="text" name="motivo" maxlength="150" placeholder="Motivo (opcional)" class="<?= $input ?>">

                        <?php $enCaja = $r['cobrado'] - $r['saldo_aplicado']; ?>
                        <?php if ($enCaja > 0): ?>
                            <p class="text-gray-700">Pagó <strong><?= $pesos($enCaja) ?></strong>. ¿Qué hacemos con eso?</p>
                            <label class="flex items-center gap-2"><input type="radio" name="destino" value="devolver" checked onchange="toggleDevolucion()"> Devolverlo</label>
                            <select name="id_medio_devolucion" id="medioDevolucion" class="<?= $input ?> bg-white">
                                <?php foreach ($medios as $m): ?>
                                    <option value="<?= (int) $m['id_medio'] ?>"><?= htmlspecialchars($m['nombre']) ?> (sale de <?= htmlspecialchars($m['caja']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($p['id_cliente']): ?>
                                <label class="flex items-center gap-2"><input type="radio" name="destino" value="saldo" onchange="toggleDevolucion()"> Dejarlo como saldo a favor</label>
                            <?php endif; ?>
                            <label class="flex items-center gap-2"><input type="radio" name="destino" value="nada" onchange="toggleDevolucion()"> No se devuelve (ej: seña perdida)</label>
                        <?php else: ?>
                            <input type="hidden" name="destino" value="nada">
                        <?php endif; ?>

                        <?php if ($r['saldo_aplicado'] > 0): ?>
                            <p class="text-xs text-gray-500">Los <?= $pesos($r['saldo_aplicado']) ?> de saldo a favor que usó vuelven a su saldo.</p>
                        <?php endif; ?>
                        <?php if (in_array($p['estado'], GestionPedido::RESERVAN, true)): ?>
                            <p class="text-xs text-gray-500">El stock reservado se libera.</p>
                        <?php endif; ?>

                        <button class="w-full bg-red-600 text-white rounded-lg px-3 py-2 hover:bg-red-700">Cancelar pedido</button>
                    </form>
                </details>
            <?php endif; ?>
        </div>

        <!-- Cliente -->
        <div class="bg-white rounded-lg shadow p-5 text-sm">
            <h3 class="font-bold text-gray-800 mb-3">Cliente</h3>

            <p class="font-medium text-gray-900">
                <?php if ($p['id_cliente']): ?>
                    <a href="<?= BASE_URL ?>/admin/cliente?id=<?= (int) $p['id_cliente'] ?>" class="hover:underline">
                        <?= htmlspecialchars(trim(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? ''))) ?: '—' ?>
                    </a>
                <?php else: ?>
                    <?= htmlspecialchars(trim(($p['nombre'] ?? '') . ' ' . ($p['apellido'] ?? ''))) ?: '—' ?>
                <?php endif; ?>
            </p>
            <div class="mt-2 space-y-1 text-gray-600">
                <?php if ($p['telefono']): ?><p>📞 <?= htmlspecialchars($p['telefono']) ?></p><?php endif; ?>
                <?php if ($p['email']): ?><p class="truncate">✉️ <?= htmlspecialchars($p['email']) ?></p><?php endif; ?>
                <?php if ($p['localidad'] || $p['direccion']): ?>
                    <p>📍 <?= htmlspecialchars(trim(($p['direccion'] ?? '') . ($p['localidad'] ? ', ' . $p['localidad'] : ''), ', ')) ?></p>
                <?php endif; ?>
            </div>

            <?php if (!empty($p['observaciones'])): ?>
                <div class="mt-3 p-3 rounded-lg bg-yellow-50 text-yellow-900 whitespace-pre-line">
                    <span class="block text-xs font-semibold mb-1">Observaciones del cliente</span>
                    <?= htmlspecialchars($p['observaciones']) ?>
                </div>
            <?php endif; ?>

            <?php if ($r['saldo_cliente'] > 0): ?>
                <p class="mt-3 text-green-700">🎟️ Saldo a favor: <strong><?= $pesos($r['saldo_cliente']) ?></strong></p>
            <?php endif; ?>

            <?php if ($linkWa): ?>
                <a href="<?= htmlspecialchars($linkWa) ?>" target="_blank" rel="noopener" onclick="marcarContactado()"
                   class="mt-4 block text-center w-full bg-green-600 text-white py-2.5 rounded-lg hover:bg-green-700">
                    💬 WhatsApp al cliente
                </a>
                <?php if ($p['estado'] === 'pendiente_contacto'): ?>
                    <p class="text-xs text-gray-400 mt-1 text-center">Al tocarlo, el pedido pasa a Contactado.</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="mt-4 bg-red-50 text-red-700 rounded-lg p-3">Este pedido no tiene teléfono cargado.</p>
            <?php endif; ?>
        </div>

        <!-- Ganancia -->
        <?php $g = $r['ganancia']; ?>
        <div class="bg-white rounded-lg shadow p-5 text-sm">
            <h3 class="font-bold text-gray-800 mb-3">Ganancia</h3>
            <div class="space-y-1.5">
                <div class="flex justify-between"><span class="text-gray-500">Productos (con descuentos)</span><span><?= $pesos($g['productos']) ?></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Costo de los productos</span><span class="text-red-600">−<?= $pesos($g['costo']) ?></span></div>
                <?php if ($g['comisiones'] > 0): ?>
                    <div class="flex justify-between"><span class="text-gray-500">Comisiones</span><span class="text-red-600">−<?= $pesos($g['comisiones']) ?></span></div>
                <?php endif; ?>
                <?php if ((float) $p['envio_cobrado'] > 0 || (float) $p['envio_costo'] > 0): ?>
                    <div class="flex justify-between"><span class="text-gray-500">Envío (cobrado − costo)</span>
                        <span class="<?= $g['envio'] < 0 ? 'text-red-600' : '' ?>"><?= $g['envio'] < 0 ? '−' : '+' ?><?= $pesos(abs($g['envio'])) ?></span></div>
                <?php endif; ?>
                <div class="flex justify-between pt-2 border-t font-bold">
                    <span>Resultado</span>
                    <span class="<?= $g['resultado'] < 0 ? 'text-red-600' : 'text-green-700' ?>"><?= $pesos($g['resultado']) ?></span>
                </div>
            </div>
            <?php if ($g['sin_costo'] > 0): ?>
                <p class="text-xs text-gray-400 mt-2"><?= (int) $g['sin_costo'] ?> unidad(es) sin costo cargado: la ganancia real es menor.</p>
            <?php endif; ?>
        </div>

        <!-- Notas internas -->
        <form method="POST" action="<?= BASE_URL ?>/admin/pedido/nota" class="bg-white rounded-lg shadow p-5 text-sm">
            <?= csrf_field() ?>
            <input type="hidden" name="id_pedido" value="<?= $id ?>">
            <h3 class="font-bold text-gray-800 mb-1">Notas internas</h3>
            <p class="text-xs text-gray-400 mb-3">Solo las ves vos. El cliente no.</p>
            <textarea name="notas_internas" rows="4" maxlength="2000" class="<?= $input ?>"
                      placeholder="Ej: pasa el sábado a retirar"><?= htmlspecialchars($p['notas_internas'] ?? '') ?></textarea>
            <button class="mt-2 w-full border rounded-lg px-3 py-2 hover:bg-gray-50">Guardar notas</button>
        </form>
    </div>
</div>

<script>
function marcarContactado() {
    try {
        const datos = new FormData();
        datos.append('csrf_token', <?= json_encode(csrf_token()) ?>);
        datos.append('id_pedido', <?= $id ?>);
        navigator.sendBeacon('<?= BASE_URL ?>/admin/pedido/contactado', datos);
        // Recargar al volver, para ver el estado nuevo
        setTimeout(() => location.reload(), 1500);
    } catch (e) {}
}

function toggleDevolucion() {
    const sel = document.getElementById('medioDevolucion');
    const dev = document.querySelector('input[name="destino"][value="devolver"]');
    if (sel && dev) sel.classList.toggle('hidden', !dev.checked);
}
</script>