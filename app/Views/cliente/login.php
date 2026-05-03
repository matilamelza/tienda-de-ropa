<section class="max-w-md mx-auto px-4 py-16">

    <h1 class="text-3xl font-bold text-gray-900 mb-2">Ingresar</h1>
    <p class="text-gray-500 mb-8">Accedé para ver tus pedidos y finalizar más rápido.</p>

    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-50 text-red-700 rounded-xl p-4 mb-4">
            Email o contraseña incorrectos.
        </div>
    <?php endif; ?>

   <form action="<?= BASE_URL ?>/cliente/ingresar"  method="POST" class="space-y-4 bg-white border rounded-3xl p-6">

        <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" required class="w-full border rounded-xl px-4 py-3">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Contraseña</label>
            <input type="password" name="password" required class="w-full border rounded-xl px-4 py-3">
        </div>

        <button class="w-full bg-gray-900 text-white py-4 rounded-full font-semibold">
            Ingresar
        </button>

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