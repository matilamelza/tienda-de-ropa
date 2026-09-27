<?php
$msg = $_SESSION['caja_msg'] ?? null;
unset($_SESSION['caja_msg']);

$input = 'w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring focus:ring-gray-200';
$pesos = fn($n) => '$' . number_format((float) $n, 2, ',', '.');
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Configuración de caja</h2>
        <p class="text-gray-500 text-sm">Dónde está la plata, cómo te pagan y en qué gastás.</p>
    </div>
    <a href="<?= BASE_URL ?>/admin/caja" class="self-start px-4 py-2 rounded-lg border bg-white text-gray-700">← Volver a Caja</a>
</div>

<?php if ($msg): ?>
    <div class="mb-6 px-4 py-3 rounded-xl text-sm border
        <?= $msg['tipo'] === 'ok' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
        <?= htmlspecialchars($msg['texto']) ?>
    </div>
<?php endif; ?>

<!-- ══ CAJAS ════════════════════════════════════════════════════ -->
<section class="bg-white rounded-lg shadow p-5 mb-6">
    <h3 class="font-bold text-gray-800">🏦 Cajas</h3>
    <p class="text-xs text-gray-400 mb-4">Cada lugar donde tenés plata. Una caja con movimientos no se borra: se desactiva.</p>

    <div class="hidden md:grid grid-cols-12 gap-2 px-1 mb-1 text-xs text-gray-400">
        <div class="col-span-5">Nombre</div>
        <div class="col-span-2">Orden</div>
        <div class="col-span-2 text-right">Saldo</div>
        <div class="col-span-1 text-center">Activa</div>
        <div class="col-span-2"></div>
    </div>

    <div class="space-y-2">
        <?php foreach ($cajas as $c): ?>
            <form method="POST" action="<?= BASE_URL ?>/admin/caja/config/caja"
                  class="grid grid-cols-12 gap-2 items-center p-1 rounded-lg hover:bg-gray-50">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $c['id_caja'] ?>">

                <input name="nombre" value="<?= htmlspecialchars($c['nombre']) ?>" required maxlength="60"
                       class="col-span-12 md:col-span-5 <?= $input ?>">
                <input type="number" name="orden" value="<?= (int) $c['orden'] ?>"
                       class="col-span-4 md:col-span-2 <?= $input ?>" title="Orden">
                <div class="col-span-4 md:col-span-2 text-right text-sm font-semibold <?= $c['saldo'] < 0 ? 'text-red-600' : 'text-gray-900' ?>">
                    <?= $pesos($c['saldo']) ?>
                </div>
                <label class="col-span-2 md:col-span-1 flex justify-center">
                    <input type="checkbox" name="activo" <?= $c['activo'] ? 'checked' : '' ?>>
                </label>
                <button class="col-span-2 md:col-span-2 text-sm px-3 py-2 rounded-lg border hover:bg-gray-100">Guardar</button>
            </form>
        <?php endforeach; ?>

        <!-- Nueva -->
        <form method="POST" action="<?= BASE_URL ?>/admin/caja/config/caja"
              class="grid grid-cols-12 gap-2 items-center p-1 border-t pt-3 mt-3">
            <?= csrf_field() ?>
            <input type="hidden" name="activo" value="1">
            <input name="nombre" placeholder="Nueva caja (ej: Caja chica)" required maxlength="60"
                   class="col-span-12 md:col-span-5 <?= $input ?>">
            <input type="number" name="orden" value="<?= count($cajas) + 1 ?>"
                   class="col-span-4 md:col-span-2 <?= $input ?>">
            <div class="hidden md:block md:col-span-3"></div>
            <button class="col-span-8 md:col-span-2 text-sm px-3 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">+ Agregar</button>
        </form>
    </div>
</section>

