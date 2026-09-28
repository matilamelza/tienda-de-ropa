<?php
$msg = $_SESSION['promo_msg'] ?? null;
unset($_SESSION['promo_msg']);

$idPromo = (int) $promo['id_promocion'];
$pesos   = fn($n) => '$' . number_format((float) $n, 0, ',', '.');
$pctTxt  = fn($n) => rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',');
$fecha   = fn($f) => $f ? date('d/m/Y H:i', strtotime($f)) : null;
?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>/admin/promociones" class="text-sm text-gray-500 hover:text-gray-900">← Promociones</a>

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mt-1">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($promo['nombre']) ?></h2>
                <?php require __DIR__ . '/_estado.php'; ?>
            </div>
            <p class="text-sm text-gray-500 mt-1">
                <strong class="text-gray-900">-<?= $pctTxt($promo['pct']) ?>%</strong>
                <?= $promo['etiqueta'] ? ' · etiqueta "' . htmlspecialchars($promo['etiqueta']) . '"' : '' ?>
                · <?= $promo['desde'] ? 'desde ' . $fecha($promo['desde']) : 'desde que se creó' ?>
                · <?= $promo['hasta'] ? 'hasta ' . $fecha($promo['hasta']) : 'sin fecha de fin' ?>
            </p>
        </div>

        <div class="flex gap-2 shrink-0">
            <a href="<?= BASE_URL ?>/admin/promociones/editar?id=<?= $idPromo ?>"
               class="px-3 py-2 rounded-lg border bg-white text-sm text-gray-700 hover:bg-gray-50">Editar</a>
            <?= boton_eliminar(
                BASE_URL . '/admin/promociones/toggle',
                ['id' => $idPromo],
                (int) $promo['activa'] === 1 ? '¿Pausar la promoción? Los productos vuelven a su precio normal.' : '¿Reanudar la promoción?',
                (int) $promo['activa'] === 1 ? '❚❚ Pausar' : '▶ Reanudar',
                'px-3 py-2 rounded-lg border bg-white text-sm text-gray-700 hover:bg-gray-50'
            ) ?>
            <?= boton_eliminar(
                BASE_URL . '/admin/promociones/eliminar',
                ['id' => $idPromo],
                '¿Eliminar esta promoción? Los productos quedan con su precio normal. Las ventas ya hechas no se modifican.',
                'Eliminar',
                'px-3 py-2 rounded-lg border border-red-200 bg-white text-sm text-red-600 hover:bg-red-50'
            ) ?>
        </div>
    </div>
</div>

<?php if ($msg): ?>
    <div class="mb-4 px-4 py-3 rounded-xl text-sm border
        <?= $msg[0] === 'ok' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
        <?= htmlspecialchars($msg[1]) ?>
    </div>
<?php endif; ?>

<?php if (empty($productos)): ?>
    <div class="bg-white rounded-lg shadow p-10 text-center">
        <p class="text-4xl mb-3">🛍️</p>
        <p class="font-medium text-gray-700">Esta promoción todavía no tiene productos.</p>
        <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">
            Andá a <strong>Productos</strong>, filtrá los que quieras (por ejemplo, una marca), seleccionalos
            y elegí <strong>"Agregar a promoción"</strong> en la barra de acciones.
        </p>
        <a href="<?= BASE_URL ?>/admin/productos" class="inline-block mt-4 bg-gray-900 text-white px-4 py-2 rounded-lg text-sm">Ir a Productos</a>
    </div>
