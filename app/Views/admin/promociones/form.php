<?php
$editando = !empty($promo);
$msg      = $_SESSION['promo_msg'] ?? null;
unset($_SESSION['promo_msg']);

$valorFecha = fn($f) => $f ? date('Y-m-d\TH:i', strtotime($f)) : '';
$input      = 'w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200';
?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>/admin/promociones<?= $editando ? '/ver?id=' . (int) $promo['id_promocion'] : '' ?>"
       class="text-sm text-gray-500 hover:text-gray-900">← <?= $editando ? 'Volver a la promoción' : 'Promociones' ?></a>
    <h2 class="text-2xl font-bold text-gray-800 mt-1"><?= $editando ? 'Editar promoción' : 'Nueva promoción' ?></h2>
</div>

<?php if ($msg): ?>
    <div class="mb-4 max-w-2xl px-4 py-3 rounded-xl text-sm border
        <?= $msg[0] === 'ok' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
        <?= htmlspecialchars($msg[1]) ?>
    </div>
<?php endif; ?>

<form action="<?= BASE_URL ?>/admin/promociones/guardar" method="POST"
      class="bg-white rounded-lg shadow p-6 space-y-5 max-w-2xl">
    <?= csrf_field() ?>
    <?php if ($editando): ?>
        <input type="hidden" name="id" value="<?= (int) $promo['id_promocion'] ?>">
    <?php endif; ?>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
        <input name="nombre" required maxlength="80" class="<?= $input ?>"
               value="<?= htmlspecialchars($promo['nombre'] ?? '') ?>" placeholder="Ej: Hot Sale 2026">
        <p class="text-xs text-gray-400 mt-1">Solo lo ves vos, para identificarla.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Descuento *</label>
            <div class="relative">
                <input type="number" name="pct" required min="1" max="99" step="0.01" class="<?= $input ?> pr-8"
                       value="<?= htmlspecialchars((string) ($promo['pct'] ?? '')) ?>" placeholder="20">
                <span class="absolute right-3 top-2 text-gray-400">%</span>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Etiqueta en la tienda</label>
            <input name="etiqueta" maxlength="30" class="<?= $input ?>"
                   value="<?= htmlspecialchars($promo['etiqueta'] ?? '') ?>" placeholder="Ej: HOT SALE">
            <p class="text-xs text-gray-400 mt-1">Aparece sobre la foto. Vacío = solo "-20%".</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Empieza</label>
            <input type="datetime-local" name="desde" class="<?= $input ?>" value="<?= $valorFecha($promo['desde'] ?? null) ?>">
            <p class="text-xs text-gray-400 mt-1">Vacío = empieza ya.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Termina</label>
            <input type="datetime-local" name="hasta" class="<?= $input ?>" value="<?= $valorFecha($promo['hasta'] ?? null) ?>">
            <p class="text-xs text-gray-400 mt-1">Vacío = sin fecha de fin (hasta que la pauses).</p>
        </div>
    </div>

    <label class="flex items-center gap-2">
        <input type="checkbox" name="activa" <?= !$editando || (int) $promo['activa'] === 1 ? 'checked' : '' ?>>
        <span class="text-sm">Activa <span class="text-gray-400">(destildala para pausarla sin borrarla)</span></span>
    </label>

    <div class="flex justify-end gap-2 pt-2">
        <button class="px-5 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">
            <?= $editando ? 'Guardar cambios' : 'Crear promoción' ?>
        </button>
    </div>
</form>