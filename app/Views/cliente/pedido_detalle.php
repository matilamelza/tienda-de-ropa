<section class="max-w-5xl mx-auto px-4 py-10">

    <div class="mb-6">
        <a href="<?= BASE_URL ?>/mi-cuenta/pedidos" class="text-sm text-gray-500 hover:text-gray-900">
            ← Volver
        </a>
    </div>

    <h1 class="text-3xl font-bold mb-6">
        Pedido #<?php echo $pedido['id_pedido']; ?>
    </h1>

    <div class="bg-white border rounded-2xl p-6 mb-6">

        <p><strong>Estado:</strong> <?php echo $pedido['estado']; ?></p>
        <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></p>
        <p><strong>Total:</strong> $<?php echo number_format($pedido['total'], 2, ',', '.'); ?></p>

    </div>

    <div class="bg-white border rounded-2xl p-6">

        <h2 class="text-xl font-bold mb-4">Productos</h2>

        <table class="w-full text-sm">

            <thead>
                <tr>
                    <th class="text-left">Producto</th>
                    <th>Talle</th>
                    <th>Color</th>
                    <th>Cantidad</th>
                    <th>Subtotal</th>
                </tr>
            </thead>

            <tbody>

                <?php while ($i = $items->fetch_assoc()): ?>

                    <tr class="border-t">
                        <td><?php echo $i['producto']; ?></td>
                        <td><?php echo $i['talle']; ?></td>
                        <td><?php echo $i['color']; ?></td>
                        <td><?php echo $i['cantidad']; ?></td>
                        <td>$<?php echo number_format($i['subtotal'], 2, ',', '.'); ?></td>
                    </tr>

                <?php endwhile; ?>

            </tbody>

        </table>

    </div>

</section>