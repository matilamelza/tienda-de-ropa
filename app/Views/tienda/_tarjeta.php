<?php
/** Tarjeta de producto del listado. Recibe $p. */
$sinStock = isset($p['con_stock']) && !$p['con_stock'];
$foto2    = !empty($p['foto_secundaria']) ? BASE_URL . '/public/uploads/productos/' . htmlspecialchars($p['foto_secundaria']) : '';
?>
<a href="<?= BASE_URL ?>/producto/<?= htmlspecialchars($p['slug']) ?>" class="group block">
    <div class="relative aspect-[3/4] bg-gray-100 rounded-2xl overflow-hidden shadow-sm group-hover:shadow-lg transition">
        <?php if (!empty($p['foto_principal'])): ?>
            <img src="<?= BASE_URL ?>/public/uploads/productos/<?= htmlspecialchars($p['foto_principal']) ?>"
                 alt="<?= htmlspecialchars($p['nombre']) ?>"
                 class="w-full h-full object-cover transition duration-300 <?= $foto2 ? 'md:group-hover:opacity-0' : 'group-hover:scale-105' ?> <?= $sinStock ? 'opacity-60' : '' ?>"
                 loading="lazy">
            <?php if ($foto2): ?>
                <img src="<?= $foto2 ?>" alt="" aria-hidden="true" loading="lazy"
                     class="hidden md:block absolute inset-0 w-full h-full object-cover opacity-0 group-hover:opacity-100 transition duration-300">
            <?php endif; ?>
        <?php else: ?>
            <div class="w-full h-full flex items-center justify-center text-gray-400 text-sm">Sin imagen</div>
        <?php endif; ?>

        <?php if ($sinStock): ?>
            <span class="absolute top-3 left-3 text-xs font-semibold px-2.5 py-1 rounded-full bg-white/90 text-gray-700 shadow-sm">Sin stock</span>
        <?php else: ?>
            <?php if (!empty($p['destacado'])): ?>
                <span class="absolute top-3 left-3 text-xs font-semibold px-2.5 py-1 rounded-full shadow-sm"
                      style="background: var(--color-acento); color: var(--color-boton-texto)">★ Destacado</span>
            <?php endif; ?>
            <?= html_badge_oferta((float) ($p['descuento_pct'] ?? 0), $p['descuento_etiqueta'] ?? null) ?>
        <?php endif; ?>
    </div>
    <div class="mt-4">
        <p class="text-xs text-gray-400">
            <?= htmlspecialchars($p['categoria']) ?>
            <?= !empty($p['marca']) ? ' · ' . htmlspecialchars($p['marca']) : '' ?>
        </p>
        <h3 class="font-semibold text-gray-900 mt-1"><?= htmlspecialchars($p['nombre']) ?></h3>
        <p class="mt-2"><?= html_precio((float) $p['precio_base'], (float) ($p['descuento_pct'] ?? 0)) ?></p>
    </div>
</a>