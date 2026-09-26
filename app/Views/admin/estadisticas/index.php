<?php
$num = fn($n) => number_format((float) $n, 0, ',', '.');

$badgeVariacion = function (?float $v): string {
    if ($v === null) {
        return '<span class="text-xs text-gray-400">—</span>';
    }
    $sube  = $v >= 0;
    $clase = $sube ? 'text-green-700 bg-green-50' : 'text-red-700 bg-red-50';
    return '<span class="text-xs font-semibold px-2 py-0.5 rounded-full ' . $clase . '">'
         . ($sube ? '▲' : '▼') . ' ' . number_format(abs($v), 0, ',', '.') . '%</span>';
};

/** Lista con barra proporcional al máximo */
$listaConBarras = function (array $filas, string $campoTexto, string $campoValor, string $sufijo = '') use ($num): string {
    if (empty($filas)) {
        return '<p class="text-sm text-gray-400 py-4 text-center">Sin datos en este período.</p>';
    }
    $max  = max(array_column($filas, $campoValor)) ?: 1;
    $html = '<ul class="space-y-3">';
    foreach ($filas as $f) {
        $pct   = round($f[$campoValor] / $max * 100);
        $html .= '<li>'
               . '<div class="flex justify-between text-sm mb-1 gap-3">'
               . '<span class="truncate text-gray-800">' . htmlspecialchars($f[$campoTexto]) . '</span>'
               . '<span class="shrink-0 text-gray-500">' . $num($f[$campoValor]) . $sufijo . '</span>'
               . '</div>'
               . '<div class="h-2 bg-gray-100 rounded-full overflow-hidden">'
               . '<div class="h-full bg-gray-900 rounded-full" style="width:' . $pct . '%"></div>'
               . '</div></li>';
    }
    return $html . '</ul>';
};

$origenesNombre = [
    'directo'      => '↗️ Directo / apps',
    'instagram'    => '📸 Instagram',
    'facebook'     => '👍 Facebook',
    'google'       => '🔎 Google',
    'whatsapp'     => '💬 WhatsApp',
    'tiktok'       => '🎵 TikTok',
    'mercadolibre' => '🛒 Mercado Libre',
    'bing'         => '🔎 Bing',
];
$origenesVista = array_map(function ($o) use ($origenesNombre) {
    $o['nombre'] = $origenesNombre[$o['origen']] ?? '🌐 ' . $o['origen'];
    return $o;
}, $origenes);

$totalDispositivos = $dispositivos['mobile'] + $dispositivos['desktop'];
$pctCelular        = $totalDispositivos > 0 ? round($dispositivos['mobile'] / $totalDispositivos * 100) : null;

$maxGrafico = max(array_column($grafico, 'total') ?: [0]);

$pasosEmbudo = [
    ['Entraron a la tienda', $embudo['entraron']],
    ['Vieron un producto',   $embudo['vieron_producto']],
    ['Llegaron al carrito',  $embudo['carrito']],
    ['Llegaron al checkout', $embudo['checkout']],
    ['Hicieron un pedido',   $embudo['pedidos']],
];
?>

<!-- ── Encabezado + período ─────────────────────────────────────── -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Estadísticas</h2>
        <p class="text-gray-500">Quién entra a la tienda y qué mira</p>
    </div>

    <div class="inline-flex bg-white border rounded-lg p-1 text-sm overflow-x-auto">
        <?php foreach ($periodos as $clave => $etiqueta): ?>
            <a href="<?= BASE_URL ?>/admin/estadisticas?periodo=<?= $clave ?>"
               class="px-3 py-1.5 rounded-md whitespace-nowrap <?= $periodo === $clave ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                <?= $etiqueta ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($resumen['visitantes'] === 0): ?>
    <div class="bg-white rounded-lg shadow p-10 text-center text-gray-500 mb-6">
        <p class="text-4xl mb-3">📈</p>
        <p class="font-medium">Todavía no hay visitas registradas en este período.</p>
        <p class="text-sm text-gray-400 mt-1">Las visitas del admin no se cuentan. Probá desde otro navegador o desde el celular.</p>
    </div>
