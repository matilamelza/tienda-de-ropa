<?php

class CheckoutController extends Controller
{
    public function index()
    {

        $clienteLogueado = $_SESSION['cliente'] ?? null;
$modoInvitado = isset($_GET['invitado']) && $_GET['invitado'] == 1;

if (!$clienteLogueado && !$modoInvitado) {
    $this->redirect('<?= BASE_URL ?>/ingresar');
}
    
        if (empty($_SESSION['carrito'])) {
            $this->redirect('<?= BASE_URL ?>/carrito');
        }

        $categoriaModel = new Categoria();
        $categoriasMenu = $categoriaModel->listarMenu();

        $carritoModel = new Carrito();

        $items = [];
        $total = 0;

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

        $this->view('checkout/index', [
            'items' => $items,
            'total' => $total,
            'categoriasMenu' => $categoriasMenu,
            'clienteLogueado' => $clienteLogueado
        ], 'tienda');
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['carrito'])) {
            $this->redirect('<?= BASE_URL ?>/carrito');
        }

        $carritoModel = new Carrito();
        $pedidoModel = new Pedido();

        $items = [];
        $total = 0;

        foreach ($_SESSION['carrito'] as $id_variante => $itemCarrito) {
            $variante = $carritoModel->buscarVarianteDetalle((int)$id_variante);

            if (!$variante || $variante['stock'] < $itemCarrito['cantidad']) {
                $this->redirect('<?= BASE_URL ?>/carrito');
            }

            $precio = $variante['precio'] !== null && $variante['precio'] !== ''
                ? $variante['precio']
                : $variante['precio_base'];

            $cantidad = (int)$itemCarrito['cantidad'];
            $subtotal = $precio * $cantidad;

            $items[] = [
                'id_variante' => (int)$id_variante,
                'producto' => $variante['producto'],
                'talle' => $variante['talle'],
                'color' => $variante['color'],
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'subtotal' => $subtotal
            ];

            $total += $subtotal;
        }

        try {
            $pedidoModel->begin();
            $cliente = $pedidoModel->buscarCliente($_POST['telefono'], $_POST['email']);

            if ($cliente) {
                $id_cliente = $cliente['id_cliente'];
            } else {
                $id_cliente = $pedidoModel->crearCliente([
                    'nombre' => trim($_POST['nombre']),
                    'apellido' => trim($_POST['apellido'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'telefono' => trim($_POST['telefono'] ?? ''),
                    'direccion' => trim($_POST['direccion'] ?? ''),
                    'localidad' => trim($_POST['localidad'] ?? '')
                ]);
            }

            $id_usuario_cliente = isset($_SESSION['cliente'])
    ? (int) $_SESSION['cliente']['id_usuario_cliente']
    : null;

            $id_pedido = $pedidoModel->crearPedido(
    $id_cliente,
    $id_usuario_cliente,
    $total,
    trim($_POST['observaciones'] ?? '')
);

            foreach ($items as $item) {
                $pedidoModel->agregarItem($id_pedido, $item);
                // $pedidoModel->descontarStock($item['id_variante'], $item['cantidad']);
            }

            $pedidoModel->commit();

            unset($_SESSION['carrito']);

                $config = require __DIR__ . '/../../config/database.php';
                $telefonoTienda = $config['whatsapp_tienda'];

                $mensaje = "Hola, quiero consultar por mi pedido #" . $id_pedido . "\n\n";
                $mensaje .= "Detalle del pedido:\n";

                foreach ($items as $item) {
                    $mensaje .= "- " . $item['producto'] . " ";
                    $mensaje .= "(" . $item['talle'] . " / " . $item['color'] . ") ";
                    $mensaje .= "x" . $item['cantidad'] . " - $";
                    $mensaje .= number_format($item['subtotal'], 2, ',', '.') . "\n";
                }

                $mensaje .= "\nTotal: $" . number_format($total, 2, ',', '.');
                $mensaje .= "\n\nMis datos:";
                $mensaje .= "\nNombre: " . trim($_POST['nombre'] . ' ' . ($_POST['apellido'] ?? ''));
                $mensaje .= "\nTeléfono: " . ($_POST['telefono'] ?? '');
                $mensaje .= "\nEmail: " . ($_POST['email'] ?? '');

                $urlWhatsapp = "https://wa.me/" . $telefonoTienda . "?text=" . urlencode($mensaje);

                $_SESSION['ultimo_pedido_whatsapp'] = $urlWhatsapp;

                $this->redirect('<?= BASE_URL ?>/pedido/gracias?id=' . $id_pedido);

        } catch (Exception $e) {
            $pedidoModel->rollback();
            die('Error al guardar el pedido: ' . $e->getMessage());
        }
    }

    public function gracias()
    {
        $categoriaModel = new Categoria();
        $categoriasMenu = $categoriaModel->listarMenu();

        $this->view('checkout/gracias', [
            'categoriasMenu' => $categoriasMenu,
            'id_pedido' => (int)($_GET['id'] ?? 0)
        ], 'tienda');
    }
}