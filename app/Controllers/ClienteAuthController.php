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
        $ip       = LoginIntento::ip();

        // ¿Bloqueado por demasiados intentos? (antes de validar la contraseña)
        $intentos = new LoginIntento();
        $minutos  = $intentos->minutosBloqueo('cliente', $email, $ip);

        if ($minutos > 0) {
            $this->redirect(BASE_URL . '/ingresar?error=bloqueado&min=' . $minutos);
        }

        $usuarioModel = new UsuarioCliente();
        $usuario      = $usuarioModel->validarLogin($email, $password);

        $intentos->registrar('cliente', $email, $ip, (bool) $usuario);

        if (!$usuario) {
            $this->redirect(BASE_URL . '/ingresar?error=credenciales');
        }

        session_regenerate_id(true);

        $_SESSION['cliente'] = [
            'id_usuario_cliente' => $usuario['id_usuario_cliente'],
            'nombre'             => $usuario['nombre'],
            'apellido'           => $usuario['apellido'],
            'email'              => $usuario['email'],
            'telefono'           => $usuario['telefono']
        ];

        $this->redirect($this->destinoDespuesDeLogin());
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

        $nombre   = trim($_POST['nombre'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirect(BASE_URL . '/registro?error=datos');
        }

        if (strlen($password) < 6) {
            $this->redirect(BASE_URL . '/registro?error=password');
        }

        $usuarioModel = new UsuarioCliente();

        if ($usuarioModel->buscarPorEmail($email)) {
            $this->redirect(BASE_URL . '/registro?error=email');
        }

        $id_usuario_cliente = $usuarioModel->crear([
            'nombre'   => $nombre,
            'apellido' => trim($_POST['apellido'] ?? ''),
            'email'    => $email,
            'telefono' => trim($_POST['telefono'] ?? ''),
            'password' => $password
        ]);

        session_regenerate_id(true);

        $_SESSION['cliente'] = [
            'id_usuario_cliente' => $id_usuario_cliente,
            'nombre'             => $nombre,
            'apellido'           => trim($_POST['apellido'] ?? ''),
            'email'              => $email,
            'telefono'           => trim($_POST['telefono'] ?? '')
        ];

        $this->redirect($this->destinoDespuesDeLogin());
    }

    public function logout()
    {
        unset($_SESSION['cliente']);
        session_regenerate_id(true);

        $this->redirect(BASE_URL . '/tienda');
    }

    /**
     * Si tiene algo en el carrito → checkout. Si no → la tienda.
     * (Antes siempre iba al checkout y, con el carrito vacío, parecía que "no hacía nada").
     */
    private function destinoDespuesDeLogin(): string
    {
        return !empty($_SESSION['carrito'])
            ? BASE_URL . '/checkout'
            : BASE_URL . '/tienda';
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
            // El token queda guardado en la tabla password_resets.
            // Si hay SMTP configurado se envía por mail; si no, el admin
            // lo ve en "Recuperar contraseñas" (se lee de la base, no de la sesión).
            $config = require __DIR__ . '/../../config/database.php';

            if (!empty($config['smtp_host']) && !empty($config['smtp_usuario'])) {
                $this->_enviarEmailReset($email, $this->linkReset($token), $config);
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

    /** Link completo (con dominio) para mandar por mail o WhatsApp. */
    private function linkReset(string $token): string
    {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return ($https ? 'https' : 'http') . '://' . $host . BASE_URL . '/nueva-password?token=' . $token;
    }

    // ─── SMTP ────────────────────────────────────────────────────────────────

    private function _enviarEmailReset(string $email, string $link, array $config): bool
    {
        $asunto  = 'Recuperación de contraseña - ' . ($config['smtp_nombre'] ?? 'Mi Tienda');
        $cuerpo  = "Hola,\n\n";
        $cuerpo .= "Recibimos una solicitud para restablecer la contraseña de tu cuenta.\n\n";
        $cuerpo .= "Hacé click en el siguiente link para crear una nueva contraseña:\n";
        $cuerpo .= $link . "\n\n";
        $cuerpo .= "Este link es válido por 24 hora.\n\n";
        $cuerpo .= "Si no solicitaste este cambio, ignorá este mensaje.\n\n";
        $cuerpo .= "— " . ($config['smtp_nombre'] ?? 'Mi Tienda');

        $headers  = "From: " . ($config['smtp_nombre'] ?? 'Mi Tienda') . " <" . $config['smtp_from'] . ">\r\n";
        $headers .= "Reply-To: " . $config['smtp_from'] . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        return @mail($email, $asunto, $cuerpo, $headers);
    }
}