<?php

class ProductoController extends Controller
{
    private const FOTO_MAX_BYTES = 5 * 1024 * 1024; // 5 MB
    private const FOTO_MIMES     = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

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

        $data = $this->datosProductoDesdePost();

        if ($data === null) {
            $this->redirect(BASE_URL . '/admin/productos/crear?error=datos');
        }

        $productoModel = new Producto();
        $productoModel->guardar($data);

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

        $id   = (int) ($_POST['id_producto'] ?? 0);
        $data = $this->datosProductoDesdePost();

        if ($id <= 0) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        if ($data === null) {
            $this->redirect(BASE_URL . '/admin/productos/editar?id=' . $id . '&error=datos');
        }

        $productoModel = new Producto();
        $productoModel->actualizar($id, $data);

        $this->redirect(BASE_URL . '/admin/productos?ok=actualizado');
    }

    public function eliminar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $productoModel = new Producto();

        if (!$productoModel->eliminar($id)) {
            $this->redirect(BASE_URL . '/admin/productos?error=eliminar');
        }

        $this->redirect(BASE_URL . '/admin/productos?ok=eliminado');
    }

    /** Arma y valida los datos del producto. Devuelve null si falta algo obligatorio. */
    private function datosProductoDesdePost(): ?array
    {
        $nombre       = trim($_POST['nombre'] ?? '');
        $id_categoria = (int) ($_POST['id_categoria'] ?? 0);
        $precioRaw    = $_POST['precio_base'] ?? '';

        if ($nombre === '' || $id_categoria <= 0 || $precioRaw === '' || (float) $precioRaw < 0) {
            return null;
        }

        return [
            'id_categoria' => $id_categoria,
            'id_marca'     => !empty($_POST['id_marca']) ? (int) $_POST['id_marca'] : null,
            'nombre'       => $nombre,
            'slug'         => generarSlug($nombre),
            'descripcion'  => trim($_POST['descripcion'] ?? ''),
            'precio_base'  => (float) $precioRaw,
            'activo'       => isset($_POST['activo']) ? 1 : 0,
            'destacado'    => isset($_POST['destacado']) ? 1 : 0
        ];
    }

    // ─── VARIANTES ─────────────────────────────────────────────────────────────

    public function variantes()
    {
        $id_producto = (int) ($_GET['id'] ?? 0);

        $productoModel = new Producto();
        $producto      = $productoModel->buscarPorId($id_producto);

        if (!$producto) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $talleModel = new Talle();
        $colorModel = new Color();

        $this->view('productos/variantes', [
            'producto'  => $producto,
            'variantes' => $productoModel->listarVariantes($id_producto),
            'talles'    => $talleModel->listarActivos(),
            'colores'   => $colorModel->listarActivos()
        ]);
    }

    public function guardarVariante()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $id_producto = (int) ($_POST['id_producto'] ?? 0);

        if ($id_producto <= 0) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $data = [
            'id_producto' => $id_producto,
            'id_talle'    => !empty($_POST['id_talle']) ? (int) $_POST['id_talle'] : null,
            'id_color'    => !empty($_POST['id_color']) ? (int) $_POST['id_color'] : null,
            'sku'         => trim($_POST['sku'] ?? ''),
            'precio'      => ($_POST['precio'] ?? '') !== '' ? (float) $_POST['precio'] : null,
            'stock'       => max(0, (int) ($_POST['stock'] ?? 0)),
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
        $variante      = $productoModel->buscarVariantePorId($id_variante);

        if (!$variante) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $talleModel = new Talle();
        $colorModel = new Color();

        $this->view('productos/variante_form', [
            'variante' => $variante,
            'talles'   => $talleModel->listarParaVariante($variante['id_talle'] ? (int) $variante['id_talle'] : null),
            'colores'  => $colorModel->listarParaVariante($variante['id_color'] ? (int) $variante['id_color'] : null)
        ]);
    }

    public function actualizarVariante()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $id_variante = (int) ($_POST['id_variante'] ?? 0);
        $id_producto = (int) ($_POST['id_producto'] ?? 0);

        $productoModel = new Producto();
        $variante      = $productoModel->buscarVariantePorId($id_variante);

        if (!$variante) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        // El stock nunca puede quedar por debajo de lo reservado
        $reservado = (int) ($variante['stock_reservado'] ?? 0);
        $stock     = max($reservado, (int) ($_POST['stock'] ?? 0));

        $data = [
            'id_talle' => !empty($_POST['id_talle']) ? (int) $_POST['id_talle'] : null,
            'id_color' => !empty($_POST['id_color']) ? (int) $_POST['id_color'] : null,
            'sku'      => trim($_POST['sku'] ?? ''),
            'precio'   => ($_POST['precio'] ?? '') !== '' ? (float) $_POST['precio'] : null,
            'stock'    => $stock,
            'activo'   => isset($_POST['activo']) ? 1 : 0
        ];

        $productoModel->actualizarVariante($id_variante, $data);

        $this->redirect(BASE_URL . '/admin/productos/variantes?id=' . $id_producto . '&ok=actualizada');
    }

    public function eliminarVariante()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $id_variante = (int) ($_POST['id'] ?? 0);
        $id_producto = (int) ($_POST['id_producto'] ?? 0);

        if ($id_variante > 0) {
            $productoModel = new Producto();
            $productoModel->eliminarVariante($id_variante);
        }

        $this->redirect(BASE_URL . '/admin/productos/variantes?id=' . $id_producto . '&ok=eliminada');
    }

    // ─── FOTOS ─────────────────────────────────────────────────────────────────

    public function fotos()
    {
        $id_producto = (int) ($_GET['id'] ?? 0);

        $productoModel = new Producto();
        $producto      = $productoModel->buscarPorId($id_producto);

        if (!$producto) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $this->view('productos/fotos', [
            'producto' => $producto,
            'fotos'    => $productoModel->listarFotos($id_producto)
        ]);
    }

    public function subirFoto()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $id_producto = (int) ($_POST['id_producto'] ?? 0);
        $volver      = BASE_URL . '/admin/productos/fotos?id=' . $id_producto;

        if ($id_producto <= 0) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $error = ($_FILES['foto']['error'] ?? null) === UPLOAD_ERR_INI_SIZE ? 'tamano' : 'subida';
            $this->redirect($volver . '&error=' . $error);
        }

        $archivo = $_FILES['foto'];

        if ($archivo['size'] > self::FOTO_MAX_BYTES) {
            $this->redirect($volver . '&error=tamano');
        }

        // Verificar que sea una imagen real, no solo la extensión
        $info = @getimagesize($archivo['tmp_name']);
        $mime = $info['mime'] ?? '';

        if (!isset(self::FOTO_MIMES[$mime])) {
            $this->redirect($volver . '&error=formato');
        }

        $nombre = uniqid('prod_', true) . '.' . self::FOTO_MIMES[$mime];
        $nombre = str_replace('.', '', substr($nombre, 0, strrpos($nombre, '.'))) . '.' . self::FOTO_MIMES[$mime];
        $ruta   = __DIR__ . '/../../public/uploads/productos/' . $nombre;

        if (!move_uploaded_file($archivo['tmp_name'], $ruta)) {
            $this->redirect($volver . '&error=subida');
        }

        $productoModel = new Producto();
        $productoModel->guardarFoto($id_producto, $nombre);

        $this->redirect($volver . '&ok=subida');
    }

    public function eliminarFoto()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $id_foto = (int) ($_POST['id'] ?? 0);

        $productoModel = new Producto();
        $id_producto   = $productoModel->eliminarFoto($id_foto);

        if (!$id_producto) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $this->redirect(BASE_URL . '/admin/productos/fotos?id=' . $id_producto . '&ok=eliminada');
    }
}