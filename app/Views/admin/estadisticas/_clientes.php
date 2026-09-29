<?php
$pctRecompra = $recompra['clientes'] > 0 ? round($recompra['repiten'] / $recompra['clientes'] * 100) : 0;

/** Link de WhatsApp al cliente (celulares argentinos: 549 + número sin 0) */
$waCliente = function (array $c, string $mensaje): ?string {
    $tel = preg_replace('/\D/', '', $c['telefono'] ?? '');
    if ($tel === '') return null;
    if (substr($tel, 0, 2) !== '54') {
        $tel = '549' . ltrim($tel, '0');
    }
    return 'https://wa.me/' . $tel . '?text=' . rawurlencode($mensaje);
};

$haceCuanto = function (string $fecha): string {
    $dias = (int) (new DateTime($fecha))->diff(new DateTime())->days;
    if ($dias < 1)  return 'hoy';
    if ($dias < 31) return "hace $dias días";
    $meses = (int) round($dias / 30);
    return "hace $meses mes" . ($meses > 1 ? 'es' : '');
};
?>

<!-- ── Números ───────────────────────────────────────────────────── -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500 mb-2">Compraron en el período</p>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $num($clientesPer['total']) ?></p>
        <p class="text-xs text-gray-400 mt-1">clientes distintos</p>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500 mb-2">Clientes nuevos</p>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $num($clientesPer['nuevos']) ?></p>
        <p class="text-xs text-gray-400 mt-1">su primera compra</p>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500 mb-2">Volvieron a comprar</p>
        <p class="text-2xl md:text-3xl font-bold text-green-700"><?= $num($clientesPer['recurrentes']) ?></p>
        <p class="text-xs text-gray-400 mt-1">ya habían comprado antes</p>
    </div>
    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500 mb-2">Recompra (histórico)</p>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $pctRecompra ?>%</p>
        <p class="text-xs text-gray-400 mt-1"><?= $num($recompra['repiten']) ?> de <?= $num($recompra['clientes']) ?> compraron más de una vez</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- ── Mejores clientes ─────────────────────────────────────── -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-5 py-4 border-b">
            <h3 class="font-bold text-gray-800">🏆 Mejores clientes del período</h3>
        </div>
        <?php if (empty($mejores)): ?>
            <p class="px-5 py-8 text-sm text-gray-400 text-center">No hay ventas en este período.</p>
        <?php else: ?>
            <ul class="divide-y text-sm">
                <?php foreach ($mejores as $i => $c): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/cliente?id=<?= (int) $c['id_cliente'] ?>"
                           class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50">
                            <span class="w-6 text-center font-bold text-gray-400"><?= $i + 1 ?></span>
                            <span class="flex-1 min-w-0">
                                <span class="block font-medium text-gray-900 truncate"><?= htmlspecialchars(trim($c['nombre'] . ' ' . $c['apellido'])) ?></span>
                                <span class="block text-xs text-gray-400"><?= (int) $c['pedidos'] ?> pedido(s) · última <?= $haceCuanto($c['ultima']) ?></span>
                            </span>
                            <span class="font-semibold whitespace-nowrap"><?= $pesos($c['total']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- ── Para reactivar ───────────────────────────────────────── -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-5 py-4 border-b">
            <h3 class="font-bold text-gray-800">📲 Para reactivar</h3>
            <p class="text-xs text-gray-400">Compraron hace entre 3 meses y 1 año y no volvieron. Buenos candidatos para mandarles una promo.</p>
        </div>
        <?php if (empty($reactivar)): ?>
            <p class="px-5 py-8 text-sm text-gray-400 text-center">✅ Ningún cliente en esa situación.</p>
        <?php else: ?>
            <ul class="divide-y text-sm max-h-[480px] overflow-y-auto">
                <?php foreach ($reactivar as $c): ?>
                    <?php
                    $nombre = trim($c['nombre'] . ' ' . $c['apellido']);
                    $wa     = $waCliente($c, 'Hola ' . $c['nombre'] . '! ¿Cómo estás? Te escribimos de la tienda: tenemos novedades que te pueden gustar 😊');
                    ?>
                    <li class="flex items-center gap-3 px-5 py-3">
                        <a href="<?= BASE_URL ?>/admin/cliente?id=<?= (int) $c['id_cliente'] ?>" class="flex-1 min-w-0 hover:underline">
                            <span class="block font-medium text-gray-900 truncate"><?= htmlspecialchars($nombre) ?></span>
                            <span class="block text-xs text-gray-400">
                                <?= (int) $c['pedidos'] ?> pedido(s) · <?= $pesos($c['total']) ?> · última <?= $haceCuanto($c['ultima']) ?>
                            </span>
                        </a>
                        <?php if ($wa): ?>
                            <a href="<?= htmlspecialchars($wa) ?>" target="_blank" rel="noopener"
                               class="shrink-0 px-3 py-1.5 rounded-lg bg-green-600 text-white text-xs hover:bg-green-700">WhatsApp</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<p class="text-xs text-gray-400 mt-4">
    Cuentan los pedidos pagados o entregados. "Para reactivar" no depende del período elegido arriba.
</p>