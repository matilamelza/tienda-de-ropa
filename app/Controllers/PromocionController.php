<?php

class PromocionController extends Controller
{
    public function index()
    {
        $this->view('admin/promociones/index', [
            'promociones' => (new Promocion())->listar(),
        ]);
    }

    public function nueva()
    {
        $this->view('admin/promociones/form', ['promo' => null]);
    }

    public function editar()
    {
        $promo = (new Promocion())->buscarPorId((int) ($_GET['id'] ?? 0));

        if (!$promo) {
            $this->redirect(BASE_URL . '/admin/promociones');
        }

        $this->view('admin/promociones/form', ['promo' => $promo]);
    }

    public function guardar()
    {
        $this->soloPost();

        $id     = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $pct    = (float) str_replace(',', '.', $_POST['pct'] ?? '0');
        $desde  = $this->fechaDesdeInput($_POST['desde'] ?? '');
        $hasta  = $this->fechaDesdeInput($_POST['hasta'] ?? '');
        $volver = BASE_URL . '/admin/promociones/' . ($id ? 'editar?id=' . $id : 'nueva');

        if ($nombre === '') {
            $this->flash('error', 'Poné un nombre para la promoción.', $volver);
        }
        if ($pct <= 0 || $pct >= 100) {
            $this->flash('error', 'El descuento tiene que estar entre 1% y 99%.', $volver);
        }
        if ($desde && $hasta && $hasta <= $desde) {
            $this->flash('error', 'La fecha de fin tiene que ser posterior a la de inicio.', $volver);
        }

        $datos = [
            'nombre'   => mb_substr($nombre, 0, 80),
            'etiqueta' => mb_substr(trim($_POST['etiqueta'] ?? ''), 0, 30) ?: null,
            'pct'      => round($pct, 2),
            'desde'    => $desde,
            'hasta'    => $hasta,
            'activa'   => isset($_POST['activa']) ? 1 : 0,
        ];

        $modelo = new Promocion();

        if ($id > 0) {
            $modelo->actualizar($id, $datos);
            $this->flash('ok', 'Promoción guardada.', BASE_URL . '/admin/promociones/ver?id=' . $id);
        }

        $id = $modelo->crear($datos);
        $this->flash('ok', 'Promoción creada. Ahora sumale productos.', BASE_URL . '/admin/promociones/ver?id=' . $id);
    }

    public function ver()
    {
        $modelo = new Promocion();
        $promo  = $modelo->buscarPorId((int) ($_GET['id'] ?? 0));

        if (!$promo) {
            $this->redirect(BASE_URL . '/admin/promociones');
        }

        $this->view('admin/promociones/ver', [
            'promo'     => $promo,
            'productos' => $modelo->productos((int) $promo['id_promocion']),
            'reporte'   => $modelo->reporte((int) $promo['id_promocion']),
            'top'       => $modelo->topProductos((int) $promo['id_promocion']),
        ]);
    }

    public function toggle()
    {
        $this->soloPost();

        $valor = (new Promocion())->toggleActiva((int) ($_POST['id'] ?? 0));

        $this->flash('ok', $valor ? 'Promoción reanudada.' : 'Promoción pausada.',
                     $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/admin/promociones');
    }

    public function eliminar()
    {
        $this->soloPost();

        (new Promocion())->eliminar((int) ($_POST['id'] ?? 0));

        $this->flash('ok', 'Promoción eliminada. Los productos quedaron con su precio normal.',
                     BASE_URL . '/admin/promociones');
    }

    /** Guardar % propios o quitar productos de una promoción. */
    public function productos()
    {
        $this->soloPost();

        $id     = (int) ($_POST['id_promocion'] ?? 0);
        $modelo = new Promocion();
        $volver = BASE_URL . '/admin/promociones/ver?id=' . $id;

        if (!$modelo->buscarPorId($id)) {
            $this->redirect(BASE_URL . '/admin/promociones');
        }

        if (($_POST['accion'] ?? '') === 'quitar') {
            $ids = array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])));
            $n   = $modelo->quitarProductos($id, $ids);
            $this->flash('ok', "Se quitaron $n producto(s) de la promoción.", $volver);
        }

        // Guardar % propios (vacío = usa el de la promoción)
        foreach ((array) ($_POST['pct'] ?? []) as $idProducto => $valor) {
            $valor = trim((string) $valor);
            $pct   = $valor === '' ? null : (float) str_replace(',', '.', $valor);

            if ($pct !== null && ($pct <= 0 || $pct >= 100)) {
                continue;   // se ignora un % inválido
            }

            $modelo->actualizarPctProducto($id, (int) $idProducto, $pct);
        }

        $this->flash('ok', 'Descuentos guardados.', $volver);
    }

        /** Oferta rápida desde un producto: crea una promoción con ese solo producto. */
    public function ofertaRapida()
    {
        $this->soloPost();

        $idProducto = (int) ($_POST['id_producto'] ?? 0);
        $producto   = (new Producto())->buscarPorId($idProducto);

        if (!$producto) {
            $this->redirect(BASE_URL . '/admin/productos');
        }

        $volver = BASE_URL . '/admin/productos/editar?id=' . $idProducto;
        $pct    = (float) str_replace(',', '.', $_POST['pct'] ?? '0');
        $hasta  = $this->fechaDesdeInput($_POST['hasta'] ?? '');

        if ($pct <= 0 || $pct >= 100) {
            $this->redirect($volver . '&error=oferta');
        }

        $modelo = new Promocion();
        $id     = $modelo->crear([
            'nombre'   => mb_substr('Oferta: ' . $producto['nombre'], 0, 80),
            'etiqueta' => null,
            'pct'      => round($pct, 2),
            'desde'    => null,
            'hasta'    => $hasta,
            'activa'   => 1,
        ]);
        $modelo->agregarProductos($id, [$idProducto]);

        $this->redirect($volver . '&ok=oferta');
    }

    // ─── HELPERS ───────────────────────────────────────────────────────────────

    private function soloPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '/admin/promociones');
        }
    }

    /** "2026-10-05T00:00" (input datetime-local) → "2026-10-05 00:00:00", o null. */
    private function fechaDesdeInput(string $valor): ?string
    {
        $d = DateTime::createFromFormat('Y-m-d\TH:i', trim($valor));
        return $d ? $d->format('Y-m-d H:i:00') : null;
    }

    private function flash(string $tipo, string $texto, string $url): void
    {
        $_SESSION['promo_msg'] = [$tipo, $texto];
        $this->redirect($url);
    }
}