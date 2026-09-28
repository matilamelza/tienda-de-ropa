<?php
$msg = $_SESSION['promo_msg'] ?? null;
unset($_SESSION['promo_msg']);

$fecha = fn($f) => $f ? date('d/m/y H:i', strtotime($f)) : null;
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Promociones</h2>
        <p class="text-gray-500 text-sm">Descuentos por fechas: Hot Sale, Cyber Monday, liquidación…</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/promociones/nueva"
       class="self-start bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800">+ Nueva promoción</a>
</div>

<?php if ($msg): ?>
    <div class="mb-4 px-4 py-3 rounded-xl text-sm border
        <?= $msg[0] === 'ok' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
        <?= htmlspecialchars($msg[1]) ?>
    </div>
<?php endif; ?>

<?php if (empty($promociones)): ?>
    <div class="bg-white rounded-lg shadow p-10 text-center text-gray-500">
        <p class="text-4xl mb-3">🔥</p>
        <p class="font-medium">Todavía no hay promociones.</p>
        <p class="text-sm text-gray-400 mt-1">Creá una, sumale productos y elegí desde cuándo hasta cuándo dura.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow divide-y">
        <?php foreach ($promociones as $promo): ?>
            <a href="<?= BASE_URL ?>/admin/promociones/ver?id=<?= (int) $promo['id_promocion'] ?>"
               class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 px-5 py-4 hover:bg-gray-50">

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="font-semibold text-gray-900 truncate"><?= htmlspecialchars($promo['nombre']) ?></p>
                        <?php require __DIR__ . '/_estado.php'; ?>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">
                        <?= $promo['desde'] ? 'Desde ' . $fecha($promo['desde']) : 'Desde que se creó' ?>
                        · <?= $promo['hasta'] ? 'hasta ' . $fecha($promo['hasta']) : 'sin fecha de fin' ?>
                    </p>
                </div>

                <div class="flex items-center gap-4 text-sm shrink-0">
                    <span class="text-2xl font-bold text-gray-900">-<?= rtrim(rtrim(number_format($promo['pct'], 2, ',', ''), '0'), ',') ?>%</span>
                    <span class="text-gray-500 w-24 text-right"><?= (int) $promo['cant_productos'] ?> producto(s)</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>