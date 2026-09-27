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
        // Recordar cómo estaba el listado para volver igual
        $_SESSION['productos_lista_url'] = $_SERVER['REQUEST_URI'] ?? BASE_URL . '/admin/productos';

        $porPagina = 25;
        $pagina    = max(1, (int) ($_GET['pagina'] ?? 1));
        $filtros   = $this->filtrosDesde($_GET);

        $productoModel = new Producto();
        $total         = $productoModel->contarAdmin($filtros);
        $productos     = $productoModel->listarAdmin($filtros, $pagina, $porPagina);

        $this->view('productos/index', [
            'productos'    => $productos,
            'talles'       => $productoModel->tallesPorProducto(array_map('intval', array_column($productos, 'id_producto'))),
            'total'        => $total,
            'pagina'       => $pagina,
            'totalPaginas' => (int) ceil($total / $porPagina),
            'filtros'      => $filtros,
            'categorias'   => (new Categoria())->listarActivas()->fetch_all(MYSQLI_ASSOC),
            'marcas'       => (new Marca())->listarActivas()->fetch_all(MYSQLI_ASSOC),
        ]);
    }
    
        /** AJAX: invierte activo o destacado de un producto. */
    public function toggle()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false], 405);
        }

        $valor = (new Producto())->toggleCampo((int) ($_POST['id'] ?? 0), $_POST['campo'] ?? '');

        if ($valor === null) {
            $this->json(['ok' => false, 'error' => 'No se pudo cambiar'], 400);
        }

        $this->json(['ok' => true, 'valor' => $valor]);
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
        $id = $productoModel->guardar($data);

       $this->redirect(BASE_URL . '/admin/productos/variantes?id=' . (int) $id . '&ok=producto_creado');
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

        $this->redirect(BASE_URL . '/admin/productos/editar?id=' . $id . '&ok=actualizado');
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

        $_SESSION['productos_msg'] = ['ok', 'Producto eliminado.'];
        $this->redirect(url_listado_productos());
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

        /** Lee los filtros del listado desde un array (GET o POST). */
        /** Lee los filtros del listado desde un array (GET o POST). */
    private function filtrosDesde(array $src): array
    {
        return [
            'q'         => trim($src['q'] ?? ''),
            'categoria' => (int) ($src['categoria'] ?? 0),
            'estado'    => in_array($src['estado'] ?? '', ['activos', 'inactivos'], true) ? $src['estado'] : '',
            'problema'  => in_array($src['problema'] ?? '', ['agotados', 'faltantes', 'sin_foto', 'sin_costo'], true) ? $src['problema'] : '',
            'orden'     => isset(Producto::ORDENES[$src['orden'] ?? '']) ? $src['orden'] : '',
        ];
    }

        public function masivo()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $modelo = new Producto();
        $accion = $_POST['accion'] ?? '';

        // ¿Todos los del filtro, o los tildados?
        $ids = !empty($_POST['todos_filtro'])
            ? $modelo->idsAdmin($this->filtrosDesde($_POST))
            : array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])))));

        $volver = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/admin/productos';

        if (empty($ids)) {
            $_SESSION['productos_msg'] = ['error', 'No hay productos seleccionados.'];
            $this->redirect($volver);
        }

        $n = count($ids);

        switch ($accion) {
            case 'activar':
                $modelo->actualizarCampoMasivo($ids, 'activo', 1);
                $msg = "$n producto(s) activados.";
                break;

            case 'desactivar':
                $modelo->actualizarCampoMasivo($ids, 'activo', 0);
                $msg = "$n producto(s) desactivados.";
                break;

            case 'destacar':
                $modelo->actualizarCampoMasivo($ids, 'destacado', 1);
                $msg = "$n producto(s) destacados.";
                break;

            case 'quitar_destacado':
                $modelo->actualizarCampoMasivo($ids, 'destacado', 0);
                $msg = "Se quitó el destacado de $n producto(s).";
                break;

            case 'categoria':
                $idCat   = (int) ($_POST['id_categoria'] ?? 0);
                $validas = array_map('intval', array_column((new Categoria())->listarActivas()->fetch_all(MYSQLI_ASSOC), 'id_categoria'));
                if (!in_array($idCat, $validas, true)) {
                    $_SESSION['productos_msg'] = ['error', 'Elegí una categoría.'];
                    $this->redirect($volver);
                }
                $modelo->actualizarCampoMasivo($ids, 'id_categoria', $idCat);
                $msg = "Se cambió la categoría de $n producto(s).";
                break;

            case 'marca':
                $idMarca = (int) ($_POST['id_marca'] ?? -1);
                $validas = array_map('intval', array_column((new Marca())->listarActivas()->fetch_all(MYSQLI_ASSOC), 'id_marca'));
                if ($idMarca !== 0 && !in_array($idMarca, $validas, true)) {
                    $_SESSION['productos_msg'] = ['error', 'Elegí una marca.'];
                    $this->redirect($volver);
                }
                $modelo->actualizarCampoMasivo($ids, 'id_marca', $idMarca ?: null);
                $msg = "Se cambió la marca de $n producto(s).";
                break;

            case 'precios':
                $pct      = (float) str_replace(',', '.', $_POST['pct'] ?? '0');
                $pct      = ($_POST['direccion'] ?? 'subir') === 'bajar' ? -abs($pct) : abs($pct);
                $redondeo = (int) ($_POST['redondeo'] ?? 0);

                if ($pct == 0 || abs($pct) > 500 || !in_array($redondeo, [0, 10, 100, 500, 1000], true)) {
                    $_SESSION['productos_msg'] = ['error', 'Revisá el porcentaje (entre 0 y 500) y el redondeo.'];
                    $this->redirect($volver);
                }
                if ($pct <= -100) {
                    $_SESSION['productos_msg'] = ['error', 'No se puede bajar un 100% o más.'];
                    $this->redirect($volver);
                }

                $n   = $modelo->ajustarPrecios($ids, $pct, $redondeo, !empty($_POST['con_costo']), !empty($_POST['con_variantes']));
                $msg = ($pct > 0 ? 'Aumento' : 'Rebaja') . ' del ' . rtrim(rtrim(number_format(abs($pct), 2, ',', ''), '0'), ',') . "% aplicado a $n producto(s).";
                break;

            case 'eliminar':
                foreach ($ids as $id) {
                    $modelo->eliminar($id);
                }
                $msg = "$n producto(s) eliminados.";
                break;

            default:
                $this->redirect($volver);
        }

        $_SESSION['productos_msg'] = ['ok', $msg];
        $this->redirect($volver);
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

        $this->view('productos/variantes', [
            'producto'    => $producto,
            'variantes'   => $productoModel->listarVariantes($id_producto),
            'talles'      => (new Talle())->listarActivos()->fetch_all(MYSQLI_ASSOC),
            'colores'     => (new Color())->listarActivos()->fetch_all(MYSQLI_ASSOC),
            'existentes'  => $productoModel->combinacionesExistentes($id_producto),
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

        public function guardarVariantesMasivo()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $id_producto   = (int) ($_POST['id_producto'] ?? 0);
        $productoModel = new Producto();

        if (!$productoModel->buscarPorId($id_producto)) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $volver = BASE_URL . '/admin/productos/variantes?id=' . $id_producto;

        // Talles y colores válidos (activos)
        $tallesValidos  = array_column((new Talle())->listarActivos()->fetch_all(MYSQLI_ASSOC), 'id_talle');
        $coloresValidos = array_column((new Color())->listarActivos()->fetch_all(MYSQLI_ASSOC), 'id_color');

        $tallesValidos  = array_map('intval', $tallesValidos);
        $coloresValidos = array_map('intval', $coloresValidos);

        $combos = [];
        foreach ((array) ($_POST['combos'] ?? []) as $c) {
            $idTalle = (int) ($c['talle'] ?? 0);
            $idColor = (int) ($c['color'] ?? 0);   // 0 = sin color

            if (!in_array($idTalle, $tallesValidos, true)) {
                continue;
            }
            if ($idColor !== 0 && !in_array($idColor, $coloresValidos, true)) {
                continue;
            }

            $combos[] = [
                'id_talle' => $idTalle,
                'id_color' => $idColor ?: null,
                'stock'    => max(0, (int) ($c['stock'] ?? 0)),
            ];
        }

        if (empty($combos)) {
            $this->redirect($volver . '&error=sin_combinaciones');
        }

        $precio = ($_POST['precio'] ?? '') !== '' ? max(0, (float) $_POST['precio']) : null;
        $activo = isset($_POST['activo']) ? 1 : 0;

        [$creadas, $omitidas] = $productoModel->crearVariantesMasivo($id_producto, $combos, $precio, $activo);

        $this->redirect($volver . '&ok=masivo&c=' . $creadas . '&o=' . $omitidas);
    }

        public function editarVariante()
    {
        $id_variante = (int) ($_GET['id'] ?? 0);

        $productoModel = new Producto();
        $variante      = $productoModel->buscarVariantePorId($id_variante);

        if (!$variante) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $producto = $productoModel->buscarPorId((int) $variante['id_producto']);

        if (!$producto) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $this->view('productos/variante_form', [
            'producto' => $producto,
            'variante' => $variante,
            'talles'   => (new Talle())->listarParaVariante($variante['id_talle'] ? (int) $variante['id_talle'] : null),
            'colores'  => (new Color())->listarParaVariante($variante['id_color'] ? (int) $variante['id_color'] : null),
        ]);
    }

    public function actualizarVariante()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $id_variante = (int) ($_POST['id_variante'] ?? 0);

        $productoModel = new Producto();
        $variante      = $productoModel->buscarVariantePorId($id_variante);

        if (!$variante) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $id_producto = (int) $variante['id_producto'];   // del registro, no del formulario
        $id_talle    = !empty($_POST['id_talle']) ? (int) $_POST['id_talle'] : null;
        $id_color    = !empty($_POST['id_color']) ? (int) $_POST['id_color'] : null;

        if ($id_talle === null) {
            $this->redirect(BASE_URL . '/admin/productos/editar-variante?id=' . $id_variante . '&error=talle');
        }

        if ($productoModel->existeCombinacion($id_producto, $id_talle, $id_color, $id_variante)) {
            $this->redirect(BASE_URL . '/admin/productos/editar-variante?id=' . $id_variante . '&error=duplicada');
        }

        // El stock nunca puede quedar por debajo de lo reservado
        $reservado = (int) ($variante['stock_reservado'] ?? 0);
        $stock     = max($reservado, (int) ($_POST['stock'] ?? 0));

        $productoModel->actualizarVariante($id_variante, [
            'id_talle' => $id_talle,
            'id_color' => $id_color,
            'sku'      => trim($_POST['sku'] ?? ''),
            'precio'   => ($_POST['precio'] ?? '') !== '' ? (float) $_POST['precio'] : null,
            'stock'    => $stock,
            'activo'   => isset($_POST['activo']) ? 1 : 0,
        ]);

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

       public function actualizarVariantesMasivo()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $id_producto = (int) ($_POST['id_producto'] ?? 0);
        $filas       = [];

        foreach ((array) ($_POST['var'] ?? []) as $id_variante => $f) {
            $precio = trim((string) ($f['precio'] ?? ''));

            $filas[(int) $id_variante] = [
                'sku'    => mb_substr(trim((string) ($f['sku'] ?? '')), 0, 60),
                'precio' => $precio !== '' ? max(0, (float) $precio) : null,
                'stock'  => max(0, (int) ($f['stock'] ?? 0)),
                'activo' => !empty($f['activo']) ? 1 : 0,
            ];
        }

        if ($id_producto > 0 && $filas) {
            (new Producto())->actualizarVariantesMasivo($id_producto, $filas);
        }

        $this->redirect(BASE_URL . '/admin/productos/variantes?id=' . $id_producto . '&ok=guardadas');
    }

    public function eliminarVariantesMasivo()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $id_producto = (int) ($_POST['id_producto'] ?? 0);
        $ids         = array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])));

        [$e, $d] = $ids ? (new Producto())->eliminarVariantesMasivo($id_producto, $ids) : [0, 0];

        $this->redirect(BASE_URL . '/admin/productos/variantes?id=' . $id_producto . '&ok=eliminadas&e=' . $e . '&d=' . $d);
    }

        public function cambiarColorVariantes()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $id_producto = (int) ($_POST['id_producto'] ?? 0);
        $ids         = array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])));
        $id_color    = (int) ($_POST['id_color'] ?? 0);
        $volver      = BASE_URL . '/admin/productos/variantes?id=' . $id_producto;

        // 0 = sin color. Si viene un color, tiene que ser uno activo.
        if ($id_color > 0) {
            $activos = array_map('intval', array_column((new Color())->listarActivos()->fetch_all(MYSQLI_ASSOC), 'id_color'));
            if (!in_array($id_color, $activos, true)) {
                $this->redirect($volver);
            }
        }

        [$c, $o] = $ids
            ? (new Producto())->cambiarColorMasivo($id_producto, $ids, $id_color ?: null)
            : [0, 0];

        $this->redirect($volver . '&ok=color&c=' . $c . '&o=' . $o);
    }
}