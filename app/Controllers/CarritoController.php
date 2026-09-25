<?php

class CarritoController extends Controller
{
    public function index()
    {
        $categoriaModel = new Categoria();
        $categoriasMenu = $categoriaModel->listarMenu();

        $carritoModel = new Carrito();

        $items = [];
        $total = 0;

        if (!empty($_SESSION['carrito'])) {
            foreach ($_SESSION['carrito'] as $id_variante => $item) {
                $variante = $carritoModel->buscarVarianteDetalle((int)$id_variante);

                // Si la variante ya no existe, está inactiva o sin stock, se saca del carrito
                if (!$variante || $variante['activo'] != 1 || $variante['disponible'] <= 0) {
                    unset($_SESSION['carrito'][$id_variante]);
                    continue;
                }

                // Si mientras tanto se reservó stock, se ajusta la cantidad
                $cantidad = min((int)$item['cantidad'], (int)$variante['disponible']);
                $_SESSION['carrito'][$id_variante]['cantidad'] = $cantidad;

                $precio = $variante['precio'] !== null && $variante['precio'] !== ''
                    ? $variante['precio']
                    : $variante['precio_base'];

                $subtotal = $precio * $cantidad;

                $items[] = [
                    'variante' => $variante,
                    'precio'   => $precio,
                    'cantidad' => $cantidad,
                    'subtotal' => $subtotal
                ];

                $total += $subtotal;
            }
        }

        $this->view('carrito/index', [
            'items'          => $items,
            'total'          => $total,
            'categoriasMenu' => $categoriasMenu
        ], 'tienda');
    }

    public function agregar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/tienda');
        }

        $id_variante = (int) ($_POST['id_variante'] ?? 0);
        $cantidad    = (int) ($_POST['cantidad'] ?? 1);

        if ($id_variante <= 0) {
            $this->redirect(BASE_URL . '/tienda');
        }

        if ($cantidad < 1) {
            $cantidad = 1;
        }

        $carritoModel = new Carrito();
        $variante     = $carritoModel->buscarVarianteDetalle($id_variante);

        if (!$variante || $variante['activo'] != 1 || $variante['disponible'] <= 0) {
            $this->redirect(BASE_URL . '/tienda');
        }

        $disponible = (int) $variante['disponible'];

        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        $actual = (int) ($_SESSION['carrito'][$id_variante]['cantidad'] ?? 0);

        $_SESSION['carrito'][$id_variante] = [
            'cantidad' => min($actual + $cantidad, $disponible)
        ];

        $this->redirect(BASE_URL . '/carrito');
    }

    public function eliminar()
    {
        $id_variante = (int) ($_GET['id'] ?? 0);

        if ($id_variante > 0 && isset($_SESSION['carrito'][$id_variante])) {
            unset($_SESSION['carrito'][$id_variante]);
        }

        $this->redirect(BASE_URL . '/carrito');
    }

    public function actualizar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/carrito');
        }

        $cantidades   = $_POST['cantidades'] ?? [];
        $carritoModel = new Carrito();

        foreach ($cantidades as $id_variante => $cantidad) {
            $id_variante = (int) $id_variante;
            $cantidad    = (int) $cantidad;

            // Solo se actualizan variantes que ya están en el carrito
            if (!isset($_SESSION['carrito'][$id_variante])) {
                continue;
            }

            if ($cantidad <= 0) {
                unset($_SESSION['carrito'][$id_variante]);
                continue;
            }

            $variante = $carritoModel->buscarVarianteDetalle($id_variante);

            if (!$variante || $variante['activo'] != 1 || $variante['disponible'] <= 0) {
                unset($_SESSION['carrito'][$id_variante]);
                continue;
            }

            $_SESSION['carrito'][$id_variante]['cantidad'] = min($cantidad, (int) $variante['disponible']);
        }

        $this->redirect(BASE_URL . '/carrito');
    }
}