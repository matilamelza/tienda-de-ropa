<?php $editando = !empty($producto); ?>

<?php if ($editando): ?>
    <?php $tabActiva = 'datos'; require __DIR__ . '/_tabs.php'; ?>
<?php else: ?>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Nuevo producto</h2>
            <p class="text-gray-500">Cargá la información principal del producto</p>
        </div>
        <a href="<?= BASE_URL ?>/admin/productos"
           class="px-4 py-2 rounded-lg border bg-white text-gray-700">
            Volver
        </a>
    </div>
<?php endif; ?>

<?php if (($_GET['ok'] ?? '') === 'actualizado'): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm max-w-2xl">
        ✓ Cambios guardados.
    </div>
<?php endif; ?>

<?php if (($_GET['ok'] ?? '') === 'duplicado'): ?>
    <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl px-4 py-3 mb-4 text-sm max-w-2xl">
        <p class="font-semibold mb-1">⧉ Copia creada</p>
        <p>Está <strong>inactiva</strong>, sin fotos y con stock en 0. Antes de activarla:</p>
        <ol class="list-decimal ml-5 mt-1 space-y-0.5">
            <li>Cambiale el <strong>nombre</strong> (sacale "(copia)") y revisá precio y descripción.</li>
            <li>Subí las <strong>fotos</strong> en la pestaña Fotos.</li>
            <li>Cargá el <strong>stock</strong> en la pestaña Variantes.</li>
            <li>Tildá <strong>Activo</strong> y guardá.</li>
        </ol>
    </div>
<?php endif; ?>

<?php if (($_GET['ok'] ?? '') === 'oferta'): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm max-w-2xl">
        🔥 Oferta creada. Ya se ve en la tienda.
    </div>
<?php endif; ?>
<?php if (($_GET['error'] ?? '') === 'oferta'): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm max-w-2xl">
        El descuento tiene que estar entre 1% y 99%.
    </div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] !== 'oferta'): ?>
     <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm max-w-2xl">
         Completá nombre, categoría y precio de venta.
     </div>
 <?php endif; ?>

