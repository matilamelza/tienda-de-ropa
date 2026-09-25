<?php require __DIR__ . '/header_admin.php'; ?>

<!-- Barra superior (solo mobile) -->
<header class="lg:hidden sticky top-0 z-30 bg-gray-900 text-white flex items-center justify-between px-4 h-14">
    <button type="button" onclick="abrirSidebar()" aria-label="Abrir menú"
            class="-ml-2 p-2 rounded-lg hover:bg-gray-800">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
        </svg>
    </button>
    <span class="font-semibold truncate"><?= htmlspecialchars($nombreTiendaAdmin) ?></span>
    <a href="<?= BASE_URL ?>/tienda" target="_blank" class="p-2 -mr-2 text-sm text-gray-300">Ver tienda</a>
</header>

<div class="lg:flex min-h-screen">
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- min-w-0: permite que las tablas anchas scrolleen adentro en vez de ensanchar la página -->
    <main class="flex-1 min-w-0 p-4 md:p-6">
        <?php require $viewPath; ?>
    </main>
</div>

<?php require __DIR__ . '/footer_admin.php'; ?>