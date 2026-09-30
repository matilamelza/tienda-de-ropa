<?php
$estadosCliente = [
    'pendiente_contacto' => ['Recibido',            'Recibimos tu pedido. Te vamos a escribir por WhatsApp para coordinar.'],
    'contactado'         => ['En contacto',         'Ya te escribimos para coordinar el pago y la entrega.'],
    'confirmado'         => ['Confirmado',          'Tu pedido está confirmado y lo estamos preparando.'],
    'listo'              => ['Listo para entregar', 'Tu pedido está listo. Coordinamos la entrega o el retiro.'],
    'entregado'          => ['Entregado',           '¡Gracias por tu compra!'],
    'cancelado'          => ['Cancelado',           'Este pedido fue cancelado.'],
];
[$txtEstado, $descEstado] = $estadosCliente[$pedido['estado']] ?? [$pedido['estado'], ''];

$cobrado = (float) ($pedido['cobrado'] ?? 0);
$total   = (float) $pedido['total'];
$falta   = max(0, $total - $cobrado);
$pesos   = fn($n) => '$' . number_format((float) $n, 2, ',', '.');
?>

<section class="max-w-5xl mx-auto px-4 py-10">

    <div class="mb-6">
        <a href="<?= BASE_URL ?>/mi-cuenta/pedidos" class="text-sm text-gray-500 hover:text-gray-900">← Mis pedidos</a>
    </div>

    <h1 class="text-3xl font-bold mb-6">Pedido #<?= (int) $pedido['id_pedido'] ?></h1>

    <div class="bg-white border rounded-2xl p-6 mb-6">
        <p class="text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></p>
        <p class="text-xl font-bold mt-1"><?= htmlspecialchars($txtEstado) ?></p>
        <?php if ($descEstado): ?>
            <p class="text-gray-600 mt-1"><?= htmlspecialchars($descEstado) ?></p>
        <?php endif; ?>

        <?php if (!empty($pedido['seguimiento'])): ?>
            <p class="mt-3 text-sm">📦 Seguimiento del envío: <strong><?= htmlspecialchars($pedido['seguimiento']) ?></strong></p>
        <?php endif; ?>
    </div>

    <div class="bg-white border rounded-2xl p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">Productos</h2>

        <ul class="divide-y text-sm">
            <?php while ($i = $items->fetch_assoc()): ?>
                <li class="py-3 flex justify-between gap-4">
                    <div>
                        <p class="font-medium"><?= htmlspecialchars($i['producto']) ?></p>
                        <p class="text-gray-500">
                            <?= htmlspecialchars(variante_texto($i['talle'], $i['color']) ?: '—') ?> · <?= (int) $i['cantidad'] ?> u.
                        </p>
                    </div>
                    <span class="font-semibold whitespace-nowrap"><?= $pesos($i['subtotal']) ?></span>
                </li>
            <?php endwhile; ?>
        </ul>

        <div class="border-t mt-2 pt-4 space-y-1.5 text-sm">
            <?php if ((float) ($pedido['descuento'] ?? 0) > 0): ?>
                <div class="flex justify-between text-green-700"><span>Descuento</span><span>−<?= $pesos($pedido['descuento']) ?></span></div>
            <?php endif; ?>
            <?php if ((float) ($pedido['ajuste_monto'] ?? 0) != 0): ?>
                <div class="flex justify-between"><span><?= $pedido['ajuste_monto'] > 0 ? 'Recargo' : 'Descuento' ?> por medio de pago</span>
                    <span><?= $pedido['ajuste_monto'] > 0 ? '+' : '−' ?><?= $pesos(abs($pedido['ajuste_monto'])) ?></span></div>
            <?php endif; ?>
            <?php if ((float) ($pedido['envio_cobrado'] ?? 0) > 0): ?>
                <div class="flex justify-between"><span>Envío</span><span>+<?= $pesos($pedido['envio_cobrado']) ?></span></div>
            <?php endif; ?>
            <div class="flex justify-between text-lg font-bold pt-2 border-t"><span>Total</span><span><?= $pesos($total) ?></span></div>

            <?php if ($pedido['estado'] !== 'cancelado' && $cobrado > 0): ?>
                <div class="flex justify-between text-green-700"><span>Pagaste</span><span><?= $pesos($cobrado) ?></span></div>
                <?php if ($falta > 0.009): ?>
                    <div class="flex justify-between font-semibold text-orange-600"><span>Resta pagar</span><span><?= $pesos($falta) ?></span></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

</section>