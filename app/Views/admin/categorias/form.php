<?php $editando = !empty($categoria); ?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">
            <?php echo $editando ? 'Editar categoría' : 'Nueva categoría'; ?>
        </h2>
        <p class="text-gray-500">
            <?php echo $editando ? htmlspecialchars($categoria['nombre']) : 'Completá los datos de la nueva categoría'; ?>
        </p>
    </div>

    <a href="<?= BASE_URL ?>/admin/categorias"
       class="px-4 py-2 rounded-lg border bg-white text-gray-700">
        Volver
    </a>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm">
        El nombre es obligatorio.
    </div>
<?php endif; ?>

<div class="max-w-lg">
    <form action="<?= BASE_URL ?>/admin/categorias/<?php echo $editando ? 'actualizar' : 'guardar'; ?>"
          method="POST"
          class="bg-white rounded-lg shadow p-6 space-y-5">

        <?php if ($editando): ?>
            <input type="hidden" name="id_categoria" value="<?php echo $categoria['id_categoria']; ?>">
        <?php endif; ?>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
            <input type="text"
                   name="nombre"
                   required
                   value="<?php echo htmlspecialchars($categoria['nombre'] ?? ''); ?>"
                   class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200">
        </div>

        <?php if ($editando): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Slug actual</label>
                <p class="text-sm text-gray-500 font-mono bg-gray-50 px-3 py-2 rounded-lg">
                    <?php echo htmlspecialchars($categoria['slug']); ?>
                </p>
                <p class="text-xs text-gray-400 mt-1">El slug se actualiza automáticamente al guardar.</p>
            </div>
        <?php endif; ?>

        <label class="flex items-center gap-2">
            <input type="checkbox"
                   name="activo"
                   <?php echo (!$editando || $categoria['activo'] == 1) ? 'checked' : ''; ?>>
            <span class="text-sm">Activa</span>
        </label>

        <div class="flex justify-end gap-2 pt-2">
            <a href="<?= BASE_URL ?>/admin/categorias"
               class="px-4 py-2 rounded-lg border text-gray-700">
                Cancelar
            </a>

            <button type="submit"
                    class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">
                <?php echo $editando ? 'Guardar cambios' : 'Crear categoría'; ?>
            </button>
        </div>

    </form>
</div>