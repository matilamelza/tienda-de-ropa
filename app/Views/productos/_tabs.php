<?php
/**
 * Encabezado + pestañas de un producto.
 * Antes de incluirlo: $producto (array) y $tabActiva ('datos' | 'variantes' | 'fotos').
 */
$idProd = (int) $producto['id_producto'];

$tabs = [
    'datos'     => ['Datos',     BASE_URL . '/admin/productos/editar?id=' . $idProd],
    'variantes' => ['Variantes', BASE_URL . '/admin/productos/variantes?id=' . $idProd],
    'fotos'     => ['Fotos',     BASE_URL . '/admin/productos/fotos?id=' . $idProd],
];
?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>/admin/productos" class="text-sm text-gray-500 hover:text-gray-900">← Productos</a>

    <h2 class="text-2xl font-bold text-gray-800 mt-1 truncate"><?= htmlspecialchars($producto['nombre']) ?></h2>

    <div class="flex gap-1 mt-4 border-b overflow-x-auto">
        <?php foreach ($tabs as $clave => [$texto, $url]): ?>
            <a href="<?= $url ?>"
               class="px-4 py-2 text-sm whitespace-nowrap border-b-2 -mb-px
                      <?= $tabActiva === $clave
                          ? 'border-gray-900 text-gray-900 font-semibold'
                          : 'border-transparent text-gray-500 hover:text-gray-900' ?>">
                <?= $texto ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>