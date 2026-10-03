<?php

class DashboardController extends Controller
{
    public const PERIODOS = [
        'mes'        => 'Este mes',
        'mes_pasado' => 'Mes pasado',
        '30d'        => 'Últimos 30 días',
        'todo'       => 'Todo',
    ];

        public function index()
    {
        $pedidoModel   = new Pedido();
        $productoModel = new Producto();
        $visitaModel   = new Visita();
        $cfg           = new ConfiguracionTienda();

        // ── Período elegido ────────────────────────────────────────────────────
        $periodo = $_GET['periodo'] ?? 'mes';
        if (!isset(self::PERIODOS[$periodo])) {
            $periodo = 'mes';
        }

        [$desde, $hasta, $antDesde, $antHasta] = self::rangos($periodo);

        $d  = $desde->format('Y-m-d H:i:s');
        $h  = $hasta->format('Y-m-d H:i:s');
        $ad = $antDesde ? $antDesde->format('Y-m-d H:i:s') : null;
        $ah = $antHasta ? $antHasta->format('Y-m-d H:i:s') : null;

        // ── Números principales + comparación ─────────────────────────────────
        $resumen  = $pedidoModel->resumenPeriodo($d, $h);
        $anterior = $ad ? $pedidoModel->resumenPeriodo($ad, $ah) : null;

        $resumen['cobrado'] = $pedidoModel->cobradoPeriodo($d, $h);
        $cobradoAnt         = $ad ? $pedidoModel->cobradoPeriodo($ad, $ah) : null;

        $variaciones = [
            'ventas'          => $anterior ? self::variacion($resumen['ventas'], $anterior['ventas']) : null,
            'ganancia'        => $anterior ? self::variacion($resumen['ganancia'], $anterior['ganancia']) : null,
            'pedidos'         => $anterior ? self::variacion($resumen['pedidos'], $anterior['pedidos']) : null,
            'ticket_promedio' => $anterior ? self::variacion($resumen['ticket_promedio'], $anterior['ticket_promedio']) : null,
            'cobrado'         => $cobradoAnt !== null ? self::variacion($resumen['cobrado'], $cobradoAnt) : null,
        ];

        // ── Visitantes ─────────────────────────────────────────────────────────
        $visitas         = $visitaModel->resumen($d, $h);
        $visitasAnterior = $ad ? $visitaModel->resumen($ad, $ah) : null;
        $variaciones['visitantes'] = $visitasAnterior ? self::variacion($visitas['visitantes'], $visitasAnterior['visitantes']) : null;

        // ── Hoy ────────────────────────────────────────────────────────────────
        $hoy = $pedidoModel->resumenHoy();
        $hoy['visitas'] = $visitaModel->resumen(date('Y-m-d 00:00:00'), date('Y-m-d 00:00:00', strtotime('+1 day')))['visitantes'];

        // ── Gráfico: ventas, cobrado y período anterior (día por día) ──────────
        [$gDesde, $gHasta] = $periodo === 'todo' ? self::rangos('30d') : [$desde, $hasta];
        $gd = $gDesde->format('Y-m-d H:i:s');
        $gh = $gHasta->format('Y-m-d H:i:s');

        $grafico = self::armarGrafico($pedidoModel->ventasPorDia($gd, $gh), $gDesde, $gHasta);
        $cobrado = self::armarGrafico($pedidoModel->cobradoPorDia($gd, $gh), $gDesde, $gHasta);

        $previo = [];
        if ($periodo !== 'todo' && $antDesde) {
            // Mismo largo que el período actual, contando desde el inicio del anterior
            $previoHasta = $antDesde->modify('+' . count($grafico) . ' days');
            $previo = self::armarGrafico(
                $pedidoModel->ventasPorDia($ad, $previoHasta->format('Y-m-d H:i:s')),
                $antDesde,
                $previoHasta
            );
        }
        foreach ($grafico as $i => &$g) {
            $g['cobrado']  = $cobrado[$i]['total'] ?? 0;
            $g['anterior'] = $previo[$i]['total'] ?? null;
        }
        unset($g);

        // ── Meta del mes (siempre del mes en curso, sin importar el período) ───
        $meta = (float) $cfg->get('meta_ventas_mes', 0);
        [$mesDesde, $mesHasta] = self::rangos('mes');
        $ventasMes = $periodo === 'mes'
            ? $resumen['ventas']
            : $pedidoModel->resumenPeriodo($mesDesde->format('Y-m-d H:i:s'), $mesHasta->format('Y-m-d H:i:s'))['ventas'];

        // ── Agotados que buscan (últimos 30 días) ─────────────────────────────
        [$d30, $h30] = self::rangos('30d');
        $agotados = (new Reportes())->agotadosBuscados($d30->format('Y-m-d H:i:s'), $h30->format('Y-m-d H:i:s'), 6);

        $this->view('dashboard/index', [
            'periodo'         => $periodo,
            'periodos'        => self::PERIODOS,
            'resumen'         => $resumen,
            'variaciones'     => $variaciones,
            'visitas'         => $visitas,
            'hoy'             => $hoy,
            'grafico'         => $grafico,
            'graficoTitulo'   => $periodo === 'todo' ? 'Ventas de los últimos 30 días' : 'Ventas por día',
            'hayAnterior'     => !empty($previo),
            'deuda'           => $pedidoModel->resumenListado(),
            'cajas'           => (new Caja())->listarConSaldo(true),
            'canales'         => $pedidoModel->ventasPorOrigen($d, $h),
            'paraEntregar'    => $pedidoModel->listosParaEntregar(6),
            'agotados'        => $agotados,
            'sinGuia'         => (new GuiaTalles())->productosSinGuia(),
            'meta'            => $meta,
            'ventasMes'       => $ventasMes,
            'topProductos'    => $pedidoModel->topProductos($d, $h, 5),
            'alertasPedidos'  => $pedidoModel->alertasPedidos(),
            'alertasCatalogo' => $productoModel->alertasCatalogo(),
            'ultimosPedidos'  => $pedidoModel->ultimosPedidos(6),
            'stockBajo'       => $productoModel->productosStockBajo(3),
        ]);
    }

