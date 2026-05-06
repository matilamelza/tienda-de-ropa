<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Productos</h2>
        <p class="text-gray-500">Listado general de productos</p>
    </div>

    <a href="<?= BASE_URL ?>/admin/productos/crear"
       class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800">
        Nuevo producto
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

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-100 text-gray-700">
            <tr>
                <th class="text-left px-4 py-3">Producto</th>
                <th class="text-left px-4 py-3">Categoría</th>
                <th class="text-left px-4 py-3">Marca</th>
                <th class="text-right px-4 py-3">Precio base</th>
                <th class="text-center px-4 py-3">Estado</th>
                <th class="text-right px-4 py-3">Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php if ($productos && $productos->num_rows > 0): ?>
                <?php while ($p = $productos->fetch_assoc()): ?>
                    <tr class="border-t hover:bg-gray-50">

                        <td class="px-4 py-3 font-medium text-gray-800">
                            <?php echo htmlspecialchars($p['nombre']); ?>
                        </td>

                        <td class="px-4 py-3 text-gray-600">
                            <?php echo htmlspecialchars($p['categoria']); ?>
                        </td>

                        <td class="px-4 py-3 text-gray-600">
                            <?php echo htmlspecialchars($p['marca'] ?? '—'); ?>
                        </td>

                        <td class="px-4 py-3 text-right">
                            $<?php echo number_format($p['precio_base'], 2, ',', '.'); ?>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <?php if ($p['activo'] == 1): ?>
                                <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Activo</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">Inactivo</span>
                            <?php endif; ?>
                        </td>

                        <td class="px-4 py-3 text-right space-x-3 whitespace-nowrap">
                            <a href="<?= BASE_URL ?>/admin/productos/editar?id=<?php echo $p['id_producto']; ?>"
                               class="text-gray-600 hover:text-gray-900">
                                Editar
                            </a>

                            <a href="<?= BASE_URL ?>/admin/productos/variantes?id=<?php echo $p['id_producto']; ?>"
                               class="text-blue-600 hover:text-blue-800">
                                Variantes
                            </a>

                            <a href="<?= BASE_URL ?>/admin/productos/fotos?id=<?php echo $p['id_producto']; ?>"
                               class="text-indigo-600 hover:text-indigo-800">
                                Fotos
                            </a>

                            <a href="<?= BASE_URL ?>/admin/productos/eliminar?id=<?php echo $p['id_producto']; ?>"
                               class="text-red-500 hover:text-red-700"
                               onclick="return confirm('¿Seguro que querés eliminar este producto? También se eliminarán sus variantes y fotos.')">
                                Eliminar
                            </a>
                        </td>

                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                        No hay productos cargados.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>