<?php
$pesos = fn($n) => '$' . number_format((float) $n, 2, ',', '.');
$num   = fn($n) => number_format((float) $n, 0, ',', '.');

$stockTotal = array_sum(array_map(fn($v) => (int) $v['stock'], $variantes));
$reservado  = array_sum(array_map(fn($v) => (int) $v['stock_reservado'], $variantes));
$disponible = array_sum(array_map(fn($v) => max(0, (int) $v['stock_disponible']), $variantes));

$ganancia = $producto['precio_costo'] !== null ? $producto['precio_base'] - $producto['precio_costo'] : null;
$margen   = $ganancia !== null && $producto['precio_base'] > 0 ? $ganancia / $producto['precio_base'] * 100 : null;

$idp = (int) $producto['id_producto'];

// Estados de pedido (viejos y nuevos, para que se vean bien en los dos casos)
$estados = [
    'pendiente_contacto' => 'Esperando contacto', 'contactado' => 'Contactado',
    'pendiente_pago' => 'Pendiente de pago', 'pagado' => 'Pagado',
    'nuevo' => 'Nuevo', 'confirmado' => 'Confirmado', 'encargado' => 'Encargado',
    'llego' => 'Llegó', 'listo' => 'Listo para entregar',
    'entregado' => 'Entregado', 'cancelado' => 'Cancelado',
];
?>

<!-- Encabezado -->
<div class="flex items-start justify-between gap-4 px-5 py-4 border-b sticky top-0 bg-white z-10">
    <div class="min-w-0">
        <p class="text-xs text-gray-400">
            <?= htmlspecialchars($producto['categoria']) ?><?= $producto['marca'] ? ' · ' . htmlspecialchars($producto['marca']) : '' ?>
        </p>
        <h3 class="text-xl font-bold text-gray-900 leading-tight">
            <?= (int) $producto['destacado'] === 1 ? '<span class="text-yellow-500">★</span> ' : '' ?><?= htmlspecialchars($producto['nombre']) ?>
        </h3>
        <div class="flex flex-wrap gap-2 mt-2">
            <?php if ((int) $producto['activo'] === 1): ?>
                <span class="px-2 py-0.5 text-xs rounded bg-green-100 text-green-700">Activo</span>
            <?php else: ?>
                <span class="px-2 py-0.5 text-xs rounded bg-red-100 text-red-700">Inactivo</span>
            <?php endif; ?>
            <?php if ($disponible <= 0): ?>
                <span class="px-2 py-0.5 text-xs rounded bg-red-100 text-red-700">⛔ Agotado</span>
            <?php endif; ?>
            <?php if (empty($fotos)): ?>
                <span class="px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-600">🖼️ Sin foto</span>
            <?php endif; ?>
            <?php if ($producto['precio_costo'] === null): ?>
                <span class="px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-600">💲 Sin costo</span>
            <?php endif; ?>
        </div>
    </div>
    <button type="button" onclick="cerrarModalProducto()" aria-label="Cerrar"
            class="p-2 -mr-2 rounded-lg text-gray-400 hover:text-gray-900 hover:bg-gray-100 shrink-0">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
        </svg>
    </button>
</div>

