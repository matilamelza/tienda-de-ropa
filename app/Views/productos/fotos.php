<?php
$fotosArray = [];
while ($f = $fotos->fetch_assoc()) {
    $fotosArray[] = $f;
}
$cantidadFotos = count($fotosArray);
$lugares       = max(0, $maxFotos - $cantidadFotos);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">

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

<?php if (isset($_GET['ok']) && $_GET['ok'] === 'eliminada'): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm">
        Foto eliminada.
    </div>
<?php endif; ?>

<!-- ── SUBIR FOTOS ─────────────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow p-5 mb-6">

    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-gray-800">Agregar fotos</h3>
        <span class="text-sm text-gray-500">
            <span id="contadorFotos"><?php echo $cantidadFotos; ?></span> de <?php echo (int) $maxFotos; ?> fotos
        </span>
    </div>

    <?php if ($lugares > 0): ?>

        <!-- Zona para soltar / elegir -->
        <label id="zonaSubida"
               class="block border-2 border-dashed border-gray-300 rounded-xl p-8 text-center cursor-pointer hover:border-gray-500 hover:bg-gray-50 transition">
            <input type="file" id="inputFotos" accept="image/*" multiple class="hidden">
            <p class="text-3xl mb-2">📷</p>
            <p class="font-medium text-gray-700">Tocá para elegir fotos o arrastralas acá</p>
            <p class="text-xs text-gray-400 mt-1">
                Podés elegir varias a la vez (hasta <?php echo $lugares; ?> más). Se achican solas antes de subir.
            </p>
        </label>

        <!-- Cola de fotos elegidas -->
        <div id="cola" class="hidden mt-5">
            <div id="colaLista" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4"></div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mt-5">
                <p id="colaMensaje" class="text-sm text-gray-500"></p>
                <div class="flex gap-2">
                    <button type="button" id="btnLimpiar"
                            class="px-4 py-2 rounded-lg border text-gray-700 text-sm">
                        Cancelar
                    </button>
                    <button type="button" id="btnSubir"
                            class="px-5 py-2 rounded-lg bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800">
                        Subir fotos
                    </button>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="bg-gray-50 rounded-lg p-5 text-sm text-gray-500 text-center">
            Llegaste al máximo de <?php echo (int) $maxFotos; ?> fotos. Eliminá alguna para agregar otra.
        </div>
    <?php endif; ?>
</div>

<!-- ── FOTOS ACTUALES (ordenables) ─────────────────────────────── -->
<?php if (!empty($fotosArray)): ?>

    <div class="flex items-center justify-between mb-3">
        <p class="text-sm text-gray-500">
            Arrastrá las fotos para cambiar el orden. <strong>La primera es la principal.</strong>
        </p>
        <span id="estadoOrden" class="text-xs text-gray-400"></span>
    </div>

    <div id="listaFotos" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <?php foreach ($fotosArray as $i => $f): ?>
            <div class="foto-item bg-white p-2 rounded-lg shadow relative cursor-grab active:cursor-grabbing select-none"
                 data-id="<?php echo (int) $f['id_foto']; ?>">

                <img src="<?= BASE_URL ?>/public/uploads/productos/<?php echo htmlspecialchars($f['imagen']); ?>"
                     alt=""
                     draggable="false"
                     class="w-full aspect-[3/4] object-cover rounded pointer-events-none">

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

<!-- ── MODAL DE RECORTE ────────────────────────────────────────── -->
<div id="modalRecorte" class="hidden fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-3xl max-h-full flex flex-col overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b">
            <h3 class="font-bold text-gray-800">Recortar foto</h3>
            <div class="flex gap-1 text-xs" id="botonesProporcion">
                <button type="button" data-ratio="0.75" class="px-3 py-1.5 rounded-md bg-gray-900 text-white">3:4</button>
                <button type="button" data-ratio="1"    class="px-3 py-1.5 rounded-md bg-gray-100 text-gray-700">1:1</button>
                <button type="button" data-ratio="NaN"  class="px-3 py-1.5 rounded-md bg-gray-100 text-gray-700">Libre</button>
            </div>
        </div>

        <div class="flex-1 min-h-0 bg-gray-900" style="height: 60vh;">
            <img id="imagenRecorte" src="" alt="" class="block max-w-full">
        </div>

        <div class="flex items-center justify-between px-5 py-3 border-t">
            <button type="button" id="btnRotar" class="text-sm text-gray-600 hover:text-gray-900">↻ Rotar</button>
            <div class="flex gap-2">
                <button type="button" id="btnCancelarRecorte" class="px-4 py-2 rounded-lg border text-gray-700 text-sm">Cancelar</button>
                <button type="button" id="btnAplicarRecorte" class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm font-semibold">Aplicar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>

