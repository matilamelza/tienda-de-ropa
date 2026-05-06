<?php
  require_once __DIR__ . '/../../app/Models/ConfiguracionTienda.php';
  $cfg = new ConfiguracionTienda();
  $conf = $cfg->todas();
?>
<footer style="background-color: var(--color-footer-bg); color:#fff" class="py-10">
  <div class="max-w-7xl mx-auto px-4 text-center">
 
    <?php if (!empty($conf['footer_mostrar_redes']) && $conf['footer_mostrar_redes'] === '1'): ?>
      <div class="flex justify-center gap-5 mb-4">
        <?php if (!empty($conf['tienda_instagram'])): ?>
          <a href="https://instagram.com/<?= htmlspecialchars($conf['tienda_instagram']) ?>"
             target="_blank" class="hover:opacity-70">Instagram</a>
        <?php endif; ?>
        <?php if (!empty($conf['tienda_facebook'])): ?>
          <a href="https://facebook.com/<?= htmlspecialchars($conf['tienda_facebook']) ?>"
             target="_blank" class="hover:opacity-70">Facebook</a>
        <?php endif; ?>
        <?php if (!empty($conf['tienda_tiktok'])): ?>
          <a href="https://tiktok.com/@<?= htmlspecialchars($conf['tienda_tiktok']) ?>"
             target="_blank" class="hover:opacity-70">TikTok</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
 
    <p class="text-sm opacity-70"><?= htmlspecialchars($conf['footer_texto'] ?? '') ?></p>
    
    <p class="text-xs opacity-50 mt-2">Desarrollado por Matias Lamelza</p>
  </div>
</footer>