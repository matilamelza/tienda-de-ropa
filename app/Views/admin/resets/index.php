<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Recuperación de contraseñas</h2>
    <p class="text-gray-500 text-sm mt-1">
        Clientes que pidieron recuperar su contraseña. Mandales el link por WhatsApp; vence a las 24 horas.
    </p>
</div>

<?php if (empty($resets)): ?>
    <div class="bg-white rounded-xl border p-10 text-center text-gray-400">
        <p class="text-4xl mb-3">🔑</p>
        <p class="font-medium">No hay solicitudes pendientes.</p>
        <p class="text-sm mt-1">Cuando un cliente pida recuperar su contraseña, va a aparecer acá.</p>
    </div>
<?php else: ?>

    <div class="space-y-4">
        <?php foreach ($resets as $reset): ?>
            <?php
            $link   = $baseLink . $reset['token'];
            $nombre = trim(($reset['nombre'] ?? '') . ' ' . ($reset['apellido'] ?? ''));

            // WhatsApp al CLIENTE (549 + número sin 0 inicial)
            $tel = preg_replace('/\D/', '', $reset['telefono'] ?? '');
            if ($tel !== '' && substr($tel, 0, 2) !== '54') {
                $tel = '549' . ltrim($tel, '0');
            }
            $msg = rawurlencode("Hola" . ($nombre ? " $nombre" : '') . ", te mando el link para crear una nueva contraseña: " . $link);
            ?>
            <div class="bg-white border rounded-xl p-5 flex flex-col sm:flex-row sm:items-center gap-4">

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">
                        <?= htmlspecialchars($nombre ?: $reset['email']) ?>
                    </p>
                    <p class="text-xs text-gray-500">
                        <?= htmlspecialchars($reset['email']) ?>
                        <?php if (!empty($reset['telefono'])): ?> · <?= htmlspecialchars($reset['telefono']) ?><?php endif; ?>
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        Vence: <?= date('d/m H:i', strtotime($reset['expira_en'])) ?>
                    </p>
                    <p class="text-xs text-gray-500 font-mono break-all bg-gray-50 rounded-lg px-3 py-2 mt-2">
                        <?= htmlspecialchars($link) ?>
                    </p>
                </div>

                <div class="flex gap-2 shrink-0">
                    <button type="button"
                            data-link="<?= htmlspecialchars($link) ?>"
                            onclick="copiarLink(this)"
                            class="px-4 py-2 text-sm bg-gray-800 text-white rounded-lg hover:bg-gray-700">
                        Copiar link
                    </button>

                    <?php if ($tel !== ''): ?>
                        <a href="https://wa.me/<?= $tel ?>?text=<?= $msg ?>" target="_blank" rel="noopener"
                           class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700">
                            WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4 text-right">
        <?= boton_eliminar(
            BASE_URL . '/admin/resets/limpiar',
            [],
            '¿Descartar todas las solicitudes? Los links dejan de funcionar.',
            'Limpiar lista',
            'text-sm text-gray-400 hover:text-red-500'
        ) ?>
    </div>

<?php endif; ?>

<script>
function copiarLink(btn) {
    navigator.clipboard.writeText(btn.dataset.link).then(() => {
        const texto = btn.textContent;
        btn.textContent = '¡Copiado!';
        setTimeout(() => btn.textContent = texto, 1500);
    });
}
</script>