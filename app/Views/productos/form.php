<?php $editando = !empty($producto); ?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">
            <?php echo $editando ? 'Editar producto' : 'Nuevo producto'; ?>
        </h2>
        <p class="text-gray-500">
            <?php echo $editando ? htmlspecialchars($producto['nombre']) : 'Cargá la información principal del producto'; ?>
        </p>
    </div>

    <a href="<?= BASE_URL ?>/admin/productos"
       class="px-4 py-2 rounded-lg border bg-white text-gray-700">
        Volver
    </a>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm max-w-2xl">
        Completá nombre, categoría y precio de venta.
    </div>
<?php endif; ?>

<form action="<?= BASE_URL ?>/admin/productos/<?php echo $editando ? 'actualizar' : 'guardar'; ?>"
      method="POST"
      class="bg-white rounded-lg shadow p-6 space-y-5 max-w-2xl">

    <?= csrf_field() ?>

    <?php if ($editando): ?>
        <input type="hidden" name="id_producto" value="<?php echo (int) $producto['id_producto']; ?>">
    <?php endif; ?>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
        <input type="text"
               name="nombre"
               required
               value="<?php echo htmlspecialchars($producto['nombre'] ?? ''); ?>"
               class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
            <select name="id_categoria" required
                    class="w-full border rounded-lg px-3 py-2 bg-white">
                <option value="">Seleccionar</option>
                <?php while ($cat = $categorias->fetch_assoc()): ?>
                    <option value="<?php echo (int) $cat['id_categoria']; ?>"
                        <?php echo ($editando && $producto['id_categoria'] == $cat['id_categoria']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['nombre']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
            <select name="id_marca"
                    class="w-full border rounded-lg px-3 py-2 bg-white">
                <option value="">Sin marca</option>
                <?php while ($marca = $marcas->fetch_assoc()): ?>
                    <option value="<?php echo (int) $marca['id_marca']; ?>"
                        <?php echo ($editando && $producto['id_marca'] == $marca['id_marca']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($marca['nombre']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
        <textarea name="descripcion"
                  rows="4"
                  class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Precio de venta *</label>
            <input type="number"
                   name="precio_base"
                   id="precioVenta"
                   step="0.01"
                   min="0"
                   required
                   value="<?php echo htmlspecialchars((string) ($producto['precio_base'] ?? '')); ?>"
                   class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Precio de costo <span class="text-gray-400 font-normal">(solo lo ves vos)</span>
            </label>
            <input type="number"
                   name="precio_costo"
                   id="precioCosto"
                   step="0.01"
                   min="0"
                   value="<?php echo htmlspecialchars((string) ($producto['precio_costo'] ?? '')); ?>"
                   placeholder="Opcional"
                   class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200">
        </div>
    </div>

    <!-- Ganancia calculada en vivo -->
    <div id="cajaGanancia" class="hidden rounded-lg border px-4 py-3 text-sm">
        <div class="flex flex-wrap gap-x-6 gap-y-1">
            <span>Ganancia: <strong id="gananciaMonto"></strong></span>
            <span>Margen: <strong id="gananciaMargen"></strong></span>
            <span>Recargo sobre costo: <strong id="gananciaRecargo"></strong></span>
        </div>
    </div>

    <div class="flex gap-6">
        <label class="flex items-center gap-2">
            <input type="checkbox"
                   name="activo"
                   <?php echo (!$editando || $producto['activo'] == 1) ? 'checked' : ''; ?>>
            <span class="text-sm">Activo</span>
        </label>

        <label class="flex items-center gap-2">
            <input type="checkbox"
                   name="destacado"
                   <?php echo ($editando && $producto['destacado'] == 1) ? 'checked' : ''; ?>>
            <span class="text-sm">Destacado</span>
        </label>
    </div>

    <div class="flex justify-end gap-2 pt-2">
        <a href="<?= BASE_URL ?>/admin/productos"
           class="px-4 py-2 rounded-lg border text-gray-700">
            Cancelar
        </a>

        <button type="submit"
                class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">
            <?php echo $editando ? 'Guardar cambios' : 'Crear producto'; ?>
        </button>
    </div>

</form>

<?php if ($editando): ?>
    <div class="mt-6 flex gap-3 max-w-2xl">
        <a href="<?= BASE_URL ?>/admin/productos/variantes?id=<?php echo (int) $producto['id_producto']; ?>"
           class="px-4 py-2 rounded-lg border text-blue-700 bg-blue-50 hover:bg-blue-100">
            Gestionar variantes →
        </a>
        <a href="<?= BASE_URL ?>/admin/productos/fotos?id=<?php echo (int) $producto['id_producto']; ?>"
           class="px-4 py-2 rounded-lg border text-indigo-700 bg-indigo-50 hover:bg-indigo-100">
            Gestionar fotos →
        </a>
    </div>
<?php endif; ?>

<script>
(function () {
    const venta   = document.getElementById('precioVenta');
    const costo   = document.getElementById('precioCosto');
    const caja    = document.getElementById('cajaGanancia');
    const monto   = document.getElementById('gananciaMonto');
    const margen  = document.getElementById('gananciaMargen');
    const recargo = document.getElementById('gananciaRecargo');

    const pesos = n => '$' + n.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const pct   = n => n.toLocaleString('es-AR', { maximumFractionDigits: 1 }) + '%';

    function calcular() {
        const v = parseFloat(venta.value);
        const c = parseFloat(costo.value);

        if (isNaN(v) || isNaN(c) || costo.value === '') {
            caja.classList.add('hidden');
            return;
        }

        const g = v - c;

        monto.textContent   = pesos(g);
        margen.textContent  = v > 0 ? pct(g / v * 100) : '—';
        recargo.textContent = c > 0 ? pct(g / c * 100) : '—';

        caja.className = 'rounded-lg border px-4 py-3 text-sm ' +
            (g < 0 ? 'bg-red-50 border-red-200 text-red-700'
                   : 'bg-green-50 border-green-200 text-green-800');
    }

    venta.addEventListener('input', calcular);
    costo.addEventListener('input', calcular);
    calcular();
})();
</script>