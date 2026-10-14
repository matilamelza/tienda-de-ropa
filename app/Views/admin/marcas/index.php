<?php
$lista = [];
if ($marcas && $marcas->num_rows > 0) {
    while ($m = $marcas->fetch_assoc()) {
        $lista[] = $m;
    }
}

$msgs = [
    'creada'      => 'Marca creada.',
    'actualizada' => 'Marca actualizada.',
    'eliminada'   => 'Marca eliminada.',
];
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Marcas</h2>
        <p class="text-gray-500 text-sm"><?= count($lista) ?> marca<?= count($lista) !== 1 ? 's' : '' ?></p>
    </div>
    <button type="button" onclick="abrirMarca()" class="self-start sm:self-auto bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800">
        + Nueva marca
    </button>
</div>

<?php if (isset($_GET['ok'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm max-w-4xl">
        <?= htmlspecialchars($msgs[$_GET['ok']] ?? 'Listo.') ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm max-w-4xl">
        <?php if ($_GET['error'] === 'tiene_productos'): ?>
            No podés eliminar esta marca porque tiene productos activos. Cambiales la marca o desactivala.
        <?php else: ?>
            No se pudo completar la operación. Revisá que el nombre no esté vacío.
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (count($lista) > 8): ?>
    <div class="mb-3">
        <input type="search" id="buscarMarca" placeholder="Buscar marca…"
               class="w-full sm:w-72 border rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gray-200">
    </div>
<?php endif; ?>

<?php if (empty($lista)): ?>
    <div class="bg-white rounded-lg shadow px-4 py-12 text-center text-gray-400 text-sm max-w-4xl">
        Todavía no hay marcas. <button type="button" onclick="abrirMarca()" class="underline">Crear la primera</button>.
    </div>
<?php else: ?>

    <div class="bg-white rounded-lg shadow overflow-hidden max-w-4xl">
        <div class="hidden md:grid grid-cols-12 gap-3 px-4 py-3 bg-gray-100 text-gray-700 text-sm font-semibold">
            <div class="col-span-6">Marca</div>
            <div class="col-span-2 text-center">Productos</div>
            <div class="col-span-2 text-center">Estado</div>
            <div class="col-span-2 text-right">Acciones</div>
        </div>

        <ul id="listaMarcas" class="divide-y">
            <?php foreach ($lista as $m): ?>
                <?php
                $id         = (int) $m['id_marca'];
                $vivos      = (int) $m['cantidad_productos'];
                $eliminados = (int) ($m['en_papelera'] ?? 0);
                $activa     = (int) $m['activo'] === 1;
                ?>
                <li data-nombre="<?= htmlspecialchars(mb_strtolower($m['nombre'])) ?>"
                    class="grid grid-cols-12 gap-3 items-center px-4 py-3 hover:bg-gray-50">

                    <div class="col-span-12 md:col-span-6 flex items-center gap-3 min-w-0">
                        <span class="w-9 h-9 rounded-full bg-gray-100 text-gray-600 font-semibold text-sm flex items-center justify-center shrink-0 <?= $activa ? '' : 'opacity-40' ?>">
                            <?= htmlspecialchars(mb_strtoupper(mb_substr(trim($m['nombre']), 0, 1))) ?>
                        </span>
                        <p class="font-medium text-gray-900 truncate <?= $activa ? '' : 'text-gray-400' ?>"><?= htmlspecialchars($m['nombre']) ?></p>
                    </div>

                    <div class="col-span-4 md:col-span-2 md:text-center text-sm">
                        <?php if ($vivos > 0): ?>
                            <a href="<?= BASE_URL ?>/admin/productos?q=<?= urlencode($m['nombre']) ?>" class="text-gray-700 hover:underline"><?= $vivos ?> producto<?= $vivos !== 1 ? 's' : '' ?></a>
                        <?php else: ?>
                            <span class="text-gray-300">Sin productos</span>
                        <?php endif; ?>
                        <?php if ($eliminados > 0): ?>
                            <span class="block text-xs text-gray-400">+<?= $eliminados ?> eliminado(s)</span>
                        <?php endif; ?>
                    </div>

                    <div class="col-span-4 md:col-span-2 md:text-center">
                        <button type="button" class="btn-estado px-2 py-1 text-xs rounded whitespace-nowrap <?= $activa ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"
                                data-id="<?= $id ?>" title="Tocá para <?= $activa ? 'desactivar' : 'activar' ?>">
                            <?= $activa ? 'Activa' : 'Inactiva' ?>
                        </button>
                    </div>

                    <div class="col-span-4 md:col-span-2 text-right whitespace-nowrap text-sm">
                        <button type="button" class="text-gray-600 hover:text-gray-900 mr-2"
                                onclick='abrirMarca(<?= json_encode(["id" => $id, "nombre" => $m["nombre"], "activo" => $activa ? 1 : 0], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                            Editar
                        </button>
                        <?php if ($vivos === 0): ?>
                            <?= boton_eliminar(BASE_URL . '/admin/marcas/eliminar', ['id' => $id], '¿Eliminar la marca "' . $m['nombre'] . '"?', 'Eliminar', 'text-red-500 hover:text-red-700') ?>
                        <?php else: ?>
                            <span class="text-gray-300 cursor-not-allowed" title="Tiene productos activos">Eliminar</span>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <p id="sinResultados" class="hidden px-4 py-8 text-center text-sm text-gray-400">No hay marcas con ese nombre.</p>
    </div>

    <p class="text-xs text-gray-400 mt-2 max-w-4xl">
        Las marcas se muestran en orden alfabético, en el admin y en el filtro de la tienda. Una marca inactiva no aparece en ese filtro.
    </p>
<?php endif; ?>

<!-- ══ Ventana: nueva / editar ═══════════════════════════════════════ -->
<div id="modalMarca" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/40" onclick="cerrarMarca()"></div>
    <div class="absolute inset-x-0 bottom-0 md:inset-0 md:flex md:items-center md:justify-center md:p-6 pointer-events-none">
        <form method="POST" id="formMarca"
              class="pointer-events-auto bg-white w-full md:max-w-sm rounded-t-2xl md:rounded-2xl shadow-xl p-5 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="id_marca" id="marcaId">

            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800" id="marcaTitulo"></h3>
                <button type="button" onclick="cerrarMarca()" class="text-gray-400 hover:text-gray-900 text-xl leading-none">✕</button>
            </div>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Nombre</span>
                <input type="text" name="nombre" id="marcaNombre" required maxlength="100" placeholder="Ej: Nike, Adidas, Topper"
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-200">
            </label>

            <label class="flex items-center justify-between gap-3 py-1">
                <span class="text-sm text-gray-700">Activa <span class="block text-xs text-gray-400">Si la desactivás, no aparece en el filtro de la tienda.</span></span>
                <input type="checkbox" name="activo" id="marcaActivo" class="w-5 h-5">
            </label>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="cerrarMarca()" class="px-4 py-2 rounded-lg border text-sm">Cancelar</button>
                <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800" id="marcaBoton"></button>
            </div>
        </form>
    </div>
</div>

<div id="toastMarca" class="hidden fixed bottom-4 left-1/2 -translate-x-1/2 z-50 bg-gray-900 text-white text-sm px-4 py-2 rounded-lg shadow-lg"></div>

<script>
(function () {
    const CSRF = <?= json_encode(csrf_token()) ?>;
    const BASE = '<?= BASE_URL ?>';
    const $    = id => document.getElementById(id);

    function toast(texto) {
        const t = $('toastMarca');
        t.textContent = texto;
        t.classList.remove('hidden');
        clearTimeout(t._timer);
        t._timer = setTimeout(() => t.classList.add('hidden'), 1800);
    }

    // ── Activa / Inactiva con un toque ───────────────────────────
    document.addEventListener('click', async e => {
        const b = e.target.closest('.btn-estado');
        if (!b || b.disabled) return;
        b.disabled = true;
        const datos = new FormData();
        datos.append('csrf_token', CSRF);
        datos.append('id', b.dataset.id);
        try {
            const r = await fetch(BASE + '/admin/marcas/toggle', { method: 'POST', body: datos, credentials: 'same-origin' });
            const j = await r.json();
            if (!j.ok) throw new Error();
            const on = j.activo === 1;
            b.textContent = on ? 'Activa' : 'Inactiva';
            b.className = 'btn-estado px-2 py-1 text-xs rounded whitespace-nowrap ' + (on ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
            b.title = 'Tocá para ' + (on ? 'desactivar' : 'activar');
            const li = b.closest('li');
            li.querySelector('.rounded-full.bg-gray-100').classList.toggle('opacity-40', !on);
            li.querySelector('p.font-medium').classList.toggle('text-gray-400', !on);
            toast(on ? '✓ Marca activada' : '✓ Marca desactivada');
        } catch (err) {
            toast('No se pudo cambiar. Recargá la página.');
        } finally {
            b.disabled = false;
        }
    });

    // ── Buscar ───────────────────────────────────────────────────
    const buscar = $('buscarMarca');
    if (buscar) {
        buscar.addEventListener('input', () => {
            const q = buscar.value.trim().toLowerCase();
            let visibles = 0;
            document.querySelectorAll('#listaMarcas li').forEach(li => {
                const ver = q === '' || li.dataset.nombre.includes(q);
                li.classList.toggle('hidden', !ver);
                if (ver) visibles++;
            });
            $('sinResultados').classList.toggle('hidden', visibles > 0);
        });
    }

    // ── Ventana nueva / editar ───────────────────────────────────
    window.abrirMarca = function (m) {
        m = m || {};
        const editando = !!m.id;
        $('formMarca').action    = BASE + '/admin/marcas/' + (editando ? 'actualizar' : 'guardar');
        $('marcaId').value       = m.id || '';
        $('marcaId').disabled    = !editando;
        $('marcaNombre').value   = m.nombre || '';
        $('marcaActivo').checked = editando ? m.activo === 1 : true;
        $('marcaTitulo').textContent = editando ? 'Editar marca' : 'Nueva marca';
        $('marcaBoton').textContent  = editando ? 'Guardar cambios' : 'Crear marca';
        $('modalMarca').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(() => $('marcaNombre').focus(), 50);
    };

    window.cerrarMarca = function () {
        $('modalMarca').classList.add('hidden');
        document.body.style.overflow = '';
    };

    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarMarca(); });
})();
</script>