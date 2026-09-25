<?php

class ColorController extends Controller
{
    public function index()
    {
        $colorModel = new Color();

        $this->view('admin/colores/index', [
            'colores' => $colorModel->listarTodos()
        ]);
    }

    public function crear()
    {
        $this->view('admin/colores/form', [
            'color' => null
        ]);
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/colores');
        }

        $colorModel = new Color();
        $nombre     = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            $this->redirect(BASE_URL . '/admin/colores/crear?error=nombre');
        }

        if ($colorModel->existeNombre($nombre)) {
            $this->redirect(BASE_URL . '/admin/colores/crear?error=duplicado');
        }

        $colorModel->crear([
            'nombre'     => $nombre,
            'codigo_hex' => $this->hexDesdePost(),
            'activo'     => isset($_POST['activo']) ? 1 : 0,
        ]);

        $this->redirect(BASE_URL . '/admin/colores?ok=creado');
    }

    public function editar()
    {
        $id = (int) ($_GET['id'] ?? 0);

        $colorModel = new Color();
        $color      = $colorModel->buscarPorId($id);

        if (!$color) {
            $this->redirect(BASE_URL . '/admin/colores');
        }

        $this->view('admin/colores/form', [
            'color' => $color
        ]);
    }

    public function actualizar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/colores');
        }

        $colorModel = new Color();
        $id         = (int) ($_POST['id_color'] ?? 0);
        $nombre     = trim($_POST['nombre'] ?? '');

        if (!$colorModel->buscarPorId($id)) {
            $this->redirect(BASE_URL . '/admin/colores');
        }

        if ($nombre === '') {
            $this->redirect(BASE_URL . '/admin/colores/editar?id=' . $id . '&error=nombre');
        }

        if ($colorModel->existeNombre($nombre, $id)) {
            $this->redirect(BASE_URL . '/admin/colores/editar?id=' . $id . '&error=duplicado');
        }

        $colorModel->actualizar($id, [
            'nombre'     => $nombre,
            'codigo_hex' => $this->hexDesdePost(),
            'activo'     => isset($_POST['activo']) ? 1 : 0,
        ]);

        $this->redirect(BASE_URL . '/admin/colores?ok=actualizado');
    }

    public function eliminar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/colores');
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->redirect(BASE_URL . '/admin/colores');
        }

        $colorModel = new Color();

        if ($colorModel->enUso($id)) {
            $this->redirect(BASE_URL . '/admin/colores?error=en_uso');
        }

        $colorModel->eliminar($id);

        $this->redirect(BASE_URL . '/admin/colores?ok=eliminado');
    }

    /**
     * Devuelve el hex en formato #RRGGBB o null si no es válido / no se cargó.
     * Acepta "#abc", "abc", "#aabbcc" o "aabbcc".
     */
    private function hexDesdePost(): ?string
    {
        $hex = strtoupper(ltrim(trim($_POST['codigo_hex'] ?? ''), '#'));

        if (preg_match('/^[0-9A-F]{3}$/', $hex)) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return preg_match('/^[0-9A-F]{6}$/', $hex) ? '#' . $hex : null;
    }
}