<?php else: ?>

    <form method="POST" action="<?= BASE_URL ?>/admin/promociones/productos" id="formPromoProductos">
        <?= csrf_field() ?>
        <input type="hidden" name="id_promocion" value="<?= $idPromo ?>">
        <input type="hidden" name="accion" id="accionPromo" value="guardar_pct">

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b bg-gray-50 text-sm">
                <p class="text-gray-600"><strong><?= count($productos) ?></strong> producto(s) en esta promoción</p>
                <div class="flex gap-2">
                    <button type="button" onclick="quitarSeleccionados()"
                            class="px-3 py-1.5 rounded-lg border border-red-200 bg-white text-red-600 hover:bg-red-50">Quitar seleccionados</button>
                    <button class="px-3 py-1.5 rounded-lg bg-gray-900 text-white hover:bg-gray-800">Guardar descuentos</button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 text-gray-600">
                        <tr>
                            <th class="px-3 py-3 w-8"><input type="checkbox" id="selTodosPromo"></th>
                            <th class="text-left px-3 py-3">Producto</th>
                            <th class="text-right px-3 py-3">Precio</th>
                            <th class="text-right px-3 py-3">Descuento</th>
                            <th class="text-right px-3 py-3">Queda en</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $p): ?>
                            <?php $pctEfectivo = $p['pct_propio'] !== null ? (float) $p['pct_propio'] : (float) $promo['pct']; ?>
                            <tr class="border-t">
                                <td class="px-3 py-2">
                                    <input type="checkbox" class="sel-promo" value="<?= (int) $p['id_producto'] ?>">
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-11 shrink-0 rounded bg-gray-100 overflow-hidden">
                                            <?php if ($p['foto_principal']): ?>
                                                <img src="<?= BASE_URL ?>/public/uploads/productos/<?= htmlspecialchars($p['foto_principal']) ?>"
                                                     alt="" loading="lazy" class="w-full h-full object-cover">
                                            <?php endif; ?>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-900"><?= htmlspecialchars($p['nombre']) ?></p>
                                            <p class="text-xs text-gray-400">
                                                <?= $p['marca'] ? htmlspecialchars($p['marca']) : '' ?>
                                                <?= (int) $p['activo'] !== 1 ? ' · <span class="text-red-500">inactivo</span>' : '' ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-right text-gray-500 whitespace-nowrap"><?= $pesos($p['precio_base']) ?></td>
                                <td class="px-3 py-2 text-right">
                                    <span class="relative inline-block">
                                        <input type="number" name="pct[<?= (int) $p['id_producto'] ?>]" min="1" max="99" step="0.01"
                                               value="<?= $p['pct_propio'] !== null ? htmlspecialchars((string) $p['pct_propio']) : '' ?>"
                                               placeholder="<?= $pctTxt($promo['pct']) ?>"
                                               class="w-20 border rounded px-2 py-1 pr-5 text-right">
                                        <span class="absolute right-1.5 top-1 text-gray-400 text-xs">%</span>
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right font-semibold text-green-700 whitespace-nowrap">
                                    <?= $pesos(precio_con_descuento((float) $p['precio_base'], $pctEfectivo)) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="px-4 py-3 text-xs text-gray-400 border-t">
                El % en gris es el de la promoción. Escribí otro solo si ese producto lleva un descuento distinto.
                Si un producto está en dos promociones vigentes a la vez, se aplica el descuento mayor.
                Las variantes con precio especial reciben el mismo %.
            </p>
        </div>

        <div id="idsQuitar"></div>
    </form>

    <script>
    (function () {
        const todos = document.getElementById('selTodosPromo');
        const cajas = document.querySelectorAll('.sel-promo');

        todos.addEventListener('change', () => cajas.forEach(c => c.checked = todos.checked));

        window.quitarSeleccionados = function () {
            const sel = [...cajas].filter(c => c.checked);
            if (!sel.length) { alert('Seleccioná los productos que querés quitar.'); return; }
            if (!confirm(`¿Quitar ${sel.length} producto(s) de la promoción? Vuelven a su precio normal.`)) return;

            document.getElementById('accionPromo').value = 'quitar';
            document.getElementById('idsQuitar').innerHTML =
                sel.map(c => `<input type="hidden" name="ids[]" value="${c.value}">`).join('');
            document.getElementById('formPromoProductos').submit();
        };
    })();
    </script>

<?php endif; ?>