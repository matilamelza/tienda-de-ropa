<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Ingresar</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-md">

        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-gray-900 text-white font-bold text-xl mb-4">
                A
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Panel de administración</h1>
            <p class="text-gray-500 text-sm mt-1">Ingresá para continuar</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm">
                <?php if ($_GET['error'] === 'credenciales'): ?>
                    Email o contraseña incorrectos.
                <?php elseif ($_GET['error'] === 'campos'): ?>
                    Completá todos los campos.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/admin/ingresar" method="POST"
              class="bg-white rounded-2xl shadow-sm border p-8 space-y-5">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Email
                </label>
                <input type="email"
                       name="email"
                       required
                       autofocus
                       class="w-full border rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Contraseña
                </label>
                <input type="password"
                       name="password"
                       required
                       class="w-full border rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900">
            </div>

            <button type="submit"
                    class="w-full bg-gray-900 text-white py-3 rounded-xl font-semibold hover:bg-gray-800 transition text-sm">
                Ingresar
            </button>

        </form>

        <p class="text-center text-xs text-gray-400 mt-6">
            Esta área es solo para administradores.
        </p>

    </div>

</body>
</html>