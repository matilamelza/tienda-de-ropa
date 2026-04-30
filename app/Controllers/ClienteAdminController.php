<?php

class ClienteAdminController extends Controller
{
    public function index()
    {
        $clienteModel = new Cliente();

        $clientes = $clienteModel->listarClientes();

        $this->view('admin/clientes/index', [
            'clientes' => $clientes
        ]);
    }

    public function detalle()
    {
        $id_cliente = (int)($_GET['id'] ?? 0);

        $clienteModel = new Cliente();

        $cliente = $clienteModel->buscarCliente($id_cliente);
        $pedidos = $clienteModel->pedidosCliente($id_cliente);

        if (!$cliente) {
            $this->redirect('admin/clientes');
        }

        $this->view('admin/clientes/detalle', [
            'cliente' => $cliente,
            'pedidos' => $pedidos
        ]);
    }
}