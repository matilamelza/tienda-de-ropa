<section class="max-w-7xl mx-auto px-4 py-10">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Finalizar compra</h1>
        <p class="text-gray-500 mt-1">Completá tus datos para confirmar el pedido.</p>
    </div>

    <form action="/tienda_ropa/checkout/guardar" method="POST">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 bg-white border rounded-3xl p-6">

                <h2 class="text-xl font-bold text-gray-900 mb-6">Datos del cliente</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div>
                        <label class="block text-sm font-medium mb-1">Nombre *</label>
                        <input type="text" name="nombre" required class="w-full border rounded-xl px-4 py-3"value="<?php echo htmlspecialchars($clienteLogueado['nombre'] ?? ''); ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Apellido</label>
                        <input type="text" name="apellido" class="w-full border rounded-xl px-4 py-3"value="<?php echo htmlspecialchars($clienteLogueado['apellido'] ?? ''); ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Teléfono *</label>
                        <input type="text" name="telefono" required class="w-full border rounded-xl px-4 py-3"value="<?php echo htmlspecialchars($clienteLogueado['telefono'] ?? ''); ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" name="email" class="w-full border rounded-xl px-4 py-3"value="<?php echo htmlspecialchars($clienteLogueado['email'] ?? ''); ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Localidad</label>
                        <input type="text" name="localidad" class="w-full border rounded-xl px-4 py-3"value="<?php echo htmlspecialchars($clienteLogueado['localidad'] ?? ''); ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Dirección</label>
                        <input type="text" name="direccion" class="w-full border rounded-xl px-4 py-3"value="<?php echo htmlspecialchars($clienteLogueado['direccion'] ?? ''); ?>">
                    </div>

                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium mb-1">Observaciones</label>
                    <textarea name="observaciones" rows="4" class="w-full border rounded-xl px-4 py-3"></textarea>
                </div>

            </div>

            <div>
                <div class="bg-gray-50 rounded-3xl p-6 sticky top-28">

                    <h2 class="text-xl font-bold text-gray-900 mb-4">Resumen</h2>

                    <div class="space-y-3 mb-4">
                        <?php foreach ($items as $item): ?>
                            <?php $v = $item['variante']; ?>

                            <div class="flex justify-between gap-3 text-sm">
                                <div>
                                    <p class="font-medium">
                                        <?php echo htmlspecialchars($v['producto']); ?>
                                    </p>
                                    <p class="text-gray-500">
                                        <?php echo htmlspecialchars($v['talle']); ?> /
                                        <?php echo htmlspecialchars($v['color']); ?> x
                                        <?php echo $item['cantidad']; ?>
                                    </p>
                                </div>

                                <strong>
                                    $<?php echo number_format($item['subtotal'], 2, ',', '.'); ?>
                                </strong>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="flex justify-between py-4 border-t text-lg">
                        <span class="font-bold">Total</span>
                        <strong>$<?php echo number_format($total, 2, ',', '.'); ?></strong>
                    </div>

                    <button type="submit"
                            class="w-full bg-gray-900 text-white py-4 rounded-full font-semibold hover:bg-gray-800">
                        Enviar solicitud por whatsapp
                    </button>

                    <a href="/tienda_ropa/carrito"
                       class="block text-center mt-4 text-sm text-gray-500 hover:text-gray-900">
                        Volver al carrito
                    </a>

                </div>
            </div>

        </div>

    </form>

</section>