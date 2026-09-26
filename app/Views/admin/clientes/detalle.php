<?php
$estados = [
    'pendiente_contacto' => ['Pendiente contacto', 'bg-yellow-100 text-yellow-800'],
    'contactado'         => ['Contactado',         'bg-blue-100 text-blue-800'],
    'pendiente_pago'     => ['Pendiente pago',     'bg-orange-100 text-orange-800'],
    'pagado'             => ['Pagado',             'bg-green-100 text-green-800'],
    'cancelado'          => ['Cancelado',          'bg-red-100 text-red-700'],
    'entregado'          => ['Entregado',          'bg-gray-200 text-gray-800'],
];

$nombreCompleto = trim($cliente['nombre'] . ' ' . $cliente['apellido']);
$registrado     = !empty($cliente['id_usuario_cliente']);

// WhatsApp del cliente (celulares argentinos: 549 + número sin 0)
$telefono = preg_replace('/\D/', '', $cliente['telefono'] ?? '');
if ($telefono !== '' && substr($telefono, 0, 2) !== '54') {
    $telefono = '549' . ltrim($telefono, '0');
}

// Contraseña recién asignada (se muestra una sola vez)
$passwordNueva = $_SESSION['flash_password_cliente'] ?? null;
unset($_SESSION['flash_password_cliente']);

