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

        // ── Estadísticas ─────────────────────────────────────────────────────
        if ($filtros['q'] !== '') {
            registrar_visita('busqueda', null, $filtros['q'], count($productos));
        } elseif ($categoriaActual) {
            registrar_visita('categoria', (int) $categoriaActual['id_categoria']);
        } elseif ($filtros['marca'] > 0) {
            registrar_visita('marca', $filtros['marca']);
        } elseif (!$hayFiltros) {
            registrar_visita('inicio');
        } else {
            registrar_visita('otra');
        }

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

        // No existe, fue eliminado o está desactivado → 404
        if (!$producto || (int) $producto['activo'] !== 1) {
            $this->noEncontrado(
                'Este producto ya no está disponible',
                'Puede que se haya agotado o que lo hayamos dado de baja. Mirá lo que tenemos ahora.'
            );
            return;
        }

        registrar_visita('producto', (int) $producto['id_producto']);

        $id        = $producto['id_producto'];
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

    /** Página 404 con el layout de la tienda. */
    public function noEncontrado(?string $titulo = null, ?string $mensaje = null): void
    {
        http_response_code(404);

        $categoriaModel = new Categoria();

        $this->view('errores/404', [
            'titulo'         => $titulo,
            'mensaje'        => $mensaje,
            'categoriasMenu' => $categoriaModel->listarMenu(),
        ], 'tienda');
    }
}