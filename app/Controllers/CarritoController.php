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

                if ($variante) {
                    $precio = $variante['precio'] !== null && $variante['precio'] !== ''
                        ? $variante['precio']
                        : $variante['precio_base'];

                    $cantidad = (int)$item['cantidad'];
                    $subtotal = $precio * $cantidad;

                    $items[] = [
                        'variante' => $variante,
                        'precio' => $precio,
                        'cantidad' => $cantidad,
                        'subtotal' => $subtotal
                    ];

                    $total += $subtotal;
                }
            }
        }

        $this->view('carrito/index', [
            'items' => $items,
            'total' => $total,
            'categoriasMenu' => $categoriasMenu
        ], 'tienda');
    }

    public function agregar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/tienda');
        }

        $id_variante = (int) ($_POST['id_variante'] ?? 0);
        $cantidad = (int) ($_POST['cantidad'] ?? 1);

        if ($id_variante <= 0) {
            $this->redirect(BASE_URL . '/tienda');
        }

        if ($cantidad < 1) {
            $cantidad = 1;
        }

        $carritoModel = new Carrito();
        $variante = $carritoModel->buscarVarianteDetalle($id_variante);

        if (!$variante || $variante['activo'] != 1 || $variante['stock'] <= 0) {
            $this->redirect(BASE_URL . '/producto/' . ($variante['id_producto'] ?? 0));
        }

        if ($cantidad > $variante['stock']) {
            $cantidad = (int)$variante['stock'];
        }

        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        if (isset($_SESSION['carrito'][$id_variante])) {
            $_SESSION['carrito'][$id_variante]['cantidad'] += $cantidad;

            if ($_SESSION['carrito'][$id_variante]['cantidad'] > $variante['stock']) {
                $_SESSION['carrito'][$id_variante]['cantidad'] = (int)$variante['stock'];
            }
        } else {
            $_SESSION['carrito'][$id_variante] = [
                'cantidad' => $cantidad
            ];
        }

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

        $cantidades = $_POST['cantidades'] ?? [];

        foreach ($cantidades as $id_variante => $cantidad) {
            $id_variante = (int)$id_variante;
            $cantidad = (int)$cantidad;

            if ($cantidad <= 0) {
                unset($_SESSION['carrito'][$id_variante]);
            } else {
                $_SESSION['carrito'][$id_variante]['cantidad'] = $cantidad;
            }
        }

        $this->redirect(BASE_URL . '/carrito');
    }
}