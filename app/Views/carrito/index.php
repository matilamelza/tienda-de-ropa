<section class="max-w-7xl mx-auto px-4 py-10">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Carrito</h1>
        <p class="text-gray-500 mt-1">Revisá los productos antes de finalizar la compra.</p>
    </div>

    <?php if (empty($items)): ?>

        <div class="bg-gray-50 rounded-3xl p-12 text-center">
            <h2 class="text-2xl font-bold text-gray-900">Tu carrito está vacío</h2>
            <p class="text-gray-500 mt-2">Agregá productos desde la tienda.</p>

            <a href="/tienda_ropa/tienda"
               class="inline-block mt-6 bg-gray-900 text-white px-6 py-3 rounded-full">
                Ver productos
            </a>
        </div>

    <?php else: ?>

        <form action="/tienda_ropa/carrito/actualizar" method="POST">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <div class="lg:col-span-2 space-y-4">

                    <?php foreach ($items as $item): ?>
                        <?php $v = $item['variante']; ?>

                        <div class="bg-white border rounded-3xl p-4 flex gap-4">

                            <div class="w-28 h-32 bg-gray-100 rounded-2xl overflow-hidden flex-shrink-0">
                                <?php if (!empty($v['foto'])): ?>
                                    <img src="/tienda_ropa/public/uploads/productos/<?php echo htmlspecialchars($v['foto']); ?>"
                                         class="w-full h-full object-cover">
                                <?php endif; ?>
                            </div>

                            <div class="flex-1">
                                <div class="flex justify-between gap-4">
                                    <div>
                                        <h3 class="font-bold text-gray-900">
                                            <?php echo htmlspecialchars($v['producto']); ?>
                                        </h3>

                                        <p class="text-sm text-gray-500 mt-1">
                                            Talle: <?php echo htmlspecialchars($v['talle']); ?> ·
                                            Color: <?php echo htmlspecialchars($v['color']); ?>
                                        </p>

                                        <p class="text-sm text-gray-400 mt-1">
                                            SKU: <?php echo htmlspecialchars($v['sku'] ?: '-'); ?>
                                        </p>
                                    </div>

                                    <a href="/tienda_ropa/carrito/eliminar/<?php echo $v['id_variante']; ?>">
                                       class="text-sm text-red-500 hover:text-red-700">
                                        Eliminar
                                    </a>
                                </div>

                                <div class="flex items-center justify-between mt-6">
                                    <div>
                                        <label class="text-sm text-gray-500">Cantidad</label>
                                        <input type="number"
                                               name="cantidades[<?php echo $v['id_variante']; ?>]"
                                               value="<?php echo $item['cantidad']; ?>"
                                               min="1"
                                               max="<?php echo $v['stock']; ?>"
                                               class="w-20 border rounded-lg px-3 py-2 ml-2">
                                    </div>

                                    <div class="text-right">
                                        <p class="font-bold text-gray-900">
                                            $<?php echo number_format($item['subtotal'], 2, ',', '.'); ?>
                                        </p>

                                        <p class="text-sm text-gray-500">
                                            $<?php echo number_format($item['precio'], 2, ',', '.'); ?> c/u
                                        </p>
                                    </div>
                                </div>
                            </div>

                        </div>

                    <?php endforeach; ?>

                    <button type="submit"
                            class="border px-5 py-3 rounded-full text-sm hover:bg-gray-50">
                        Actualizar carrito
                    </button>

                </div>

                <div>
                    <div class="bg-gray-50 rounded-3xl p-6 sticky top-28">

                        <h2 class="text-xl font-bold text-gray-900 mb-4">Resumen</h2>

                        <div class="flex justify-between py-3 border-b">
                            <span class="text-gray-600">Subtotal</span>
                            <strong>$<?php echo number_format($total, 2, ',', '.'); ?></strong>
                        </div>

                        <div class="flex justify-between py-3 border-b">
                            <span class="text-gray-600">Envío</span>
                            <span class="text-gray-500">A coordinar</span>
                        </div>

                        <div class="flex justify-between py-4 text-lg">
                            <span class="font-bold">Total</span>
                            <strong>$<?php echo number_format($total, 2, ',', '.'); ?></strong>
                        </div>

                       <a href="/tienda_ropa/checkout"
   class="block text-center w-full bg-gray-900 text-white py-4 rounded-full font-semibold hover:bg-gray-800">
    Finalizar compra
</a>

                        <a href="/tienda_ropa/tienda"
                           class="block text-center mt-4 text-sm text-gray-500 hover:text-gray-900">
                            Seguir comprando
                        </a>

                    </div>
                </div>

            </div>

        </form>

    <?php endif; ?>

</section>