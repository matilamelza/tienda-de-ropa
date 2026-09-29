<?php
$fotosArray = [];
while ($f = $fotos->fetch_assoc()) {
    $fotosArray[] = $f;
}

$fotoPrincipal = $fotosArray[0]['imagen'] ?? null;

$variantesArray = [];
while ($v = $variantes->fetch_assoc()) {
    $variantesArray[] = $v;
}

// Solo variantes activas, y si el producto maneja colores o no
$variantesArray = array_values(array_filter($variantesArray, fn($v) => (int) $v['activo'] === 1));
$hayColores     = count(array_filter(array_column($variantesArray, 'color'))) > 0;

$metodosPago = trim($conf['metodos_pago'] ?? '');
$politica    = trim($conf['politica_cambios'] ?? '');

// Promoción vigente del producto
$pctDesc = !empty($descuento) ? (float) $descuento['pct'] : 0;
?>

<section class="max-w-7xl mx-auto px-4 py-10">

    <div class="mb-6">
        <a href="<?= BASE_URL ?>/tienda" class="text-sm text-gray-500 hover:text-gray-900">
            ← Volver a la tienda
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">

        <!-- GALERÍA -->
        <div>
            <div class="relative bg-gray-100 rounded-3xl overflow-hidden aspect-[4/5]">
                <?php if ($fotoPrincipal): ?>
                    <img id="imagenPrincipal"
                         src="<?= BASE_URL ?>/public/uploads/productos/<?php echo htmlspecialchars($fotoPrincipal); ?>"
                         alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                         class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                        Sin imagen
                    </div>
                <?php endif; ?>

                <?= html_badge_oferta($pctDesc, $descuento['etiqueta'] ?? null) ?>
            </div>

            <?php if (count($fotosArray) > 1): ?>
                <div class="grid grid-cols-5 gap-3 mt-4">
                    <?php foreach ($fotosArray as $foto): ?>
                        <button type="button"
                                onclick="cambiarImagen('<?php echo htmlspecialchars($foto['imagen']); ?>')"
                                class="aspect-square rounded-xl overflow-hidden border bg-gray-100 hover:border-gray-900">
                            <img src="<?= BASE_URL ?>/public/uploads/productos/<?php echo htmlspecialchars($foto['imagen']); ?>"
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                 loading="lazy"
                                 class="w-full h-full object-cover">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- INFO PRODUCTO -->
        <div class="lg:pt-6">

            <p class="text-sm uppercase tracking-widest text-gray-400 mb-2">
                <?php echo htmlspecialchars($producto['categoria']); ?>
            </p>

            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                <?php echo htmlspecialchars($producto['nombre']); ?>
            </h1>

            <?php if ($pctDesc > 0): ?>
                <div class="inline-flex flex-wrap items-center gap-2 bg-red-50 text-red-700 text-sm font-semibold px-3 py-1.5 rounded-full mb-3">
                    🔥 <?= $descuento['etiqueta'] ? htmlspecialchars($descuento['etiqueta']) . ' · ' : '' ?>-<?= pct_texto($pctDesc) ?>% OFF
                    <?php if (!empty($descuento['hasta'])): ?>
                        <span class="font-normal">· hasta el <?= date('d/m', strtotime($descuento['hasta'])) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <p id="precioProducto" class="text-3xl mb-6">
                <?= html_precio((float) $producto['precio_base'], $pctDesc, 'font-bold text-gray-900') ?>
            </p>

            <?php if (!empty($producto['descripcion'])): ?>
                <p class="text-gray-600 leading-relaxed mb-8">
                    <?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?>
                </p>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>/carrito/agregar" method="POST" class="space-y-6">
                <input type="hidden" name="id_variante" id="id_variante">

                <?= csrf_field() ?>

                <!-- TALLE -->
                <div>
                    <div class="flex justify-between mb-2">
                        <label class="font-semibold text-gray-800">Talle</label>
                        <span class="text-sm text-gray-400">Elegí una opción</span>
                    </div>

                    <div id="tallesBox" class="flex flex-wrap gap-2"></div>
                </div>

                <!-- COLOR -->
                <div id="bloqueColor" class="<?= $hayColores ? '' : 'hidden' ?>">
                    <div class="flex justify-between mb-2">
                        <label class="font-semibold text-gray-800">Color</label>
                        <span class="text-sm text-gray-400">Disponible según talle</span>
                    </div>

                    <div id="coloresBox" class="flex flex-wrap gap-2"></div>
                </div>

                <!-- STOCK -->
                <div id="stockBox" class="hidden rounded-2xl border p-4 text-sm"></div>

                <!-- CANTIDAD -->
                <div>
                    <label class="font-semibold text-gray-800 block mb-2">Cantidad</label>

                    <div class="flex items-center w-36 border rounded-full overflow-hidden">
                        <button type="button" onclick="cambiarCantidad(-1)"
                                class="w-10 h-10 hover:bg-gray-100">
                            -
                        </button>

                        <input type="text" id="cantidad" name="cantidad" value="1" readonly
                               class="w-14 text-center border-0 focus:outline-none">

                        <button type="button" onclick="cambiarCantidad(1)"
                                class="w-10 h-10 hover:bg-gray-100">
                            +
                        </button>
                    </div>
                </div>

                <button type="submit"
                        id="btnAgregar"
                        disabled
                        class="w-full bg-gray-300 text-white py-4 rounded-full font-semibold cursor-not-allowed">
                    Seleccioná talle y color
                </button>

            </form>

            <!-- INFO DE COMPRA -->
            <div class="mt-10 space-y-3 text-sm">

                <?php if ($metodosPago !== ''): ?>
                    <div class="bg-gray-50 rounded-2xl p-4 flex gap-3">
                        <span class="text-xl">💳</span>
                        <div>
                            <p class="font-semibold text-gray-900">Medios de pago</p>
                            <p class="text-gray-500"><?= htmlspecialchars($metodosPago) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="bg-gray-50 rounded-2xl p-4 flex gap-3">
                    <span class="text-xl">🚚</span>
                    <div>
                        <p class="font-semibold text-gray-900">Envíos</p>
                        <p class="text-gray-500">Coordinamos la entrega por WhatsApp una vez confirmado el pedido.</p>
                    </div>
                </div>

                <?php if ($politica !== ''): ?>
                    <details class="group bg-gray-50 rounded-2xl p-4">
                        <summary class="cursor-pointer list-none flex gap-3 items-center">
                            <span class="text-xl">🔄</span>
                            <span class="font-semibold text-gray-900 flex-1">Cambios y devoluciones</span>
                            <span class="text-gray-400 transition group-open:rotate-180">▾</span>
                        </summary>
                        <p class="mt-3 pl-9 text-gray-500 whitespace-pre-line"><?= htmlspecialchars($politica) ?></p>
                    </details>
                <?php endif; ?>

            </div>

        </div>

    </div>