<form action="<?= BASE_URL ?>/admin/productos/<?php echo $editando ? 'actualizar' : 'guardar'; ?>"
      method="POST"
      class="bg-white rounded-lg shadow p-6 space-y-5 max-w-2xl">

    <?= csrf_field() ?>

    <?php if ($editando): ?>
        <input type="hidden" name="id_producto" value="<?php echo (int) $producto['id_producto']; ?>">
    <?php endif; ?>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
        <input type="text"
               name="nombre"
               required
               value="<?php echo htmlspecialchars($producto['nombre'] ?? ''); ?>"
               class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
            <select name="id_categoria" required
                    class="w-full border rounded-lg px-3 py-2 bg-white">
                <option value="">Seleccionar</option>
                <?php while ($cat = $categorias->fetch_assoc()): ?>
                    <option value="<?php echo (int) $cat['id_categoria']; ?>"
                        <?php echo ($editando && $producto['id_categoria'] == $cat['id_categoria']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['nombre']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
            <select name="id_marca"
                    class="w-full border rounded-lg px-3 py-2 bg-white">
                <option value="">Sin marca</option>
                <?php while ($marca = $marcas->fetch_assoc()): ?>
                    <option value="<?php echo (int) $marca['id_marca']; ?>"
                        <?php echo ($editando && $producto['id_marca'] == $marca['id_marca']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($marca['nombre']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
        <textarea name="descripcion"
                  rows="4"
                  class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Precio de venta *</label>
            <input type="number"
                   name="precio_base"
                   id="precioVenta"
                   step="0.01"
                   min="0"
                   required
                   value="<?php echo htmlspecialchars((string) ($producto['precio_base'] ?? '')); ?>"
                   class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Precio de costo <span class="text-gray-400 font-normal">(solo lo ves vos)</span>
            </label>
            <input type="number"
                   name="precio_costo"
                   id="precioCosto"
                   step="0.01"
                   min="0"
                   value="<?php echo htmlspecialchars((string) ($producto['precio_costo'] ?? '')); ?>"
                   placeholder="Opcional"
                   class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200">
        </div>
    </div>

    <!-- Ganancia calculada en vivo -->
    <div id="cajaGanancia" class="hidden rounded-lg border px-4 py-3 text-sm">
        <div class="flex flex-wrap gap-x-6 gap-y-1">
            <span>Ganancia: <strong id="gananciaMonto"></strong></span>
            <span>Margen: <strong id="gananciaMargen"></strong></span>
            <span>Recargo sobre costo: <strong id="gananciaRecargo"></strong></span>
        </div>
    </div>

    <div class="flex gap-6">
        <label class="flex items-center gap-2">
            <input type="checkbox"
                   name="activo"
                   <?php echo (!$editando || $producto['activo'] == 1) ? 'checked' : ''; ?>>
            <span class="text-sm">Activo</span>
        </label>

        <label class="flex items-center gap-2">
            <input type="checkbox"
                   name="destacado"
                   <?php echo ($editando && $producto['destacado'] == 1) ? 'checked' : ''; ?>>
            <span class="text-sm">
                ★ Destacado
                <span class="block text-xs text-gray-400">Aparece en "Destacados" del inicio y primero en el catálogo</span>
            </span>
        </label>
    </div>

    <div class="flex justify-end gap-2 pt-2">
        <a href="<?= htmlspecialchars(url_listado_productos()) ?>"
           class="px-4 py-2 rounded-lg border text-gray-700">
            <?php echo $editando ? 'Volver al listado' : 'Cancelar'; ?>
        </a>

        <button type="submit"
                class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">
            <?php echo $editando ? 'Guardar cambios' : 'Crear producto'; ?>
        </button>
    </div>

</form>


<?php if ($editando): ?>
    <?php
    $precioBase = (float) $producto['precio_base'];
    $pctTxt     = fn($n) => rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',');
    ?>
    <div class="bg-white rounded-lg shadow p-6 mt-6 max-w-2xl">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-gray-800">🔥 Oferta</h3>
            <a href="<?= BASE_URL ?>/admin/promociones" class="text-xs text-gray-500 hover:text-gray-900">Ver todas las promociones →</a>
        </div>

        <?php if (!empty($promocionesProducto)): ?>
            <ul class="divide-y border rounded-lg mb-4 text-sm">
                <?php foreach ($promocionesProducto as $promo): ?>
                    <?php $pctEf = $promo['pct_propio'] !== null ? (float) $promo['pct_propio'] : (float) $promo['pct']; ?>
                    <li>
                        <a href="<?= BASE_URL ?>/admin/promociones/ver?id=<?= (int) $promo['id_promocion'] ?>"
                           class="flex items-center justify-between gap-3 px-3 py-2 hover:bg-gray-50">
                            <span class="min-w-0 flex items-center gap-2">
                                <span class="truncate"><?= htmlspecialchars($promo['nombre']) ?></span>
                                <?php require __DIR__ . '/../admin/promociones/_estado.php'; ?>
                            </span>
                            <span class="whitespace-nowrap">
                                -<?= $pctTxt($pctEf) ?>% →
                                <strong>$<?= number_format(precio_con_descuento($precioBase, $pctEf), 0, ',', '.') ?></strong>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/admin/promociones/oferta-rapida"
              class="grid grid-cols-2 sm:grid-cols-4 gap-3 items-end">
            <?= csrf_field() ?>
            <input type="hidden" name="id_producto" value="<?= (int) $producto['id_producto'] ?>">

            <label class="col-span-1">
                <span class="block text-xs text-gray-500 mb-1">Descuento</span>
                <span class="relative block">
                    <input type="number" name="pct" id="ofertaPct" min="1" max="99" step="0.01" required
                           class="w-full border rounded-lg px-3 py-2 pr-7" placeholder="20">
                    <span class="absolute right-3 top-2 text-gray-400">%</span>
                </span>
            </label>

            <label class="col-span-1">
                <span class="block text-xs text-gray-500 mb-1">o precio final</span>
                <input type="number" id="ofertaPrecio" min="1" step="0.01"
                       class="w-full border rounded-lg px-3 py-2" placeholder="$">
            </label>

            <label class="col-span-2 sm:col-span-1">
                <span class="block text-xs text-gray-500 mb-1">Hasta (opcional)</span>
                <input type="datetime-local" name="hasta" class="w-full border rounded-lg px-3 py-2">
            </label>

            <button class="col-span-2 sm:col-span-1 bg-gray-900 text-white rounded-lg px-3 py-2 hover:bg-gray-800">
                Poner en oferta
            </button>

            <p class="col-span-2 sm:col-span-4 text-xs text-gray-400">
                Precio actual: $<?= number_format($precioBase, 0, ',', '.') ?>.
                Escribí el % o el precio final y el otro se calcula solo.
            </p>
        </form>
    </div>


<?php if (!empty($producto['id_producto'])): ?>
<!-- ══ Guía de talles ══════════════════════════════════════════════════ -->
<form method="POST" action="<?= BASE_URL ?>/admin/productos/guia" class="bg-white rounded-lg shadow p-5 mt-6 max-w-3xl">
    <?= csrf_field() ?>
    <input type="hidden" name="id_producto" value="<?= (int) $producto['id_producto'] ?>">

    <div class="flex items-start justify-between gap-3 mb-4">
        <div>
            <h3 class="font-bold text-gray-800">Guía de talles</h3>
            <p class="text-xs text-gray-400">La tabla que ve el cliente al tocar "¿Qué talle soy?" en este producto.</p>
        </div>
        <a href="<?= BASE_URL ?>/admin/guias" class="text-sm text-gray-500 hover:text-gray-900 whitespace-nowrap">Ver guías →</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <label class="block">
            <span class="block text-gray-500 mb-1">Guía</span>
            <select name="id_guia" class="w-full border rounded-lg px-3 py-2 bg-white">
                <option value="">Sin guía</option>
                <?php foreach ($guias ?? [] as $g): ?>
                    <option value="<?= (int) $g['id_guia'] ?>" <?= (int) ($producto['id_guia'] ?? 0) === (int) $g['id_guia'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="block">
            <span class="block text-gray-500 mb-1">Cómo calza <span class="text-gray-400">(opcional)</span></span>
            <input name="nota_calce" id="notaCalce" maxlength="150" value="<?= htmlspecialchars($producto['nota_calce'] ?? '') ?>"
                   placeholder="Ej: Calza chico, te recomendamos un talle más" class="w-full border rounded-lg px-3 py-2">
            <span class="flex flex-wrap gap-1 mt-1.5">
                <?php foreach (['Calza chico, te recomendamos un talle más', 'Calza normal', 'Calza grande, te recomendamos un talle menos', 'Es holgado', 'Es entallado'] as $sug): ?>
                    <button type="button" onclick="document.getElementById('notaCalce').value = this.textContent"
                            class="text-xs px-2 py-0.5 rounded border text-gray-500 hover:bg-gray-50"><?= $sug ?></button>
                <?php endforeach; ?>
            </span>
        </label>
    </div>

    <?php if (empty($guias)): ?>
        <p class="text-xs text-gray-400 mt-3">Todavía no armaste ninguna guía. <a href="<?= BASE_URL ?>/admin/guias" class="underline">Crear una</a>.</p>
    <?php endif; ?>

    <div class="flex justify-end mt-4">
        <button class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">Guardar guía</button>
    </div>
</form>
<?php endif; ?>

    <script>
    (function () {
        const base   = <?= json_encode($precioBase) ?>;
        const pct    = document.getElementById('ofertaPct');
        const precio = document.getElementById('ofertaPrecio');

        pct.addEventListener('input', () => {
            const p = parseFloat(pct.value);
            precio.value = p > 0 && p < 100 ? Math.round(base * (1 - p / 100) * 100) / 100 : '';
        });

        precio.addEventListener('input', () => {
            const v = parseFloat(precio.value);
            pct.value = v > 0 && v < base ? Math.round((1 - v / base) * 10000) / 100 : '';
        });
    })();
    </script>
<?php endif; ?>

<script>
(function () {
    const venta   = document.getElementById('precioVenta');
    const costo   = document.getElementById('precioCosto');
    const caja    = document.getElementById('cajaGanancia');
    const monto   = document.getElementById('gananciaMonto');
    const margen  = document.getElementById('gananciaMargen');
    const recargo = document.getElementById('gananciaRecargo');

    const pesos = n => '$' + n.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const pct   = n => n.toLocaleString('es-AR', { maximumFractionDigits: 1 }) + '%';

    function calcular() {
        const v = parseFloat(venta.value);
        const c = parseFloat(costo.value);

        if (isNaN(v) || isNaN(c) || costo.value === '') {
            caja.classList.add('hidden');
            return;
        }

        const g = v - c;

        monto.textContent   = pesos(g);
        margen.textContent  = v > 0 ? pct(g / v * 100) : '—';
        recargo.textContent = c > 0 ? pct(g / c * 100) : '—';

        caja.className = 'rounded-lg border px-4 py-3 text-sm ' +
            (g < 0 ? 'bg-red-50 border-red-200 text-red-700'
                   : 'bg-green-50 border-green-200 text-green-800');
    }

    venta.addEventListener('input', calcular);
    costo.addEventListener('input', calcular);
    calcular();
})();
</script>