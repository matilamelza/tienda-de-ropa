<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Categorías</h2>
        <p class="text-gray-500">Gestioná las categorías de productos</p>
    </div>

    <a href="<?= BASE_URL ?>/admin/categorias/crear"
       class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800">
        Nueva categoría
    </a>
</div>

<?php if (isset($_GET['ok'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm">
        <?php
        $msgs = [
            'creada'      => 'Categoría creada correctamente.',
            'actualizada' => 'Categoría actualizada correctamente.',
            'eliminada'   => 'Categoría eliminada correctamente.',
        ];
        echo $msgs[$_GET['ok']] ?? 'Operación realizada.';
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm">
        <?php if ($_GET['error'] === 'tiene_productos'): ?>
            No podés eliminar esta categoría porque tiene productos asociados.
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-100 text-gray-700">
            <tr>
                <th class="text-left px-4 py-3">Nombre</th>
                <th class="text-left px-4 py-3">Slug</th>
                <th class="text-center px-4 py-3">Productos</th>
                <th class="text-center px-4 py-3">Estado</th>
                <th class="text-right px-4 py-3">Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php if ($categorias && $categorias->num_rows > 0): ?>
                <?php while ($c = $categorias->fetch_assoc()): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">
                            <?php echo htmlspecialchars($c['nombre']); ?>
                        </td>

                        <td class="px-4 py-3 text-gray-500 font-mono text-xs">
                            <?php echo htmlspecialchars($c['slug']); ?>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded bg-gray-100 text-gray-700 text-xs">
                                <?php echo $c['cantidad_productos']; ?>
                            </span>
                        </td>

                        <td class="px-4 py-3 text-center">
                            <?php if ($c['activo'] == 1): ?>
                                <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Activa</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">Inactiva</span>
                            <?php endif; ?>
                        </td>

                        <td class="px-4 py-3 text-right space-x-3">
                            <a href="<?= BASE_URL ?>/admin/categorias/editar?id=<?php echo $c['id_categoria']; ?>"
                               class="text-gray-600 hover:text-gray-900">
                                Editar
                            </a>

                            <?php if ($c['cantidad_productos'] == 0): ?>
                                <a href="<?= BASE_URL ?>/admin/categorias/eliminar?id=<?php echo $c['id_categoria']; ?>"
                                   class="text-red-500 hover:text-red-700"
                                   onclick="return confirm('¿Seguro que querés eliminar esta categoría?')">
                                    Eliminar
                                </a>
                            <?php else: ?>
                                <span class="text-gray-300 cursor-not-allowed" title="Tiene productos asociados">
                                    Eliminar
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                        No hay categorías cargadas.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>