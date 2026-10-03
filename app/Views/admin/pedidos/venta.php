<?php
$error = $_SESSION['venta_error'] ?? null;
$prev  = $_SESSION['venta_post'] ?? [];
unset($_SESSION['venta_error'], $_SESSION['venta_post']);

$input = 'w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-200';
$card  = 'bg-white rounded-xl shadow-sm p-5';
$v     = fn($k, $def = '') => htmlspecialchars((string) ($prev[$k] ?? $def));

$mediosJs = array_map(fn($m) => [
    'id' => (int) $m['id_medio'], 'nombre' => $m['nombre'],
    'ajuste' => (float) $m['ajuste_pct'], 'comision' => (float) $m['comision_pct'],
], $medios);
?>

<form method="POST" action="<?= BASE_URL ?>/admin/venta/guardar" id="formVenta" autocomplete="off">
<?= csrf_field() ?>

<div class="flex items-end justify-between gap-3 mb-6">
    <div>
        <a href="<?= BASE_URL ?>/admin/pedidos" class="text-sm text-gray-500 hover:text-gray-900">← Pedidos</a>
        <h2 class="text-2xl font-bold text-gray-800 mt-1">Venta manual</h2>
        <p class="text-gray-500 text-sm">Lo que vendiste por fuera de la web: en el local, por Instagram, por WhatsApp…</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="mb-6 px-4 py-3 rounded-xl text-sm border bg-red-50 text-red-700 border-red-200"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
