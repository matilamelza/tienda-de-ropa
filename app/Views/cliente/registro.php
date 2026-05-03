<section class="max-w-xl mx-auto px-4 py-16">

    <h1 class="text-3xl font-bold text-gray-900 mb-2">Crear cuenta</h1>
    <p class="text-gray-500 mb-8">Guardá tus datos y consultá tus pedidos más adelante.</p>

    <?php if (isset($_GET['error']) && $_GET['error'] == 'email'): ?>
        <div class="bg-red-50 text-red-700 rounded-xl p-4 mb-4">
            Ya existe una cuenta con ese email.
        </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/cliente/guardar-registro" method="POST" class="space-y-4 bg-white border rounded-3xl p-6">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Nombre *</label>
                <input type="text" name="nombre" required class="w-full border rounded-xl px-4 py-3">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Apellido</label>
                <input type="text" name="apellido" class="w-full border rounded-xl px-4 py-3">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Teléfono</label>
            <input type="text" name="telefono" class="w-full border rounded-xl px-4 py-3">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Email *</label>
            <input type="email" name="email" required class="w-full border rounded-xl px-4 py-3">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Contraseña *</label>
            <input type="password" name="password" required minlength="6" class="w-full border rounded-xl px-4 py-3">
        </div>

        <button class="w-full bg-gray-900 text-white py-4 rounded-full font-semibold">
            Crear cuenta
        </button>

        <a href="<?= BASE_URL ?>/ingresar"
           class="block text-center text-sm text-gray-500 hover:text-gray-900">
            Ya tengo cuenta
        </a>

    </form>

</section>