<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Recuperación de contraseñas</h2>
    <p class="text-gray-500 text-sm mt-1">Links de reset solicitados por clientes. Copiá el link y enviáselo por WhatsApp o email.</p>
</div>

<?php
$resets = $_SESSION['admin_resets_pendientes'] ?? [];
?>

<?php if (empty($resets)): ?>
    <div class="bg-white rounded-xl border p-10 text-center text-gray-400">
        <p class="text-4xl mb-3">🔑</p>
        <p class="font-medium">No hay solicitudes pendientes.</p>
        <p class="text-sm mt-1">Cuando un cliente solicite recuperar su contraseña, aparecerá acá.</p>
    </div>
<?php else: ?>

    <div class="space-y-4">
        <?php foreach (array_reverse($resets) as $i => $reset): ?>
            <div class="bg-white border rounded-xl p-5 flex flex-col sm:flex-row sm:items-center gap-4">

                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-semibold text-gray-900">
                            <?= htmlspecialchars($reset['email']) ?>
                        </span>
                        <span class="text-xs text-gray-400"><?= $reset['fecha'] ?></span>
                    </div>
                    <p class="text-xs text-gray-500 font-mono break-all bg-gray-50 rounded-lg px-3 py-2 mt-2">
                        <?= htmlspecialchars($reset['link']) ?>
                    </p>
                </div>

                <div class="flex gap-2 flex-shrink-0">
                    <button onclick="copiarLink(this, '<?= htmlspecialchars($reset['link']) ?>')"
                            class="px-4 py-2 text-sm bg-gray-800 text-white rounded-lg hover:bg-gray-700">
                        Copiar link
                    </button>
                    <?php
                    // Link de WhatsApp directo si hay número guardado
                    $config = require dirname(__DIR__, 4) . '/config/database.php';
                    $wa = $config['whatsapp_tienda'] ?? '';
                    $msg = urlencode("Hola, te mando el link para restablecer tu contraseña: " . $reset['link']);
                    ?>
                    <?php if ($wa): ?>
                        <a href="https://wa.me/<?= $wa ?>?text=<?= $msg ?>" target="_blank"
                           class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700">
                            WhatsApp
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4 text-right">
        <a href="<?= BASE_URL ?>/admin/resets/limpiar"
           onclick="return confirm('¿Limpiar todas las solicitudes vistas?')"
           class="text-sm text-gray-400 hover:text-red-500">
            Limpiar lista
        </a>
    </div>

<?php endif; ?>

<script>
function copiarLink(btn, link) {
    navigator.clipboard.writeText(link).then(() => {
        const original = btn.textContent;
        btn.textContent = '¡Copiado!';
        btn.classList.add('bg-green-700');
        setTimeout(() => {
            btn.textContent = original;
            btn.classList.remove('bg-green-700');
        }, 2000);
    });
}
</script>