    /** Guarda la meta de ventas del mes (0 = sin meta). */
    public function guardarMeta()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $meta = max(0, (float) str_replace(',', '.', $_POST['meta'] ?? '0'));
            (new ConfiguracionTienda())->guardarMultiple(['meta_ventas_mes' => (string) round($meta, 2)]);
        }
        $this->redirect($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/admin');
    }

    /**
     * Devuelve [desde, hasta, anteriorDesde, anteriorHasta] como DateTimeImmutable.
     * Rangos semiabiertos: desde <= fecha < hasta.
     * En "todo" no hay período anterior (null).
     */
    public static function rangos(string $periodo): array
    {
        $hoy    = new DateTimeImmutable('today');
        $manana = $hoy->modify('+1 day');

        switch ($periodo) {
            case 'mes_pasado':
                $desde = $hoy->modify('first day of last month');
                $hasta = $hoy->modify('first day of this month');
                return [$desde, $hasta, $desde->modify('-1 month'), $desde];

            case '30d':
                $desde = $manana->modify('-30 days');
                return [$desde, $manana, $desde->modify('-30 days'), $desde];

            case 'todo':
                return [new DateTimeImmutable('2000-01-01'), $manana, null, null];

            case 'mes':
            default:
                $desde = $hoy->modify('first day of this month');
                $hasta = $desde->modify('+1 month');
                return [$desde, $hasta, $desde->modify('-1 month'), $desde];
        }
    }

    /** % de cambio respecto del anterior. null si el anterior es 0 (no se puede comparar). */
    public static function variacion(float $actual, float $anterior): ?float
    {
        if ($anterior == 0) {
            return null;
        }

        return ($actual - $anterior) / abs($anterior) * 100;
    }

    /**
     * Completa todos los días del rango (los sin datos en 0).
     * No incluye días futuros: en "Este mes" el gráfico llega hasta hoy.
     */
    public static function armarGrafico(array $porDia, DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        $limite = min($hasta, new DateTimeImmutable('tomorrow'));
        $dias   = [];

        for ($d = $desde; $d < $limite; $d = $d->modify('+1 day')) {
            $clave  = $d->format('Y-m-d');
            $dias[] = [
                'fecha'   => $clave,
                'label'   => $d->format('d/m'),
                'total'   => $porDia[$clave]['total'] ?? 0,
                'pedidos' => $porDia[$clave]['pedidos'] ?? 0,
            ];
        }

        return $dias;
    }
}