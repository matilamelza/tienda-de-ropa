<?php
$msg = $_SESSION['caja_msg'] ?? null;
unset($_SESSION['caja_msg']);

$input = 'w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-200';
$pesos = fn($n) => '$' . number_format((float) $n, 2, ',', '.');
$pct   = fn($n) => rtrim(rtrim(number_format(abs((float) $n), 2, ',', ''), '0'), ',');

$catGastos   = array_values(array_filter($categorias, fn($c) => $c['tipo'] === 'gasto'));
$catIngresos = array_values(array_filter($categorias, fn($c) => $c['tipo'] === 'ingreso'));

$manija = '<span class="manija cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 px-1 select-none" title="Arrastrá para ordenar">⋮⋮</span>';
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Configuración de caja</h2>
        <p class="text-gray-500 text-sm">Cómo te pagan, dónde queda la plata y en qué se va.</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/caja" class="self-start px-4 py-2 rounded-lg border bg-white text-gray-700 text-sm">← Volver a Caja</a>
</div>

<?php if ($msg): ?>
    <div class="mb-4 px-4 py-3 rounded-xl text-sm border
        <?= $msg['tipo'] === 'ok' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
        <?= htmlspecialchars($msg['texto']) ?>
    </div>
<?php endif; ?>

<!-- ══ PESTAÑAS ════════════════════════════════════════════════════════ -->
<div class="flex gap-1 border-b mb-6 overflow-x-auto">
    <button type="button" data-tab="medios"     class="tab-cfg px-4 py-2 text-sm whitespace-nowrap border-b-2 -mb-px">💳 Medios de pago</button>
    <button type="button" data-tab="cajas"      class="tab-cfg px-4 py-2 text-sm whitespace-nowrap border-b-2 -mb-px">🏦 Cajas</button>
    <button type="button" data-tab="categorias" class="tab-cfg px-4 py-2 text-sm whitespace-nowrap border-b-2 -mb-px">🏷️ Categorías</button>
</div>

<!-- ══ MEDIOS DE PAGO ══════════════════════════════════════════════════ -->
<section data-panel="medios" class="panel-cfg max-w-3xl">
    <div class="flex items-start justify-between gap-3 mb-3">
        <p class="text-sm text-gray-500">
            Cómo te pueden pagar. Cada medio deja la plata en una caja y puede tener comisión y recargo o descuento.
        </p>
        <button type="button" onclick="abrirMedio()" class="shrink-0 px-3 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">+ Nuevo</button>
    </div>

    <ul id="listaMedios" class="bg-white rounded-lg shadow divide-y">
        <?php foreach ($medios as $m): ?>
            <li data-id="<?= (int) $m['id_medio'] ?>" class="flex items-center gap-3 px-3 py-3 <?= $m['activo'] ? '' : 'bg-gray-50' ?>">
                <?= $manija ?>
                <div class="flex-1 min-w-0 <?= $m['activo'] ? '' : 'opacity-50' ?>">
                    <p class="font-medium text-gray-900 truncate">
                        <?= htmlspecialchars($m['nombre']) ?>
                        <?php if (!$m['activo']): ?><span class="ml-1 text-xs font-normal text-gray-500">· inactivo</span><?php endif; ?>
                    </p>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1 text-xs">
                        <span class="text-gray-400">→ <?= htmlspecialchars($m['caja']) ?></span>
                        <?php if ((float) $m['comision_pct'] > 0): ?>
                            <span class="px-2 py-0.5 rounded-full bg-orange-50 text-orange-700">Comisión <?= $pct($m['comision_pct']) ?>%</span>
                        <?php endif; ?>
                        <?php if ((float) $m['ajuste_pct'] > 0): ?>
                            <span class="px-2 py-0.5 rounded-full bg-red-50 text-red-700">Recargo <?= $pct($m['ajuste_pct']) ?>%</span>
                        <?php elseif ((float) $m['ajuste_pct'] < 0): ?>
                            <span class="px-2 py-0.5 rounded-full bg-green-50 text-green-700">Descuento <?= $pct($m['ajuste_pct']) ?>%</span>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="button" class="text-sm text-gray-500 hover:text-gray-900 px-2 py-1"
                        onclick='abrirMedio(<?= json_encode([
                            "id" => (int) $m["id_medio"], "nombre" => $m["nombre"], "id_caja" => (int) $m["id_caja"],
                            "comision" => (float) $m["comision_pct"], "ajuste" => (float) $m["ajuste_pct"],
                            "orden" => (int) $m["orden"], "activo" => (int) $m["activo"],
                        ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Editar</button>
            </li>
        <?php endforeach; ?>
    </ul>
    <p class="text-xs text-gray-400 mt-2">Arrastrá ⋮⋮ para cambiar el orden en que aparecen al cobrar.</p>
