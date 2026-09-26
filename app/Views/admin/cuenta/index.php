<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Mi cuenta</h2>
    <p class="text-gray-500">
        <?= htmlspecialchars($admin['nombre']) ?> · <?= htmlspecialchars($admin['email']) ?>
    </p>
</div>

<?php if (isset($_GET['ok'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm max-w-lg">
        ✓ Contraseña actualizada. La próxima vez ingresá con la nueva.
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm max-w-lg">
        <?php
        $errores = [
            'actual'    => 'La contraseña actual no es correcta.',
            'corta'     => 'La nueva contraseña tiene que tener al menos 8 caracteres.',
            'distintas' => 'La nueva contraseña y la confirmación no coinciden.',
            'igual'     => 'La nueva contraseña es igual a la actual.',
        ];
        echo $errores[$_GET['error']] ?? 'No se pudo cambiar la contraseña.';
        ?>
    </div>
<?php endif; ?>

<div class="max-w-lg">
    <form action="<?= BASE_URL ?>/admin/cuenta/password" method="POST"
          class="bg-white rounded-lg shadow p-6 space-y-5">

        <?= csrf_field() ?>

        <h3 class="font-bold text-gray-800">Cambiar contraseña</h3>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña actual</label>
            <input type="password" name="actual" required autocomplete="current-password"
                   class="campo-pass w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nueva contraseña</label>
            <input type="password" name="nueva" required minlength="8" autocomplete="new-password"
                   class="campo-pass w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200">
            <p class="text-xs text-gray-400 mt-1">Mínimo 8 caracteres.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Repetir nueva contraseña</label>
            <input type="password" name="confirma" required minlength="8" autocomplete="new-password"
                   class="campo-pass w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-gray-200">
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-500">
            <input type="checkbox" onchange="document.querySelectorAll('.campo-pass').forEach(i => i.type = this.checked ? 'text' : 'password')">
            Mostrar contraseñas
        </label>

        <div class="flex justify-end pt-2">
            <button type="submit"
                    class="px-5 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">
                Guardar contraseña
            </button>
        </div>
    </form>
</div>