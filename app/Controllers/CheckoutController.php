<?php

class CheckoutController extends Controller
{
    public function index()
    {
        $clienteLogueado = $_SESSION['cliente'] ?? null;
        $cfgTienda       = new ConfiguracionTienda();
        $permiteInvitado = $cfgTienda->get('permitir_invitados', '1') === '1';
            $modoInvitado    = $permiteInvitado && isset($_GET['invitado']) && $_GET['invitado'] == 1;

        if (!$clienteLogueado && !$modoInvitado) {
            $this->redirect(BASE_URL . '/ingresar');
        }

        if (empty($_SESSION['carrito'])) {
            $this->redirect(BASE_URL . '/carrito');
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
                    'precio'   => $precio,
                    'cantidad' => $cantidad,
                    'subtotal' => $subtotal
                ];

                $total += $subtotal;
            }
        }

        $this->view('checkout/index', [
            'items'           => $items,
            'total'           => $total,
            'categoriasMenu'  => $categoriasMenu,
            'clienteLogueado' => $clienteLogueado
        ], 'tienda');
    }

    public function guardar()
    {
        $cfgTienda = new ConfiguracionTienda();
        if (empty($_SESSION['cliente']) && $cfgTienda->get('permitir_invitados', '1') !== '1') {
            $this->redirect(BASE_URL . '/ingresar');
        }

        $carritoModel = new Carrito();
        $pedidoModel  = new Pedido();

        $items = [];
        $total = 0;

        foreach ($_SESSION['carrito'] as $id_variante => $itemCarrito) {
            $variante = $carritoModel->buscarVarianteDetalle((int)$id_variante);

            if (!$variante || $variante['disponible'] < $itemCarrito['cantidad']) {
                $this->redirect(BASE_URL . '/carrito');
            }

            $precio = $variante['precio'] !== null && $variante['precio'] !== ''
                ? $variante['precio']
                : $variante['precio_base'];

            $cantidad = (int)$itemCarrito['cantidad'];
            $subtotal = $precio * $cantidad;

            $items[] = [
                'id_variante'     => (int)$id_variante,
                'producto'        => $variante['producto'],
                'talle'           => $variante['talle'],
                'color'           => $variante['color'],
                'cantidad'        => $cantidad,
                'precio_unitario' => $precio,
                'subtotal'        => $subtotal
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
                    'nombre'    => trim($_POST['nombre']),
                    'apellido'  => trim($_POST['apellido'] ?? ''),
                    'email'     => trim($_POST['email'] ?? ''),
                    'telefono'  => trim($_POST['telefono'] ?? ''),
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
            }

            $pedidoModel->commit();

            unset($_SESSION['carrito']);
            unset($_SESSION['ultimo_pedido_whatsapp']);

            // ── Link de WhatsApp con el detalle del pedido ──────────────────
            $cfgTienda      = new ConfiguracionTienda();
            $telefonoTienda = preg_replace('/\D/', '', $cfgTienda->get('tienda_whatsapp'));

            if ($telefonoTienda !== '') {
                $mensaje  = "Hola, quiero consultar por mi pedido #" . $id_pedido . "\n\n";
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

                $_SESSION['ultimo_pedido_whatsapp'] = "https://wa.me/" . $telefonoTienda . "?text=" . urlencode($mensaje);
            }

            $this->redirect(BASE_URL . '/pedido/gracias?id=' . $id_pedido);

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
            'id_pedido'      => (int)($_GET['id'] ?? 0)
        ], 'tienda');
    }
}