<div class="p-5 grid grid-cols-1 md:grid-cols-5 gap-6">

    <!-- ── Fotos ───────────────────────────────────────────── -->
    <div class="md:col-span-2">
        <div class="aspect-[3/4] bg-gray-100 rounded-xl overflow-hidden">
            <?php if ($fotos): ?>
                <img id="modalFotoPrincipal"
                     src="<?= BASE_URL ?>/public/uploads/productos/<?= htmlspecialchars($fotos[0]['imagen']) ?>"
                     alt="" class="w-full h-full object-cover">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-gray-400 text-sm">Sin fotos</div>
            <?php endif; ?>
        </div>

        <?php if (count($fotos) > 1): ?>
            <div class="grid grid-cols-5 gap-2 mt-2">
                <?php foreach ($fotos as $f): ?>
                    <button type="button"
                            onclick="document.getElementById('modalFotoPrincipal').src = this.querySelector('img').src"
                            class="aspect-square rounded-lg overflow-hidden border hover:border-gray-900">
                        <img src="<?= BASE_URL ?>/public/uploads/productos/<?= htmlspecialchars($f['imagen']) ?>"
                             alt="" loading="lazy" class="w-full h-full object-cover">
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Datos ───────────────────────────────────────────── -->
    <div class="md:col-span-3 space-y-5">

        <!-- Precios -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Precio</p>
                <p class="font-bold text-gray-900"><?= $pesos($producto['precio_base']) ?></p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Costo</p>
                <p class="font-bold text-gray-700"><?= $producto['precio_costo'] !== null ? $pesos($producto['precio_costo']) : '—' ?></p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Ganancia</p>
                <p class="font-bold <?= $ganancia !== null && $ganancia < 0 ? 'text-red-600' : 'text-green-700' ?>">
                    <?= $ganancia !== null ? $pesos($ganancia) : '—' ?>
                </p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Margen</p>
                <p class="font-bold text-gray-900"><?= $margen !== null ? number_format($margen, 1, ',', '.') . '%' : '—' ?></p>
            </div>
        </div>

        <!-- Stock y rendimiento -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Disponible</p>
                <p class="font-bold <?= $disponible <= 0 ? 'text-red-600' : 'text-gray-900' ?>"><?= $num($disponible) ?></p>
                <p class="text-[11px] text-gray-400"><?= $num($stockTotal) ?> total<?= $reservado ? ' · ' . $num($reservado) . ' reservado' : '' ?></p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Vistas (30 días)</p>
                <p class="font-bold text-gray-900"><?= $num($resumen['vistas_30d']) ?></p>
                <p class="text-[11px] text-gray-400"><?= $num($resumen['personas_30d']) ?> persona(s)</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Vendidos (30 días)</p>
                <p class="font-bold text-gray-900"><?= $num($resumen['vendidos_30d']) ?></p>
                <p class="text-[11px] text-gray-400"><?= $num($resumen['vendidos_total']) ?> en total</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <p class="text-xs text-gray-500">Facturado</p>
                <p class="font-bold text-gray-900"><?= $pesos($resumen['facturado_total']) ?></p>
                <p class="text-[11px] text-gray-400">histórico</p>
            </div>
        </div>

        <?php if (!empty($producto['descripcion'])): ?>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Descripción</p>
                <p class="text-sm text-gray-700 whitespace-pre-line"><?= htmlspecialchars($producto['descripcion']) ?></p>
            </div>
        <?php endif; ?>

        <!-- Variantes -->
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                Variantes (<?= count($variantes) ?>)
            </p>

            <?php if (empty($variantes)): ?>
                <p class="text-sm text-gray-400">Todavía no tiene variantes cargadas.</p>
            <?php else: ?>
                <div class="border rounded-lg overflow-hidden max-h-64 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs sticky top-0">
                            <tr>
                                <th class="text-left px-3 py-2">Variante</th>
                                <th class="text-right px-3 py-2">Stock</th>
                                <th class="text-right px-3 py-2">Reserv.</th>
                                <th class="text-right px-3 py-2">Precio</th>
                                <th class="text-center px-3 py-2">Activa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($variantes as $v): ?>
                                <tr class="border-t <?= (int) $v['activo'] !== 1 ? 'text-gray-400' : '' ?>">
                                    <td class="px-3 py-1.5 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5">
                                            <?php if (!empty($v['codigo_hex'])): ?>
                                                <span class="w-3 h-3 rounded-full border" style="background:<?= htmlspecialchars($v['codigo_hex']) ?>"></span>
                                            <?php endif; ?>
                                            <?= htmlspecialchars(variante_texto($v['talle'], $v['color'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-3 py-1.5 text-right <?= $v['stock_disponible'] <= 0 && (int) $v['activo'] === 1 ? 'text-red-600 font-semibold' : '' ?>">
                                        <?= (int) $v['stock'] ?>
                                    </td>
                                    <td class="px-3 py-1.5 text-right text-orange-600"><?= (int) $v['stock_reservado'] ?: '' ?></td>
                                    <td class="px-3 py-1.5 text-right"><?= $v['precio'] !== null ? $pesos($v['precio']) : '<span class="text-gray-400">base</span>' ?></td>
                                    <td class="px-3 py-1.5 text-center"><?= (int) $v['activo'] === 1 ? '✓' : '—' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Últimos pedidos -->
        <div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Últimos pedidos</p>

            <?php if (empty($pedidos)): ?>
                <p class="text-sm text-gray-400">Todavía no se vendió.</p>
            <?php else: ?>
                <ul class="divide-y border rounded-lg text-sm">
                    <?php foreach ($pedidos as $pe): ?>
                        <li>
                            <a href="<?= BASE_URL ?>/admin/pedido/<?= (int) $pe['id_pedido'] ?>"
                               class="flex items-center justify-between gap-3 px-3 py-2 hover:bg-gray-50">
                                <span class="min-w-0">
                                    <span class="font-medium">#<?= (int) $pe['id_pedido'] ?></span>
                                    <span class="text-gray-500 truncate">· <?= htmlspecialchars(trim(($pe['nombre'] ?? '') . ' ' . ($pe['apellido'] ?? ''))) ?: '—' ?></span>
                                    <span class="block text-xs text-gray-400">
                                        <?= date('d/m/Y', strtotime($pe['fecha'])) ?> · <?= htmlspecialchars($estados[$pe['estado']] ?? $pe['estado']) ?>
                                    </span>
                                </span>
                                <span class="text-right whitespace-nowrap">
                                    <?= (int) $pe['unidades'] ?> u.
                                    <span class="block text-xs text-gray-500"><?= $pesos($pe['importe']) ?></span>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Botones -->
<div class="flex flex-wrap justify-end gap-2 px-5 py-4 border-t sticky bottom-0 bg-white">
    <?php if ((int) $producto['activo'] === 1): ?>
        <a href="<?= BASE_URL ?>/producto/<?= htmlspecialchars($producto['slug']) ?>" target="_blank"
           class="px-4 py-2 rounded-lg border text-gray-700 hover:bg-gray-50 text-sm">↗ Ver en tienda</a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/admin/productos/fotos?id=<?= $idp ?>" class="px-4 py-2 rounded-lg border text-gray-700 hover:bg-gray-50 text-sm">Fotos</a>
    <a href="<?= BASE_URL ?>/admin/productos/variantes?id=<?= $idp ?>" class="px-4 py-2 rounded-lg border text-gray-700 hover:bg-gray-50 text-sm">Variantes</a>
    <a href="<?= BASE_URL ?>/admin/productos/editar?id=<?= $idp ?>" class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800 text-sm">Editar</a>
</div>