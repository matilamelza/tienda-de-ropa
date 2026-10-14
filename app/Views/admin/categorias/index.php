<?php
$lista = [];
if ($categorias && $categorias->num_rows > 0) {
    while ($c = $categorias->fetch_assoc()) {
        $lista[] = $c;
    }
}
$archivo = Categoria::SLUG_ARCHIVO;

$msgs = [
    'creada'      => 'Categoría creada. Quedó al final de la lista: arrastrala adonde quieras.',
    'actualizada' => 'Categoría actualizada.',
    'eliminada'   => 'Categoría eliminada.',
];
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Categorías</h2>
        <p class="text-gray-500 text-sm"><?= count($lista) ?> categoría<?= count($lista) !== 1 ? 's' : '' ?> · el orden de acá es el del menú de la tienda</p>
    </div>
    <button type="button" onclick="abrirCategoria()" class="self-start sm:self-auto bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800">
        + Nueva categoría
    </button>
</div>

<?php if (isset($_GET['ok'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm">
        <?= htmlspecialchars($msgs[$_GET['ok']] ?? 'Listo.') ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm">
        <?php if ($_GET['error'] === 'tiene_productos'): ?>
            No podés eliminar esta categoría porque tiene productos activos. Mové esos productos a otra categoría o desactivala.
        <?php else: ?>
            No se pudo completar la operación. Revisá que el nombre no esté vacío.
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (count($lista) > 8): ?>
    <div class="mb-3">
        <input type="search" id="buscarCat" placeholder="Buscar categoría…"
               class="w-full sm:w-72 border rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gray-200">
    </div>
<?php endif; ?>

<?php if (empty($lista)): ?>
    <div class="bg-white rounded-lg shadow px-4 py-12 text-center text-gray-400 text-sm">
        Todavía no hay categorías. <button type="button" onclick="abrirCategoria()" class="underline">Crear la primera</button>.
    </div>
<?php else: ?>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="hidden md:grid grid-cols-12 gap-3 px-4 py-3 bg-gray-100 text-gray-700 text-sm font-semibold">
            <div class="col-span-6">Categoría</div>
            <div class="col-span-2 text-center">Productos</div>
            <div class="col-span-2 text-center">Estado</div>
            <div class="col-span-2 text-right">Acciones</div>
        </div>

        <ul id="listaCategorias" class="divide-y">
            <?php foreach ($lista as $pos => $c): ?>
                <?php
                $id         = (int) $c['id_categoria'];
                $vivos      = (int) $c['cantidad_productos'];
                $eliminados = (int) ($c['en_papelera'] ?? 0);
                $esArchivo  = $c['slug'] === $archivo;
                $activa     = (int) $c['activo'] === 1;
                ?>
                <li data-id="<?= $id ?>" data-nombre="<?= htmlspecialchars(mb_strtolower($c['nombre'])) ?>"
                    class="grid grid-cols-12 gap-3 items-center px-4 py-3 hover:bg-gray-50 <?= $esArchivo ? 'bg-gray-50/60' : '' ?>">

                    <!-- Nombre -->
                    <div class="col-span-12 md:col-span-6 flex items-center gap-3 min-w-0">
                        <?php if (!$esArchivo): ?>
                            <span class="manija cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 select-none" title="Arrastrá para ordenar">⋮⋮</span>
                        <?php else: ?>
                            <span class="w-4"></span>
                        <?php endif; ?>
                        <span class="posicion w-6 text-xs text-gray-400 text-right shrink-0"><?= $pos + 1 ?></span>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 truncate">
                                <?= htmlspecialchars($c['nombre']) ?>
                                <?php if ($esArchivo): ?><span class="text-xs font-normal text-gray-400">· interna</span><?php endif; ?>
                            </p>
                            <p class="text-xs text-gray-400 font-mono truncate">/categoria/<?= htmlspecialchars($c['slug']) ?></p>
                        </div>
                    </div>

                    <!-- Productos -->
                    <div class="col-span-4 md:col-span-2 md:text-center text-sm">
                        <?php if ($vivos > 0): ?>
                            <a href="<?= BASE_URL ?>/admin/productos?categoria=<?= $id ?>" class="text-gray-700 hover:underline"><?= $vivos ?> producto<?= $vivos !== 1 ? 's' : '' ?></a>
                        <?php else: ?>
                            <span class="text-gray-300">Sin productos</span>
                        <?php endif; ?>
                        <?php if ($eliminados > 0): ?>
                            <span class="block text-xs text-gray-400">+<?= $eliminados ?> eliminado(s)</span>
                        <?php endif; ?>
                    </div>

                    <!-- Estado -->
                    <div class="col-span-4 md:col-span-2 md:text-center">
                        <?php if ($esArchivo): ?>
                            <span class="text-xs text-gray-400">Oculta</span>
                        <?php else: ?>
                            <button type="button" class="btn-estado px-2 py-1 text-xs rounded whitespace-nowrap <?= $activa ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>"
                                    data-id="<?= $id ?>" data-activo="<?= $activa ? 1 : 0 ?>" title="Tocá para <?= $activa ? 'desactivar' : 'activar' ?>">
                                <?= $activa ? 'Activa' : 'Inactiva' ?>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Acciones -->
                    <div class="col-span-4 md:col-span-2 text-right whitespace-nowrap text-sm">
                        <?php if (!$esArchivo): ?>
                            <?php if ($activa): ?>
                                <a href="<?= BASE_URL ?>/categoria/<?= urlencode($c['slug']) ?>" target="_blank" class="text-gray-400 hover:text-gray-900 mr-2" title="Ver en la tienda">↗</a>
                            <?php endif; ?>
                            <button type="button" class="text-gray-600 hover:text-gray-900 mr-2"
                                    onclick='abrirCategoria(<?= json_encode(["id" => $id, "nombre" => $c["nombre"], "slug" => $c["slug"], "activo" => $activa ? 1 : 0], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                Editar
                            </button>
                            <?php if ($vivos === 0): ?>
                                <?= boton_eliminar(
                                    BASE_URL . '/admin/categorias/eliminar',
                                    ['id' => $id],
                                    '¿Eliminar la categoría "' . $c['nombre'] . '"?',
                                    'Eliminar',
                                    'text-red-500 hover:text-red-700'
                                ) ?>
                            <?php else: ?>
                                <span class="text-gray-300 cursor-not-allowed" title="Tiene productos activos">Eliminar</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <p class="text-xs text-gray-400 mt-2">
        Arrastrá ⋮⋮ para ordenar. Las primeras son las que se ven en la barra del menú de la tienda; las demás van al "Más".
    </p>
<?php endif; ?>

<!-- ══ Ventana: nueva / editar ═══════════════════════════════════════ -->
<div id="modalCat" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/40" onclick="cerrarCategoria()"></div>
    <div class="absolute inset-x-0 bottom-0 md:inset-0 md:flex md:items-center md:justify-center md:p-6 pointer-events-none">
        <form method="POST" id="formCat"
              class="pointer-events-auto bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-xl p-5 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="id_categoria" id="catId">

            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800" id="catTitulo"></h3>
                <button type="button" onclick="cerrarCategoria()" class="text-gray-400 hover:text-gray-900 text-xl leading-none">✕</button>
            </div>

            <label class="block">
                <span class="block text-sm font-medium text-gray-700 mb-1">Nombre</span>
                <input type="text" name="nombre" id="catNombre" required maxlength="100" placeholder="Ej: Zapatillas urbanas"
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-200">
                <span class="block text-xs text-gray-400 mt-1 font-mono truncate">
                    <?= htmlspecialchars(preg_replace('#^https?://#', '', rtrim(url_absoluta(''), '/'))) ?>/categoria/<span id="catSlug" class="text-gray-600"></span>
                </span>
            </label>

            <label class="flex items-center justify-between gap-3 py-1">
                <span class="text-sm text-gray-700">Activa <span class="block text-xs text-gray-400">Si la desactivás, no aparece en la tienda.</span></span>
                <input type="checkbox" name="activo" id="catActivo" class="w-5 h-5">
            </label>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="cerrarCategoria()" class="px-4 py-2 rounded-lg border text-sm">Cancelar</button>
                <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800" id="catBoton"></button>
            </div>
        </form>
    </div>
</div>

<div id="toastCat" class="hidden fixed bottom-4 left-1/2 -translate-x-1/2 z-50 bg-gray-900 text-white text-sm px-4 py-2 rounded-lg shadow-lg"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const CSRF  = <?= json_encode(csrf_token()) ?>;
    const BASE  = '<?= BASE_URL ?>';
    const $     = id => document.getElementById(id);
    const lista = $('listaCategorias');

    function toast(texto) {
        const t = $('toastCat');
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

    // ── Ordenar arrastrando ──────────────────────────────────────
    function renumerar() {
        lista.querySelectorAll('li').forEach((li, i) => { li.querySelector('.posicion').textContent = i + 1; });
    }

    let sortable = null;
    if (lista && window.Sortable) {
        sortable = Sortable.create(lista, {
            handle: '.manija',
            animation: 150,
            ghostClass: 'opacity-40',
            onEnd: async () => {
                renumerar();
                const datos = new FormData();
                lista.querySelectorAll('li').forEach(li => datos.append('ids[]', li.dataset.id));
                try {
                    const j = await enviar(BASE + '/admin/categorias/ordenar', datos);
                    toast(j.ok ? '✓ Orden guardado' : 'No se pudo guardar el orden');
                } catch (e) {
                    toast('No se pudo guardar el orden. Recargá la página.');
                }
            },
        });
    }

    // ── Activa / Inactiva con un toque ───────────────────────────
    document.addEventListener('click', async e => {
        const b = e.target.closest('.btn-estado');
        if (!b || b.disabled) return;
        b.disabled = true;
        const datos = new FormData();
        datos.append('id', b.dataset.id);
        try {
            const j = await enviar(BASE + '/admin/categorias/toggle', datos);
            if (!j.ok) throw new Error();
            const on = j.activo === 1;
            b.dataset.activo = on ? 1 : 0;
            b.textContent = on ? 'Activa' : 'Inactiva';
            b.className = 'btn-estado px-2 py-1 text-xs rounded whitespace-nowrap ' + (on ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
            b.title = 'Tocá para ' + (on ? 'desactivar' : 'activar');
            toast(on ? '✓ Categoría activada' : '✓ Categoría desactivada');
        } catch (err) {
            toast('No se pudo cambiar. Recargá la página.');
        } finally {
            b.disabled = false;
        }
    });

    // ── Buscar (mientras se busca, no se puede arrastrar) ────────
    const buscar = $('buscarCat');
    if (buscar) {
        buscar.addEventListener('input', () => {
            const q = buscar.value.trim().toLowerCase();
            lista.querySelectorAll('li').forEach(li => li.classList.toggle('hidden', q !== '' && !li.dataset.nombre.includes(q)));
            if (sortable) sortable.option('disabled', q !== '');
        });
    }

    // ── Ventana nueva / editar ───────────────────────────────────
    const slugDe = t => t.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                         .replace(/ñ/g, 'n').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

    window.abrirCategoria = function (c) {
        c = c || {};
        const editando = !!c.id;
        $('formCat').action    = BASE + '/admin/categorias/' + (editando ? 'actualizar' : 'guardar');
        $('catId').value       = c.id || '';
        $('catId').disabled    = !editando;
        $('catNombre').value   = c.nombre || '';
        $('catActivo').checked = editando ? c.activo === 1 : true;
        $('catTitulo').textContent = editando ? 'Editar categoría' : 'Nueva categoría';
        $('catBoton').textContent  = editando ? 'Guardar cambios' : 'Crear categoría';
        $('catSlug').textContent   = slugDe(c.nombre || '') || '…';
        $('modalCat').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        setTimeout(() => $('catNombre').focus(), 50);
    };

    window.cerrarCategoria = function () {
        $('modalCat').classList.add('hidden');
        document.body.style.overflow = '';
    };

    $('catNombre').addEventListener('input', e => { $('catSlug').textContent = slugDe(e.target.value) || '…'; });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarCategoria(); });
})();
</script>