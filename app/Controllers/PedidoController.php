<?php

class PedidoController extends Controller
{
    private const ESTADOS_VALIDOS = [
        'pendiente_contacto',
        'contactado',
        'pendiente_pago',
        'pagado',
        'cancelado',
        'entregado',
    ];

    public function index()
    {
        $porPagina = 20;
        $pagina    = max(1, (int)($_GET['pagina'] ?? 1));
        $busqueda  = trim($_GET['q'] ?? '');
        $estado    = $_GET['estado'] ?? '';

        if ($estado !== 'vencidos' && !in_array($estado, Pedido::ESTADOS, true)) {
            $estado = '';
        }

        $pedidoModel = new Pedido();

        $total        = $pedidoModel->contarPedidos($busqueda, $estado);
        $pedidos      = $pedidoModel->listarPaginado($pagina, $porPagina, $busqueda, $estado);
        $totalPaginas = (int) ceil($total / $porPagina);

        $this->view('admin/pedidos/index', [
            'pedidos'      => $pedidos,
            'pagina'       => $pagina,
            'totalPaginas' => $totalPaginas,
            'total'        => $total,
            'busqueda'     => $busqueda,
            'estado'       => $estado,
            'conteo'       => $pedidoModel->contarPorEstado(),
        ]);
    }

    public function detalle()
    {
        $id = (int) ($_GET['id'] ?? 0);

        $pedidoModel = new Pedido();

        $pedido = $pedidoModel->buscarPedidoCompleto($id);

        if (!$pedido) {
            $this->redirect(BASE_URL . '/admin/pedidos');
        }

        $items = $pedidoModel->listarItems($id);

        $this->view('admin/pedidos/detalle', [
            'pedido' => $pedido,
            'items'  => $items
        ]);
    }

    public function actualizarEstado()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/pedidos');
        }

        $id_pedido    = (int) ($_POST['id_pedido'] ?? 0);
        $nuevo_estado = $_POST['estado'] ?? '';

        if (!in_array($nuevo_estado, self::ESTADOS_VALIDOS, true)) {
            $this->redirect(BASE_URL . '/admin/pedido/' . $id_pedido);
        }

        $pedidoModel = new Pedido();
        $pedido      = $pedidoModel->buscarPedido($id_pedido);

        if (!$pedido) {
            $this->redirect(BASE_URL . '/admin/pedidos');
        }

        $estado_anterior = $pedido['estado'];

        if ($estado_anterior === $nuevo_estado) {
            $this->redirect(BASE_URL . '/admin/pedido/' . $id_pedido);
        }

        try {
            $pedidoModel->begin();

            if (!$pedidoModel->aplicarCambioStock($id_pedido, $estado_anterior, $nuevo_estado)) {
                $pedidoModel->rollback();
                $this->redirect(BASE_URL . '/admin/pedido/' . $id_pedido . '?error=stock');
            }

            $pedidoModel->actualizarEstado($id_pedido, $nuevo_estado);
            $pedidoModel->commit();

        } catch (Exception $e) {
            $pedidoModel->rollback();
            $this->redirect(BASE_URL . '/admin/pedido/' . $id_pedido . '?error=general');
        }

        $this->redirect(BASE_URL . '/admin/pedido/' . $id_pedido . '?ok=estado');
    }
}