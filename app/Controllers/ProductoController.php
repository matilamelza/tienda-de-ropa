<?php

class ProductoController extends Controller
{
    // ─── PRODUCTOS ─────────────────────────────────────────────────────────────

    public function index()
    {
        $productoModel = new Producto();

        $this->view('productos/index', [
            'productos' => $productoModel->listar()
        ]);
    }

    public function crear()
    {
        $categoriaModel = new Categoria();
        $marcaModel     = new Marca();

        $this->view('productos/form', [
            'producto'   => null,
            'categorias' => $categoriaModel->listarActivas(),
            'marcas'     => $marcaModel->listarActivas()
        ]);
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $nombre = trim($_POST['nombre'] ?? '');

        $data = [
            'id_categoria' => (int) $_POST['id_categoria'],
            'id_marca'     => !empty($_POST['id_marca']) ? (int) $_POST['id_marca'] : null,
            'nombre'       => $nombre,
            'slug'         => generarSlug($nombre),
            'descripcion'  => trim($_POST['descripcion'] ?? ''),
            'precio_base'  => (float) $_POST['precio_base'],
            'activo'       => isset($_POST['activo']) ? 1 : 0,
            'destacado'    => isset($_POST['destacado']) ? 1 : 0
        ];

        $productoModel = new Producto();
        $id = $productoModel->guardar($data);

        $this->redirect(BASE_URL . '/admin/productos?ok=creado');
    }

    public function editar()
    {
        $id = (int) ($_GET['id'] ?? 0);

        $productoModel  = new Producto();
        $categoriaModel = new Categoria();
        $marcaModel     = new Marca();

        $producto = $productoModel->buscarPorId($id);

        if (!$producto) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $this->view('productos/form', [
            'producto'   => $producto,
            'categorias' => $categoriaModel->listarActivas(),
            'marcas'     => $marcaModel->listarActivas()
        ]);
    }

    public function actualizar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $id     = (int) ($_POST['id_producto'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        $data = [
            'id_categoria' => (int) $_POST['id_categoria'],
            'id_marca'     => !empty($_POST['id_marca']) ? (int) $_POST['id_marca'] : null,
            'nombre'       => $nombre,
            'slug'         => generarSlug($nombre),
            'descripcion'  => trim($_POST['descripcion'] ?? ''),
            'precio_base'  => (float) $_POST['precio_base'],
            'activo'       => isset($_POST['activo']) ? 1 : 0,
            'destacado'    => isset($_POST['destacado']) ? 1 : 0
        ];

        $productoModel = new Producto();
        $productoModel->actualizar($id, $data);

        $this->redirect(BASE_URL . '/admin/productos?ok=actualizado');
    }

    public function eliminar()
    {
        $id = (int) ($_GET['id'] ?? 0);

        $productoModel = new Producto();
        $productoModel->eliminar($id);

        $this->redirect(BASE_URL . '/admin/productos?ok=eliminado');
    }

    // ─── VARIANTES ─────────────────────────────────────────────────────────────

    public function variantes()
    {
        $id_producto = (int) ($_GET['id'] ?? 0);

        if ($id_producto <= 0) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $productoModel = new Producto();
        $talleModel    = new Talle();
        $colorModel    = new Color();

        $producto  = $productoModel->buscarPorId($id_producto);
        $variantes = $productoModel->listarVariantes($id_producto);
        $talles    = $talleModel->listarActivos();
        $colores   = $colorModel->listarActivos();

        $this->view('productos/variantes', [
            'producto'  => $producto,
            'variantes' => $variantes,
            'talles'    => $talles,
            'colores'   => $colores
        ]);
    }

    public function guardarVariante()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $id_producto = (int) $_POST['id_producto'];

        $data = [
            'id_producto' => $id_producto,
            'id_talle'    => !empty($_POST['id_talle']) ? (int) $_POST['id_talle'] : null,
            'id_color'    => !empty($_POST['id_color']) ? (int) $_POST['id_color'] : null,
            'sku'         => trim($_POST['sku'] ?? ''),
            'precio'      => $_POST['precio'] !== '' ? (float) $_POST['precio'] : null,
            'stock'       => (int) $_POST['stock'],
            'activo'      => isset($_POST['activo']) ? 1 : 0
        ];

        $productoModel = new Producto();
        $productoModel->guardarVariante($data);

        $this->redirect(BASE_URL . '/admin/productos/variantes?id=' . $id_producto . '&ok=creada');
    }