<?php else: ?>

<!-- ── Números principales ──────────────────────────────────────── -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Visitantes</p>
            <?= $badgeVariacion($variaciones['visitantes']) ?>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $num($resumen['visitantes']) ?></p>
        <p class="text-xs text-gray-400 mt-1">personas distintas</p>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Páginas vistas</p>
            <?= $badgeVariacion($variaciones['vistas']) ?>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $num($resumen['vistas']) ?></p>
        <p class="text-xs text-gray-400 mt-1">
            <?= $resumen['visitantes'] > 0 ? number_format($resumen['vistas'] / $resumen['visitantes'], 1, ',', '.') : 0 ?> por visitante
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500 mb-2">Conversión</p>
        <p class="text-2xl md:text-3xl font-bold text-gray-900">
            <?= $conversion !== null ? number_format($conversion, 1, ',', '.') . '%' : '—' ?>
        </p>
        <p class="text-xs text-gray-400 mt-1">visitantes que hicieron un pedido</p>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500 mb-2">Desde el celular</p>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $pctCelular !== null ? $pctCelular . '%' : '—' ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= $num($dispositivos['desktop']) ?> desde compu</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <!-- ── Gráfico ──────────────────────────────────────────────── -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow p-5">
        <h3 class="font-bold text-gray-800 mb-4"><?= htmlspecialchars($graficoTitulo) ?></h3>

        <?php if ($maxGrafico > 0): ?>
            <div class="flex items-end gap-1 h-48">
                <?php foreach ($grafico as $dia): ?>
                    <?php $alto = $dia['total'] > 0 ? max(4, round($dia['total'] / $maxGrafico * 100)) : 0; ?>
                    <div class="flex-1 h-full flex flex-col justify-end group relative">
                        <div class="w-full rounded-t <?= $dia['total'] > 0 ? 'bg-gray-900 group-hover:bg-gray-700' : 'bg-gray-100' ?>"
                             style="height: <?= $dia['total'] > 0 ? $alto : 2 ?>%"></div>
                        <div class="hidden group-hover:block absolute bottom-full left-1/2 -translate-x-1/2 mb-2 z-10
                                    bg-gray-900 text-white text-xs rounded px-2 py-1 whitespace-nowrap">
                            <?= $dia['label'] ?>: <?= (int) $dia['total'] ?> visitante(s) · <?= (int) $dia['pedidos'] ?> página(s)
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php $paso = count($grafico) > 15 ? 5 : 1; ?>
            <div class="flex gap-1 mt-2">
                <?php foreach ($grafico as $i => $dia): ?>
                    <div class="flex-1 text-center text-[10px] text-gray-400"><?= ($i % $paso === 0) ? $dia['label'] : '' ?></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="h-48 flex items-center justify-center text-gray-400 text-sm">Sin visitas en estos días.</div>
        <?php endif; ?>
    </div>

    <!-- ── Embudo ───────────────────────────────────────────────── -->
    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-bold text-gray-800 mb-1">Camino a la compra</h3>
        <p class="text-xs text-gray-400 mb-4">Cuántos avanzan en cada paso</p>

        <ul class="space-y-3">
            <?php foreach ($pasosEmbudo as $i => [$texto, $valor]): ?>
                <?php
                $base = max(1, $embudo['entraron']);
                $pct  = round($valor / $base * 100);
                ?>
                <li>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-700"><?= $texto ?></span>
                        <span class="text-gray-500"><?= $num($valor) ?> <span class="text-xs text-gray-400">(<?= $pct ?>%)</span></span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full <?= $i === count($pasosEmbudo) - 1 ? 'bg-green-600' : 'bg-gray-900' ?>"
                             style="width: <?= max($valor > 0 ? 2 : 0, $pct) ?>%"></div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- ── Productos ─────────────────────────────────────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <div class="lg:col-span-2 bg-white rounded-lg shadow overflow-hidden">
        <div class="px-5 py-4 border-b">
            <h3 class="font-bold text-gray-800">Productos más vistos</h3>
        </div>

        <?php if (empty($topProductos)): ?>
            <p class="px-5 py-8 text-sm text-gray-400 text-center">Sin visitas a productos en este período.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="text-left px-5 py-3">Producto</th>
                            <th class="text-right px-5 py-3">Visitas</th>
                            <th class="text-right px-5 py-3">Personas</th>
                            <th class="text-right px-5 py-3">Vendidas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProductos as $p): ?>
                            <tr class="border-t">
                                <td class="px-5 py-3">
                                    <a href="<?= BASE_URL ?>/admin/productos/editar?id=<?= (int) $p['id_producto'] ?>"
                                       class="text-gray-900 hover:underline"><?= htmlspecialchars($p['nombre']) ?></a>
                                </td>
                                <td class="px-5 py-3 text-right"><?= $num($p['vistas']) ?></td>
                                <td class="px-5 py-3 text-right text-gray-500"><?= $num($p['visitantes']) ?></td>
                                <td class="px-5 py-3 text-right <?= (int) $p['vendidas'] > 0 ? 'text-green-700 font-semibold' : 'text-gray-300' ?>">
                                    <?= $num($p['vendidas']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-bold text-gray-800 mb-1">👀 Muy vistos, sin ventas</h3>
        <p class="text-xs text-gray-400 mb-4">Los miraron varias personas y nadie los compró. Revisá precio, fotos o talles.</p>

        <?php if (empty($vistosSinVenta)): ?>
            <p class="text-sm text-gray-500">✅ Ninguno por ahora.</p>
        <?php else: ?>
            <ul class="space-y-2">
                <?php foreach ($vistosSinVenta as $p): ?>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/productos/editar?id=<?= (int) $p['id_producto'] ?>"
                           class="flex items-center justify-between p-3 rounded-lg bg-orange-50 hover:bg-orange-100 text-sm">
                            <span class="truncate pr-2"><?= htmlspecialchars($p['nombre']) ?></span>
                            <span class="shrink-0 text-orange-700 font-semibold"><?= $num($p['visitantes']) ?> personas</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- ── Categorías, marcas y origen ──────────────────────────────── -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-bold text-gray-800 mb-4">Categorías más vistas</h3>
        <?= $listaConBarras($topCategorias, 'nombre', 'vistas') ?>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-bold text-gray-800 mb-4">Marcas más vistas</h3>
        <?= $listaConBarras($topMarcas, 'nombre', 'vistas') ?>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-bold text-gray-800 mb-1">De dónde llegan</h3>
        <p class="text-xs text-gray-400 mb-4">Personas que entraron desde cada lugar</p>
        <?= $listaConBarras($origenesVista, 'nombre', 'visitantes') ?>
    </div>
</div>

<!-- ── Búsquedas ─────────────────────────────────────────────────── -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-bold text-gray-800 mb-4">🔎 Lo más buscado</h3>
        <?= $listaConBarras($busquedas, 'termino', 'veces', ' veces') ?>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <h3 class="font-bold text-gray-800 mb-1">❌ Buscaron y no encontraron</h3>
        <p class="text-xs text-gray-400 mb-4">Lo que te piden y no tenés (o está cargado con otro nombre).</p>

        <?php if (empty($sinResultados)): ?>
            <p class="text-sm text-gray-500">✅ Todo lo que buscaron tuvo resultados.</p>
        <?php else: ?>
            <ul class="divide-y text-sm">
                <?php foreach ($sinResultados as $b): ?>
                    <li class="py-2 flex justify-between gap-3">
                        <span class="text-gray-800 truncate">"<?= htmlspecialchars($b['termino']) ?>"</span>
                        <span class="shrink-0 text-red-600"><?= $num($b['personas']) ?> persona(s)</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<p class="text-xs text-gray-400 mt-6">
    No se cuentan las visitas del admin logueado ni las de robots (Google, WhatsApp, Facebook). Las visitas se identifican
    con un código anónimo; no se guardan IPs ni datos personales.
</p>