</section>

<!-- ══ CAJAS ═══════════════════════════════════════════════════════════ -->
<section data-panel="cajas" class="panel-cfg hidden max-w-3xl">
    <div class="flex items-start justify-between gap-3 mb-3">
        <p class="text-sm text-gray-500">
            Cada lugar donde tenés plata: el cajón, el banco, Mercado Pago… Una caja con movimientos no se borra: se desactiva.
        </p>
        <button type="button" onclick="abrirCaja()" class="shrink-0 px-3 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">+ Nueva</button>
    </div>

    <ul id="listaCajas" class="bg-white rounded-lg shadow divide-y">
        <?php foreach ($cajas as $c): ?>
            <li data-id="<?= (int) $c['id_caja'] ?>" class="flex items-center gap-3 px-3 py-3 <?= $c['activo'] ? '' : 'bg-gray-50' ?>">
                <?= $manija ?>
                <div class="flex-1 min-w-0 <?= $c['activo'] ? '' : 'opacity-50' ?>">
                    <p class="font-medium text-gray-900 truncate">
                        <?= htmlspecialchars($c['nombre']) ?>
                        <?php if (!$c['activo']): ?><span class="ml-1 text-xs font-normal text-gray-500">· inactiva</span><?php endif; ?>
                    </p>
                </div>
                <span class="text-sm font-semibold whitespace-nowrap <?= $c['saldo'] < 0 ? 'text-red-600' : 'text-gray-900' ?>"><?= $pesos($c['saldo']) ?></span>
                <button type="button" class="text-sm text-gray-500 hover:text-gray-900 px-2 py-1"
                        onclick='abrirCaja(<?= json_encode([
                            "id" => (int) $c["id_caja"], "nombre" => $c["nombre"],
                            "orden" => (int) $c["orden"], "activo" => (int) $c["activo"],
                        ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Editar</button>
            </li>
        <?php endforeach; ?>
    </ul>
    <p class="text-xs text-gray-400 mt-2">
        Arrastrá ⋮⋮ para ordenarlas. El saldo inicial se carga desde Caja → "Ajustar saldo".
    </p>
</section>

<!-- ══ CATEGORÍAS ══════════════════════════════════════════════════════ -->
<section data-panel="categorias" class="panel-cfg hidden max-w-3xl">
    <p class="text-sm text-gray-500 mb-4">Para agrupar los gastos e ingresos que no son ventas. Tocá una para editarla.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach (['gasto' => ['➖ Gastos', $catGastos], 'ingreso' => ['➕ Ingresos', $catIngresos]] as $tipo => [$titulo, $lista]): ?>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-800"><?= $titulo ?></h3>
                    <button type="button" onclick='abrirCategoria({"tipo": "<?= $tipo ?>"})'
                            class="text-sm text-blue-600 hover:underline">+ Nueva</button>
                </div>
                <?php if (empty($lista)): ?>
                    <p class="text-sm text-gray-400">Todavía no hay.</p>
                <?php else: ?>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($lista as $cat): ?>
                            <button type="button"
                                    onclick='abrirCategoria(<?= json_encode([
                                        "id" => (int) $cat["id_categoria_mov"], "nombre" => $cat["nombre"],
                                        "tipo" => $cat["tipo"], "activo" => (int) $cat["activo"],
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                    class="px-3 py-1.5 rounded-full border text-sm hover:border-gray-400
                                           <?= $cat['activo'] ? 'bg-white text-gray-800' : 'bg-gray-50 text-gray-400 line-through' ?>">
                                <?= htmlspecialchars($cat['nombre']) ?>
                                <span class="text-xs text-gray-400 no-underline"><?= (int) $cat['cant_movimientos'] ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="text-xs text-gray-400 mt-2">El número al lado de cada una es cuántos movimientos tiene. Las tachadas están inactivas.</p>
</section>

