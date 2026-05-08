<?php

class ClienteAdminController extends Controller
{
    public function index()
    {
        $porPagina = 20;
        $pagina    = max(1, (int)($_GET['pagina'] ?? 1));
        $busqueda  = trim($_GET['q'] ?? '');

        $clienteModel = new Cliente();

        $total        = $clienteModel->contarClientes($busqueda);
        $clientes     = $clienteModel->listarPaginado($pagina, $porPagina, $busqueda);
        $totalPaginas = (int) ceil($total / $porPagina);

        $this->view('admin/clientes/index', [
            'clientes'     => $clientes,
            'pagina'       => $pagina,
            'totalPaginas' => $totalPaginas,
            'total'        => $total,
            'busqueda'     => $busqueda,
        ]);
    }

    public function detalle()
    {
        $id_cliente = (int)($_GET['id'] ?? 0);

        $clienteModel = new Cliente();
        $cliente      = $clienteModel->buscarCliente($id_cliente);
        $pedidos      = $clienteModel->pedidosCliente($id_cliente);

        if (!$cliente) {
            $this->redirect(BASE_URL . '/admin/clientes');
        }

        $this->view('admin/clientes/detalle', [
            'cliente' => $cliente,
            'pedidos' => $pedidos
        ]);
    }

    public function resets()
    {
        $this->view('admin/resets/index', []);
    }
}