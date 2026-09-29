<?php
$error = $error ?? null;
$cantCambios = $analisis ? count($analisis['productos']) + count($analisis['variantes']) : 0;
?>

<div class="mb-6">
    <a href="<?= htmlspecialchars(url_listado_productos()) ?>" class="text-sm text-gray-500 hover:text-gray-900">← Productos</a>
    <h2 class="text-2xl font-bold text-gray-800 mt-1">Importar desde Excel</h2>
    <p class="text-gray-500 text-sm">Actualizá precios, costos, stock y SKU de muchos productos a la vez.</p>
</div>

<?php if ($error): ?>
    <div class="mb-4 px-4 py-3 rounded-xl text-sm border bg-red-50 text-red-700 border-red-200 max-w-3xl">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if (!$analisis): ?>

    <!-- ── Copia de seguridad ──────────────────────────────────── -->
    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-5 mb-6 max-w-3xl flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="text-3xl">🛟</div>
        <div class="flex-1 text-sm">
            <p class="font-semibold text-yellow-900">Antes de importar, bajá una copia de seguridad</p>
            <p class="text-yellow-800 mt-0.5">
                Es el archivo con <strong>todos</strong> tus productos como están ahora. Guardalo en tu compu:
                si algo sale mal, lo subís acá mismo y todo vuelve a como estaba.
            </p>
        </div>
        <a href="<?= BASE_URL ?>/admin/productos/exportar"
           class="shrink-0 text-center px-4 py-2 rounded-lg bg-yellow-400 text-yellow-950 font-semibold hover:bg-yellow-300">
            ⬇️ Descargar copia de seguridad
        </a>
    </div>

    <!-- ── Instrucciones + subir ───────────────────────────────── -->
    <div class="bg-white rounded-lg shadow p-6 max-w-3xl">
        <ol class="space-y-3 text-sm text-gray-700 list-decimal ml-5 mb-6">
            <li>En <strong>Productos</strong>, tocá <strong>⬇️ Exportar</strong> (podés filtrar antes, por ejemplo, una marca).</li>
            <li>Abrí el archivo con Excel y cambiá lo que necesites: <strong>Precio, Precio especial, Costo, Stock, SKU o Variante activa</strong>.</li>
            <li><strong>No cambies</strong> las columnas <code>id_variante</code> ni <code>id_producto</code>. Las demás (producto, talle, color…) son solo para que te ubiques.</li>
            <li>Guardalo como <strong>CSV</strong> (Archivo → Guardar como → "CSV UTF-8").</li>
            <li>Subilo acá. Vas a ver <strong>qué cambia antes de guardar</strong>.</li>
        </ol>

        <form method="POST" action="<?= BASE_URL ?>/admin/productos/importar/previsualizar" enctype="multipart/form-data"
              class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
            <?= csrf_field() ?>
            <input type="file" name="archivo" accept=".csv,.txt" required
                   class="text-sm file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
            <button class="px-5 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">Revisar cambios →</button>
        </form>

        <p class="text-xs text-gray-400 mt-4">
            Tip: si solo querés actualizar el stock, podés borrar las otras columnas (dejá <code>id_variante</code>). Lo que no esté en el archivo no se toca.
        </p>
        <p class="text-xs text-gray-400 mt-1">
            ¿Algo salió mal en una importación? Subí acá la copia de seguridad y confirmá: los productos vuelven a como estaban.
        </p>
    </div>

<?php else: ?>

    <!-- ── Vista previa ────────────────────────────────────────── -->
    <div class="grid grid-cols-3 gap-4 mb-6 max-w-3xl">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Filas leídas</p>
            <p class="text-2xl font-bold"><?= (int) $analisis['filas'] ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Con cambios</p>
            <p class="text-2xl font-bold text-blue-700"><?= $cantCambios ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Errores</p>
            <p class="text-2xl font-bold <?= $analisis['errores'] ? 'text-red-600' : 'text-gray-900' ?>"><?= count($analisis['errores']) ?></p>
        </div>
    </div>

    <?php if ($analisis['errores']): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 max-w-3xl">
            <p class="font-semibold text-red-800 mb-2">⚠️ Estas filas no se van a aplicar:</p>
            <ul class="text-sm text-red-700 space-y-1 max-h-60 overflow-y-auto list-disc ml-5">
                <?php foreach ($analisis['errores'] as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="text-xs text-red-600 mt-2">El resto de los cambios se puede aplicar igual. Si preferís, corregí el archivo y subilo de nuevo.</p>
        </div>
    <?php endif; ?>

    <?php if ($cantCambios === 0): ?>
        <div class="bg-white rounded-lg shadow p-10 text-center text-gray-500 max-w-3xl">
            <p class="text-4xl mb-3">🤷</p>
            <p class="font-medium">No hay nada para cambiar.</p>
            <p class="text-sm text-gray-400 mt-1">Los datos del archivo son iguales a los que ya están cargados.</p>
            <a href="<?= BASE_URL ?>/admin/productos/importar" class="inline-block mt-4 text-sm underline">Subir otro archivo</a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow max-w-3xl">
            <div class="px-5 py-3 border-b bg-gray-50 text-sm text-gray-600">
                Archivo: <strong><?= htmlspecialchars($archivo) ?></strong> · Revisá los cambios antes de confirmar.
            </div>

            <div class="divide-y max-h-[60vh] overflow-y-auto">
                <?php foreach ($analisis['detalle'] as $prod): ?>
                    <div class="px-5 py-3 text-sm">
                        <p class="font-semibold text-gray-900"><?= htmlspecialchars($prod['nombre']) ?></p>

                        <?php foreach ($prod['producto'] ?? [] as [$campo, $antes, $despues]): ?>
                            <p class="ml-3 text-gray-700">
                                ✏️ <?= htmlspecialchars($campo) ?>:
                                <span class="text-gray-400 line-through"><?= htmlspecialchars((string) $antes) ?></span>
                                → <strong><?= htmlspecialchars((string) $despues) ?></strong>
                            </p>
                        <?php endforeach; ?>

                        <?php foreach ($prod['variantes'] ?? [] as $var): ?>
                            <p class="ml-3 text-gray-700">
                                <span class="text-gray-500"><?= htmlspecialchars($var['nombre']) ?>:</span>
                                <?php foreach ($var['cambios'] as $k => [$campo, $antes, $despues]): ?>
                                    <?= $k ? ' · ' : '' ?><?= htmlspecialchars($campo) ?>
                                    <span class="text-gray-400 line-through"><?= htmlspecialchars((string) $antes) ?></span>
                                    → <strong><?= htmlspecialchars((string) $despues) ?></strong>
                                <?php endforeach; ?>
                            </p>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="POST" action="<?= BASE_URL ?>/admin/productos/importar/aplicar"
                  class="px-5 py-4 border-t space-y-3"
                  onsubmit="return confirm('¿Aplicar <?= $cantCambios ?> cambio(s)? Se guardan todos juntos.')">
                <?= csrf_field() ?>

                <p class="text-xs text-yellow-800 bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2">
                    🛟 ¿Tenés una copia de cómo están los productos ahora?
                    Si no, <a href="<?= BASE_URL ?>/admin/productos/exportar" class="font-semibold underline">descargala acá</a>
                    antes de confirmar. Se baja aparte y no perdés esta vista previa.
                </p>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <a href="<?= BASE_URL ?>/admin/productos/importar" class="text-sm text-gray-500 hover:text-gray-900">Cancelar y subir otro</a>
                    <button class="px-5 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">
                        ✓ Confirmar <?= $cantCambios ?> cambio(s)
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

<?php endif; ?>