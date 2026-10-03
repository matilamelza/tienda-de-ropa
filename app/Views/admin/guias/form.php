<?php
$msg = $_SESSION['guias_msg'] ?? null;
unset($_SESSION['guias_msg']);

$input  = 'w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-200';
$nueva  = (int) $guia['id_guia'] === 0;
?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>/admin/guias" class="text-sm text-gray-500 hover:text-gray-900">← Guías de talles</a>
    <h2 class="text-2xl font-bold text-gray-800 mt-1"><?= $nueva ? 'Nueva guía' : 'Editar guía' ?></h2>
</div>

<?php if ($msg): ?>
    <div class="mb-4 px-4 py-3 rounded-xl text-sm border
        <?= $msg[0] === 'ok' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
        <?= htmlspecialchars($msg[1]) ?>
    </div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>/admin/guias/guardar" enctype="multipart/form-data" id="formGuia" class="space-y-6 max-w-4xl">
    <?= csrf_field() ?>
    <input type="hidden" name="id_guia" value="<?= (int) $guia['id_guia'] ?>">
    <input type="hidden" name="columnas_json" id="columnasJson">
    <input type="hidden" name="filas_json" id="filasJson">

    <div class="bg-white rounded-lg shadow p-5">
        <label class="block">
            <span class="block text-sm font-medium text-gray-700 mb-1">Nombre</span>
            <input name="nombre" required maxlength="80" value="<?= htmlspecialchars($guia['nombre']) ?>"
                   placeholder="Ej: Zapatillas adulto, Remeras, Jeans mujer" class="<?= $input ?>">
        </label>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-5 py-4 border-b flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="font-bold text-gray-800">Tabla</h3>
                <p class="text-xs text-gray-400">
                    Las <strong>medidas</strong> pueden ser un número (24,5) o un rango (88–94). Las que marques como
                    "Medida" sirven para recomendarle el talle al cliente.
                </p>
            </div>
            <div class="flex gap-2 text-sm">
                <button type="button" onclick="agregarColumna()" class="px-3 py-1.5 rounded-lg border hover:bg-gray-50">+ Columna</button>
                <button type="button" onclick="agregarFila()" class="px-3 py-1.5 rounded-lg border hover:bg-gray-50">+ Fila</button>
            </div>
        </div>

        <div class="overflow-x-auto p-3">
            <table class="text-sm border-separate" style="border-spacing: 6px">
                <thead id="tablaHead"></thead>
                <tbody id="tablaBody"></tbody>
            </table>
        </div>
    </div>

    <!-- Consejos e imagen -->
    <div class="bg-white rounded-lg shadow p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
        <label class="block">
            <span class="block text-sm font-medium text-gray-700 mb-1">Cómo medirse y consejos</span>
            <textarea name="consejos" rows="6" maxlength="2000" class="<?= $input ?>"
                      placeholder="Ej: Medí el pie apoyado en una hoja, del talón a la punta. Si estás entre dos talles, elegí el más grande."><?= htmlspecialchars($guia['consejos'] ?? '') ?></textarea>
            <span class="block text-xs text-gray-400 mt-1">Cada renglón se muestra como un consejo aparte.</span>
        </label>

        <div>
            <span class="block text-sm font-medium text-gray-700 mb-1">Imagen <span class="font-normal text-gray-400">(opcional)</span></span>
            <?php if (!empty($guia['imagen'])): ?>
                <img src="<?= BASE_URL ?>/public/uploads/guias/<?= htmlspecialchars($guia['imagen']) ?>" alt=""
                     class="w-full max-h-48 object-contain bg-gray-50 rounded-lg border mb-2">
                <label class="flex items-center gap-2 text-sm text-gray-600 mb-2">
                    <input type="checkbox" name="quitar_imagen" value="1"> Quitar imagen
                </label>
            <?php endif; ?>
            <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp"
                   class="text-sm file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
            <span class="block text-xs text-gray-400 mt-1">Un dibujo de dónde medir (el pie, el pecho, la cintura…). JPG, PNG o WebP, hasta 3 MB.</span>
        </div>
    </div>

    <div class="flex justify-end gap-2">
        <a href="<?= BASE_URL ?>/admin/guias" class="px-4 py-2 rounded-lg border bg-white text-gray-700">Cancelar</a>
        <button class="px-5 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">Guardar guía</button>
    </div>
</form>

