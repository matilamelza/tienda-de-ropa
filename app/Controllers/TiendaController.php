<?php

class TiendaController extends Controller
{
    public function index()
    {
        $productoModel  = new Producto();
        $categoriaModel = new Categoria();
        $cfgModel       = new ConfiguracionTienda();

        $categoriasMenu = $categoriaModel->listarMenu();

        // ── Leer filtros del GET ─────────────────────────────────────────────
        $filtros = [
            'q'          => trim($_GET['q']          ?? ''),
            'categoria'  => trim($_GET['categoria']  ?? ''),
            'marca'      => (int)($_GET['marca']     ?? 0),
            'precio_min' => $_GET['precio_min']      ?? '',
            'precio_max' => $_GET['precio_max']      ?? '',
            'orden'      => $_GET['orden']           ?? 'reciente',
        ];

        $hayFiltros = $filtros['q'] !== ''
            || $filtros['categoria'] !== ''
            || $filtros['marca'] > 0
            || $filtros['precio_min'] !== ''
            || $filtros['precio_max'] !== ''
            || $filtros['orden'] !== 'reciente';

        // ── Datos para los selects de filtro ─────────────────────────────────
        $categorias  = $categoriaModel->listarActivas();
        $marcas      = $productoModel->listarMarcasActivas();
        $rangoPrecio = $productoModel->rangoPrecio();

        // ── Categoría actual (para el breadcrumb) ────────────────────────────
        $categoriaActual = null;
        if ($filtros['categoria'] !== '') {
            $categoriaActual = $categoriaModel->buscarPorSlug($filtros['categoria']);
        }

        // ── Productos ────────────────────────────────────────────────────────
        $productos = $productoModel->buscarFiltrado($filtros);

        $this->view('tienda/index', [
            'productos'      => $productos,
            'categoriasMenu' => $categoriasMenu,
            'categoriaActual'=> $categoriaActual,
            'config'         => $cfgModel->todas(),
            'filtros'        => $filtros,
            'categorias'     => $categorias,
            'marcas'         => $marcas,
            'rangoPrecio'    => $rangoPrecio,
            'hayFiltros'     => $hayFiltros,
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
            'producto'       => $producto,
            'variantes'      => $variantes,
            'fotos'          => $fotos,
            'categoriasMenu' => $categoriaModel->listarMenu(),
            'config'         => $cfgModel->todas(),
        ], 'tienda');
    }
}