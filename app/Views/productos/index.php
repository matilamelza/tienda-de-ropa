<?php
$hayFiltros = $filtros['q'] !== '' || $filtros['categoria'] > 0 || $filtros['estado'] !== '' || $filtros['problema'] !== '';

$url = function (array $cambios = []) use ($filtros): string {
    $q = array_filter(array_merge($filtros, $cambios), fn($v) => $v !== '' && $v !== 0 && $v !== null);
    return BASE_URL . '/admin/productos' . ($q ? '?' . http_build_query($q) : '');
};

$pesos = fn($n) => '$' . number_format((float) $n, 2, ',', '.');
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Productos</h2>
        <p class="text-gray-500 text-sm"><?= (int) $total ?> producto<?= $total !== 1 ? 's' : '' ?><?= $hayFiltros ? ' con estos filtros' : '' ?></p>
    </div>

    <a href="<?= BASE_URL ?>/admin/productos/crear"
       class="self-start sm:self-auto bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800">
        + Nuevo producto
    </a>
</div>

<?php if (isset($_GET['ok'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm">
        <?php
        $msgs = [
            'creado'      => 'Producto creado correctamente.',
            'actualizado' => 'Producto actualizado correctamente.',
            'eliminado'   => 'Producto eliminado correctamente.',
        ];
        echo $msgs[$_GET['ok']] ?? 'Operación realizada.';
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm">
        No se pudo eliminar el producto. Puede que ya haya sido eliminado.
    </div>
<?php endif; ?>

<!-- ── Filtros ─────────────────────────────────────────────── -->
<form method="GET" action="<?= BASE_URL ?>/admin/productos" id="formFiltros"
      class="bg-white rounded-lg shadow p-3 mb-4 grid grid-cols-2 md:grid-cols-5 gap-2">

    <input type="text" name="q" value="<?= htmlspecialchars($filtros['q']) ?>"
           placeholder="Buscar por nombre o marca…"
           class="col-span-2 md:col-span-2 border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-200">

    <select name="categoria" class="auto-filtro border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">Todas las categorías</option>
        <?php while ($cat = $categorias->fetch_assoc()): ?>
            <option value="<?= (int) $cat['id_categoria'] ?>" <?= $filtros['categoria'] === (int) $cat['id_categoria'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['nombre']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <select name="estado" class="auto-filtro border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">Activos e inactivos</option>
        <option value="activos"   <?= $filtros['estado'] === 'activos'   ? 'selected' : '' ?>>Solo activos</option>
        <option value="inactivos" <?= $filtros['estado'] === 'inactivos' ? 'selected' : '' ?>>Solo inactivos</option>
    </select>

    <select name="problema" class="auto-filtro col-span-2 md:col-span-1 border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">Sin filtro de problemas</option>
        <option value="agotados"  <?= $filtros['problema'] === 'agotados'  ? 'selected' : '' ?>>⛔ Agotados</option>
        <option value="faltantes" <?= $filtros['problema'] === 'faltantes' ? 'selected' : '' ?>>📦 Con talles agotados</option>
        <option value="sin_foto"  <?= $filtros['problema'] === 'sin_foto'  ? 'selected' : '' ?>>🖼️ Sin foto</option>
        <option value="sin_costo" <?= $filtros['problema'] === 'sin_costo' ? 'selected' : '' ?>>💲 Sin costo</option>
    </select>

    <?php if ($hayFiltros): ?>
        <a href="<?= BASE_URL ?>/admin/productos"
           class="col-span-2 md:col-span-5 text-center text-sm text-gray-500 hover:text-red-600">
            ✕ Limpiar filtros
        </a>
    <?php endif; ?>
</form>

<?php if (empty($productos)): ?>
    <div class="bg-white rounded-lg shadow px-4 py-10 text-center text-gray-400 text-sm">
        <?= $hayFiltros ? '✅ No hay productos con estos filtros.' : 'Todavía no hay productos cargados.' ?>
    </div>
<?php else: ?>

    <!-- ── Mobile: tarjetas ─────────────────────────────────── -->
    <div class="md:hidden space-y-3">
        <?php foreach ($productos as $p): ?>
            <div class="bg-white rounded-lg shadow p-3">
                <div class="flex gap-3">
                    <div class="w-16 h-20 shrink-0 rounded bg-gray-100 overflow-hidden">
                        <?php if ($p['foto_principal']): ?>
                            <img src="<?= BASE_URL ?>/public/uploads/productos/<?= htmlspecialchars($p['foto_principal']) ?>"
                                 alt="" loading="lazy" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-300 text-xs">Sin foto</div>
                        <?php endif; ?>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-semibold text-gray-900 leading-tight"><?= htmlspecialchars($p['nombre']) ?></p>
                            <?php if ($p['activo'] != 1): ?>
                                <span class="shrink-0 px-2 py-0.5 text-xs rounded bg-red-100 text-red-700">Inactivo</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-gray-400 truncate">
                            <?= htmlspecialchars($p['categoria']) ?><?= $p['marca'] ? ' · ' . htmlspecialchars($p['marca']) : '' ?>
                        </p>
                        <div class="flex items-center justify-between mt-2 text-sm">
                            <span class="font-bold"><?= $pesos($p['precio_base']) ?></span>
                            <span class="<?= $p['stock_disponible'] <= 0 ? 'text-red-600 font-semibold' : 'text-gray-500' ?>">
                                <?= (int) $p['stock_disponible'] ?> en stock
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-3 pt-3 border-t text-sm">
                    <a href="<?= BASE_URL ?>/admin/productos/editar?id=<?= (int) $p['id_producto'] ?>" class="text-gray-700 font-medium">Editar</a>
                    <a href="<?= BASE_URL ?>/admin/productos/variantes?id=<?= (int) $p['id_producto'] ?>" class="text-blue-600">Variantes</a>
                    <a href="<?= BASE_URL ?>/admin/productos/fotos?id=<?= (int) $p['id_producto'] ?>" class="text-indigo-600">Fotos</a>
                    <?= boton_eliminar(
                        BASE_URL . '/admin/productos/eliminar',
                        ['id' => $p['id_producto']],
                        '¿Eliminar este producto? Va a dejar de aparecer en la tienda y en el admin. Los pedidos anteriores no se modifican.',
                        'Eliminar',
                        'text-red-500'
                    ) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Desktop: tabla ───────────────────────────────────── -->
    <div class="hidden md:block bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3">Producto</th>
                        <th class="text-right px-4 py-3">Precio</th>
                        <th class="text-right px-4 py-3">Costo</th>
                        <th class="text-right px-4 py-3">Ganancia</th>
                        <th class="text-right px-4 py-3">Stock</th>
                        <th class="text-center px-4 py-3">Estado</th>
                        <th class="text-right px-4 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-12 shrink-0 rounded bg-gray-100 overflow-hidden">
                                        <?php if ($p['foto_principal']): ?>
                                            <img src="<?= BASE_URL ?>/public/uploads/productos/<?= htmlspecialchars($p['foto_principal']) ?>"
                                                 alt="" loading="lazy" class="w-full h-full object-cover">
                                        <?php endif; ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-900"><?= htmlspecialchars($p['nombre']) ?></p>
                                        <p class="text-xs text-gray-400">
                                            <?= htmlspecialchars($p['categoria']) ?><?= $p['marca'] ? ' · ' . htmlspecialchars($p['marca']) : '' ?>
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap"><?= $pesos($p['precio_base']) ?></td>

                            <td class="px-4 py-3 text-right text-gray-500 whitespace-nowrap">
                                <?= $p['precio_costo'] !== null ? $pesos($p['precio_costo']) : '—' ?>
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <?php if ($p['precio_costo'] !== null): ?>
                                    <?php
                                    $ganancia = $p['precio_base'] - $p['precio_costo'];
                                    $margen   = $p['precio_base'] > 0 ? $ganancia / $p['precio_base'] * 100 : 0;
                                    ?>
                                    <span class="font-semibold <?= $ganancia < 0 ? 'text-red-600' : 'text-green-700' ?>"><?= $pesos($ganancia) ?></span>
                                    <span class="block text-xs text-gray-400"><?= number_format($margen, 1, ',', '.') ?>% margen</span>
                                <?php else: ?>
                                    <span class="text-gray-300">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <span class="<?= $p['stock_disponible'] <= 0 ? 'text-red-600 font-semibold' : '' ?>">
                                    <?= (int) $p['stock_disponible'] ?>
                                </span>
                                <span class="block text-xs text-gray-400"><?= (int) $p['cant_variantes'] ?> variante(s)</span>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <?php if ($p['activo'] == 1): ?>
                                    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Activo</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">Inactivo</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-3">
                                    <a href="<?= BASE_URL ?>/admin/productos/editar?id=<?= (int) $p['id_producto'] ?>" class="text-gray-600 hover:text-gray-900">Editar</a>
                                    <a href="<?= BASE_URL ?>/admin/productos/variantes?id=<?= (int) $p['id_producto'] ?>" class="text-blue-600 hover:text-blue-800">Variantes</a>
                                    <a href="<?= BASE_URL ?>/admin/productos/fotos?id=<?= (int) $p['id_producto'] ?>" class="text-indigo-600 hover:text-indigo-800">Fotos</a>
                                    <?= boton_eliminar(
                                        BASE_URL . '/admin/productos/eliminar',
                                        ['id' => $p['id_producto']],
                                        '¿Eliminar este producto? Va a dejar de aparecer en la tienda y en el admin. Los pedidos anteriores no se modifican.',
                                        'Eliminar',
                                        'text-red-500 hover:text-red-700'
                                    ) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<!-- ── Paginación ───────────────────────────────────────────── -->
<?php if ($totalPaginas > 1): ?>
    <?php
    $inicio = max(1, $pagina - 2);
    $fin    = min($totalPaginas, $pagina + 2);
    ?>
    <div class="mt-6 flex flex-wrap items-center justify-center gap-1 text-sm">
        <?php if ($pagina > 1): ?>
            <a href="<?= $url(['pagina' => $pagina - 1]) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">←</a>
        <?php endif; ?>

        <?php if ($inicio > 1): ?>
            <a href="<?= $url(['pagina' => 1]) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">1</a>
            <?php if ($inicio > 2): ?><span class="px-2 text-gray-400">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
            <a href="<?= $url(['pagina' => $i]) ?>"
               class="px-3 py-2 rounded-lg border <?= $i === $pagina ? 'bg-gray-800 text-white border-gray-800' : 'bg-white hover:bg-gray-50' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($fin < $totalPaginas): ?>
            <?php if ($fin < $totalPaginas - 1): ?><span class="px-2 text-gray-400">…</span><?php endif; ?>
            <a href="<?= $url(['pagina' => $totalPaginas]) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50"><?= $totalPaginas ?></a>
        <?php endif; ?>

        <?php if ($pagina < $totalPaginas): ?>
            <a href="<?= $url(['pagina' => $pagina + 1]) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">→</a>
        <?php endif; ?>
    </div>
    <p class="text-center text-xs text-gray-400 mt-2">Página <?= $pagina ?> de <?= $totalPaginas ?></p>
<?php endif; ?>

<script>
// Los selects filtran apenas cambian (el buscador se aplica con Enter)
document.querySelectorAll('#formFiltros .auto-filtro').forEach(sel => {
    sel.addEventListener('change', () => sel.form.submit());
});
</script>