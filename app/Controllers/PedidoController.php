<?php

class PedidoController extends Controller
{
    public function index()
    {
        $pedidoModel = new Pedido();
        $pedidos = $pedidoModel->listarPedidos();

        $this->view('admin/pedidos/index', [
            'pedidos' => $pedidos
        ]);
    }

    public function detalle()
    {
        $id = (int) ($_GET['id'] ?? 0);

        $pedidoModel = new Pedido();

        $pedido = $pedidoModel->buscarPedidoCompleto($id);
        $items = $pedidoModel->listarItems($id);

        $this->view('admin/pedidos/detalle', [
            'pedido' => $pedido,
            'items' => $items
        ]);
    }

    public function actualizarEstado()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?route=admin_pedidos');
        }

        $id_pedido = (int) $_POST['id_pedido'];
        $nuevo_estado = $_POST['estado'];

        $pedidoModel = new Pedido();

        $pedido = $pedidoModel->buscarPedido($id_pedido);
        $estado_anterior = $pedido['estado'];

        $items = $pedidoModel->listarItems($id_pedido);

        //  RESERVAR STOCK
        if ($nuevo_estado === 'pendiente_pago' && $estado_anterior !== 'pendiente_pago') {

            while ($item = $items->fetch_assoc()) {
                $pedidoModel->reservarStock(
                    $item['id_variante'],
                    $item['cantidad']
                );
            }
        }

        //  LIBERAR STOCK (cancelado)
        if ($nuevo_estado === 'cancelado' && $estado_anterior === 'pendiente_pago') {

            while ($item = $items->fetch_assoc()) {
                $pedidoModel->liberarStock(
                    $item['id_variante'],
                    $item['cantidad']
                );
            }
        }

        //  DESCONTAR STOCK DEFINITIVO
        if ($nuevo_estado === 'pagado' && $estado_anterior === 'pendiente_pago') {

            while ($item = $items->fetch_assoc()) {
                $pedidoModel->descontarStock(
                    $item['id_variante'],
                    $item['cantidad']
                );

                $pedidoModel->liberarStock(
                    $item['id_variante'],
                    $item['cantidad']
                );
            }
        }

        $pedidoModel->actualizarEstado($id_pedido, $nuevo_estado);

        $this->redirect('<?= BASE_URL ?>/admin/pedido/' . $id_pedido);
    }
}