<!-- ══ MODAL ═══════════════════════════════════════════════════════════ -->
<div id="modalCfg" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/40" onclick="cerrarModal()"></div>
    <div class="absolute inset-x-0 bottom-0 md:inset-0 md:flex md:items-center md:justify-center md:p-6 pointer-events-none">
        <div class="pointer-events-auto bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-xl">

            <!-- Medio de pago -->
            <form id="formMedio" method="POST" action="<?= BASE_URL ?>/admin/caja/config/medio" class="hidden p-5 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="id">
                <input type="hidden" name="orden">
                <h3 class="text-lg font-bold text-gray-800" data-titulo></h3>

                <label class="block">
                    <span class="block text-sm font-medium text-gray-700 mb-1">Nombre</span>
                    <input name="nombre" required maxlength="60" placeholder="Ej: Transferencia, Mercado Pago, Tarjeta" class="<?= $input ?>">
                </label>

                <label class="block">
                    <span class="block text-sm font-medium text-gray-700 mb-1">¿A qué caja entra la plata?</span>
                    <select name="id_caja" required class="<?= $input ?> bg-white">
                        <?php foreach ($cajasActivas as $c): ?>
                            <option value="<?= (int) $c['id_caja'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="grid grid-cols-2 gap-3">
                    <label class="block">
                        <span class="block text-sm font-medium text-gray-700 mb-1">Comisión</span>
                        <span class="relative block">
                            <input type="number" step="0.01" min="0" max="99" name="comision_pct" class="<?= $input ?> pr-7">
                            <span class="absolute right-3 top-2 text-sm text-gray-400">%</span>
                        </span>
                        <span class="block text-xs text-gray-400 mt-1">Lo que se queda Mercado Pago o el posnet.</span>
                    </label>
                    <label class="block">
                        <span class="block text-sm font-medium text-gray-700 mb-1">Recargo o descuento</span>
                        <span class="relative block">
                            <input type="number" step="0.01" min="-99" max="100" name="ajuste_pct" class="<?= $input ?> pr-7">
                            <span class="absolute right-3 top-2 text-sm text-gray-400">%</span>
                        </span>
                        <span class="block text-xs text-gray-400 mt-1">Al cliente. <code>10</code> = recargo, <code>-10</code> = descuento.</span>
                    </label>
                </div>

                <?= '' ?>
                <label class="flex items-center justify-between gap-3 py-1">
                    <span class="text-sm text-gray-700">Activo <span class="block text-xs text-gray-400">Si lo desactivás, no aparece al cobrar.</span></span>
                    <input type="checkbox" name="activo" class="w-5 h-5">
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="cerrarModal()" class="px-4 py-2 rounded-lg border text-sm">Cancelar</button>
                    <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">Guardar</button>
                </div>
            </form>

            <!-- Caja -->
            <form id="formCaja" method="POST" action="<?= BASE_URL ?>/admin/caja/config/caja" class="hidden p-5 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="id">
                <input type="hidden" name="orden">
                <h3 class="text-lg font-bold text-gray-800" data-titulo></h3>

                <label class="block">
                    <span class="block text-sm font-medium text-gray-700 mb-1">Nombre</span>
                    <input name="nombre" required maxlength="60" placeholder="Ej: Efectivo, Banco, Caja chica" class="<?= $input ?>">
                </label>

                <label class="flex items-center justify-between gap-3 py-1">
                    <span class="text-sm text-gray-700">Activa <span class="block text-xs text-gray-400">Si la desactivás, no aparece para nuevos movimientos.</span></span>
                    <input type="checkbox" name="activo" class="w-5 h-5">
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="cerrarModal()" class="px-4 py-2 rounded-lg border text-sm">Cancelar</button>
                    <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">Guardar</button>
                </div>
            </form>

            <!-- Categoría -->
            <form id="formCategoria" method="POST" action="<?= BASE_URL ?>/admin/caja/config/categoria" class="hidden p-5 space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="id">
                <h3 class="text-lg font-bold text-gray-800" data-titulo></h3>

                <label class="block">
                    <span class="block text-sm font-medium text-gray-700 mb-1">Nombre</span>
                    <input name="nombre" required maxlength="60" placeholder="Ej: Envíos, Alquiler, Publicidad" class="<?= $input ?>">
                </label>

                <div>
                    <span class="block text-sm font-medium text-gray-700 mb-1">Tipo</span>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 border rounded-lg px-3 py-2 cursor-pointer has-[:checked]:border-gray-900">
                            <input type="radio" name="tipo" value="gasto"> ➖ Gasto
                        </label>
                        <label class="flex items-center gap-2 border rounded-lg px-3 py-2 cursor-pointer has-[:checked]:border-gray-900">
                            <input type="radio" name="tipo" value="ingreso"> ➕ Ingreso
                        </label>
                    </div>
                </div>

                <label class="flex items-center justify-between gap-3 py-1">
                    <span class="text-sm text-gray-700">Activa</span>
                    <input type="checkbox" name="activo" class="w-5 h-5">
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="cerrarModal()" class="px-4 py-2 rounded-lg border text-sm">Cancelar</button>
                    <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Aviso flotante -->
