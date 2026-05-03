<div class="mb-6 flex justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Fotos</h2>
        <p class="text-gray-500"><?php echo $producto['nombre']; ?></p>
    </div>

    <a href="index.php?route=productos"
       class="px-4 py-2 border rounded-lg bg-white">
        Volver
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6">

    <div class="bg-white p-4 rounded shadow">
        <form action="index.php?route=productos_subir_foto" method="POST" enctype="multipart/form-data">

            <input type="hidden" name="id_producto" value="<?php echo $producto['id_producto']; ?>">

            <input type="file" name="foto" required class="mb-3">

            <button class="w-full bg-gray-900 text-white py-2 rounded">
                Subir
            </button>

        </form>
    </div>

    <?php while ($f = $fotos->fetch_assoc()): ?>

        <div class="bg-white p-2 rounded shadow">
            <img src="<?= BASE_URL ?>/public/uploads/productos/<?php echo $f['imagen']; ?>"
                 class="w-full h-40 object-cover rounded">
        </div>

    <?php endwhile; ?>

</div>