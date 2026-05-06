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

<form action="<?= BASE_URL ?>/admin/productos/<?php echo $editando ? 'actualizar' : 'guardar'; ?>"
      method="POST"
      class="bg-white rounded-lg shadow p-6 space-y-5 max-w-2xl">

    <?php if ($editando): ?>
        <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">
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
                    <option value="<?php echo $cat['id_categoria']; ?>"
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
                    <option value="<?php echo $marca['id_marca']; ?>"
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

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Precio base *</label>
        <input type="number"
               name="precio_base"
               step="0.01"
               min="0"
               required
               value="<?php echo $producto['precio_base'] ?? ''; ?>"
               class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200">
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
        <a href="<?= BASE_URL ?>/admin/productos/variantes?id=<?php echo $producto['id_producto']; ?>"
           class="px-4 py-2 rounded-lg border text-blue-700 bg-blue-50 hover:bg-blue-100">
            Gestionar variantes →
        </a>
        <a href="<?= BASE_URL ?>/admin/productos/fotos?id=<?php echo $producto['id_producto']; ?>"
           class="px-4 py-2 rounded-lg border text-indigo-700 bg-indigo-50 hover:bg-indigo-100">
            Gestionar fotos →
        </a>
    </div>
<?php endif; ?>