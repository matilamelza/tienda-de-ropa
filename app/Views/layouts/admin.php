<?php require __DIR__ . '/header_admin.php'; ?>

<div class="min-h-screen flex bg-gray-100">
    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="flex-1 p-6">
        <?php require $viewPath; ?>
    </main>
</div>

<?php require __DIR__ . '/footer_admin.php'; ?>