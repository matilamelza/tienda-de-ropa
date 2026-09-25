<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Variantes</h2>
        <p class="text-gray-500">
            Producto: <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
        </p>
    </div>

    <a href="<?= BASE_URL ?>/admin/productos"
       class="px-4 py-2 rounded-lg border text-gray-700 bg-white">
        Volver
    </a>
</div>

<?php if (isset($_GET['ok'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm">
        <?php
        $msgs = [
            'creada'      => 'Variante agregada correctamente.',
            'actualizada' => 'Variante actualizada correctamente.',
            'eliminada'   => 'Variante eliminada correctamente.',
        ];
        echo $msgs[$_GET['ok']] ?? 'Operación realizada.';
        ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- FORMULARIO NUEVA VARIANTE -->
    <div class="bg-white rounded-lg shadow p-6 h-fit">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Nueva variante</h3>

        <form action="<?= BASE_URL ?>/admin/productos/guardar-variante" method="POST" class="space-y-4">

            <?= csrf_field() ?>

            <input type="hidden" name="id_producto" value="<?php echo (int) $producto['id_producto']; ?>">

            <!-- TALLE -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Talle</label>
                    <button type="button" class="text-xs text-blue-600 hover:underline"
                            onclick="toggleNuevo('talle')">+ Nuevo</button>
                </div>

                <select name="id_talle" id="selectTalle" required class="w-full border rounded-lg px-3 py-2 bg-white">
                    <option value="">Seleccionar</option>
                    <?php while ($talle = $talles->fetch_assoc()): ?>
                        <option value="<?php echo (int) $talle['id_talle']; ?>">
                            <?php echo htmlspecialchars($talle['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Mini form: nuevo talle -->
                <div id="nuevoTalle" class="hidden mt-2 p-3 bg-gray-50 border rounded-lg space-y-2">
                    <input type="text" id="nuevoTalleNombre" maxlength="20"
                           placeholder="Ej: 42, XXL, Único"
                           class="w-full border rounded-lg px-3 py-2 text-sm">
                    <p id="nuevoTalleError" class="hidden text-xs text-red-600"></p>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="text-xs text-gray-500 px-2 py-1"
                                onclick="toggleNuevo('talle')">Cancelar</button>
                        <button type="button" id="nuevoTalleBtn"
                                class="text-xs bg-gray-900 text-white px-3 py-1.5 rounded-lg hover:bg-gray-800"
                                onclick="crearTalle()">Crear talle</button>
                    </div>
                </div>
            </div>

            <!-- COLOR -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Color</label>
                    <button type="button" class="text-xs text-blue-600 hover:underline"
                            onclick="toggleNuevo('color')">+ Nuevo</button>
                </div>

                <select name="id_color" id="selectColor" required class="w-full border rounded-lg px-3 py-2 bg-white">
                    <option value="">Seleccionar</option>
                    <?php while ($color = $colores->fetch_assoc()): ?>
                        <option value="<?php echo (int) $color['id_color']; ?>">
                            <?php echo htmlspecialchars($color['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Mini form: nuevo color -->
                <div id="nuevoColor" class="hidden mt-2 p-3 bg-gray-50 border rounded-lg space-y-2">
                    <input type="text" id="nuevoColorNombre" maxlength="50"
                           placeholder="Ej: Azul marino, Estampado"
                           class="w-full border rounded-lg px-3 py-2 text-sm">
                    <div class="flex items-center gap-3">
                        <input type="color" id="nuevoColorHex" value="#000000"
                               class="h-9 w-12 rounded border cursor-pointer p-0.5">
                        <label class="flex items-center gap-2 text-xs text-gray-500">
                            <input type="checkbox" id="nuevoColorSinMuestra">
                            Sin muestra de color
                        </label>
                    </div>
                    <p id="nuevoColorError" class="hidden text-xs text-red-600"></p>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="text-xs text-gray-500 px-2 py-1"
                                onclick="toggleNuevo('color')">Cancelar</button>
                        <button type="button" id="nuevoColorBtn"
                                class="text-xs bg-gray-900 text-white px-3 py-1.5 rounded-lg hover:bg-gray-800"
                                onclick="crearColor()">Crear color</button>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                <input type="text" name="sku"
                       placeholder="Ej: REM-NEG-M"
                       class="w-full border rounded-lg px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Precio especial</label>
                <input type="number" name="precio" step="0.01" min="0"
                       placeholder="Vacío = precio base"
                       class="w-full border rounded-lg px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
                <input type="number" name="stock" min="0" value="0" required
                       class="w-full border rounded-lg px-3 py-2">
            </div>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="activo" checked>
                <span class="text-sm">Activa</span>
            </label>

            <button type="submit"
                    class="w-full bg-gray-900 text-white py-2 rounded-lg hover:bg-gray-800">
                Agregar variante
            </button>
        </form>
    </div>

    <!-- LISTADO DE VARIANTES -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow overflow-hidden">
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
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($v['talle'] ?? '—'); ?></td>
                                    <td class="px-4 py-3">
                                        <?php if (!empty($v['codigo_hex'])): ?>
                                            <span class="inline-flex items-center gap-1">
                                                <span class="w-3 h-3 rounded-full border"
                                                      style="background:<?php echo htmlspecialchars($v['codigo_hex']); ?>"></span>
                                                <?php echo htmlspecialchars($v['color'] ?? '—'); ?>
                                            </span>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($v['color'] ?? '—'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs"><?php echo htmlspecialchars($v['sku'] ?? '—'); ?></td>
                                    <td class="px-4 py-3 text-right">
                                        <?php echo $v['precio'] ? '$' . number_format($v['precio'], 2, ',', '.') : '<span class="text-gray-400">base</span>'; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <span class="<?php echo $v['stock_disponible'] <= 0 ? 'text-red-600 font-semibold' : ''; ?>">
                                            <?php echo (int) $v['stock_disponible']; ?>
                                        </span>
                                        <?php if (!empty($v['stock_reservado']) && $v['stock_reservado'] > 0): ?>
                                            <span class="block text-xs text-orange-600">
                                                +<?php echo (int) $v['stock_reservado']; ?> reservado
                                            </span>
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
                                            <a href="<?= BASE_URL ?>/admin/productos/editar-variante?id=<?php echo (int) $v['id_variante']; ?>"
                                               class="text-gray-600 hover:text-gray-900">
                                                Editar
                                            </a>

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
                                <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                    No hay variantes cargadas.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    const csrf = <?php echo json_encode(csrf_token()); ?>;
    const base = '<?= BASE_URL ?>';

    const cfg = {
        talle: {
            caja:   document.getElementById('nuevoTalle'),
            nombre: document.getElementById('nuevoTalleNombre'),
            error:  document.getElementById('nuevoTalleError'),
            boton:  document.getElementById('nuevoTalleBtn'),
            select: document.getElementById('selectTalle'),
            url:    base + '/admin/talles/crear-ajax',
            textoBoton: 'Crear talle'
        },
        color: {
            caja:   document.getElementById('nuevoColor'),
            nombre: document.getElementById('nuevoColorNombre'),
            error:  document.getElementById('nuevoColorError'),
            boton:  document.getElementById('nuevoColorBtn'),
            select: document.getElementById('selectColor'),
            url:    base + '/admin/colores/crear-ajax',
            textoBoton: 'Crear color'
        }
    };

    function mostrarError(tipo, texto) {
        const c = cfg[tipo];
        c.error.textContent = texto;
        c.error.classList.toggle('hidden', !texto);
    }

    window.toggleNuevo = function (tipo) {
        const c = cfg[tipo];
        const abrir = c.caja.classList.contains('hidden');

        c.caja.classList.toggle('hidden', !abrir);
        mostrarError(tipo, '');

        if (abrir) {
            c.nombre.value = '';
            c.nombre.focus();
        }
    };

    async function crear(tipo, datosExtra) {
        const c = cfg[tipo];
        const nombre = c.nombre.value.trim();

        if (nombre === '') {
            mostrarError(tipo, 'Escribí un nombre.');
            c.nombre.focus();
            return;
        }

        const datos = new FormData();
        datos.append('csrf_token', csrf);
        datos.append('nombre', nombre);
        Object.entries(datosExtra || {}).forEach(([k, v]) => datos.append(k, v));

        c.boton.disabled = true;
        c.boton.textContent = 'Creando…';
        mostrarError(tipo, '');

        try {
            const resp   = await fetch(c.url, { method: 'POST', body: datos, credentials: 'same-origin' });
            const esJson = (resp.headers.get('Content-Type') || '').includes('application/json');

            if (!esJson) {
                mostrarError(tipo, 'Tu sesión expiró. Recargá la página.');
                return;
            }

            const json = await resp.json();

            if (!json.ok) {
                mostrarError(tipo, json.error || 'No se pudo crear.');
                return;
            }

            // Agregar al select y dejarlo seleccionado
            const opcion = new Option(json.nombre, json.id, true, true);
            c.select.add(opcion);
            c.select.value = String(json.id);

            c.caja.classList.add('hidden');

        } catch (e) {
            mostrarError(tipo, 'Sin conexión. Probá de nuevo.');
        } finally {
            c.boton.disabled = false;
            c.boton.textContent = c.textoBoton;
        }
    }

    window.crearTalle = function () {
        crear('talle');
    };

    window.crearColor = function () {
        const sinMuestra = document.getElementById('nuevoColorSinMuestra').checked;
        const hex        = document.getElementById('nuevoColorHex').value;
        crear('color', { codigo_hex: sinMuestra ? '' : hex });
    };

    // Enter en el mini form crea el talle/color en vez de enviar la variante
    cfg.talle.nombre.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); crearTalle(); }
    });
    cfg.color.nombre.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); crearColor(); }
    });
})();
</script>