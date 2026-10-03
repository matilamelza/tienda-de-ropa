<?php
$rutaActual = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');

$esActivo = function (string $ruta, bool $exacta = false) use ($rutaActual): bool {
    $ruta = rtrim(BASE_URL . $ruta, '/');
    return $exacta ? $rutaActual === $ruta : str_starts_with($rutaActual . '/', $ruta . '/');
};

// Números rojos al lado de cada sección
$badges = [
    '/admin/pedidos' => (new Pedido())->contarSinAtender(),
    '/admin/resets'  => (new UsuarioCliente())->contarResetsPendientes(),
];

/** Íconos de línea (contenido del <svg>) */
$iconos = [
    'dashboard'    => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
    'estadisticas' => '<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
    'pedidos'      => '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/>',
    'caja'         => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>',
    'clientes'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'productos'    => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
    'promociones'  => '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>',
    'catalogo'     => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/>',
    'categorias'   => '<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>',
    'marcas'       => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r="1"/>',
    'talles'       => '<path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/>',
    'colores'      => '<circle cx="13.5" cy="6.5" r="1"/><circle cx="17.5" cy="10.5" r="1"/><circle cx="8.5" cy="7.5" r="1"/><circle cx="6.5" cy="12.5" r="1"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>',
    'personalizar' => '<path d="M4 21v-7"/><path d="M4 10V3"/><path d="M12 21v-9"/><path d="M12 8V3"/><path d="M20 21v-5"/><path d="M20 12V3"/><path d="M2 14h4"/><path d="M10 8h4"/><path d="M18 16h4"/>',
    'claves'       => '<path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/><circle cx="16.5" cy="7.5" r="1"/>',
    'cuenta'       => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'mas'          => '<path d="M12 5v14"/><path d="M5 12h14"/>',
    'tienda'       => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
    'salir'        => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
    'panel'        => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/>',
];
$icono = fn(string $n, string $cls = 'w-[18px] h-[18px]') =>
    '<svg class="' . $cls . ' shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">' . ($iconos[$n] ?? '') . '</svg>';

$menu = [
    'Tienda' => [
        ['/admin',              'Dashboard',    'dashboard',    true],
        ['/admin/pedidos',      'Pedidos',      'pedidos',      false],
        ['/admin/caja',         'Caja',         'caja',         false],
        ['/admin/clientes',     'Clientes',     'clientes',     false],
        ['/admin/estadisticas', 'Estadísticas', 'estadisticas', false],
    ],
    'Catálogo' => [
        ['/admin/productos',   'Productos',    'productos',   false],
        ['/admin/promociones', 'Promociones',  'promociones', false],
        ['/admin/catalogo',    'Catálogo PDF', 'catalogo',    false],
        ['/admin/categorias',  'Categorías',   'categorias',  false],
        ['/admin/marcas',      'Marcas',       'marcas',      false],
        ['/admin/talles',      'Talles',       'talles',      false],
        ['/admin/colores',     'Colores',      'colores',     false],
    ],
    'Ajustes' => [
        ['/admin/configuracion', 'Personalización', 'personalizar', false],
        ['/admin/resets',        'Contraseñas',     'claves',       false],
        ['/admin/cuenta',        'Mi cuenta',       'cuenta',       false],
    ],
];
?>

<!-- Modo compacto: se aplica antes de dibujar el menú, para que no "salte" al cargar -->
<script>
try { if (localStorage.getItem('sbMini') === '1') document.documentElement.classList.add('sb-mini'); } catch (e) {}
</script>

<style>
  #sidebar { transition: width .2s ease, transform .2s ease; }
  #sidebarNav { scrollbar-width: thin; scrollbar-color: transparent transparent; }
  #sidebarNav:hover { scrollbar-color: rgba(255,255,255,.2) transparent; }
  #sidebarNav::-webkit-scrollbar { width: 6px; }
  #sidebarNav::-webkit-scrollbar-thumb { background: transparent; border-radius: 3px; }
  #sidebarNav:hover::-webkit-scrollbar-thumb { background: rgba(255,255,255,.2); }
  .sb-dot { display: none; }

  /* ── Modo compacto (solo escritorio) ───────────────────────── */
  @media (min-width: 1024px) {
    html.sb-mini #sidebar { width: 4.25rem; }
    html.sb-mini #sidebar .sb-texto,
    html.sb-mini #sidebar .sb-num,
    html.sb-mini #sidebar .sb-grupo-txt { display: none; }
    html.sb-mini #sidebar .sb-grupo-linea { display: block; }
    html.sb-mini #sidebar .sb-dot { display: block; }
    html.sb-mini #sidebar .sb-link,
    html.sb-mini #sidebar .sb-encabezado { justify-content: center; padding-left: 0; padding-right: 0; }

    /* Nombre flotante al pasar el mouse */
    html.sb-mini #sidebar .sb-link { position: relative; }
    html.sb-mini #sidebar .sb-link:hover::after {
      content: attr(data-nombre);
      position: absolute; left: calc(100% + 10px); top: 50%; transform: translateY(-50%);
      background: #111827; color: #fff; font-size: 12px; font-weight: 500;
      padding: 4px 8px; border-radius: 6px; white-space: nowrap; z-index: 60;
      box-shadow: 0 4px 12px rgba(0,0,0,.25);
    }
  }
