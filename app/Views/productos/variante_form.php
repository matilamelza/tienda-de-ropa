<?php
$tabActiva = 'variantes';
require __DIR__ . '/_tabs.php';

$reservado  = (int) ($variante['stock_reservado'] ?? 0);
$disponible = (int) $variante['stock'] - $reservado;
?>

<div class="max-w-lg">

    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-800">
            Editar variante
            <span class="font-normal text-gray-500">· <?= htmlspecialchars(variante_texto($variante['talle'], $variante['color'])) ?></span>
        </h3>
        <a href="<?= BASE_URL ?>/admin/productos/variantes?id=<?= (int) $variante['id_producto'] ?>"
           class="text-sm text-gray-500 hover:text-gray-900">← Variantes</a>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm">
            <?= $_GET['error'] === 'duplicada'
                ? 'Este producto ya tiene una variante con ese talle y color.'
                : 'Elegí un talle.' ?>
        </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/admin/productos/actualizar-variante"
          method="POST"
          class="bg-white rounded-lg shadow p-6 space-y-4">

        <?= csrf_field() ?>
        <input type="hidden" name="id_variante" value="<?= (int) $variante['id_variante'] ?>">

        <!-- TALLE -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="text-sm font-medium text-gray-700">Talle *</label>
                <button type="button" class="text-xs text-blue-600 hover:underline" onclick="toggleNuevo('talle')">+ Nuevo</button>
            </div>
            <select name="id_talle" id="selectTalle" required class="w-full border rounded-lg px-3 py-2 bg-white">
                <option value="">Seleccionar</option>
                <?php while ($t = $talles->fetch_assoc()): ?>
                    <option value="<?= (int) $t['id_talle'] ?>" <?= (int) $variante['id_talle'] === (int) $t['id_talle'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['nombre']) ?><?= empty($t['activo']) ? ' (inactivo)' : '' ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <div id="nuevoTalle" class="hidden mt-2 p-3 bg-gray-50 border rounded-lg space-y-2">
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

        <!-- COLOR -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="text-sm font-medium text-gray-700">Color <span class="text-gray-400 font-normal">(opcional)</span></label>
                <button type="button" class="text-xs text-blue-600 hover:underline" onclick="toggleNuevo('color')">+ Nuevo</button>
            </div>
            <select name="id_color" id="selectColor" class="w-full border rounded-lg px-3 py-2 bg-white">
                <option value="">Sin color</option>
                <?php while ($c = $colores->fetch_assoc()): ?>
                    <option value="<?= (int) $c['id_color'] ?>" <?= (int) $variante['id_color'] === (int) $c['id_color'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre']) ?><?= empty($c['activo']) ? ' (inactivo)' : '' ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <div id="nuevoColor" class="hidden mt-2 p-3 bg-gray-50 border rounded-lg space-y-2">
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

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                <input type="text" name="sku" value="<?= htmlspecialchars($variante['sku'] ?? '') ?>"
                       class="w-full border rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Precio especial</label>
                <input type="number" name="precio" step="0.01" min="0"
                       value="<?= htmlspecialchars((string) ($variante['precio'] ?? '')) ?>"
                       placeholder="Vacío = base"
                       class="w-full border rounded-lg px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Stock (unidades que hay)</label>
            <input type="number" name="stock" min="<?= $reservado ?>" required
                   value="<?= (int) $variante['stock'] ?>"
                   class="w-full border rounded-lg px-3 py-2">
            <?php if ($reservado > 0): ?>
                <p class="text-xs text-orange-600 mt-1">
                    <?= $reservado ?> reservada(s) por pedidos pendientes · <?= max(0, $disponible) ?> disponible(s) para vender.
                    El stock no puede ser menor a lo reservado.
                </p>
            <?php endif; ?>
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="activo" <?= $variante['activo'] == 1 ? 'checked' : '' ?>>
            <span class="text-sm">Activa <span class="text-gray-400">(si la desactivás, no aparece en la tienda)</span></span>
        </label>

        <div class="flex justify-end gap-2 pt-2">
            <a href="<?= BASE_URL ?>/admin/productos/variantes?id=<?= (int) $variante['id_producto'] ?>"
               class="px-4 py-2 rounded-lg border text-gray-700">Cancelar</a>
            <button type="submit" class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">
                Guardar cambios
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    const CSRF = <?= json_encode(csrf_token()) ?>;
    const BASE = '<?= BASE_URL ?>';
    const $    = id => document.getElementById(id);

    const cfg = {
        talle: { caja: 'nuevoTalle', nombre: 'nuevoTalleNombre', error: 'nuevoTalleError', boton: 'nuevoTalleBtn',
                 select: 'selectTalle', url: BASE + '/admin/talles/crear-ajax', texto: 'Crear talle' },
        color: { caja: 'nuevoColor', nombre: 'nuevoColorNombre', error: 'nuevoColorError', boton: 'nuevoColorBtn',
                 select: 'selectColor', url: BASE + '/admin/colores/crear-ajax', texto: 'Crear color' }
    };

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

            // Agregar al select y dejarlo elegido
            $(c.select).add(new Option(json.nombre, json.id, true, true));
            $(c.caja).classList.add('hidden');

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
})();
</script>