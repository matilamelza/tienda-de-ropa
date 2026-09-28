<?php
$nombreTienda = $conf['tienda_nombre'] ?? 'Tienda';
$logo         = $conf['tienda_logo'] ?? '';
$whatsapp     = trim($conf['tienda_whatsapp'] ?? '');
$sitio        = preg_replace('#^https?://#', '', rtrim(url_absoluta(''), '/'));

$fmt = fn($n) => '$' . number_format($n, 0, ',', '.');

$clasesColumnas = [
    2 => 'grid-cols-2',
    3 => 'grid-cols-2 sm:grid-cols-3',
    4 => 'grid-cols-2 sm:grid-cols-4',
];

// Agrupar por categoría, para mostrar un título por sección
$porCategoria = [];
foreach ($productos as $p) {
    $porCategoria[$p['categoria']][] = $p;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars(($titulo ?: 'Catálogo') . ' · ' . $nombreTienda) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= (new ConfiguracionTienda())->generarCSS() ?>
    <style>
        @page { size: A4; margin: 12mm; }

        @media print {
            .no-imprimir { display: none !important; }
            body { background: #fff !important; }
            .hoja { box-shadow: none !important; margin: 0 !important; padding: 0 !important; max-width: none !important; }
            /* Que un producto no quede cortado entre dos páginas */
            .producto, .titulo-seccion { break-inside: avoid; page-break-inside: avoid; }
            .titulo-seccion { break-after: avoid; page-break-after: avoid; }
            /* Que se impriman los colores de fondo (etiquetas de oferta, chips) */
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900">

<!-- ── Barra de acciones (no se imprime) ─────────────────────────── -->
<div class="no-imprimir sticky top-0 z-10 bg-gray-900 text-white">
    <div class="max-w-4xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-3 text-sm">
        <div>
            <strong><?= count($productos) ?></strong> producto(s)
            <?php if ($ajuste != 0): ?>
                · precios <?= $ajuste > 0 ? '+' : '' ?><?= pct_texto($ajuste) ?>%
            <?php endif; ?>
            <?php if (!$precios): ?> · sin precios<?php endif; ?>
        </div>
        <div class="flex items-center gap-2">
            <a href="javascript:history.back()" class="px-3 py-2 rounded-lg text-gray-300 hover:text-white">← Volver</a>
            <button onclick="window.print()" class="px-4 py-2 rounded-lg bg-white text-gray-900 font-semibold hover:bg-gray-100">
                🖨️ Imprimir / Guardar PDF
            </button>
        </div>
    </div>
    <p class="max-w-4xl mx-auto px-4 pb-3 text-xs text-gray-400">
        Para guardarlo como PDF: en "Destino" (o "Impresora") elegí <strong>Guardar como PDF</strong>.
        En el celular: Compartir → Imprimir → pellizcá la vista previa para abrir el PDF.
    </p>
</div>

<!-- ── Hoja ──────────────────────────────────────────────────────── -->
<main class="hoja max-w-4xl mx-auto bg-white shadow-lg my-6 p-6 sm:p-10">

    <!-- Encabezado -->
    <header class="flex items-center justify-between gap-6 pb-6 mb-6 border-b-2" style="border-color: var(--color-primario, #111827)">
        <div class="flex items-center gap-4 min-w-0">
            <?php if ($logo): ?>
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($logo) ?>" alt="" class="h-14 w-auto object-contain">
            <?php endif; ?>
            <div class="min-w-0">
                <p class="text-2xl font-bold leading-tight"><?= htmlspecialchars($nombreTienda) ?></p>
                <?php if ($titulo): ?>
                    <p class="text-lg text-gray-600"><?= htmlspecialchars($titulo) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-right text-sm text-gray-600 shrink-0">
            <?php if ($whatsapp): ?>
                <p class="font-semibold">💬 <?= htmlspecialchars($whatsapp) ?></p>
            <?php endif; ?>
            <p><?= htmlspecialchars($sitio) ?></p>
            <p class="text-xs text-gray-400 mt-1"><?= date('d/m/Y') ?></p>
        </div>
    </header>

    <?php if (empty($productos)): ?>
        <p class="py-16 text-center text-gray-400">No hay productos con estas opciones.</p>
    <?php endif; ?>

    <!-- Productos, agrupados por categoría -->
    <?php foreach ($porCategoria as $categoria => $lista): ?>
        <?php if (count($porCategoria) > 1): ?>
            <h2 class="titulo-seccion text-sm font-bold uppercase tracking-widest text-gray-500 mt-8 mb-4 first:mt-0">
                <?= htmlspecialchars($categoria) ?>
            </h2>
        <?php endif; ?>

        <div class="grid <?= $clasesColumnas[$columnas] ?> gap-5">
            <?php foreach ($lista as $p): ?>
                <?php
                $idp   = (int) $p['id_producto'];
                $pct   = (float) ($p['descuento_pct'] ?? 0);
                $lista_ = (float) $p['precio_base'] * (1 + $ajuste / 100);
                $final = precio_con_descuento($lista_, $pct);

                // Talles: con "solo con stock", solo los disponibles; si no, todos (agotados tachados)
                $tallesProd = $talles[$idp] ?? [];
                if ($opciones['con_stock']) {
                    $tallesProd = array_filter($tallesProd, fn($t) => $t['disponible'] > 0);
                }
                ?>
                <article class="producto">
                    <div class="relative aspect-[3/4] bg-gray-100 rounded-xl overflow-hidden">
                        <?php if (!empty($p['foto_principal'])): ?>
                            <img src="<?= BASE_URL ?>/public/uploads/productos/<?= htmlspecialchars($p['foto_principal']) ?>"
                                 alt="" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-300 text-xs">Sin foto</div>
                        <?php endif; ?>

                        <?php if ($precios && $pct > 0): ?>
                            <span class="absolute top-2 right-2 bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                                -<?= pct_texto($pct) ?>%
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="mt-2">
                        <?php if (!empty($p['marca'])): ?>
                            <p class="text-[10px] uppercase tracking-wider text-gray-400"><?= htmlspecialchars($p['marca']) ?></p>
                        <?php endif; ?>
                        <p class="font-semibold text-sm leading-tight"><?= htmlspecialchars($p['nombre']) ?></p>

                        <?php if ($precios): ?>
                            <p class="mt-1">
                                <span class="font-bold"><?= $fmt($final) ?></span>
                                <?php if ($pct > 0): ?>
                                    <span class="text-xs text-gray-400 line-through ml-1"><?= $fmt($lista_) ?></span>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($tallesProd): ?>
                            <div class="flex flex-wrap gap-1 mt-1.5">
                                <?php foreach ($tallesProd as $t): ?>
                                    <span class="text-[10px] leading-none px-1.5 py-1 rounded border
                                                 <?= $t['disponible'] > 0 ? 'border-gray-300 text-gray-700' : 'border-gray-200 text-gray-300 line-through' ?>">
                                        <?= htmlspecialchars($t['nombre']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <!-- Pie -->
    <?php if (!empty($productos)): ?>
        <footer class="mt-10 pt-4 border-t text-center text-xs text-gray-400">
            <?php if ($precios): ?>Precios en pesos, sujetos a cambios sin previo aviso. <?php endif; ?>
            Consultá stock y talles por WhatsApp<?= $whatsapp ? ' al ' . htmlspecialchars($whatsapp) : '' ?>.
        </footer>
    <?php endif; ?>

</main>

</body>
</html>