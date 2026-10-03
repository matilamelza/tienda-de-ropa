<?php
$msg = $_SESSION['guias_msg'] ?? null;
unset($_SESSION['guias_msg']);

$plantillas = array_map(fn($p) => $p['nombre'], GuiaTalles::PLANTILLAS);
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Guías de talles</h2>
        <p class="text-gray-500 text-sm">Las tablas que ve el cliente al tocar "¿Qué talle soy?". Se arman una vez y cada producto elige la suya.</p>
    </div>

    <details class="relative self-start">
        <summary class="list-none cursor-pointer bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800">+ Nueva guía</summary>
        <div class="absolute right-0 mt-2 w-64 bg-white border rounded-lg shadow-lg z-20 py-1 text-sm">
            <a href="<?= BASE_URL ?>/admin/guias/editar" class="block px-4 py-2 hover:bg-gray-50">En blanco</a>
            <p class="px-4 pt-2 pb-1 text-xs text-gray-400 border-t">Desde una plantilla</p>
            <?php foreach ($plantillas as $clave => $nombre): ?>
                <a href="<?= BASE_URL ?>/admin/guias/editar?plantilla=<?= $clave ?>" class="block px-4 py-2 hover:bg-gray-50"><?= htmlspecialchars($nombre) ?></a>
            <?php endforeach; ?>
        </div>
    </details>
</div>

<?php if ($msg): ?>
    <div class="mb-4 px-4 py-3 rounded-xl text-sm border
        <?= $msg[0] === 'ok' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
        <?= htmlspecialchars($msg[1]) ?>
    </div>
<?php endif; ?>

<?php if (empty($guias)): ?>
    <div class="bg-white rounded-lg shadow px-4 py-12 text-center">
        <p class="text-gray-600 font-medium">Todavía no hay guías de talles.</p>
        <p class="text-sm text-gray-400 mt-1">Empezá desde una plantilla (calzado, remeras, pantalones…) y ajustala a tus medidas.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="text-left px-4 py-3">Guía</th>
                    <th class="text-left px-4 py-3 hidden md:table-cell">Columnas</th>
                    <th class="text-right px-4 py-3">Talles</th>
                    <th class="text-right px-4 py-3">Productos</th>
                    <th class="text-right px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($guias as $g): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="<?= BASE_URL ?>/admin/guias/editar?id=<?= (int) $g['id_guia'] ?>" class="font-medium text-gray-900 hover:underline">
                                <?= htmlspecialchars($g['nombre']) ?>
                            </a>
                            <span class="block text-xs text-gray-400">
                                <?= htmlspecialchars(implode(', ', array_slice(array_column($g['filas'], 0), 0, 6))) ?><?= count($g['filas']) > 6 ? '…' : '' ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell text-gray-500">
                            <?= htmlspecialchars(implode(' · ', array_column($g['columnas'], 'nombre'))) ?>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-500"><?= count($g['filas']) ?></td>
                        <td class="px-4 py-3 text-right">
                            <?php if ((int) $g['productos'] > 0): ?>
                                <a href="<?= BASE_URL ?>/admin/productos?guia=<?= (int) $g['id_guia'] ?>" class="text-gray-700 hover:underline"><?= (int) $g['productos'] ?></a>
                            <?php else: ?>
                                <span class="text-gray-300">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="<?= BASE_URL ?>/admin/guias/editar?id=<?= (int) $g['id_guia'] ?>" class="text-gray-600 hover:text-gray-900 mr-3">Editar</a>
                            <?= boton_eliminar(BASE_URL . '/admin/guias/duplicar', ['id' => $g['id_guia']], '¿Duplicar esta guía?', 'Duplicar', 'text-gray-500 hover:text-gray-900 mr-3') ?>
                            <?= boton_eliminar(
                                BASE_URL . '/admin/guias/eliminar',
                                ['id' => $g['id_guia']],
                                (int) $g['productos'] > 0
                                    ? '¿Eliminar esta guía? ' . (int) $g['productos'] . ' producto(s) la usan y van a quedar sin guía.'
                                    : '¿Eliminar esta guía?',
                                'Eliminar',
                                'text-red-500 hover:text-red-700'
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>