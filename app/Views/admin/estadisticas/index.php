<?php
// ── Helpers compartidos por todas las pestañas ────────────────────
$num   = fn($n) => number_format((float) $n, 0, ',', '.');
$pesos = fn($n) => '$' . number_format((float) $n, 0, ',', '.');

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
               . '<span class="truncate text-gray-800">' . htmlspecialchars((string) $f[$campoTexto]) . '</span>'
               . '<span class="shrink-0 text-gray-500">' . $num($f[$campoValor]) . $sufijo . '</span>'
               . '</div>'
               . '<div class="h-2 bg-gray-100 rounded-full overflow-hidden">'
               . '<div class="h-full bg-gray-900 rounded-full" style="width:' . $pct . '%"></div>'
               . '</div></li>';
    }
    return $html . '</ul>';
};

// URL conservando período/comparación, cambiando lo que se pase
$urlEst = function (array $cambios = []) use ($rango, $tab): string {
    $q = [
        'tab'      => $tab,
        'periodo'  => $rango['periodo'],
        'comparar' => $rango['comparar'] === 'anio' ? 'anio' : null,
    ];
    if ($rango['periodo'] === 'personalizado') {
        $q['desde'] = $rango['desde']->format('Y-m-d');
        $q['hasta'] = $rango['hasta']->modify('-1 day')->format('Y-m-d');
    }
    $q = array_filter(array_merge($q, $cambios), fn($v) => $v !== null && $v !== '');
    return BASE_URL . '/admin/estadisticas?' . http_build_query($q);
};
?>

<!-- ── Encabezado ────────────────────────────────────────────────── -->
<div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3 mb-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Estadísticas</h2>
        <p class="text-gray-500 text-sm">
            <?= htmlspecialchars($rango['etiqueta']) ?>
            <?php if ($rango['antDesde']): ?>
                · comparado con <?= $rango['comparar'] === 'anio' ? 'el mismo período del año pasado' : 'el período anterior' ?>
            <?php endif; ?>
        </p>
    </div>
</div>

<!-- ── Período ───────────────────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow p-3 mb-4 space-y-3">
    <div class="flex flex-wrap gap-1 text-sm">
        <?php foreach ($periodos as $clave => $txt): ?>
            <?php if ($clave === 'personalizado') continue; ?>
            <a href="<?= htmlspecialchars($urlEst(['periodo' => $clave, 'desde' => null, 'hasta' => null])) ?>"
               class="px-3 py-1.5 rounded-md whitespace-nowrap <?= $rango['periodo'] === $clave ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                <?= $txt ?>
            </a>
        <?php endforeach; ?>
    </div>

    <form method="GET" action="<?= BASE_URL ?>/admin/estadisticas" class="flex flex-wrap items-end gap-2 text-sm">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <input type="hidden" name="periodo" value="personalizado">
        <label>
            <span class="block text-xs text-gray-400 mb-1">Desde</span>
            <input type="date" name="desde" value="<?= $rango['desde']->format('Y-m-d') ?>" class="border rounded-lg px-2 py-1.5">
        </label>
        <label>
            <span class="block text-xs text-gray-400 mb-1">Hasta</span>
            <input type="date" name="hasta" value="<?= $rango['hasta']->modify('-1 day')->format('Y-m-d') ?>" class="border rounded-lg px-2 py-1.5">
        </label>
        <label>
            <span class="block text-xs text-gray-400 mb-1">Comparar con</span>
            <select name="comparar" class="border rounded-lg px-2 py-1.5 bg-white">
                <option value="">Período anterior</option>
                <option value="anio" <?= $rango['comparar'] === 'anio' ? 'selected' : '' ?>>Mismo período del año pasado</option>
            </select>
        </label>
        <button class="px-3 py-1.5 rounded-lg <?= $rango['periodo'] === 'personalizado' ? 'bg-gray-900 text-white' : 'border hover:bg-gray-50' ?>">
            Aplicar
        </button>
    </form>
</div>

<!-- ── Pestañas ──────────────────────────────────────────────────── -->
<div class="flex gap-1 mb-6 border-b overflow-x-auto">
    <?php foreach ($pestanas as $clave => $txt): ?>
        <a href="<?= htmlspecialchars($urlEst(['tab' => $clave])) ?>"
           class="px-4 py-2 text-sm whitespace-nowrap border-b-2 -mb-px
                  <?= $tab === $clave ? 'border-gray-900 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-900' ?>">
            <?= $txt ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- ── Contenido de la pestaña ───────────────────────────────────── -->
<?php
$archivoTab = __DIR__ . '/_' . $tab . '.php';
if (is_file($archivoTab)) {
    require $archivoTab;
} else {
    require __DIR__ . '/_pronto.php';
}
?>

<p class="text-xs text-gray-400 mt-8">
    No se cuentan las visitas del admin logueado ni las de robots (Google, WhatsApp, Facebook). Los visitantes se identifican
    con un código anónimo; no se guardan IPs ni datos personales.
</p>