</style>

<!-- Fondo oscuro del menú en mobile -->
<div id="sidebarOverlay" onclick="cerrarSidebar()" class="lg:hidden fixed inset-0 z-40 bg-black/50 hidden"></div>

<aside id="sidebar"
       class="fixed lg:sticky top-0 left-0 z-50 h-screen w-60 shrink-0 bg-gray-900 text-white flex flex-col
              -translate-x-full lg:translate-x-0">

    <!-- Encabezado: nombre de la tienda + contraer -->
    <div class="sb-encabezado px-4 h-14 flex items-center justify-between gap-2 border-b border-white/10 shrink-0">
        <p class="sb-texto font-bold truncate"><?= htmlspecialchars($nombreTiendaAdmin) ?></p>

        <button type="button" onclick="alternarMenuCompacto()" title="Contraer / expandir menú"
                class="hidden lg:flex p-2 -mr-2 rounded-md text-gray-400 hover:text-white hover:bg-white/10">
            <?= $icono('panel') ?>
        </button>
        <button type="button" onclick="cerrarSidebar()" aria-label="Cerrar menú"
                class="lg:hidden p-2 -mr-2 rounded-md text-gray-400 hover:text-white hover:bg-white/10">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>

    <!-- Acción rápida -->
    <div class="px-2 pt-3 shrink-0">
        <a href="<?= BASE_URL ?>/admin/venta/nueva" data-nombre="Nueva venta"
           class="sb-link flex items-center gap-3 px-3 py-2 rounded-md border border-white/15 text-sm text-white hover:bg-white/10 transition">
            <?= $icono('mas') ?>
            <span class="sb-texto">Nueva venta</span>
        </a>
    </div>

    <!-- Menú -->
    <nav id="sidebarNav" class="flex-1 overflow-y-auto overflow-x-hidden px-2 py-3 space-y-4">
        <?php foreach ($menu as $grupo => $links): ?>
            <div>
                <p class="sb-grupo-txt px-3 mb-1 text-[10px] font-semibold uppercase tracking-wider text-gray-500"><?= $grupo ?></p>
                <div class="sb-grupo-linea hidden mx-auto mb-2 w-6 border-t border-white/10"></div>

                <div class="space-y-0.5">
                    <?php foreach ($links as [$ruta, $texto, $ico, $exacta]): ?>
                        <?php
                        $activo = $esActivo($ruta, $exacta);
                        $cant   = array_key_exists($ruta, $badges) ? (int) $badges[$ruta] : null;
                        ?>
                        <a href="<?= BASE_URL . $ruta ?>" data-nombre="<?= htmlspecialchars($texto) ?>"
                           class="sb-link flex items-center gap-3 px-3 py-1.5 rounded-md text-sm transition
                                  <?= $activo ? 'bg-white/10 text-white font-medium' : 'text-gray-400 hover:bg-white/5 hover:text-white' ?>">
                            <span class="relative">
                                <?= $icono($ico) ?>
                                <?php if ($cant !== null): ?>
                                    <span class="sb-dot absolute -top-1 -right-1 w-2 h-2 rounded-full bg-red-500 ring-2 ring-gray-900 <?= $cant > 0 ? '' : '!hidden' ?>"></span>
                                <?php endif; ?>
                            </span>
                            <span class="sb-texto flex-1 truncate"><?= $texto ?></span>
                            <?php if ($cant !== null): ?>
                                <span data-badge="<?= htmlspecialchars($ruta) ?>"
                                      class="sb-num <?= $cant > 0 ? 'flex' : 'hidden' ?> bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] px-1 items-center justify-center">
                                    <?= $cant > 99 ? '99+' : $cant ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </nav>

    <!-- Pie: ver tienda y sesión -->
    <div class="border-t border-white/10 p-2 space-y-0.5 shrink-0">
        <a href="<?= BASE_URL ?>/tienda" target="_blank" data-nombre="Ver tienda"
           class="sb-link flex items-center gap-3 px-3 py-1.5 rounded-md text-sm text-gray-400 hover:bg-white/5 hover:text-white transition">
            <?= $icono('tienda') ?>
            <span class="sb-texto">Ver tienda</span>
        </a>
        <a href="<?= BASE_URL ?>/admin/logout" data-nombre="Cerrar sesión"
           class="sb-link flex items-center gap-3 px-3 py-1.5 rounded-md text-sm text-gray-400 hover:bg-white/5 hover:text-white transition">
            <?= $icono('salir') ?>
            <span class="sb-texto flex-1 min-w-0 truncate">
                Salir <span class="text-gray-500">· <?= htmlspecialchars($_SESSION['admin']['nombre'] ?? '') ?></span>
            </span>
        </a>
    </div>
</aside>

<script>
function alternarMenuCompacto() {
    const mini = document.documentElement.classList.toggle('sb-mini');
    try { localStorage.setItem('sbMini', mini ? '1' : '0'); } catch (e) {}
}
</script>