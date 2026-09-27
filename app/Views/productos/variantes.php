<?php $tabActiva = 'variantes'; require __DIR__ . '/_tabs.php'; ?>

<?php if (isset($_GET['ok'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm">
        <?php
        if ($_GET['ok'] === 'masivo') {
            $c = (int) ($_GET['c'] ?? 0);
            $o = (int) ($_GET['o'] ?? 0);
            echo "Se crearon $c variante(s)." . ($o > 0 ? " Se omitieron $o que ya existían." : '');
        } else {
            $msgs = [
                'producto_creado' => 'Producto creado. Ahora cargale los talles y colores.',
                'creada'          => 'Variante agregada correctamente.',
                'actualizada'     => 'Variante actualizada correctamente.',
                'eliminada'       => 'Variante eliminada correctamente.',
                'stock'           => 'Stock actualizado.',
            ];
            echo $msgs[$_GET['ok']] ?? 'Operación realizada.';
        }
        ?>
    </div>
<?php endif; ?>

<?php if (($_GET['error'] ?? '') === 'sin_combinaciones'): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm">
        Elegí al menos un talle y un color.
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

    <!-- ══ AGREGAR VARIANTES ══════════════════════════════════════ -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow p-5 h-fit">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Agregar variantes</h3>

        <form action="<?= BASE_URL ?>/admin/productos/guardar-variantes" method="POST" id="formMasivo" class="space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" name="id_producto" value="<?= (int) $producto['id_producto'] ?>">

            <!-- TALLES -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-medium text-gray-700">Talles</label>
                    <div class="flex gap-3 text-xs">
                        <button type="button" class="text-gray-500 hover:underline" onclick="marcarTodos('talle', true)">Todos</button>
                        <button type="button" class="text-gray-500 hover:underline" onclick="marcarTodos('talle', false)">Ninguno</button>
                        <button type="button" class="text-blue-600 hover:underline" onclick="toggleNuevo('talle')">+ Nuevo</button>
                    </div>
                </div>

                <div id="chipsTalle" class="flex flex-wrap gap-2">
                    <?php foreach ($talles as $t): ?>
                        <button type="button" class="chip" data-tipo="talle"
                                data-id="<?= (int) $t['id_talle'] ?>" data-orden="<?= (int) $t['orden'] ?>"
                                data-nombre="<?= htmlspecialchars($t['nombre']) ?>">
                            <?= htmlspecialchars($t['nombre']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Rango -->
                <?php if (count($talles) > 2): ?>
                    <div class="flex flex-wrap items-center gap-2 mt-3 text-sm">
                        <span class="text-gray-500">Desde</span>
                        <select id="rangoDesde" class="border rounded-lg px-2 py-1.5 bg-white text-sm">
                            <?php foreach ($talles as $t): ?>
                                <option value="<?= (int) $t['orden'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="text-gray-500">hasta</span>
                        <select id="rangoHasta" class="border rounded-lg px-2 py-1.5 bg-white text-sm">
                            <?php foreach ($talles as $i => $t): ?>
                                <option value="<?= (int) $t['orden'] ?>" <?= $i === count($talles) - 1 ? 'selected' : '' ?>><?= htmlspecialchars($t['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" onclick="marcarRango()"
                                class="px-3 py-1.5 rounded-lg border text-gray-700 hover:bg-gray-50">Marcar</button>
                    </div>
                <?php endif; ?>

                <!-- Nuevo talle -->
                <div id="nuevoTalle" class="hidden mt-3 p-3 bg-gray-50 border rounded-lg space-y-2">
                    <input type="text" id="nuevoTalleNombre" maxlength="20" placeholder="Ej: 45, XXL, Único"
                           class="w-full border rounded-lg px-3 py-2 text-sm">
                    <p id="nuevoTalleError" class="hidden text-xs text-red-600"></p>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="text-xs text-gray-500 px-2 py-1" onclick="toggleNuevo('talle')">Cancelar</button>
                        <button type="button" id="nuevoTalleBtn" onclick="crearTalle()"
                                class="text-xs bg-gray-900 text-white px-3 py-1.5 rounded-lg hover:bg-gray-800">Crear talle</button>
                    </div>
                </div>
            </div>

            <!-- COLORES -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-medium text-gray-700">Colores</label>
                    <div class="flex gap-3 text-xs">
                        <button type="button" class="text-gray-500 hover:underline" onclick="marcarTodos('color', false)">Ninguno</button>
                        <button type="button" class="text-blue-600 hover:underline" onclick="toggleNuevo('color')">+ Nuevo</button>
                    </div>
                </div>

                <div id="chipsColor" class="flex flex-wrap gap-2">
                    <?php foreach ($colores as $c): ?>
                        <button type="button" class="chip" data-tipo="color"
                                data-id="<?= (int) $c['id_color'] ?>" data-nombre="<?= htmlspecialchars($c['nombre']) ?>">
                            <span class="w-3 h-3 rounded-full border inline-block align-middle mr-1"
                                  style="background: <?= htmlspecialchars($c['codigo_hex'] ?: '#fff') ?>"></span>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Nuevo color -->
                <div id="nuevoColor" class="hidden mt-3 p-3 bg-gray-50 border rounded-lg space-y-2">
                    <input type="text" id="nuevoColorNombre" maxlength="50" placeholder="Ej: Azul marino, Estampado"
                           class="w-full border rounded-lg px-3 py-2 text-sm">
                    <div class="flex items-center gap-3">
                        <input type="color" id="nuevoColorHex" value="#000000" class="h-9 w-12 rounded border cursor-pointer p-0.5">
                        <label class="flex items-center gap-2 text-xs text-gray-500">
                            <input type="checkbox" id="nuevoColorSinMuestra">
                            No mostrar circulito (ej: estampado)
                        </label>
                    </div>
                    <p id="nuevoColorError" class="hidden text-xs text-red-600"></p>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="text-xs text-gray-500 px-2 py-1" onclick="toggleNuevo('color')">Cancelar</button>
                        <button type="button" id="nuevoColorBtn" onclick="crearColor()"
                                class="text-xs bg-gray-900 text-white px-3 py-1.5 rounded-lg hover:bg-gray-800">Crear color</button>
                    </div>
                </div>
            </div>

            <!-- STOCK / PRECIO -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stock de cada una</label>
                    <input type="number" id="stockTodas" min="0" value="0" class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio especial</label>
                    <input type="number" name="precio" step="0.01" min="0" placeholder="Vacío = base"
                           class="w-full border rounded-lg px-3 py-2">
                </div>
            </div>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="activo" checked>
                <span class="text-sm">Activas</span>
            </label>

            <!-- VISTA PREVIA -->
            <div id="preview" class="hidden">
                <p class="text-sm font-medium text-gray-700 mb-2">Vista previa <span class="text-gray-400 font-normal">(podés cambiar el stock de cada una)</span></p>
                <div id="previewLista" class="max-h-72 overflow-y-auto border rounded-lg divide-y text-sm"></div>
            </div>

            <p id="totalUnidades" class="hidden text-sm bg-blue-50 text-blue-800 rounded-lg px-3 py-2"></p>

            <div id="hiddenCombos"></div>

            <button type="submit" id="btnCrear" disabled
                    class="w-full bg-gray-300 text-white py-2.5 rounded-lg font-semibold cursor-not-allowed">
                Elegí talles y colores
            </button>
        </form>
    </div>

    <!-- ══ LISTADO DE VARIANTES ═══════════════════════════════════ -->
    <div class="lg:col-span-3">
        <div class="bg-white rounded-lg shadow overflow-hidden">

            <form id="formStock" method="POST" action="<?= BASE_URL ?>/admin/productos/stock-variantes"
                  class="flex items-center justify-between gap-3 px-4 py-3 border-b bg-gray-50">
                <?= csrf_field() ?>
                <input type="hidden" name="id_producto" value="<?= (int) $producto['id_producto'] ?>">
                <p class="text-sm text-gray-600">
                    Total: <strong id="totalStockListado">0</strong> unidades
                </p>
                <button id="btnGuardarStock" disabled
                        class="text-sm px-4 py-2 rounded-lg bg-gray-300 text-white cursor-not-allowed">
                    Guardar stock
                </button>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-left px-4 py-3">Talle</th>
                            <th class="text-left px-4 py-3">Color</th>
                            <th class="text-left px-4 py-3">SKU</th>
                            <th class="text-right px-4 py-3">Precio</th>
                            <th class="text-right px-4 py-3">Stock</th>
                            <th class="text-center px-4 py-3">Estado</th>
                            <th class="text-right px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($variantes && $variantes->num_rows > 0): ?>
                            <?php while ($v = $variantes->fetch_assoc()): ?>
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="px-4 py-3"><?= htmlspecialchars($v['talle'] ?? '—') ?></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1">
                                            <?php if (!empty($v['codigo_hex'])): ?>
                                                <span class="w-3 h-3 rounded-full border" style="background:<?= htmlspecialchars($v['codigo_hex']) ?>"></span>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($v['color'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars($v['sku'] ?: '—') ?></td>
                                    <td class="px-4 py-3 text-right">
                                        <?= $v['precio'] ? '$' . number_format($v['precio'], 2, ',', '.') : '<span class="text-gray-400">base</span>' ?>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <input type="number" form="formStock"
                                               name="stock[<?= (int) $v['id_variante'] ?>]"
                                               value="<?= (int) $v['stock'] ?>"
                                               min="<?= (int) $v['stock_reservado'] ?>"
                                               data-original="<?= (int) $v['stock'] ?>"
                                               class="input-stock w-20 border rounded px-2 py-1 text-right
                                                      <?= $v['stock_disponible'] <= 0 ? 'border-red-300 text-red-600' : '' ?>">
                                        <?php if (!empty($v['stock_reservado']) && $v['stock_reservado'] > 0): ?>
                                            <span class="block text-xs text-orange-600 mt-1"><?= (int) $v['stock_reservado'] ?> reservado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($v['activo'] == 1): ?>
                                            <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Activa</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">Inactiva</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-3">
                                            <a href="<?= BASE_URL ?>/admin/productos/editar-variante?id=<?= (int) $v['id_variante'] ?>"
                                               class="text-gray-600 hover:text-gray-900">Editar</a>
                                            <?= boton_eliminar(
                                                BASE_URL . '/admin/productos/eliminar-variante',
                                                ['id' => $v['id_variante'], 'id_producto' => $producto['id_producto']],
                                                '¿Eliminar esta variante?',
                                                'Eliminar',
                                                'text-red-500 hover:text-red-700'
                                            ) ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-gray-500">No hay variantes cargadas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<style>
  .chip { padding: .375rem .75rem; border: 1px solid #E5E7EB; border-radius: 9999px; font-size: .875rem; background: #fff; }
  .chip:hover { border-color: #9CA3AF; }
  .chip.activo { background: #111827; color: #fff; border-color: #111827; }
</style>

<script>
(function () {
    const CSRF       = <?= json_encode(csrf_token()) ?>;
    const BASE       = '<?= BASE_URL ?>';
    const EXISTENTES = <?= json_encode(array_keys($existentes)) ?>;

    const elegidos = { talle: new Set(), color: new Set() };

    const previewBox   = document.getElementById('preview');
    const previewLista = document.getElementById('previewLista');
    const hidden       = document.getElementById('hiddenCombos');
    const btnCrear     = document.getElementById('btnCrear');
    const stockTodas   = document.getElementById('stockTodas');
    const cajaTotal    = document.getElementById('totalUnidades');

    // Stock editado a mano por combinación (clave "talle-color")
    const stockManual = {};

    // ── Chips ───────────────────────────────────────────────────
    function chips(tipo) {
        return document.querySelectorAll('#chips' + (tipo === 'talle' ? 'Talle' : 'Color') + ' .chip');
    }

    function pintar() {
        ['talle', 'color'].forEach(tipo => {
            chips(tipo).forEach(ch => ch.classList.toggle('activo', elegidos[tipo].has(ch.dataset.id)));
        });
        armarPreview();
    }

    document.addEventListener('click', e => {
        const ch = e.target.closest('.chip');
        if (!ch) return;
        const set = elegidos[ch.dataset.tipo];
        set.has(ch.dataset.id) ? set.delete(ch.dataset.id) : set.add(ch.dataset.id);
        pintar();
    });

    window.marcarTodos = function (tipo, marcar) {
        elegidos[tipo].clear();
        if (marcar) chips(tipo).forEach(ch => elegidos[tipo].add(ch.dataset.id));
        pintar();
    };

    window.marcarRango = function () {
        let desde = Number(document.getElementById('rangoDesde').value);
        let hasta = Number(document.getElementById('rangoHasta').value);
        if (desde > hasta) [desde, hasta] = [hasta, desde];

        chips('talle').forEach(ch => {
            const orden = Number(ch.dataset.orden);
            if (orden >= desde && orden <= hasta) elegidos.talle.add(ch.dataset.id);
        });
        pintar();
    };

    // ── Vista previa ────────────────────────────────────────────
    function nombreDe(tipo, id) {
        const ch = [...chips(tipo)].find(c => c.dataset.id === id);
        return ch ? ch.dataset.nombre : '';
    }

    function ordenados(tipo) {
        return [...chips(tipo)].map(c => c.dataset.id).filter(id => elegidos[tipo].has(id));
    }

    /** Recalcula el total de unidades y el texto del botón (sin redibujar la lista). */
    function actualizarTotal() {
        const inputs = hidden.querySelectorAll('input[name$="[stock]"]');
        const nuevas = inputs.length;
        const total  = [...inputs].reduce((s, inp) => s + (parseInt(inp.value) || 0), 0);

        cajaTotal.classList.toggle('hidden', nuevas === 0);
        cajaTotal.innerHTML = `<strong>${nuevas}</strong> variante${nuevas !== 1 ? 's' : ''} → `
            + `<strong>${total}</strong> unidades en total`;

        btnCrear.disabled = nuevas === 0;
        btnCrear.className = 'w-full py-2.5 rounded-lg font-semibold ' +
            (nuevas === 0 ? 'bg-gray-300 text-white cursor-not-allowed' : 'bg-gray-900 text-white hover:bg-gray-800');
        btnCrear.textContent = nuevas === 0
            ? (previewLista.children.length ? 'Todas ya existen' : 'Elegí talles y colores')
            : `Crear ${nuevas} variante${nuevas !== 1 ? 's' : ''} (${total} unidades)`;
    }

    function armarPreview() {
        previewLista.innerHTML = '';
        hidden.innerHTML       = '';

        let i = 0;

        ordenados('color').forEach(idColor => {
            ordenados('talle').forEach(idTalle => {
                const clave  = idTalle + '-' + idColor;
                const existe = EXISTENTES.includes(clave);
                const stock  = stockManual[clave] ?? stockTodas.value;

                const fila = document.createElement('div');
                fila.className = 'flex items-center justify-between px-3 py-2 ' + (existe ? 'bg-gray-50 text-gray-400' : '');
                fila.innerHTML = `
                    <span>${escapar(nombreDe('talle', idTalle))} · ${escapar(nombreDe('color', idColor))}</span>
                    ${existe
                        ? '<span class="text-xs">ya existe</span>'
                        : `<input type="number" min="0" data-clave="${clave}" data-i="${i}" value="${stock}"
                                  class="w-20 border rounded px-2 py-1 text-right">`}
                `;
                previewLista.appendChild(fila);

                if (!existe) {
                    hidden.insertAdjacentHTML('beforeend',
                        `<input type="hidden" name="combos[${i}][talle]" value="${idTalle}">
                         <input type="hidden" name="combos[${i}][color]" value="${idColor}">
                         <input type="hidden" name="combos[${i}][stock]" id="stock-${i}" value="${stock}">`);
                    i++;
                }
            });
        });

        previewBox.classList.toggle('hidden', previewLista.children.length === 0);
        actualizarTotal();
    }

    // Stock editado en una fila → se guarda para esa combinación y se recalcula el total
    previewLista.addEventListener('input', e => {
        const inp = e.target.closest('input[data-clave]');
        if (!inp) return;
        stockManual[inp.dataset.clave] = inp.value;
        document.getElementById('stock-' + inp.dataset.i).value = inp.value;
        actualizarTotal();
    });

    // "Stock de cada una" pisa todo lo editado a mano
    stockTodas.addEventListener('input', () => {
        Object.keys(stockManual).forEach(k => delete stockManual[k]);
        armarPreview();
    });

    // ── Crear talle / color (AJAX) ──────────────────────────────
    const cfg = {
        talle: { caja: 'nuevoTalle', nombre: 'nuevoTalleNombre', error: 'nuevoTalleError', boton: 'nuevoTalleBtn',
                 url: BASE + '/admin/talles/crear-ajax', texto: 'Crear talle', chips: 'chipsTalle' },
        color: { caja: 'nuevoColor', nombre: 'nuevoColorNombre', error: 'nuevoColorError', boton: 'nuevoColorBtn',
                 url: BASE + '/admin/colores/crear-ajax', texto: 'Crear color', chips: 'chipsColor' }
    };
    const $ = id => document.getElementById(id);

    function mostrarError(tipo, texto) {
        $(cfg[tipo].error).textContent = texto;
        $(cfg[tipo].error).classList.toggle('hidden', !texto);
    }

    window.toggleNuevo = function (tipo) {
        const caja  = $(cfg[tipo].caja);
        const abrir = caja.classList.contains('hidden');
        caja.classList.toggle('hidden', !abrir);
        mostrarError(tipo, '');
        if (abrir) { $(cfg[tipo].nombre).value = ''; $(cfg[tipo].nombre).focus(); }
    };

    async function crear(tipo, extra) {
        const c      = cfg[tipo];
        const nombre = $(c.nombre).value.trim();
        if (!nombre) { mostrarError(tipo, 'Escribí un nombre.'); return; }

        const datos = new FormData();
        datos.append('csrf_token', CSRF);
        datos.append('nombre', nombre);
        Object.entries(extra || {}).forEach(([k, v]) => datos.append(k, v));

        $(c.boton).disabled = true;
        $(c.boton).textContent = 'Creando…';

        try {
            const resp = await fetch(c.url, { method: 'POST', body: datos, credentials: 'same-origin' });
            if (!(resp.headers.get('Content-Type') || '').includes('application/json')) {
                mostrarError(tipo, 'Tu sesión expiró. Recargá la página.');
                return;
            }
            const json = await resp.json();
            if (!json.ok) { mostrarError(tipo, json.error || 'No se pudo crear.'); return; }

            // Nuevo chip, ya marcado
            const ch = document.createElement('button');
            ch.type = 'button';
            ch.className = 'chip';
            ch.dataset.tipo   = tipo;
            ch.dataset.id     = String(json.id);
            ch.dataset.nombre = json.nombre;
            ch.dataset.orden  = '9999';
            ch.innerHTML = (tipo === 'color'
                ? `<span class="w-3 h-3 rounded-full border inline-block align-middle mr-1" style="background:${json.codigo_hex || '#fff'}"></span>`
                : '') + escapar(json.nombre);
            $(c.chips).appendChild(ch);

            elegidos[tipo].add(String(json.id));
            $(c.caja).classList.add('hidden');
            pintar();

        } catch (e) {
            mostrarError(tipo, 'Sin conexión. Probá de nuevo.');
        } finally {
            $(c.boton).disabled = false;
            $(c.boton).textContent = c.texto;
        }
    }

    window.crearTalle = () => crear('talle');
    window.crearColor = () => crear('color', {
        codigo_hex: $('nuevoColorSinMuestra').checked ? '' : $('nuevoColorHex').value
    });

    $('nuevoTalleNombre').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); crearTalle(); } });
    $('nuevoColorNombre').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); crearColor(); } });

    // ── Stock editable del listado ──────────────────────────────
    const inputsStock  = document.querySelectorAll('.input-stock');
    const btnGuardar   = document.getElementById('btnGuardarStock');
    const totalListado = document.getElementById('totalStockListado');

    function revisarStock() {
        let total = 0, cambios = 0;

        inputsStock.forEach(inp => {
            total += parseInt(inp.value) || 0;
            const cambio = inp.value !== inp.dataset.original;
            if (cambio) cambios++;
            inp.classList.toggle('bg-yellow-50', cambio);
        });

        totalListado.textContent = total;
        btnGuardar.disabled  = cambios === 0;
        btnGuardar.className = 'text-sm px-4 py-2 rounded-lg ' +
            (cambios ? 'bg-gray-900 text-white hover:bg-gray-800' : 'bg-gray-300 text-white cursor-not-allowed');
        btnGuardar.textContent = cambios ? `Guardar stock (${cambios})` : 'Guardar stock';
    }

    inputsStock.forEach(inp => inp.addEventListener('input', revisarStock));
    revisarStock();

    function escapar(t) {
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }

    pintar();
})();
</script>