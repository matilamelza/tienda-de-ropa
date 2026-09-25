<?php

// ── Buffer de salida: permite reemplazar una página a medias por la de error ──
ob_start();

// ── Manejo de errores (lo antes posible) ─────────────────────────────────────
require_once __DIR__ . '/../app/Core/errores.php';

// ── Entorno ──────────────────────────────────────────────────────────────────
$esLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true)
        || str_starts_with($_SERVER['HTTP_HOST'] ?? '', 'localhost:');

// Los errores nunca se imprimen crudos: los maneja errores.php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ── Sesión con cookie segura ─────────────────────────────────────────────────
$esHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $esHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// ── ¿Mostrar el detalle del error? En local siempre; en producción solo admin + ?debug=1
define('MOSTRAR_ERRORES', $esLocal || ( ($_GET['debug'] ?? '') === '1'));

define('BASE_URL', '');

// ── Core ─────────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Core/helpers.php';
require_once __DIR__ . '/../app/Core/auth.php';
require_once __DIR__ . '/../app/Core/csrf.php';

// ── CSRF: todo POST tiene que traer el token de la sesión ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valido()) {
    http_response_code(419);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <title>Sesión expirada</title>
          <script src="https://cdn.tailwindcss.com"></script></head>
          <body class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
          <div class="text-center p-10 bg-white rounded-3xl shadow-lg max-w-md">
            <p class="text-5xl mb-4">⏳</p>
            <h1 class="text-2xl font-bold mb-2">La página expiró</h1>
            <p class="text-gray-500 mb-6">Volvé atrás, recargá la página y probá de nuevo.</p>
            <a href="javascript:history.back()" class="inline-block bg-gray-900 text-white px-6 py-3 rounded-full">Volver</a>
          </div></body></html>';
    exit;
}

// ── Models ───────────────────────────────────────────────────────────────────
require_once __DIR__ . '/../app/Models/Producto.php';
require_once __DIR__ . '/../app/Models/Categoria.php';
require_once __DIR__ . '/../app/Models/Marca.php';
require_once __DIR__ . '/../app/Models/Talle.php';
require_once __DIR__ . '/../app/Models/Color.php';
require_once __DIR__ . '/../app/Models/Carrito.php';
require_once __DIR__ . '/../app/Models/Pedido.php';
require_once __DIR__ . '/../app/Models/UsuarioCliente.php';
require_once __DIR__ . '/../app/Models/Cliente.php';
require_once __DIR__ . '/../app/Models/UsuarioAdmin.php';
require_once __DIR__ . '/../app/Models/ConfiguracionTienda.php';

// ── Controllers ──────────────────────────────────────────────────────────────
require_once __DIR__ . '/../app/Controllers/DashboardController.php';
require_once __DIR__ . '/../app/Controllers/ProductoController.php';
require_once __DIR__ . '/../app/Controllers/CategoriaController.php';
require_once __DIR__ . '/../app/Controllers/MarcaController.php';
require_once __DIR__ . '/../app/Controllers/TalleController.php';
require_once __DIR__ . '/../app/Controllers/ColorController.php';
require_once __DIR__ . '/../app/Controllers/TiendaController.php';
require_once __DIR__ . '/../app/Controllers/CarritoController.php';
require_once __DIR__ . '/../app/Controllers/CheckoutController.php';
require_once __DIR__ . '/../app/Controllers/ClienteAuthController.php';
require_once __DIR__ . '/../app/Controllers/PedidoController.php';
require_once __DIR__ . '/../app/Controllers/ClienteController.php';
require_once __DIR__ . '/../app/Controllers/ClienteAdminController.php';
require_once __DIR__ . '/../app/Controllers/AdminAuthController.php';
require_once __DIR__ . '/../app/Controllers/ConfiguracionController.php';

// ── Modo mantenimiento ───────────────────────────────────────────────────────
$cfgTienda = new ConfiguracionTienda();
if ($cfgTienda->get('mantenimiento_activo') === '1' && !isset($_SESSION['admin'])) {
    $msg = $cfgTienda->get('mantenimiento_mensaje', 'Estamos en mantenimiento. Volvemos pronto.');
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <title>Mantenimiento</title>
          <script src="https://cdn.tailwindcss.com"></script></head>
          <body class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
          <div class="text-center p-10 bg-white rounded-3xl shadow-lg max-w-md">
            <p class="text-6xl mb-4">🚧</p>
            <h1 class="text-2xl font-bold mb-2">Sitio en mantenimiento</h1>
            <p class="text-gray-500">' . htmlspecialchars($msg) . '</p>
          </div></body></html>';
    exit;
}

// ── Router ───────────────────────────────────────────────────────────────────
$route = $_GET['route'] ?? 'tienda';

