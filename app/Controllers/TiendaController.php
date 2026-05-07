<?php

class TiendaController extends Controller
{
    public function index()
    {
        $productoModel  = new Producto();
        $categoriaModel = new Categoria();
        $cfgModel       = new ConfiguracionTienda();

        $categoriasMenu  = $categoriaModel->listarMenu();
        $categoriaSlug   = $_GET['categoria'] ?? null;
        $categoriaActual = null;

        if ($categoriaSlug) {
            $categoriaActual = $categoriaModel->buscarPorSlug($categoriaSlug);
            $productos       = $productoModel->listarPorCategoriaSlug($categoriaSlug);
        } else {
            $productos = $productoModel->listar();
        }

        $this->view('tienda/index', [
            'productos'       => $productos,
            'categoriasMenu'  => $categoriasMenu,
            'categoriaActual' => $categoriaActual,
            'config'          => $cfgModel->todas(),
        ], 'tienda');
    }

    public function detalle()
    {
        $productoModel  = new Producto();
        $categoriaModel = new Categoria();
        $cfgModel       = new ConfiguracionTienda();

        if (isset($_GET['slug'])) {
            $producto = $productoModel->buscarPorSlug($_GET['slug']);
        } else {
            $id       = (int) ($_GET['id'] ?? 0);
            $producto = $productoModel->buscarPorId($id);
        }

        if (!$producto) {
            $this->redirect('tienda');
        }

        $id       = $producto['id_producto'];
        $variantes = $productoModel->listarVariantes($id);
        $fotos     = $productoModel->listarFotos($id);

        $this->view('tienda/detalle', [
            'producto'      => $producto,
            'variantes'     => $variantes,
            'fotos'         => $fotos,
            'categoriasMenu'=> $categoriaModel->listarMenu(),
            'config'        => $cfgModel->todas(),
        ], 'tienda');
    }
}