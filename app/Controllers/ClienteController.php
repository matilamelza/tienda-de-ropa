<?php

class ClienteController extends Controller
{
    public function pedidos()
    {
        if (!isset($_SESSION['cliente'])) {
            $this->redirect(BASE_URL . '/cliente/ingresar');
        }

        $pedidoModel = new Pedido();
        $categoriaModel = new Categoria();

        $pedidos = $pedidoModel->listarPedidosPorUsuario(
            $_SESSION['cliente']['id_usuario_cliente']
        );

        $this->view('cliente/pedidos', [
            'pedidos' => $pedidos,
            'categoriasMenu' => $categoriaModel->listarMenu()
        ], 'tienda');
    }

    public function pedidoDetalle()
    {
        if (!isset($_SESSION['cliente'])) {
            $this->redirect(BASE_URL . '/cliente/ingresar');
        }

        $id = (int) ($_GET['id'] ?? 0);

        $pedidoModel = new Pedido();
        $categoriaModel = new Categoria();

        $pedido = $pedidoModel->buscarPedido($id);

        //  seguridad: solo ver su pedido
        if (!$pedido || $pedido['id_usuario_cliente'] != $_SESSION['cliente']['id_usuario_cliente']) {
            $this->redirect(BASE_URL . '/mi-cuenta/pedidos');
        }

        $items = $pedidoModel->listarItems($id);

        $this->view('cliente/pedido_detalle', [
            'pedido' => $pedido,
            'items' => $items,
            'categoriasMenu' => $categoriaModel->listarMenu()
        ], 'tienda');
    }
}