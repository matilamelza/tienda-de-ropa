<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    // ── Cargar configuración desde la BD ──────────────────────
    require_once dirname(__DIR__, 2) . '/Models/ConfiguracionTienda.php';
    $cfg  = new ConfiguracionTienda();
    $conf = $cfg->todas();

    $nombreTienda = $conf['tienda_nombre'] ?? 'Mi Tienda';

    // ── SEO y vista previa al compartir ──────────────────────
    // Una página puede mandar $meta (titulo, descripcion, imagen, tipo, precio) para personalizarlo.
    $meta = $meta ?? [];

    $tituloPag = $meta['titulo']      ?? ($conf['seo_titulo'] ?? $nombreTienda);
    $metaDesc  = $meta['descripcion'] ?? ($conf['seo_descripcion'] ?? '');
    $metaKeys  = $conf['seo_keywords'] ?? '';
    $ogTipo    = $meta['tipo'] ?? 'website';
    $ogUrl     = url_absoluta(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'));

    // Imagen: la de la página, o el logo, o la imagen del banner
    $ogImagen = $meta['imagen'] ?? '';
    if ($ogImagen === '' && !empty($conf['tienda_logo'])) {
        $ogImagen = url_absoluta($conf['tienda_logo']);
    } elseif ($ogImagen === '' && !empty($conf['hero_imagen'])) {
        $ogImagen = url_absoluta($conf['hero_imagen']);
    }

    $fuente        = htmlspecialchars($conf['fuente_principal'] ?? 'Inter');
    $fuenteTitulos = htmlspecialchars($conf['fuente_titulos']  ?? 'Inter');
    $favicon       = $conf['tienda_favicon'] ?? '';

    // ── Contador de carrito ──────────────────────────────────
    $totalItemsCarrito = 0;
    if (!empty($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
        foreach ($_SESSION['carrito'] as $item) {
            $totalItemsCarrito += (int)($item['cantidad'] ?? 1);
        }
    }

    // ── Categorías del menú (a array, para usarlas en desktop y mobile) ──
    $itemsMenu = [];
    if (isset($categoriasMenu) && $categoriasMenu && $categoriasMenu->num_rows > 0) {
        $categoriasMenu->data_seek(0);
        while ($catMenu = $categoriasMenu->fetch_assoc()) {
            $itemsMenu[] = $catMenu;
        }
    }

    // Inicial para cuando no hay logo
    $inicial = mb_strtoupper(mb_substr(trim($nombreTienda), 0, 1));
    ?>

    <title><?= htmlspecialchars($tituloPag) ?></title>

    <?php if ($metaDesc): ?>
        <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
    <?php endif; ?>
    <?php if ($metaKeys): ?>
        <meta name="keywords" content="<?= htmlspecialchars($metaKeys) ?>">
    <?php endif; ?>
    <link rel="canonical" href="<?= htmlspecialchars($ogUrl) ?>">

    <!-- Vista previa al compartir (WhatsApp, Instagram, Facebook, Telegram) -->
    <meta property="og:site_name" content="<?= htmlspecialchars($nombreTienda) ?>">
    <meta property="og:type"      content="<?= htmlspecialchars($ogTipo) ?>">
    <meta property="og:title"     content="<?= htmlspecialchars($tituloPag) ?>">
    <?php if ($metaDesc): ?>
        <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
    <?php endif; ?>
    <meta property="og:url"       content="<?= htmlspecialchars($ogUrl) ?>">
    <meta property="og:locale"    content="es_AR">
    <?php if ($ogImagen): ?>
        <meta property="og:image"     content="<?= htmlspecialchars($ogImagen) ?>">
        <meta property="og:image:alt" content="<?= htmlspecialchars($tituloPag) ?>">
    <?php endif; ?>
    <?php if (isset($meta['precio'])): ?>
        <meta property="product:price:amount"   content="<?= number_format((float) $meta['precio'], 2, '.', '') ?>">
        <meta property="product:price:currency" content="ARS">
    <?php endif; ?>
    <meta name="twitter:card" content="<?= $ogImagen ? 'summary_large_image' : 'summary' ?>">

    <!-- Favicon -->
    <?php if ($favicon): ?>
        <link rel="icon" href="<?= BASE_URL ?>/<?= htmlspecialchars($favicon) ?>">
    <?php endif; ?>

    <!-- Google Fonts (solo las fuentes usadas) -->
    <?php
    $fontsNeeded = array_unique([$fuente, $fuenteTitulos]);
    $fontsQuery  = implode('&family=', array_map(fn($f) => urlencode($f) . ':wght@400;500;600;700', $fontsNeeded));
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=<?= $fontsQuery ?>&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <!-- CSS dinámico con las variables personalizadas -->
    <?= $cfg->generarCSS() ?>

</head>

<body class="bg-white text-gray-900">

<div class="sticky top-0 z-40">

<!-- ── Barra de anuncio ──────────────────────────────────────── -->
<?php if (!empty($conf['anuncio_activo']) && $conf['anuncio_activo'] === '1' && !empty($conf['anuncio_texto'])): ?>
    <div class="text-center text-sm py-2 px-4 font-medium"
         style="background-color: <?= htmlspecialchars($conf['anuncio_color_bg'] ?? '#111827') ?>;
                color: <?= htmlspecialchars($conf['anuncio_color_texto'] ?? '#fff') ?>">
        <?= htmlspecialchars($conf['anuncio_texto']) ?>
    </div>
<?php endif; ?>

<header class="border-b"
        style="background-color: var(--color-header-bg)">
    <div class="max-w-7xl mx-auto px-4">
        <div class="h-16 md:h-20 flex items-center justify-between gap-4">

            <!-- Hamburguesa (solo mobile) -->
            <button type="button"
                    onclick="abrirMenuMobile()"
                    aria-label="Abrir menú"
                    aria-controls="menuMobile"
                    aria-expanded="false"
                    id="btnMenuMobile"
                    class="md:hidden -ml-2 p-2 rounded-lg hover:bg-black/5">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>

             <!-- Logo + Nombre (nunca se achica: es el menú el que se adapta) -->
            <a href="<?= BASE_URL ?>/tienda" class="flex items-center gap-3 shrink-0">
                <?php if (!empty($conf['tienda_logo'])): ?>
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($conf['tienda_logo']) ?>"
                         alt="<?= htmlspecialchars($nombreTienda) ?>"
                         class="h-10 md:h-12 w-auto object-contain">
                <?php else: ?>
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center font-bold text-lg shrink-0"
                         style="background-color: var(--color-primario); color: var(--color-boton-texto)">
                        <?= htmlspecialchars($inicial) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <span class="block text-lg md:text-xl font-bold leading-none whitespace-nowrap">
                        <?= htmlspecialchars($nombreTienda) ?>
                    </span>
                    <?php if (!empty($conf['tienda_slogan'])): ?>
                        <span class="hidden lg:block text-xs text-gray-500 mt-1 whitespace-nowrap">
                            <?= htmlspecialchars($conf['tienda_slogan']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </a>

                        <!-- Nav categorías (desktop): entran todas las que caben; el resto va a "Más" -->
            <nav id="navCategorias" class="hidden md:flex flex-1 min-w-0 items-center justify-center gap-5 text-sm font-medium"
                 style="visibility: hidden">
                <a href="<?= BASE_URL ?>/tienda" class="hover:opacity-70 whitespace-nowrap shrink-0">Inicio</a>

                <?php foreach ($itemsMenu as $i => $catMenu): ?>
                    <a href="<?= BASE_URL ?>/categoria/<?= urlencode($catMenu['slug']) ?>"
                       data-cat="<?= $i ?>" class="cat-barra hover:opacity-70 whitespace-nowrap shrink-0">
                        <?= htmlspecialchars($catMenu['nombre']) ?>
                    </a>
                <?php endforeach; ?>

                <?php if (!empty($itemsMenu)): ?>
                    <div class="relative shrink-0 hidden" id="menuMas">
                        <button type="button" onclick="alternarMenuMas(event)" aria-haspopup="true" aria-expanded="false"
                                class="inline-flex items-center gap-1 hover:opacity-70 whitespace-nowrap">
                            <span id="textoMas">Más</span>
                            <svg class="w-3.5 h-3.5 transition-transform" id="flechaMas" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <div id="panelMas"
                             class="hidden absolute right-0 top-full mt-3 z-50 bg-white text-gray-900 border rounded-2xl shadow-xl p-2
                                    w-56 max-h-[70vh] overflow-y-auto">
                            <?php foreach ($itemsMenu as $i => $catMenu): ?>
                                <a href="<?= BASE_URL ?>/categoria/<?= urlencode($catMenu['slug']) ?>"
                                   data-cat="<?= $i ?>"
                                   class="cat-mas hidden px-3 py-2 rounded-lg text-sm font-normal hover:bg-gray-50 whitespace-nowrap overflow-hidden text-ellipsis">
                                    <?= htmlspecialchars($catMenu['nombre']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </nav>

            <!-- Acciones -->
            <div class="flex items-center gap-4 shrink-0 whitespace-nowrap">
                <div class="hidden md:flex items-center gap-4">
                    <?php if (isset($_SESSION['cliente'])): ?>
                        <a href="<?= BASE_URL ?>/mi-cuenta/pedidos" class="text-sm hover:opacity-70">Mis pedidos</a>
                        <a href="<?= BASE_URL ?>/salir" class="text-sm font-medium hover:opacity-70">Salir</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/ingresar" class="text-sm font-medium hover:opacity-70">Ingresar</a>
                    <?php endif; ?>
                </div>

                <!-- Carrito con contador (siempre visible) -->
                <a href="<?= BASE_URL ?>/carrito"
                   aria-label="Carrito"
                   class="relative inline-flex items-center gap-2 text-sm font-medium hover:opacity-70 p-2 -mr-2 md:p-0 md:mr-0">
                    <span class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 md:w-5 md:h-5" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.4 6M17 13l1.4 6M9 19a1 1 0 100 2 1 1 0 000-2zm8 0a1 1 0 100 2 1 1 0 000-2z"/>
                        </svg>
                        <?php if ($totalItemsCarrito > 0): ?>
                            <span class="absolute -top-2 -right-2 min-w-[18px] h-[18px] px-1
                                         rounded-full text-[10px] font-bold flex items-center justify-center"
                                  style="background-color: var(--color-primario); color: var(--color-boton-texto)">
                                <?= $totalItemsCarrito > 99 ? '99+' : $totalItemsCarrito ?>
                            </span>
                        <?php endif; ?>
                    </span>
                    <span class="hidden md:inline">Carrito</span>
                </a>
            </div>

        </div>
    </div>
</header>
</div>

<!-- ── Menú mobile (drawer) ───────────────────────────────────── -->
<div id="menuMobile" class="md:hidden fixed inset-0 z-50 invisible" aria-hidden="true">

    <!-- Fondo oscuro -->
    <div id="menuMobileOverlay"
         onclick="cerrarMenuMobile()"
         class="absolute inset-0 bg-black/40 opacity-0 transition-opacity duration-300"></div>

    <!-- Panel -->
    <aside id="menuMobilePanel"
           class="absolute top-0 left-0 h-full w-80 max-w-[85%] bg-white shadow-xl
                  -translate-x-full transition-transform duration-300 flex flex-col">

        <div class="flex items-center justify-between px-5 h-16 border-b">
            <span class="font-bold text-lg truncate"><?= htmlspecialchars($nombreTienda) ?></span>
            <button type="button" onclick="cerrarMenuMobile()" aria-label="Cerrar menú"
                    class="p-2 -mr-2 rounded-lg hover:bg-gray-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-5 py-5">

            <!-- Buscador -->
            <form method="GET" action="<?= BASE_URL ?>/tienda" class="mb-6">
                <div class="flex items-center border rounded-full px-4 py-2">
                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/>
                    </svg>
                    <input type="text" name="q" placeholder="Buscar productos…"
                           class="flex-1 ml-2 text-sm focus:outline-none bg-transparent">
                </div>
            </form>

            <!-- Navegación -->
            <nav class="flex flex-col">
                <a href="<?= BASE_URL ?>/tienda" class="py-3 font-medium border-b border-gray-100">Inicio</a>

                <?php if (!empty($itemsMenu)): ?>
                    <p class="mt-5 mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Categorías</p>
                    <?php foreach ($itemsMenu as $catMenu): ?>
                        <a href="<?= BASE_URL ?>/categoria/<?= urlencode($catMenu['slug']) ?>"
                           class="py-3 border-b border-gray-100">
                            <?= htmlspecialchars($catMenu['nombre']) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>

                <p class="mt-5 mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Mi cuenta</p>
                <?php if (isset($_SESSION['cliente'])): ?>
                    <a href="<?= BASE_URL ?>/mi-cuenta/pedidos" class="py-3 border-b border-gray-100">Mis pedidos</a>
                    <a href="<?= BASE_URL ?>/salir" class="py-3 border-b border-gray-100 text-gray-500">Salir</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/ingresar" class="py-3 border-b border-gray-100">Ingresar</a>
                    <a href="<?= BASE_URL ?>/registro" class="py-3 border-b border-gray-100">Crear cuenta</a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Carrito abajo -->
        <div class="p-5 border-t">
            <a href="<?= BASE_URL ?>/carrito"
               class="btn-primario w-full py-3 rounded-full font-semibold flex items-center justify-center gap-2">
                Ver carrito
                <?php if ($totalItemsCarrito > 0): ?>
                    <span class="bg-white/25 rounded-full px-2 text-sm">
                        <?= $totalItemsCarrito > 99 ? '99+' : $totalItemsCarrito ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>
    </aside>
</div>

<script>
(function () {
    const menu    = document.getElementById('menuMobile');
    const overlay = document.getElementById('menuMobileOverlay');
    const panel   = document.getElementById('menuMobilePanel');
    const boton   = document.getElementById('btnMenuMobile');

    window.abrirMenuMobile = function () {
        menu.classList.remove('invisible');
        menu.setAttribute('aria-hidden', 'false');
        boton.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';

        requestAnimationFrame(() => {
            overlay.classList.remove('opacity-0');
            panel.classList.remove('-translate-x-full');
        });
    };

    window.cerrarMenuMobile = function () {
        overlay.classList.add('opacity-0');
        panel.classList.add('-translate-x-full');
        boton.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';

        setTimeout(() => {
            menu.classList.add('invisible');
            menu.setAttribute('aria-hidden', 'true');
        }, 300);
    };

    // Compatibilidad con el nombre viejo
    window.toggleMenuMobile = function () {
        menu.classList.contains('invisible') ? abrirMenuMobile() : cerrarMenuMobile();
    };

    // Cerrar con Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !menu.classList.contains('invisible')) cerrarMenuMobile();
    });

    // Cerrar al tocar un link del menú
    panel.querySelectorAll('a').forEach(a => a.addEventListener('click', cerrarMenuMobile));

    // Si se agranda la pantalla con el menú abierto, cerrarlo
    window.matchMedia('(min-width: 768px)').addEventListener('change', e => {
        if (e.matches) cerrarMenuMobile();
    });
})();
</script>
<script>
(function () {
    const nav    = document.getElementById('navCategorias');
    const wrap   = document.getElementById('menuMas');
    const panel  = document.getElementById('panelMas');
    const flecha = document.getElementById('flechaMas');
    const texto  = document.getElementById('textoMas');
    if (!nav) return;

    const enBarra = [...nav.querySelectorAll('.cat-barra')];
    const enMas   = panel ? [...panel.querySelectorAll('.cat-mas')] : [];

    /** Ancho que ocupa todo lo visible del menú (elementos + separaciones) */
    function anchoUsado() {
        const visibles = [...nav.children].filter(el => !el.classList.contains('hidden'));
        const gap = parseFloat(getComputedStyle(nav).columnGap) || 0;
        return visibles.reduce((s, el) => s + el.getBoundingClientRect().width, 0) + gap * Math.max(0, visibles.length - 1);
    }

    /** Pone en la barra todas las que entran; las demás, al "Más" */
    function acomodar() {
        if (!wrap) { nav.style.visibility = 'visible'; return; }

        // Arranca con todo en la barra
        enBarra.forEach(a => a.classList.remove('hidden'));
        enMas.forEach(a => a.classList.add('hidden'));
        wrap.classList.add('hidden');

        const disponible = nav.clientWidth;

        if (anchoUsado() > disponible) {
            // Con el botón "Más" a la vista, saca categorías desde la última hasta que entre
            wrap.classList.remove('hidden');
            for (let i = enBarra.length - 1; i >= 0 && anchoUsado() > disponible; i--) {
                enBarra[i].classList.add('hidden');
                enMas[i].classList.remove('hidden');
                enMas[i].classList.add('block');
            }
        }

        // Si no quedó ninguna en la barra, el botón dice "Categorías"
        const quedanEnBarra = enBarra.some(a => !a.classList.contains('hidden'));
        if (texto) texto.textContent = quedanEnBarra ? 'Más' : 'Categorías';

        // Si son muchas en el desplegable, en dos columnas
        const cantMas = enMas.filter(a => !a.classList.contains('hidden')).length;
        if (panel) {
            panel.classList.toggle('w-56', cantMas <= 8);
            panel.classList.toggle('w-[26rem]', cantMas > 8);
            panel.classList.toggle('grid', cantMas > 8);
            panel.classList.toggle('grid-cols-2', cantMas > 8);
        }

        nav.style.visibility = 'visible';
    }

    // Al cargar, cuando terminan de cargar las fuentes (cambian el ancho del texto) y al cambiar el tamaño
    acomodar();
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(acomodar);
    let timer;
    window.addEventListener('resize', () => { clearTimeout(timer); timer = setTimeout(acomodar, 100); });

    // ── Abrir y cerrar el desplegable ────────────────────────────
    if (!panel) return;
    const boton = panel.previousElementSibling;

    function cerrar() {
        panel.classList.add('hidden');
        flecha.classList.remove('rotate-180');
        boton.setAttribute('aria-expanded', 'false');
    }

    window.alternarMenuMas = function (e) {
        e.stopPropagation();
        const abrir = panel.classList.contains('hidden');
        panel.classList.toggle('hidden', !abrir);
        flecha.classList.toggle('rotate-180', abrir);
        boton.setAttribute('aria-expanded', abrir ? 'true' : 'false');
    };

    document.addEventListener('click', e => { if (!panel.contains(e.target)) cerrar(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrar(); });
})();
</script>