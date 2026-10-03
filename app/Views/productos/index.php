<?php
$hayFiltros = $filtros['q'] !== '' || $filtros['categoria'] > 0 || $filtros['estado'] !== '' || $filtros['problema'] !== '' || ($filtros['guia'] ?? 0) > 0;

$url = function (array $cambios = []) use ($filtros): string {
    $q = array_filter(array_merge($filtros, $cambios), fn($v) => $v !== '' && $v !== 0 && $v !== null);
    return BASE_URL . '/admin/productos' . ($q ? '?' . http_build_query($q) : '');
};

$pesos = fn($n) => '$' . number_format((float) $n, 2, ',', '.');
$num   = fn($n) => number_format((float) $n, 0, ',', '.');

$flash = $_SESSION['productos_msg'] ?? null;
unset($_SESSION['productos_msg']);

/** Chips de talles: los agotados tachados. */
$chipsTalles = function (int $idProducto) use ($talles): string {
    if (empty($talles[$idProducto])) {
        return '';
    }
    $html = '<div class="flex flex-wrap gap-1 mt-1">';
    foreach ($talles[$idProducto] as $t) {
        $clase = $t['disponible'] > 0
            ? 'bg-gray-100 text-gray-700'
            : 'bg-red-50 text-red-300 line-through';
        $titulo = $t['disponible'] > 0 ? $t['disponible'] . ' disponible(s)' : 'Agotado';
        $html  .= '<span class="px-1.5 py-0.5 rounded text-[11px] leading-none ' . $clase . '" title="' . $titulo . '">'
                . htmlspecialchars($t['nombre']) . '</span>';
    }
    return $html . '</div>';
};

/** Íconos de aviso al lado del nombre. */
$avisos = function (array $p): string {
    $a = [];
    if (empty($p['foto_principal']))      $a[] = '<span title="Sin foto">🖼️</span>';
    if ($p['precio_costo'] === null)       $a[] = '<span title="Sin precio de costo">💲</span>';
    if ((int) $p['stock_disponible'] <= 0) $a[] = '<span title="Agotado">⛔</span>';
    return $a ? '<span class="ml-1 text-xs opacity-80">' . implode(' ', $a) . '</span>' : '';
};

