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

        // ── Período elegido ────────────────────────────────────────────────────
        $periodo = $_GET['periodo'] ?? 'mes';
        if (!isset(self::PERIODOS[$periodo])) {
            $periodo = 'mes';
        }

        [$desde, $hasta, $antDesde, $antHasta] = self::rangos($periodo);

        $d = $desde->format('Y-m-d H:i:s');
        $h = $hasta->format('Y-m-d H:i:s');

        // ── Números principales + comparación con el período anterior ──────────
        $resumen  = $pedidoModel->resumenPeriodo($d, $h);
        $anterior = $antDesde
            ? $pedidoModel->resumenPeriodo($antDesde->format('Y-m-d H:i:s'), $antHasta->format('Y-m-d H:i:s'))
            : null;

        $variaciones = [
            'ventas'          => $anterior ? self::variacion($resumen['ventas'], $anterior['ventas']) : null,
            'ganancia'        => $anterior ? self::variacion($resumen['ganancia'], $anterior['ganancia']) : null,
            'pedidos'         => $anterior ? self::variacion($resumen['pedidos'], $anterior['pedidos']) : null,
            'ticket_promedio' => $anterior ? self::variacion($resumen['ticket_promedio'], $anterior['ticket_promedio']) : null,
        ];

        // ── Visitantes (tarjeta que lleva a Estadísticas) ──────────────────────
        $visitas         = $visitaModel->resumen($d, $h);
        $visitasAnterior = $antDesde
            ? $visitaModel->resumen($antDesde->format('Y-m-d H:i:s'), $antHasta->format('Y-m-d H:i:s'))
            : null;

        $variaciones['visitantes'] = $visitasAnterior
            ? self::variacion($visitas['visitantes'], $visitasAnterior['visitantes'])
            : null;

        // ── Gráfico de ventas por día ──────────────────────────────────────────
        // En "Todo" se grafican los últimos 30 días (si no, serían cientos de barras)
        [$gDesde, $gHasta] = $periodo === 'todo'
            ? self::rangos('30d')
            : [$desde, $hasta];

        $grafico = self::armarGrafico(
            $pedidoModel->ventasPorDia($gDesde->format('Y-m-d H:i:s'), $gHasta->format('Y-m-d H:i:s')),
            $gDesde,
            $gHasta
        );

        // ── Resto ──────────────────────────────────────────────────────────────
        $this->view('dashboard/index', [
            'periodo'         => $periodo,
            'periodos'        => self::PERIODOS,
            'resumen'         => $resumen,
            'variaciones'     => $variaciones,
            'visitas'         => $visitas,
            'grafico'         => $grafico,
            'graficoTitulo'   => $periodo === 'todo' ? 'Ventas de los últimos 30 días' : 'Ventas por día',
            'topProductos'    => $pedidoModel->topProductos($d, $h, 5),
            'alertasPedidos'  => $pedidoModel->alertasPedidos(3),
            'alertasCatalogo' => $productoModel->alertasCatalogo(),
            'ultimosPedidos'  => $pedidoModel->ultimosPedidos(6),
            'stockBajo'       => $productoModel->productosStockBajo(3),
        ]);
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