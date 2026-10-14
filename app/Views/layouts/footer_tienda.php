<?php
  require_once dirname(__DIR__, 2) . '/Models/ConfiguracionTienda.php';
  $cfg  = new ConfiguracionTienda();
  $conf = $cfg->todas();

  // ── Tu firma ────────────────────────────────────────────────────────
  // A dónde lleva tu nombre (WhatsApp, Instagram o tu web). Vacío = sin link.
  $creditoNombre = 'Matias Lamelza';
  $creditoUrl    = '';   // ej: 'https://wa.me/5492364XXXXXX?text=' . rawurlencode('Hola Matias! Vi una tienda tuya y quiero una igual')

  // Limpia usuarios de redes: saca @, espacios y URLs pegadas completas
  $usuarioRed = function (string $valor): string {
      $valor = trim($valor);
      $valor = preg_replace('#^https?://[^/]+/#i', '', $valor);
      return trim($valor, "@/ ");
  };

  $nombreTienda = $conf['tienda_nombre'] ?? 'Tienda';
  $descripcion  = trim($conf['tienda_descripcion'] ?? '');
  $email        = trim($conf['tienda_email'] ?? '');
  $telefono     = trim($conf['tienda_telefono'] ?? '');
  $direccion    = trim($conf['tienda_direccion'] ?? '');
  $whatsapp     = preg_replace('/\D/', '', $conf['tienda_whatsapp'] ?? '');
  $metodosPago  = trim($conf['metodos_pago'] ?? '');
  $politica     = trim($conf['politica_cambios'] ?? '');

  $instagram = $usuarioRed($conf['tienda_instagram'] ?? '');
  $facebook  = $usuarioRed($conf['tienda_facebook'] ?? '');
  $tiktok    = $usuarioRed($conf['tienda_tiktok'] ?? '');

  $mostrarRedes = ($conf['footer_mostrar_redes'] ?? '') === '1' && ($instagram || $facebook || $tiktok);
  $hayContacto  = $email || $telefono || $whatsapp || $direccion;

  // Medios de pago como etiquetas ("Efectivo, Transferencia y Mercado Pago" → 3 etiquetas)
  $medios = array_filter(array_map('trim', preg_split('/\s*(?:,|\/|·|\by\b|\be\b)\s*/u', $metodosPago)));

  // Categorías: las mismas del menú (las arma el encabezado)
  $catsFooter = $itemsMenu ?? [];

  $textoFooter = trim($conf['footer_texto'] ?? '');
  if ($textoFooter === '') {
      $textoFooter = '© ' . date('Y') . ' ' . $nombreTienda;
  }

  $titulo = 'text-xs font-semibold uppercase tracking-widest opacity-50 mb-4';
  $link   = 'opacity-75 hover:opacity-100 transition';