/** Botón Activo/Inactivo que se cambia con un toque. */
$botonActivo = function (array $p): string {
    $on = (int) $p['activo'] === 1;
    return '<button type="button" class="btn-toggle px-2 py-1 text-xs rounded whitespace-nowrap '
         . ($on ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') . '"'
         . ' data-id="' . (int) $p['id_producto'] . '" data-campo="activo" data-valor="' . ($on ? 1 : 0) . '"'
         . ' title="Tocá para ' . ($on ? 'desactivar' : 'activar') . '">'
         . ($on ? 'Activo' : 'Inactivo') . '</button>';
};

/** Estrella de destacado que se cambia con un toque. */
$botonDestacado = function (array $p): string {
    $on = (int) $p['destacado'] === 1;
    return '<button type="button" class="btn-toggle text-lg leading-none ' . ($on ? 'text-yellow-500' : 'text-gray-300 hover:text-yellow-400') . '"'
         . ' data-id="' . (int) $p['id_producto'] . '" data-campo="destacado" data-valor="' . ($on ? 1 : 0) . '"'
         . ' title="' . ($on ? 'Destacado (tocá para quitar)' : 'Tocá para destacar') . '">'
         . ($on ? '★' : '☆') . '</button>';
};

/** Línea de actividad de los últimos 30 días. */
$actividad = function (array $p) use ($num): string {
    $vistas = (int) $p['vistas_30d'];
    $ventas = (int) $p['vendidos_30d'];
    if ($vistas === 0 && $ventas === 0) {
        return '';
    }
    return '<p class="text-[11px] text-gray-400 mt-1">30 días: ' . $num($vistas) . ' vistas · ' . $num($ventas) . ' vendidos</p>';
};
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Productos</h2>
        <p class="text-gray-500 text-sm"><?= (int) $total ?> producto<?= $total !== 1 ? 's' : '' ?><?= $hayFiltros ? ' con estos filtros' : '' ?></p>
    </div>

    <div class="flex gap-2 self-start sm:self-auto">
        <a href="<?= htmlspecialchars(BASE_URL . '/admin/productos/exportar?' . http_build_query(array_filter($filtros, fn($v) => $v !== '' && $v !== 0))) ?>"
           class="px-4 py-2 rounded-lg border bg-white text-gray-700 hover:bg-gray-50"
           title="Descargar los productos de este filtro en un archivo para Excel">
            ⬇️ Exportar
        </a>
        <a href="<?= BASE_URL ?>/admin/productos/importar"
           class="px-4 py-2 rounded-lg border bg-white text-gray-700 hover:bg-gray-50"
           title="Subir un archivo editado en Excel para actualizar precios, stock, etc.">
            ⬆️ Importar
        </a>
        <a href="<?= BASE_URL ?>/admin/productos/crear"
           class="bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-800">
            + Nuevo producto
        </a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="mb-4 px-4 py-3 rounded-xl text-sm border
        <?= $flash[0] === 'ok' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
        <?= htmlspecialchars($flash[1]) ?>
    </div>
<?php endif; ?>

<!-- ── Filtros y orden ─────────────────────────────────────── -->
<form method="GET" action="<?= BASE_URL ?>/admin/productos" id="formFiltros"
      class="bg-white rounded-lg shadow p-3 mb-4 grid grid-cols-2 md:grid-cols-6 gap-2">

          <?php if (($filtros['guia'] ?? 0) > 0): ?>
        <input type="hidden" name="guia" value="<?= (int) $filtros['guia'] ?>">
        <p class="col-span-2 md:col-span-6 text-sm text-gray-600">
            📏 Productos con la guía <strong><?= htmlspecialchars(array_column($guias, 'nombre', 'id_guia')[$filtros['guia']] ?? '') ?></strong>
            · <a href="<?= $url(['guia' => 0, 'pagina' => null]) ?>" class="underline">ver todos</a>
        </p>
    <?php endif; ?>

    <input type="text" name="q" value="<?= htmlspecialchars($filtros['q']) ?>"
           placeholder="Buscar por nombre o marca…"
           class="col-span-2 border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-200">

    <select name="categoria" class="auto-filtro border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">Todas las categorías</option>
        <?php foreach ($categorias as $cat): ?>
            <option value="<?= (int) $cat['id_categoria'] ?>" <?= $filtros['categoria'] === (int) $cat['id_categoria'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['nombre']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="estado" class="auto-filtro border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">Activos e inactivos</option>
        <option value="activos"   <?= $filtros['estado'] === 'activos'   ? 'selected' : '' ?>>Solo activos</option>
        <option value="inactivos" <?= $filtros['estado'] === 'inactivos' ? 'selected' : '' ?>>Solo inactivos</option>
    </select>

    <select name="problema" class="auto-filtro border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">Sin filtro de problemas</option>
        <option value="agotados"  <?= $filtros['problema'] === 'agotados'  ? 'selected' : '' ?>>⛔ Agotados</option>
        <option value="faltantes" <?= $filtros['problema'] === 'faltantes' ? 'selected' : '' ?>>📦 Con talles agotados</option>
        <option value="sin_foto"  <?= $filtros['problema'] === 'sin_foto'  ? 'selected' : '' ?>>🖼️ Sin foto</option>
        <option value="sin_costo" <?= $filtros['problema'] === 'sin_costo' ? 'selected' : '' ?>>💲 Sin costo</option>
        <option value="sin_guia"  <?= $filtros['problema'] === 'sin_guia'  ? 'selected' : '' ?>>📏 Sin guía de talles</option>
    </select>

    <select name="orden" class="auto-filtro border rounded-lg px-3 py-2 text-sm bg-white">
        <?php foreach (Producto::ORDENES as $clave => [$texto]): ?>
            <option value="<?= $clave === 'recientes' ? '' : $clave ?>" <?= ($filtros['orden'] ?: 'recientes') === $clave ? 'selected' : '' ?>>
                <?= htmlspecialchars($texto) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php if ($hayFiltros): ?>
        <a href="<?= $url(['q' => '', 'categoria' => 0, 'estado' => '', 'problema' => '', 'guia' => 0, 'pagina' => null]) ?>"
           class="col-span-2 md:col-span-6 text-center text-sm text-gray-500 hover:text-red-600">
            ✕ Limpiar filtros
        </a>
    <?php endif; ?>
</form>

<!-- ── Formulario de acciones masivas ──────────────────────── -->
<form id="formMasivo" method="POST" action="<?= BASE_URL ?>/admin/productos/masivo">
    <?= csrf_field() ?>
    <input type="hidden" name="accion" id="accionMasiva">
    <input type="hidden" name="todos_filtro" id="todosFiltro" value="">
    <?php foreach ($filtros as $k => $v): ?>
        <input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars((string) $v) ?>">
    <?php endforeach; ?>
    <div id="idsMasivo"></div>

    <div id="barraAcciones" class="hidden sticky top-14 lg:top-0 z-20 bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 text-sm">
        <div class="flex flex-wrap items-center gap-2">
            <span class="font-medium text-blue-900 mr-1"><span id="cantSel">0</span> seleccionado(s)</span>

            <button type="button" id="btnTodosFiltro" onclick="seleccionarTodoElFiltro()"
                    class="hidden text-blue-700 underline mr-2">
                Seleccionar los <?= (int) $total ?> productos del filtro
            </button>

            <button type="button" onclick="accion('activar')"          class="px-2 py-1 rounded border bg-white hover:bg-gray-50">Activar</button>
            <button type="button" onclick="accion('desactivar')"       class="px-2 py-1 rounded border bg-white hover:bg-gray-50">Desactivar</button>
            <button type="button" onclick="accion('destacar')"         class="px-2 py-1 rounded border bg-white hover:bg-gray-50">★ Destacar</button>
            <button type="button" onclick="accion('quitar_destacado')" class="px-2 py-1 rounded border bg-white hover:bg-gray-50">Quitar ★</button>

            <span class="flex items-center gap-1">
                <select name="id_categoria" id="accCategoria" class="border rounded px-2 py-1 bg-white">
                    <option value="">Categoría…</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= (int) $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" onclick="accion('categoria', 'accCategoria')" class="px-2 py-1 rounded border bg-white hover:bg-gray-50">Cambiar</button>
            </span>

            <span class="flex items-center gap-1">
                <select name="id_marca" id="accMarca" class="border rounded px-2 py-1 bg-white">
                    <option value="">Marca…</option>
                    <option value="0">Sin marca</option>
                    <?php foreach ($marcas as $m): ?>
                        <option value="<?= (int) $m['id_marca'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" onclick="accion('marca', 'accMarca')" class="px-2 py-1 rounded border bg-white hover:bg-gray-50">Cambiar</button>
            </span>

            <?php if (!empty($guias)): ?>
                <span class="flex items-center gap-1">
                    <select name="id_guia" id="accGuia" class="border rounded px-2 py-1 bg-white">
                        <option value="">Guía de talles…</option>
                        <option value="0">Sin guía</option>
                        <?php foreach ($guias as $g): ?>
                            <option value="<?= (int) $g['id_guia'] ?>"><?= htmlspecialchars($g['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" onclick="accion('guia', 'accGuia')" class="px-2 py-1 rounded border bg-white hover:bg-gray-50">📏 Asignar</button>
                </span>
            <?php endif; ?>

            <?php if (!empty($promociones)): ?>
                <span class="flex items-center gap-1">
                    <select name="id_promocion" id="accPromocion" class="border rounded px-2 py-1 bg-white">
                        <option value="">Promoción…</option>
                        <?php foreach ($promociones as $pr): ?>
                            <option value="<?= (int) $pr['id_promocion'] ?>">
                                <?= htmlspecialchars($pr['nombre']) ?> (-<?= rtrim(rtrim(number_format($pr['pct'], 2, ',', ''), '0'), ',') ?>%)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" onclick="accion('promocion', 'accPromocion')" class="px-2 py-1 rounded border bg-white hover:bg-gray-50">🔥 Agregar</button>
                </span>
            <?php endif; ?>

            <button type="button" onclick="catalogoSeleccionados()" class="px-2 py-1 rounded border bg-white hover:bg-gray-50">📄 Catálogo</button>
            <button type="button" onclick="togglePrecios()" class="px-2 py-1 rounded border bg-white hover:bg-gray-50 font-medium">💲 Precios</button>
            <button type="button" onclick="accion('eliminar')" class="px-2 py-1 rounded border border-red-200 bg-white text-red-600 hover:bg-red-50">Eliminar</button>
        </div>

        <div id="panelPrecios" class="hidden mt-3 pt-3 border-t border-blue-200">
            <div class="flex flex-wrap items-end gap-3">
                <label>
                    <span class="block text-xs text-gray-600 mb-1">Acción</span>
                    <select name="direccion" id="precDireccion" class="border rounded px-2 py-1.5 bg-white">
                        <option value="subir">Aumentar</option>
                        <option value="bajar">Bajar</option>
                    </select>
                </label>
                <label>
                    <span class="block text-xs text-gray-600 mb-1">Porcentaje</span>
                    <span class="relative inline-block">
                        <input type="number" name="pct" id="precPct" min="0" max="500" step="0.01" placeholder="10"
                               class="w-24 border rounded px-2 py-1.5 pr-6">
                        <span class="absolute right-2 top-1.5 text-gray-400">%</span>
                    </span>
                </label>
                <label>
                    <span class="block text-xs text-gray-600 mb-1">Redondear</span>
                    <select name="redondeo" id="precRedondeo" class="border rounded px-2 py-1.5 bg-white">
                        <option value="0">Sin redondear</option>
                        <option value="10">A $10</option>
                        <option value="100">A $100</option>
                        <option value="500" selected>A $500</option>
                        <option value="1000">A $1.000</option>
                    </select>
                </label>
                <label class="flex items-center gap-1 pb-1.5">
                    <input type="checkbox" name="con_costo" value="1" id="precCosto">
                    <span>También el costo</span>
                </label>
                <label class="flex items-center gap-1 pb-1.5">
                    <input type="checkbox" name="con_variantes" value="1" checked>
                    <span>También precios especiales de variantes</span>
                </label>
                <button type="button" onclick="aplicarPrecios()"
                        class="px-4 py-1.5 rounded bg-gray-900 text-white hover:bg-gray-800">Aplicar</button>
            </div>

            <div id="previewPrecios" class="hidden mt-3 bg-white border rounded-lg max-h-56 overflow-y-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 sticky top-0">
                        <tr>
                            <th class="text-left px-3 py-2">Producto</th>
                            <th class="text-right px-3 py-2">Precio actual</th>
                            <th class="text-right px-3 py-2">Precio nuevo</th>
                            <th class="text-right px-3 py-2" id="thCosto">Costo nuevo</th>
                        </tr>
                    </thead>
                    <tbody id="previewPreciosBody"></tbody>
                </table>
            </div>
            <p id="notaPreview" class="hidden text-xs text-gray-500 mt-2"></p>
        </div>
    </div>
</form>

<?php if (empty($productos)): ?>
    <div class="bg-white rounded-lg shadow px-4 py-10 text-center text-gray-400 text-sm">
        <?= $hayFiltros ? '✅ No hay productos con estos filtros.' : 'Todavía no hay productos cargados.' ?>
    </div>
<?php else: ?>

    <!-- ── Mobile: tarjetas ─────────────────────────────────── -->
    <div class="md:hidden space-y-3">
        <label class="flex items-center gap-2 text-sm text-gray-500 px-1">
            <input type="checkbox" class="sel-todos"> Seleccionar todos los de esta página
        </label>

        <?php foreach ($productos as $p): ?>
            <?php $idp = (int) $p['id_producto']; ?>
            <div class="bg-white rounded-lg shadow p-3 cursor-pointer" data-abrir-producto="<?= $idp ?>">
                <div class="flex gap-3">
                    <input type="checkbox" class="sel-prod mt-1 shrink-0" value="<?= $idp ?>"
                           data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                           data-precio="<?= (float) $p['precio_base'] ?>"
                           data-costo="<?= $p['precio_costo'] !== null ? (float) $p['precio_costo'] : '' ?>">

                    <div class="w-16 h-20 shrink-0 rounded bg-gray-100 overflow-hidden">
                        <?php if ($p['foto_principal']): ?>
                            <img src="<?= BASE_URL ?>/public/uploads/productos/<?= htmlspecialchars($p['foto_principal']) ?>"
                                 alt="" loading="lazy" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-300 text-xs">Sin foto</div>
                        <?php endif; ?>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-semibold text-gray-900 leading-tight">
                                <?= htmlspecialchars($p['nombre']) ?><?= $avisos($p) ?>
                            </p>
                            <?= $botonDestacado($p) ?>
                        </div>
                        <p class="text-xs text-gray-400 truncate">
                            <?= htmlspecialchars($p['categoria']) ?><?= $p['marca'] ? ' · ' . htmlspecialchars($p['marca']) : '' ?>
                        </p>
                        <?= $chipsTalles($idp) ?>
                        <div class="flex items-center justify-between mt-2 text-sm">
                            <span class="font-bold"><?= $pesos($p['precio_base']) ?></span>
                            <?= $botonActivo($p) ?>
                        </div>
                        <?= $actividad($p) ?>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-3 pt-3 border-t text-sm">
                    <a href="<?= BASE_URL ?>/admin/productos/editar?id=<?= $idp ?>" class="text-gray-700 font-medium">Editar</a>
                    <a href="<?= BASE_URL ?>/admin/productos/variantes?id=<?= $idp ?>" class="text-blue-600">Variantes</a>
                    <a href="<?= BASE_URL ?>/admin/productos/fotos?id=<?= $idp ?>" class="text-indigo-600">Fotos</a>
                    <?php if ((int) $p['activo'] === 1): ?>
                        <a href="<?= BASE_URL ?>/producto/<?= htmlspecialchars($p['slug']) ?>" target="_blank" class="text-gray-400">↗ Ver</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Desktop: tabla ───────────────────────────────────── -->
    <div class="hidden md:block bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-3 py-3 w-8"><input type="checkbox" class="sel-todos" title="Seleccionar todos los de esta página"></th>
                        <th class="px-2 py-3 w-6"></th>
                        <th class="text-left px-3 py-3">Producto</th>
                        <th class="text-right px-3 py-3">Precio</th>
                        <th class="text-right px-3 py-3">Costo</th>
                        <th class="text-right px-3 py-3">Ganancia</th>
                        <th class="text-right px-3 py-3">Stock</th>
                        <th class="text-center px-3 py-3">Estado</th>
                        <th class="text-right px-3 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p): ?>
                        <?php $idp = (int) $p['id_producto']; ?>
                        <tr class="border-t hover:bg-gray-50 align-top cursor-pointer" data-abrir-producto="<?= $idp ?>">
                            <td class="px-3 py-3">
                                <input type="checkbox" class="sel-prod" value="<?= $idp ?>"
                                       data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                                       data-precio="<?= (float) $p['precio_base'] ?>"
                                       data-costo="<?= $p['precio_costo'] !== null ? (float) $p['precio_costo'] : '' ?>">
                            </td>

                            <td class="px-2 py-3"><?= $botonDestacado($p) ?></td>

                            <td class="px-3 py-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-12 shrink-0 rounded bg-gray-100 overflow-hidden">
                                        <?php if ($p['foto_principal']): ?>
                                            <img src="<?= BASE_URL ?>/public/uploads/productos/<?= htmlspecialchars($p['foto_principal']) ?>"
                                                 alt="" loading="lazy" class="w-full h-full object-cover">
                                        <?php endif; ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-900"><?= htmlspecialchars($p['nombre']) ?><?= $avisos($p) ?></p>
                                        <p class="text-xs text-gray-400">
                                            <?= htmlspecialchars($p['categoria']) ?><?= $p['marca'] ? ' · ' . htmlspecialchars($p['marca']) : '' ?>
                                        </p>
                                        <?= $chipsTalles($idp) ?>
                                        <?= $actividad($p) ?>
                                    </div>
                                </div>
                            </td>

                            <td class="px-3 py-3 text-right whitespace-nowrap"><?= $pesos($p['precio_base']) ?></td>

                            <td class="px-3 py-3 text-right text-gray-500 whitespace-nowrap">
                                <?= $p['precio_costo'] !== null ? $pesos($p['precio_costo']) : '—' ?>
                            </td>

                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                <?php if ($p['precio_costo'] !== null): ?>
                                    <?php
                                    $ganancia = $p['precio_base'] - $p['precio_costo'];
                                    $margen   = $p['precio_base'] > 0 ? $ganancia / $p['precio_base'] * 100 : 0;
                                    ?>
                                    <span class="font-semibold <?= $ganancia < 0 ? 'text-red-600' : 'text-green-700' ?>"><?= $pesos($ganancia) ?></span>
                                    <span class="block text-xs text-gray-400"><?= number_format($margen, 1, ',', '.') ?>% margen</span>
                                <?php else: ?>
                                    <span class="text-gray-300">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                <span class="<?= $p['stock_disponible'] <= 0 ? 'text-red-600 font-semibold' : '' ?>"><?= (int) $p['stock_disponible'] ?></span>
                                <span class="block text-xs text-gray-400"><?= (int) $p['cant_variantes'] ?> variante(s)</span>
                            </td>

                            <td class="px-3 py-3 text-center"><?= $botonActivo($p) ?></td>

                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-3">
                                    <a href="<?= BASE_URL ?>/admin/productos/editar?id=<?= $idp ?>" class="text-gray-600 hover:text-gray-900">Editar</a>
                                    <a href="<?= BASE_URL ?>/admin/productos/variantes?id=<?= $idp ?>" class="text-blue-600 hover:text-blue-800">Variantes</a>
                                    <a href="<?= BASE_URL ?>/admin/productos/fotos?id=<?= $idp ?>" class="text-indigo-600 hover:text-indigo-800">Fotos</a>
                                    <?= boton_eliminar(
                                        BASE_URL . '/admin/productos/duplicar',
                                        ['id' => $idp],
                                        '¿Duplicar este producto? La copia queda inactiva, sin fotos y con stock en 0.',
                                        'Duplicar',
                                        'text-gray-500 hover:text-gray-900'
                                    ) ?>
                                    <?php if ((int) $p['activo'] === 1): ?>
                                        <a href="<?= BASE_URL ?>/producto/<?= htmlspecialchars($p['slug']) ?>" target="_blank"
                                           class="text-gray-400 hover:text-gray-900" title="Ver en la tienda">↗</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<!-- ── Paginación ───────────────────────────────────────────── -->
<?php if ($totalPaginas > 1): ?>
    <?php
    $inicio = max(1, $pagina - 2);
    $fin    = min($totalPaginas, $pagina + 2);
    ?>
    <div class="mt-6 flex flex-wrap items-center justify-center gap-1 text-sm">
        <?php if ($pagina > 1): ?>
            <a href="<?= $url(['pagina' => $pagina - 1]) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">←</a>
        <?php endif; ?>

        <?php if ($inicio > 1): ?>
            <a href="<?= $url(['pagina' => 1]) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">1</a>
            <?php if ($inicio > 2): ?><span class="px-2 text-gray-400">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $inicio; $i <= $fin; $i++): ?>
            <a href="<?= $url(['pagina' => $i]) ?>"
               class="px-3 py-2 rounded-lg border <?= $i === $pagina ? 'bg-gray-800 text-white border-gray-800' : 'bg-white hover:bg-gray-50' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($fin < $totalPaginas): ?>
            <?php if ($fin < $totalPaginas - 1): ?><span class="px-2 text-gray-400">…</span><?php endif; ?>
            <a href="<?= $url(['pagina' => $totalPaginas]) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50"><?= $totalPaginas ?></a>
        <?php endif; ?>

        <?php if ($pagina < $totalPaginas): ?>
            <a href="<?= $url(['pagina' => $pagina + 1]) ?>" class="px-3 py-2 rounded-lg border bg-white hover:bg-gray-50">→</a>
        <?php endif; ?>
    </div>
    <p class="text-center text-xs text-gray-400 mt-2">Página <?= $pagina ?> de <?= $totalPaginas ?></p>
<?php endif; ?>

<!-- ── Modal: ficha del producto ────────────────────────────── -->
<div id="modalProducto" class="hidden fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/50" onclick="cerrarModalProducto()"></div>
    <div class="absolute inset-x-0 bottom-0 md:inset-0 md:flex md:items-center md:justify-center md:p-6 pointer-events-none">
        <div id="modalProductoContenido"
             class="pointer-events-auto bg-white w-full md:max-w-4xl max-h-[92vh] overflow-y-auto rounded-t-2xl md:rounded-2xl shadow-xl">
        </div>
    </div>
</div>

<script>
(function () {
    const CSRF         = <?= json_encode(csrf_token()) ?>;
    const URL_TOGGLE   = '<?= BASE_URL ?>/admin/productos/toggle';
    const URL_MODAL    = '<?= BASE_URL ?>/admin/productos/modal';
    const URL_CATALOGO = '<?= BASE_URL ?>/admin/catalogo';
    const TOTAL_FILTRO = <?= (int) $total ?>;
    const $     = id => document.getElementById(id);
    const barra = $('barraAcciones');

    // Filtros y orden se aplican apenas cambian (el buscador, con Enter)
    document.querySelectorAll('#formFiltros .auto-filtro').forEach(sel => {
        sel.addEventListener('change', () => sel.form.submit());
    });

    // ══════════════════════════════════════════════════════════════
    // INTERRUPTORES Activo / ★ Destacado (con un toque)
    // ══════════════════════════════════════════════════════════════
    function pintarToggle(btn, valor) {
        btn.dataset.valor = valor;
        if (btn.dataset.campo === 'activo') {
            btn.textContent = valor ? 'Activo' : 'Inactivo';
            btn.className = 'btn-toggle px-2 py-1 text-xs rounded whitespace-nowrap ' +
                (valor ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
            btn.title = 'Tocá para ' + (valor ? 'desactivar' : 'activar');
        } else {
            btn.textContent = valor ? '★' : '☆';
            btn.className = 'btn-toggle text-lg leading-none ' + (valor ? 'text-yellow-500' : 'text-gray-300 hover:text-yellow-400');
            btn.title = valor ? 'Destacado (tocá para quitar)' : 'Tocá para destacar';
        }
    }

    document.addEventListener('click', async e => {
        const btn = e.target.closest('.btn-toggle');
        if (!btn || btn.disabled) return;

        const id = btn.dataset.id, campo = btn.dataset.campo;
        const todos = document.querySelectorAll(`.btn-toggle[data-id="${id}"][data-campo="${campo}"]`);
        const anterior = Number(btn.dataset.valor);

        // Cambio optimista (se ve al instante); si falla, se vuelve atrás
        todos.forEach(b => { pintarToggle(b, anterior ? 0 : 1); b.disabled = true; });

        try {
            const datos = new FormData();
            datos.append('csrf_token', CSRF);
            datos.append('id', id);
            datos.append('campo', campo);

            const resp = await fetch(URL_TOGGLE, { method: 'POST', body: datos, credentials: 'same-origin' });
            const json = (resp.headers.get('Content-Type') || '').includes('application/json') ? await resp.json() : null;

            if (!json || !json.ok) throw new Error();
            todos.forEach(b => pintarToggle(b, json.valor));
        } catch (err) {
            todos.forEach(b => pintarToggle(b, anterior));
            alert('No se pudo guardar. Si pasó mucho tiempo, recargá la página.');
        } finally {
            todos.forEach(b => b.disabled = false);
        }
    });

    // ══════════════════════════════════════════════════════════════
    // MODAL: ficha completa del producto
    // ══════════════════════════════════════════════════════════════
    const modal          = $('modalProducto');
    const modalContenido = $('modalProductoContenido');

    async function abrirModalProducto(id) {
        modalContenido.innerHTML = '<div class="p-16 text-center text-gray-400">Cargando…</div>';
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        try {
            const resp = await fetch(URL_MODAL + '?id=' + encodeURIComponent(id), { credentials: 'same-origin' });
            modalContenido.innerHTML = await resp.text();
        } catch (e) {
            modalContenido.innerHTML = '<p class="p-10 text-center text-red-600">No se pudo cargar. Probá de nuevo.</p>';
        }
    }

    window.cerrarModalProducto = function () {
        modal.classList.add('hidden');
        modalContenido.innerHTML = '';
        document.body.style.overflow = '';
    };

    // Tocar la fila/tarjeta abre el modal, salvo que se toque un botón, link, casilla o campo
    document.addEventListener('click', e => {
        const fila = e.target.closest('[data-abrir-producto]');
        if (!fila) return;
        if (e.target.closest('a, button, input, select, label, textarea, form')) return;
        abrirModalProducto(fila.dataset.abrirProducto);
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) cerrarModalProducto();
    });

    // ══════════════════════════════════════════════════════════════
    // SELECCIÓN
    // ══════════════════════════════════════════════════════════════
    let todoElFiltro = false;

    function idsSeleccionados() {
        return [...new Set([...document.querySelectorAll('.sel-prod:checked')].map(c => c.value))];
    }

    function datosSeleccionados() {
        const vistos = {};
        document.querySelectorAll('.sel-prod:checked').forEach(c => { vistos[c.value] = c.dataset; });
        return Object.values(vistos);
    }

    function revisar() {
        const n = idsSeleccionados().length;
        const enPagina = new Set([...document.querySelectorAll('.sel-prod')].map(c => c.value)).size;

        $('cantSel').textContent = todoElFiltro ? TOTAL_FILTRO + ' (todos los del filtro)' : n;
        barra.classList.toggle('hidden', n === 0 && !todoElFiltro);
        $('btnTodosFiltro').classList.toggle('hidden', todoElFiltro || n < enPagina || TOTAL_FILTRO <= enPagina);

        document.querySelectorAll('.sel-todos').forEach(c => {
            c.checked = n > 0 && n === enPagina;
            c.indeterminate = n > 0 && n < enPagina;
        });

        if (!$('panelPrecios').classList.contains('hidden')) previewPrecios();
    }

    document.querySelectorAll('.sel-prod').forEach(c => c.addEventListener('change', () => {
        todoElFiltro = false;
        document.querySelectorAll(`.sel-prod[value="${c.value}"]`).forEach(o => o.checked = c.checked);
        revisar();
    }));

    document.querySelectorAll('.sel-todos').forEach(t => t.addEventListener('change', () => {
        todoElFiltro = false;
        document.querySelectorAll('.sel-prod').forEach(c => c.checked = t.checked);
        revisar();
    }));

    window.seleccionarTodoElFiltro = function () {
        todoElFiltro = true;
        revisar();
    };

    // ══════════════════════════════════════════════════════════════
    // ACCIONES MASIVAS
    // ══════════════════════════════════════════════════════════════
    const textos = {
        activar: 'activar', desactivar: 'desactivar', destacar: 'destacar',
        quitar_destacado: 'quitarle el destacado a', categoria: 'cambiar la categoría de',
        marca: 'cambiar la marca de', eliminar: 'ELIMINAR', precios: 'cambiar el precio de',
        promocion: 'agregar a la promoción', guia: 'asignarle la guía de talles a'
    };

    function enviar(accion) {
        const cant = todoElFiltro ? TOTAL_FILTRO : idsSeleccionados().length;
        if (!confirm(`¿Seguro que querés ${textos[accion]} ${cant} producto(s)?`)) return;

        $('accionMasiva').value = accion;
        $('todosFiltro').value  = todoElFiltro ? '1' : '';
        $('idsMasivo').innerHTML = todoElFiltro ? '' :
            idsSeleccionados().map(id => `<input type="hidden" name="ids[]" value="${id}">`).join('');
        $('formMasivo').submit();
    }

    window.accion = function (accion, selectId) {
        if (selectId && $(selectId).value === '') { $(selectId).focus(); return; }
        enviar(accion);
    };

    // Catálogo con los productos seleccionados
    window.catalogoSeleccionados = function () {
        if (todoElFiltro) {
            alert('Para el catálogo, seleccioná productos de esta página, o usá los filtros desde "Catálogo PDF" en el menú.');
            return;
        }
        const ids = idsSeleccionados();
        if (!ids.length) return;
        window.location.href = URL_CATALOGO + '?ids=' + ids.join(',');
    };

    // ══════════════════════════════════════════════════════════════
    // PRECIOS
    // ══════════════════════════════════════════════════════════════
    window.togglePrecios = function () {
        $('panelPrecios').classList.toggle('hidden');
        previewPrecios();
    };

    /** Misma fórmula que Producto::calcularPrecio() en PHP */
    function calcular(precio, pct, redondeo) {
        let nuevo = precio * (1 + pct / 100);
        if (redondeo > 0) {
            nuevo = pct >= 0 ? Math.ceil(nuevo / redondeo) * redondeo : Math.floor(nuevo / redondeo) * redondeo;
        }
        return Math.max(0, Math.round(nuevo * 100) / 100);
    }

    const pesos = n => '$' + n.toLocaleString('es-AR', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

    function pctActual() {
        const v = parseFloat(($('precPct').value || '0').replace(',', '.')) || 0;
        return $('precDireccion').value === 'bajar' ? -Math.abs(v) : Math.abs(v);
    }

    function previewPrecios() {
        const pct      = pctActual();
        const redondeo = parseInt($('precRedondeo').value);
        const conCosto = $('precCosto').checked;
        const datos    = datosSeleccionados();

        $('previewPrecios').classList.toggle('hidden', pct === 0 || datos.length === 0);
        $('thCosto').classList.toggle('hidden', !conCosto);

        $('previewPreciosBody').innerHTML = datos.map(d => {
            const precio = parseFloat(d.precio);
            const costo  = d.costo !== '' ? parseFloat(d.costo) : null;
            return `<tr class="border-t">
                <td class="px-3 py-1.5">${escapar(d.nombre)}</td>
                <td class="px-3 py-1.5 text-right text-gray-400">${pesos(precio)}</td>
                <td class="px-3 py-1.5 text-right font-semibold">${pesos(calcular(precio, pct, redondeo))}</td>
                <td class="px-3 py-1.5 text-right ${conCosto ? '' : 'hidden'}">${costo !== null ? pesos(calcular(costo, pct, 0)) : '—'}</td>
            </tr>`;
        }).join('');

        const nota = $('notaPreview');
        nota.classList.toggle('hidden', !todoElFiltro);
        nota.textContent = todoElFiltro
            ? `Se va a aplicar a los ${TOTAL_FILTRO} productos del filtro. La vista previa muestra solo los de esta página.`
            : '';
    }

    ['precPct', 'precDireccion', 'precRedondeo', 'precCosto'].forEach(id => {
        $(id).addEventListener('input', previewPrecios);
        $(id).addEventListener('change', previewPrecios);
    });

    window.aplicarPrecios = function () {
        if (pctActual() === 0) { $('precPct').focus(); return; }
        enviar('precios');
    };

    function escapar(t) {
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }

    revisar();
})();
</script>