<div class="lg:col-span-2 space-y-6">

    <!-- ══ 1. DÓNDE Y CUÁNDO ══════════════════════════════════════════ -->
    <div class="<?= $card ?>">
        <h3 class="font-bold text-gray-800 mb-3">1. ¿Dónde se vendió?</h3>
        <div class="flex flex-wrap gap-2 mb-4">
            <?php foreach (GestionPedido::ORIGENES as $clave => $texto): ?>
                <label class="cursor-pointer">
                    <input type="radio" name="origen" value="<?= $clave ?>" class="peer sr-only"
                           <?= ($prev['origen'] ?? 'local') === $clave ? 'checked' : '' ?>>
                    <span class="inline-block px-3 py-2 rounded-lg border text-sm peer-checked:bg-gray-900 peer-checked:text-white peer-checked:border-gray-900 hover:bg-gray-50"><?= $texto ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <label class="inline-flex items-center gap-2 text-sm">
            <span class="text-gray-600">Fecha</span>
            <input type="date" name="fecha" value="<?= $v('fecha', date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>" class="border rounded-lg px-2 py-1.5">
        </label>
    </div>

    <!-- ══ 2. CLIENTE ═════════════════════════════════════════════════ -->
    <div class="<?= $card ?>">
        <h3 class="font-bold text-gray-800 mb-3">2. Cliente</h3>
        <?php $tipoCliente = $prev['tipo_cliente'] ?? 'ninguno'; ?>
        <div class="grid grid-cols-3 gap-1 bg-gray-100 rounded-lg p-1 text-sm mb-4">
            <?php foreach (['ninguno' => 'Sin datos', 'existente' => 'Ya es cliente', 'nuevo' => 'Cliente nuevo'] as $clave => $texto): ?>
                <label class="cursor-pointer">
                    <input type="radio" name="tipo_cliente" value="<?= $clave ?>" class="peer sr-only" <?= $tipoCliente === $clave ? 'checked' : '' ?>>
                    <span class="block text-center py-2 rounded-md text-gray-500 peer-checked:bg-white peer-checked:shadow-sm peer-checked:text-gray-900 peer-checked:font-semibold"><?= $texto ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <div data-cliente="ninguno" class="text-sm text-gray-500">Consumidor final: la venta se carga sin datos del cliente.</div>

        <div data-cliente="existente" class="hidden relative">
            <input type="hidden" name="id_cliente" id="idCliente" value="<?= $v('id_cliente') ?>">
            <input type="text" id="buscaCliente" placeholder="🔎 Nombre o teléfono…" class="<?= $input ?>">
            <div id="resCliente" class="hidden absolute z-20 inset-x-0 mt-1 bg-white border rounded-lg shadow-lg max-h-64 overflow-y-auto"></div>
            <div id="clienteElegido" class="hidden mt-3 flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2 text-sm"></div>
        </div>

        <div data-cliente="nuevo" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-3">
            <input name="cliente[nombre]" value="<?= htmlspecialchars($prev['cliente']['nombre'] ?? '') ?>" placeholder="Nombre *" class="<?= $input ?>">
            <input name="cliente[apellido]" value="<?= htmlspecialchars($prev['cliente']['apellido'] ?? '') ?>" placeholder="Apellido" class="<?= $input ?>">
            <input name="cliente[telefono]" value="<?= htmlspecialchars($prev['cliente']['telefono'] ?? '') ?>" placeholder="Teléfono (WhatsApp)" inputmode="tel" class="<?= $input ?>">
            <input name="cliente[localidad]" value="<?= htmlspecialchars($prev['cliente']['localidad'] ?? '') ?>" placeholder="Localidad" class="<?= $input ?>">
            <p class="sm:col-span-2 text-xs text-gray-400">Si ya hay un cliente con ese teléfono, se usa ese en vez de crear otro.</p>
        </div>
    </div>

    <!-- ══ 3. PRODUCTOS ═══════════════════════════════════════════════ -->
    <div class="<?= $card ?>">
        <h3 class="font-bold text-gray-800 mb-3">3. Productos</h3>

        <div class="relative mb-4">
            <input type="text" id="buscaProducto" placeholder="🔎 Buscá por nombre, marca o SKU…" class="<?= $input ?>">
            <div id="resProducto" class="hidden absolute z-20 inset-x-0 mt-1 bg-white border rounded-lg shadow-lg max-h-80 overflow-y-auto"></div>
        </div>

        <div id="sinItems" class="text-center text-sm text-gray-400 py-6 border-2 border-dashed rounded-lg">
            Todavía no agregaste productos. Buscalos arriba.
        </div>
        <ul id="listaItems" class="divide-y"></ul>
    </div>

    <!-- ══ 4. CONDICIONES ═════════════════════════════════════════════ -->
    <div class="<?= $card ?>">
        <h3 class="font-bold text-gray-800 mb-3">4. Condiciones <span class="font-normal text-sm text-gray-400">(opcional)</span></h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <label><span class="block text-gray-500 mb-1">Descuento $</span>
                <input type="number" name="descuento" id="descuento" step="0.01" min="0" value="<?= $v('descuento', '0') ?>" class="<?= $input ?> calc"></label>
            <label><span class="block text-gray-500 mb-1">Motivo</span>
                <input name="motivo_descuento" value="<?= $v('motivo_descuento') ?>" maxlength="150" placeholder="Ej: cliente frecuente" class="<?= $input ?>"></label>
            <label class="sm:col-span-2"><span class="block text-gray-500 mb-1">Medio de pago acordado</span>
                <select name="id_medio_acordado" id="medioAcordado" class="<?= $input ?> bg-white calc">
                    <option value="">Sin recargo ni descuento</option>
                    <?php foreach ($medios as $m): ?>
                        <option value="<?= (int) $m['id_medio'] ?>" <?= (int) ($prev['id_medio_acordado'] ?? 0) === (int) $m['id_medio'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['nombre']) ?><?= (float) $m['ajuste_pct'] != 0 ? ' (' . ($m['ajuste_pct'] > 0 ? '+' : '') . pct_texto((float) $m['ajuste_pct']) . '%)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select></label>
            <label><span class="block text-gray-500 mb-1">Entrega</span>
                <select name="entrega" id="entrega" class="<?= $input ?> bg-white">
                    <option value="retiro" <?= ($prev['entrega'] ?? 'retiro') === 'retiro' ? 'selected' : '' ?>>🏪 Se lo lleva / retira</option>
                    <option value="envio"  <?= ($prev['entrega'] ?? '') === 'envio' ? 'selected' : '' ?>>🚚 Envío</option>
                </select></label>
            <div id="camposEnvio" class="hidden grid grid-cols-2 gap-3">
                <label><span class="block text-gray-500 mb-1">Le cobro $</span>
                    <input type="number" name="envio_cobrado" id="envioCobrado" step="0.01" min="0" value="<?= $v('envio_cobrado', '0') ?>" class="<?= $input ?> calc"></label>
                <label><span class="block text-gray-500 mb-1">Me cuesta $</span>
                    <input type="number" name="envio_costo" step="0.01" min="0" value="<?= $v('envio_costo', '0') ?>" class="<?= $input ?>"></label>
            </div>
            <label class="sm:col-span-2"><span class="block text-gray-500 mb-1">Notas internas</span>
                <input name="notas" value="<?= $v('notas') ?>" maxlength="500" placeholder="Ej: pasa el sábado, lo pidió por mensaje" class="<?= $input ?>"></label>
        </div>
    </div>
