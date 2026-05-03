<?php
session_start();
define('BASE_URL', '');

require_once __DIR__ . '/../app/Core/Controller.php';
require_once __DIR__ . '/../app/Core/helpers.php';

require_once __DIR__ . '/../app/Models/Producto.php';
require_once __DIR__ . '/../app/Models/Categoria.php';
require_once __DIR__ . '/../app/Models/Marca.php';

require_once __DIR__ . '/../app/Controllers/DashboardController.php';
require_once __DIR__ . '/../app/Controllers/ProductoController.php';
require_once __DIR__ . '/../app/Controllers/CategoriaController.php';
require_once __DIR__ . '/../app/Controllers/TiendaController.php';

require_once __DIR__ . '/../app/Models/Talle.php';
require_once __DIR__ . '/../app/Models/Color.php';
require_once __DIR__ . '/../app/Models/Carrito.php';
require_once __DIR__ . '/../app/Controllers/CarritoController.php';

require_once __DIR__ . '/../app/Controllers/CheckoutController.php';
require_once __DIR__ . '/../app/Models/Pedido.php';

require_once __DIR__ . '/../app/Models/UsuarioCliente.php';
require_once __DIR__ . '/../app/Controllers/ClienteAuthController.php';


require_once __DIR__ . '/../app/Controllers/PedidoController.php';

require_once __DIR__ . '/../app/Controllers/ClienteController.php';

require_once __DIR__ . '/../app/Models/Cliente.php';
require_once __DIR__ . '/../app/Controllers/ClienteAdminController.php';

$route = $_GET['route'] ?? 'dashboard';

switch ($route) {

    case 'dashboard':
        $controller = new DashboardController();
        $controller->index();
        break;

    case 'productos':
        $controller = new ProductoController();
        $controller->index();
        break;

    case 'productos_crear':
        $controller = new ProductoController();
        $controller->crear();
        break;

    case 'productos_guardar':
        $controller = new ProductoController();
        $controller->guardar();
        break;

    case 'categorias':
        $controller = new CategoriaController();
        $controller->index();
        break;

    case 'productos_variantes':
        $controller = new ProductoController();
        $controller->variantes();
        break;

    case 'productos_guardar_variante':
        $controller = new ProductoController();
        $controller->guardarVariante();
        break;

    case 'productos_fotos':
        $controller = new ProductoController();
        $controller->fotos();
        break;

    case 'productos_subir_foto':
        $controller = new ProductoController();
        $controller->subirFoto();
        break;

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

    case 'admin_pedidos':
        $controller = new PedidoController();
        $controller->index();
        break;

    case 'admin_pedido_detalle':
        $controller = new PedidoController();
        $controller->detalle();
        break;

    case 'admin_pedido_estado':
        $controller = new PedidoController();
        $controller->actualizarEstado();
    break;

    case 'cliente_pedidos':
    $controller = new ClienteController();
    $controller->pedidos();
    break;

case 'cliente_pedido_detalle':
    $controller = new ClienteController();
    $controller->pedidoDetalle();
    break;

    case 'admin_clientes':
    case 'admin/clientes':
        $controller = new ClienteAdminController();
        $controller->index();
        break;

    case 'admin_cliente_detalle':
    case 'admin/cliente':
        $controller = new ClienteAdminController();
        $controller->detalle();
        break;
    default:
        echo 'Ruta no encontrada';
        break;
}