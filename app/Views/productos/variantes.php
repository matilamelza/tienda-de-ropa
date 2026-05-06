<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Variantes</h2>
        <p class="text-gray-500">
            Producto: <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
        </p>
    </div>

    <a href="<?= BASE_URL ?>/admin/productos"
       class="px-4 py-2 rounded-lg border text-gray-700 bg-white">
        Volver
    </a>
</div>

<?php if (isset($_GET['ok'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm">
        <?php
        $msgs = [
            'creada'      => 'Variante agregada correctamente.',
            'actualizada' => 'Variante actualizada correctamente.',
            'eliminada'   => 'Variante eliminada correctamente.',
        ];
        echo $msgs[$_GET['ok']] ?? 'Operación realizada.';
        ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- FORMULARIO NUEVA VARIANTE -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Nueva variante</h3>

        <form action="<?= BASE_URL ?>/admin/productos/guardar-variante" method="POST" class="space-y-4">

            <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Talle</label>
                <select name="id_talle" required class="w-full border rounded-lg px-3 py-2 bg-white">
                    <option value="">Seleccionar</option>
                    <?php while ($talle = $talles->fetch_assoc()): ?>
                        <option value="<?php echo $talle['id_talle']; ?>">
                            <?php echo htmlspecialchars($talle['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                <select name="id_color" required class="w-full border rounded-lg px-3 py-2 bg-white">
                    <option value="">Seleccionar</option>
                    <?php while ($color = $colores->fetch_assoc()): ?>
                        <option value="<?php echo $color['id_color']; ?>">
                            <?php echo htmlspecialchars($color['nombre']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                <input type="text" name="sku"
                       placeholder="Ej: REM-NEG-M"
                       class="w-full border rounded-lg px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Precio especial</label>
                <input type="number" name="precio" step="0.01" min="0"
                       placeholder="Vacío = precio base"
                       class="w-full border rounded-lg px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
                <input type="number" name="stock" min="0" value="0" required
                       class="w-full border rounded-lg px-3 py-2">
            </div>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="activo" checked>
                <span class="text-sm">Activa</span>
            </label>

            <button type="submit"
                    class="w-full bg-gray-900 text-white py-2 rounded-lg hover:bg-gray-800">
                Agregar variante
            </button>
        </form>
    </div>

    <!-- LISTADO DE VARIANTES -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="text-left px-4 py-3">Talle</th>
                        <th class="text-left px-4 py-3">Color</th>
                        <th class="text-left px-4 py-3">SKU</th>
                        <th class="text-right px-4 py-3">Precio</th>
                        <th class="text-right px-4 py-3">Stock</th>
                        <th class="text-center px-4 py-3">Estado</th>
                        <th class="text-right px-4 py-3">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($variantes && $variantes->num_rows > 0): ?>
                        <?php while ($v = $variantes->fetch_assoc()): ?>
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-3"><?php echo htmlspecialchars($v['talle'] ?? '—'); ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($v['codigo_hex']): ?>
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-3 h-3 rounded-full border"
                                                  style="background:<?php echo htmlspecialchars($v['codigo_hex']); ?>"></span>
                                            <?php echo htmlspecialchars($v['color'] ?? '—'); ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($v['color'] ?? '—'); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs"><?php echo htmlspecialchars($v['sku'] ?? '—'); ?></td>
                                <td class="px-4 py-3 text-right">
                                    <?php echo $v['precio'] ? '$' . number_format($v['precio'], 2, ',', '.') : '<span class="text-gray-400">base</span>'; ?>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="<?php echo $v['stock_disponible'] <= 0 ? 'text-red-600 font-semibold' : ''; ?>">
                                        <?php echo $v['stock_disponible']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ($v['activo'] == 1): ?>
                                        <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Activa</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                                    <a href="<?= BASE_URL ?>/admin/productos/editar-variante?id=<?php echo $v['id_variante']; ?>"
                                       class="text-gray-600 hover:text-gray-900">
                                        Editar
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/productos/eliminar-variante?id=<?php echo $v['id_variante']; ?>&id_producto=<?php echo $producto['id_producto']; ?>"
                                       class="text-red-500 hover:text-red-700"
                                       onclick="return confirm('¿Eliminár esta variante?')">
                                        Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                No hay variantes cargadas.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>