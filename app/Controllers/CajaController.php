<?php

class CajaController extends Controller
{
    // ─── PANTALLA PRINCIPAL ────────────────────────────────────────────────────

        public function index()
    {
        // ── Período ────────────────────────────────────────────────────────
        $periodo = $_GET['periodo'] ?? '';
        $hoy     = date('Y-m-d');

        switch ($periodo) {
            case 'hoy':
                $desde = $hasta = $hoy;
                break;
            case 'semana':
                $desde = date('Y-m-d', strtotime('-' . (date('N') - 1) . ' days'));   // lunes
                $hasta = $hoy;
                break;
            case 'mes_pasado':
                $desde = date('Y-m-01', strtotime('first day of last month'));
                $hasta = date('Y-m-t', strtotime('last day of last month'));
                break;
            case 'mes':
                $desde = date('Y-m-01');
                $hasta = $hoy;
                break;
            default:
                $desde   = $this->fechaValida($_GET['desde'] ?? '') ?: date('Y-m-01');
                $hasta   = $this->fechaValida($_GET['hasta'] ?? '') ?: $hoy;
                $periodo = (isset($_GET['desde']) || isset($_GET['hasta'])) ? 'personalizado' : 'mes';
        }
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $filtros = [
            'caja'      => (int) ($_GET['caja'] ?? 0),
            'tipo'      => $_GET['tipo'] ?? '',
            'categoria' => (int) ($_GET['categoria'] ?? 0),
            'anulados'  => !empty($_GET['anulados']),
            'q'         => mb_substr(trim($_GET['q'] ?? ''), 0, 60),
            'desde'     => $desde . ' 00:00:00',
            'hasta'     => date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00',   // rango semiabierto
        ];

        $porPagina = 50;
        $pagina    = max(1, (int) ($_GET['pagina'] ?? 1));

        $movModel = new Movimiento();
        $catModel = new CategoriaMovimiento();
        $total    = $movModel->contar($filtros);

        $this->view('admin/caja/index', [
            'cajas'        => (new Caja())->listarConSaldo(true),
            'movimientos'  => $movModel->listar($filtros, $pagina, $porPagina),
            'totales'      => $movModel->totalesDetalle($filtros),
            'porCategoria' => $movModel->gastosPorCategoria($filtros),
            'porMedio'     => $movModel->cobrosPorMedio($filtros),
            'total'        => $total,
            'pagina'       => $pagina,
            'totalPaginas' => (int) ceil($total / $porPagina),
            'filtros'      => $filtros,
            'periodo'      => $periodo,
            'desde'        => $desde,
            'hasta'        => $hasta,
            'catGastos'    => $catModel->listarActivas('gasto'),
            'catIngresos'  => $catModel->listarActivas('ingreso'),
        ]);
    }

    // ─── MOVIMIENTOS ───────────────────────────────────────────────────────────

    public function gasto()
    {
        $this->soloPost();
        [$id_caja, $monto, $fecha] = $this->datosBasicos();

        $id_cat = (int) ($_POST['id_categoria'] ?? 0);
        if ($id_cat <= 0) {
            $this->volver('error', 'Elegí una categoría.');
        }

        (new Movimiento())->registrarGasto($id_caja, $monto, $id_cat, $this->concepto(), $fecha);
        $this->volver('ok', 'Gasto registrado.');
    }

    public function ingreso()
    {
        $this->soloPost();
        [$id_caja, $monto, $fecha] = $this->datosBasicos();

        $id_cat = (int) ($_POST['id_categoria'] ?? 0);
        if ($id_cat <= 0) {
            $this->volver('error', 'Elegí una categoría.');
        }

        (new Movimiento())->registrarIngreso($id_caja, $monto, $id_cat, $this->concepto(), $fecha);
        $this->volver('ok', 'Ingreso registrado.');
    }

    public function transferir()
    {
        $this->soloPost();
        [$origen, $monto, $fecha] = $this->datosBasicos();

        $destino = (int) ($_POST['id_caja_destino'] ?? 0);

        if ($destino === $origen || !(new Caja())->buscarPorId($destino)) {
            $this->volver('error', 'Elegí una caja de destino distinta a la de origen.');
        }

        $ok = (new Movimiento())->transferir($origen, $destino, $monto, $this->concepto(), $fecha);
        $this->volver($ok ? 'ok' : 'error', $ok ? 'Transferencia registrada.' : 'No se pudo registrar la transferencia.');
    }

    public function ajustar()
    {
        $this->soloPost();

        $id_caja = (int) ($_POST['id_caja'] ?? 0);
        $real    = $_POST['saldo_real'] ?? '';
        $fecha   = $this->fechaMovimiento($_POST['fecha'] ?? '');

        if (!(new Caja())->buscarPorId($id_caja) || $real === '' || !is_numeric($real)) {
            $this->volver('error', 'Elegí la caja y escribí cuánto hay.');
        }

        $id = (new Movimiento())->ajustarSaldo($id_caja, round((float) $real, 2), $this->concepto(), $fecha);
        $this->volver('ok', $id ? 'Saldo ajustado.' : 'El saldo ya coincidía, no hizo falta ajustar.');
    }

    public function anular()
    {
        $this->soloPost();

        $ok = (new Movimiento())->anular((int) ($_POST['id'] ?? 0));
        $this->volver($ok ? 'ok' : 'error', $ok ? 'Movimiento anulado.' : 'No se pudo anular (puede que ya estuviera anulado).');
    }

    // ─── CONFIGURACIÓN ─────────────────────────────────────────────────────────

