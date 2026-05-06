<?php

class AdminAuthController extends Controller
{
    public function login()
    {
        // Si ya está logueado, redirigir al dashboard
        if (isset($_SESSION['admin'])) {
            $this->redirect(BASE_URL . '/admin');
        }

        $this->view('admin/auth/login', [], 'admin_auth');
    }

    public function ingresar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/login');
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->redirect(BASE_URL . '/admin/login?error=campos');
        }

        $adminModel = new UsuarioAdmin();
        $admin      = $adminModel->validarLogin($email, $password);

        if (!$admin) {
            $this->redirect(BASE_URL . '/admin/login?error=credenciales');
        }

        // Guardar sesión admin
        $_SESSION['admin'] = [
            'id_admin' => $admin['id_admin'],
            'nombre'   => $admin['nombre'],
            'email'    => $admin['email'],
        ];

        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);

        $this->redirect(BASE_URL . '/admin');
    }

    public function logout()
    {
        unset($_SESSION['admin']);
        session_regenerate_id(true);

        $this->redirect(BASE_URL . '/admin/login');
    }
}