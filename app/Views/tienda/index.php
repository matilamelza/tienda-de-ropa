<?php
function urlFiltro(array $nuevos): string {
    $base = array_merge($_GET, $nuevos);
    $base = array_filter($base, fn($v) => $v !== '' && $v !== '0' && $v !== null);
    unset($base['route']);
    return BASE_URL . '/tienda?' . http_build_query($base);
}
?>

<?php if (!$hayFiltros): ?>
<!-- ── Hero (solo sin filtros activos) ─────────────────────────────────────── -->
<section class="bg-gray-100">
    <div class="max-w-7xl mx-auto px x-4 py-16 grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
        <div>
            <p class="text-sm uppercase tracking-widest text-gray-500 mb-3">Nueva temporada</p>
            <h2 class="text-4xl md:text-6xl font-bold text-gray-900 leading-tight">
                <?= htmlspecialchars($config['hero_titulo'] ?? 'Ropa con estilo para todos los días') ?>
            </h2>
            <p class="mt-4 text-gray-600 max-w-lg">
                <?= htmlspecialchars($config['hero_subtitulo'] ?? 'Descubrí prendas seleccionadas, talles disponibles y stock actualizado.') ?>
            </p>
            <a href="#productos" class="inline-block mt-6 bg-gray-900 text-white px-6 py-3 rounded-full hover:bg-gray-800">
                <?= htmlspecialchars($config['hero_boton_texto'] ?? 'Ver productos') ?>
            </a>
        </div>
        <div class="bg-white rounded-3xl shadow p-6">
            <?php if (!empty($config['hero_imagen'])): ?>
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($config['hero_imagen']) ?>"
                     alt="Banner" class="w-full h-80 rounded-2xl object-cover">
            <?php else: ?>
                <div class="h-80 rounded-2xl bg-gradient-to-br from-gray-200 to-gray-400 flex items-center justify-center">
                    <span class="text-gray-600 font-medium">Banner / imagen principal</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── Sección de productos ─────────────────────────────────────────────────── -->
