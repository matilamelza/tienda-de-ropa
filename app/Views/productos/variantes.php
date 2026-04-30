<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Variantes</h2>
        <p class="text-gray-500">
            Producto: <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
        </p>
    </div>

    <a href="index.php?route=productos"
       class="px-4 py-2 rounded-lg border text-gray-700 bg-white">
        Volver
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Nueva variante</h3>

        <form action="index.php?route=productos_guardar_variante" method="POST" class="space-y-4">

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
                       placeholder="Vacío usa precio base"
                       class="w-full border rounded-lg px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
                <input type="number" name="stock" min="0" value="0" required
                       class="w-full border rounded-lg px-3 py-2">
            </div>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="activo" checked>
                <span>Activa</span>
            </label>

            <button type="submit"
                    class="w-full bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800">
                Guardar variante
            </button>

        </form>
    </div>

    <div class="lg:col-span-2 bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="text-left px-4 py-3">Talle</th>
                    <th class="text-left px-4 py-3">Color</th>
                    <th class="text-left px-4 py-3">SKU</th>
                    <th class="text-right px-4 py-3">Precio</th>
                    <th class="text-center px-4 py-3">Stock</th>
                    <th class="text-center px-4 py-3">Estado</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($variantes && $variantes->num_rows > 0): ?>
                    <?php while ($v = $variantes->fetch_assoc()): ?>
                        <tr class="border-t">
                            <td class="px-4 py-3 font-medium">
                                <?php echo htmlspecialchars($v['talle'] ?? '-'); ?>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <?php if (!empty($v['codigo_hex'])): ?>
                                        <span class="w-4 h-4 rounded-full border inline-block"
                                              style="background: <?php echo htmlspecialchars($v['codigo_hex']); ?>"></span>
                                    <?php endif; ?>

                                    <?php echo htmlspecialchars($v['color'] ?? '-'); ?>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <?php echo htmlspecialchars($v['sku'] ?: '-'); ?>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <?php if ($v['precio'] !== null): ?>
                                    $<?php echo number_format($v['precio'], 2, ',', '.'); ?>
                                <?php else: ?>
                                    <span class="text-gray-400">Precio base</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <?php if ($v['stock'] <= 0): ?>
                                    <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">Sin stock</span>
                                <?php elseif ($v['stock'] <= 3): ?>
                                    <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700">
                                        <?php echo $v['stock']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                                        <?php echo $v['stock']; ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <?php if ($v['activo'] == 1): ?>
                                    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Activa</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">Inactiva</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                            Este producto todavía no tiene variantes.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>