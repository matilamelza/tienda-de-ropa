<?php

class ClienteAuthController extends Controller
{
    public function login()
    {
        $categoriaModel = new Categoria();
        $this->view('cliente/login', [
            'categoriasMenu' => $categoriaModel->listarMenu()
        ], 'tienda');
    }

    public function ingresar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/ingresar');
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $usuarioModel = new UsuarioCliente();
        $usuario      = $usuarioModel->validarLogin($email, $password);

        if (!$usuario) {
            $this->redirect(BASE_URL . '/ingresar?error=1');
        }

        $_SESSION['cliente'] = [
            'id_usuario_cliente' => $usuario['id_usuario_cliente'],
            'nombre'             => $usuario['nombre'],
            'apellido'           => $usuario['apellido'],
            'email'              => $usuario['email'],
            'telefono'           => $usuario['telefono']
        ];

        $this->redirect(BASE_URL . '/checkout');
    }

    public function registro()
    {
        $categoriaModel = new Categoria();
        $this->view('cliente/registro', [
            'categoriasMenu' => $categoriaModel->listarMenu()
        ], 'tienda');
    }

    public function guardarRegistro()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/registro');
        }

        $email        = trim($_POST['email'] ?? '');
        $usuarioModel = new UsuarioCliente();

        if ($usuarioModel->buscarPorEmail($email)) {
            $this->redirect(BASE_URL . '/registro?error=email');
        }

        $id_usuario_cliente = $usuarioModel->crear([
            'nombre'   => trim($_POST['nombre']   ?? ''),
            'apellido' => trim($_POST['apellido'] ?? ''),
            'email'    => $email,
            'telefono' => trim($_POST['telefono'] ?? ''),
            'password' => $_POST['password']      ?? ''
        ]);

        $_SESSION['cliente'] = [
            'id_usuario_cliente' => $id_usuario_cliente,
            'nombre'             => trim($_POST['nombre']   ?? ''),
            'apellido'           => trim($_POST['apellido'] ?? ''),
            'email'              => $email,
            'telefono'           => trim($_POST['telefono'] ?? '')
        ];

        $this->redirect(BASE_URL . '/checkout');
    }

    public function logout()
    {
        unset($_SESSION['cliente']);
        $this->redirect(BASE_URL . '/tienda');
    }

    // ─── RECUPERAR CONTRASEÑA ─────────────────────────────────────────────────

    public function olvideMiPassword()
    {
        $categoriaModel = new Categoria();
        $this->view('cliente/olvide_password', [
            'categoriasMenu' => $categoriaModel->listarMenu()
        ], 'tienda');
    }

    public function solicitarReset()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/olvide-mi-password');
        }

        $email        = trim($_POST['email'] ?? '');
        $usuarioModel = new UsuarioCliente();
        $token        = $usuarioModel->crearTokenReset($email);

        if ($token) {
            $linkReset = BASE_URL . '/nueva-password?token=' . $token;

            // ── Intentar envío por SMTP ──────────────────────────────────────
            $config = require __DIR__ . '/../../config/database.php';
            $enviado = false;

            if (!empty($config['smtp_host']) && !empty($config['smtp_usuario'])) {
                $enviado = $this->_enviarEmailReset($email, $linkReset, $config);
            }

            // Si no hay SMTP o falló el envío → guardar para que lo vea el admin
            if (!$enviado) {
                // Guardamos en sesión admin para que aparezca en el panel
                if (!isset($_SESSION['admin_resets_pendientes'])) {
                    $_SESSION['admin_resets_pendientes'] = [];
                }
                $_SESSION['admin_resets_pendientes'][] = [
                    'email'     => $email,
                    'link'      => $linkReset,
                    'fecha'     => date('H:i d/m/Y'),
                ];
            }
        }

        // Siempre mensaje genérico al cliente — no revelar si el email existe
        $this->redirect(BASE_URL . '/olvide-mi-password?enviado=1');
    }

    public function formularioNuevaPassword()
    {
        $token        = trim($_GET['token'] ?? '');
        $usuarioModel = new UsuarioCliente();

        if (!$token || !$usuarioModel->validarToken($token)) {
            $this->redirect(BASE_URL . '/olvide-mi-password?error=token');
        }

        $categoriaModel = new Categoria();
        $this->view('cliente/nueva_password', [
            'token'          => $token,
            'categoriasMenu' => $categoriaModel->listarMenu()
        ], 'tienda');
    }

    public function guardarNuevaPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/ingresar');
        }

        $token    = trim($_POST['token']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirma = trim($_POST['confirma'] ?? '');

        if ($password !== $confirma || strlen($password) < 6) {
            $this->redirect(BASE_URL . '/nueva-password?token=' . urlencode($token) . '&error=password');
        }

        $usuarioModel = new UsuarioCliente();
        $ok           = $usuarioModel->resetearPassword($token, $password);

        if (!$ok) {
            $this->redirect(BASE_URL . '/olvide-mi-password?error=token');
        }

        $this->redirect(BASE_URL . '/ingresar?reset=ok');
    }

    // ─── SMTP ────────────────────────────────────────────────────────────────

    private function _enviarEmailReset(string $email, string $link, array $config): bool
    {
        // Usamos la función mail() de PHP con headers SMTP si están configurados
        // Para producción real, instalá PHPMailer con composer
        $asunto  = 'Recuperación de contraseña - ' . ($config['smtp_nombre'] ?? 'Mi Tienda');
        $cuerpo  = "Hola,\n\n";
        $cuerpo .= "Recibimos una solicitud para restablecer la contraseña de tu cuenta.\n\n";
        $cuerpo .= "Hacé click en el siguiente link para crear una nueva contraseña:\n";
        $cuerpo .= $link . "\n\n";
        $cuerpo .= "Este link es válido por 1 hora.\n\n";
        $cuerpo .= "Si no solicitaste este cambio, ignorá este mensaje.\n\n";
        $cuerpo .= "— " . ($config['smtp_nombre'] ?? 'Mi Tienda');

        $headers  = "From: " . ($config['smtp_nombre'] ?? 'Mi Tienda') . " <" . $config['smtp_from'] . ">\r\n";
        $headers .= "Reply-To: " . $config['smtp_from'] . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        return @mail($email, $asunto, $cuerpo, $headers);
    }
}