<script>
(function () {
    const CSRF        = <?php echo json_encode(csrf_token()); ?>;
    const ID_PRODUCTO = <?php echo (int) $producto['id_producto']; ?>;
    const URL_SUBIR   = '<?= BASE_URL ?>/admin/productos/subir-foto-ajax';
    const URL_ORDEN   = '<?= BASE_URL ?>/admin/productos/ordenar-fotos';
    const LUGARES     = <?php echo (int) $lugares; ?>;
    const MAX_LADO    = 1600;   // px del lado más largo
    const CALIDAD     = 0.85;   // JPEG

    // ══════════════════════════════════════════════════════════════
    // SUBIDA MÚLTIPLE
    // ══════════════════════════════════════════════════════════════
    const input = document.getElementById('inputFotos');

    if (input) {
        const zona      = document.getElementById('zonaSubida');
        const cola      = document.getElementById('cola');
        const colaLista = document.getElementById('colaLista');
        const colaMsg   = document.getElementById('colaMensaje');
        const btnSubir  = document.getElementById('btnSubir');
        const btnLimpiar = document.getElementById('btnLimpiar');

        let items    = [];      // { id, file, blob, preview, estado, error, progreso }
        let subiendo = false;
        let contador = 0;

        // ── Elegir archivos ───────────────────────────────────────
        input.addEventListener('change', () => {
            agregar(input.files);
            input.value = '';
        });

        ['dragover', 'dragenter'].forEach(ev => zona.addEventListener(ev, e => {
            e.preventDefault();
            zona.classList.add('border-gray-900', 'bg-gray-50');
        }));
        ['dragleave', 'drop'].forEach(ev => zona.addEventListener(ev, e => {
            e.preventDefault();
            zona.classList.remove('border-gray-900', 'bg-gray-50');
        }));
        zona.addEventListener('drop', e => agregar(e.dataTransfer.files));

        function agregar(fileList) {
            if (subiendo) return;

            const imagenes = [...fileList].filter(f => f.type.startsWith('image/'));
            const libres   = LUGARES - items.length;
            const tomar    = imagenes.slice(0, Math.max(0, libres));

            tomar.forEach(file => {
                items.push({
                    id: ++contador,
                    file,
                    blob: null,                         // se genera al recortar o al subir
                    preview: URL.createObjectURL(file),
                    estado: 'pendiente',
                    error: '',
                    progreso: 0
                });
            });

            if (imagenes.length > tomar.length) {
                colaMsg.textContent = `Se agregaron ${tomar.length}. El máximo es ${LUGARES} foto(s) más para este producto.`;
                colaMsg.className = 'text-sm text-orange-600';
            } else {
                colaMsg.textContent = '';
            }

            render();
        }

        // ── Dibujar la cola ───────────────────────────────────────
        function render() {
            cola.classList.toggle('hidden', items.length === 0);
            colaLista.innerHTML = '';

            items.forEach(it => {
                const div = document.createElement('div');
                div.className = 'relative bg-gray-50 rounded-lg p-2 border';

                let estadoHtml = '';
                if (it.estado === 'subiendo') {
                    estadoHtml = `<div class="h-1.5 bg-gray-200 rounded mt-2 overflow-hidden">
                                      <div class="h-full bg-gray-900 transition-all" style="width:${it.progreso}%"></div>
                                  </div>`;
                } else if (it.estado === 'listo') {
                    estadoHtml = '<p class="text-xs text-green-700 mt-2">✓ Subida</p>';
                } else if (it.estado === 'error') {
                    estadoHtml = `<p class="text-xs text-red-600 mt-2">${escapar(it.error)}</p>`;
                }

                const acciones = (it.estado === 'pendiente' || it.estado === 'error') && !subiendo
                    ? `<div class="flex justify-between mt-2 text-xs">
                           <button type="button" data-accion="recortar" data-id="${it.id}" class="text-blue-600 hover:underline">✂ Recortar</button>
                           <button type="button" data-accion="quitar" data-id="${it.id}" class="text-gray-400 hover:text-red-600">Quitar</button>
                       </div>`
                    : '';

                div.innerHTML = `
                    <img src="${it.preview}" class="w-full aspect-[3/4] object-cover rounded ${it.estado === 'listo' ? 'opacity-60' : ''}">
                    ${it.blob ? '<span class="absolute top-3 left-3 bg-white/90 text-xs px-2 py-0.5 rounded">Recortada</span>' : ''}
                    ${estadoHtml}
                    ${acciones}
                `;
                colaLista.appendChild(div);
            });

            const pendientes = items.filter(i => i.estado === 'pendiente' || i.estado === 'error').length;
            btnSubir.disabled = subiendo || pendientes === 0;
            btnSubir.classList.toggle('opacity-50', btnSubir.disabled);
            btnSubir.textContent = subiendo ? 'Subiendo…' : `Subir ${pendientes} foto(s)`;
            btnLimpiar.disabled = subiendo;
        }

        colaLista.addEventListener('click', e => {
            const btn = e.target.closest('button[data-accion]');
            if (!btn) return;

            const it = items.find(i => i.id === Number(btn.dataset.id));
            if (!it) return;

            if (btn.dataset.accion === 'quitar') {
                URL.revokeObjectURL(it.preview);
                items = items.filter(i => i !== it);
                render();
            } else if (btn.dataset.accion === 'recortar') {
                abrirRecorte(it);
            }
        });

        btnLimpiar.addEventListener('click', () => {
            items.forEach(i => URL.revokeObjectURL(i.preview));
            items = [];
            colaMsg.textContent = '';
            render();
        });

        // ── Achicar en el navegador (si no se recortó) ─────────────
        async function achicar(file) {
            const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
            const escala = Math.min(1, MAX_LADO / Math.max(bitmap.width, bitmap.height));
            const canvas = document.createElement('canvas');
            canvas.width  = Math.round(bitmap.width * escala);
            canvas.height = Math.round(bitmap.height * escala);
            canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
            bitmap.close();
            return canvasABlob(canvas);
        }

        function canvasABlob(canvas) {
            return new Promise(res => {
                const ctx = canvas.getContext('2d');
                // Fondo blanco por si la imagen tenía transparencia (JPEG no la soporta)
                ctx.globalCompositeOperation = 'destination-over';
                ctx.fillStyle = '#fff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                canvas.toBlob(res, 'image/jpeg', CALIDAD);
            });
        }

        // ── Subir de a una ────────────────────────────────────────
        function subirUna(it) {
            return new Promise(async resolve => {
                try {
                    const blob = it.blob || await achicar(it.file);

                    const datos = new FormData();
                    datos.append('csrf_token', CSRF);
                    datos.append('id_producto', ID_PRODUCTO);
                    datos.append('foto', blob, 'foto.jpg');

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', URL_SUBIR);

                    xhr.upload.onprogress = e => {
                        if (e.lengthComputable) {
                            it.progreso = Math.round(e.loaded / e.total * 100);
                            render();
                        }
                    };

                    xhr.onload = () => {
                        let json = null;
                        try { json = JSON.parse(xhr.responseText); } catch (e) {}

                        if (json && json.ok) {
                            it.estado = 'listo';
                        } else {
                            it.estado = 'error';
                            it.error  = json ? json.error : 'Sesión expirada. Recargá la página.';
                        }
                        resolve();
                    };

                    xhr.onerror = () => {
                        it.estado = 'error';
                        it.error  = 'Sin conexión';
                        resolve();
                    };

                    xhr.send(datos);

                } catch (e) {
                    it.estado = 'error';
                    it.error  = 'No se pudo leer la imagen';
                    resolve();
                }
            });
        }

        btnSubir.addEventListener('click', async () => {
            subiendo = true;
            colaMsg.textContent = '';

            for (const it of items) {
                if (it.estado !== 'pendiente' && it.estado !== 'error') continue;
                it.estado   = 'subiendo';
                it.progreso = 0;
                it.error    = '';
                render();
                await subirUna(it);
                render();
            }

            subiendo = false;
            const errores = items.filter(i => i.estado === 'error').length;
            const listas  = items.filter(i => i.estado === 'listo').length;

            if (errores === 0) {
                colaMsg.textContent = '✓ Todas las fotos se subieron. Actualizando…';
                colaMsg.className = 'text-sm text-green-700';
                setTimeout(() => location.reload(), 800);
            } else {
                colaMsg.innerHTML = `${listas} subida(s), ${errores} con error. Podés reintentar o
                    <a href="" class="underline">recargar la página</a> para ver las que se subieron.`;
                colaMsg.className = 'text-sm text-red-600';
            }

            render();
        });

        // ══════════════════════════════════════════════════════════
        // RECORTE
        // ══════════════════════════════════════════════════════════
        const modal      = document.getElementById('modalRecorte');
        const imgRecorte = document.getElementById('imagenRecorte');
        let cropper      = null;
        let itemActual   = null;
        let ratioActual  = 3 / 4;

        function abrirRecorte(it) {
            itemActual = it;
            imgRecorte.src = URL.createObjectURL(it.file);   // siempre desde el original
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            imgRecorte.onload = () => {
                if (cropper) cropper.destroy();
                cropper = new Cropper(imgRecorte, {
                    aspectRatio: ratioActual,
                    viewMode: 1,
                    autoCropArea: 1,
                    background: false,
                    responsive: true
                });
            };
        }

        function cerrarRecorte() {
            if (cropper) { cropper.destroy(); cropper = null; }
            URL.revokeObjectURL(imgRecorte.src);
            imgRecorte.src = '';
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            itemActual = null;
        }

        document.getElementById('botonesProporcion').addEventListener('click', e => {
            const btn = e.target.closest('button[data-ratio]');
            if (!btn || !cropper) return;

            ratioActual = Number(btn.dataset.ratio);
            cropper.setAspectRatio(ratioActual);

            btn.parentElement.querySelectorAll('button').forEach(b => {
                b.className = 'px-3 py-1.5 rounded-md ' + (b === btn ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700');
            });
        });

        document.getElementById('btnRotar').addEventListener('click', () => cropper && cropper.rotate(90));
        document.getElementById('btnCancelarRecorte').addEventListener('click', cerrarRecorte);

        document.getElementById('btnAplicarRecorte').addEventListener('click', async () => {
            if (!cropper || !itemActual) return;

            const canvas = cropper.getCroppedCanvas({
                maxWidth: MAX_LADO,
                maxHeight: MAX_LADO,
                imageSmoothingQuality: 'high'
            });

            const blob = await canvasABlob(canvas);
            const it   = itemActual;

            URL.revokeObjectURL(it.preview);
            it.blob    = blob;
            it.preview = URL.createObjectURL(blob);

            cerrarRecorte();
            render();
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) cerrarRecorte();
        });
    }

    // ══════════════════════════════════════════════════════════════
    // ORDENAR FOTOS EXISTENTES (arrastrar)
    // ══════════════════════════════════════════════════════════════
    const lista  = document.getElementById('listaFotos');
    const estado = document.getElementById('estadoOrden');

    if (lista && lista.children.length > 1) {
        function mostrarEstado(texto, clase) {
            estado.textContent = texto;
            estado.className   = 'text-xs ' + clase;
        }

        function refrescarEtiquetas() {
            lista.querySelectorAll('.foto-item').forEach((item, i) => {
                item.querySelector('.foto-numero').textContent = i + 1;
                item.querySelector('.foto-principal').classList.toggle('hidden', i !== 0);
            });
        }

        async function guardarOrden() {
            const datos = new FormData();
            datos.append('csrf_token', CSRF);
            datos.append('id_producto', ID_PRODUCTO);
            lista.querySelectorAll('.foto-item').forEach(el => datos.append('ids[]', el.dataset.id));

            mostrarEstado('Guardando…', 'text-gray-400');

            try {
                const resp   = await fetch(URL_ORDEN, { method: 'POST', body: datos, credentials: 'same-origin' });
                const esJson = (resp.headers.get('Content-Type') || '').includes('application/json');

                if (!esJson) {
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
            filter: 'form, button',
            preventOnFilter: false,
            delay: 150,
            delayOnTouchOnly: true,
            onEnd: function (evt) {
                if (evt.oldIndex === evt.newIndex) return;
                refrescarEtiquetas();
                guardarOrden();
            }
        });
    }

    function escapar(t) {
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }
})();
</script>