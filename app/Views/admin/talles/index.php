<?php
$lista = [];
if ($talles && $talles->num_rows > 0) {
    while ($t = $talles->fetch_assoc()) {
        $lista[] = $t;
    }
}

$msgs = [
    'creado'      => 'Talle creado. Quedó al final: arrastralo adonde va, o usá "Ordenar automáticamente".',
    'actualizado' => 'Talle actualizado.',
    'eliminado'   => 'Talle eliminado.',
];
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Talles</h2>
        <p class="text-gray-500 text-sm"><?= count($lista) ?> talle<?= count($lista) !== 1 ? 's' : '' ?> · el orden de acá es el de la tienda</p>
    </div>
    <div class="flex gap-2 self-start sm:self-auto">
        <?php if (count($lista) > 1): ?>
            <button type="button" onclick="ordenarAutomatico()" class="px-4 py-2 rounded-lg border bg-white text-gray-700 hover:bg-gray-50 text-sm">
                Ordenar automáticamente
            </button>
        <?php endif; ?>
        <button type="button" onclick="abrirTalle()" class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800">+ Nuevo talle</button>
    </div>
</div>

<?php if (isset($_GET['ok'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm max-w-3xl">
        <?= htmlspecialchars($msgs[$_GET['ok']] ?? 'Listo.') ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm max-w-3xl">
        <?php if ($_GET['error'] === 'en_uso'): ?>
            No podés eliminar este talle porque hay variantes que lo usan. Si no lo querés ofrecer más, desactivalo.
        <?php elseif ($_GET['error'] === 'duplicado'): ?>
            Ya existe un talle con ese nombre.
        <?php else: ?>
            No se pudo completar la operación. Revisá que el nombre no esté vacío.
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Aviso del orden automático (antes de guardar) -->
<div id="avisoAuto" class="hidden max-w-3xl mb-3 px-4 py-3 rounded-xl text-sm border bg-blue-50 border-blue-200 text-blue-800 flex flex-wrap items-center justify-between gap-2">
    <span>Así quedaría el orden. ¿Lo guardamos?</span>
    <span class="flex gap-2">
        <button type="button" onclick="location.reload()" class="px-3 py-1.5 rounded-lg border border-blue-200 bg-white">Cancelar</button>
        <button type="button" onclick="guardarOrden(true)" class="px-3 py-1.5 rounded-lg bg-gray-900 text-white">Guardar orden</button>
    </span>
</div>

<?php if (empty($lista)): ?>
    <div class="bg-white rounded-lg shadow px-4 py-12 text-center text-gray-400 text-sm max-w-3xl">
        Todavía no hay talles. <button type="button" onclick="abrirTalle()" class="underline">Crear el primero</button>.
    </div>
<?php else: ?>

    <div class="bg-white rounded-lg shadow overflow-hidden max-w-3xl">
        <div class="hidden md:grid grid-cols-12 gap-3 px-4 py-3 bg-gray-100 text-gray-700 text-sm font-semibold">
            <div class="col-span-6">Talle</div>
            <div class="col-span-2 text-center">Variantes</div>
            <div class="col-span-2 text-center">Estado</div>
            <div class="col-span-2 text-right">Acciones</div>
        </div>

        <ul id="listaTalles" class="divide-y">
            <?php foreach ($lista as $pos => $t): ?>
                <?php
                $id     = (int) $t['id_talle'];
                $enUso  = (int) $t['cantidad_variantes'];
                $activo = (int) $t['activo'] === 1;
                ?>
                <li data-id="<?= $id ?>" data-nombre="<?= htmlspecialchars($t['nombre']) ?>"
                    class="grid grid-cols-12 gap-3 items-center px-4 py-2.5 hover:bg-gray-50">

                    <div class="col-span-12 md:col-span-6 flex items-center gap-3">
                        <span class="manija cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 select-none" title="Arrastrá para ordenar">⋮⋮</span>
                        <span class="posicion w-6 text-xs text-gray-400 text-right shrink-0"><?= $pos + 1 ?></span>
                        <span class="inline-flex items-center justify-center min-w-[2.75rem] px-2 py-1 rounded-lg border font-semibold text-gray-900 <?= $activo ? '' : 'opacity-40' ?>">
                            <?= htmlspecialchars($t['nombre']) ?>
                        </span>
                    </div>

                    <div class="col-span-4 md:col-span-2 md:text-center text-sm">
                        <?= $enUso > 0 ? '<span class="text-gray-700">' . $enUso . ' variante' . ($enUso !== 1 ? 's' : '') . '</span>' : '<span class="text-gray-300">Sin uso</span>' ?>
                    </div>

                    <div class="col-span-4 md:col-span-2 md:text-center">
                        <button type="button" class="btn-estado px-2 py-1 text-xs rounded whitespace-nowrap <?= $activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"
                                data-id="<?= $id ?>" title="Tocá para <?= $activo ? 'desactivar' : 'activar' ?>">
                            <?= $activo ? 'Activo' : 'Inactivo' ?>
                        </button>
                    </div>

                    <div class="col-span-4 md:col-span-2 text-right whitespace-nowrap text-sm">
                        <button type="button" class="text-gray-600 hover:text-gray-900 mr-2"
                                onclick='abrirTalle(<?= json_encode(["id" => $id, "nombre" => $t["nombre"], "orden" => (int) $t["orden"], "activo" => $activo ? 1 : 0], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                            Editar
                        </button>
                        <?php if ($enUso === 0): ?>
                            <?= boton_eliminar(BASE_URL . '/admin/talles/eliminar', ['id' => $id], '¿Eliminar el talle "' . $t['nombre'] . '"?', 'Eliminar', 'text-red-500 hover:text-red-700') ?>
                        <?php else: ?>
                            <span class="text-gray-300 cursor-not-allowed" title="Hay variantes que usan este talle">Eliminar</span>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <p class="text-xs text-gray-400 mt-2 max-w-3xl">
        Arrastrá ⋮⋮ para ordenar. Un talle inactivo no aparece al cargar variantes nuevas; las que ya lo usan no cambian.
    </p>
<?php endif; ?>

<!-- ══ Ventana: nuevo / editar ═══════════════════════════════════════ -->
<div id="modalTalle" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/40" onclick="cerrarTalle()"></div>
    <div class="absolute inset-x-0 bottom-0 md:inset-0 md:flex md:items-center md:justify-center md:p-6 pointer-events-none">
        <form method="POST" id="formTalle"
              class="pointer-events-auto bg-white w-full md:max-w-sm rounded-t-2xl md:rounded-2xl shadow-xl p-5 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="id_talle" id="talleId">
            <input type="hidden" name="orden" id="talleOrden">

            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800" id="talleTitulo"></h3>
                <button type="button" onclick="cerrarTalle()" class="text-gray-400 hover:text-gray-900 text-xl leading-none">✕</button>
            </div>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Talle</span>
                <input type="text" name="nombre" id="talleNombre" required maxlength="20" placeholder="Ej: 38, M, XL, Único"
                       class="w-full border rounded-lg px-3 py-2 text-lg font-semibold focus:outline-none focus:ring-2 focus:ring-gray-200">
            </label>

            <label class="flex items-center justify-between gap-3 py-1">
                <span class="text-sm text-gray-700">Activo <span class="block text-xs text-gray-400">Si lo desactivás, no aparece al cargar variantes nuevas.</span></span>
                <input type="checkbox" name="activo" id="talleActivo" class="w-5 h-5">
            </label>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="cerrarTalle()" class="px-4 py-2 rounded-lg border text-sm">Cancelar</button>
                <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800" id="talleBoton"></button>
            </div>
        </form>
    </div>
</div>

<div id="toastTalle" class="hidden fixed bottom-4 left-1/2 -translate-x-1/2 z-50 bg-gray-900 text-white text-sm px-4 py-2 rounded-lg shadow-lg"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const CSRF  = <?= json_encode(csrf_token()) ?>;
    const BASE  = '<?= BASE_URL ?>';
    const $     = id => document.getElementById(id);
    const lista = $('listaTalles');

    function toast(texto) {
        const t = $('toastTalle');
        t.textContent = texto;
        t.classList.remove('hidden');
        clearTimeout(t._timer);
        t._timer = setTimeout(() => t.classList.add('hidden'), 1800);
    }

    async function enviar(url, datos) {
        datos.append('csrf_token', CSRF);
        const r = await fetch(url, { method: 'POST', body: datos, credentials: 'same-origin' });
        return r.json();
    }

    function renumerar() {
        lista.querySelectorAll('li').forEach((li, i) => { li.querySelector('.posicion').textContent = i + 1; });
    }

    window.guardarOrden = async function (desdeAuto) {
        renumerar();
        const datos = new FormData();
        lista.querySelectorAll('li').forEach(li => datos.append('ids[]', li.dataset.id));
        try {
            const j = await enviar(BASE + '/admin/talles/ordenar', datos);
            toast(j.ok ? '✓ Orden guardado' : 'No se pudo guardar el orden');
            if (j.ok && desdeAuto) $('avisoAuto').classList.add('hidden');
        } catch (e) {
            toast('No se pudo guardar el orden. Recargá la página.');
        }
    };

    if (lista && window.Sortable) {
        Sortable.create(lista, { handle: '.manija', animation: 150, ghostClass: 'opacity-40', onEnd: () => guardarOrden(false) });
    }

    // ── Ordenar automáticamente: números de menor a mayor, letras en orden de talle, el resto al final ──
    const LETRAS = ['XXXS', 'XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL', '2XL', '3XL', '4XL', '5XL'];

    function claveOrden(nombre) {
        const n = nombre.trim().toUpperCase().replace(',', '.');
        if (/^\d+(\.\d+)?$/.test(n)) return [0, parseFloat(n)];                              // números: 17, 18, 38.5…
        const i = LETRAS.indexOf(n.replace(/\s+/g, ''));
        if (i >= 0) return [1, i];                                                            // letras: S, M, L…
        const rango = n.match(/^(\d+)\s*[-\/AL]\s*(\d+)/);                                     // rangos: 2-4, 6/8
        if (rango) return [0, parseFloat(rango[1]) + 0.1];
        return [2, 0];                                                                        // Único y demás: al final
    }

    window.ordenarAutomatico = function () {
        const items = [...lista.querySelectorAll('li')];
        items.sort((a, b) => {
            const ka = claveOrden(a.dataset.nombre), kb = claveOrden(b.dataset.nombre);
            return ka[0] - kb[0] || ka[1] - kb[1] || a.dataset.nombre.localeCompare(b.dataset.nombre);
        });
        items.forEach(li => lista.appendChild(li));
        renumerar();
        $('avisoAuto').classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // ── Activo / Inactivo con un toque ───────────────────────────
    document.addEventListener('click', async e => {
        const b = e.target.closest('.btn-estado');
        if (!b || b.disabled) return;
        b.disabled = true;
        const datos = new FormData();
        datos.append('id', b.dataset.id);
        try {
            const j = await enviar(BASE + '/admin/talles/toggle', datos);
            if (!j.ok) throw new Error();
            const on = j.activo === 1;
            b.textContent = on ? 'Activo' : 'Inactivo';
            b.className = 'btn-estado px-2 py-1 text-xs rounded whitespace-nowrap ' + (on ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
            b.title = 'Tocá para ' + (on ? 'desactivar' : 'activar');
            b.closest('li').querySelector('.border.font-semibold').classList.toggle('opacity-40', !on);
            toast(on ? '✓ Talle activado' : '✓ Talle desactivado');
        } catch (err) {
            toast('No se pudo cambiar. Recargá la página.');
        } finally {
            b.disabled = false;
        }
    });

    // ── Ventana nuevo / editar ───────────────────────────────────
    window.abrirTalle = function (t) {
        t = t || {};
        const editando = !!t.id;
        $('formTalle').action    = BASE + '/admin/talles/' + (editando ? 'actualizar' : 'guardar');
        $('talleId').value       = t.id || '';
        $('talleId').disabled    = !editando;
        $('talleOrden').value    = editando ? t.orden : '';   // nuevo: vacío = al final
        $('talleNombre').value   = t.nombre || '';
        $('talleActivo').checked = editando ? t.activo === 1 : true;
        $('talleTitulo').textContent = editando ? 'Editar talle' : 'Nuevo talle';
        $('talleBoton').textContent  = editando ? 'Guardar cambios' : 'Crear talle';
        $('modalTalle').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(() => $('talleNombre').focus(), 50);
    };

    window.cerrarTalle = function () {
        $('modalTalle').classList.add('hidden');
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarTalle(); });
})();
</script>