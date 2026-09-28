<?php $input = 'w-full border rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring focus:ring-gray-200'; ?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Catálogo</h2>
    <p class="text-gray-500 text-sm">Armá un catálogo con fotos, precios y talles para mandar por WhatsApp o imprimir.</p>
</div>

<form method="GET" action="<?= BASE_URL ?>/admin/catalogo/ver" target="_blank"
      class="bg-white rounded-lg shadow p-6 space-y-5 max-w-2xl">

    <?php if (!empty($ids)): ?>
        <input type="hidden" name="ids" value="<?= htmlspecialchars(implode(',', $ids)) ?>">
        <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-lg px-4 py-3 text-sm">
            📄 Catálogo con los <strong><?= count($ids) ?> producto(s)</strong> que seleccionaste.
            <a href="<?= BASE_URL ?>/admin/catalogo" class="underline ml-1">Usar filtros en su lugar</a>
        </div>
    <?php endif; ?>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
        <input name="titulo" maxlength="80" class="<?= $input ?>" placeholder="Ej: Colección Otoño · Lista mayorista">
    </div>

    <?php if (empty($ids)): ?>
        <div>
            <p class="text-sm font-medium text-gray-700 mb-2">¿Qué productos?</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <select name="categoria" class="<?= $input ?>">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int) $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="marca" class="<?= $input ?>">
                    <option value="">Todas las marcas</option>
                    <?php foreach ($marcas as $m): ?>
                        <option value="<?= (int) $m['id_marca'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="flex items-center gap-2 mt-3 text-sm">
                <input type="checkbox" name="oferta" value="1">
                Solo productos en oferta
            </label>
        </div>
    <?php endif; ?>

    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="con_stock" value="1" checked>
        Solo con stock <span class="text-gray-400">(y mostrar solo los talles disponibles)</span>
    </label>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Precios</label>
            <select name="precios" class="<?= $input ?>">
                <option value="1">Mostrar</option>
                <option value="0">Ocultar</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ajuste de precio</label>
            <div class="relative">
                <input type="number" name="ajuste" step="0.01" min="-90" max="200" value="0" class="<?= $input ?> pr-7">
                <span class="absolute right-3 top-2 text-gray-400">%</span>
            </div>
            <p class="text-xs text-gray-400 mt-1">Ej: -20 para lista mayorista.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Por fila</label>
            <select name="columnas" class="<?= $input ?>">
                <option value="2">2 productos</option>
                <option value="3" selected>3 productos</option>
                <option value="4">4 productos</option>
            </select>
        </div>
    </div>

    <div class="flex justify-end pt-2">
        <button class="px-5 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">
            Generar catálogo →
        </button>
    </div>

    <p class="text-xs text-gray-400">
        Se abre en otra pestaña. Desde ahí, <strong>Imprimir → Guardar como PDF</strong> y lo mandás por WhatsApp.
    </p>
</form>