<?php
$diasNombre = [2 => 'Lun', 3 => 'Mar', 4 => 'Mié', 5 => 'Jue', 6 => 'Vie', 7 => 'Sáb', 1 => 'Dom'];
$diasLargo  = [1 => 'domingo', 2 => 'lunes', 3 => 'martes', 4 => 'miércoles', 5 => 'jueves', 6 => 'viernes', 7 => 'sábado'];

// Máximo del mapa y mejor momento
$maxMapa = 0;
$mejor   = null;
foreach ($mapa as $dia => $horas) {
    foreach ($horas as $hora => $v) {
        if ($v > $maxMapa) {
            $maxMapa = $v;
            $mejor   = [$dia, $hora];
        }
    }
}

$fmtDuracion = function (int $seg): string {
    if ($seg < 60) return $seg . ' s';
    $m = intdiv($seg, 60);
    return $m . ' min' . ($seg % 60 ? ' ' . ($seg % 60) . ' s' : '');
};

$tiposEntrada = [
    'inicio' => '🏠 Inicio', 'producto' => '👟', 'categoria' => '🗂️', 'marca' => '🏷️',
    'busqueda' => '🔎 Búsqueda:', 'carrito' => '🛒 Carrito', 'checkout' => '💳 Checkout', 'otra' => '📄 Catálogo filtrado',
];
$entradasVista = array_map(function ($e) use ($tiposEntrada) {
    $prefijo = $tiposEntrada[$e['tipo']] ?? $e['tipo'];
    $e['texto'] = in_array($e['tipo'], ['inicio', 'carrito', 'checkout', 'otra'], true)
        ? $prefijo
        : $prefijo . ' ' . ($e['nombre'] ?? '(eliminado)');
    return $e;
}, $entradas);

$origenesNombre = [
    'directo' => '↗️ Directo / apps', 'instagram' => '📸 Instagram', 'facebook' => '👍 Facebook', 'google' => '🔎 Google',
    'whatsapp' => '💬 WhatsApp', 'tiktok' => '🎵 TikTok', 'mercadolibre' => '🛒 Mercado Libre', 'bing' => '🔎 Bing',
];
$origenesVista = array_map(function ($o) use ($origenesNombre) {
    $o['nombre'] = $origenesNombre[$o['origen']] ?? '🌐 ' . $o['origen'];
    return $o;
}, $origenes);

$totalDisp  = $dispositivos['mobile'] + $dispositivos['desktop'];
$pctCelular = $totalDisp > 0 ? round($dispositivos['mobile'] / $totalDisp * 100) : 0;
$pctNuevos  = $nuevos['total'] > 0 ? round($nuevos['nuevos'] / $nuevos['total'] * 100) : 0;

$varSes = $sesionesAnt ? DashboardController::variacion($sesiones['sesiones'], $sesionesAnt['sesiones']) : null;
?>

<?php if ($nuevos['total'] === 0): ?>
    <div class="bg-white rounded-lg shadow p-10 text-center text-gray-500">
        <p class="text-4xl mb-3">🚦</p>
        <p class="font-medium">Todavía no hay visitas en este período.</p>
    </div>
<?php else: ?>

<!-- ── Números ───────────────────────────────────────────────────── -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between mb-2">
            <p class="text-sm text-gray-500">Visitas (sesiones)</p>
            <?= $badgeVariacion($varSes) ?>
        </div>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $num($sesiones['sesiones']) ?></p>
        <p class="text-xs text-gray-400 mt-1"><?= number_format($sesiones['paginas_por_sesion'], 1, ',', '.') ?> páginas por visita</p>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500 mb-2">Duración promedio</p>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $fmtDuracion($sesiones['duracion']) ?></p>
        <p class="text-xs text-gray-400 mt-1">de las visitas de más de 1 página</p>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500 mb-2">Rebote</p>
        <p class="text-2xl md:text-3xl font-bold <?= $sesiones['rebote_pct'] > 70 ? 'text-orange-600' : 'text-gray-900' ?>">
            <?= round($sesiones['rebote_pct']) ?>%
        </p>
        <p class="text-xs text-gray-400 mt-1">entraron, vieron 1 página y se fueron</p>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <p class="text-sm text-gray-500 mb-2">Nuevos</p>
        <p class="text-2xl md:text-3xl font-bold text-gray-900"><?= $pctNuevos ?>%</p>
        <p class="text-xs text-gray-400 mt-1"><?= $num($nuevos['nuevos']) ?> nuevos · <?= $num($nuevos['recurrentes']) ?> volvieron</p>
    </div>
</div>

<!-- ── Mapa de calor ─────────────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow p-5 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-4">
        <div>
            <h3 class="font-bold text-gray-800">🔥 ¿Cuándo entra la gente?</h3>
            <p class="text-xs text-gray-400">Visitantes por día y hora. Más oscuro = más gente.</p>
        </div>
        <?php if ($mejor): ?>
            <p class="text-sm bg-gray-900 text-white rounded-lg px-3 py-2">
                Mejor momento: <strong><?= $diasLargo[$mejor[0]] ?> a las <?= $mejor[1] ?> h</strong>
            </p>
        <?php endif; ?>
    </div>

    <div class="overflow-x-auto">
        <table class="text-[10px] border-separate" style="border-spacing: 2px">
            <thead>
                <tr>
                    <th></th>
                    <?php for ($hora = 0; $hora < 24; $hora++): ?>
                        <th class="font-normal text-gray-400 w-6"><?= $hora % 3 === 0 ? $hora : '' ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diasNombre as $dia => $nombreDia): ?>
                    <tr>
                        <th class="font-normal text-gray-500 text-right pr-2"><?= $nombreDia ?></th>
                        <?php for ($hora = 0; $hora < 24; $hora++): ?>
                            <?php
                            $v   = $mapa[$dia][$hora] ?? 0;
                            $int = $maxMapa > 0 ? $v / $maxMapa : 0;
                            ?>
                            <td class="w-6 h-6 rounded"
                                style="background: <?= $v > 0 ? 'rgba(17,24,39,' . round(0.08 + $int * 0.92, 2) . ')' : '#f3f4f6' ?>"
                                title="<?= $diasLargo[$dia] ?> <?= $hora ?> h: <?= $v ?> visitante(s)"></td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-xs text-gray-400 mt-3">Tip: publicá en Instagram un rato antes de los horarios más oscuros.</p>
</div>

<!-- ── Entradas, origen y dispositivos ──────────────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-lg shadow p-5">
        <h3 class="font-bold text-gray-800 mb-1">🚪 Por dónde entran</h3>
        <p class="text-xs text-gray-400 mb-4">La primera página que ven al llegar a la tienda.</p>
        <?= $listaConBarras($entradasVista, 'texto', 'entradas', ' entradas') ?>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-bold text-gray-800 mb-1">De dónde llegan</h3>
            <p class="text-xs text-gray-400 mb-4">Personas que entraron desde cada lugar</p>
            <?= $listaConBarras($origenesVista, 'nombre', 'visitantes') ?>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-bold text-gray-800 mb-3">📱 Celular vs. compu</h3>
            <div class="h-3 rounded-full overflow-hidden flex bg-gray-100">
                <div class="bg-gray-900" style="width: <?= $pctCelular ?>%"></div>
            </div>
            <div class="flex justify-between text-sm mt-2">
                <span>📱 Celular <strong><?= $pctCelular ?>%</strong></span>
                <span>💻 Compu <strong><?= 100 - $pctCelular ?>%</strong></span>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>