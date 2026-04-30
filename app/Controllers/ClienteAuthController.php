<?php

class ClienteAuthController extends Controller
{
    public function login()
    {
        $categoriaModel = new Categoria();
        $categoriasMenu = $categoriaModel->listarMenu();

        $this->view('cliente/login', [
            'categoriasMenu' => $categoriasMenu
        ], 'tienda');
    }

    public function ingresar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/tienda_ropa/cliente/login');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $usuarioModel = new UsuarioCliente();
        $usuario = $usuarioModel->validarLogin($email, $password);

        if (!$usuario) {
            $this->redirect('/tienda_ropa/cliente/login?error=1');
        }

        $_SESSION['cliente'] = [
            'id_usuario_cliente' => $usuario['id_usuario_cliente'],
            'nombre' => $usuario['nombre'],
            'apellido' => $usuario['apellido'],
            'email' => $usuario['email'],
            'telefono' => $usuario['telefono']
        ];

        $this->redirect('/tienda_ropa/checkout');
    }

    public function registro()
    {
        $categoriaModel = new Categoria();
        $categoriasMenu = $categoriaModel->listarMenu();

        $this->view('cliente/registro', [
            'categoriasMenu' => $categoriasMenu
        ], 'tienda');
    }

    public function guardarRegistro()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/tienda_ropa/cliente/registro');
        }

        $email = trim($_POST['email'] ?? '');

        $usuarioModel = new UsuarioCliente();

        if ($usuarioModel->buscarPorEmail($email)) {
            $this->redirect('/tienda_ropa/cliente/registro?error=email');
        }

        $id_usuario_cliente = $usuarioModel->crear([
            'nombre' => trim($_POST['nombre'] ?? ''),
            'apellido' => trim($_POST['apellido'] ?? ''),
            'email' => $email,
            'telefono' => trim($_POST['telefono'] ?? ''),
            'password' => $_POST['password'] ?? ''
        ]);

        $_SESSION['cliente'] = [
            'id_usuario_cliente' => $id_usuario_cliente,
            'nombre' => trim($_POST['nombre'] ?? ''),
            'apellido' => trim($_POST['apellido'] ?? ''),
            'email' => $email,
            'telefono' => trim($_POST['telefono'] ?? '')
        ];

        $this->redirect('/tienda_ropa/checkout');
    }

    public function logout()
    {
        unset($_SESSION['cliente']);
        $this->redirect('/tienda_ropa/tienda');
    }
}