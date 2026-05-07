<?php if (empty($categoriaActual)): ?>

<section class="bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 py-16 grid grid-cols-1 md:grid-cols-2 gap-10 items-center">

        <div>
            <p class="text-sm uppercase tracking-widest text-gray-500 mb-3">Nueva temporada</p>

            <h2 class="text-4xl md:text-6xl font-bold text-gray-900 leading-tight">
                <?= htmlspecialchars($config['hero_titulo'] ?? 'Ropa con estilo para todos los días') ?>
            </h2>

            <p class="mt-4 text-gray-600 max-w-lg">
                <?= htmlspecialchars($config['hero_subtitulo'] ?? 'Descubrí prendas seleccionadas, talles disponibles y stock actualizado.') ?>
            </p>

            <a href="#productos"
               class="inline-block mt-6 bg-gray-900 text-white px-6 py-3 rounded-full hover:bg-gray-800">
                <?= htmlspecialchars($config['hero_boton_texto'] ?? 'Ver productos') ?>
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow p-6">
            <?php if (!empty($config['hero_imagen'])): ?>
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($config['hero_imagen']) ?>"
                     alt="Banner principal"
                     class="w-full h-80 rounded-2xl object-cover">
            <?php else: ?>
                <div class="h-80 rounded-2xl bg-gradient-to-br from-gray-200 to-gray-400 flex items-center justify-center">
                    <span class="text-gray-600 font-medium">Banner / imagen principal</span>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<?php else: ?>

<section class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 py-10">

        <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="<?= BASE_URL ?>/tienda" class="hover:text-gray-900">Inicio</a>
            <span>/</span>
            <span class="text-gray-900"><?= htmlspecialchars($categoriaActual['nombre']) ?></span>
        </div>

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-widest text-gray-400 mb-2">Categoría</p>
                <h1 class="text-4xl font-bold text-gray-900"><?= htmlspecialchars($categoriaActual['nombre']) ?></h1>
            </div>
            <a href="<?= BASE_URL ?>/tienda" class="text-sm text-gray-500 hover:text-gray-900">
                Ver todos los productos
            </a>
        </div>

    </div>
</section>

<?php endif; ?>

<section id="productos" class="max-w-7xl mx-auto px-4 py-14">

    <div class="flex justify-between items-end mb-8">
        <div>
            <p class="text-sm uppercase tracking-widest text-gray-400">
                <?= empty($categoriaActual) ? 'Catálogo' : 'Listado' ?>
            </p>
            <h2 class="text-3xl font-bold text-gray-900">
                <?= empty($categoriaActual) ? 'Indumentaria destacada' : 'Indumentaria disponibles' ?>
            </h2>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">

        <?php if ($productos && $productos->num_rows > 0): ?>
            <?php while ($p = $productos->fetch_assoc()): ?>

                <a href="<?= BASE_URL ?>/producto/<?= htmlspecialchars($p['slug']) ?>" class="group block">

                    <div class="aspect-[3/4] bg-gray-100 rounded-2xl overflow-hidden shadow-sm group-hover:shadow-lg transition">
                        <?php if (!empty($p['foto_principal'])): ?>
                            <img src="<?= BASE_URL ?>/public/uploads/productos/<?= htmlspecialchars($p['foto_principal']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400">Sin imagen</div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-4">
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($p['categoria']) ?></p>
                        <h3 class="font-semibold text-gray-900 mt-1"><?= htmlspecialchars($p['nombre']) ?></h3>
                        <p class="font-bold mt-2">$<?= number_format($p['precio_base'], 2, ',', '.') ?></p>
                    </div>

                </a>

            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-span-full bg-gray-50 rounded-2xl p-10 text-center">
                <h3 class="text-xl font-bold text-gray-800">No hay productos</h3>
                <p class="text-gray-500 mt-2">Todavía no cargaste productos en esta categoría.</p>
            </div>
        <?php endif; ?>

    </div>

</section>