    public function config()
    {
        $this->view('admin/caja/config', [
            'cajas'        => (new Caja())->listarConSaldo(),
            'cajasActivas' => (new Caja())->listarActivas(),
            'medios'       => (new MedioPago())->listarTodos(),
            'categorias'   => (new CategoriaMovimiento())->listarTodas(),
        ]);
    }

    public function guardarCaja()
    {
        $this->soloPost();

        $id     = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            $this->volverConfig('error', 'El nombre de la caja es obligatorio.');
        }

        $datos = [
            'nombre' => mb_substr($nombre, 0, 60),
            'orden'  => (int) ($_POST['orden'] ?? 0) ?: 999,
            'activo' => isset($_POST['activo']) ? 1 : 0,
        ];

        $modelo = new Caja();
        $id > 0 ? $modelo->actualizar($id, $datos) : $modelo->crear($datos);

        $this->volverConfig('ok', 'Caja guardada.');
    }

    public function guardarMedio()
    {
        $this->soloPost();

        $id       = (int) ($_POST['id'] ?? 0);
        $nombre   = trim($_POST['nombre'] ?? '');
        $id_caja  = (int) ($_POST['id_caja'] ?? 0);
        $comision = (float) str_replace(',', '.', $_POST['comision_pct'] ?? '0');
        $ajuste   = (float) str_replace(',', '.', $_POST['ajuste_pct'] ?? '0');

        if ($nombre === '' || !(new Caja())->buscarPorId($id_caja)) {
            $this->volverConfig('error', 'Completá el nombre y la caja del medio de pago.');
        }

        if ($comision < 0 || $comision >= 100 || $ajuste <= -100 || $ajuste > 100) {
            $this->volverConfig('error', 'Revisá los porcentajes: comisión entre 0 y 99, ajuste entre -99 y 100.');
        }

        $datos = [
            'nombre'       => mb_substr($nombre, 0, 60),
            'id_caja'      => $id_caja,
            'comision_pct' => round($comision, 2),
            'ajuste_pct'   => round($ajuste, 2),
            'orden'        => (int) ($_POST['orden'] ?? 0) ?: 999,
            'activo'       => isset($_POST['activo']) ? 1 : 0,
        ];

        $modelo = new MedioPago();
        $id > 0 ? $modelo->actualizar($id, $datos) : $modelo->crear($datos);

        $this->volverConfig('ok', 'Medio de pago guardado.');
    }

    public function guardarCategoria()
    {
        $this->soloPost();

        $id     = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $tipo   = $_POST['tipo'] ?? '';

        if ($nombre === '' || !in_array($tipo, ['gasto', 'ingreso'], true)) {
            $this->volverConfig('error', 'Completá el nombre y el tipo de la categoría.');
        }

        $datos = [
            'nombre' => mb_substr($nombre, 0, 60),
            'tipo'   => $tipo,
            'activo' => isset($_POST['activo']) ? 1 : 0,
        ];

        $modelo = new CategoriaMovimiento();
        $id > 0 ? $modelo->actualizar($id, $datos) : $modelo->crear($datos);

        $this->volverConfig('ok', 'Categoría guardada.');
    }

        /** AJAX: nuevo orden de cajas o medios después de arrastrar. */
    public function ordenar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false], 405);
        }

        $tipo = $_POST['tipo'] ?? '';
        $ids  = array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])));

        if (!$ids || !in_array($tipo, ['cajas', 'medios'], true)) {
            $this->json(['ok' => false], 400);
        }

        $tipo === 'cajas' ? (new Caja())->ordenar($ids) : (new MedioPago())->ordenar($ids);

        $this->json(['ok' => true]);
    }

    // ─── HELPERS ───────────────────────────────────────────────────────────────

    private function soloPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/caja');
        }
    }

    /** Caja, monto y fecha de un formulario de movimiento (corta con error si algo falta). */
    private function datosBasicos(): array
    {
        $id_caja = (int) ($_POST['id_caja'] ?? 0);
        $monto   = round((float) ($_POST['monto'] ?? 0), 2);

        if (!(new Caja())->buscarPorId($id_caja)) {
            $this->volver('error', 'Elegí una caja.');
        }
        if ($monto <= 0) {
            $this->volver('error', 'El monto tiene que ser mayor a 0.');
        }

        return [$id_caja, $monto, $this->fechaMovimiento($_POST['fecha'] ?? '')];
    }

    private function concepto(): ?string
    {
        $c = trim($_POST['concepto'] ?? '');
        return $c !== '' ? mb_substr($c, 0, 150) : null;
    }

    /** 'Y-m-d' válida o '' */
    private function fechaValida(string $f): string
    {
        $d = DateTime::createFromFormat('Y-m-d', $f);
        return ($d && $d->format('Y-m-d') === $f) ? $f : '';
    }

    /** Fecha del movimiento: hoy → ahora; otro día → ese día a las 12:00. No se permiten fechas futuras. */
    private function fechaMovimiento(string $f): string
    {
        $f = $this->fechaValida($f);

        if ($f === '' || $f >= date('Y-m-d')) {
            return date('Y-m-d H:i:s');
        }

        return $f . ' 12:00:00';
    }

    /** Vuelve a la pantalla de Caja (con los filtros que tenía) mostrando un mensaje. */
    private function volver(string $tipo, string $texto): void
    {
        $_SESSION['caja_msg'] = ['tipo' => $tipo, 'texto' => $texto];
        $this->redirect($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/admin/caja');
    }

    /** Vuelve a la configuración de caja mostrando un mensaje. */
    private function volverConfig(string $tipo, string $texto): void
    {
        $_SESSION['caja_msg'] = ['tipo' => $tipo, 'texto' => $texto];
        $this->redirect(BASE_URL . '/admin/caja/config');
    }
}