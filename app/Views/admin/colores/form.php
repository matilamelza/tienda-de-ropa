<?php
$editando = !empty($color);
$hex      = $color['codigo_hex'] ?? '';
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">
            <?php echo $editando ? 'Editar color' : 'Nuevo color'; ?>
        </h2>
        <p class="text-gray-500">
            <?php echo $editando ? htmlspecialchars($color['nombre']) : 'Ej: Negro, Blanco, Azul marino, Estampado'; ?>
        </p>
    </div>

    <a href="<?= BASE_URL ?>/admin/colores"
       class="px-4 py-2 rounded-lg border bg-white text-gray-700">
        Volver
    </a>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm max-w-lg">
        <?php echo $_GET['error'] === 'duplicado'
            ? 'Ya existe un color con ese nombre.'
            : 'El nombre es obligatorio.'; ?>
    </div>
<?php endif; ?>

<div class="max-w-lg">
    <form action="<?= BASE_URL ?>/admin/colores/<?php echo $editando ? 'actualizar' : 'guardar'; ?>"
          method="POST"
          class="bg-white rounded-lg shadow p-6 space-y-5">

        <?= csrf_field() ?>

        <?php if ($editando): ?>
            <input type="hidden" name="id_color" value="<?php echo (int) $color['id_color']; ?>">
        <?php endif; ?>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
            <input type="text"
                   name="nombre"
                   id="nombreColor"
                   required
                   maxlength="50"
                   value="<?php echo htmlspecialchars($color['nombre'] ?? ''); ?>"
                   class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Muestra de color</label>
            <div class="flex items-center gap-3">
                <input type="color"
                       id="pickerColor"
                       value="<?php echo htmlspecialchars($hex !== '' ? $hex : '#000000'); ?>"
                       class="h-10 w-14 rounded-lg border cursor-pointer p-0.5">

                <input type="text"
                       name="codigo_hex"
                       id="hexColor"
                       maxlength="7"
                       value="<?php echo htmlspecialchars($hex); ?>"
                       placeholder="#000000"
                       class="w-32 border rounded-lg px-3 py-2 font-mono text-sm focus:outline-none focus:ring focus:ring-gray-200">

                <button type="button" id="quitarHex"
                        class="text-xs text-gray-400 hover:text-red-500">
                    Sin muestra
                </button>
            </div>
            <p class="text-xs text-gray-400 mt-1">
                Opcional. Dejalo vacío para colores como "Estampado" o "Multicolor".
            </p>
        </div>

        <!-- Vista previa: así se ve en la tienda -->
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-xs text-gray-400 mb-2">Vista previa en la tienda</p>
            <span class="inline-flex items-center gap-2 px-4 py-3 rounded-full border bg-white text-sm">
                <span id="previewMuestra" class="w-4 h-4 rounded-full border"></span>
                <span id="previewNombre"><?php echo htmlspecialchars($color['nombre'] ?? 'Nombre'); ?></span>
            </span>
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox"
                   name="activo"
                   <?php echo (!$editando || $color['activo'] == 1) ? 'checked' : ''; ?>>
            <span class="text-sm">Activo</span>
        </label>
        <p class="text-xs text-gray-400 -mt-3">
            Un color inactivo no aparece al cargar variantes nuevas. Las variantes que ya lo usan no se modifican.
        </p>

        <div class="flex justify-end gap-2 pt-2">
            <a href="<?= BASE_URL ?>/admin/colores"
               class="px-4 py-2 rounded-lg border text-gray-700">
                Cancelar
            </a>

            <button type="submit"
                    class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">
                <?php echo $editando ? 'Guardar cambios' : 'Crear color'; ?>
            </button>
        </div>

    </form>
</div>

<script>
(function () {
    const picker   = document.getElementById('pickerColor');
    const hexInput = document.getElementById('hexColor');
    const nombre   = document.getElementById('nombreColor');
    const muestra  = document.getElementById('previewMuestra');
    const pNombre  = document.getElementById('previewNombre');
    const quitar   = document.getElementById('quitarHex');

    const esHex = v => /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v);

    function normalizar(v) {
        v = v.trim().replace(/^#/, '');
        if (v.length === 3) v = v.split('').map(c => c + c).join('');
        return '#' + v.toUpperCase();
    }

    function actualizarPreview() {
        const v = hexInput.value.trim();
        if (v !== '' && esHex(v)) {
            muestra.style.background = normalizar(v);
            muestra.classList.remove('border-dashed');
        } else {
            muestra.style.background = '#fff';
            muestra.classList.add('border-dashed');
        }
        pNombre.textContent = nombre.value.trim() || 'Nombre';
    }

    picker.addEventListener('input', () => {
        hexInput.value = picker.value.toUpperCase();
        actualizarPreview();
    });

    hexInput.addEventListener('input', () => {
        if (esHex(hexInput.value)) picker.value = normalizar(hexInput.value);
        actualizarPreview();
    });

    quitar.addEventListener('click', () => {
        hexInput.value = '';
        actualizarPreview();
    });

    nombre.addEventListener('input', actualizarPreview);

    actualizarPreview();
})();
</script>