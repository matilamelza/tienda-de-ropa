<a href="index.php?route=admin_pedidos"
   class="block px-4 py-2 rounded hover:bg-gray-800">
    Pedidos
</a><?php
$estados = [
    'pendiente_contacto' => 'Pendiente de contacto',
    'contactado' => 'Contactado',
    'pendiente_pago' => 'Pendiente de pago',
    'pagado' => 'Pagado',
    'cancelado' => 'Cancelado',
    'entregado' => 'Entregado'
];

$telefonoLimpio = preg_replace('/\D/', '', $pedido['telefono'] ?? '');

if (strlen($telefonoLimpio) > 0 && substr($telefonoLimpio, 0, 2) !== '54') {
    $telefonoLimpio = '54' . $telefonoLimpio;
}

$mensaje = "Hola " . ($pedido['nombre'] ?? '') . ", te escribimos por tu pedido #" . $pedido['id_pedido'] . ".%0A";
$mensaje .= "Total: $" . number_format($pedido['total'], 2, ',', '.') . "%0A";
$mensaje .= "Estado actual: " . ($estados[$pedido['estado']] ?? $pedido['estado']);

$linkWhatsapp = "https://wa.me/" . $telefonoLimpio . "?text=" . $mensaje;
?>

<div class="mb-6 flex justify-between items-start">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">
            Pedido #<?php echo $pedido['id_pedido']; ?>
        </h2>
        <p class="text-gray-500">
            Gestión del pedido y contacto con el cliente
        </p>
    </div>

    <a href="index.php?route=admin_pedidos"
       class="px-4 py-2 rounded-lg border bg-white text-gray-700">
        Volver
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-6">

        <div class="bg-white rounded-lg shadow p-6">

            <h3 class="text-lg font-bold text-gray-800 mb-4">
                Datos del cliente
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div>
                    <p class="text-gray-500">Nombre</p>
                    <p class="font-medium">
                        <?php echo htmlspecialchars(($pedido['nombre'] ?? '') . ' ' . ($pedido['apellido'] ?? '')); ?>
                    </p>
                </div>

                <div>
                    <p class="text-gray-500">Teléfono</p>
                    <p class="font-medium">
                        <?php echo htmlspecialchars($pedido['telefono'] ?? '-'); ?>
                    </p>
                </div>

                <div>
                    <p class="text-gray-500">Email</p>
                    <p class="font-medium">
                        <?php echo htmlspecialchars($pedido['email'] ?? '-'); ?>
                    </p>
                </div>

                <div>
                    <p class="text-gray-500">Localidad</p>
                    <p class="font-medium">
                        <?php echo htmlspecialchars($pedido['localidad'] ?? '-'); ?>
                    </p>
                </div>

                <div class="md:col-span-2">
                    <p class="text-gray-500">Dirección</p>
                    <p class="font-medium">
                        <?php echo htmlspecialchars($pedido['direccion'] ?? '-'); ?>
                    </p>
                </div>

            </div>

        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">

            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-bold text-gray-800">
                    Productos del pedido
                </h3>
            </div>

            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="text-left px-4 py-3">Producto</th>
                        <th class="text-left px-4 py-3">Talle</th>
                        <th class="text-left px-4 py-3">Color</th>
                        <th class="text-center px-4 py-3">Cantidad</th>
                        <th class="text-right px-4 py-3">Precio</th>
                        <th class="text-right px-4 py-3">Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($i = $items->fetch_assoc()): ?>
                        <tr class="border-t">
                            <td class="px-4 py-3 font-medium">
                                <?php echo htmlspecialchars($i['producto']); ?>
                            </td>

                            <td class="px-4 py-3">
                                <?php echo htmlspecialchars($i['talle'] ?? '-'); ?>
                            </td>

                            <td class="px-4 py-3">
                                <?php echo htmlspecialchars($i['color'] ?? '-'); ?>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <?php echo $i['cantidad']; ?>
                            </td>

                            <td class="px-4 py-3 text-right">
                                $<?php echo number_format($i['precio_unitario'], 2, ',', '.'); ?>
                            </td>

                            <td class="px-4 py-3 text-right font-bold">
                                $<?php echo number_format($i['subtotal'], 2, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        </div>

    </div>

    <div class="space-y-6">

        <div class="bg-white rounded-lg shadow p-6">

            <h3 class="text-lg font-bold text-gray-800 mb-4">
                Resumen
            </h3>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Total</span>
                    <strong>
                        $<?php echo number_format($pedido['total'], 2, ',', '.'); ?>
                    </strong>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Estado</span>
                    <strong>
                        <?php echo htmlspecialchars($estados[$pedido['estado']] ?? $pedido['estado']); ?>
                    </strong>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-500">Fecha</span>
                    <strong>
                        <?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?>
                    </strong>
                </div>
            </div>

        </div>

        <div class="bg-white rounded-lg shadow p-6">

            <h3 class="text-lg font-bold text-gray-800 mb-4">
                Cambiar estado
            </h3>

            <form action="index.php?route=admin_pedido_estado" method="POST" class="space-y-4">

                <input type="hidden" name="id_pedido" value="<?php echo $pedido['id_pedido']; ?>">

                <select name="estado" class="w-full border rounded-lg px-3 py-2 bg-white">
                    <?php foreach ($estados as $key => $label): ?>
                        <option value="<?php echo $key; ?>"
                            <?php echo $pedido['estado'] == $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit"
                        class="w-full bg-gray-900 text-white py-2 rounded-lg hover:bg-gray-800">
                    Guardar estado
                </button>

            </form>

        </div>

        <div class="bg-white rounded-lg shadow p-6">

            <h3 class="text-lg font-bold text-gray-800 mb-4">
                Contacto
            </h3>

            <?php if (!empty($telefonoLimpio)): ?>
                <a href="<?php echo $linkWhatsapp; ?>"
                   target="_blank"
                   class="block text-center w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700">
                    Contactar por WhatsApp
                </a>
            <?php else: ?>
                <div class="bg-red-50 text-red-700 rounded-lg p-3 text-sm">
                    Este pedido no tiene teléfono cargado.
                </div>
            <?php endif; ?>

        </div>

    </div>

</div>