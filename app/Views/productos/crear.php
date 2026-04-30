<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Nuevo producto</h2>
    <p class="text-gray-500">Cargá la información principal del producto</p>
</div>

<form action="index.php?route=productos_guardar" method="POST" class="bg-white rounded-lg shadow p-6 space-y-5">

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
        <input type="text" name="nombre" required
               class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
            <select name="id_categoria" required
                    class="w-full border rounded-lg px-3 py-2 bg-white">
                <option value="">Seleccionar</option>
                <?php while ($cat = $categorias->fetch_assoc()): ?>
                    <option value="<?php echo $cat['id_categoria']; ?>">
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
                    <option value="<?php echo $marca['id_marca']; ?>">
                        <?php echo htmlspecialchars($marca['nombre']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
        <textarea name="descripcion" rows="4"
                  class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200"></textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Precio base</label>
        <input type="number" name="precio_base" step="0.01" min="0" required
               class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200">
    </div>

    <div class="flex gap-6">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="activo" checked>
            <span>Activo</span>
        </label>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="destacado">
            <span>Destacado</span>
        </label>
    </div>

    <div class="flex justify-end gap-2 pt-4">
        <a href="index.php?route=productos"
           class="px-4 py-2 rounded-lg border text-gray-700">
            Cancelar
        </a>

        <button type="submit"
                class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">
            Guardar producto
        </button>
    </div>

</form>