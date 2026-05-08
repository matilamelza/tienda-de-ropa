<section class="max-w-md mx-auto px-4 py-16">

    <h1 class="text-3xl font-bold text-gray-900 mb-2">Ingresar</h1>
    <p class="text-gray-500 mb-8">Accedé para ver tus pedidos y finalizar más rápido.</p>

    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-50 text-red-700 rounded-xl p-4 mb-4 text-sm">
            Email o contraseña incorrectos.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['reset']) && $_GET['reset'] === 'ok'): ?>
        <div class="bg-green-50 text-green-700 rounded-xl p-4 mb-4 text-sm">
            ✅ Contraseña actualizada correctamente. Ya podés ingresar.
        </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/cliente/ingresar" method="POST"
          class="space-y-4 bg-white border rounded-3xl p-6">

        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" required autofocus
                   class="w-full border rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-300">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Contraseña</label>
            <input type="password" name="password" required
                   class="w-full border rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-300">
        </div>

        <button class="w-full bg-gray-900 text-white py-4 rounded-full font-semibold hover:bg-gray-800">
            Ingresar
        </button>

        <a href="<?= BASE_URL ?>/olvide-mi-password"
           class="block text-center text-sm text-gray-400 hover:text-gray-700">
            Olvidé mi contraseña
        </a>

        <hr class="border-gray-100">

        <a href="<?= BASE_URL ?>/registro"
           class="block text-center text-sm text-gray-500 hover:text-gray-900">
            Crear cuenta
        </a>

        <a href="<?= BASE_URL ?>/checkout/invitado"
           class="block text-center text-sm text-gray-500 hover:text-gray-900">
            Continuar como invitado
        </a>

    </form>

</section>