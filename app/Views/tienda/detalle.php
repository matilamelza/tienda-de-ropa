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

$metodosPago = trim($conf['metodos_pago'] ?? '');
$politica    = trim($conf['politica_cambios'] ?? '');
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
            <div class="bg-gray-100 rounded-3xl overflow-hidden aspect-[4/5]">
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

            <p class="text-3xl font-bold text-gray-900 mb-6">
                $<?php echo number_format($producto['precio_base'], 2, ',', '.'); ?>
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
                <div>
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

<script>
const variantes = <?php echo json_encode($variantesArray, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

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

function cargarTalles() {
    const talles = [...new Set(variantes.map(v => v.talle).filter(Boolean))];

    tallesBox.innerHTML = '';

    talles.forEach(talle => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = talle;
        btn.className = 'px-5 py-3 rounded-full border text-sm hover:border-gray-900';

        btn.onclick = () => {
            talleSeleccionado    = talle;
            colorSeleccionado    = null;
            varianteSeleccionada = null;
            cantidadInput.value  = 1;

            cargarTalles();
            cargarColores();
            actualizarStock();
        };

        if (talleSeleccionado === talle) {
            btn.className = 'px-5 py-3 rounded-full border text-sm bg-gray-900 text-white border-gray-900';
        }

        tallesBox.appendChild(btn);
    });
}

function cargarColores() {
    coloresBox.innerHTML = '';

    if (!talleSeleccionado) {
        coloresBox.innerHTML = '<p class="text-sm text-gray-400">Primero seleccioná un talle.</p>';
        return;
    }

    const colores = variantes
        .filter(v => v.talle === talleSeleccionado)
        .map(v => v.color)
        .filter(Boolean);

    [...new Set(colores)].forEach(color => {
        const variante = variantes.find(v => v.talle === talleSeleccionado && v.color === color);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'flex items-center gap-2 px-4 py-3 rounded-full border text-sm hover:border-gray-900';

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

        if (colorSeleccionado === color) {
            btn.className = 'flex items-center gap-2 px-4 py-3 rounded-full border text-sm bg-gray-900 text-white border-gray-900';
        }

        coloresBox.appendChild(btn);
    });
}

function actualizarStock() {
    if (!varianteSeleccionada) {
        document.getElementById('id_variante').value = '';
        stockBox.className = 'hidden';
        btnAgregar.disabled = true;
        btnAgregar.className = 'w-full bg-gray-300 text-white py-4 rounded-full font-semibold cursor-not-allowed';
        btnAgregar.textContent = 'Seleccioná talle y color';
        return;
    }

    const stock = parseInt(varianteSeleccionada.stock_disponible);

    stockBox.className = 'rounded-2xl border p-4 text-sm';

    if (stock <= 0) {
        document.getElementById('id_variante').value = '';
        stockBox.innerHTML = '<strong class="text-red-600">Sin stock disponible</strong>';
        btnAgregar.disabled = true;
        btnAgregar.className = 'w-full bg-gray-300 text-white py-4 rounded-full font-semibold cursor-not-allowed';
        btnAgregar.textContent = 'Sin stock';
    } else {
        document.getElementById('id_variante').value = varianteSeleccionada.id_variante;

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
</script>