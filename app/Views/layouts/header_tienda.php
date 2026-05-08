<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
 
    <?php
    // ── Cargar configuración desde la BD ──────────────────────
    require_once dirname(__DIR__, 2) . '/Models/ConfiguracionTienda.php';
    $cfg = new ConfiguracionTienda();
    $conf = $cfg->todas();
 
    $tituloPag     = htmlspecialchars($conf['seo_titulo']      ?? ($conf['tienda_nombre'] ?? 'Tienda'));
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
 
<header class="border-b sticky top-0 z-50"
        style="background-color: var(--color-header-bg)">
    <div class="max-w-7xl mx-auto px-4">
        <div class="h-20 flex items-center justify-between">
 
            <!-- Logo + Nombre -->
            <a href="<?= BASE_URL ?>/tienda" class="flex items-center gap-3">
                <?php if (!empty($conf['tienda_logo'])): ?>
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($conf['tienda_logo']) ?>"
                         alt="<?= htmlspecialchars($conf['tienda_nombre'] ?? 'Logo') ?>"
                         class="h-12 w-auto object-contain">
                <?php else: ?>
                    <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-sm"
                         style="background-color: var(--color-primario); color: var(--color-boton-texto)">
                        LOGO
                    </div>
                <?php endif; ?>
                <div>
                    <h1 class="text-xl font-bold leading-none">
                        <?= htmlspecialchars($conf['tienda_nombre'] ?? 'Mi Tienda') ?>
                    </h1>
                    <?php if (!empty($conf['tienda_slogan'])): ?>
                        <p class="text-xs text-gray-500">
                            <?= htmlspecialchars($conf['tienda_slogan']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </a>
 
            <!-- Nav categorías -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="<?= BASE_URL ?>/tienda" class="hover:opacity-70">Inicio</a>
                <?php if (isset($categoriasMenu) && $categoriasMenu && $categoriasMenu->num_rows > 0): ?>
                    <?php while ($catMenu = $categoriasMenu->fetch_assoc()): ?>
                        <a href="<?= BASE_URL ?>/categoria/<?= urlencode($catMenu['slug']) ?>"
                           class="hover:opacity-70">
                            <?= htmlspecialchars($catMenu['nombre']) ?>
                        </a>
                    <?php endwhile; ?>
                <?php endif; ?>
            </nav>
 
            <!-- Acciones -->
            <div class="hidden md:flex items-center gap-4">
                <?php if (isset($_SESSION['cliente'])): ?>
                    <a href="<?= BASE_URL ?>/mi-cuenta/pedidos" class="text-sm hover:opacity-70">Mis pedidos</a>
                    <a href="<?= BASE_URL ?>/salir" class="text-sm font-medium hover:opacity-70">Salir</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/ingresar" class="text-sm font-medium hover:opacity-70">Ingresar</a>
                <?php endif; ?>

                <!-- Carrito con contador -->
                <a href="<?= BASE_URL ?>/carrito"
                   class="relative inline-flex items-center gap-2 text-sm font-medium hover:opacity-70">
                    <span class="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
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
                    Carrito
                </a>
            </div>
 
            <button type="button" onclick="toggleMenuMobile()"
                    class="md:hidden border rounded-lg px-3 py-2 text-sm">Menú</button>
        </div>
 
        <!-- Menú mobile -->
        <div id="menuMobile" class="hidden pb-4 md:hidden border-t pt-4">
            <nav class="flex flex-col gap-2 text-sm">
                <a href="<?= BASE_URL ?>/tienda" class="py-2 hover:opacity-70">Inicio</a>
                <?php if (isset($_SESSION['cliente'])): ?>
                    <a href="<?= BASE_URL ?>/mi-cuenta/pedidos" class="py-2">Mis pedidos</a>
                    <a href="<?= BASE_URL ?>/salir" class="py-2">Salir</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/ingresar" class="py-2">Ingresar</a>
                <?php endif; ?>

                <!-- Carrito mobile con contador -->
                <a href="<?= BASE_URL ?>/carrito" class="py-2 flex items-center gap-2">
                    Carrito
                    <?php if ($totalItemsCarrito > 0): ?>
                        <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1
                                     rounded-full text-[11px] font-bold"
                              style="background-color: var(--color-primario); color: var(--color-boton-texto)">
                            <?= $totalItemsCarrito > 99 ? '99+' : $totalItemsCarrito ?>
                        </span>
                    <?php endif; ?>
                </a>
            </nav>
        </div>
    </div>
</header>
<?php