<?php

class CarritoController extends Controller
{
    public function index()
    {
        registrar_visita('carrito');

        $categoriaModel = new Categoria();
        $categoriasMenu = $categoriaModel->listarMenu();

        $carritoModel = new Carrito();

        $items = [];
        $total = 0;

        if (!empty($_SESSION['carrito'])) {
            foreach ($_SESSION['carrito'] as $id_variante => $item) {
                $variante = $carritoModel->buscarVarianteDetalle((int)$id_variante);

                // Si la variante ya no existe, está inactiva o sin stock, se saca del carrito
                if (!$variante || $variante['activo'] != 1 || $variante['disponible'] <= 0) {
                    unset($_SESSION['carrito'][$id_variante]);
                    continue;
                }

                // Si mientras tanto se reservó stock, se ajusta la cantidad
                $cantidad = min((int)$item['cantidad'], (int)$variante['disponible']);
                $_SESSION['carrito'][$id_variante]['cantidad'] = $cantidad;

                // Precio real, con la promoción vigente aplicada
                $px       = precio_item($variante);
                $precio   = $px['precio'];
                $subtotal = $precio * $cantidad;

                $items[] = [
                    'variante' => $variante,
                    'precio'   => $precio,
                    'lista'    => $px['lista'],
                    'pct'      => $px['pct'],
                    'cantidad' => $cantidad,
                    'subtotal' => $subtotal
                ];

                $total += $subtotal;
            }
        }

        $this->view('carrito/index', [
            'items'          => $items,
            'total'          => $total,
            'categoriasMenu' => $categoriasMenu
        ], 'tienda');
    }

    public function eliminar()
    {
        $id_variante = (int) ($_GET['id'] ?? 0);

        if ($id_variante > 0 && isset($_SESSION['carrito'][$id_variante])) {
            unset($_SESSION['carrito'][$id_variante]);
        }

        $this->redirect(BASE_URL . '/carrito');
    }

    public function actualizar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/carrito');
        }

        $cantidades   = $_POST['cantidades'] ?? [];
        $carritoModel = new Carrito();

        foreach ($cantidades as $id_variante => $cantidad) {
            $id_variante = (int) $id_variante;
            $cantidad    = (int) $cantidad;

            // Solo se actualizan variantes que ya están en el carrito
            if (!isset($_SESSION['carrito'][$id_variante])) {
                continue;
            }

            if ($cantidad <= 0) {
                unset($_SESSION['carrito'][$id_variante]);
                continue;
            }

            $variante = $carritoModel->buscarVarianteDetalle($id_variante);

            if (!$variante || $variante['activo'] != 1 || $variante['disponible'] <= 0) {
                unset($_SESSION['carrito'][$id_variante]);
                continue;
            }

            $_SESSION['carrito'][$id_variante]['cantidad'] = min($cantidad, (int) $variante['disponible']);
        }

        $this->redirect(BASE_URL . '/carrito');
    }

        public function agregar()
    {
        // La ficha agrega "por detrás" y espera una respuesta corta; si no, se redirige como siempre
        $esAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

        $responder = function (bool $ok, string $mensaje, string $destino) use ($esAjax) {
            if ($esAjax) {
                $this->json(['ok' => $ok, 'mensaje' => $mensaje, 'cantidad_total' => self::totalItems()] + self::resumenCarrito());
            }
            $this->redirect($destino);
        };

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/tienda');
        }

        $id_variante = (int) ($_POST['id_variante'] ?? 0);
        $cantidad    = max(1, (int) ($_POST['cantidad'] ?? 1));

        if ($id_variante <= 0) {
            $responder(false, 'Elegí un talle.', BASE_URL . '/tienda');
        }

        $carritoModel = new Carrito();
        $variante     = $carritoModel->buscarVarianteDetalle($id_variante);

        if (!$variante || $variante['activo'] != 1 || $variante['disponible'] <= 0) {
            $responder(false, 'Ese talle se quedó sin stock.', BASE_URL . '/tienda');
        }

        $disponible = (int) $variante['disponible'];

        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        $actual = (int) ($_SESSION['carrito'][$id_variante]['cantidad'] ?? 0);

        $_SESSION['carrito'][$id_variante] = [
            'cantidad' => min($actual + $cantidad, $disponible)
        ];

        registrar_evento('agregar', [
            'id_producto' => (int) $variante['id_producto'],
            'id_variante' => $id_variante,
            'talle'       => $variante['talle'],
            'cantidad'    => $cantidad,
        ]);

        $mensaje = $actual + $cantidad > $disponible
            ? 'Agregamos las últimas ' . $disponible . ' unidades disponibles.'
            : 'Agregado al carrito.';

        $responder(true, $mensaje, BASE_URL . '/carrito');
    }

    /** Cantidad total de unidades en el carrito. */
    private static function totalItems(): int
    {
        $total = 0;
        foreach ($_SESSION['carrito'] ?? [] as $item) {
            $total += (int) ($item['cantidad'] ?? 0);
        }
        return $total;
    }

        /** El carrito completo, para mostrarlo en el panel de la ficha. */
    private static function resumenCarrito(): array
    {
        $modelo = new Carrito();
        $items  = [];
        $total  = 0;

        foreach ($_SESSION['carrito'] ?? [] as $idVariante => $item) {
            $v = $modelo->buscarVarianteDetalle((int) $idVariante);
            if (!$v) {
                continue;
            }

            $cantidad = (int) ($item['cantidad'] ?? 1);
            $lista    = isset($v['precio']) && $v['precio'] !== null && $v['precio'] !== '' ? (float) $v['precio'] : (float) ($v['precio_base'] ?? 0);
            $pct      = (float) ($v['descuento_pct'] ?? 0);
            $precio   = $pct > 0 ? precio_con_descuento($lista, $pct) : $lista;
            $foto     = $v['foto'] ?? null;

            $items[] = [
                'id_variante' => (int) $idVariante,
                'nombre'      => $v['producto'] ?? $v['nombre'] ?? '',
                'variante'    => variante_texto($v['talle'] ?? null, $v['color'] ?? null),
                'cantidad'    => $cantidad,
                'subtotal'    => round($precio * $cantidad, 2),
                'foto'        => $foto ? BASE_URL . '/public/uploads/productos/' . $foto : null,
            ];
            $total += round($precio * $cantidad, 2);
        }

        return ['items' => $items, 'total' => round($total, 2)];
    }
}