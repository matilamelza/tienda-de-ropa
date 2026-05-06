<?php
/**
 * Vista: admin/configuracion/index.php
 * Panel de personalización completa de la tienda.
 */
?>

<div class="flex min-h-screen bg-gray-50">

    <?php require __DIR__ . '/../../layouts/sidebar.php'; ?>

    <main class="flex-1 p-8">

        <!-- Cabecera -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Personalización de la tienda</h1>
            <p class="text-gray-500 mt-1">Configurá todos los aspectos visuales y funcionales de tu tienda.</p>
        </div>

        <!-- Mensaje de feedback -->
        <?php if (!empty($mensaje)): ?>
            <div class="mb-6 px-5 py-4 rounded-xl text-sm font-medium
                <?php echo $mensaje['tipo'] === 'ok' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
                <?php echo htmlspecialchars($mensaje['texto']); ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/admin/configuracion/guardar"
              method="POST"
              enctype="multipart/form-data"
              class="space-y-8">

            <!-- ═══════════════════════════════════════════════ -->
            <!-- 1. IDENTIDAD                                    -->
            <!-- ═══════════════════════════════════════════════ -->
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1 flex items-center gap-2">
                    <span class="text-2xl">🏪</span> Identidad de la tienda
                </h2>
                <p class="text-sm text-gray-400 mb-6">Nombre, logo y datos principales que se muestran en toda la tienda.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <div>
                        <label class="label">Nombre de la tienda *</label>
                        <input type="text" name="tienda_nombre"
                               value="<?= h($config['tienda_nombre'] ?? '') ?>"
                               class="input" required>
                    </div>

                    <div>
                        <label class="label">Slogan</label>
                        <input type="text" name="tienda_slogan"
                               value="<?= h($config['tienda_slogan'] ?? '') ?>"
                               class="input" placeholder="Ej: Ropa con estilo para todos">
                    </div>

                    <div class="md:col-span-2">
                        <label class="label">Descripción breve de la tienda</label>
                        <textarea name="tienda_descripcion" rows="2"
                                  class="input"><?= h($config['tienda_descripcion'] ?? '') ?></textarea>
                    </div>

                    <!-- Logo -->
                    <div>
                        <label class="label">Logo principal</label>
                        <?php if (!empty($config['tienda_logo'])): ?>
                            <div class="mb-3 flex items-center gap-3">
                                <img src="<?= BASE_URL ?>/<?= h($config['tienda_logo']) ?>"
                                     alt="Logo actual" class="h-14 rounded-lg border bg-gray-50 object-contain p-1">
                                <form method="POST" action="<?= BASE_URL ?>/admin/configuracion/eliminar-imagen"
                                      onsubmit="return confirm('¿Eliminar el logo actual?')">
                                    <input type="hidden" name="campo" value="tienda_logo">
                                    <button class="text-xs text-red-500 hover:underline">Eliminar</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="tienda_logo" accept="image/*" class="input-file">
                        <p class="text-xs text-gray-400 mt-1">PNG, SVG o JPG. Máx. 5 MB. Recomendado: fondo transparente.</p>
                    </div>

                    <!-- Favicon -->
                    <div>
                        <label class="label">Favicon</label>
                        <?php if (!empty($config['tienda_favicon'])): ?>
                            <div class="mb-3 flex items-center gap-3">
                                <img src="<?= BASE_URL ?>/<?= h($config['tienda_favicon']) ?>"
                                     alt="Favicon actual" class="h-8 w-8 rounded border bg-gray-50 object-contain">
                                <form method="POST" action="<?= BASE_URL ?>/admin/configuracion/eliminar-imagen"
                                      onsubmit="return confirm('¿Eliminar el favicon?')">
                                    <input type="hidden" name="campo" value="tienda_favicon">
                                    <button class="text-xs text-red-500 hover:underline">Eliminar</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="tienda_favicon" accept="image/*,.ico" class="input-file">
                        <p class="text-xs text-gray-400 mt-1">ICO, PNG 32×32 o 16×16.</p>
                    </div>

                </div>
            </div>

            <!-- ═══════════════════════════════════════════════ -->
            <!-- 2. COLORES                                      -->
            <!-- ═══════════════════════════════════════════════ -->
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1 flex items-center gap-2">
                    <span class="text-2xl">🎨</span> Colores
                </h2>
                <p class="text-sm text-gray-400 mb-6">Paleta de colores que define la identidad visual de tu tienda.</p>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
                    <?php
                    $coloresConfig = [
                        'color_primario'    => 'Color primario',
                        'color_secundario'  => 'Color secundario',
                        'color_acento'      => 'Color de acento',
                        'color_fondo'       => 'Fondo del sitio',
                        'color_texto'       => 'Texto principal',
                        'color_header_bg'   => 'Fondo del header',
                        'color_footer_bg'   => 'Fondo del footer',
                        'color_boton_bg'    => 'Fondo de botones',
                        'color_boton_texto' => 'Texto en botones',
                    ];
                    foreach ($coloresConfig as $campo => $etiqueta): ?>
                        <div>
                            <label class="label"><?= $etiqueta ?></label>
                            <div class="flex items-center gap-2">
                                <input type="color" name="<?= $campo ?>"
                                       value="<?= h($config[$campo] ?? '#111827') ?>"
                                       class="h-10 w-14 rounded-lg border cursor-pointer p-0.5">
                                <input type="text"
                                       value="<?= h($config[$campo] ?? '#111827') ?>"
                                       class="input flex-1 font-mono text-sm"
                                       oninput="document.querySelector('input[type=color][name=<?= $campo ?>]').value=this.value"
                                       onchange="this.previousElementSibling.value=this.value"
                                       placeholder="#111827">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Vista previa de botón -->
                <div class="mt-6 pt-5 border-t">
                    <p class="text-sm font-medium text-gray-700 mb-3">Vista previa de botón</p>
                    <div id="preview-boton"
                         class="inline-flex items-center px-6 py-3 rounded-full font-medium text-sm transition-all"
                         style="background-color: <?= h($config['color_boton_bg'] ?? '#111827') ?>; color: <?= h($config['color_boton_texto'] ?? '#FFFFFF') ?>">
                        Ver productos
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════ -->
            <!-- 3. TIPOGRAFÍA                                   -->
            <!-- ═══════════════════════════════════════════════ -->
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1 flex items-center gap-2">
                    <span class="text-2xl">🔤</span> Tipografía
                </h2>
                <p class="text-sm text-gray-400 mb-6">Fuentes usadas en la tienda (deben ser fuentes de Google Fonts).</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="label">Fuente principal (cuerpo)</label>
                        <select name="fuente_principal" class="input">
                            <?php
                            $fuentes = ['Inter','Roboto','Lato','Poppins','Montserrat','Open Sans','Nunito','Raleway','Playfair Display','Merriweather','DM Sans','Outfit','Be Vietnam Pro'];
                            foreach ($fuentes as $f): ?>
                                <option value="<?= $f ?>" <?= ($config['fuente_principal'] ?? 'Inter') === $f ? 'selected' : '' ?>>
                                    <?= $f ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Fuente para títulos</label>
                        <select name="fuente_titulos" class="input">
                            <?php foreach ($fuentes as $f): ?>
                                <option value="<?= $f ?>" <?= ($config['fuente_titulos'] ?? 'Inter') === $f ? 'selected' : '' ?>>
                                    <?= $f ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Tamaño base (px)</label>
                        <input type="number" name="tamano_base" min="12" max="22"
                               value="<?= h($config['tamano_base'] ?? '16') ?>"
                               class="input">
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════ -->
            <!-- 4. HERO / BANNER                               -->
            <!-- ═══════════════════════════════════════════════ -->
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1 flex items-center gap-2">
                    <span class="text-2xl">🖼️</span> Hero / Banner principal
                </h2>
                <p class="text-sm text-gray-400 mb-6">El banner que aparece en la portada de la tienda.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <div>
                        <label class="label">Título principal</label>
                        <input type="text" name="hero_titulo"
                               value="<?= h($config['hero_titulo'] ?? '') ?>"
                               class="input">
                    </div>

                    <div>
                        <label class="label">Subtítulo / descripción</label>
                        <input type="text" name="hero_subtitulo"
                               value="<?= h($config['hero_subtitulo'] ?? '') ?>"
                               class="input">
                    </div>

                    <div>
                        <label class="label">Texto del botón</label>
                        <input type="text" name="hero_boton_texto"
                               value="<?= h($config['hero_boton_texto'] ?? 'Ver productos') ?>"
                               class="input">
                    </div>

                    <div>
                        <label class="label">Estilo del hero</label>
                        <select name="hero_estilo" class="input">
                            <option value="gradiente" <?= ($config['hero_estilo'] ?? '') === 'gradiente' ? 'selected' : '' ?>>Gradiente (sin imagen)</option>
                            <option value="imagen"    <?= ($config['hero_estilo'] ?? '') === 'imagen'    ? 'selected' : '' ?>>Imagen de fondo</option>
                            <option value="color"     <?= ($config['hero_estilo'] ?? '') === 'color'     ? 'selected' : '' ?>>Color sólido</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="label">Imagen del hero</label>
                        <?php if (!empty($config['hero_imagen'])): ?>
                            <div class="mb-3 flex items-center gap-3">
                                <img src="<?= BASE_URL ?>/<?= h($config['hero_imagen']) ?>"
                                     alt="Hero actual" class="h-24 rounded-xl border object-cover">
                                <form method="POST" action="<?= BASE_URL ?>/admin/configuracion/eliminar-imagen"
                                      onsubmit="return confirm('¿Eliminar la imagen del hero?')">
                                    <input type="hidden" name="campo" value="hero_imagen">
                                    <button class="text-xs text-red-500 hover:underline">Eliminar imagen</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="hero_imagen" accept="image/*" class="input-file">
                        <p class="text-xs text-gray-400 mt-1">Recomendado: 1400×600 px. JPG o WebP.</p>
                    </div>

                </div>
            </div>

            <!-- ═══════════════════════════════════════════════ -->
            <!-- 5. ANUNCIO (barra superior)                    -->
            <!-- ═══════════════════════════════════════════════ -->
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1 flex items-center gap-2">
                    <span class="text-2xl">📢</span> Barra de anuncio
                </h2>
                <p class="text-sm text-gray-400 mb-6">Barra superior con un mensaje destacado (ej: envío gratis, promoción).</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <div class="md:col-span-2 flex items-center gap-3">
                        <label class="toggle-label">
                            <input type="checkbox" name="anuncio_activo" value="1"
                                   <?= !empty($config['anuncio_activo']) && $config['anuncio_activo'] === '1' ? 'checked' : '' ?>>
                            <span class="toggle"></span>
                        </label>
                        <span class="text-sm font-medium text-gray-700">Mostrar barra de anuncio</span>
                    </div>

                    <div class="md:col-span-2">
                        <label class="label">Texto del anuncio</label>
                        <input type="text" name="anuncio_texto"
                               value="<?= h($config['anuncio_texto'] ?? '') ?>"
                               class="input" placeholder="🚚 Envío gratis en compras mayores a $10.000">
                    </div>

                    <div>
                        <label class="label">Color de fondo</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="anuncio_color_bg"
                                   value="<?= h($config['anuncio_color_bg'] ?? '#111827') ?>"
                                   class="h-10 w-14 rounded-lg border cursor-pointer p-0.5">
                            <input type="text" value="<?= h($config['anuncio_color_bg'] ?? '#111827') ?>"
                                   class="input flex-1 font-mono text-sm" placeholder="#111827"
                                   oninput="syncColor(this,'anuncio_color_bg')">
                        </div>
                    </div>

                    <div>
                        <label class="label">Color del texto</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="anuncio_color_texto"
                                   value="<?= h($config['anuncio_color_texto'] ?? '#FFFFFF') ?>"
                                   class="h-10 w-14 rounded-lg border cursor-pointer p-0.5">
                            <input type="text" value="<?= h($config['anuncio_color_texto'] ?? '#FFFFFF') ?>"
                                   class="input flex-1 font-mono text-sm" placeholder="#FFFFFF"
                                   oninput="syncColor(this,'anuncio_color_texto')">
                        </div>
                    </div>

                </div>
            </div>

            <!-- ═══════════════════════════════════════════════ -->
            <!-- 6. CONTACTO Y REDES SOCIALES                  -->
            <!-- ═══════════════════════════════════════════════ -->
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1 flex items-center gap-2">
                    <span class="text-2xl">📱</span> Contacto y redes sociales
                </h2>
                <p class="text-sm text-gray-400 mb-6">Información de contacto que se muestra en el footer y en el proceso de compra.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <div>
                        <label class="label">Email de contacto</label>
                        <input type="email" name="tienda_email"
                               value="<?= h($config['tienda_email'] ?? '') ?>"
                               class="input" placeholder="contacto@mitienda.com">
                    </div>

                    <div>
                        <label class="label">Teléfono</label>
                        <input type="text" name="tienda_telefono"
                               value="<?= h($config['tienda_telefono'] ?? '') ?>"
                               class="input" placeholder="+54 11 1234-5678">
                    </div>

                    <div>
                        <label class="label">WhatsApp (con código de país, sin +)</label>
                        <input type="text" name="tienda_whatsapp"
                               value="<?= h($config['tienda_whatsapp'] ?? '') ?>"
                               class="input" placeholder="5491112345678">
                        <p class="text-xs text-gray-400 mt-1">Se usa para el botón de consulta por pedido.</p>
                    </div>

                    <div>
                        <label class="label">Dirección física</label>
                        <input type="text" name="tienda_direccion"
                               value="<?= h($config['tienda_direccion'] ?? '') ?>"
                               class="input" placeholder="Av. Corrientes 1234, Buenos Aires">
                    </div>

                    <div>
                        <label class="label">Instagram</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 bg-gray-50 text-gray-500 text-sm">@</span>
                            <input type="text" name="tienda_instagram"
                                   value="<?= h($config['tienda_instagram'] ?? '') ?>"
                                   class="input rounded-l-none" placeholder="mitienda">
                        </div>
                    </div>

                    <div>
                        <label class="label">Facebook</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 bg-gray-50 text-gray-500 text-sm">fb/</span>
                            <input type="text" name="tienda_facebook"
                                   value="<?= h($config['tienda_facebook'] ?? '') ?>"
                                   class="input rounded-l-none" placeholder="mitienda">
                        </div>
                    </div>

                    <div>
                        <label class="label">TikTok</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 bg-gray-50 text-gray-500 text-sm">@</span>
                            <input type="text" name="tienda_tiktok"
                                   value="<?= h($config['tienda_tiktok'] ?? '') ?>"
                                   class="input rounded-l-none" placeholder="mitienda">
                        </div>
                    </div>

                </div>
            </div>

            <!-- ═══════════════════════════════════════════════ -->
            <!-- 7. SEO                                         -->
            <!-- ═══════════════════════════════════════════════ -->
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1 flex items-center gap-2">
                    <span class="text-2xl">🔍</span> SEO
                </h2>
                <p class="text-sm text-gray-400 mb-6">Metadatos que mejoran el posicionamiento en Google.</p>

                <div class="grid grid-cols-1 gap-5">
                    <div>
                        <label class="label">Título de la página (title)</label>
                        <input type="text" name="seo_titulo"
                               value="<?= h($config['seo_titulo'] ?? '') ?>"
                               class="input" maxlength="70">
                        <p class="text-xs text-gray-400 mt-1">Máx. 70 caracteres. Se ve en la pestaña del navegador y en Google.</p>
                    </div>
                    <div>
                        <label class="label">Meta descripción</label>
                        <textarea name="seo_descripcion" rows="2" maxlength="160"
                                  class="input"><?= h($config['seo_descripcion'] ?? '') ?></textarea>
                        <p class="text-xs text-gray-400 mt-1">Máx. 160 caracteres. Aparece en los resultados de búsqueda.</p>
                    </div>
                    <div>
                        <label class="label">Palabras clave (separadas por coma)</label>
                        <input type="text" name="seo_keywords"
                               value="<?= h($config['seo_keywords'] ?? '') ?>"
                               class="input" placeholder="ropa, moda, indumentaria">
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════ -->
            <!-- 8. ENVÍOS Y PAGOS                             -->
            <!-- ═══════════════════════════════════════════════ -->
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1 flex items-center gap-2">
                    <span class="text-2xl">🚚</span> Envíos y pagos
                </h2>
                <p class="text-sm text-gray-400 mb-6">Configuración de costos de envío y métodos de pago aceptados.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="label">Monto mínimo para envío gratis (0 = siempre gratis)</label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 bg-gray-50 text-gray-500 text-sm">$</span>
                            <input type="number" name="envio_gratis_minimo" min="0"
                                   value="<?= h($config['envio_gratis_minimo'] ?? '0') ?>"
                                   class="input rounded-l-none">
                        </div>
                    </div>
                    <div>
                        <label class="label">Métodos de pago aceptados</label>
                        <input type="text" name="metodos_pago"
                               value="<?= h($config['metodos_pago'] ?? '') ?>"
                               class="input" placeholder="Efectivo, Transferencia, Mercado Pago">
                    </div>
                    <div class="md:col-span-2">
                        <label class="label">Política de cambios y devoluciones</label>
                        <textarea name="politica_cambios" rows="3"
                                  class="input" placeholder="Aceptamos cambios dentro de los 30 días de realizada la compra..."><?= h($config['politica_cambios'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════ -->
            <!-- 9. MONEDA Y COMPORTAMIENTO                    -->
            <!-- ═══════════════════════════════════════════════ -->
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1 flex items-center gap-2">
                    <span class="text-2xl">⚙️</span> Moneda y comportamiento
                </h2>
                <p class="text-sm text-gray-400 mb-6">Opciones generales de la tienda.</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="label">Símbolo de moneda</label>
                        <input type="text" name="moneda_simbolo" maxlength="5"
                               value="<?= h($config['moneda_simbolo'] ?? '$') ?>"
                               class="input" placeholder="$">
                    </div>
                    <div>
                        <label class="label">Código de moneda</label>
                        <input type="text" name="moneda_codigo" maxlength="5"
                               value="<?= h($config['moneda_codigo'] ?? 'ARS') ?>"
                               class="input" placeholder="ARS">
                    </div>
                    <div>
                        <label class="label">Productos por página</label>
                        <select name="productos_por_pagina" class="input">
                            <?php foreach ([6, 9, 12, 16, 20, 24] as $n): ?>
                                <option value="<?= $n ?>" <?= ($config['productos_por_pagina'] ?? '12') == $n ? 'selected' : '' ?>><?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php
                    $checks = [
                        'mostrar_precio'    => 'Mostrar precios en la tienda',
                        'mostrar_stock'     => 'Mostrar indicador de stock',
                        'permitir_invitados'=> 'Permitir compra sin registro',
                    ];
                    foreach ($checks as $campo => $etiqueta): ?>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <label class="toggle-label">
                                <input type="checkbox" name="<?= $campo ?>" value="1"
                                       <?= !empty($config[$campo]) && $config[$campo] === '1' ? 'checked' : '' ?>>
                                <span class="toggle"></span>
                            </label>
                            <span class="text-sm text-gray-700"><?= $etiqueta ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════ -->
            <!-- 10. FOOTER                                     -->
            <!-- ═══════════════════════════════════════════════ -->
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1 flex items-center gap-2">
                    <span class="text-2xl">🦶</span> Footer
                </h2>
                <p class="text-sm text-gray-400 mb-6">Texto e íconos del pie de página.</p>

                <div class="grid grid-cols-1 gap-5">
                    <div>
                        <label class="label">Texto del footer</label>
                        <input type="text" name="footer_texto"
                               value="<?= h($config['footer_texto'] ?? '') ?>"
                               class="input" placeholder="© 2025 Mi Tienda. Todos los derechos reservados.">
                    </div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <label class="toggle-label">
                            <input type="checkbox" name="footer_mostrar_redes" value="1"
                                   <?= !empty($config['footer_mostrar_redes']) && $config['footer_mostrar_redes'] === '1' ? 'checked' : '' ?>>
                            <span class="toggle"></span>
                        </label>
                        <span class="text-sm text-gray-700">Mostrar íconos de redes sociales en el footer</span>
                    </label>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════ -->
            <!-- 11. MODO MANTENIMIENTO                        -->
            <!-- ═══════════════════════════════════════════════ -->
            <div class="bg-white rounded-2xl shadow-sm border border-orange-100 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1 flex items-center gap-2">
                    <span class="text-2xl">🚧</span> Modo mantenimiento
                </h2>
                <p class="text-sm text-gray-400 mb-6">Activá para mostrar una página de mantenimiento a los visitantes mientras seguís trabajando como admin.</p>

                <div class="grid grid-cols-1 gap-5">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <label class="toggle-label">
                            <input type="checkbox" name="mantenimiento_activo" value="1"
                                   <?= !empty($config['mantenimiento_activo']) && $config['mantenimiento_activo'] === '1' ? 'checked' : '' ?>>
                            <span class="toggle toggle-orange"></span>
                        </label>
                        <span class="text-sm font-medium text-gray-700">Activar modo mantenimiento</span>
                    </label>
                    <div>
                        <label class="label">Mensaje para los visitantes</label>
                        <textarea name="mantenimiento_mensaje" rows="2"
                                  class="input"><?= h($config['mantenimiento_mensaje'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- ─── Botón guardar ─────────────────────────── -->
            <div class="flex justify-end pb-8">
                <button type="submit"
                        class="px-10 py-3 rounded-xl bg-gray-900 text-white font-semibold text-sm hover:bg-gray-800 transition shadow-lg">
                    💾 Guardar toda la configuración
                </button>
            </div>

        </form>

    </main>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- ESTILOS UTILITARIOS PARA ESTE PANEL                       -->
<!-- ═══════════════════════════════════════════════════════════ -->
<style>
  .label  { display:block; font-size:.8125rem; font-weight:600; color:#374151; margin-bottom:.35rem; }
  .input  { width:100%; border:1px solid #E5E7EB; border-radius:.6rem; padding:.55rem .85rem; font-size:.875rem;
            outline:none; transition:border .15s; background:#fff; }
  .input:focus { border-color:#6B7280; box-shadow:0 0 0 3px rgba(107,114,128,.15); }
  textarea.input { resize:vertical; }
  .input-file { display:block; font-size:.8rem; color:#6B7280; }

  /* Toggle switch */
  .toggle-label { position:relative; display:inline-block; width:44px; height:24px; cursor:pointer; }
  .toggle-label input { opacity:0; width:0; height:0; }
  .toggle { position:absolute; inset:0; background:#D1D5DB; border-radius:999px; transition:.25s; }
  .toggle::before { content:''; position:absolute; left:3px; bottom:3px; width:18px; height:18px;
                    background:#fff; border-radius:50%; transition:.25s; }
  .toggle-label input:checked + .toggle { background:#111827; }
  .toggle-label input:checked + .toggle::before { transform:translateX(20px); }
  .toggle-orange.toggle { }
  .toggle-label input:checked + .toggle-orange { background:#F97316; }
</style>

<!-- Sincronización color picker ↔ texto hex -->
<script>
function syncColor(textInput, name) {
    const picker = document.querySelector(`input[type=color][name="${name}"]`);
    if (picker) picker.value = textInput.value;
}

// Sync picker → text
document.querySelectorAll('input[type=color]').forEach(picker => {
    picker.addEventListener('input', () => {
        const next = picker.nextElementSibling;
        if (next && next.tagName === 'INPUT') next.value = picker.value;
    });
});

// Vista previa del botón en tiempo real
const btnBg   = document.querySelector('input[name=color_boton_bg]');
const btnTxt  = document.querySelector('input[name=color_boton_texto]');
const preview = document.getElementById('preview-boton');

function actualizarPreview() {
    if (!preview) return;
    const bg = document.querySelector('input[type=color][name=color_boton_bg]')?.value  || '#111827';
    const tx = document.querySelector('input[type=color][name=color_boton_texto]')?.value || '#FFFFFF';
    preview.style.backgroundColor = bg;
    preview.style.color = tx;
}

document.querySelectorAll('input[type=color]').forEach(p => p.addEventListener('input', actualizarPreview));
</script>

<?php
// Helper para escapar HTML
function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>