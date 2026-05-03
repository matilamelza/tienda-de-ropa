<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tienda de Ropa</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-white text-gray-900">

<header class="border-b bg-white sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4">

        <div class="h-20 flex items-center justify-between">

            <a href="<?= BASE_URL ?>/tienda" class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-gray-900 text-white flex items-center justify-center font-bold">
                    LOGO
                </div>
                <div>
                    <h1 class="text-xl font-bold leading-none">Mi Tienda</h1>
                    <!-- <p class="text-xs text-gray-500">Ropa urbana</p> -->
                </div>
            </a>

            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="<?= BASE_URL ?>/tienda" class="hover:text-gray-500">Inicio</a>

                <?php if (isset($categoriasMenu) && $categoriasMenu && $categoriasMenu->num_rows > 0): ?>
                    <?php while ($catMenu = $categoriasMenu->fetch_assoc()): ?>
                        <a href="<?= BASE_URL ?>/categoria/<?php echo urlencode($catMenu['slug']); ?>"
                           class="hover:text-gray-500">
                            <?php echo htmlspecialchars($catMenu['nombre']); ?>
                        </a>
                    <?php endwhile; ?>
                <?php endif; ?>
            </nav>

            <div class="hidden md:flex items-center gap-4">

                <?php if (isset($_SESSION['cliente'])): ?>
                   <a href="<?= BASE_URL ?>/mi-cuenta/pedidos"
   class="text-sm text-gray-500 hover:text-gray-900">
    Mis pedidos
</a>

                    <a href="<?= BASE_URL ?>/salir" class="text-sm font-medium hover:text-gray-500">
                        Salir
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/ingresar" class="text-sm font-medium hover:text-gray-500">
                        Ingresar
                    </a>
                <?php endif; ?>

                <a href="<?= BASE_URL ?>/carrito" class="text-sm font-medium hover:text-gray-500">
                    Carrito
                </a>

            </div>

            <button type="button"
                    onclick="toggleMenuMobile()"
                    class="md:hidden border rounded-lg px-3 py-2 text-sm">
                Menú
            </button>

        </div>

        <div id="menuMobile" class="hidden md:hidden border-t py-4">
            <nav class="flex flex-col gap-3 text-sm font-medium">
                <a href="<?= BASE_URL ?>/tienda" class="py-2">
                    Inicio
                </a>

                <?php
                $categoriaModelMobile = new Categoria();
                $categoriasMobile = $categoriaModelMobile->listarMenu();
                ?>

                <?php while ($catMobile = $categoriasMobile->fetch_assoc()): ?>
                    <a href="<?= BASE_URL ?>/categoria/<?php echo urlencode($catMobile['slug']); ?>"
                       class="py-2 border-t">
                        <?php echo htmlspecialchars($catMobile['nombre']); ?>
                    </a>
                <?php endwhile; ?>

                <?php if (isset($_SESSION['cliente'])): ?>
    <a href="<?= BASE_URL ?>/salir" class="py-2 border-t">
        Salir
    </a>
<?php else: ?>
    <a href="<?= BASE_URL ?>/ingresar" class="py-2 border-t">
        Ingresar
    </a>
<?php endif; ?>

                <a href="<?= BASE_URL ?>/carrito" class="py-2 border-t">
                    Carrito
                </a>
            </nav>
        </div>

    </div>
</header>

<script>
function toggleMenuMobile() {
    document.getElementById('menuMobile').classList.toggle('hidden');
}
</script>