<?php
$msg   = $_SESSION['est_msg'] ?? null;
$nuevo = $_SESSION['est_nuevo'] ?? null;
unset($_SESSION['est_msg'], $_SESSION['est_nuevo']);
?>

<?php if ($msg): ?>
    <div class="mb-4 px-4 py-3 rounded-xl text-sm border
        <?= $msg[0] === 'ok' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
        <?= htmlspecialchars($msg[1]) ?>
    </div>
<?php endif; ?>

<!-- ── Crear link ────────────────────────────────────────────────── -->
<div class="bg-white rounded-lg shadow p-5 mb-6">
    <h3 class="font-bold text-gray-800 mb-1">📣 Crear un link de campaña</h3>
    <p class="text-xs text-gray-400 mb-4">
        Usá un link distinto en cada publicación (historia, posteo, grupo de WhatsApp) y vas a saber cuál trae más gente y más ventas.
    </p>

    <form method="POST" action="<?= BASE_URL ?>/admin/estadisticas/campania/crear"
          class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
        <?= csrf_field() ?>
        <label class="md:col-span-2">
            <span class="block text-xs text-gray-500 mb-1">Nombre</span>
            <input name="nombre" required maxlength="100" placeholder="Ej: Historia lunes NB 530"
                   class="w-full border rounded-lg px-3 py-2">
        </label>
        <label class="md:col-span-2">
            <span class="block text-xs text-gray-500 mb-1">¿A qué página lleva?</span>
            <input name="destino" placeholder="Pegá el link de un producto o categoría (vacío = inicio)"
                   class="w-full border rounded-lg px-3 py-2">
        </label>
        <button class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-800">Crear link</button>
    </form>
</div>

<!-- ── Campañas y resultados ─────────────────────────────────────── -->
<?php if (empty($campanias)): ?>
    <div class="bg-white rounded-lg shadow p-10 text-center text-gray-500">
        <p class="text-4xl mb-3">🔗</p>
        <p class="font-medium">Todavía no creaste ningún link.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow divide-y">
        <?php foreach ($campanias as $c): ?>
            <?php
            $link    = Campania::link($c);
            $esNueva = $nuevo === $c['codigo'];
            ?>
            <div class="px-5 py-4 <?= $esNueva ? 'bg-green-50' : '' ?>">
                <div class="flex flex-col lg:flex-row lg:items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900"><?= htmlspecialchars($c['nombre']) ?></p>
                        <div class="flex items-center gap-2 mt-1">
                            <code class="text-xs text-gray-600 bg-gray-50 border rounded px-2 py-1 truncate"><?= htmlspecialchars($link) ?></code>
                            <button type="button" data-link="<?= htmlspecialchars($link) ?>" onclick="copiarLink(this)"
                                    class="shrink-0 text-xs px-2 py-1 rounded border hover:bg-gray-50">📋 Copiar</button>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Creada el <?= date('d/m/Y', strtotime($c['creado_at'])) ?></p>
                    </div>

                    <div class="grid grid-cols-4 gap-4 text-center text-sm shrink-0">
                        <div><p class="text-lg font-bold"><?= $num($c['visitantes']) ?></p><p class="text-[11px] text-gray-400">entraron</p></div>
                        <div><p class="text-lg font-bold"><?= $num($c['agregaron']) ?></p><p class="text-[11px] text-gray-400">al carrito</p></div>
                        <div><p class="text-lg font-bold text-green-700"><?= $num($c['pedidos']) ?></p><p class="text-[11px] text-gray-400">ventas</p></div>
                        <div><p class="text-lg font-bold"><?= $pesos($c['facturado']) ?></p><p class="text-[11px] text-gray-400">facturado</p></div>
                    </div>

                    <?= boton_eliminar(
                        BASE_URL . '/admin/estadisticas/campania/eliminar',
                        ['id' => $c['id_campania']],
                        '¿Eliminar este link? Lo que ya trajo queda guardado, pero deja de aparecer acá.',
                        '✕',
                        'text-gray-300 hover:text-red-600 text-lg px-2'
                    ) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="text-xs text-gray-400 mt-3">
        Los resultados son del período elegido arriba. Una venta cuenta para la campaña si el cliente entró por ese link en los 7 días anteriores.
    </p>
<?php endif; ?>

<script>
function copiarLink(btn) {
    navigator.clipboard.writeText(btn.dataset.link).then(() => {
        const txt = btn.textContent;
        btn.textContent = '✓ Copiado';
        setTimeout(() => btn.textContent = txt, 1500);
    });
}
</script>