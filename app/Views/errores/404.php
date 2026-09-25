<section class="max-w-2xl mx-auto px-4 py-24 text-center">

    <p class="text-7xl font-bold text-gray-200 mb-2">404</p>

    <h1 class="text-3xl font-bold text-gray-900 mb-3">
        <?= htmlspecialchars($titulo ?? 'No encontramos esta página') ?>
    </h1>

    <p class="text-gray-500 mb-10">
        <?= htmlspecialchars($mensaje ?? 'Puede que el link esté mal escrito o que la página ya no exista.') ?>
    </p>

    <form method="GET" action="<?= BASE_URL ?>/tienda" class="flex gap-2 max-w-md mx-auto mb-6">
        <input type="text" name="q"
               placeholder="Buscá un producto…"
               class="flex-1 border rounded-full px-5 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-gray-200">
        <button type="submit" class="btn-primario px-5 py-3 rounded-full text-sm font-semibold">
            Buscar
        </button>
    </form>

    <a href="<?= BASE_URL ?>/tienda"
       class="inline-block text-sm text-gray-500 hover:text-gray-900 underline">
        Ver todos los productos
    </a>

</section>