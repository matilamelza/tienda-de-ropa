<?php
$estados = [
    'pendiente_contacto' => 'Pendiente contacto',
    'contactado' => 'Contactado',
    'pendiente_pago' => 'Pendiente pago',
    'pagado' => 'Pagado',
    'cancelado' => 'Cancelado',
    'entregado' => 'Entregado'
];
?>

<section class="max-w-5xl mx-auto px-4 py-10">

    <h1 class="text-3xl font-bold mb-6">Mis pedidos</h1>

    <?php if ($pedidos && $pedidos->num_rows > 0): ?>

        <div class="space-y-4">

            <?php while ($p = $pedidos->fetch_assoc()): ?>

                <div class="bg-white border rounded-2xl p-5 flex justify-between items-center">

                    <div>
                        <p class="font-bold text-lg">
                            Pedido #<?php echo $p['id_pedido']; ?>
                        </p>

                        <p class="text-sm text-gray-500">
                            <?php echo date('d/m/Y H:i', strtotime($p['fecha'])); ?>
                        </p>

                        <p class="text-sm mt-1">
                            Estado:
                            <strong>
                                <?php echo $estados[$p['estado']] ?? $p['estado']; ?>
                            </strong>
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="font-bold text-lg">
                            $<?php echo number_format($p['total'], 2, ',', '.'); ?>
                        </p>

                                <a href="<?= BASE_URL ?>/mi-cuenta/pedido/<?php echo $p['id_pedido']; ?>"
                                   class="text-sm text-gray-600 hover:underline">
                                    Ver detalle
                                </a>
                    </div>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="bg-gray-50 rounded-3xl p-10 text-center">
            <h2 class="text-xl font-bold">Todavía no hiciste pedidos</h2>

            <a href="<?= BASE_URL ?>/tienda"
               class="inline-block mt-4 bg-gray-900 text-white px-5 py-3 rounded-full">
                Ir a la tienda
            </a>
        </div>

    <?php endif; ?>

</section>