<!-- ══ MEDIOS DE PAGO ═══════════════════════════════════════════ -->
<section class="bg-white rounded-lg shadow p-5 mb-6">
    <h3 class="font-bold text-gray-800">💳 Medios de pago</h3>
    <p class="text-xs text-gray-400 mb-4">
        <strong>Comisión</strong>: lo que se queda Mercado Pago o el posnet (se descuenta de lo que entra a la caja).
        <strong>Ajuste</strong>: descuento o recargo al cliente (ej: <code>-10</code> = 10% off en efectivo, <code>15</code> = 15% de recargo en cuotas).
    </p>

    <div class="hidden md:grid grid-cols-12 gap-2 px-1 mb-1 text-xs text-gray-400">
        <div class="col-span-3">Nombre</div>
        <div class="col-span-3">Entra a la caja</div>
        <div class="col-span-2">Comisión %</div>
        <div class="col-span-2">Ajuste %</div>
        <div class="col-span-1 text-center">Activo</div>
        <div class="col-span-1"></div>
    </div>

    <div class="space-y-2">
        <?php foreach (array_merge($medios, [null]) as $m): ?>
            <?php $nuevo = $m === null; ?>
            <form method="POST" action="<?= BASE_URL ?>/admin/caja/config/medio"
                  class="grid grid-cols-12 gap-2 items-center p-1 rounded-lg <?= $nuevo ? 'border-t pt-3 mt-3' : 'hover:bg-gray-50' ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $nuevo ? 0 : (int) $m['id_medio'] ?>">
                <input type="hidden" name="orden" value="<?= $nuevo ? count($medios) + 1 : (int) $m['orden'] ?>">
                <?php if ($nuevo): ?><input type="hidden" name="activo" value="1"><?php endif; ?>

                <input name="nombre" value="<?= $nuevo ? '' : htmlspecialchars($m['nombre']) ?>" required maxlength="60"
                       placeholder="<?= $nuevo ? 'Nuevo medio (ej: Cuenta DNI)' : '' ?>"
                       class="col-span-12 md:col-span-3 <?= $input ?>">

                <select name="id_caja" required class="col-span-12 md:col-span-3 <?= $input ?> bg-white">
                    <?php foreach ($cajasActivas as $c): ?>
                        <option value="<?= (int) $c['id_caja'] ?>" <?= !$nuevo && (int) $m['id_caja'] === (int) $c['id_caja'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label class="col-span-6 md:col-span-2 relative">
                    <input type="number" step="0.01" min="0" max="99" name="comision_pct"
                           value="<?= $nuevo ? '0' : htmlspecialchars((string) $m['comision_pct']) ?>"
                           class="<?= $input ?> pr-7" title="Comisión %">
                    <span class="absolute right-3 top-2 text-sm text-gray-400">%</span>
                </label>

                <label class="col-span-6 md:col-span-2 relative">
                    <input type="number" step="0.01" min="-99" max="100" name="ajuste_pct"
                           value="<?= $nuevo ? '0' : htmlspecialchars((string) $m['ajuste_pct']) ?>"
                           class="<?= $input ?> pr-7" title="Ajuste %">
                    <span class="absolute right-3 top-2 text-sm text-gray-400">%</span>
                </label>

                <label class="col-span-4 md:col-span-1 flex justify-center">
                    <?php if (!$nuevo): ?>
                        <input type="checkbox" name="activo" <?= $m['activo'] ? 'checked' : '' ?>>
                    <?php endif; ?>
                </label>

                <button class="col-span-8 md:col-span-1 text-sm px-3 py-2 rounded-lg
                               <?= $nuevo ? 'bg-gray-900 text-white hover:bg-gray-800' : 'border hover:bg-gray-100' ?>">
                    <?= $nuevo ? '+ Agregar' : 'Guardar' ?>
                </button>
            </form>
        <?php endforeach; ?>
    </div>
</section>

<!-- ══ CATEGORÍAS ═══════════════════════════════════════════════ -->
<section class="bg-white rounded-lg shadow p-5">
    <h3 class="font-bold text-gray-800">🏷️ Categorías de gastos e ingresos</h3>
    <p class="text-xs text-gray-400 mb-4">Para agrupar los gastos e ingresos que no son ventas.</p>

    <div class="space-y-2">
        <?php foreach (array_merge($categorias, [null]) as $cat): ?>
            <?php $nuevo = $cat === null; ?>
            <form method="POST" action="<?= BASE_URL ?>/admin/caja/config/categoria"
                  class="grid grid-cols-12 gap-2 items-center p-1 rounded-lg <?= $nuevo ? 'border-t pt-3 mt-3' : 'hover:bg-gray-50' ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $nuevo ? 0 : (int) $cat['id_categoria_mov'] ?>">
                <?php if ($nuevo): ?><input type="hidden" name="activo" value="1"><?php endif; ?>

                <input name="nombre" value="<?= $nuevo ? '' : htmlspecialchars($cat['nombre']) ?>" required maxlength="60"
                       placeholder="<?= $nuevo ? 'Nueva categoría' : '' ?>"
                       class="col-span-12 md:col-span-5 <?= $input ?>">

                <select name="tipo" class="col-span-6 md:col-span-3 <?= $input ?> bg-white">
                    <option value="gasto"   <?= !$nuevo && $cat['tipo'] === 'gasto'   ? 'selected' : '' ?>>Gasto</option>
                    <option value="ingreso" <?= !$nuevo && $cat['tipo'] === 'ingreso' ? 'selected' : '' ?>>Ingreso</option>
                </select>

                <div class="col-span-6 md:col-span-2 text-xs text-gray-400 text-center">
                    <?= $nuevo ? '' : (int) $cat['cant_movimientos'] . ' mov.' ?>
                </div>

                <label class="col-span-4 md:col-span-1 flex justify-center">
                    <?php if (!$nuevo): ?>
                        <input type="checkbox" name="activo" <?= $cat['activo'] ? 'checked' : '' ?> title="Activa">
                    <?php endif; ?>
                </label>

                <button class="col-span-8 md:col-span-1 text-sm px-3 py-2 rounded-lg
                               <?= $nuevo ? 'bg-gray-900 text-white hover:bg-gray-800' : 'border hover:bg-gray-100' ?>">
                    <?= $nuevo ? '+' : 'Guardar' ?>
                </button>
            </form>
        <?php endforeach; ?>
    </div>
</section>