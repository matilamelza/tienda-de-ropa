<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Pedidos</h2>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">

<table class="w-full text-sm">
<thead class="bg-gray-100">
<tr>
    <th class="px-4 py-3 text-left">#</th>
    <th class="px-4 py-3 text-left">Cliente</th>
    <th class="px-4 py-3 text-left">Teléfono</th>
    <th class="px-4 py-3 text-right">Total</th>
    <th class="px-4 py-3 text-center">Estado</th>
    <th class="px-4 py-3 text-right">Acciones</th>
</tr>
</thead>

<tbody>

<?php while ($p = $pedidos->fetch_assoc()): ?>

<tr class="border-t">
    <td class="px-4 py-3">
        #<?php echo $p['id_pedido']; ?>
    </td>

    <td class="px-4 py-3">
        <?php echo htmlspecialchars($p['nombre']. ' ' . $p['apellido']); ?>
    </td>

    <td class="px-4 py-3">
        <?php echo htmlspecialchars($p['telefono']); ?>
    </td>

    <td class="px-4 py-3 text-right">
        $<?php echo number_format($p['total'], 2, ',', '.'); ?>
    </td>

    <td class="px-4 py-3 text-center">
        <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded">
            <?php echo $p['estado']; ?>
        </span>
    </td>

    <td class="px-4 py-3 text-right">
        <a href="index.php?route=admin_pedido_detalle&id=<?php echo $p['id_pedido']; ?>">
            Ver
        </a>
    </td>
</tr>

<?php endwhile; ?>

</tbody>
</table>

</div>