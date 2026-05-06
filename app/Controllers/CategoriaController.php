<?php

class CategoriaController extends Controller
{
    public function index()
    {
        $categoriaModel = new Categoria();

        $this->view('admin/categorias/index', [
            'categorias' => $categoriaModel->listarTodas()
        ]);
    }

    public function crear()
    {
        $this->view('admin/categorias/form', [
            'categoria' => null
        ]);
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/categorias');
        }

        $nombre = trim($_POST['nombre'] ?? '');

        if (empty($nombre)) {
            $this->redirect(BASE_URL . '/admin/categorias/crear?error=nombre');
        }

        $data = [
            'nombre' => $nombre,
            'slug'   => generarSlug($nombre),
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];

        $categoriaModel = new Categoria();
        $categoriaModel->crear($data);

        $this->redirect(BASE_URL . '/admin/categorias?ok=creada');
    }

    public function editar()
    {
        $id = (int) ($_GET['id'] ?? 0);

        $categoriaModel = new Categoria();
        $categoria = $categoriaModel->buscarPorId($id);

        if (!$categoria) {
            $this->redirect(BASE_URL . '/admin/categorias');
        }

        $this->view('admin/categorias/form', [
            'categoria' => $categoria
        ]);
    }

    public function actualizar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/categorias');
        }

        $id     = (int) ($_POST['id_categoria'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if (empty($nombre)) {
            $this->redirect(BASE_URL . '/admin/categorias/editar?id=' . $id . '&error=nombre');
        }

        $data = [
            'nombre' => $nombre,
            'slug'   => generarSlug($nombre),
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];

        $categoriaModel = new Categoria();
        $categoriaModel->actualizar($id, $data);

        $this->redirect(BASE_URL . '/admin/categorias?ok=actualizada');
    }

    public function eliminar()
    {
        $id = (int) ($_GET['id'] ?? 0);

        $categoriaModel = new Categoria();

        if ($categoriaModel->tieneProdutos($id)) {
            $this->redirect(BASE_URL . '/admin/categorias?error=tiene_productos');
        }

        $categoriaModel->eliminar($id);

        $this->redirect(BASE_URL . '/admin/categorias?ok=eliminada');
    }
}