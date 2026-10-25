<!-- ── Sección de productos ─────────────────────────────────────────────────── -->
<section id="productos" class="max-w-7xl mx-auto px-4 py-10 scroll-mt-24">

    <!-- Barra de filtros -->
    <div class="bg-white border rounded-2xl px-4 py-3 mb-8 flex flex-wrap gap-3 items-center">

        <!-- Buscador -->
        <form method="GET" action="<?= BASE_URL ?>/tienda" class="flex gap-2 flex-1 min-w-[200px]">
            <?php foreach (['categoria', 'marca', 'precio_min', 'precio_max', 'orden', 'oferta', 'talle'] as $k): ?>
                <?php if (!empty($filtros[$k])): ?>
                    <input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars((string) $filtros[$k]) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <input type="text" name="q" value="<?= htmlspecialchars($filtros['q']) ?>"
                   placeholder="Buscar productos…"
                   class="flex-1 border rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-200">
            <button type="submit" class="btn-primario px-4 py-2 rounded-xl text-sm">Buscar</button>
        </form>

        <div class="flex flex-wrap gap-2 items-center">

            <!-- En oferta (solo si hay alguna vigente) -->
            <?php if (!empty($hayOfertas) || !empty($filtros['oferta'])): ?>
                <a href="<?= htmlspecialchars(urlFiltro(['oferta' => !empty($filtros['oferta']) ? '' : '1'])) ?>#productos"
                   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-sm font-medium whitespace-nowrap transition
                          <?= !empty($filtros['oferta'])
                              ? 'bg-red-600 text-white border-red-600'
                              : 'bg-white text-red-600 border-red-200 hover:bg-red-50' ?>">
                    🔥 En oferta
                    <?php if (!empty($filtros['oferta'])): ?><span class="opacity-75">✕</span><?php endif; ?>
                </a>
            <?php endif; ?>

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

            <!-- Talle (solo los que tienen stock) -->
            <?php if (!empty($tallesFiltro)): ?>
            <select onchange="aplicarFiltro('talle', this.value)"
                    class="border rounded-xl px-3 py-2 text-sm focus:outline-none cursor-pointer">
                <option value="">Todos los talles</option>
                <?php foreach ($tallesFiltro as $t): ?>
                    <option value="<?= htmlspecialchars($t) ?>" <?= $filtros['talle'] === $t ? 'selected' : '' ?>>
                        Talle <?= htmlspecialchars($t) ?>
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
                       onchange="aplicarPrecios()">
                <span class="text-gray-300 mx-1">—</span>
                <input type="number" id="precioMax" min="0"
                       value="<?= htmlspecialchars($filtros['precio_max']) ?>"
                       placeholder="Máx"
                       class="w-16 text-sm focus:outline-none"
                       onchange="aplicarPrecios()">
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
        <?php if (!empty($filtros['oferta'])): ?>
            <span class="bg-red-50 text-red-700 text-xs px-3 py-1 rounded-full">🔥 En oferta</span>
        <?php endif; ?>
        <?php if ($filtros['talle'] !== ''): ?>
            <span class="bg-gray-100 text-gray-700 text-xs px-3 py-1 rounded-full">Talle <?= htmlspecialchars($filtros['talle']) ?></span>
        <?php endif; ?>
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
                $<?= htmlspecialchars((string) ($filtros['precio_min'] ?: '0')) ?> — $<?= htmlspecialchars((string) ($filtros['precio_max'] ?: '∞')) ?>
            </span>
        <?php endif; ?>
        <span class="text-sm text-gray-400 ml-auto">
            <?= (int) $total ?> producto<?= $total !== 1 ? 's' : '' ?>
        </span>
    </div>
    <?php endif; ?>

    <!-- Grid de productos -->
    <?php if (!empty($productos)): ?>
        <div id="grillaProductos" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php foreach ($productos as $p): ?>
                <?php require __DIR__ . '/_tarjeta.php'; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($total > count($productos)): ?>
            <div id="cargarMas" class="mt-10 flex justify-center">
                <div id="cargando" class="hidden w-8 h-8 rounded-full border-2 border-gray-200 border-t-gray-900 animate-spin"></div>
                <button type="button" id="btnMas" onclick="cargarMas()"
                        class="px-6 py-3 rounded-full border text-sm font-medium hover:border-gray-900">
                    Ver más productos
                </button>
            </div>
        <?php endif; ?>
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
document.documentElement.style.scrollBehavior = 'smooth';

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

// ── Carga infinita: al acercarse al final, trae los siguientes ──
(function () {
    const grilla = document.getElementById('grillaProductos');
    const caja   = document.getElementById('cargarMas');
    if (!grilla || !caja) return;

    const QUERY = <?= json_encode($queryFiltros) ?>;
    let pagina   = 1;
    let cargando = false;
    let hayMas   = true;

    window.cargarMas = async function () {
        if (cargando || !hayMas) return;
        cargando = true;
        document.getElementById('cargando').classList.remove('hidden');
        document.getElementById('btnMas').classList.add('hidden');

        try {
            const r = await fetch('<?= BASE_URL ?>/tienda/mas?' + QUERY + (QUERY ? '&' : '') + 'pagina=' + (pagina + 1));
            const j = await r.json();

            // Agregar solo los que no están ya en pantalla
            const yaEstan = new Set([...grilla.querySelectorAll('[data-id]')].map(a => a.dataset.id));
            const tmp = document.createElement('div');
            tmp.innerHTML = j.html;
            let nuevos = 0;
            [...tmp.children].forEach(el => {
                if (!yaEstan.has(el.dataset.id)) { grilla.appendChild(el); nuevos++; }
            });

            pagina++;
            hayMas = j.hayMas && nuevos > 0;   // si no trajo nada nuevo, no sigue
        } catch (e) {
            // si falló, queda el botón para reintentar
        }

        cargando = false;
        document.getElementById('cargando').classList.add('hidden');
        if (hayMas) {
            document.getElementById('btnMas').classList.remove('hidden');
        } else {
            caja.remove();
        }
    };

    if ('IntersectionObserver' in window) {
        new IntersectionObserver(e => { if (e[0].isIntersecting) cargarMas(); }, { rootMargin: '400px 0px' }).observe(caja);
    }
})();
</script>