</section>

<?php if (!empty($relacionados)): ?>
<!-- ── También te puede gustar ─────────────────────────────────────────────── -->
<section class="max-w-7xl mx-auto px-4 pb-16">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">También te puede gustar</h2>

    <!-- Mobile: se desliza de costado · Desktop: grilla -->
    <div class="flex md:grid md:grid-cols-4 gap-4 md:gap-8 overflow-x-auto md:overflow-visible snap-x snap-mandatory -mx-4 px-4 md:mx-0 md:px-0 pb-2">
        <?php foreach ($relacionados as $r): ?>
            <a href="<?= BASE_URL ?>/producto/<?= htmlspecialchars($r['slug']) ?>"
               class="group block shrink-0 w-[65%] sm:w-[40%] md:w-auto snap-start">
                <div class="relative aspect-[3/4] bg-gray-100 rounded-2xl overflow-hidden shadow-sm group-hover:shadow-lg transition">
                    <?php if (!empty($r['foto_principal'])): ?>
                        <img src="<?= BASE_URL ?>/public/uploads/productos/<?= htmlspecialchars($r['foto_principal']) ?>"
                             alt="<?= htmlspecialchars($r['nombre']) ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                             loading="lazy">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-gray-400 text-sm">Sin imagen</div>
                    <?php endif; ?>

                    <?= html_badge_oferta((float) ($r['descuento_pct'] ?? 0), $r['descuento_etiqueta'] ?? null) ?>
                </div>
                <div class="mt-3">
                    <p class="text-xs text-gray-400">
                        <?= htmlspecialchars($r['categoria']) ?><?= !empty($r['marca']) ? ' · ' . htmlspecialchars($r['marca']) : '' ?>
                    </p>
                    <h3 class="font-semibold text-gray-900 mt-1 line-clamp-2"><?= htmlspecialchars($r['nombre']) ?></h3>
                    <p class="mt-1"><?= html_precio((float) $r['precio_base'], (float) ($r['descuento_pct'] ?? 0)) ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<script>
