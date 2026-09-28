<?php
function urlFiltro(array $nuevos): string {
    $base = array_merge($_GET, $nuevos);
    $base = array_filter($base, fn($v) => $v !== '' && $v !== '0' && $v !== null);
    unset($base['route']);
    return BASE_URL . '/tienda?' . http_build_query($base);
}
?>

<?php if (!$hayFiltros && ($config['hero_estilo'] ?? '') !== 'ninguno'):
    $heroImg    = !empty($config['hero_imagen']) ? BASE_URL . '/' . $config['hero_imagen'] : '';
    $heroEstilo = $config['hero_estilo'] ?? '';

    if ($heroEstilo === '') {
        $heroEstilo = $heroImg ? 'imagen' : 'gradiente';
    }
    if ($heroEstilo === 'imagen' && !$heroImg) {
        $heroEstilo = 'gradiente';
    }

    $estilosHero = [
        'imagen'    => "background-image:url('" . htmlspecialchars($heroImg, ENT_QUOTES) . "');background-size:cover;background-position:center;",
        'gradiente' => 'background:linear-gradient(135deg, var(--color-primario), var(--color-secundario));',
        'color'     => 'background:var(--color-primario);',
    ];
    $heroBg       = $estilosHero[$heroEstilo] ?? $estilosHero['gradiente'];
    $heroEtiqueta = $config['hero_etiqueta'] ?? '';
?>
<!-- ── Hero (solo sin filtros activos) ─────────────────────────────────────── -->
<section class="relative overflow-hidden" style="<?= $heroBg ?>">

    <?php if ($heroEstilo === 'imagen'): ?>
        <div class="absolute inset-0 bg-gradient-to-r from-black/75 via-black/40 to-transparent"></div>
    <?php else: ?>
        <!-- Formas decorativas -->
        <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/10 blur-3xl"></div>
        <div class="absolute -bottom-32 left-1/3 w-80 h-80 rounded-full bg-white/10 blur-3xl"></div>
    <?php endif; ?>

    <div class="relative max-w-7xl mx-auto px-4 py-20 md:py-32">
        <div class="max-w-2xl text-white">

            <?php if (trim($heroEtiqueta) !== ''): ?>
                <span class="inline-flex items-center gap-2 bg-white/15 backdrop-blur-sm border border-white/25 text-white text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-6">
                    <span class="w-2 h-2 rounded-full animate-pulse" style="background:var(--color-acento)"></span>
                    <?= htmlspecialchars($heroEtiqueta) ?>
                </span>
            <?php endif; ?>

            <h2 class="text-4xl md:text-6xl font-bold leading-tight drop-shadow-sm">
                <?= htmlspecialchars($config['hero_titulo'] ?? 'Ropa con estilo para todos los días') ?>
            </h2>

            <p class="mt-5 text-lg md:text-xl text-white/85 max-w-xl">
                <?= htmlspecialchars($config['hero_subtitulo'] ?? 'Descubrí prendas seleccionadas, talles disponibles y stock actualizado.') ?>
            </p>

            <a href="#productos"
               class="inline-flex items-center gap-2 mt-8 bg-white text-gray-900 font-semibold px-8 py-3.5 rounded-full shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition">
                <?= htmlspecialchars($config['hero_boton_texto'] ?? 'Ver productos') ?>
                <span aria-hidden="true">→</span>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($destacados)): ?>
