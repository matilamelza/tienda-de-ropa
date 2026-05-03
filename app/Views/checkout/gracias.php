<section class="max-w-3xl mx-auto px-4 py-20 text-center">

    <div class="bg-gray-50 rounded-3xl p-12">

        <div class="w-16 h-16 rounded-full bg-green-100 text-green-700 flex items-center justify-center mx-auto mb-6 text-2xl">
            ✓
        </div>

        <h1 class="text-3xl font-bold text-gray-900">Solicitud enviada</h1>

        <p class="text-gray-500 mt-3">
            Registramos tu pedido como pendiente. Para continuar, envianos el detalle por WhatsApp.
        </p>

        <p class="mt-6 text-lg font-semibold">
            Pedido #<?php echo $id_pedido; ?>
        </p>

        <?php if (!empty($_SESSION['ultimo_pedido_whatsapp'])): ?>
            <a href="<?php echo $_SESSION['ultimo_pedido_whatsapp']; ?>"
               target="_blank"
               class="inline-block mt-8 bg-green-600 text-white px-6 py-3 rounded-full hover:bg-green-700">
                Enviar por WhatsApp
            </a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/tienda"
           class="block mt-5 text-sm text-gray-500 hover:text-gray-900">
            Volver a la tienda
        </a>

    </div>

</section>