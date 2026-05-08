<section class="max-w-md mx-auto px-4 py-16">

    <h1 class="text-3xl font-bold text-gray-900 mb-2">Olvidé mi contraseña</h1>
    <p class="text-gray-500 mb-8">Ingresá tu email y te enviamos instrucciones para crear una nueva.</p>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'token'): ?>
        <div class="bg-red-50 text-red-700 rounded-xl p-4 mb-4 text-sm">
            El link expiró o ya fue usado. Solicitá uno nuevo.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['enviado'])): ?>
        <div class="bg-green-50 text-green-800 rounded-xl p-5 mb-6 text-sm">
            <p class="font-semibold mb-1">✅ Solicitud recibida.</p>
            <p>Si tu email está registrado, recibirás instrucciones para restablecer tu contraseña en breve.</p>
            <p class="mt-2 text-gray-500">Si no recibís nada en unos minutos, contactá al negocio por WhatsApp.</p>
        </div>
    <?php else: ?>

        <form action="<?= BASE_URL ?>/solicitar-reset" method="POST"
              class="space-y-4 bg-white border rounded-3xl p-6">

            <div>
                <label class="block text-sm font-medium mb-1">Email de tu cuenta</label>
                <input type="email" name="email" required autofocus
                       class="w-full border rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-gray-300"
                       placeholder="tucorreo@email.com">
            </div>

            <button class="w-full bg-gray-900 text-white py-4 rounded-full font-semibold hover:bg-gray-800">
                Enviar instrucciones
            </button>

        </form>

    <?php endif; ?>

    <a href="<?= BASE_URL ?>/ingresar"
       class="block text-center text-sm text-gray-500 hover:text-gray-900 mt-6">
        ← Volver al login
    </a>

</section>