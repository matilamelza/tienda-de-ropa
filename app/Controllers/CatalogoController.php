<?php

class CatalogoController extends Controller
{
    /** Pantalla de opciones. */
    public function index()
    {
        // Si viene desde el listado de productos con seleccionados
        $ids = array_values(array_filter(array_map('intval', explode(',', $_GET['ids'] ?? ''))));

        $this->view('admin/catalogo/index', [
            'categorias' => (new Categoria())->listarActivas()->fetch_all(MYSQLI_ASSOC),
            'marcas'     => (new Marca())->listarActivas()->fetch_all(MYSQLI_ASSOC),
            'ids'        => $ids,
        ]);
    }

    /** El catálogo para imprimir / guardar como PDF. Sin el layout del admin. */
    public function ver()
    {
        $opciones = [
            'ids'       => array_values(array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')))),
            'categoria' => (int) ($_GET['categoria'] ?? 0),
            'marca'     => (int) ($_GET['marca'] ?? 0),
            'oferta'    => !empty($_GET['oferta']),
            'con_stock' => !empty($_GET['con_stock']),
        ];

        $titulo    = mb_substr(trim($_GET['titulo'] ?? ''), 0, 80);
        $precios   = ($_GET['precios'] ?? '1') === '1';
        $ajuste    = max(-90, min(200, (float) str_replace(',', '.', $_GET['ajuste'] ?? '0')));
        $columnas  = in_array((int) ($_GET['columnas'] ?? 3), [2, 3, 4], true) ? (int) $_GET['columnas'] : 3;

        $modelo    = new Producto();
        $productos = $modelo->paraCatalogo($opciones);
        $talles    = $modelo->tallesPorProducto(array_map('intval', array_column($productos, 'id_producto')));

        $conf = (new ConfiguracionTienda())->todas();

        // Vista suelta: no usa el layout del admin (se imprime)
        require __DIR__ . '/../Views/admin/catalogo/ver.php';
        exit;
    }
}