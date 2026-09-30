<?php
/** Estados con nombres pensados para el cliente */
$estadosCliente = [
    'pendiente_contacto' => ['Recibido',              'bg-gray-100 text-gray-700'],
    'contactado'         => ['En contacto',           'bg-blue-100 text-blue-800'],
    'confirmado'         => ['Confirmado',            'bg-indigo-100 text-indigo-800'],
    'listo'              => ['Listo para entregar',   'bg-amber-100 text-amber-800'],
    'entregado'          => ['Entregado',             'bg-green-100 text-green-800'],
    'cancelado'          => ['Cancelado',             'bg-red-100 text-red-700'],
];
?>

<section class="max-w-5xl mx-auto px-4 py-10">

    <h1 class="text-3xl font-bold mb-6">Mis pedidos</h1>

    <?php if ($pedidos && $pedidos->num_rows > 0): ?>

        <div class="space-y-4">
            <?php while ($p = $pedidos->fetch_assoc()): ?>
                <?php
                [$txt, $cls] = $estadosCliente[$p['estado']] ?? [$p['estado'], 'bg-gray-100 text-gray-700'];
                $falta = (float) $p['total'] - (float) ($p['cobrado'] ?? 0);
                ?>
                <a href="<?= BASE_URL ?>/mi-cuenta/pedido/<?= (int) $p['id_pedido'] ?>"
                   class="bg-white border rounded-2xl p-5 flex justify-between items-center gap-4 hover:shadow-sm transition">
                    <div>
                        <p class="font-bold text-lg">Pedido #<?= (int) $p['id_pedido'] ?></p>
                        <p class="text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></p>
                        <span class="inline-block mt-2 px-2.5 py-1 rounded-full text-xs font-semibold <?= $cls ?>"><?= $txt ?></span>
                    </div>

                    <div class="text-right">
                        <p class="font-bold text-lg">$<?= number_format($p['total'], 2, ',', '.') ?></p>
                        <?php if ($p['estado'] !== 'cancelado' && $falta > 0.009 && (float) ($p['cobrado'] ?? 0) > 0): ?>
                            <p class="text-xs text-orange-600">Resta $<?= number_format($falta, 0, ',', '.') ?></p>
                        <?php endif; ?>
                        <span class="text-sm text-gray-500">Ver detalle →</span>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>

    <?php else: ?>

        <div class="bg-gray-50 rounded-3xl p-10 text-center">
            <h2 class="text-xl font-bold">Todavía no hiciste pedidos</h2>
            <a href="<?= BASE_URL ?>/tienda" class="inline-block mt-4 bg-gray-900 text-white px-5 py-3 rounded-full">Ir a la tienda</a>
        </div>

    <?php endif; ?>

</section>