$pedidosArray = [];
if ($pedidos) {
    while ($p = $pedidos->fetch_assoc()) {
        $pedidosArray[] = $p;
    }
}
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
    <div class="min-w-0">
        <h2 class="text-2xl font-bold text-gray-800 truncate"><?= htmlspecialchars($nombreCompleto) ?></h2>
        <p class="text-gray-500">Detalle del cliente y pedidos realizados</p>
    </div>

    <a href="<?= BASE_URL ?>/admin/clientes"
       class="self-start px-4 py-2 rounded-lg border bg-white text-gray-700">
        Volver
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- ── Columna izquierda ────────────────────────────────── -->
    <div class="space-y-6">

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Datos del cliente</h3>

            <div class="space-y-3 text-sm">
                <div>
                    <p class="text-gray-500">Teléfono</p>
                    <p class="font-medium"><?= htmlspecialchars($cliente['telefono'] ?: '-') ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Email</p>
                    <p class="font-medium break-all"><?= htmlspecialchars($cliente['email'] ?: '-') ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Localidad</p>
                    <p class="font-medium"><?= htmlspecialchars($cliente['localidad'] ?: '-') ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Dirección</p>
                    <p class="font-medium"><?= htmlspecialchars($cliente['direccion'] ?: '-') ?></p>
                </div>
                <div>
                    <p class="text-gray-500">Tipo</p>
                    <?php if ($registrado): ?>
                        <span class="inline-block mt-1 px-2 py-1 text-xs rounded bg-green-100 text-green-700">Cliente registrado</span>
                    <?php else: ?>
                        <span class="inline-block mt-1 px-2 py-1 text-xs rounded bg-gray-100 text-gray-700">Invitado</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($telefono !== ''): ?>
            <a href="https://wa.me/<?= $telefono ?>?text=<?= rawurlencode('Hola ' . $cliente['nombre'] . ', te escribimos de la tienda.') ?>"
               target="_blank" rel="noopener"
               class="block text-center bg-green-600 text-white py-3 rounded-lg hover:bg-green-700">
                Contactar por WhatsApp
            </a>
        <?php endif; ?>

        <!-- ── Contraseña (solo registrados) ───────────────── -->
        <?php if ($registrado): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-1">Contraseña</h3>
                <p class="text-xs text-gray-400 mb-4">Para cuando el cliente se la olvidó y te la pide.</p>

                <?php if ($passwordNueva !== null): ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4 text-sm">
                        <p class="text-green-800 mb-2">✓ Contraseña actualizada. Mandásela al cliente:</p>
                        <p class="font-mono text-lg font-bold text-gray-900 bg-white border rounded px-3 py-2 select-all">
                            <?= htmlspecialchars($passwordNueva) ?>
                        </p>
                        <?php if ($telefono !== ''): ?>
                            <a href="https://wa.me/<?= $telefono ?>?text=<?= rawurlencode('Hola ' . $cliente['nombre'] . ', tu nueva contraseña para ingresar a la tienda es: ' . $passwordNueva) ?>"
                               target="_blank" rel="noopener"
                               class="mt-3 block text-center bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">
                                Enviar por WhatsApp
                            </a>
                        <?php endif; ?>
                        <p class="text-xs text-gray-400 mt-2">Esta es la única vez que se muestra.</p>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="bg-red-50 text-red-700 rounded-lg p-3 mb-4 text-sm">
                        <?= $_GET['error'] === 'corta' ? 'Mínimo 6 caracteres.' : 'No se pudo cambiar la contraseña.' ?>
                    </div>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/admin/cliente/password" method="POST" class="space-y-3"
                      onsubmit="return confirm('¿Cambiar la contraseña de este cliente? La anterior deja de funcionar.')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_cliente" value="<?= (int) $cliente['id_cliente'] ?>">

                    <div class="flex gap-2">
                        <input type="text" name="nueva" id="passNueva" required minlength="6"
                               placeholder="Nueva contraseña" autocomplete="off"
                               class="flex-1 min-w-0 border rounded-lg px-3 py-2 text-sm font-mono">
                        <button type="button" onclick="generarPass()"
                                class="px-3 py-2 rounded-lg border text-sm text-gray-700 hover:bg-gray-50">
                            Generar
                        </button>
                    </div>

                    <button type="submit" class="w-full bg-gray-900 text-white py-2 rounded-lg text-sm hover:bg-gray-800">
                        Guardar nueva contraseña
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Pedidos ──────────────────────────────────────────── -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-bold text-gray-800">Pedidos del cliente</h3>
            </div>

            <?php if (empty($pedidosArray)): ?>
                <p class="px-6 py-8 text-center text-gray-500 text-sm">Este cliente no tiene pedidos.</p>
            <?php else: ?>

                <!-- Mobile: tarjetas -->
                <div class="md:hidden divide-y">
                    <?php foreach ($pedidosArray as $p): ?>
                        <?php [$txt, $cls] = $estados[$p['estado']] ?? [$p['estado'], 'bg-gray-100 text-gray-700']; ?>
                        <a href="<?= BASE_URL ?>/admin/pedido/<?= (int) $p['id_pedido'] ?>" class="block px-5 py-4 hover:bg-gray-50">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold">#<?= (int) $p['id_pedido'] ?></span>
                                <span class="font-bold">$<?= number_format($p['total'], 2, ',', '.') ?></span>
                            </div>
                            <div class="flex justify-between items-center mt-1 text-sm">
                                <span class="px-2 py-0.5 rounded text-xs <?= $cls ?>"><?= $txt ?></span>
                                <span class="text-gray-400"><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Desktop: tabla -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="text-left px-4 py-3">Pedido</th>
                                <th class="text-center px-4 py-3">Estado</th>
                                <th class="text-left px-4 py-3">Fecha</th>
                                <th class="text-right px-4 py-3">Total</th>
                                <th class="text-right px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidosArray as $p): ?>
                                <?php [$txt, $cls] = $estados[$p['estado']] ?? [$p['estado'], 'bg-gray-100 text-gray-700']; ?>
                                <tr class="border-t">
                                    <td class="px-4 py-3 font-medium">#<?= (int) $p['id_pedido'] ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 rounded text-xs <?= $cls ?>"><?= $txt ?></span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap"><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
                                    <td class="px-4 py-3 text-right font-bold whitespace-nowrap">$<?= number_format($p['total'], 2, ',', '.') ?></td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="<?= BASE_URL ?>/admin/pedido/<?= (int) $p['id_pedido'] ?>" class="text-gray-700 hover:underline">Ver pedido</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </div>
    </div>

</div>

<script>
function generarPass() {
    // Sin caracteres confusos (0/O, 1/l/I) para que sea fácil de dictar o copiar
    const chars = 'abcdefghjkmnpqrstuvwxyz23456789';
    let pass = '';
    const valores = crypto.getRandomValues(new Uint32Array(8));
    valores.forEach(v => pass += chars[v % chars.length]);
    document.getElementById('passNueva').value = pass;
}
</script>