?>
<footer style="background-color: var(--color-footer-bg); color:#fff" class="mt-16">
  <div class="max-w-7xl mx-auto px-4 pt-14 pb-8">

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-10">

      <!-- ── Marca ─────────────────────────────────────────────── -->
      <div class="lg:col-span-4">
        <?php if (!empty($conf['tienda_logo'])): ?>
          <img src="<?= BASE_URL ?>/<?= htmlspecialchars($conf['tienda_logo']) ?>"
               alt="<?= htmlspecialchars($nombreTienda) ?>"
               class="h-12 mb-4 object-contain">
        <?php else: ?>
          <p class="text-2xl font-bold mb-3"><?= htmlspecialchars($nombreTienda) ?></p>
        <?php endif; ?>

        <?php if ($descripcion !== ''): ?>
          <p class="text-sm opacity-70 max-w-sm leading-relaxed"><?= nl2br(htmlspecialchars($descripcion)) ?></p>
        <?php endif; ?>

        <?php if ($mostrarRedes): ?>
          <div class="flex gap-3 mt-6">
            <?php if ($instagram): ?>
              <a href="https://instagram.com/<?= htmlspecialchars($instagram) ?>" target="_blank" rel="noopener" aria-label="Instagram"
                 class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/>
                  <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
                </svg>
              </a>
            <?php endif; ?>
            <?php if ($facebook): ?>
              <a href="https://facebook.com/<?= htmlspecialchars($facebook) ?>" target="_blank" rel="noopener" aria-label="Facebook"
                 class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M14 8h3V4h-3a5 5 0 0 0-5 5v2H7v4h2v7h4v-7h3l1-4h-4V9a1 1 0 0 1 1-1z"/>
                </svg>
              </a>
            <?php endif; ?>
            <?php if ($tiktok): ?>
              <a href="https://tiktok.com/@<?= htmlspecialchars($tiktok) ?>" target="_blank" rel="noopener" aria-label="TikTok"
                 class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M16 3c.3 2.2 1.7 3.8 4 4.1v3.1c-1.5 0-2.9-.4-4-1.2v6a6 6 0 1 1-6-6v3.1a2.9 2.9 0 1 0 2.9 2.9V3H16z"/>
                </svg>
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- ── Categorías ───────────────────────────────────────── -->
      <?php if ($catsFooter): ?>
        <div class="lg:col-span-3">
          <p class="<?= $titulo ?>">Categorías</p>
          <ul class="text-sm grid <?= count($catsFooter) > 6 ? 'grid-cols-2 gap-x-6' : 'grid-cols-1' ?> gap-y-2.5">
            <?php foreach ($catsFooter as $c): ?>
              <li>
                <a href="<?= BASE_URL ?>/categoria/<?= urlencode($c['slug']) ?>" class="<?= $link ?>">
                  <?= htmlspecialchars($c['nombre']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- ── Ayuda ────────────────────────────────────────────── -->
      <div class="lg:col-span-2">
        <p class="<?= $titulo ?>">Ayuda</p>
        <ul class="space-y-2.5 text-sm">
          <?php if (isset($_SESSION['cliente'])): ?>
            <li><a href="<?= BASE_URL ?>/mi-cuenta/pedidos" class="<?= $link ?>">Mis pedidos</a></li>
          <?php else: ?>
            <li><a href="<?= BASE_URL ?>/ingresar" class="<?= $link ?>">Ingresar</a></li>
            <li><a href="<?= BASE_URL ?>/registro" class="<?= $link ?>">Crear cuenta</a></li>
          <?php endif; ?>
          <li><a href="<?= BASE_URL ?>/carrito" class="<?= $link ?>">Carrito</a></li>
          <?php if ($politica): ?>
            <li>
              <details class="group">
                <summary class="cursor-pointer list-none flex items-center gap-1.5 <?= $link ?>">
                  Cambios y devoluciones
                  <svg class="w-3 h-3 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                  </svg>
                </summary>
                <p class="mt-3 opacity-70 whitespace-pre-line leading-relaxed"><?= htmlspecialchars($politica) ?></p>
              </details>
            </li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- ── Contacto ─────────────────────────────────────────── -->
      <?php if ($hayContacto): ?>
        <div class="lg:col-span-3">
          <p class="<?= $titulo ?>">Contacto</p>
          <ul class="space-y-3 text-sm">
            <?php if ($whatsapp): ?>
              <li>
                <a href="https://wa.me/<?= $whatsapp ?>" target="_blank" rel="noopener" class="flex items-center gap-3 <?= $link ?>">
                  <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2z"/>
                  </svg>
                  WhatsApp
                </a>
              </li>
            <?php endif; ?>
            <?php if ($telefono): ?>
              <li>
                <a href="tel:<?= htmlspecialchars(preg_replace('/[^\d+]/', '', $telefono)) ?>" class="flex items-center gap-3 <?= $link ?>">
                  <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>
                  </svg>
                  <?= htmlspecialchars($telefono) ?>
                </a>
              </li>
            <?php endif; ?>
            <?php if ($email): ?>
              <li>
                <a href="mailto:<?= htmlspecialchars($email) ?>" class="flex items-center gap-3 break-all <?= $link ?>">
                  <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="2" y="4" width="20" height="16" rx="2"/><path stroke-linecap="round" d="m22 7-10 6L2 7"/>
                  </svg>
                  <?= htmlspecialchars($email) ?>
                </a>
              </li>
            <?php endif; ?>
            <?php if ($direccion): ?>
              <li>
                <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($direccion) ?>" target="_blank" rel="noopener"
                   class="flex items-start gap-3 <?= $link ?>">
                  <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                  </svg>
                  <?= htmlspecialchars($direccion) ?>
                </a>
              </li>
            <?php endif; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>

    <!-- ── Medios de pago ─────────────────────────────────────────── -->
    <?php if ($medios): ?>
      <div class="mt-12 pt-8 border-t border-white/10 flex flex-col md:flex-row md:items-center gap-3">
        <p class="text-xs font-semibold uppercase tracking-widest opacity-50 shrink-0 md:mr-3">Medios de pago</p>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($medios as $m): ?>
            <span class="px-3 py-1 rounded-full bg-white/10 text-xs"><?= htmlspecialchars($m) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- ── Pie ────────────────────────────────────────────────────── -->
    <div class="<?= $medios ? 'mt-8' : 'mt-12 border-t border-white/10' ?> pt-6 flex flex-col md:flex-row md:items-center justify-between gap-3 text-xs">
      <p class="opacity-50"><?= htmlspecialchars($textoFooter) ?></p>

      <p class="opacity-50 hover:opacity-90 transition flex items-center gap-1.5">
        Hecho con
        <svg class="w-3.5 h-3.5 text-red-400" fill="currentColor" viewBox="0 0 24 24" aria-label="amor">
          <path d="M12 21s-7.5-4.6-9.6-9.4C.9 8.2 3 4.5 6.6 4.5c2 0 3.6 1.1 4.4 2.6.8-1.5 2.4-2.6 4.4-2.6 3.6 0 5.7 3.7 4.2 7.1C19.5 16.4 12 21 12 21z"/>
        </svg>
        por
        <?php if ($creditoUrl): ?>
          <a href="<?= htmlspecialchars($creditoUrl) ?>" target="_blank" rel="noopener" class="font-semibold underline-offset-2 hover:underline">
            <?= htmlspecialchars($creditoNombre) ?>
          </a>
        <?php else: ?>
          <span class="font-semibold"><?= htmlspecialchars($creditoNombre) ?></span>
        <?php endif; ?>
      </p>
    </div>

  </div>
</footer>

<?php if ($whatsapp): ?>
  <!-- Botón flotante de WhatsApp -->
  <a href="https://wa.me/<?= $whatsapp ?>?text=<?= rawurlencode('Hola! Quería hacer una consulta.') ?>"
     target="_blank" rel="noopener"
     aria-label="Escribinos por WhatsApp"
     class="fixed bottom-5 right-5 z-50 w-14 h-14 rounded-full bg-green-500 hover:bg-green-600 text-white shadow-lg hover:scale-105 transition flex items-center justify-center">
    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
      <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.3-.4.8-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 11.9 11.9 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7.5-.1 1.5-.6 1.8-1.2.2-.6.2-1.1.1-1.2l-.4-.3z"/>
    </svg>
  </a>
<?php endif; ?>