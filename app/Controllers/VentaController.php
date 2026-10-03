<?php

class VentaController extends Controller
{
    /** Pantalla de venta manual. */
    public function nueva()
    {
        $this->view('admin/pedidos/venta', [
            'medios' => (new MedioPago())->listarActivos(),
        ]);
    }

    /** AJAX: variantes que coinciden con lo que escribe. */
    public function buscarProductos()
    {
        $q = trim($_GET['q'] ?? '');
        $this->json(['ok' => true, 'resultados' => mb_strlen($q) >= 2 ? (new GestionPedido())->buscarVariantes($q) : []]);
    }

    /** AJAX: clientes que coinciden con lo que escribe. */
    public function buscarClientes()
    {
        $q = trim($_GET['q'] ?? '');
        $this->json(['ok' => true, 'resultados' => mb_strlen($q) >= 2 ? (new GestionPedido())->buscarClientes($q) : []]);
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/venta/nueva');
        }

        $num = fn($v) => (float) str_replace(',', '.', (string) ($v ?? '0'));

        // Fecha: hoy → ahora; otro día → ese día a las 12:00; sin futuras
        $f     = $_POST['fecha'] ?? '';
        $dt    = DateTime::createFromFormat('Y-m-d', $f);
        $fecha = ($dt && $dt->format('Y-m-d') === $f && $f < date('Y-m-d')) ? $f . ' 12:00:00' : date('Y-m-d H:i:s');

        // Cliente
        $tipoCliente = $_POST['tipo_cliente'] ?? 'ninguno';
        $cliente = null;
        if ($tipoCliente === 'existente' && (int) ($_POST['id_cliente'] ?? 0) > 0) {
            $cliente = ['id' => (int) $_POST['id_cliente']];
        } elseif ($tipoCliente === 'nuevo') {
            $cliente = ['nuevo' => (array) ($_POST['cliente'] ?? [])];
        }

        // Productos
        $items = [];
        foreach ((array) ($_POST['items'] ?? []) as $it) {
            if ((int) ($it['id_variante'] ?? 0) > 0) {
                $items[] = [
                    'id_variante' => (int) $it['id_variante'],
                    'cantidad'    => (int) ($it['cantidad'] ?? 1),
                    'precio'      => $num($it['precio'] ?? 0),
                ];
            }
        }

        $res = (new GestionPedido())->crearVentaManual([
            'origen'            => $_POST['origen'] ?? 'otro',
            'fecha'             => $fecha,
            'cliente'           => $cliente,
            'items'             => $items,
            'descuento'         => $num($_POST['descuento'] ?? 0),
            'motivo_descuento'  => trim($_POST['motivo_descuento'] ?? ''),
            'id_medio_acordado' => (int) ($_POST['id_medio_acordado'] ?? 0) ?: null,
            'entrega'           => $_POST['entrega'] ?? '',
            'envio_cobrado'     => $num($_POST['envio_cobrado'] ?? 0),
            'envio_costo'       => $num($_POST['envio_costo'] ?? 0),
            'notas'             => trim($_POST['notas'] ?? ''),
            'entregado'         => ($_POST['entregado'] ?? '1') === '1',
            'cobro'             => !empty($_POST['pago'])
                ? ['monto' => $num($_POST['cobro_monto'] ?? 0), 'id_medio' => (int) ($_POST['cobro_medio'] ?? 0)]
                : null,
        ]);

        if (!$res['ok']) {
            $_SESSION['venta_error'] = $res['mensaje'];
            $_SESSION['venta_post']  = $_POST;   // para no perder lo cargado
            $this->redirect(BASE_URL . '/admin/venta/nueva');
        }

        $_SESSION['pedido_msg'] = ['ok', 'Venta cargada.'];
        $this->redirect(BASE_URL . '/admin/pedido/' . $res['id']);
    }
}