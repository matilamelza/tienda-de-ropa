<?php $editando = !empty($talle); ?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">
            <?php echo $editando ? 'Editar talle' : 'Nuevo talle'; ?>
        </h2>
        <p class="text-gray-500">
            <?php echo $editando ? htmlspecialchars($talle['nombre']) : 'Ej: XS, S, M, L, XL, 38, 40, Único'; ?>
        </p>
    </div>

    <a href="<?= BASE_URL ?>/admin/talles"
       class="px-4 py-2 rounded-lg border bg-white text-gray-700">
        Volver
    </a>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm max-w-lg">
        <?php echo $_GET['error'] === 'duplicado'
            ? 'Ya existe un talle con ese nombre.'
            : 'El nombre es obligatorio.'; ?>
    </div>
<?php endif; ?>

<div class="max-w-lg">
    <form action="<?= BASE_URL ?>/admin/talles/<?php echo $editando ? 'actualizar' : 'guardar'; ?>"
          method="POST"
          class="bg-white rounded-lg shadow p-6 space-y-5">

        <?= csrf_field() ?>

        <?php if ($editando): ?>
            <input type="hidden" name="id_talle" value="<?php echo (int) $talle['id_talle']; ?>">
        <?php endif; ?>

        <div class="grid grid-cols-3 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                <input type="text"
                       name="nombre"
                       required
                       maxlength="20"
                       value="<?php echo htmlspecialchars($talle['nombre'] ?? ''); ?>"
                       class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                <input type="number"
                       name="orden"
                       min="0"
                       value="<?php echo $editando ? (int) $talle['orden'] : ''; ?>"
                       placeholder="Al final"
                       class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200">
            </div>
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox"
                   name="activo"
                   <?php echo (!$editando || $talle['activo'] == 1) ? 'checked' : ''; ?>>
            <span class="text-sm">Activo</span>
        </label>
        <p class="text-xs text-gray-400 -mt-3">
            Un talle inactivo no aparece al cargar variantes nuevas. Las variantes que ya lo usan no se modifican.
        </p>

        <div class="flex justify-end gap-2 pt-2">
            <a href="<?= BASE_URL ?>/admin/talles"
               class="px-4 py-2 rounded-lg border text-gray-700">
                Cancelar
            </a>

            <button type="submit"
                    class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">
                <?php echo $editando ? 'Guardar cambios' : 'Crear talle'; ?>
            </button>
        </div>

    </form>
</div>