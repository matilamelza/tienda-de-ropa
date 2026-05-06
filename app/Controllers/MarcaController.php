<?php

class MarcaController extends Controller
{
    public function index()
    {
        $marcaModel = new Marca();

        $this->view('admin/marcas/index', [
            'marcas' => $marcaModel->listarTodas()
        ]);
    }

    public function crear()
    {
        $this->view('admin/marcas/form', [
            'marca' => null
        ]);
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/marcas');
        }

        $nombre = trim($_POST['nombre'] ?? '');

        if (empty($nombre)) {
            $this->redirect(BASE_URL . '/admin/marcas/crear?error=nombre');
        }

        $data = [
            'nombre' => $nombre,
            'slug'   => generarSlug($nombre),
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];

        $marcaModel = new Marca();
        $marcaModel->crear($data);

        $this->redirect(BASE_URL . '/admin/marcas?ok=creada');
    }

    public function editar()
    {
        $id = (int) ($_GET['id'] ?? 0);

        $marcaModel = new Marca();
        $marca = $marcaModel->buscarPorId($id);

        if (!$marca) {
            $this->redirect(BASE_URL . '/admin/marcas');
        }

        $this->view('admin/marcas/form', [
            'marca' => $marca
        ]);
    }

    public function actualizar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/marcas');
        }

        $id     = (int) ($_POST['id_marca'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if (empty($nombre)) {
            $this->redirect(BASE_URL . '/admin/marcas/editar?id=' . $id . '&error=nombre');
        }

        $data = [
            'nombre' => $nombre,
            'slug'   => generarSlug($nombre),  
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];

        $marcaModel = new Marca();
        $marcaModel->actualizar($id, $data);

        $this->redirect(BASE_URL . '/admin/marcas?ok=actualizada');
    }

    public function eliminar()
    {
        $id = (int) ($_GET['id'] ?? 0);

        $marcaModel = new Marca();

        if ($marcaModel->tieneProductos($id)) {
            $this->redirect(BASE_URL . '/admin/marcas?error=tiene_productos');
        }

        $marcaModel->eliminar($id);

        $this->redirect(BASE_URL . '/admin/marcas?ok=eliminada');
    }
}