<div id="toast" class="hidden fixed bottom-4 left-1/2 -translate-x-1/2 z-50 bg-gray-900 text-white text-sm px-4 py-2 rounded-lg shadow-lg"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const CSRF      = <?= json_encode(csrf_token()) ?>;
    const URL_ORDEN = '<?= BASE_URL ?>/admin/caja/config/orden';
    const $         = id => document.getElementById(id);

    // ── Pestañas (se recuerda la última con #medios / #cajas / #categorias) ──
    function mostrarTab(nombre) {
        document.querySelectorAll('.tab-cfg').forEach(t => {
            const activa = t.dataset.tab === nombre;
            t.classList.toggle('border-gray-900', activa);
            t.classList.toggle('text-gray-900', activa);
            t.classList.toggle('font-semibold', activa);
            t.classList.toggle('border-transparent', !activa);
            t.classList.toggle('text-gray-500', !activa);
        });
        document.querySelectorAll('.panel-cfg').forEach(p => p.classList.toggle('hidden', p.dataset.panel !== nombre));
        history.replaceState(null, '', '#' + nombre);
    }
    document.querySelectorAll('.tab-cfg').forEach(t => t.addEventListener('click', () => mostrarTab(t.dataset.tab)));
    mostrarTab(['medios', 'cajas', 'categorias'].includes(location.hash.slice(1)) ? location.hash.slice(1) : 'medios');

    // ── Ordenar arrastrando (se guarda solo) ──────────────────────
    function toast(texto) {
        const t = $('toast');
        t.textContent = texto;
        t.classList.remove('hidden');
        clearTimeout(t._timer);
        t._timer = setTimeout(() => t.classList.add('hidden'), 1800);
    }

    function activarOrden(lista, tipo) {
        if (!lista || !window.Sortable) return;
        Sortable.create(lista, {
            handle: '.manija',
            animation: 150,
            ghostClass: 'opacity-40',
            onEnd: async () => {
                const datos = new FormData();
                datos.append('csrf_token', CSRF);
                datos.append('tipo', tipo);
                [...lista.children].forEach(li => datos.append('ids[]', li.dataset.id));
                try {
                    const r = await fetch(URL_ORDEN, { method: 'POST', body: datos, credentials: 'same-origin' });
                    const j = await r.json();
                    toast(j.ok ? '✓ Orden guardado' : 'No se pudo guardar el orden');
                } catch (e) {
                    toast('No se pudo guardar el orden. Recargá la página.');
                }
            }
        });
    }
    activarOrden($('listaMedios'), 'medios');
    activarOrden($('listaCajas'), 'cajas');

    // ── Modal ─────────────────────────────────────────────────────
    const modal = $('modalCfg');
    const forms = ['formMedio', 'formCaja', 'formCategoria'];

    function abrir(idForm, titulo, valores) {
        forms.forEach(f => $(f).classList.toggle('hidden', f !== idForm));
        const form = $(idForm);
        form.reset();
        form.querySelector('[data-titulo]').textContent = titulo;

        Object.entries(valores).forEach(([k, v]) => {
            const campos = form.querySelectorAll(`[name="${k}"]`);
            campos.forEach(c => {
                if (c.type === 'checkbox') c.checked = !!v;
                else if (c.type === 'radio') c.checked = c.value === v;
                else c.value = v;
            });
        });

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(() => form.querySelector('[name="nombre"]').focus(), 50);
    }

    window.cerrarModal = function () {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    };
    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });

    window.abrirMedio = function (m) {
        m = m || {};
        abrir('formMedio', m.id ? 'Editar medio de pago' : 'Nuevo medio de pago', {
            id: m.id || 0, orden: m.orden || 0, nombre: m.nombre || '',
            id_caja: m.id_caja || $('formMedio').querySelector('[name="id_caja"] option')?.value || '',
            comision_pct: m.comision ?? 0, ajuste_pct: m.ajuste ?? 0,
            activo: m.id ? m.activo : 1,
        });
    };

    window.abrirCaja = function (c) {
        c = c || {};
        abrir('formCaja', c.id ? 'Editar caja' : 'Nueva caja', {
            id: c.id || 0, orden: c.orden || 0, nombre: c.nombre || '',
            activo: c.id ? c.activo : 1,
        });
    };

    window.abrirCategoria = function (c) {
        c = c || {};
        abrir('formCategoria', c.id ? 'Editar categoría' : 'Nueva categoría', {
            id: c.id || 0, nombre: c.nombre || '', tipo: c.tipo || 'gasto',
            activo: c.id ? c.activo : 1,
        });
    };

    // Al guardar, volver a la misma pestaña
    forms.forEach(f => $(f).addEventListener('submit', () => {
        sessionStorage.setItem('tabCfg', location.hash);
    }));
    const tabGuardada = sessionStorage.getItem('tabCfg');
    if (tabGuardada) {
        sessionStorage.removeItem('tabCfg');
        mostrarTab(tabGuardada.slice(1));
    }
})();
</script>