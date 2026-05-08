<section class="max-w-md mx-auto px-4 py-16">

    <h1 class="text-3xl font-bold text-gray-900 mb-2">Nueva contraseña</h1>
    <p class="text-gray-500 mb-8">Elegí una contraseña nueva para tu cuenta.</p>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'password'): ?>
        <div class="bg-red-50 text-red-700 rounded-xl p-4 mb-4 text-sm">
            Las contraseñas no coinciden o son muy cortas (mínimo 6 caracteres).
        </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/guardar-nueva-password" method="POST"
          class="space-y-4 bg-white border rounded-3xl p-6">

        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div>
            <label class="block text-sm font-medium mb-1">Nueva contraseña</label>
            <input type="password" name="password" required minlength="6" autofocus
                   class="w-full border rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-300"
                   placeholder="Mínimo 6 caracteres">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Repetir contraseña</label>
            <input type="password" name="confirma" required minlength="6"
                   class="w-full border rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-300">
        </div>

        <button class="w-full bg-gray-900 text-white py-4 rounded-full font-semibold hover:bg-gray-800">
            Guardar nueva contraseña
        </button>

    </form>

</section>