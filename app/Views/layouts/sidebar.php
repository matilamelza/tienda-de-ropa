<?php
$rutaActual = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');

$claseLink = function (string $ruta, bool $exacta = false) use ($rutaActual): string {
    $ruta   = rtrim(BASE_URL . $ruta, '/');
    $activo = $exacta ? $rutaActual === $ruta : str_starts_with($rutaActual . '/', $ruta . '/');

    return 'flex items-center gap-3 px-3 py-1.5 rounded-md text-sm transition '
         . ($activo ? 'bg-white/10 text-white font-medium' : 'text-gray-400 hover:bg-white/5 hover:text-white');
};

$cantResets = (new UsuarioCliente())->contarResetsPendientes();

$menu = [
    'Tienda' => [
        ['/admin',              'Dashboard',    '📊', true],
        ['/admin/estadisticas', 'Estadísticas', '📈', false],
        ['/admin/pedidos',      'Pedidos',      '🧾', false],
        ['/admin/clientes',     'Clientes',     '👥', false],
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
        ['/admin/resets',        'Contraseñas',     '🔑', false],
        ['/admin/cuenta',        'Mi cuenta',       '👤', false],
    ],
];
?>

<style>
  /* Scroll del menú: fino y solo visible al pasar el mouse */
  #sidebarNav { scrollbar-width: thin; scrollbar-color: transparent transparent; }
  #sidebarNav:hover { scrollbar-color: rgba(255,255,255,.2) transparent; }
  #sidebarNav::-webkit-scrollbar { width: 6px; }
  #sidebarNav::-webkit-scrollbar-thumb { background: transparent; border-radius: 3px; }
  #sidebarNav:hover::-webkit-scrollbar-thumb { background: rgba(255,255,255,.2); }
</style>

<!-- Fondo oscuro del menú en mobile -->
<div id="sidebarOverlay" onclick="cerrarSidebar()"
     class="lg:hidden fixed inset-0 z-40 bg-black/50 hidden"></div>

<aside id="sidebar"
       class="fixed lg:sticky top-0 left-0 z-50 h-screen w-60 shrink-0 bg-gray-900 text-white flex flex-col
              -translate-x-full lg:translate-x-0 transition-transform duration-200">

    <!-- Encabezado -->
    <div class="px-4 h-14 flex items-center justify-between gap-2 border-b border-white/10 shrink-0">
        <p class="font-bold truncate"><?= htmlspecialchars($nombreTiendaAdmin) ?></p>

        <div class="flex items-center">
            <a href="<?= BASE_URL ?>/tienda" target="_blank" title="Ver tienda"
               class="p-2 rounded-md text-gray-400 hover:text-white hover:bg-white/10">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1h5"/>
                </svg>
            </a>
            <button type="button" onclick="cerrarSidebar()" aria-label="Cerrar menú"
                    class="lg:hidden p-2 rounded-md text-gray-400 hover:text-white hover:bg-white/10">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Menú -->
    <nav id="sidebarNav" class="flex-1 overflow-y-auto px-2 py-3 space-y-4">
        <?php foreach ($menu as $grupo => $links): ?>
            <div>
                <p class="px-3 mb-1 text-[10px] font-semibold uppercase tracking-wider text-gray-500"><?= $grupo ?></p>
                <div class="space-y-0.5">
                    <?php foreach ($links as [$ruta, $texto, $icono, $exacta]): ?>
                        <a href="<?= BASE_URL . $ruta ?>" class="<?= $claseLink($ruta, $exacta) ?>">
                            <span class="w-5 text-center text-base leading-none"><?= $icono ?></span>
                            <span class="flex-1 truncate"><?= $texto ?></span>
                            <?php if ($ruta === '/admin/resets' && $cantResets > 0): ?>
                                <span class="bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] px-1 flex items-center justify-center">
                                    <?= $cantResets ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </nav>

    <!-- Sesión (una línea) -->
    <div class="px-3 py-3 border-t border-white/10 shrink-0 flex items-center gap-2">
        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-sm font-semibold shrink-0">
            <?= htmlspecialchars(mb_strtoupper(mb_substr($_SESSION['admin']['nombre'] ?? 'A', 0, 1))) ?>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate"><?= htmlspecialchars($_SESSION['admin']['nombre'] ?? '') ?></p>
            <p class="text-[11px] text-gray-500 truncate"><?= htmlspecialchars($_SESSION['admin']['email'] ?? '') ?></p>
        </div>
        <a href="<?= BASE_URL ?>/admin/logout" title="Cerrar sesión"
           class="p-2 rounded-md text-gray-400 hover:text-white hover:bg-white/10 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17l5-5-5-5M20 12H9M12 20H5a1 1 0 01-1-1V5a1 1 0 011-1h7"/>
            </svg>
        </a>
    </div>
</aside>