</div>

<!-- ══ COLUMNA DERECHA: TOTAL, COBRO Y ENTREGA ═════════════════════════ -->
<div>
    <div class="<?= $card ?> lg:sticky lg:top-6 space-y-5">
        <div>
            <h3 class="font-bold text-gray-800 mb-3">Total</h3>
            <div class="text-sm space-y-1.5">
                <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span id="tSubtotal">$0</span></div>
                <div class="flex justify-between text-green-700 hidden" id="filaDesc"><span>Descuento</span><span id="tDesc"></span></div>
                <div class="flex justify-between hidden" id="filaAjuste"><span id="tAjusteTxt"></span><span id="tAjuste"></span></div>
                <div class="flex justify-between hidden" id="filaEnvio"><span class="text-gray-500">Envío</span><span id="tEnvio"></span></div>
                <div class="flex justify-between pt-2 border-t text-xl font-bold"><span>Total</span><span id="tTotal">$0</span></div>
            </div>
        </div>

        <!-- Cobro -->
        <div class="pt-4 border-t">
            <label class="flex items-center justify-between gap-3 cursor-pointer">
                <span class="font-semibold text-gray-800">¿Ya pagó?</span>
                <input type="checkbox" name="pago" value="1" id="pago" class="w-5 h-5" <?= !$prev || !empty($prev['pago']) ? 'checked' : '' ?>>
            </label>
            <div id="camposPago" class="mt-3 space-y-2">
                <input type="number" name="cobro_monto" id="cobroMonto" step="0.01" min="0" value="<?= $v('cobro_monto') ?>" class="<?= $input ?> text-lg font-semibold">
                <select name="cobro_medio" id="cobroMedio" class="<?= $input ?> bg-white">
                    <?php foreach ($medios as $m): ?>
                        <option value="<?= (int) $m['id_medio'] ?>" <?= (int) ($prev['cobro_medio'] ?? 0) === (int) $m['id_medio'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['nombre']) ?><?= (float) $m['comision_pct'] > 0 ? ' (' . pct_texto((float) $m['comision_pct']) . '% com.)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-400">Si pagó una parte (seña), cambiá el monto. El resto se cobra después desde el pedido.</p>
            </div>
        </div>

        <!-- Entrega -->
        <div class="pt-4 border-t">
            <p class="font-semibold text-gray-800 mb-2">¿Ya se lo llevó?</p>
            <?php $entregado = $prev['entregado'] ?? '1'; ?>
            <div class="space-y-2 text-sm">
                <label class="flex items-start gap-2 border rounded-lg p-3 cursor-pointer has-[:checked]:border-gray-900 has-[:checked]:bg-gray-50">
                    <input type="radio" name="entregado" value="1" <?= $entregado === '1' ? 'checked' : '' ?> class="mt-0.5">
                    <span><strong>Sí, ya lo tiene</strong><span class="block text-xs text-gray-500">Queda Entregado y se descuenta el stock.</span></span>
                </label>
                <label class="flex items-start gap-2 border rounded-lg p-3 cursor-pointer has-[:checked]:border-gray-900 has-[:checked]:bg-gray-50">
                    <input type="radio" name="entregado" value="0" <?= $entregado === '0' ? 'checked' : '' ?> class="mt-0.5">
                    <span><strong>No, lo entrego después</strong><span class="block text-xs text-gray-500">Queda Confirmado y el stock reservado.</span></span>
                </label>
            </div>
        </div>

        <div id="itemsOcultos"></div>
        <button type="submit" id="btnGuardar" class="w-full py-3 rounded-lg bg-gray-900 text-white font-semibold hover:bg-gray-800">Guardar venta</button>
    </div>
