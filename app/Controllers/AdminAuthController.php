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
        $ip       = LoginIntento::ip();

        if ($email === '' || $password === '') {
            $this->redirect(BASE_URL . '/admin/login?error=campos');
        }

        // ¿Bloqueado por demasiados intentos? (se chequea ANTES de validar la contraseña)
        $intentos = new LoginIntento();
        $minutos  = $intentos->minutosBloqueo('admin', $email, $ip);

        if ($minutos > 0) {
            $this->redirect(BASE_URL . '/admin/login?error=bloqueado&min=' . $minutos);
        }

        $adminModel = new UsuarioAdmin();
        $admin      = $adminModel->validarLogin($email, $password);

        $intentos->registrar('admin', $email, $ip, (bool) $admin);

        if (!$admin) {
            $this->redirect(BASE_URL . '/admin/login?error=credenciales');
        }

        // Regenerar ID de sesión ANTES de guardar los datos (evita fijación de sesión)
        session_regenerate_id(true);

        $_SESSION['admin'] = [
            'id_admin' => $admin['id_admin'],
            'nombre'   => $admin['nombre'],
            'email'    => $admin['email'],
        ];

        $this->redirect(BASE_URL . '/admin');
    }

    public function logout()
    {
        unset($_SESSION['admin']);
        session_regenerate_id(true);

        $this->redirect(BASE_URL . '/admin/login');
    }
}