const variantes   = <?php echo json_encode($variantesArray, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const HAY_COLORES = <?= $hayColores ? 'true' : 'false' ?>;

const PRECIO_BASE = <?= json_encode((float) $producto['precio_base']) ?>;
const DESCUENTO   = <?= json_encode($pctDesc) ?>;

function formatoPesos(n) {
    return '$' + Math.round(n).toLocaleString('es-AR');
}

/** Precio de la variante elegida (o del producto), con el descuento aplicado. */
function mostrarPrecio(variante) {
    const lista = variante && variante.precio !== null && variante.precio !== '' ? parseFloat(variante.precio) : PRECIO_BASE;
    const caja  = document.getElementById('precioProducto');

    if (DESCUENTO > 0) {
        const final = Math.round(lista * (1 - DESCUENTO / 100) * 100) / 100;
        caja.innerHTML = `<span class="font-bold" style="color: var(--color-acento)">${formatoPesos(final)}</span>
                          <span class="text-sm text-gray-400 line-through font-normal">${formatoPesos(lista)}</span>`;
    } else {
        caja.innerHTML = `<span class="font-bold text-gray-900">${formatoPesos(lista)}</span>`;
    }
}

let talleSeleccionado    = null;
let colorSeleccionado    = null;
let varianteSeleccionada = null;

const tallesBox     = document.getElementById('tallesBox');
const coloresBox    = document.getElementById('coloresBox');
const stockBox      = document.getElementById('stockBox');
const btnAgregar    = document.getElementById('btnAgregar');
const cantidadInput = document.getElementById('cantidad');

function cambiarImagen(imagen) {
    document.getElementById('imagenPrincipal').src = '<?= BASE_URL ?>/public/uploads/productos/' + imagen;
}

/** Estadísticas: avisa de fondo qué talle eligió (y si había stock). No frena nada. */
function avisarTalle(talle) {
    try {
        const token = document.querySelector('input[name="csrf_token"]');
        if (!token || !navigator.sendBeacon) return;

        const conStock = variantes.some(v => v.talle === talle && parseInt(v.stock_disponible) > 0);

        const datos = new FormData();
        datos.append('csrf_token', token.value);
        datos.append('id_producto', <?= (int) $producto['id_producto'] ?>);
        datos.append('talle', talle);
        datos.append('con_stock', conStock ? '1' : '');

        navigator.sendBeacon('<?= BASE_URL ?>/evento/talle', datos);
    } catch (e) {}
}

/** Elegir un talle: si tiene un solo color (o ninguno), se elige solo. */
function seleccionarTalle(talle) {
    avisarTalle(talle);
    talleSeleccionado    = talle;
    colorSeleccionado    = null;
    varianteSeleccionada = null;
    cantidadInput.value  = 1;

    const delTalle = variantes.filter(v => v.talle === talle);
    const colores  = [...new Set(delTalle.map(v => v.color).filter(Boolean))];

    if (colores.length === 0) {
        varianteSeleccionada = delTalle[0] || null;           // sin color
    } else if (colores.length === 1) {
        colorSeleccionado    = colores[0];                    // un solo color: ya elegido
        varianteSeleccionada = delTalle.find(v => v.color === colores[0]);
    }

    cargarTalles();
    cargarColores();
    actualizarStock();
}

function cargarTalles() {
    const talles = [...new Set(variantes.map(v => v.talle).filter(Boolean))];

    tallesBox.innerHTML = '';

    talles.forEach(talle => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = talle;
        btn.className = talleSeleccionado === talle
            ? 'px-5 py-3 rounded-full border text-sm bg-gray-900 text-white border-gray-900'
            : 'px-5 py-3 rounded-full border text-sm hover:border-gray-900';
        btn.onclick = () => seleccionarTalle(talle);
        tallesBox.appendChild(btn);
    });
}

