<?php

class PedidoController extends Controller
{
    // ═══════════════════════════════════════════════════════════════════════
    // LISTADO
    // ═══════════════════════════════════════════════════════════════════════

        public function index()
    {
        $porPagina = 20;
        $pagina    = max(1, (int) ($_GET['pagina'] ?? 1));

        $estado = $_GET['estado'] ?? '';
        if (!in_array($estado, ['vencidos', 'por_entregar'], true) && !isset(GestionPedido::ESTADOS[$estado])) {
            $estado = '';
        }

        $fecha = function ($v) {
            $d = DateTime::createFromFormat('Y-m-d', (string) $v);
            return $d && $d->format('Y-m-d') === $v ? $v : '';
        };

        $filtros = [
            'q'       => mb_substr(trim($_GET['q'] ?? ''), 0, 60),
            'estado'  => $estado,
            'pago'    => in_array($_GET['pago'] ?? '', ['sin_pagar', 'senado', 'pagado', 'debe'], true) ? $_GET['pago'] : '',
            'entrega' => in_array($_GET['entrega'] ?? '', ['retiro', 'envio', 'sin'], true) ? $_GET['entrega'] : '',
            'desde'   => $fecha($_GET['desde'] ?? ''),
            'hasta'   => $fecha($_GET['hasta'] ?? ''),
            'orden'   => isset(Pedido::ORDENES_LISTADO[$_GET['orden'] ?? '']) ? $_GET['orden'] : '',
        ];

        $pedidoModel = new Pedido();
        $total       = $pedidoModel->contarPedidos($filtros);

        $this->view('admin/pedidos/index', [
            'pedidos'      => $pedidoModel->listarPaginado($pagina, $porPagina, $filtros),
            'pagina'       => $pagina,
            'totalPaginas' => (int) ceil($total / $porPagina),
            'total'        => $total,
            'filtros'      => $filtros,
            'conteo'       => $pedidoModel->contarPorEstado(),
            'resumen'      => $pedidoModel->resumenListado(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // DETALLE
    // ═══════════════════════════════════════════════════════════════════════

    public function detalle()
    {
        $id = (int) ($_GET['id'] ?? 0);

        $r = (new GestionPedido())->resumen($id);

        if (!$r) {
            $this->redirect(BASE_URL . '/admin/pedidos');
        }

        $this->view('admin/pedidos/detalle', [
            'r'      => $r,
            'medios' => (new MedioPago())->listarActivos(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCIONES (todas POST, todas vuelven al detalle con un mensaje)
    // ═══════════════════════════════════════════════════════════════════════

    public function actualizarEstado()
    {
        $id    = $this->idPost();
        $error = (new GestionPedido())->cambiarEstado($id, $_POST['estado'] ?? '');

        $this->volver($id, $error ? 'error' : 'ok', $error ?: 'Estado actualizado.');
    }

    public function cobro()
    {
        $id = $this->idPost();

        $res = (new GestionPedido())->registrarCobro(
            $id,
            (int) ($_POST['id_medio'] ?? 0),
            (float) str_replace(',', '.', $_POST['monto'] ?? '0'),
            $this->fechaMovimiento($_POST['fecha'] ?? ''),
            trim($_POST['comprobante'] ?? '') !== '' ? mb_substr(trim($_POST['comprobante']), 0, 100) : null
        );

        $this->volver($id, $res['ok'] ? 'ok' : 'error', $res['mensaje']);
    }

    public function anularCobro()
    {
        $id = $this->idPost();
        $ok = (new GestionPedido())->anularCobro($id, (int) ($_POST['id_movimiento'] ?? 0));

        $this->volver($id, $ok ? 'ok' : 'error', $ok ? 'Movimiento anulado.' : 'No se pudo anular.');
    }

    public function usarSaldo()
    {
        $id  = $this->idPost();
        $res = (new GestionPedido())->usarSaldo($id, (float) str_replace(',', '.', $_POST['monto'] ?? '0'));

        $this->volver($id, $res['ok'] ? 'ok' : 'error', $res['mensaje']);
    }

    public function condiciones()
    {
        $id = $this->idPost();

        $error = (new GestionPedido())->actualizarCondiciones($id, [
            'descuento'         => (float) str_replace(',', '.', $_POST['descuento'] ?? '0'),
            'motivo_descuento'  => trim($_POST['motivo_descuento'] ?? ''),
            'id_medio_acordado' => (int) ($_POST['id_medio_acordado'] ?? 0) ?: null,
            'envio_cobrado'     => (float) str_replace(',', '.', $_POST['envio_cobrado'] ?? '0'),
            'envio_costo'       => (float) str_replace(',', '.', $_POST['envio_costo'] ?? '0'),
            'entrega'           => $_POST['entrega'] ?? '',
            'seguimiento'       => trim($_POST['seguimiento'] ?? ''),
        ]);

        $this->volver($id, $error ? 'error' : 'ok', $error ?: 'Condiciones guardadas.');
    }

    public function nota()
    {
        $id = $this->idPost();
        (new GestionPedido())->guardarNota($id, $_POST['notas_internas'] ?? '');

        $this->volver($id, 'ok', 'Notas guardadas.');
    }

    public function cancelar()
    {
        $id = $this->idPost();

        $error = (new GestionPedido())->cancelar(
            $id,
            $_POST['destino'] ?? 'nada',
            (int) ($_POST['id_medio_devolucion'] ?? 0) ?: null,
            trim($_POST['motivo'] ?? '') !== '' ? mb_substr(trim($_POST['motivo']), 0, 150) : null
        );

        $this->volver($id, $error ? 'error' : 'ok', $error ?: 'Pedido cancelado.');
    }

    /** Lo llama el botón de WhatsApp (de fondo). No redirige. */
    public function contactado()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new GestionPedido())->marcarContactado((int) ($_POST['id_pedido'] ?? 0));
        }
        http_response_code(204);
        exit;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════════════

    private function idPost(): int
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/pedidos');
        }
        return (int) ($_POST['id_pedido'] ?? 0);
    }

    private function volver(int $id, string $tipo, string $texto): void
    {
        $_SESSION['pedido_msg'] = [$tipo, $texto];
        $this->redirect(BASE_URL . '/admin/pedido/' . $id);
    }

    /** Hoy → ahora; otro día → ese día a las 12:00. Sin fechas futuras. */
    private function fechaMovimiento(string $f): string
    {
        $d = DateTime::createFromFormat('Y-m-d', $f);

        if (!$d || $d->format('Y-m-d') !== $f || $f >= date('Y-m-d')) {
            return date('Y-m-d H:i:s');
        }
        return $f . ' 12:00:00';
    }
}