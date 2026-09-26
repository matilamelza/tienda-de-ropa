<?php

class ProductoController extends Controller
{
    private const FOTO_MAX_BYTES = 5 * 1024 * 1024; // 5 MB
    private const FOTOS_POR_PRODUCTO = 10;
    private const FOTO_MIMES     = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    // ─── PRODUCTOS ─────────────────────────────────────────────────────────────

        public function index()
    {
        $porPagina = 25;
        $pagina    = max(1, (int) ($_GET['pagina'] ?? 1));

        $filtros = [
            'q'         => trim($_GET['q'] ?? ''),
            'categoria' => (int) ($_GET['categoria'] ?? 0),
            'estado'    => in_array($_GET['estado'] ?? '', ['activos', 'inactivos'], true) ? $_GET['estado'] : '',
            'problema'  => in_array($_GET['problema'] ?? '', ['agotados', 'faltantes', 'sin_foto', 'sin_costo'], true) ? $_GET['problema'] : '',
        ];

        $productoModel  = new Producto();
        $categoriaModel = new Categoria();

        $total = $productoModel->contarAdmin($filtros);

        $this->view('productos/index', [
            'productos'    => $productoModel->listarAdmin($filtros, $pagina, $porPagina),
            'total'        => $total,
            'pagina'       => $pagina,
            'totalPaginas' => (int) ceil($total / $porPagina),
            'filtros'      => $filtros,
            'categorias'   => $categoriaModel->listarActivas(),
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
        $costoRaw     = trim($_POST['precio_costo'] ?? '');

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
            'precio_costo' => $costoRaw !== '' && (float) $costoRaw >= 0 ? (float) $costoRaw : null,
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
            'fotos'    => $productoModel->listarFotos($id_producto),
            'maxFotos' => self::FOTOS_POR_PRODUCTO,
            
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
            error_log('subirFoto: error de subida PHP código ' . ($_FILES['foto']['error'] ?? 'sin archivo'));
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

        // Carpeta de destino: se crea si no existe (ej: después de clonar el repo)
        $carpeta = __DIR__ . '/../../public/uploads/productos';

        if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true)) {
            error_log('subirFoto: no se pudo crear la carpeta ' . $carpeta);
            $this->redirect($volver . '&error=subida');
        }

        $nombre = 'prod_' . bin2hex(random_bytes(10)) . '.' . self::FOTO_MIMES[$mime];
        $ruta   = $carpeta . '/' . $nombre;

        if (!move_uploaded_file($archivo['tmp_name'], $ruta)) {
            error_log('subirFoto: move_uploaded_file falló hacia ' . $ruta . ' (¿permisos de la carpeta?)');
            $this->redirect($volver . '&error=subida');
        }

        $productoModel = new Producto();
        $productoModel->guardarFoto($id_producto, $nombre);

        $this->redirect($volver . '&ok=subida');
    }

        /**
     * AJAX: sube UNA foto (la vista las manda de a una).
     * Responde: { ok, id_foto, imagen, url, total } o { ok: false, error }
     */
    public function subirFotoAjax()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'error' => 'Método no permitido'], 405);
        }

        $id_producto   = (int) ($_POST['id_producto'] ?? 0);
        $productoModel = new Producto();

        if ($id_producto <= 0 || !$productoModel->buscarPorId($id_producto)) {
            $this->json(['ok' => false, 'error' => 'Producto no encontrado'], 404);
        }

        if ($productoModel->contarFotos($id_producto) >= self::FOTOS_POR_PRODUCTO) {
            $this->json(['ok' => false, 'error' => 'Máximo ' . self::FOTOS_POR_PRODUCTO . ' fotos por producto'], 409);
        }

        $archivo = $_FILES['foto'] ?? null;

        if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
            error_log('subirFotoAjax: error PHP ' . ($archivo['error'] ?? 'sin archivo'));
            $this->json(['ok' => false, 'error' => 'No llegó la imagen'], 400);
        }

        if ($archivo['size'] > self::FOTO_MAX_BYTES) {
            $this->json(['ok' => false, 'error' => 'La imagen supera los 5 MB'], 400);
        }

        $carpeta = __DIR__ . '/../../public/uploads/productos';

        if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true)) {
            error_log('subirFotoAjax: no se pudo crear ' . $carpeta);
            $this->json(['ok' => false, 'error' => 'Error del servidor'], 500);
        }

        $nombre = procesar_imagen($archivo['tmp_name'], $carpeta, 'prod_');

        if ($nombre === null) {
            $this->json(['ok' => false, 'error' => 'Formato no válido (usá JPG, PNG o WebP)'], 400);
        }

        $id_foto = $productoModel->guardarFoto($id_producto, $nombre);

        if ($id_foto <= 0) {
            @unlink($carpeta . '/' . $nombre);
            $this->json(['ok' => false, 'error' => 'No se pudo guardar la foto'], 500);
        }

        $this->json([
            'ok'      => true,
            'id_foto' => $id_foto,
            'imagen'  => $nombre,
            'url'     => BASE_URL . '/public/uploads/productos/' . $nombre,
            'total'   => $productoModel->contarFotos($id_producto),
        ]);
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

        /**
     * AJAX: guarda el orden de las fotos.
     * Recibe por POST: id_producto, ids[] (en el orden nuevo) y csrf_token.
     * Responde JSON: { ok: true } o { ok: false, error: "..." }
     */
    public function ordenarFotos()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            exit;
        }

        $id_producto = (int) ($_POST['id_producto'] ?? 0);
        $ids         = $_POST['ids'] ?? [];

        if ($id_producto <= 0 || !is_array($ids) || empty($ids)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
            exit;
        }

        $productoModel = new Producto();
        $ok            = $productoModel->guardarOrdenFotos($id_producto, $ids);

        if (!$ok) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el orden']);
            exit;
        }

        echo json_encode(['ok' => true]);
        exit;
    }
}