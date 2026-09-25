<?php
  require_once dirname(__DIR__, 2) . '/Models/ConfiguracionTienda.php';
  $cfg  = new ConfiguracionTienda();
  $conf = $cfg->todas();

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
  $hayCompras   = $metodosPago || $politica;

  $textoFooter = trim($conf['footer_texto'] ?? '');
  if ($textoFooter === '') {
      $textoFooter = '© ' . date('Y') . ' ' . $nombreTienda;
  }
?>
<footer style="background-color: var(--color-footer-bg); color:#fff" class="mt-16">
  <div class="max-w-7xl mx-auto px-4 py-12">

    <div class="grid grid-cols-1 md:grid-cols-3 gap-10">

      <!-- Marca -->
      <div>
        <?php if (!empty($conf['tienda_logo'])): ?>
          <img src="<?= BASE_URL ?>/<?= htmlspecialchars($conf['tienda_logo']) ?>"
               alt="<?= htmlspecialchars($nombreTienda) ?>"
               class="h-10 mb-4 object-contain">
        <?php else: ?>
          <p class="text-xl font-bold mb-3"><?= htmlspecialchars($nombreTienda) ?></p>
        <?php endif; ?>

        <?php if ($descripcion !== ''): ?>
          <p class="text-sm opacity-70 max-w-xs"><?= nl2br(htmlspecialchars($descripcion)) ?></p>
        <?php endif; ?>

        <?php if ($mostrarRedes): ?>
          <div class="flex gap-3 mt-5">
            <?php if ($instagram): ?>
              <a href="https://instagram.com/<?= htmlspecialchars($instagram) ?>" target="_blank" rel="noopener"
                 aria-label="Instagram"
                 class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <rect x="3" y="3" width="18" height="18" rx="5"/>
                  <circle cx="12" cy="12" r="4"/>
                  <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
                </svg>
              </a>
            <?php endif; ?>
            <?php if ($facebook): ?>
              <a href="https://facebook.com/<?= htmlspecialchars($facebook) ?>" target="_blank" rel="noopener"
                 aria-label="Facebook"
                 class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M14 8h3V4h-3a5 5 0 0 0-5 5v2H7v4h2v7h4v-7h3l1-4h-4V9a1 1 0 0 1 1-1z"/>
                </svg>
              </a>
            <?php endif; ?>
            <?php if ($tiktok): ?>
              <a href="https://tiktok.com/@<?= htmlspecialchars($tiktok) ?>" target="_blank" rel="noopener"
                 aria-label="TikTok"
                 class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M16 3c.3 2.2 1.7 3.8 4 4.1v3.1c-1.5 0-2.9-.4-4-1.2v6a6 6 0 1 1-6-6v3.1a2.9 2.9 0 1 0 2.9 2.9V3H16z"/>
                </svg>
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Contacto -->
      <?php if ($hayContacto): ?>
        <div>
          <p class="text-sm font-semibold uppercase tracking-wider opacity-60 mb-4">Contacto</p>
          <ul class="space-y-3 text-sm">
            <?php if ($whatsapp): ?>
              <li>
                <a href="https://wa.me/<?= $whatsapp ?>" target="_blank" rel="noopener" class="opacity-80 hover:opacity-100">
                  💬 WhatsApp
                </a>
              </li>
            <?php endif; ?>
            <?php if ($telefono): ?>
              <li>
                <a href="tel:<?= htmlspecialchars(preg_replace('/[^\d+]/', '', $telefono)) ?>" class="opacity-80 hover:opacity-100">
                  📞 <?= htmlspecialchars($telefono) ?>
                </a>
              </li>
            <?php endif; ?>
            <?php if ($email): ?>
              <li>
                <a href="mailto:<?= htmlspecialchars($email) ?>" class="opacity-80 hover:opacity-100 break-all">
                  ✉️ <?= htmlspecialchars($email) ?>
                </a>
              </li>
            <?php endif; ?>
            <?php if ($direccion): ?>
              <li>
                <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($direccion) ?>"
                   target="_blank" rel="noopener" class="opacity-80 hover:opacity-100">
                  📍 <?= htmlspecialchars($direccion) ?>
                </a>
              </li>
            <?php endif; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Compras -->
      <?php if ($hayCompras): ?>
        <div>
          <p class="text-sm font-semibold uppercase tracking-wider opacity-60 mb-4">Compras</p>

          <?php if ($metodosPago): ?>
            <p class="text-sm opacity-60 mb-1">Medios de pago</p>
            <p class="text-sm opacity-90 mb-5"><?= htmlspecialchars($metodosPago) ?></p>
          <?php endif; ?>

          <?php if ($politica): ?>
            <details class="group text-sm">
              <summary class="cursor-pointer opacity-80 hover:opacity-100 list-none flex items-center gap-2">
                Cambios y devoluciones
                <span class="transition group-open:rotate-180">▾</span>
              </summary>
              <p class="mt-3 opacity-70 whitespace-pre-line"><?= htmlspecialchars($politica) ?></p>
            </details>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </div>

    <div class="border-t border-white/10 mt-10 pt-6 flex flex-col md:flex-row justify-between gap-2 text-xs opacity-60">
      <p><?= htmlspecialchars($textoFooter) ?></p>
      <p>Desarrollado por Matias Lamelza</p>
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