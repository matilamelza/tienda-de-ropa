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
        $usuarioModel = new UsuarioCliente();

        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $base  = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL;

        $this->view('admin/resets/index', [
            'resets'   => $usuarioModel->listarResetsPendientes(),
            'baseLink' => $base . '/nueva-password?token=',
        ]);
    }

        // POST /admin/cliente/password
    public function cambiarPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/clientes');
        }

        $id_cliente = (int) ($_POST['id_cliente'] ?? 0);
        $nueva      = trim($_POST['nueva'] ?? '');
        $volver     = BASE_URL . '/admin/cliente?id=' . $id_cliente;

        $clienteModel = new Cliente();
        $cliente      = $clienteModel->buscarCliente($id_cliente);

        if (!$cliente || empty($cliente['id_usuario_cliente'])) {
            $this->redirect($volver . '&error=no_registrado');
        }

        if (strlen($nueva) < 6) {
            $this->redirect($volver . '&error=corta');
        }

        (new UsuarioCliente())->actualizarPasswordPorId((int) $cliente['id_usuario_cliente'], $nueva);

        // Se muestra UNA vez en la vista, para poder mandársela al cliente, y se borra
        $_SESSION['flash_password_cliente'] = $nueva;

        $this->redirect($volver . '&ok=password');
    }
}