<section id="productos" class="max-w-7xl mx-auto px-4 py-10">

    <!-- Barra de filtros -->
    <div class="bg-white border rounded-2xl px-4 py-3 mb-8 flex flex-wrap gap-3 items-center">

        <!-- Buscador -->
        <form method="GET" action="<?= BASE_URL ?>/tienda" class="flex gap-2 flex-1 min-w-[200px]">
            <?php foreach (['categoria','marca','precio_min','precio_max','orden'] as $k): ?>
                <?php if (!empty($filtros[$k])): ?>
                    <input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($filtros[$k]) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <input type="text" name="q" value="<?= htmlspecialchars($filtros['q']) ?>"
                   placeholder="Buscar productos…"
                   class="flex-1 border rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-200">
            <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded-xl text-sm hover:bg-gray-800">
                Buscar
            </button>
        </form>

        <div class="flex flex-wrap gap-2 items-center">

            <!-- Categoría -->
            <?php if (!empty($categorias) && $categorias->num_rows > 0): ?>
            <select onchange="aplicarFiltro('categoria', this.value)"
                    class="border rounded-xl px-3 py-2 text-sm focus:outline-none cursor-pointer">
                <option value="">Todas las categorías</option>
                <?php $categorias->data_seek(0); while ($cat = $categorias->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($cat['slug']) ?>"
                            <?= $filtros['categoria'] === $cat['slug'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <?php endif; ?>

            <!-- Marca -->
            <?php if (!empty($marcas)): ?>
            <select onchange="aplicarFiltro('marca', this.value)"
                    class="border rounded-xl px-3 py-2 text-sm focus:outline-none cursor-pointer">
                <option value="">Todas las marcas</option>
                <?php foreach ($marcas as $marca): ?>
                    <option value="<?= $marca['id_marca'] ?>"
                            <?= $filtros['marca'] === (int)$marca['id_marca'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($marca['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <!-- Precio -->
            <div class="flex items-center gap-1 border rounded-xl px-3 py-2">
                <span class="text-xs text-gray-400">$</span>
                <input type="number" id="precioMin" min="0"
                       value="<?= htmlspecialchars($filtros['precio_min']) ?>"
                       placeholder="Mín"
                       class="w-16 text-sm focus:outline-none"
                       onblur="aplicarPrecios()">
                <span class="text-gray-300 mx-1">—</span>
                <input type="number" id="precioMax" min="0"
                       value="<?= htmlspecialchars($filtros['precio_max']) ?>"
                       placeholder="Máx"
                       class="w-16 text-sm focus:outline-none"
                       onblur="aplicarPrecios()">
            </div>

            <!-- Orden -->
            <select onchange="aplicarFiltro('orden', this.value)"
                    class="border rounded-xl px-3 py-2 text-sm focus:outline-none cursor-pointer">
                <option value="reciente"    <?= $filtros['orden'] === 'reciente'    ? 'selected' : '' ?>>Más recientes</option>
                <option value="precio_asc"  <?= $filtros['orden'] === 'precio_asc'  ? 'selected' : '' ?>>Precio ↑</option>
                <option value="precio_desc" <?= $filtros['orden'] === 'precio_desc' ? 'selected' : '' ?>>Precio ↓</option>
                <option value="nombre"      <?= $filtros['orden'] === 'nombre'      ? 'selected' : '' ?>>Nombre A-Z</option>
            </select>

            <!-- Limpiar filtros -->
            <?php if ($hayFiltros): ?>
                <a href="<?= BASE_URL ?>/tienda"
                   class="text-sm text-gray-400 hover:text-red-500 px-2 py-2 whitespace-nowrap">
                    ✕ Limpiar
                </a>
            <?php endif; ?>

        </div>
    </div>

    <!-- Tags de filtros activos + contador -->
    <?php if ($hayFiltros): ?>
    <div class="mb-6 flex items-center gap-2 flex-wrap">
        <?php if ($filtros['q']): ?>
            <span class="bg-gray-100 text-gray-700 text-xs px-3 py-1 rounded-full">
                "<?= htmlspecialchars($filtros['q']) ?>"
            </span>
        <?php endif; ?>
        <?php if ($categoriaActual): ?>
            <span class="bg-gray-100 text-gray-700 text-xs px-3 py-1 rounded-full">
                <?= htmlspecialchars($categoriaActual['nombre']) ?>
            </span>
        <?php endif; ?>
        <?php if ($filtros['precio_min'] || $filtros['precio_max']): ?>
            <span class="bg-gray-100 text-gray-700 text-xs px-3 py-1 rounded-full">
                $<?= $filtros['precio_min'] ?: '0' ?> — $<?= $filtros['precio_max'] ?: '∞' ?>
            </span>
        <?php endif; ?>
        <span class="text-sm text-gray-400 ml-auto">
            <?= count($productos) ?> producto<?= count($productos) !== 1 ? 's' : '' ?>
        </span>
    </div>
    <?php endif; ?>

    <!-- Grid de productos -->
    <?php if (!empty($productos)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php foreach ($productos as $p): ?>
                <a href="<?= BASE_URL ?>/producto/<?= htmlspecialchars($p['slug']) ?>" class="group block">
                    <div class="aspect-[3/4] bg-gray-100 rounded-2xl overflow-hidden shadow-sm group-hover:shadow-lg transition">
                        <?php if (!empty($p['foto_principal'])): ?>
                            <img src="<?= BASE_URL ?>/public/uploads/productos/<?= htmlspecialchars($p['foto_principal']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400 text-sm">Sin imagen</div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4">
                        <p class="text-xs text-gray-400">
                            <?= htmlspecialchars($p['categoria']) ?>
                            <?= !empty($p['marca']) ? ' · ' . htmlspecialchars($p['marca']) : '' ?>
                        </p>
                        <h3 class="font-semibold text-gray-900 mt-1"><?= htmlspecialchars($p['nombre']) ?></h3>
                        <p class="font-bold mt-2">$<?= number_format($p['precio_base'], 2, ',', '.') ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-gray-50 rounded-2xl p-12 text-center">
            <p class="text-4xl mb-3">🔍</p>
            <h3 class="text-xl font-bold text-gray-800">Sin resultados</h3>
            <p class="text-gray-500 mt-2">
                <?= $hayFiltros ? 'Probá con otros filtros o términos de búsqueda.' : 'Todavía no hay productos cargados.' ?>
            </p>
            <?php if ($hayFiltros): ?>
                <a href="<?= BASE_URL ?>/tienda"
                   class="inline-block mt-4 text-sm text-gray-600 underline hover:text-gray-900">
                    Ver todos los productos
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</section>

<script>
function aplicarFiltro(clave, valor) {
    const params = new URLSearchParams(window.location.search);
    if (valor === '' || valor === '0') {
        params.delete(clave);
    } else {
        params.set(clave, valor);
    }
    params.delete('route');
    window.location.href = '<?= BASE_URL ?>/tienda?' + params.toString();
}

function aplicarPrecios() {
    const min = document.getElementById('precioMin').value;
    const max = document.getElementById('precioMax').value;
    const params = new URLSearchParams(window.location.search);
    min ? params.set('precio_min', min) : params.delete('precio_min');
    max ? params.set('precio_max', max) : params.delete('precio_max');
    params.delete('route');
    window.location.href = '<?= BASE_URL ?>/tienda?' + params.toString();
}
</script>