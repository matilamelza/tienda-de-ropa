<?php
session_start();
define('BASE_URL', '');

require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Core/helpers.php';
require_once __DIR__ . '/../app/Core/auth.php';         

// Models
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

// Controllers
require_once __DIR__ . '/../app/Controllers/DashboardController.php';
require_once __DIR__ . '/../app/Controllers/ProductoController.php';
require_once __DIR__ . '/../app/Controllers/CategoriaController.php';
require_once __DIR__ . '/../app/Controllers/MarcaController.php';
require_once __DIR__ . '/../app/Controllers/TiendaController.php';
require_once __DIR__ . '/../app/Controllers/CarritoController.php';
require_once __DIR__ . '/../app/Controllers/CheckoutController.php';
require_once __DIR__ . '/../app/Controllers/ClienteAuthController.php';
require_once __DIR__ . '/../app/Controllers/PedidoController.php';
require_once __DIR__ . '/../app/Controllers/ClienteController.php';
require_once __DIR__ . '/../app/Controllers/ClienteAdminController.php';
require_once __DIR__ . '/../app/Controllers/AdminAuthController.php'; 

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

    // ─── ADMIN (protegidas) ────────────────────────────────────────────────────

    case 'dashboard':
        requireAdmin();
        $controller = new DashboardController();
        $controller->index();
        break;

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

    // ─── MARCAS ───────────────────────────────────────────────────────────────

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

    // ─── TIENDA ────────────────────────────────────────────────────────────────

    case 'tienda':
        $controller = new TiendaController();
        $controller->index();
        break;

    case 'producto':
        $controller = new TiendaController();
        $controller->detalle();
        break;

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

    case 'cliente_pedidos':
        $controller = new ClienteController();
        $controller->pedidos();
        break;

    case 'cliente_pedido_detalle':
        $controller = new ClienteController();
        $controller->pedidoDetalle();
        break;

    default:
        http_response_code(404);
        echo 'Ruta no encontrada';
        break;
}