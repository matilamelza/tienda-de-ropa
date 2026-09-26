<?php

class EstadisticasController extends Controller
{
    public function index()
    {
        $periodo = $_GET['periodo'] ?? '30d';
        if (!isset(DashboardController::PERIODOS[$periodo])) {
            $periodo = '30d';
        }

        [$desde, $hasta, $antDesde, $antHasta] = DashboardController::rangos($periodo);

        $d = $desde->format('Y-m-d H:i:s');
        $h = $hasta->format('Y-m-d H:i:s');

        $visitaModel = new Visita();

        // Resumen + comparación
        $resumen  = $visitaModel->resumen($d, $h);
        $anterior = $antDesde
            ? $visitaModel->resumen($antDesde->format('Y-m-d H:i:s'), $antHasta->format('Y-m-d H:i:s'))
            : null;

        $embudo = $visitaModel->embudo($d, $h);

        // Gráfico (en "Todo" se muestran los últimos 30 días)
        [$gDesde, $gHasta] = $periodo === 'todo' ? DashboardController::rangos('30d') : [$desde, $hasta];
        $grafico = DashboardController::armarGrafico(
            $visitaModel->porDia($gDesde->format('Y-m-d H:i:s'), $gHasta->format('Y-m-d H:i:s')),
            $gDesde,
            $gHasta
        );

        // Productos: más vistos, y de esos, los que no se vendieron
        $topProductos = $visitaModel->topProductos($d, $h, 20);
        $vistosSinVenta = array_values(array_filter(
            $topProductos,
            fn($p) => (int) $p['vendidas'] === 0 && (int) $p['visitantes'] >= 5
        ));

        $this->view('admin/estadisticas/index', [
            'periodo'        => $periodo,
            'periodos'       => DashboardController::PERIODOS,
            'resumen'        => $resumen,
            'variaciones'    => [
                'visitantes' => $anterior ? DashboardController::variacion($resumen['visitantes'], $anterior['visitantes']) : null,
                'vistas'     => $anterior ? DashboardController::variacion($resumen['vistas'], $anterior['vistas']) : null,
            ],
            'conversion'     => $embudo['entraron'] > 0 ? $embudo['pedidos'] / $embudo['entraron'] * 100 : null,
            'embudo'         => $embudo,
            'grafico'        => $grafico,
            'graficoTitulo'  => $periodo === 'todo' ? 'Visitantes de los últimos 30 días' : 'Visitantes por día',
            'topProductos'   => array_slice($topProductos, 0, 10),
            'vistosSinVenta' => array_slice($vistosSinVenta, 0, 5),
            'topCategorias'  => $visitaModel->topCategorias($d, $h),
            'topMarcas'      => $visitaModel->topMarcas($d, $h),
            'busquedas'      => $visitaModel->busquedas($d, $h),
            'sinResultados'  => $visitaModel->busquedas($d, $h, true),
            'origenes'       => $visitaModel->origenes($d, $h),
            'dispositivos'   => $visitaModel->dispositivos($d, $h),
        ]);
    }
}