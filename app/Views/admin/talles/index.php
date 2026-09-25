<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Talles</h2>
        <p class="text-gray-500">Talles disponibles para las variantes de productos</p>
    </div>

    <a href="<?= BASE_URL ?>/admin/talles/crear"
       class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800">
        Nuevo talle
    </a>
</div>

<?php if (isset($_GET['ok'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm">
        <?php
        $msgs = [
            'creado'      => 'Talle creado correctamente.',
            'actualizado' => 'Talle actualizado correctamente.',
            'eliminado'   => 'Talle eliminado correctamente.',
        ];
        echo $msgs[$_GET['ok']] ?? 'Operación realizada.';
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm">
        <?php if ($_GET['error'] === 'en_uso'): ?>
            No podés eliminar este talle porque hay variantes que lo usan.
            Si no lo querés ofrecer más, desactivalo desde "Editar".
        <?php else: ?>
            No se pudo completar la operación.
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow overflow-hidden max-w-3xl">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="text-center px-4 py-3 w-20">Orden</th>
                    <th class="text-left px-4 py-3">Nombre</th>
                    <th class="text-center px-4 py-3">Variantes</th>
                    <th class="text-center px-4 py-3">Estado</th>
                    <th class="text-right px-4 py-3">Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($talles && $talles->num_rows > 0): ?>
                    <?php while ($t = $talles->fetch_assoc()): ?>
                        <?php $enUso = (int) $t['cantidad_variantes']; ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3 text-center text-gray-400">
                                <?php echo (int) $t['orden']; ?>
                            </td>

                            <td class="px-4 py-3 font-medium text-gray-900">
                                <?php echo htmlspecialchars($t['nombre']); ?>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded bg-gray-100 text-gray-700 text-xs">
                                    <?php echo $enUso; ?>
                                </span>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <?php if ($t['activo'] == 1): ?>
                                    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Activo</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">Inactivo</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-3">
                                    <a href="<?= BASE_URL ?>/admin/talles/editar?id=<?php echo (int) $t['id_talle']; ?>"
                                       class="text-gray-600 hover:text-gray-900">
                                        Editar
                                    </a>

                                    <?php if ($enUso === 0): ?>
                                        <?= boton_eliminar(
                                            BASE_URL . '/admin/talles/eliminar',
                                            ['id' => $t['id_talle']],
                                            '¿Seguro que querés eliminar este talle?',
                                            'Eliminar',
                                            'text-red-500 hover:text-red-700'
                                        ) ?>
                                    <?php else: ?>
                                        <span class="text-gray-300 cursor-not-allowed" title="Hay variantes que usan este talle">
                                            Eliminar
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            No hay talles cargados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-xs text-gray-400 mt-3 max-w-3xl">
    El orden define cómo aparecen los talles en la tienda (ej: XS=1, S=2, M=3…). Números más bajos van primero.
</p>