switch ($route) {

    // ─── AUTH ADMIN ────────────────────────────────────────────────────────────

    case 'admin_login':
        $controller = new AdminAuthController();
        $controller->login();
        break;

    case 'admin_ingresar':
        $controller = new AdminAuthController();
        $controller->ingresar();
        break;

    case 'admin_logout':
        $controller = new AdminAuthController();
        $controller->logout();
        break;

    // ─── DASHBOARD ─────────────────────────────────────────────────────────────

    case 'dashboard':
        requireAdmin();
        $controller = new DashboardController();
        $controller->index();
        break;

    // ─── PRODUCTOS ─────────────────────────────────────────────────────────────

    case 'productos':
        requireAdmin();
        $controller = new ProductoController();
        $controller->index();
        break;

    case 'productos_crear':
        requireAdmin();
        $controller = new ProductoController();
        $controller->crear();
        break;

    case 'productos_guardar':
        requireAdmin();
        $controller = new ProductoController();
        $controller->guardar();
        break;

    case 'productos_editar':
        requireAdmin();
        $controller = new ProductoController();
        $controller->editar();
        break;

    case 'productos_actualizar':
        requireAdmin();
        $controller = new ProductoController();
        $controller->actualizar();
        break;

    case 'productos_eliminar':
        requireAdmin();
        $controller = new ProductoController();
        $controller->eliminar();
        break;

    case 'productos_variantes':
        requireAdmin();
        $controller = new ProductoController();
        $controller->variantes();
        break;

    case 'productos_guardar_variante':
        requireAdmin();
        $controller = new ProductoController();
        $controller->guardarVariante();
        break;

    case 'productos_editar_variante':
        requireAdmin();
        $controller = new ProductoController();
        $controller->editarVariante();
        break;

    case 'productos_actualizar_variante':
        requireAdmin();
        $controller = new ProductoController();
        $controller->actualizarVariante();
        break;

    case 'productos_eliminar_variante':
        requireAdmin();
        $controller = new ProductoController();
        $controller->eliminarVariante();
        break;

    case 'productos_fotos':
        requireAdmin();
        $controller = new ProductoController();
        $controller->fotos();
        break;

    case 'productos_subir_foto':
        requireAdmin();
        $controller = new ProductoController();
        $controller->subirFoto();
        break;

    case 'productos_eliminar_foto':
        requireAdmin();
        $controller = new ProductoController();
        $controller->eliminarFoto();
        break;
    
    case 'productos_ordenar_fotos':
        requireAdmin();
        $controller = new ProductoController();
        $controller->ordenarFotos();
        break;

    // ─── CATEGORÍAS ────────────────────────────────────────────────────────────

    case 'categorias':
        requireAdmin();
        $controller = new CategoriaController();
        $controller->index();
        break;

    case 'categorias_crear':
        requireAdmin();
        $controller = new CategoriaController();
        $controller->crear();
        break;

    case 'categorias_guardar':
        requireAdmin();
        $controller = new CategoriaController();
        $controller->guardar();
        break;

    case 'categorias_editar':
        requireAdmin();
        $controller = new CategoriaController();
        $controller->editar();
        break;

    case 'categorias_actualizar':
        requireAdmin();
        $controller = new CategoriaController();
        $controller->actualizar();
        break;

    case 'categorias_eliminar':
        requireAdmin();
        $controller = new CategoriaController();
        $controller->eliminar();
        break;

    // ─── MARCAS ────────────────────────────────────────────────────────────────

    case 'marcas':
        requireAdmin();
        $controller = new MarcaController();
        $controller->index();
        break;

    case 'marcas_crear':
        requireAdmin();
        $controller = new MarcaController();
        $controller->crear();
        break;

    case 'marcas_guardar':
        requireAdmin();
        $controller = new MarcaController();
        $controller->guardar();
        break;

    case 'marcas_editar':
        requireAdmin();
        $controller = new MarcaController();
        $controller->editar();
        break;

    case 'marcas_actualizar':
        requireAdmin();
        $controller = new MarcaController();
        $controller->actualizar();
        break;

    case 'marcas_eliminar':
        requireAdmin();
        $controller = new MarcaController();
        $controller->eliminar();
        break;

    // ─── TALLES ────────────────────────────────────────────────────────────────

    case 'talles':
        requireAdmin();
        $controller = new TalleController();
        $controller->index();
        break;

    case 'talles_crear':
        requireAdmin();
        $controller = new TalleController();
        $controller->crear();
        break;

    case 'talles_guardar':
        requireAdmin();
        $controller = new TalleController();
        $controller->guardar();
        break;

    case 'talles_editar':
        requireAdmin();
        $controller = new TalleController();
        $controller->editar();
        break;

    case 'talles_actualizar':
        requireAdmin();
        $controller = new TalleController();
        $controller->actualizar();
        break;

    case 'talles_eliminar':
        requireAdmin();
        $controller = new TalleController();
        $controller->eliminar();
        break;

    case 'talles_crear_ajax':
        requireAdmin();
        $controller = new TalleController();
        $controller->crearAjax();
        break;

    // ─── COLORES ───────────────────────────────────────────────────────────────

    case 'colores':
        requireAdmin();
        $controller = new ColorController();
        $controller->index();
        break;

    case 'colores_crear':
        requireAdmin();
        $controller = new ColorController();
        $controller->crear();
        break;

    case 'colores_guardar':
        requireAdmin();
        $controller = new ColorController();
        $controller->guardar();
        break;

    case 'colores_editar':
        requireAdmin();
        $controller = new ColorController();
        $controller->editar();
        break;

    case 'colores_actualizar':
        requireAdmin();
        $controller = new ColorController();
        $controller->actualizar();
        break;

    case 'colores_eliminar':
        requireAdmin();
        $controller = new ColorController();
        $controller->eliminar();
        break;

    case 'colores_crear_ajax':
        requireAdmin();
        $controller = new ColorController();
        $controller->crearAjax();
        break;

    // ─── CONFIGURACIÓN ─────────────────────────────────────────────────────────

    case 'admin_configuracion':
        requireAdmin();
        $controller = new ConfiguracionController();
        $controller->index();
        break;

    case 'admin_configuracion_guardar':
        requireAdmin();
        $controller = new ConfiguracionController();
        $controller->guardar();
        break;

    case 'admin_configuracion_eliminar_imagen':
        requireAdmin();
        $controller = new ConfiguracionController();
        $controller->eliminarImagen();
        break;

    // ─── PEDIDOS ───────────────────────────────────────────────────────────────

    case 'admin_pedidos':
        requireAdmin();
        $controller = new PedidoController();
        $controller->index();
        break;

    case 'admin_pedido_detalle':
        requireAdmin();
        $controller = new PedidoController();
        $controller->detalle();
        break;

    case 'admin_pedido_estado':
        requireAdmin();
        $controller = new PedidoController();
        $controller->actualizarEstado();
        break;

    // ─── CLIENTES (ADMIN) ──────────────────────────────────────────────────────

    case 'admin_clientes':
    case 'admin/clientes':
        requireAdmin();
        $controller = new ClienteAdminController();
        $controller->index();
        break;

    case 'admin_cliente_detalle':
    case 'admin/cliente':
        requireAdmin();
        $controller = new ClienteAdminController();
        $controller->detalle();
        break;

    case 'admin_resets':
        requireAdmin();
        $controller = new ClienteAdminController();
        $controller->resets();
        break;

    case 'admin_resets_limpiar':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            unset($_SESSION['admin_resets_pendientes']);
        }
        header('Location: ' . BASE_URL . '/admin/resets');
        exit;

    // ─── TIENDA ────────────────────────────────────────────────────────────────

    case 'tienda':
        $controller = new TiendaController();
        $controller->index();
        break;

    case 'producto':
        $controller = new TiendaController();
        $controller->detalle();
        break;

    // ─── CARRITO Y CHECKOUT ────────────────────────────────────────────────────

    case 'carrito':
        $controller = new CarritoController();
        $controller->index();
        break;

    case 'carrito_agregar':
        $controller = new CarritoController();
        $controller->agregar();
        break;

    case 'carrito_eliminar':
        $controller = new CarritoController();
        $controller->eliminar();
        break;

    case 'carrito_actualizar':
        $controller = new CarritoController();
        $controller->actualizar();
        break;

    case 'checkout':
        $controller = new CheckoutController();
        $controller->index();
        break;

    case 'checkout_guardar':
        $controller = new CheckoutController();
        $controller->guardar();
        break;

    case 'pedido_gracias':
        $controller = new CheckoutController();
        $controller->gracias();
        break;

    // ─── CLIENTE: AUTH ─────────────────────────────────────────────────────────

    case 'cliente_login':
        $controller = new ClienteAuthController();
        $controller->login();
        break;

    case 'cliente_ingresar':
        $controller = new ClienteAuthController();
        $controller->ingresar();
        break;

    case 'cliente_registro':
        $controller = new ClienteAuthController();
        $controller->registro();
        break;

    case 'cliente_guardar_registro':
        $controller = new ClienteAuthController();
        $controller->guardarRegistro();
        break;

    case 'cliente_logout':
        $controller = new ClienteAuthController();
        $controller->logout();
        break;

    case 'olvide_password':
        $controller = new ClienteAuthController();
        $controller->olvideMiPassword();
        break;

    case 'solicitar_reset':
        $controller = new ClienteAuthController();
        $controller->solicitarReset();
        break;

    case 'nueva_password':
        $controller = new ClienteAuthController();
        $controller->formularioNuevaPassword();
        break;

    case 'guardar_nueva_password':
        $controller = new ClienteAuthController();
        $controller->guardarNuevaPassword();
        break;

    // ─── CLIENTE: CUENTA ───────────────────────────────────────────────────────

    case 'cliente_pedidos':
        $controller = new ClienteController();
        $controller->pedidos();
        break;

    case 'cliente_pedido_detalle':
        $controller = new ClienteController();
        $controller->pedidoDetalle();
        break;

    // ─── 404 ───────────────────────────────────────────────────────────────────

    default:
        $controller = new TiendaController();
        $controller->noEncontrado();
        break;
}