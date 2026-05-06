<aside class="w-64 bg-gray-900 text-white min-h-screen flex flex-col">

    <div class="p-5 border-b border-gray-700">
        <h1 class="text-xl font-bold">Tienda Admin</h1>
        <p class="text-sm text-gray-400">Panel de gestión</p>
    </div>

    <nav class="p-4 space-y-2 flex-1">

        <a href="<?= BASE_URL ?>/admin"
           class="block px-4 py-2 rounded hover:bg-gray-800">
            Dashboard
        </a>

        <a href="<?= BASE_URL ?>/admin/productos"
           class="block px-4 py-2 rounded hover:bg-gray-800">
            Productos
        </a>

        <a href="<?= BASE_URL ?>/admin/pedidos"
           class="block px-4 py-2 rounded hover:bg-gray-800">
            Pedidos
        </a>

        <a href="<?= BASE_URL ?>/admin/clientes"
           class="block px-4 py-2 rounded hover:bg-gray-800">
            Clientes
        </a>

        <a href="<?= BASE_URL ?>/admin/categorias"
           class="block px-4 py-2 rounded hover:bg-gray-800">
            Categorías
        </a>

        <a href="<?= BASE_URL ?>/admin/marcas"
           class="block px-4 py-2 rounded hover:bg-gray-800">
            Marcas
        </a>

        <a href="<?= BASE_URL ?>/admin/talles"
           class="block px-4 py-2 rounded hover:bg-gray-800">
            Talles
        </a>

        <a href="<?= BASE_URL ?>/admin/colores"
           class="block px-4 py-2 rounded hover:bg-gray-800">
            Colores
        </a>

         <a href="<?= BASE_URL ?>/admin/configuracion"
           class="block px-4 py-2 rounded hover:bg-gray-800">
            Personalización ⚙️
        </a>

        <div class="border-t border-gray-700 my-2"></div>

        <a href="<?= BASE_URL ?>/tienda"
           class="block px-4 py-2 rounded hover:bg-gray-800">
            Ver tienda 🛒
        </a>

    </nav>

    <!-- Info y logout del admin logueado -->
    <div class="p-4 border-t border-gray-700">
        <?php if (isset($_SESSION['admin'])): ?>
            <p class="text-xs text-gray-400 mb-1">Sesión activa</p>
            <p class="text-sm font-medium truncate">
                <?php echo htmlspecialchars($_SESSION['admin']['nombre']); ?>
            </p>
            <p class="text-xs text-gray-500 truncate mb-3">
                <?php echo htmlspecialchars($_SESSION['admin']['email']); ?>
            </p>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/admin/logout"
           class="block text-center text-sm text-gray-400 hover:text-white border border-gray-700 rounded-lg px-3 py-2 hover:border-gray-500 transition">
            Cerrar sesión
        </a>
    </div>

</aside>