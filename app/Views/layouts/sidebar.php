<?php
$rutaActual = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');

/** Clases del link según si es la sección actual. */
$claseLink = function (string $ruta, bool $exacta = false) use ($rutaActual): string {
    $ruta   = rtrim(BASE_URL . $ruta, '/');
    $activo = $exacta ? $rutaActual === $ruta : str_starts_with($rutaActual . '/', $ruta . '/');

    return 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition '
         . ($activo ? 'bg-white/10 text-white font-semibold' : 'text-gray-300 hover:bg-white/5 hover:text-white');
};

$cantResets = count($_SESSION['admin_resets_pendientes'] ?? []);

$menu = [
    'Tienda' => [
        ['/admin',           'Dashboard', '📊', true],
        ['/admin/pedidos',   'Pedidos',   '🧾', false],
        ['/admin/clientes',  'Clientes',  '👥', false],
    ],
    'Catálogo' => [
        ['/admin/productos',  'Productos',  '👟', false],
        ['/admin/categorias', 'Categorías', '🗂️', false],
        ['/admin/marcas',     'Marcas',     '🏷️', false],
        ['/admin/talles',     'Talles',     '📏', false],
        ['/admin/colores',    'Colores',    '🎨', false],
    ],
    'Ajustes' => [
        ['/admin/configuracion', 'Personalización', '⚙️', false],
        ['/admin/resets',        'Recuperar contraseñas', '🔑', false],
    ],
];
?>

<!-- Fondo oscuro del menú en mobile -->
<div id="sidebarOverlay" onclick="cerrarSidebar()"
     class="lg:hidden fixed inset-0 z-40 bg-black/50 hidden"></div>

<aside id="sidebar"
       class="fixed lg:sticky top-0 left-0 z-50 h-screen w-64 shrink-0 bg-gray-900 text-white flex flex-col
              -translate-x-full lg:translate-x-0 transition-transform duration-200">

    <div class="px-5 h-16 flex items-center justify-between border-b border-white/10 shrink-0">
        <div class="min-w-0">
            <p class="font-bold truncate"><?= htmlspecialchars($nombreTiendaAdmin) ?></p>
            <p class="text-xs text-gray-400">Panel de gestión</p>
        </div>
        <button type="button" onclick="cerrarSidebar()" aria-label="Cerrar menú"
                class="lg:hidden p-2 -mr-2 rounded-lg hover:bg-white/10">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
        <?php foreach ($menu as $grupo => $links): ?>
            <div>
                <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500"><?= $grupo ?></p>
                <div class="space-y-1">
                    <?php foreach ($links as [$ruta, $texto, $icono, $exacta]): ?>
                        <a href="<?= BASE_URL . $ruta ?>" class="<?= $claseLink($ruta, $exacta) ?>">
                            <span class="w-5 text-center"><?= $icono ?></span>
                            <span class="flex-1"><?= $texto ?></span>
                            <?php if ($ruta === '/admin/resets' && $cantResets > 0): ?>
                                <span class="bg-red-500 text-white text-xs font-bold rounded-full min-w-[20px] h-5 px-1 flex items-center justify-center">
                                    <?= $cantResets ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div>
            <a href="<?= BASE_URL ?>/tienda" target="_blank"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-300 hover:bg-white/5 hover:text-white">
                <span class="w-5 text-center">🛒</span>
                <span>Ver tienda</span>
            </a>
        </div>
    </nav>

    <!-- Sesión -->
    <div class="p-4 border-t border-white/10 shrink-0">
        <?php if (isset($_SESSION['admin'])): ?>
            <p class="text-sm font-medium truncate"><?= htmlspecialchars($_SESSION['admin']['nombre']) ?></p>
            <p class="text-xs text-gray-500 truncate mb-3"><?= htmlspecialchars($_SESSION['admin']['email']) ?></p>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/admin/logout"
           class="block text-center text-sm text-gray-400 hover:text-white border border-white/10 rounded-lg px-3 py-2 hover:border-white/30 transition">
            Cerrar sesión
        </a>
    </div>
</aside>