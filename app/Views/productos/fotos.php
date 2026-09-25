<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Fotos</h2>
        <p class="text-gray-500"><?php echo htmlspecialchars($producto['nombre']); ?></p>
    </div>

    <a href="<?= BASE_URL ?>/admin/productos"
       class="px-4 py-2 border rounded-lg bg-white text-gray-700">
        Volver
    </a>
</div>

<?php if (isset($_GET['ok'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm">
        <?php echo $_GET['ok'] === 'eliminada' ? 'Foto eliminada.' : 'Foto subida correctamente.'; ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm">
        <?php
        $errores = [
            'formato' => 'Formato no permitido. Subí una imagen JPG, PNG o WebP.',
            'tamano'  => 'La imagen supera los 5 MB.',
            'subida'  => 'No se pudo subir la imagen. Intentá de nuevo.',
        ];
        echo $errores[$_GET['error']] ?? 'No se pudo completar la operación.';
        ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-2 md:grid-cols-4 gap-6">

    <div class="bg-white p-4 rounded-lg shadow col-span-2 md:col-span-1">
        <form action="<?= BASE_URL ?>/admin/productos/subir-foto" method="POST" enctype="multipart/form-data" class="space-y-3">

            <?= csrf_field() ?>

            <input type="hidden" name="id_producto" value="<?php echo (int) $producto['id_producto']; ?>">

            <input type="file" name="foto" required accept="image/jpeg,image/png,image/webp"
                   class="block w-full text-sm text-gray-600">

            <p class="text-xs text-gray-400">JPG, PNG o WebP. Máx. 5 MB. La primera foto es la principal.</p>

            <button type="submit" class="w-full bg-gray-900 text-white py-2 rounded-lg hover:bg-gray-800">
                Subir
            </button>

        </form>
    </div>

    <?php $primera = true; ?>
    <?php while ($f = $fotos->fetch_assoc()): ?>

        <div class="bg-white p-2 rounded-lg shadow relative group">
            <img src="<?= BASE_URL ?>/public/uploads/productos/<?php echo htmlspecialchars($f['imagen']); ?>"
                 alt=""
                 class="w-full h-40 object-cover rounded">

            <?php if ($primera): ?>
                <span class="absolute top-3 left-3 bg-gray-900 text-white text-xs px-2 py-1 rounded">Principal</span>
                <?php $primera = false; ?>
            <?php endif; ?>

            <div class="mt-2 text-right">
                <?= boton_eliminar(
                    BASE_URL . '/admin/productos/eliminar-foto',
                    ['id' => $f['id_foto']],
                    '¿Eliminar esta foto?',
                    'Eliminar',
                    'text-xs text-red-500 hover:text-red-700'
                ) ?>
            </div>
        </div>

    <?php endwhile; ?>

</div>