<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Editar variante</h2>
        <p class="text-gray-500">SKU: <?php echo htmlspecialchars($variante['sku'] ?? '—'); ?></p>
    </div>

    <a href="<?= BASE_URL ?>/admin/productos/variantes?id=<?php echo $variante['id_producto']; ?>"
       class="px-4 py-2 rounded-lg border bg-white text-gray-700">
        Volver
    </a>
</div>

<div class="max-w-lg">
    <form action="<?= BASE_URL ?>/admin/productos/actualizar-variante"
          method="POST"
          class="bg-white rounded-lg shadow p-6 space-y-4">

        <input type="hidden" name="id_variante" value="<?php echo $variante['id_variante']; ?>">
        <input type="hidden" name="id_producto"  value="<?php echo $variante['id_producto']; ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Talle</label>
            <select name="id_talle" class="w-full border rounded-lg px-3 py-2 bg-white">
                <option value="">Sin talle</option>
                <?php while ($t = $talles->fetch_assoc()): ?>
                    <option value="<?php echo $t['id_talle']; ?>"
                        <?php echo ($variante['id_talle'] == $t['id_talle']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($t['nombre']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
            <select name="id_color" class="w-full border rounded-lg px-3 py-2 bg-white">
                <option value="">Sin color</option>
                <?php while ($c = $colores->fetch_assoc()): ?>
                    <option value="<?php echo $c['id_color']; ?>"
                        <?php echo ($variante['id_color'] == $c['id_color']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['nombre']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
            <input type="text"
                   name="sku"
                   value="<?php echo htmlspecialchars($variante['sku'] ?? ''); ?>"
                   class="w-full border rounded-lg px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Precio especial</label>
            <input type="number"
                   name="precio"
                   step="0.01"
                   min="0"
                   value="<?php echo $variante['precio'] ?? ''; ?>"
                   placeholder="Vacío = precio base"
                   class="w-full border rounded-lg px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
            <input type="number"
                   name="stock"
                   min="0"
                   required
                   value="<?php echo $variante['stock']; ?>"
                   class="w-full border rounded-lg px-3 py-2">
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox"
                   name="activo"
                   <?php echo $variante['activo'] == 1 ? 'checked' : ''; ?>>
            <span class="text-sm">Activa</span>
        </label>

        <div class="flex justify-end gap-2 pt-2">
            <a href="<?= BASE_URL ?>/admin/productos/variantes?id=<?php echo $variante['id_producto']; ?>"
               class="px-4 py-2 rounded-lg border text-gray-700">
                Cancelar
            </a>

            <button type="submit"
                    class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">
                Guardar cambios
            </button>
        </div>

    </form>
</div>