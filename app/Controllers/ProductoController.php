<?php

class ProductoController extends Controller
{
    public function index()
    {
        $productoModel = new Producto();
        $productos = $productoModel->listar();

        $this->view('productos/index', [
            'productos' => $productos
        ]);
    }

    public function crear()
    {
        $categoriaModel = new Categoria();
        $marcaModel = new Marca();

        $categorias = $categoriaModel->listarActivas();
        $marcas = $marcaModel->listarActivas();

        $this->view('productos/crear', [
            'categorias' => $categorias,
            'marcas' => $marcas
        ]);
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?route=productos');
        }

        $nombre = trim($_POST['nombre'] ?? '');

        $data = [
            'id_categoria' => (int) $_POST['id_categoria'],
            'id_marca' => !empty($_POST['id_marca']) ? (int) $_POST['id_marca'] : null,
            'nombre' => $nombre,
            'slug' => generarSlug($nombre),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio_base' => (float) $_POST['precio_base'],
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'destacado' => isset($_POST['destacado']) ? 1 : 0
        ];

        $productoModel = new Producto();
        $productoModel->guardar($data);

        $this->redirect('index.php?route=productos');
    }

    public function variantes()
    {
        $id_producto = (int) ($_GET['id'] ?? 0);

        if ($id_producto <= 0) {
            $this->redirect('index.php?route=productos');
        }

        $productoModel = new Producto();
        $talleModel = new Talle();
        $colorModel = new Color();

        $producto = $productoModel->buscarPorId($id_producto);
        $variantes = $productoModel->listarVariantes($id_producto);
        $talles = $talleModel->listarActivos();
        $colores = $colorModel->listarActivos();

        $this->view('productos/variantes', [
            'producto' => $producto,
            'variantes' => $variantes,
            'talles' => $talles,
            'colores' => $colores
        ]);
    }

    public function guardarVariante()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('index.php?route=productos');
        }

        $id_producto = (int) $_POST['id_producto'];

        $data = [
            'id_producto' => $id_producto,
            'id_talle' => !empty($_POST['id_talle']) ? (int) $_POST['id_talle'] : null,
            'id_color' => !empty($_POST['id_color']) ? (int) $_POST['id_color'] : null,
            'sku' => trim($_POST['sku'] ?? ''),
            'precio' => $_POST['precio'] !== '' ? (float) $_POST['precio'] : null,
            'stock' => (int) $_POST['stock'],
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];

        $productoModel = new Producto();
        $productoModel->guardarVariante($data);

        $this->redirect('index.php?route=productos_variantes&id=' . $id_producto);
    }

    public function fotos()
{
    $id_producto = (int) ($_GET['id'] ?? 0);

    $productoModel = new Producto();

    $producto = $productoModel->buscarPorId($id_producto);
    $fotos = $productoModel->listarFotos($id_producto);

    $this->view('productos/fotos', [
        'producto' => $producto,
        'fotos' => $fotos
    ]);
}

public function subirFoto()
{
    $id_producto = (int) $_POST['id_producto'];

    if (!isset($_FILES['foto'])) {
        $this->redirect("index.php?route=productos_fotos&id=$id_producto");
    }

    $archivo = $_FILES['foto'];

    if ($archivo['error'] === 0) {

        $ext = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombre = uniqid() . '.' . $ext;

        $ruta = __DIR__ . '/../../public/uploads/productos/' . $nombre;

        move_uploaded_file($archivo['tmp_name'], $ruta);

        $productoModel = new Producto();
        $productoModel->guardarFoto($id_producto, $nombre);
    }

    $this->redirect("index.php?route=productos_fotos&id=$id_producto");
}
}