<script>
(function () {
    const MAX_COLS  = <?= GuiaTalles::MAX_COLUMNAS ?>;
    const MAX_FILAS = <?= GuiaTalles::MAX_FILAS ?>;
    const TIPOS     = <?= json_encode(GuiaTalles::TIPOS, JSON_UNESCAPED_UNICODE) ?>;
    const esc       = t => { const d = document.createElement('div'); d.textContent = t ?? ''; return d.innerHTML; };

    let cols  = <?= json_encode($guia['columnas'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
    let filas = <?= json_encode($guia['filas'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

    const celda = 'border rounded-md px-2 py-1.5 w-28 focus:outline-none focus:ring-2 focus:ring-gray-200';

    function pintar() {
        // Encabezado: nombre y tipo de cada columna
        document.getElementById('tablaHead').innerHTML = '<tr>' + cols.map((c, i) => `
            <th class="align-top text-left font-normal">
                <input value="${esc(c.nombre)}" data-col="${i}" data-campo="nombre" maxlength="30"
                       class="${celda} font-semibold ${c.tipo === 'talle' ? 'bg-gray-50' : ''}">
                <div class="flex items-center gap-1 mt-1">
                    <select data-col="${i}" data-campo="tipo" class="border rounded-md px-1 py-1 text-xs bg-white w-full">
                        ${Object.entries(TIPOS).map(([k, t]) => `<option value="${k}" ${c.tipo === k ? 'selected' : ''}>${k === 'medida' ? 'Medida' : t}</option>`).join('')}
                    </select>
                    ${cols.length > 1 ? `<button type="button" data-quitar-col="${i}" class="text-gray-300 hover:text-red-600 px-1" title="Quitar columna">✕</button>` : ''}
                </div>
            </th>`).join('') + '<th></th></tr>';

        // Filas
        document.getElementById('tablaBody').innerHTML = filas.map((f, r) => '<tr>' + cols.map((c, i) => `
            <td><input value="${esc(f[i] ?? '')}" data-fila="${r}" data-col="${i}" maxlength="30"
                       class="${celda} ${c.tipo === 'talle' ? 'font-semibold' : ''}"
                       placeholder="${c.tipo === 'medida' ? 'Ej: 24,5 o 88–94' : ''}"></td>`).join('')
            + `<td><button type="button" data-quitar-fila="${r}" class="text-gray-300 hover:text-red-600 px-1" title="Quitar fila">✕</button></td></tr>`
        ).join('');
    }

    // Editar celdas y encabezados
    document.getElementById('formGuia').addEventListener('input', e => {
        const t = e.target;
        if (t.dataset.fila !== undefined) {
            filas[t.dataset.fila][t.dataset.col] = t.value;
        } else if (t.dataset.campo === 'nombre') {
            cols[t.dataset.col].nombre = t.value;
        }
    });
    document.getElementById('formGuia').addEventListener('change', e => {
        const t = e.target;
        if (t.dataset.campo !== 'tipo') return;
        const i = +t.dataset.col;
        // Solo una columna puede ser "Talle"
        if (t.value === 'talle') cols.forEach((c, k) => { if (k !== i && c.tipo === 'talle') c.tipo = 'info'; });
        cols[i].tipo = t.value;
        pintar();
    });

    // Quitar filas y columnas
    document.getElementById('formGuia').addEventListener('click', e => {
        const t = e.target;
        if (t.dataset.quitarFila !== undefined) {
            filas.splice(+t.dataset.quitarFila, 1);
            pintar();
        }
        if (t.dataset.quitarCol !== undefined) {
            const i = +t.dataset.quitarCol;
            if (!confirm('¿Quitar la columna "' + cols[i].nombre + '" con todos sus valores?')) return;
            cols.splice(i, 1);
            filas.forEach(f => f.splice(i, 1));
            if (!cols.some(c => c.tipo === 'talle')) cols[0].tipo = 'talle';
            pintar();
        }
    });

    window.agregarColumna = function () {
        if (cols.length >= MAX_COLS) { alert('Máximo ' + MAX_COLS + ' columnas.'); return; }
        cols.push({ nombre: 'Medida (cm)', tipo: 'medida' });
        filas.forEach(f => f.push(''));
        pintar();
    };

    window.agregarFila = function () {
        if (filas.length >= MAX_FILAS) { alert('Máximo ' + MAX_FILAS + ' filas.'); return; }
        filas.push(cols.map(() => ''));
        pintar();
        const ultimas = document.querySelectorAll('#tablaBody tr:last-child input');
        if (ultimas[0]) ultimas[0].focus();
    };

    // Enter en la última fila: agrega otra
    document.getElementById('tablaBody').addEventListener('keydown', e => {
        if (e.key !== 'Enter' || e.target.dataset.fila === undefined) return;
        e.preventDefault();
        if (+e.target.dataset.fila === filas.length - 1) agregarFila();
    });

    document.getElementById('formGuia').addEventListener('submit', () => {
        document.getElementById('columnasJson').value = JSON.stringify(cols);
        document.getElementById('filasJson').value    = JSON.stringify(filas);
    });

    pintar();
})();
</script>