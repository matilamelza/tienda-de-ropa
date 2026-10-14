<?php
/**
 * Vista: admin/configuracion/index.php
 * Personalización de la tienda.
 */
if (!function_exists('h')) {
    function h(string $str): string { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
}

$c = fn(string $k, string $def = '') => h((string) ($config[$k] ?? $def));

$secciones = [
    'identidad'     => ['Identidad',        '<path d="M3 9l9-6 9 6v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M9 21V12h6v9"/>'],
    'diseno'        => ['Diseño',           '<circle cx="13.5" cy="6.5" r="1"/><circle cx="17.5" cy="10.5" r="1"/><circle cx="8.5" cy="7.5" r="1"/><circle cx="6.5" cy="12.5" r="1"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.9 0 1.6-.7 1.6-1.7 0-.4-.2-.8-.4-1.1-.3-.3-.4-.7-.4-1.1 0-.9.7-1.7 1.7-1.7h2c3 0 5.5-2.5 5.5-5.5C22 6 17.5 2 12 2z"/>'],
    'portada'       => ['Portada',          '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>'],
    'anuncio'       => ['Barra de anuncio', '<path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>'],
    'contacto'      => ['Contacto y redes', '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>'],
    'compras'       => ['Compras y pagos',  '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>'],
    'seo'           => ['Google y compartir','<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>'],
    'footer'        => ['Pie de página',    '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 15h18"/>'],
    'mantenimiento' => ['Mantenimiento',    '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-6.9 6.9a2.1 2.1 0 0 1-3-3l6.9-6.9a6 6 0 0 1 7.9-7.9l-3.8 3.8z"/>'],
];

$coloresConfig = [
    'color_primario'    => ['Primario',          '#111827', 'Detalles, íconos y etiquetas'],
    'color_secundario'  => ['Secundario',        '#6B7280', 'Textos de apoyo'],
    'color_acento'      => ['Acento',            '#F97316', 'Ofertas y destacados'],
    'color_fondo'       => ['Fondo del sitio',   '#FFFFFF', ''],
    'color_texto'       => ['Texto',             '#111827', ''],
    'color_header_bg'   => ['Fondo del encabezado', '#FFFFFF', ''],
    'color_footer_bg'   => ['Fondo del pie',     '#111827', ''],
    'color_boton_bg'    => ['Botones',           '#111827', ''],
    'color_boton_texto' => ['Texto de botones',  '#FFFFFF', ''],
];

$paletas = [
    'Clásico'   => ['#111827', '#6B7280', '#F97316', '#FFFFFF', '#111827', '#FFFFFF', '#111827', '#111827', '#FFFFFF'],
    'Arena'     => ['#8B6F47', '#A8977F', '#C2410C', '#FBF8F3', '#2B2118', '#FBF8F3', '#2B2118', '#2B2118', '#FBF8F3'],
    'Rosa'      => ['#BE185D', '#9D7A86', '#DB2777', '#FFF7F9', '#3F1D2B', '#FFFFFF', '#3F1D2B', '#BE185D', '#FFFFFF'],
    'Oliva'     => ['#4D5B3A', '#7C8670', '#B45309', '#F7F7F2', '#1F2418', '#F7F7F2', '#1F2418', '#4D5B3A', '#FFFFFF'],
    'Marino'    => ['#1E3A5F', '#64748B', '#EAB308', '#F8FAFC', '#0F172A', '#FFFFFF', '#0F172A', '#1E3A5F', '#FFFFFF'],
    'Terracota' => ['#9A3412', '#A8796A', '#EA580C', '#FFFAF5', '#2D1A12', '#FFFAF5', '#2D1A12', '#9A3412', '#FFFFFF'],
];

$fuentes = ['Inter', 'Roboto', 'Lato', 'Poppins', 'Montserrat', 'Open Sans', 'Nunito', 'Raleway', 'Playfair Display', 'Merriweather', 'DM Sans', 'Outfit', 'Be Vietnam Pro'];

$mantenimiento = ($config['mantenimiento_activo'] ?? '') === '1';
$dominio       = preg_replace('#^https?://#', '', rtrim(url_absoluta(''), '/'));
$imgCompartir  = !empty($config['tienda_logo']) ? $config['tienda_logo'] : ($config['hero_imagen'] ?? '');

$input  = 'w-full border rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-gray-200';
$label  = 'block text-sm font-medium text-gray-700 mb-1';
$ayuda  = 'block text-xs text-gray-400 mt-1';
$card   = 'bg-white rounded-lg shadow p-6 scroll-mt-6';
$icono  = fn(string $svg, string $cls = 'w-4 h-4') => '<svg class="' . $cls . ' shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">' . $svg . '</svg>';

/** Interruptor (checkbox con estilo) */
$interruptor = function (string $name, bool $on, string $texto, string $sub = '') {
    return '<label class="flex items-center justify-between gap-4 cursor-pointer py-1">'
         . '<span class="text-sm text-gray-700">' . $texto . ($sub ? '<span class="block text-xs text-gray-400">' . $sub . '</span>' : '') . '</span>'
         . '<span class="relative inline-block w-11 h-6 shrink-0">'
         . '<input type="checkbox" name="' . $name . '" value="1" class="peer sr-only"' . ($on ? ' checked' : '') . '>'
         . '<span class="absolute inset-0 rounded-full bg-gray-300 transition peer-checked:bg-gray-900"></span>'
         . '<span class="absolute left-0.5 top-0.5 w-5 h-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>'
         . '</span></label>';
};

/** Campo de imagen con vista previa */
$campoImagen = function (string $name, string $titulo, string $ayudaTxt, string $clsImg, string $accept = 'image/*') use ($config, $label, $ayuda) {
    $actual = $config[$name] ?? '';
    ob_start(); ?>
    <div>
        <span class="<?= $label ?>"><?= $titulo ?></span>
        <div class="flex items-center gap-4">
            <div class="<?= $clsImg ?> rounded-lg border bg-gray-50 flex items-center justify-center overflow-hidden shrink-0">
                <img data-preview="<?= $name ?>" src="<?= $actual ? BASE_URL . '/' . htmlspecialchars($actual) : '' ?>" alt=""
                     class="max-w-full max-h-full object-contain <?= $actual ? '' : 'hidden' ?>">
                <span data-vacio="<?= $name ?>" class="text-xs text-gray-300 <?= $actual ? 'hidden' : '' ?>">Sin imagen</span>
            </div>
            <div class="min-w-0 space-y-1.5">
                <label class="inline-block cursor-pointer px-3 py-1.5 rounded-lg border text-sm text-gray-700 hover:bg-gray-50">
                    <?= $actual ? 'Cambiar' : 'Subir' ?>
                    <input type="file" name="<?= $name ?>" accept="<?= $accept ?>" class="sr-only" data-archivo="<?= $name ?>">
                </label>
                <?php if ($actual): ?>
                    <button type="button" onclick="eliminarImagen('<?= $name ?>', '¿Eliminar <?= mb_strtolower($titulo) ?>?')"
                            class="block text-xs text-red-500 hover:underline">Eliminar</button>
                <?php endif; ?>
                <span data-nombre-archivo="<?= $name ?>" class="block text-xs text-gray-500 truncate"></span>
            </div>
        </div>
        <span class="<?= $ayuda ?>"><?= $ayudaTxt ?></span>
    </div>
    <?php return ob_get_clean();
};
?>

<!-- Form oculto para eliminar imágenes (afuera del form principal) -->
<form id="form-eliminar-imagen" method="POST" action="<?= BASE_URL ?>/admin/configuracion/eliminar-imagen" class="hidden">
    <?= csrf_field() ?>
    <input type="hidden" name="campo" id="campo-eliminar">
</form>

<!-- Fuentes para la vista previa -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link id="fuentesPreview" rel="stylesheet" href="">

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Personalización</h2>
    <p class="text-gray-500 text-sm">Cómo se ve y cómo funciona tu tienda.</p>
</div>

<?php if (!empty($mensaje)): ?>
    <div class="mb-6 px-4 py-3 rounded-xl text-sm border
        <?= $mensaje['tipo'] === 'ok' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
        <?= h($mensaje['texto']) ?>
    </div>
<?php endif; ?>

<?php if ($mantenimiento): ?>
    <a href="#mantenimiento" class="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl text-sm border bg-orange-50 text-orange-800 border-orange-200">
        <?= $icono($secciones['mantenimiento'][1], 'w-5 h-5') ?>
        <span><strong>La tienda está en mantenimiento.</strong> Los clientes ven el mensaje de mantenimiento, no los productos.</span>
    </a>
<?php endif; ?>

<div class="lg:grid lg:grid-cols-[13rem_1fr] lg:gap-8 items-start">

    <!-- ══ Menú de secciones ════════════════════════════════════════════ -->
    <nav class="lg:sticky lg:top-6 mb-6 lg:mb-0 -mx-4 px-4 lg:mx-0 lg:px-0 overflow-x-auto">
        <ul class="flex lg:flex-col gap-1 text-sm whitespace-nowrap">
            <?php foreach ($secciones as $id => [$txt, $svg]): ?>
                <li>
                    <a href="#<?= $id ?>" data-nav="<?= $id ?>"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gray-600 hover:bg-white hover:text-gray-900 transition">
                        <?= $icono($svg) ?>
                        <?= $txt ?>
                        <?php if ($id === 'mantenimiento' && $mantenimiento): ?><span class="w-2 h-2 rounded-full bg-orange-500"></span><?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <!-- ══ Formulario ═══════════════════════════════════════════════════ -->
    <form action="<?= BASE_URL ?>/admin/configuracion/guardar" method="POST" enctype="multipart/form-data" id="formConfig" class="space-y-6 pb-28 min-w-0">
        <?= csrf_field() ?>

        <!-- 1. IDENTIDAD ────────────────────────────────────────────── -->
        <section id="identidad" class="<?= $card ?>">
            <h3 class="font-bold text-gray-800">Identidad</h3>
            <p class="text-sm text-gray-400 mb-5">El nombre y la imagen de tu tienda.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <label class="block">
                    <span class="<?= $label ?>">Nombre de la tienda *</span>
                    <input type="text" name="tienda_nombre" id="inNombre" required value="<?= $c('tienda_nombre') ?>" class="<?= $input ?>">
                </label>
                <label class="block">
                    <span class="<?= $label ?>">Slogan</span>
                    <input type="text" name="tienda_slogan" value="<?= $c('tienda_slogan') ?>" placeholder="Ej: Calzado para todos los días" class="<?= $input ?>">
                    <span class="<?= $ayuda ?>">Se ve debajo del nombre, en pantallas grandes.</span>
                </label>
                <label class="block md:col-span-2">
                    <span class="<?= $label ?>">Descripción breve</span>
                    <textarea name="tienda_descripcion" rows="2" class="<?= $input ?>" placeholder="Ej: Zapatillas, botas y sandalias de las mejores marcas. Envíos a todo el país."><?= $c('tienda_descripcion') ?></textarea>
                    <span class="<?= $ayuda ?>">Aparece en el pie de página.</span>
                </label>

                <?= $campoImagen('tienda_logo', 'Logo', 'PNG o SVG con fondo transparente. Si no subís logo, se usa la inicial del nombre.', 'w-28 h-16') ?>
                <?= $campoImagen('tienda_favicon', 'Ícono de la pestaña', 'El iconito que se ve en la pestaña del navegador. PNG cuadrado (32×32) o ICO.', 'w-16 h-16', 'image/*,.ico') ?>
            </div>
        </section>

        <!-- 2. DISEÑO ───────────────────────────────────────────────── -->
        <section id="diseno" class="<?= $card ?>">
            <h3 class="font-bold text-gray-800">Diseño</h3>
            <p class="text-sm text-gray-400 mb-5">Colores y letras. La vista previa cambia mientras elegís.</p>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                <div class="space-y-6">
                    <!-- Paletas -->
                    <div>
                        <span class="<?= $label ?>">Paletas listas</span>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($paletas as $nombre => $p): ?>
                                <button type="button" onclick='aplicarPaleta(<?= json_encode($p) ?>)'
                                        class="flex items-center gap-2 pl-1.5 pr-3 py-1.5 rounded-full border text-xs text-gray-700 hover:border-gray-400">
                                    <span class="flex -space-x-1">
                                        <?php foreach ([$p[3], $p[0], $p[2]] as $col): ?>
                                            <span class="w-4 h-4 rounded-full border border-white" style="background: <?= $col ?>"></span>
                                        <?php endforeach; ?>
                                    </span>
                                    <?= $nombre ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <span class="<?= $ayuda ?>">Aplicá una y después ajustá lo que quieras.</span>
                    </div>

                    <!-- Colores -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-3">
                        <?php foreach ($coloresConfig as $campo => [$etq, $def, $sub]): ?>
                            <?php $val = $c($campo, $def); ?>
                            <div class="flex items-center gap-3">
                                <label class="relative w-10 h-10 rounded-lg border cursor-pointer overflow-hidden shrink-0" style="background: <?= $val ?>" data-muestra="<?= $campo ?>">
                                    <input type="color" name="<?= $campo ?>" value="<?= $val ?>" class="color-in absolute inset-0 opacity-0 cursor-pointer">
                                </label>
                                <div class="min-w-0 flex-1">
                                    <span class="block text-sm text-gray-700 leading-tight"><?= $etq ?></span>
                                    <input type="text" value="<?= $val ?>" data-hex="<?= $campo ?>" maxlength="7"
                                           class="w-24 text-xs font-mono text-gray-500 bg-transparent border-0 p-0 focus:ring-0 focus:outline-none">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Fuentes -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <label class="block">
                            <span class="<?= $label ?>">Letra de textos</span>
                            <select name="fuente_principal" class="<?= $input ?> fuente-in">
                                <?php foreach ($fuentes as $f): ?>
                                    <option value="<?= $f ?>" style="font-family: '<?= $f ?>'" <?= ($config['fuente_principal'] ?? 'Inter') === $f ? 'selected' : '' ?>><?= $f ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="block">
                            <span class="<?= $label ?>">Letra de títulos</span>
                            <select name="fuente_titulos" class="<?= $input ?> fuente-in">
                                <?php foreach ($fuentes as $f): ?>
                                    <option value="<?= $f ?>" style="font-family: '<?= $f ?>'" <?= ($config['fuente_titulos'] ?? 'Inter') === $f ? 'selected' : '' ?>><?= $f ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="block">
                            <span class="<?= $label ?>">Tamaño (px)</span>
                            <input type="number" name="tamano_base" min="12" max="22" value="<?= $c('tamano_base', '16') ?>" class="<?= $input ?>" data-sin-mil>
                        </label>
                    </div>
                </div>

                <!-- Vista previa -->
                <div class="xl:sticky xl:top-6 self-start">
                    <span class="<?= $label ?>">Vista previa</span>
                    <div id="prev" class="rounded-xl border overflow-hidden shadow-sm text-[13px]">
                        <div id="prevHeader" class="flex items-center justify-between px-4 py-3 border-b">
                            <span id="prevNombre" class="font-bold text-base">Tienda</span>
                            <span class="flex gap-3 text-xs opacity-70"><span>Zapatillas</span><span>Botas</span><span>🛒</span></span>
                        </div>
                        <div id="prevCuerpo" class="p-4">
                            <p id="prevTitulo" class="text-lg font-bold mb-3">Nuevos ingresos</p>
                            <div class="grid grid-cols-2 gap-3">
                                <?php foreach ([['Zapatilla urbana', '$89.000', true], ['Bota de cuero', '$124.000', false]] as [$n, $pr, $oferta]): ?>
                                    <div>
                                        <div class="aspect-square rounded-lg bg-black/5 mb-2 relative">
                                            <?php if ($oferta): ?><span class="prev-acento absolute top-1.5 left-1.5 text-[10px] font-bold px-1.5 py-0.5 rounded">-20%</span><?php endif; ?>
                                        </div>
                                        <p class="font-medium leading-tight"><?= $n ?></p>
                                        <p class="prev-secundario text-xs mb-2"><?= $pr ?></p>
                                        <span class="prev-boton block text-center text-xs font-semibold py-1.5 rounded-full">Agregar</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div id="prevFooter" class="px-4 py-3 text-xs" style="color: #fff">
                            <span class="opacity-70">© <?= date('Y') ?> · Contacto · Instagram</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. PORTADA ──────────────────────────────────────────────── -->
        <section id="portada" class="<?= $card ?>">
            <h3 class="font-bold text-gray-800">Portada</h3>
            <p class="text-sm text-gray-400 mb-5">El banner grande del inicio de la tienda.</p>

            <!-- Vista previa del banner -->
            <div id="heroPrev" class="relative rounded-xl overflow-hidden mb-5 h-44 md:h-52 flex items-center bg-gray-100 bg-cover bg-center"
                 data-img="<?= !empty($config['hero_imagen']) ? BASE_URL . '/' . h($config['hero_imagen']) : '' ?>">
                <div id="heroVelo" class="absolute inset-0"></div>
                <div class="relative px-6 md:px-10 max-w-md">
                    <span id="heroPrevEtiqueta" class="inline-block text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded mb-2 prev-acento"></span>
                    <p id="heroPrevTitulo" class="text-xl md:text-2xl font-bold leading-tight"></p>
                    <p id="heroPrevSub" class="text-sm opacity-80 mt-1"></p>
                    <span id="heroPrevBoton" class="prev-boton inline-block mt-3 text-xs font-semibold px-4 py-2 rounded-full"></span>
                </div>
                <span id="heroOculto" class="hidden absolute inset-0 flex items-center justify-center text-sm text-gray-400 bg-gray-50">La portada no se muestra</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <span class="<?= $label ?>">Estilo</span>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2" id="estilosHero">
                        <?php foreach (['imagen' => 'Con imagen', 'gradiente' => 'Degradado', 'color' => 'Color liso', 'ninguno' => 'Sin portada'] as $val => $txt): ?>
                            <label class="cursor-pointer">
                                <input type="radio" name="hero_estilo" value="<?= $val ?>" class="peer sr-only" <?= ($config['hero_estilo'] ?? 'gradiente') === $val ? 'checked' : '' ?>>
                                <span class="block text-center text-sm px-3 py-2 rounded-lg border text-gray-600 peer-checked:border-gray-900 peer-checked:text-gray-900 peer-checked:font-semibold hover:bg-gray-50"><?= $txt ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <label class="block md:col-span-2">
                    <span class="<?= $label ?>">Etiqueta <span class="font-normal text-gray-400">(opcional)</span></span>
                    <input type="text" name="hero_etiqueta" maxlength="40" value="<?= $c('hero_etiqueta') ?>" placeholder="Ej: Hot Sale, Liquidación, Envíos gratis" class="<?= $input ?> hero-in">
                </label>
                <label class="block">
                    <span class="<?= $label ?>">Título</span>
                    <input type="text" name="hero_titulo" value="<?= $c('hero_titulo') ?>" placeholder="Ej: Nueva temporada" class="<?= $input ?> hero-in">
                </label>
                <label class="block">
                    <span class="<?= $label ?>">Subtítulo</span>
                    <input type="text" name="hero_subtitulo" value="<?= $c('hero_subtitulo') ?>" placeholder="Ej: Hasta 6 cuotas sin interés" class="<?= $input ?> hero-in">
                </label>
                <label class="block">
                    <span class="<?= $label ?>">Texto del botón</span>
                    <input type="text" name="hero_boton_texto" value="<?= $c('hero_boton_texto', 'Ver productos') ?>" class="<?= $input ?> hero-in">
                </label>

                <div id="campoImagenHero">
                    <?= $campoImagen('hero_imagen', 'Imagen', '1920×800 px, horizontal, con lo importante hacia la derecha. JPG o WebP.', 'w-28 h-16') ?>
                </div>
            </div>
        </section>

        <!-- 4. ANUNCIO ──────────────────────────────────────────────── -->
        <section id="anuncio" class="<?= $card ?>">
            <h3 class="font-bold text-gray-800">Barra de anuncio</h3>
            <p class="text-sm text-gray-400 mb-5">Un mensaje arriba de todo, en todas las páginas.</p>

            <div id="anuncioPrev" class="mb-5 rounded-lg text-center text-sm py-2 px-4 font-medium"></div>

            <div class="space-y-5">
                <?= $interruptor('anuncio_activo', ($config['anuncio_activo'] ?? '') === '1', 'Mostrar la barra de anuncio') ?>
                <label class="block">
                    <span class="<?= $label ?>">Mensaje</span>
                    <input type="text" name="anuncio_texto" id="anuncioTexto" value="<?= $c('anuncio_texto') ?>" placeholder="🚚 Envío gratis en compras desde $100.000" class="<?= $input ?>">
                </label>
                <div class="grid grid-cols-2 gap-5">
                    <?php foreach (['anuncio_color_bg' => ['Fondo', '#111827'], 'anuncio_color_texto' => ['Texto', '#FFFFFF']] as $campo => [$etq, $def]): ?>
                        <?php $val = $c($campo, $def); ?>
                        <div class="flex items-center gap-3">
                            <label class="relative w-10 h-10 rounded-lg border cursor-pointer overflow-hidden shrink-0" style="background: <?= $val ?>" data-muestra="<?= $campo ?>">
                                <input type="color" name="<?= $campo ?>" value="<?= $val ?>" class="color-in absolute inset-0 opacity-0 cursor-pointer">
                            </label>
                            <div>
                                <span class="block text-sm text-gray-700 leading-tight"><?= $etq ?></span>
                                <input type="text" value="<?= $val ?>" data-hex="<?= $campo ?>" maxlength="7"
                                       class="w-24 text-xs font-mono text-gray-500 bg-transparent border-0 p-0 focus:ring-0 focus:outline-none">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- 5. CONTACTO ─────────────────────────────────────────────── -->
        <section id="contacto" class="<?= $card ?>">
            <h3 class="font-bold text-gray-800">Contacto y redes</h3>
            <p class="text-sm text-gray-400 mb-5">Se muestran en el pie de página y en la compra.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="md:col-span-2">
                    <span class="<?= $label ?>">WhatsApp</span>
                    <div class="flex gap-2">
                        <input type="text" name="tienda_whatsapp" id="inWhatsapp" inputmode="numeric" value="<?= $c('tienda_whatsapp') ?>" placeholder="5492364123456" class="<?= $input ?>" data-sin-mil>
                        <a href="#" id="probarWa" target="_blank" rel="noopener" class="shrink-0 px-3 py-2 rounded-lg border text-sm text-gray-700 hover:bg-gray-50">Probar</a>
                    </div>
                    <span class="<?= $ayuda ?>">Con 549 + característica + número, sin el 0 ni el 15. A este número llegan los pedidos.</span>
                </div>
                <label class="block">
                    <span class="<?= $label ?>">Teléfono</span>
                    <input type="text" name="tienda_telefono" value="<?= $c('tienda_telefono') ?>" placeholder="236 412-3456" class="<?= $input ?>">
                </label>
                <label class="block">
                    <span class="<?= $label ?>">Email</span>
                    <input type="email" name="tienda_email" value="<?= $c('tienda_email') ?>" placeholder="hola@mitienda.com" class="<?= $input ?>">
                </label>
                <label class="block md:col-span-2">
                    <span class="<?= $label ?>">Dirección del local</span>
                    <input type="text" name="tienda_direccion" value="<?= $c('tienda_direccion') ?>" placeholder="Av. San Martín 123, Junín" class="<?= $input ?>">
                    <span class="<?= $ayuda ?>">En el pie de página abre Google Maps.</span>
                </label>

                <?php foreach (['tienda_instagram' => ['Instagram', 'instagram.com/'], 'tienda_facebook' => ['Facebook', 'facebook.com/'], 'tienda_tiktok' => ['TikTok', 'tiktok.com/@']] as $campo => [$red, $pre]): ?>
                    <label class="block">
                        <span class="<?= $label ?>"><?= $red ?></span>
                        <span class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 bg-gray-50 text-gray-400 text-xs"><?= $pre ?></span>
                            <input type="text" name="<?= $campo ?>" value="<?= $c($campo) ?>" placeholder="mitienda" class="<?= $input ?> rounded-l-none">
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- 6. COMPRAS Y PAGOS ──────────────────────────────────────── -->
        <section id="compras" class="<?= $card ?>">
            <h3 class="font-bold text-gray-800">Compras y pagos</h3>
            <p class="text-sm text-gray-400 mb-5">Lo que ve el cliente antes de comprar.</p>

            <div class="space-y-5">
                <?= $interruptor('permitir_invitados', ($config['permitir_invitados'] ?? '1') === '1', 'Permitir comprar sin crear cuenta', 'Recomendado: menos pasos, más ventas.') ?>
                <label class="block">
                    <span class="<?= $label ?>">Medios de pago</span>
                    <input type="text" name="metodos_pago" value="<?= $c('metodos_pago') ?>" placeholder="Efectivo, Transferencia, Mercado Pago" class="<?= $input ?>">
                    <span class="<?= $ayuda ?>">Separados por coma. En el pie de página se ven como etiquetas.</span>
                </label>
                <label class="block">
                    <span class="<?= $label ?>">Cambios y devoluciones</span>
                    <textarea name="politica_cambios" rows="4" class="<?= $input ?>" placeholder="Aceptamos cambios dentro de los 30 días, con el producto sin uso y en su caja original."><?= $c('politica_cambios') ?></textarea>
                </label>
            </div>
        </section>

        <!-- 7. GOOGLE Y COMPARTIR ───────────────────────────────────── -->
        <section id="seo" class="<?= $card ?>">
            <h3 class="font-bold text-gray-800">Google y compartir</h3>
            <p class="text-sm text-gray-400 mb-5">Cómo aparece tu tienda en Google y cuando se comparte el link.</p>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="space-y-5">
                    <label class="block">
                        <span class="flex justify-between <?= $label ?>">Título <span class="font-normal text-gray-400" data-cuenta="seo_titulo"></span></span>
                        <input type="text" name="seo_titulo" maxlength="70" value="<?= $c('seo_titulo') ?>" placeholder="Ej: DB Calzados · Zapatillas y botas en Junín" class="<?= $input ?> seo-in" data-max="60">
                    </label>
                    <label class="block">
                        <span class="flex justify-between <?= $label ?>">Descripción <span class="font-normal text-gray-400" data-cuenta="seo_descripcion"></span></span>
                        <textarea name="seo_descripcion" rows="3" maxlength="160" class="<?= $input ?> seo-in" data-max="155"
                                  placeholder="Ej: Zapatillas, botas y sandalias. Envíos a todo el país y 3 cuotas sin interés."><?= $c('seo_descripcion') ?></textarea>
                    </label>
                    <label class="block">
                        <span class="<?= $label ?>">Palabras clave</span>
                        <input type="text" name="seo_keywords" value="<?= $c('seo_keywords') ?>" placeholder="zapatillas, botas, calzado, junín" class="<?= $input ?>">
                        <span class="<?= $ayuda ?>">Separadas por coma. Hoy Google casi no las usa, pero no molestan.</span>
                    </label>
                </div>

                <div class="space-y-4">
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">En Google</span>
                        <div class="rounded-xl border p-4 bg-white">
                            <p class="text-xs text-gray-600 truncate"><?= h($dominio) ?></p>
                            <p id="gTitulo" class="text-[#1a0dab] text-lg leading-snug truncate"></p>
                            <p id="gDesc" class="text-sm text-gray-600 leading-snug line-clamp-2"></p>
                        </div>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">En WhatsApp</span>
                        <div class="rounded-xl overflow-hidden bg-[#e7ffdb] p-1.5 max-w-xs">
                            <div class="rounded-lg overflow-hidden bg-white/70 flex">
                                <?php if ($imgCompartir): ?>
                                    <img src="<?= BASE_URL ?>/<?= h($imgCompartir) ?>" alt="" class="w-20 h-20 object-cover bg-gray-100 shrink-0">
                                <?php endif; ?>
                                <div class="p-2 min-w-0">
                                    <p id="wTitulo" class="text-sm font-semibold text-gray-900 truncate"></p>
                                    <p id="wDesc" class="text-xs text-gray-600 line-clamp-2"></p>
                                    <p class="text-[11px] text-gray-400 truncate"><?= h($dominio) ?></p>
                                </div>
                            </div>
                        </div>
                        <span class="<?= $ayuda ?>">La imagen es el logo (o la de la portada, si no hay logo).</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- 8. PIE DE PÁGINA ────────────────────────────────────────── -->
        <section id="footer" class="<?= $card ?>">
            <h3 class="font-bold text-gray-800">Pie de página</h3>
            <p class="text-sm text-gray-400 mb-5">Lo que se ve al final de todas las páginas.</p>

            <div class="space-y-5">
                <label class="block">
                    <span class="<?= $label ?>">Texto</span>
                    <input type="text" name="footer_texto" value="<?= $c('footer_texto') ?>" placeholder="© <?= date('Y') ?> <?= $c('tienda_nombre', 'Mi tienda') ?>" class="<?= $input ?>">
                    <span class="<?= $ayuda ?>">Vacío = "© <?= date('Y') ?>" y el nombre de la tienda.</span>
                </label>
                <?= $interruptor('footer_mostrar_redes', ($config['footer_mostrar_redes'] ?? '') === '1', 'Mostrar los íconos de redes sociales') ?>
            </div>
        </section>

        <!-- 9. MANTENIMIENTO ────────────────────────────────────────── -->
        <section id="mantenimiento" class="<?= $card ?> <?= $mantenimiento ? 'ring-2 ring-orange-300' : '' ?>">
            <h3 class="font-bold text-gray-800">Mantenimiento</h3>
            <p class="text-sm text-gray-400 mb-5">Cierra la tienda a los clientes mientras hacés cambios. Vos, como admin, la seguís viendo.</p>

            <div class="space-y-5">
                <label class="flex items-center justify-between gap-4 cursor-pointer py-1">
                    <span class="text-sm text-gray-700">Activar modo mantenimiento</span>
                    <span class="relative inline-block w-11 h-6 shrink-0">
                        <input type="checkbox" name="mantenimiento_activo" value="1" class="peer sr-only" <?= $mantenimiento ? 'checked' : '' ?>>
                        <span class="absolute inset-0 rounded-full bg-gray-300 transition peer-checked:bg-orange-500"></span>
                        <span class="absolute left-0.5 top-0.5 w-5 h-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                    </span>
                </label>
                <label class="block">
                    <span class="<?= $label ?>">Mensaje para los clientes</span>
                    <textarea name="mantenimiento_mensaje" rows="2" class="<?= $input ?>" placeholder="Estamos actualizando la tienda. Volvemos en un rato 🙌"><?= $c('mantenimiento_mensaje') ?></textarea>
                </label>
            </div>
        </section>

        <!-- Barra de guardar -->
        <div id="barraGuardar" class="fixed bottom-0 inset-x-0 lg:left-auto lg:right-6 lg:bottom-6 lg:inset-x-auto z-40
                                      bg-white lg:rounded-xl border-t lg:border shadow-lg px-4 py-3 flex items-center justify-between gap-4 transition">
            <span id="estadoGuardar" class="text-sm text-gray-400">Sin cambios</span>
            <button id="btnGuardar" class="px-5 py-2 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800 disabled:opacity-40" disabled>
                Guardar cambios
            </button>
        </div>
    </form>
</div>

<script>
// Eliminar imagen (form aparte, con CSRF)
function eliminarImagen(campo, mensaje) {
    if (!confirm(mensaje)) return;
    document.getElementById('campo-eliminar').value = campo;
    document.getElementById('form-eliminar-imagen').submit();
}

(function () {
    const $   = id => document.getElementById(id);
    const val = name => (document.querySelector(`[name="${name}"]`) || {}).value || '';
    const form = $('formConfig');

    // ── Cambios sin guardar ─────────────────────────────────────────
    let sucio = false;
    function marcarCambio() {
        if (sucio) return;
        sucio = true;
        $('estadoGuardar').textContent = 'Tenés cambios sin guardar';
        $('estadoGuardar').className = 'text-sm text-gray-900 font-medium';
        $('btnGuardar').disabled = false;
    }
    form.addEventListener('input', marcarCambio);
    form.addEventListener('change', marcarCambio);
    window.addEventListener('beforeunload', e => { if (sucio) { e.preventDefault(); e.returnValue = ''; } });
    form.addEventListener('submit', () => {
        sucio = false;
        $('btnGuardar').disabled = true;
        $('btnGuardar').textContent = 'Guardando…';
    });

    // ── Colores: muestra ↔ hex ─────────────────────────────────────
    function setColor(name, hex) {
        const picker = document.querySelector(`input[type=color][name="${name}"]`);
        const texto  = document.querySelector(`[data-hex="${name}"]`);
        const caja   = document.querySelector(`[data-muestra="${name}"]`);
        if (picker) picker.value = hex;
        if (texto)  texto.value  = hex.toUpperCase();
        if (caja)   caja.style.background = hex;
    }
    document.querySelectorAll('.color-in').forEach(p => p.addEventListener('input', () => { setColor(p.name, p.value); pintarPreview(); }));
    document.querySelectorAll('[data-hex]').forEach(t => t.addEventListener('input', () => {
        if (/^#[0-9a-f]{6}$/i.test(t.value)) { setColor(t.dataset.hex, t.value); pintarPreview(); marcarCambio(); }
    }));

    const CAMPOS_PALETA = ['color_primario', 'color_secundario', 'color_acento', 'color_fondo', 'color_texto', 'color_header_bg', 'color_footer_bg', 'color_boton_bg', 'color_boton_texto'];
    window.aplicarPaleta = function (p) {
        CAMPOS_PALETA.forEach((c, i) => setColor(c, p[i]));
        pintarPreview();
        marcarCambio();
    };

    // ── Fuentes reales para la vista previa ────────────────────────
    function cargarFuentes() {
        const f = [...new Set([val('fuente_principal'), val('fuente_titulos')])];
        $('fuentesPreview').href = 'https://fonts.googleapis.com/css2?' + f.map(x => 'family=' + encodeURIComponent(x) + ':wght@400;600;700').join('&') + '&display=swap';
    }
    document.querySelectorAll('.fuente-in').forEach(s => s.addEventListener('change', () => { cargarFuentes(); pintarPreview(); }));

    // ── Vista previa: diseño, portada y anuncio ────────────────────
    function pintarPreview() {
        const fTxt = `'${val('fuente_principal')}', sans-serif`;
        const fTit = `'${val('fuente_titulos')}', sans-serif`;

        $('prev').style.fontFamily  = fTxt;
        $('prevCuerpo').style.background = val('color_fondo');
        $('prevCuerpo').style.color      = val('color_texto');
        $('prevHeader').style.background = val('color_header_bg');
        $('prevHeader').style.color      = val('color_texto');
        $('prevFooter').style.background = val('color_footer_bg');
        $('prevNombre').textContent      = val('tienda_nombre') || 'Tienda';
        $('prevNombre').style.fontFamily = fTit;
        $('prevTitulo').style.fontFamily = fTit;
        document.querySelectorAll('.prev-boton').forEach(b => { b.style.background = val('color_boton_bg'); b.style.color = val('color_boton_texto'); });
        document.querySelectorAll('.prev-acento').forEach(b => { b.style.background = val('color_acento'); b.style.color = '#fff'; });
        document.querySelectorAll('.prev-secundario').forEach(b => { b.style.color = val('color_secundario'); });

        // Portada
        const estilo = (document.querySelector('input[name="hero_estilo"]:checked') || {}).value || 'gradiente';
        const hero   = $('heroPrev'), velo = $('heroVelo');
        const img    = hero.dataset.img;
        $('heroOculto').classList.toggle('hidden', estilo !== 'ninguno');
        $('campoImagenHero').classList.toggle('opacity-40', estilo !== 'imagen');
        hero.style.fontFamily = fTxt;
        $('heroPrevTitulo').style.fontFamily = fTit;

        if (estilo === 'imagen' && img) {
            hero.style.backgroundImage = `url('${img}')`;
            hero.style.background = '';
            hero.style.backgroundImage = `url('${img}')`;
            velo.style.background = 'linear-gradient(90deg, rgba(0,0,0,.55), rgba(0,0,0,.05))';
            hero.style.color = '#fff';
        } else if (estilo === 'color') {
            hero.style.backgroundImage = 'none';
            hero.style.background = val('color_primario');
            velo.style.background = 'none';
            hero.style.color = '#fff';
        } else {
            hero.style.backgroundImage = 'none';
            hero.style.background = `linear-gradient(120deg, ${val('color_primario')}, ${val('color_acento')})`;
            velo.style.background = 'none';
            hero.style.color = '#fff';
        }

        const etiqueta = val('hero_etiqueta');
        $('heroPrevEtiqueta').textContent = etiqueta;
        $('heroPrevEtiqueta').classList.toggle('hidden', !etiqueta);
        $('heroPrevTitulo').textContent = val('hero_titulo') || 'Título de la portada';
        $('heroPrevSub').textContent    = val('hero_subtitulo');
        $('heroPrevBoton').textContent  = val('hero_boton_texto') || 'Ver productos';

        // Anuncio
        const a = $('anuncioPrev');
        const activo = document.querySelector('input[name="anuncio_activo"]').checked;
        a.textContent = val('anuncio_texto') || 'Tu mensaje acá';
        a.style.background = val('anuncio_color_bg');
        a.style.color      = val('anuncio_color_texto');
        a.style.opacity    = activo ? 1 : .35;
    }

    ['tienda_nombre', 'hero_etiqueta', 'hero_titulo', 'hero_subtitulo', 'hero_boton_texto', 'anuncio_texto'].forEach(n =>
        document.querySelector(`[name="${n}"]`).addEventListener('input', pintarPreview));
    document.querySelectorAll('input[name="hero_estilo"], input[name="anuncio_activo"]').forEach(r => r.addEventListener('change', pintarPreview));

    // ── Imágenes: vista previa antes de subir ──────────────────────
    document.querySelectorAll('[data-archivo]').forEach(inp => inp.addEventListener('change', () => {
        const f = inp.files[0];
        if (!f) return;
        const url = URL.createObjectURL(f);
        const img = document.querySelector(`[data-preview="${inp.dataset.archivo}"]`);
        img.src = url;
        img.classList.remove('hidden');
        document.querySelector(`[data-vacio="${inp.dataset.archivo}"]`).classList.add('hidden');
        document.querySelector(`[data-nombre-archivo="${inp.dataset.archivo}"]`).textContent = f.name + ' · se sube al guardar';
        if (inp.dataset.archivo === 'hero_imagen') { $('heroPrev').dataset.img = url; pintarPreview(); }
    }));

    // ── Google y WhatsApp ──────────────────────────────────────────
    function pintarSeo() {
        const nombre = val('tienda_nombre');
        const tit    = val('seo_titulo') || nombre;
        const desc   = val('seo_descripcion') || val('tienda_descripcion') || 'Agregá una descripción para que se vea acá.';
        $('gTitulo').textContent = tit;  $('gDesc').textContent = desc;
        $('wTitulo').textContent = tit;  $('wDesc').textContent = desc;

        document.querySelectorAll('.seo-in').forEach(i => {
            const cuenta = document.querySelector(`[data-cuenta="${i.name}"]`);
            const n = i.value.length, max = +i.dataset.max;
            cuenta.textContent = n + ' / ' + max;
            cuenta.className = 'font-normal ' + (n > max ? 'text-red-600' : 'text-gray-400');
        });
    }
    document.querySelectorAll('.seo-in, [name="tienda_nombre"], [name="tienda_descripcion"]').forEach(i => i.addEventListener('input', pintarSeo));

    // ── Probar WhatsApp ────────────────────────────────────────────
    function linkWa() {
        const n = $('inWhatsapp').value.replace(/\D/g, '');
        $('probarWa').href = n ? 'https://wa.me/' + n : '#';
        $('probarWa').classList.toggle('opacity-40', !n);
    }
    $('inWhatsapp').addEventListener('input', linkWa);

    // ── Menú: marcar la sección visible ────────────────────────────
    const links = document.querySelectorAll('[data-nav]');
    const obs = new IntersectionObserver(entradas => {
        entradas.forEach(en => {
            if (!en.isIntersecting) return;
            links.forEach(l => {
                const activo = l.dataset.nav === en.target.id;
                l.classList.toggle('bg-white', activo);
                l.classList.toggle('text-gray-900', activo);
                l.classList.toggle('font-medium', activo);
                l.classList.toggle('shadow-sm', activo);
            });
        });
    }, { rootMargin: '-30% 0px -60% 0px' });
    document.querySelectorAll('#formConfig section').forEach(s => obs.observe(s));

    cargarFuentes();
    pintarPreview();
    pintarSeo();
    linkWa();
})();
</script>