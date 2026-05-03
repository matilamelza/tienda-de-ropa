<aside class="w-64 bg-gray-900 text-white min-h-screen">

    <div class="p-5 border-b border-gray-700">
        <h1 class="text-xl font-bold">Tienda Admin</h1>
        <p class="text-sm text-gray-400">Panel de gestión</p>
    </div>

    <nav class="p-4 space-y-2">

        <a href="index.php?route=dashboard"
           class="block px-4 py-2 rounded hover:bg-gray-800">
            Dashboard
        </a>

        <a href="index.php?route=productos"
           class="block px-4 py-2 rounded hover:bg-gray-800">
            Productos
        </a>

        <a href="index.php?route=admin_pedidos"
   class="block px-4 py-2 rounded hover:bg-gray-800">
    Pedidos
</a>

<a href="<?= BASE_URL ?>/admin/clientes"
   class="block px-4 py-2 rounded hover:bg-gray-800">
    Clientes
</a>

        <a href="index.php?route=categorias"
           class="block px-4 py-2 rounded hover:bg-gray-800">
            Categorías
        </a>

        <a href="index.php?route=marcas"
           class="block px-4 py-2 rounded hover:bg-gray-800">
            Marcas
        </a>

        <a href="index.php?route=talles"
           class="block px-4 py-2 rounded hover:bg-gray-800">
            Talles
        </a>

        <a href="index.php?route=colores"
           class="block px-4 py-2 rounded hover:bg-gray-800">
            Colores
        </a>

        <a href="<?= BASE_URL ?>/tienda"
   class="block px-4 py-2 rounded hover:bg-gray-800">
   Ver tienda 🛒
</a>

    </nav>

</aside>