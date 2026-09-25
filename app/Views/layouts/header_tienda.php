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

    $nombreTienda  = $conf['tienda_nombre'] ?? 'Mi Tienda';
    $tituloPag     = htmlspecialchars($conf['seo_titulo']      ?? $nombreTienda);
    $metaDesc      = htmlspecialchars($conf['seo_descripcion'] ?? '');
    $metaKeys      = htmlspecialchars($conf['seo_keywords']    ?? '');
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

    <title><?= $tituloPag ?></title>

    <?php if ($metaDesc): ?>
        <meta name="description" content="<?= $metaDesc ?>">
    <?php endif; ?>
    <?php if ($metaKeys): ?>
        <meta name="keywords" content="<?= $metaKeys ?>">
    <?php endif; ?>

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

<!-- ── Barra de anuncio ──────────────────────────────────────── -->
<?php if (!empty($conf['anuncio_activo']) && $conf['anuncio_activo'] === '1' && !empty($conf['anuncio_texto'])): ?>
    <div class="text-center text-sm py-2 px-4 font-medium"
         style="background-color: <?= htmlspecialchars($conf['anuncio_color_bg'] ?? '#111827') ?>;
                color: <?= htmlspecialchars($conf['anuncio_color_texto'] ?? '#fff') ?>">
        <?= htmlspecialchars($conf['anuncio_texto']) ?>
    </div>
<?php endif; ?>

<header class="border-b sticky top-0 z-40"
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

            <!-- Logo + Nombre -->
            <a href="<?= BASE_URL ?>/tienda" class="flex items-center gap-3 min-w-0">
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
                <div class="min-w-0">
                    <span class="block text-lg md:text-xl font-bold leading-none truncate">
                        <?= htmlspecialchars($nombreTienda) ?>
                    </span>
                    <?php if (!empty($conf['tienda_slogan'])): ?>
                        <span class="hidden sm:block text-xs text-gray-500 mt-1 truncate">
                            <?= htmlspecialchars($conf['tienda_slogan']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </a>

            <!-- Nav categorías (desktop) -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="<?= BASE_URL ?>/tienda" class="hover:opacity-70">Inicio</a>
                <?php foreach ($itemsMenu as $catMenu): ?>
                    <a href="<?= BASE_URL ?>/categoria/<?= urlencode($catMenu['slug']) ?>"
                       class="hover:opacity-70">
                        <?= htmlspecialchars($catMenu['nombre']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Acciones -->
            <div class="flex items-center gap-4">
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