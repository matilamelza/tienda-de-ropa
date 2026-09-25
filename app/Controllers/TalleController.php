<?php

class TalleController extends Controller
{
    public function index()
    {
        $talleModel = new Talle();

        $this->view('admin/talles/index', [
            'talles' => $talleModel->listarTodos()
        ]);
    }

    public function crear()
    {
        $this->view('admin/talles/form', [
            'talle' => null
        ]);
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/talles');
        }

        $talleModel = new Talle();
        $nombre     = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            $this->redirect(BASE_URL . '/admin/talles/crear?error=nombre');
        }

        if ($talleModel->existeNombre($nombre)) {
            $this->redirect(BASE_URL . '/admin/talles/crear?error=duplicado');
        }

        $orden = ($_POST['orden'] ?? '') !== ''
            ? (int) $_POST['orden']
            : $talleModel->siguienteOrden();

        $talleModel->crear([
            'nombre' => $nombre,
            'orden'  => $orden,
            'activo' => isset($_POST['activo']) ? 1 : 0,
        ]);

        $this->redirect(BASE_URL . '/admin/talles?ok=creado');
    }

    public function editar()
    {
        $id = (int) ($_GET['id'] ?? 0);

        $talleModel = new Talle();
        $talle      = $talleModel->buscarPorId($id);

        if (!$talle) {
            $this->redirect(BASE_URL . '/admin/talles');
        }

        $this->view('admin/talles/form', [
            'talle' => $talle
        ]);
    }

    public function actualizar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/talles');
        }

        $talleModel = new Talle();
        $id         = (int) ($_POST['id_talle'] ?? 0);
        $nombre     = trim($_POST['nombre'] ?? '');

        if (!$talleModel->buscarPorId($id)) {
            $this->redirect(BASE_URL . '/admin/talles');
        }

        if ($nombre === '') {
            $this->redirect(BASE_URL . '/admin/talles/editar?id=' . $id . '&error=nombre');
        }

        if ($talleModel->existeNombre($nombre, $id)) {
            $this->redirect(BASE_URL . '/admin/talles/editar?id=' . $id . '&error=duplicado');
        }

        $talleModel->actualizar($id, [
            'nombre' => $nombre,
            'orden'  => (int) ($_POST['orden'] ?? 0),
            'activo' => isset($_POST['activo']) ? 1 : 0,
        ]);

        $this->redirect(BASE_URL . '/admin/talles?ok=actualizado');
    }

    public function eliminar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/talles');
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->redirect(BASE_URL . '/admin/talles');
        }

        $talleModel = new Talle();

        if ($talleModel->enUso($id)) {
            $this->redirect(BASE_URL . '/admin/talles?error=en_uso');
        }

        $talleModel->eliminar($id);

        $this->redirect(BASE_URL . '/admin/talles?ok=eliminado');
    }

        /**
     * AJAX: crea un talle desde el form de variantes.
     * Responde: { ok: true, id, nombre } o { ok: false, error }
     */
    public function crearAjax()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'error' => 'Método no permitido'], 405);
        }

        $talleModel = new Talle();
        $nombre     = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            $this->json(['ok' => false, 'error' => 'Escribí el nombre del talle.'], 400);
        }

        if (mb_strlen($nombre) > 20) {
            $this->json(['ok' => false, 'error' => 'Máximo 20 caracteres.'], 400);
        }

        if ($talleModel->existeNombre($nombre)) {
            $this->json(['ok' => false, 'error' => 'Ya existe un talle con ese nombre.'], 409);
        }

        $id = $talleModel->crear([
            'nombre' => $nombre,
            'orden'  => $talleModel->siguienteOrden(),
            'activo' => 1,
        ]);

        if ($id <= 0) {
            $this->json(['ok' => false, 'error' => 'No se pudo crear el talle.'], 500);
        }

        $this->json(['ok' => true, 'id' => $id, 'nombre' => $nombre]);
    }
}