<!-- ── Destacados ──────────────────────────────────────────────────────────── -->
<section class="max-w-7xl mx-auto px-4 pt-10">
    <div class="flex items-end justify-between mb-5">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Destacados</h2>
        <a href="#productos" class="text-sm text-gray-500 hover:text-gray-900">Ver todo →</a>
    </div>

    <!-- Mobile: se desliza de costado · Desktop: grilla -->
    <div class="flex md:grid md:grid-cols-4 gap-4 md:gap-8 overflow-x-auto md:overflow-visible snap-x snap-mandatory -mx-4 px-4 md:mx-0 md:px-0 pb-2">
        <?php foreach ($destacados as $d): ?>
            <a href="<?= BASE_URL ?>/producto/<?= htmlspecialchars($d['slug']) ?>"
               class="group block shrink-0 w-[70%] sm:w-[45%] md:w-auto snap-start">
                <div class="relative aspect-[3/4] bg-gray-100 rounded-2xl overflow-hidden shadow-sm group-hover:shadow-lg transition">
                    <?php if (!empty($d['foto_principal'])): ?>
                        <img src="<?= BASE_URL ?>/public/uploads/productos/<?= htmlspecialchars($d['foto_principal']) ?>"
                             alt="<?= htmlspecialchars($d['nombre']) ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                             loading="lazy">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-gray-400 text-sm">Sin imagen</div>
                    <?php endif; ?>

                    <span class="absolute top-3 left-3 text-xs font-semibold px-2.5 py-1 rounded-full shadow-sm"
                          style="background: var(--color-acento); color: var(--color-boton-texto)">★ Destacado</span>

                    <?= html_badge_oferta((float) ($d['descuento_pct'] ?? 0), $d['descuento_etiqueta'] ?? null) ?>
                </div>
                <div class="mt-3">
                    <p class="text-xs text-gray-400">
                        <?= htmlspecialchars($d['categoria']) ?><?= !empty($d['marca']) ? ' · ' . htmlspecialchars($d['marca']) : '' ?>
                    </p>
                    <h3 class="font-semibold text-gray-900 mt-1 line-clamp-2"><?= htmlspecialchars($d['nombre']) ?></h3>
                    <p class="mt-1"><?= html_precio((float) $d['precio_base'], (float) ($d['descuento_pct'] ?? 0)) ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ── Sección de productos ─────────────────────────────────────────────────── -->
<section id="productos" class="max-w-7xl mx-auto px-4 py-10 scroll-mt-24">

    <!-- Barra de filtros -->
    <div class="bg-white border rounded-2xl px-4 py-3 mb-8 flex flex-wrap gap-3 items-center">

        <!-- Buscador -->
        <form method="GET" action="<?= BASE_URL ?>/tienda" class="flex gap-2 flex-1 min-w-[200px]">
            <?php foreach (['categoria','marca','precio_min','precio_max','orden','oferta'] as $k): ?>
                <?php if (!empty($filtros[$k])): ?>
                    <input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars((string) $filtros[$k]) ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <input type="text" name="q" value="<?= htmlspecialchars($filtros['q']) ?>"
                   placeholder="Buscar productos…"
                   class="flex-1 border rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-200">
            <button type="submit" class="btn-primario px-4 py-2 rounded-xl text-sm">
                Buscar
            </button>
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
        <?php if (!empty($filtros['oferta'])): ?>
            <span class="bg-red-50 text-red-700 text-xs px-3 py-1 rounded-full">🔥 En oferta</span>
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
            <?= count($productos) ?> producto<?= count($productos) !== 1 ? 's' : '' ?>
        </span>
    </div>
    <?php endif; ?>

    <!-- Grid de productos -->
    <?php if (!empty($productos)): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php foreach ($productos as $p): ?>
                <a href="<?= BASE_URL ?>/producto/<?= htmlspecialchars($p['slug']) ?>" class="group block">
                    <div class="relative aspect-[3/4] bg-gray-100 rounded-2xl overflow-hidden shadow-sm group-hover:shadow-lg transition">
                        <?php if (!empty($p['foto_principal'])): ?>
                            <img src="<?= BASE_URL ?>/public/uploads/productos/<?= htmlspecialchars($p['foto_principal']) ?>"
                                 alt="<?= htmlspecialchars($p['nombre']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400 text-sm">Sin imagen</div>
                        <?php endif; ?>

                        <?php if (!empty($p['destacado'])): ?>
                            <span class="absolute top-3 left-3 text-xs font-semibold px-2.5 py-1 rounded-full shadow-sm"
                                  style="background: var(--color-acento); color: var(--color-boton-texto)">★ Destacado</span>
                        <?php endif; ?>

                        <?= html_badge_oferta((float) ($p['descuento_pct'] ?? 0), $p['descuento_etiqueta'] ?? null) ?>
                    </div>
                    <div class="mt-4">
                        <p class="text-xs text-gray-400">
                            <?= htmlspecialchars($p['categoria']) ?>
                            <?= !empty($p['marca']) ? ' · ' . htmlspecialchars($p['marca']) : '' ?>
                        </p>
                        <h3 class="font-semibold text-gray-900 mt-1"><?= htmlspecialchars($p['nombre']) ?></h3>
                        <p class="mt-2"><?= html_precio((float) $p['precio_base'], (float) ($p['descuento_pct'] ?? 0)) ?></p>
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
</script>