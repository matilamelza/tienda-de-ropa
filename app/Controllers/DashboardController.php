<?php

class DashboardController extends Controller
{
    private const PERIODOS = [
        'mes'        => 'Este mes',
        'mes_pasado' => 'Mes pasado',
        '30d'        => 'Últimos 30 días',
        'todo'       => 'Todo',
    ];

    public function index()
    {
        $pedidoModel   = new Pedido();
        $productoModel = new Producto();

        // ── Período elegido ────────────────────────────────────────────────────
        $periodo = $_GET['periodo'] ?? 'mes';
        if (!isset(self::PERIODOS[$periodo])) {
            $periodo = 'mes';
        }

        [$desde, $hasta, $antDesde, $antHasta] = $this->rangos($periodo);

        // ── Números principales + comparación con el período anterior ──────────
        $resumen  = $pedidoModel->resumenPeriodo($desde->format('Y-m-d H:i:s'), $hasta->format('Y-m-d H:i:s'));
        $anterior = $antDesde
            ? $pedidoModel->resumenPeriodo($antDesde->format('Y-m-d H:i:s'), $antHasta->format('Y-m-d H:i:s'))
            : null;

        $variaciones = [
            'ventas'          => $anterior ? $this->variacion($resumen['ventas'], $anterior['ventas']) : null,
            'ganancia'        => $anterior ? $this->variacion($resumen['ganancia'], $anterior['ganancia']) : null,
            'pedidos'         => $anterior ? $this->variacion($resumen['pedidos'], $anterior['pedidos']) : null,
            'ticket_promedio' => $anterior ? $this->variacion($resumen['ticket_promedio'], $anterior['ticket_promedio']) : null,
        ];

        // ── Gráfico de ventas por día ──────────────────────────────────────────
        // En "Todo" se grafican los últimos 30 días (si no, serían cientos de barras)
        [$gDesde, $gHasta] = $periodo === 'todo'
            ? $this->rangos('30d')
            : [$desde, $hasta];

        $grafico = $this->armarGrafico(
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
            'grafico'         => $grafico,
            'graficoTitulo'   => $periodo === 'todo' ? 'Ventas de los últimos 30 días' : 'Ventas por día',
            'topProductos'    => $pedidoModel->topProductos($desde->format('Y-m-d H:i:s'), $hasta->format('Y-m-d H:i:s'), 5),
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
    private function rangos(string $periodo): array
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
    private function variacion(float $actual, float $anterior): ?float
    {
        if ($anterior == 0) {
            return null;
        }

        return ($actual - $anterior) / abs($anterior) * 100;
    }

    /**
     * Completa todos los días del rango (los sin ventas en 0).
     * No incluye días futuros: en "Este mes" el gráfico llega hasta hoy.
     */
    private function armarGrafico(array $ventasPorDia, DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        $limite = min($hasta, new DateTimeImmutable('tomorrow'));
        $dias   = [];

        for ($d = $desde; $d < $limite; $d = $d->modify('+1 day')) {
            $clave  = $d->format('Y-m-d');
            $dias[] = [
                'fecha'   => $clave,
                'label'   => $d->format('d/m'),
                'total'   => $ventasPorDia[$clave]['total'] ?? 0,
                'pedidos' => $ventasPorDia[$clave]['pedidos'] ?? 0,
            ];
        }

        return $dias;
    }
}