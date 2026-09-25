<?php
$fotosArray = [];
while ($f = $fotos->fetch_assoc()) {
    $fotosArray[] = $f;
}
?>

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

<div class="grid grid-cols-1 md:grid-cols-4 gap-6">

    <!-- Subir foto -->
    <div class="bg-white p-4 rounded-lg shadow h-fit">
        <form action="<?= BASE_URL ?>/admin/productos/subir-foto" method="POST" enctype="multipart/form-data" class="space-y-3">

            <?= csrf_field() ?>

            <input type="hidden" name="id_producto" value="<?php echo (int) $producto['id_producto']; ?>">

            <input type="file" name="foto" required accept="image/jpeg,image/png,image/webp"
                   class="block w-full text-sm text-gray-600">

            <p class="text-xs text-gray-400">JPG, PNG o WebP. Máx. 5 MB.</p>

            <button type="submit" class="w-full bg-gray-900 text-white py-2 rounded-lg hover:bg-gray-800">
                Subir
            </button>

        </form>
    </div>

    <!-- Fotos ordenables -->
    <div class="md:col-span-3">

        <?php if (!empty($fotosArray)): ?>

            <div class="flex items-center justify-between mb-3">
                <p class="text-sm text-gray-500">
                    Arrastrá las fotos para cambiar el orden. <strong>La primera es la principal.</strong>
                </p>
                <span id="estadoOrden" class="text-xs text-gray-400"></span>
            </div>

            <div id="listaFotos" class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <?php foreach ($fotosArray as $i => $f): ?>
                    <div class="foto-item bg-white p-2 rounded-lg shadow relative cursor-grab active:cursor-grabbing select-none"
                         data-id="<?php echo (int) $f['id_foto']; ?>">

                        <img src="<?= BASE_URL ?>/public/uploads/productos/<?php echo htmlspecialchars($f['imagen']); ?>"
                             alt=""
                             draggable="false"
                             class="w-full h-40 object-cover rounded pointer-events-none">

                        <span class="foto-numero absolute top-3 left-3 bg-white/90 text-gray-700 text-xs font-semibold w-6 h-6 rounded-full flex items-center justify-center shadow">
                            <?php echo $i + 1; ?>
                        </span>

                        <span class="foto-principal absolute top-3 right-3 bg-gray-900 text-white text-xs px-2 py-1 rounded <?php echo $i === 0 ? '' : 'hidden'; ?>">
                            Principal
                        </span>

                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-gray-300 text-lg leading-none" title="Arrastrar">⠿</span>
                            <?= boton_eliminar(
                                BASE_URL . '/admin/productos/eliminar-foto',
                                ['id' => $f['id_foto']],
                                '¿Eliminar esta foto?',
                                'Eliminar',
                                'text-xs text-red-500 hover:text-red-700'
                            ) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="bg-gray-50 rounded-lg p-10 text-center text-gray-400">
                Este producto todavía no tiene fotos.
            </div>
        <?php endif; ?>

    </div>

</div>

<?php if (count($fotosArray) > 1): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const lista      = document.getElementById('listaFotos');
    const estado     = document.getElementById('estadoOrden');
    const idProducto = <?php echo (int) $producto['id_producto']; ?>;
    const csrf       = <?php echo json_encode(csrf_token()); ?>;
    const url        = '<?= BASE_URL ?>/admin/productos/ordenar-fotos';

    function mostrarEstado(texto, clase) {
        estado.textContent = texto;
        estado.className   = 'text-xs ' + clase;
    }

    // Actualiza los números y el cartel "Principal" según la posición
    function refrescarEtiquetas() {
        lista.querySelectorAll('.foto-item').forEach((item, i) => {
            item.querySelector('.foto-numero').textContent = i + 1;
            item.querySelector('.foto-principal').classList.toggle('hidden', i !== 0);
        });
    }

    async function guardarOrden() {
        const ids = [...lista.querySelectorAll('.foto-item')].map(el => el.dataset.id);

        const datos = new FormData();
        datos.append('csrf_token', csrf);
        datos.append('id_producto', idProducto);
        ids.forEach(id => datos.append('ids[]', id));

        mostrarEstado('Guardando…', 'text-gray-400');

        try {
            const resp = await fetch(url, {
                method: 'POST',
                body: datos,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const esJson = (resp.headers.get('Content-Type') || '').includes('application/json');

            if (!esJson) {
                // Sesión vencida (redirigió al login) o token CSRF inválido
                mostrarEstado('Tu sesión expiró. Recargá la página.', 'text-red-600');
                return;
            }

            const json = await resp.json();

            if (resp.ok && json.ok) {
                mostrarEstado('Orden guardado ✓', 'text-green-600');
                setTimeout(() => mostrarEstado('', ''), 2000);
            } else {
                mostrarEstado(json.error || 'No se pudo guardar. Recargá la página.', 'text-red-600');
            }

        } catch (e) {
            mostrarEstado('Sin conexión. Recargá la página.', 'text-red-600');
        }
    }

    new Sortable(lista, {
        animation: 150,
        ghostClass: 'opacity-40',
        filter: 'form, button',        // el botón eliminar sigue siendo clickeable
        preventOnFilter: false,
        delay: 150,                    // en el celular: mantener apretado para arrastrar
        delayOnTouchOnly: true,        // (así se puede scrollear normal)
        onEnd: function (evt) {
            if (evt.oldIndex === evt.newIndex) return;
            refrescarEtiquetas();
            guardarOrden();
        }
    });
})();
</script>
<?php endif; ?>