    public function editarVariante()
    {
        $id_variante = (int) ($_GET['id'] ?? 0);

        $productoModel = new Producto();
        $talleModel    = new Talle();
        $colorModel    = new Color();

        $variante = $productoModel->buscarVariantePorId($id_variante);

        if (!$variante) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $talles  = $talleModel->listarActivos();
        $colores = $colorModel->listarActivos();

        $this->view('productos/variante_form', [
            'variante' => $variante,
            'talles'   => $talles,
            'colores'  => $colores
        ]);
    }

    public function actualizarVariante()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $id_variante = (int) $_POST['id_variante'];
        $id_producto = (int) $_POST['id_producto'];

        $data = [
            'id_talle' => !empty($_POST['id_talle']) ? (int) $_POST['id_talle'] : null,
            'id_color' => !empty($_POST['id_color']) ? (int) $_POST['id_color'] : null,
            'sku'      => trim($_POST['sku'] ?? ''),
            'precio'   => $_POST['precio'] !== '' ? (float) $_POST['precio'] : null,
            'stock'    => (int) $_POST['stock'],
            'activo'   => isset($_POST['activo']) ? 1 : 0
        ];

        $productoModel = new Producto();
        $productoModel->actualizarVariante($id_variante, $data);

        $this->redirect(BASE_URL . '/admin/productos/variantes?id=' . $id_producto . '&ok=actualizada');
    }

    public function eliminarVariante()
    {
        $id_variante = (int) ($_GET['id'] ?? 0);
        $id_producto = (int) ($_GET['id_producto'] ?? 0);

        $productoModel = new Producto();
        $productoModel->eliminarVariante($id_variante);

        $this->redirect(BASE_URL . '/admin/productos/variantes?id=' . $id_producto . '&ok=eliminada');
    }

    // ─── FOTOS ─────────────────────────────────────────────────────────────────

    public function fotos()
    {
        $id_producto = (int) ($_GET['id'] ?? 0);

        $productoModel = new Producto();

        $this->view('productos/fotos', [
            'producto' => $productoModel->buscarPorId($id_producto),
            'fotos'    => $productoModel->listarFotos($id_producto)
        ]);
    }

    public function subirFoto()
    {
        $id_producto = (int) $_POST['id_producto'];

        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== 0) {
            $this->redirect(BASE_URL . '/admin/productos/fotos?id=' . $id_producto);
        }

        $archivo = $_FILES['foto'];
        $ext     = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        // Solo imágenes
        $permitidos = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $permitidos)) {
            $this->redirect(BASE_URL . '/admin/productos/fotos?id=' . $id_producto . '&error=formato');
        }

        $nombre = uniqid('prod_') . '.' . $ext;
        $ruta   = __DIR__ . '/../../public/uploads/productos/' . $nombre;

        move_uploaded_file($archivo['tmp_name'], $ruta);

        $productoModel = new Producto();
        $productoModel->guardarFoto($id_producto, $nombre);

        $this->redirect(BASE_URL . '/admin/productos/fotos?id=' . $id_producto . '&ok=subida');
    }

    public function eliminarFoto()
    {
        $id_foto = (int) ($_GET['id'] ?? 0);

        $productoModel = new Producto();
        $id_producto   = $productoModel->eliminarFoto($id_foto);

        $this->redirect(BASE_URL . '/admin/productos/fotos?id=' . $id_producto . '&ok=eliminada');
    }
}