function cargarColores() {
    coloresBox.innerHTML = '';

    if (!HAY_COLORES) return;

    if (!talleSeleccionado) {
        coloresBox.innerHTML = '<p class="text-sm text-gray-400">Primero seleccioná un talle.</p>';
        return;
    }

    const delTalle = variantes.filter(v => v.talle === talleSeleccionado && v.color);
    const colores  = [...new Set(delTalle.map(v => v.color))];

    if (colores.length === 0) {
        coloresBox.innerHTML = '<p class="text-sm text-gray-400">Este talle viene en un solo color.</p>';
        return;
    }

    colores.forEach(color => {
        const variante = delTalle.find(v => v.color === color);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = colorSeleccionado === color
            ? 'flex items-center gap-2 px-4 py-3 rounded-full border text-sm bg-gray-900 text-white border-gray-900'
            : 'flex items-center gap-2 px-4 py-3 rounded-full border text-sm hover:border-gray-900';

        const muestra = document.createElement('span');
        muestra.className = 'w-4 h-4 rounded-full border';
        muestra.style.background = variante.codigo_hex || '#fff';

        const nombre = document.createElement('span');
        nombre.textContent = color;

        btn.appendChild(muestra);
        btn.appendChild(nombre);

        btn.onclick = () => {
            colorSeleccionado    = color;
            varianteSeleccionada = variante;
            cantidadInput.value  = 1;
            cargarColores();
            actualizarStock();
        };

        coloresBox.appendChild(btn);
    });
}

function actualizarStock() {
    const idInput = document.getElementById('id_variante');
    mostrarPrecio(varianteSeleccionada);

    if (!varianteSeleccionada) {
        idInput.value = '';
        stockBox.className = 'hidden';
        btnAgregar.disabled = true;
        btnAgregar.className = 'w-full bg-gray-300 text-white py-4 rounded-full font-semibold cursor-not-allowed';
        btnAgregar.textContent = !talleSeleccionado
            ? (HAY_COLORES ? 'Seleccioná talle y color' : 'Seleccioná un talle')
            : 'Seleccioná un color';
        return;
    }

    const stock = parseInt(varianteSeleccionada.stock_disponible);

    stockBox.className = 'rounded-2xl border p-4 text-sm';

    if (stock <= 0) {
        idInput.value = '';
        stockBox.innerHTML = '<strong class="text-red-600">Sin stock disponible</strong>';
        btnAgregar.disabled = true;
        btnAgregar.className = 'w-full bg-gray-300 text-white py-4 rounded-full font-semibold cursor-not-allowed';
        btnAgregar.textContent = 'Sin stock';
    } else {
        idInput.value = varianteSeleccionada.id_variante;

        stockBox.innerHTML = stock <= 3
            ? `<strong class="text-orange-600">¡Últimas ${stock} unidades!</strong>`
            : '<strong class="text-green-700">Disponible</strong>';

        btnAgregar.disabled = false;
        btnAgregar.className = 'btn-primario w-full py-4 rounded-full font-semibold';
        btnAgregar.textContent = 'Agregar al carrito';
    }
}

function cambiarCantidad(valor) {
    let cantidad = parseInt(cantidadInput.value);
    let stock    = varianteSeleccionada ? parseInt(varianteSeleccionada.stock_disponible) : 1;

    cantidad += valor;

    if (cantidad < 1) cantidad = 1;
    if (cantidad > stock) cantidad = stock;

    cantidadInput.value = cantidad;
}

cargarTalles();
cargarColores();
actualizarStock();

// Si el producto tiene un solo talle, se elige solo
const tallesUnicos = [...new Set(variantes.map(v => v.talle).filter(Boolean))];
if (tallesUnicos.length === 1) {
    seleccionarTalle(tallesUnicos[0]);
}
</script>