</div>
</div>
</form>

<script>
(function () {
    const BASE   = '<?= BASE_URL ?>';
    const MEDIOS = <?= json_encode($mediosJs, JSON_HEX_TAG | JSON_HEX_APOS) ?>;
    const $      = id => document.getElementById(id);
    const pesos  = n => '$' + Math.round(n).toLocaleString('es-AR');
    const esc    = t => { const d = document.createElement('div'); d.textContent = t ?? ''; return d.innerHTML; };

    let items          = <?= json_encode(array_values(array_map(fn($it) => [
                              'id' => (int) $it['id_variante'], 'nombre' => $it['nombre'] ?? 'Producto', 'variante' => $it['variante'] ?? '',
                              'cantidad' => (int) $it['cantidad'], 'precio' => (float) str_replace(',', '.', $it['precio']),
                              'precio_lista' => (float) ($it['precio_lista'] ?? $it['precio']), 'disponible' => (int) ($it['disponible'] ?? 999),
                          ], (array) ($prev['items'] ?? [])))) ?>;
    let montoTocado    = false;

    // ── Cliente: pestañas ───────────────────────────────────────────
    function mostrarCliente() {
        const tipo = document.querySelector('input[name="tipo_cliente"]:checked').value;
        document.querySelectorAll('[data-cliente]').forEach(el => el.classList.toggle('hidden', el.dataset.cliente !== tipo));
    }
    document.querySelectorAll('input[name="tipo_cliente"]').forEach(r => r.addEventListener('change', mostrarCliente));
    mostrarCliente();

    // ── Buscador genérico con espera ────────────────────────────────
    function buscador(input, caja, url, pintar) {
        let timer = null;
        input.addEventListener('input', () => {
            clearTimeout(timer);
            const q = input.value.trim();
            if (q.length < 2) { caja.classList.add('hidden'); return; }
            timer = setTimeout(async () => {
                try {
                    const r = await fetch(url + '?q=' + encodeURIComponent(q), { credentials: 'same-origin' });
                    const j = await r.json();
                    caja.innerHTML = j.resultados.length ? j.resultados.map(pintar).join('') : '<p class="px-3 py-3 text-sm text-gray-400">Sin resultados.</p>';
                    caja.classList.remove('hidden');
                    caja._datos = j.resultados;
                } catch (e) {}
            }, 250);
        });
        document.addEventListener('click', e => { if (!caja.contains(e.target) && e.target !== input) caja.classList.add('hidden'); });
    }

    // ── Cliente existente ───────────────────────────────────────────
    buscador($('buscaCliente'), $('resCliente'), BASE + '/admin/venta/buscar-clientes', (c, i) => `
        <button type="button" data-i="${i}" class="pick-cliente w-full text-left px-3 py-2 hover:bg-gray-50 text-sm">
            <span class="font-medium">${esc(c.nombre)} ${esc(c.apellido || '')}</span>
            <span class="block text-xs text-gray-400">${esc(c.telefono || 'sin teléfono')}${c.localidad ? ' · ' + esc(c.localidad) : ''}</span>
        </button>`);

    $('resCliente').addEventListener('click', e => {
        const b = e.target.closest('.pick-cliente');
        if (!b) return;
        const c = $('resCliente')._datos[b.dataset.i];
        $('idCliente').value = c.id_cliente;
        $('clienteElegido').innerHTML = `<span>👤 <strong>${esc(c.nombre)} ${esc(c.apellido || '')}</strong> <span class="text-gray-400">${esc(c.telefono || '')}</span></span>
            <button type="button" class="text-gray-400 hover:text-red-600" onclick="document.getElementById('idCliente').value='';this.parentElement.classList.add('hidden')">✕</button>`;
        $('clienteElegido').classList.remove('hidden');
        $('resCliente').classList.add('hidden');
        $('buscaCliente').value = '';
    });

    // ── Productos ───────────────────────────────────────────────────
    buscador($('buscaProducto'), $('resProducto'), BASE + '/admin/venta/buscar-productos', (p, i) => `
        <button type="button" data-i="${i}" class="pick-producto w-full flex items-center gap-3 text-left px-3 py-2 hover:bg-gray-50 ${p.disponible <= 0 ? 'opacity-50' : ''}">
            <span class="w-10 h-12 shrink-0 rounded bg-gray-100 overflow-hidden">
                ${p.foto ? `<img src="${BASE}/public/uploads/productos/${esc(p.foto)}" class="w-full h-full object-cover">` : ''}
            </span>
            <span class="flex-1 min-w-0 text-sm">
                <span class="block font-medium truncate">${esc(p.nombre)}</span>
                <span class="block text-xs text-gray-500">${esc(p.variante || 'Única')}${p.marca ? ' · ' + esc(p.marca) : ''}</span>
            </span>
            <span class="text-right text-sm shrink-0">
                <span class="block font-semibold">${pesos(p.precio)}${p.oferta > 0 ? ' 🔥' : ''}</span>
                <span class="block text-xs ${p.disponible > 0 ? 'text-gray-400' : 'text-red-600'}">${p.disponible > 0 ? p.disponible + ' disp.' : 'Sin stock'}</span>
            </span>
        </button>`);

    $('resProducto').addEventListener('click', e => {
        const b = e.target.closest('.pick-producto');
        if (!b) return;
        const p = $('resProducto')._datos[b.dataset.i];
        const ya = items.find(it => it.id === p.id);
        if (ya) {
            ya.cantidad++;
        } else {
            items.push({ id: p.id, nombre: p.nombre, variante: p.variante, cantidad: 1, precio: p.precio, precio_lista: p.precio_lista, disponible: p.disponible });
        }
        $('resProducto').classList.add('hidden');
        $('buscaProducto').value = '';
        $('buscaProducto').focus();
        pintarItems();
    });

    function pintarItems() {
        $('sinItems').classList.toggle('hidden', items.length > 0);
        $('listaItems').innerHTML = items.map((it, i) => `
            <li class="py-3 flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[10rem]">
                    <p class="font-medium text-sm">${esc(it.nombre)}</p>
                    <p class="text-xs ${it.cantidad > it.disponible ? 'text-red-600' : 'text-gray-500'}">
                        ${esc(it.variante || 'Única')} · ${it.disponible} disponible${it.disponible !== 1 ? 's' : ''}
                        ${it.cantidad > it.disponible ? '· ⚠️ no alcanza el stock' : ''}
                    </p>
                </div>
                <div class="flex items-center border rounded-lg">
                    <button type="button" class="px-2.5 py-1 text-gray-500" data-menos="${i}">−</button>
                    <span class="w-8 text-center text-sm font-medium">${it.cantidad}</span>
                    <button type="button" class="px-2.5 py-1 text-gray-500" data-mas="${i}">+</button>
                </div>
                <input type="number" step="0.01" min="0" value="${it.precio}" data-precio="${i}"
                       class="w-28 border rounded-lg px-2 py-1 text-sm text-right" title="Precio unitario">
                <span class="w-24 text-right font-semibold text-sm">${pesos(it.precio * it.cantidad)}</span>
                <button type="button" class="text-gray-300 hover:text-red-600 px-1" data-quitar="${i}" title="Quitar">✕</button>
            </li>`).join('');
        calcular();
    }

    $('listaItems').addEventListener('click', e => {
        const t = e.target;
        if (t.dataset.mas !== undefined)   { items[t.dataset.mas].cantidad++; pintarItems(); }
        if (t.dataset.menos !== undefined) { const it = items[t.dataset.menos]; if (it.cantidad > 1) { it.cantidad--; pintarItems(); } }
        if (t.dataset.quitar !== undefined){ items.splice(t.dataset.quitar, 1); pintarItems(); }
    });
    $('listaItems').addEventListener('input', e => {
        if (e.target.dataset.precio === undefined) return;
        items[e.target.dataset.precio].precio = parseFloat(e.target.value) || 0;
        calcular();
        // Actualizar solo el subtotal de esa fila (sin redibujar, para no perder el foco)
        const fila = e.target.closest('li');
        fila.querySelector('.w-24').textContent = pesos(items[e.target.dataset.precio].precio * items[e.target.dataset.precio].cantidad);
    });

    // ── Totales ─────────────────────────────────────────────────────
    function calcular() {
        const subtotal = items.reduce((s, it) => s + it.precio * it.cantidad, 0);
        const desc     = Math.min(parseFloat($('descuento').value) || 0, subtotal);
        const medio    = MEDIOS.find(m => String(m.id) === $('medioAcordado').value);
        const ajuste   = medio ? Math.round((subtotal - desc) * medio.ajuste) / 100 : 0;
        const envio    = $('entrega').value === 'envio' ? (parseFloat($('envioCobrado').value) || 0) : 0;
        const total    = Math.max(0, subtotal - desc + ajuste + envio);

        $('tSubtotal').textContent = pesos(subtotal);
        $('filaDesc').classList.toggle('hidden', desc <= 0);
        $('tDesc').textContent = '−' + pesos(desc);
        $('filaAjuste').classList.toggle('hidden', !ajuste);
        $('tAjusteTxt').textContent = medio ? (ajuste > 0 ? 'Recargo ' : 'Descuento ') + medio.nombre : '';
        $('tAjuste').textContent = (ajuste > 0 ? '+' : '−') + pesos(Math.abs(ajuste));
        $('filaEnvio').classList.toggle('hidden', envio <= 0);
        $('tEnvio').textContent = '+' + pesos(envio);
        $('tTotal').textContent = pesos(total);

        if (!montoTocado) $('cobroMonto').value = Math.round(total * 100) / 100;
        $('camposEnvio').classList.toggle('hidden', $('entrega').value !== 'envio');
    }

    document.querySelectorAll('.calc').forEach(el => { el.addEventListener('input', calcular); el.addEventListener('change', calcular); });
    $('entrega').addEventListener('change', calcular);
    $('cobroMonto').addEventListener('input', () => { montoTocado = true; });

    // Medio acordado → mismo medio para el cobro
    $('medioAcordado').addEventListener('change', () => { if ($('medioAcordado').value) $('cobroMedio').value = $('medioAcordado').value; });

    const togglePago = () => $('camposPago').classList.toggle('hidden', !$('pago').checked);
    $('pago').addEventListener('change', togglePago);
    togglePago();

    // ── Enviar ──────────────────────────────────────────────────────
    $('formVenta').addEventListener('submit', e => {
        if (!items.length) {
            e.preventDefault();
            alert('Agregá al menos un producto.');
            return;
        }
        if (document.querySelector('input[name="tipo_cliente"]:checked').value === 'existente' && !$('idCliente').value) {
            e.preventDefault();
            alert('Elegí el cliente, o cambiá a "Sin datos" o "Cliente nuevo".');
            return;
        }
        if (document.querySelector('input[name="tipo_cliente"]:checked').value === 'nuevo'
            && !document.querySelector('input[name="cliente[nombre]"]').value.trim()) {
            e.preventDefault();
            alert('Escribí al menos el nombre del cliente nuevo.');
            return;
        }
        $('itemsOcultos').innerHTML = items.map((it, i) => `
            <input type="hidden" name="items[${i}][id_variante]" value="${it.id}">
            <input type="hidden" name="items[${i}][cantidad]" value="${it.cantidad}">
            <input type="hidden" name="items[${i}][precio]" value="${it.precio}">
            <input type="hidden" name="items[${i}][nombre]" value="${esc(it.nombre)}">
            <input type="hidden" name="items[${i}][variante]" value="${esc(it.variante)}">
            <input type="hidden" name="items[${i}][precio_lista]" value="${it.precio_lista}">
            <input type="hidden" name="items[${i}][disponible]" value="${it.disponible}">`).join('');
        $('btnGuardar').disabled = true;
        $('btnGuardar').textContent = 'Guardando…';
    });

    pintarItems();
})();
</script>