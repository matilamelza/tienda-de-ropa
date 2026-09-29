<?php

class EstadisticasController extends Controller
{
    public const PERIODOS = [
        'hoy'           => 'Hoy',
        '7d'            => '7 días',
        '30d'           => '30 días',
        'mes'           => 'Este mes',
        'mes_pasado'    => 'Mes pasado',
        'anio'          => 'Este año',
        'todo'          => 'Todo',
        'personalizado' => 'Personalizado',
    ];

    public const PESTANAS = [
        'resumen'   => '📊 Resumen',
        'trafico'   => '🚦 Tráfico',
        'productos' => '👟 Productos',
        'ventas'    => '💰 Ventas',
        'clientes'  => '👥 Clientes',
        'campanias' => '📣 Campañas',
    ];

    public function index()
    {
        $rango = $this->rango();
        $tab   = isset(self::PESTANAS[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'resumen';

        $d  = $rango['desde']->format('Y-m-d H:i:s');
        $h  = $rango['hasta']->format('Y-m-d H:i:s');
        $ad = $rango['antDesde'] ? $rango['antDesde']->format('Y-m-d H:i:s') : null;
        $ah = $rango['antHasta'] ? $rango['antHasta']->format('Y-m-d H:i:s') : null;

        $datos = [
            'rango'    => $rango,
            'tab'      => $tab,
            'periodos' => self::PERIODOS,
            'pestanas' => self::PESTANAS,
        ];

        $visitaModel = new Visita();

        switch ($tab) {
            case 'resumen':
                $datos += $this->datosResumen($visitaModel, $rango, $d, $h, $ad, $ah);
                break;

            case 'trafico':
                $datos += [
                    'mapa'         => $visitaModel->mapaCalor($d, $h),
                    'nuevos'       => $visitaModel->nuevosVsRecurrentes($d, $h),
                    'sesiones'     => $visitaModel->sesiones($d, $h),
                    'sesionesAnt'  => $ad ? $visitaModel->sesiones($ad, $ah) : null,
                    'entradas'     => $visitaModel->paginasEntrada($d, $h),
                    'origenes'     => $visitaModel->origenes($d, $h),
                    'dispositivos' => $visitaModel->dispositivos($d, $h),
                ];
                break;
        }

        $this->view('admin/estadisticas/index', $datos);
    }

    /** Datos de la pestaña Resumen (lo que ya teníamos). */
    private function datosResumen(Visita $m, array $rango, string $d, string $h, ?string $ad, ?string $ah): array
    {
        $resumen  = $m->resumen($d, $h);
        $anterior = $ad ? $m->resumen($ad, $ah) : null;
        $embudo   = $m->embudo($d, $h);

        // Gráfico: por día si el rango es de hasta 62 días; si no, por mes
        $dias = (int) $rango['desde']->diff(min($rango['hasta'], new DateTimeImmutable('tomorrow')))->days;
        if ($dias <= 62) {
            $grafico = DashboardController::armarGrafico($m->porDia($d, $h), $rango['desde'], $rango['hasta']);
            $graficoTitulo = 'Visitantes por día';
        } else {
            $grafico = $this->graficoPorMes($m->porMes($d, $h), $rango['desde'], $rango['hasta']);
            $graficoTitulo = 'Visitantes por mes';
        }

        $topProductos   = $m->topProductos($d, $h, 20);
        $vistosSinVenta = array_values(array_filter(
            $topProductos,
            fn($p) => (int) $p['vendidas'] === 0 && (int) $p['visitantes'] >= 5
        ));

        return [
            'resumen'        => $resumen,
            'variaciones'    => [
                'visitantes' => $anterior ? DashboardController::variacion($resumen['visitantes'], $anterior['visitantes']) : null,
                'vistas'     => $anterior ? DashboardController::variacion($resumen['vistas'], $anterior['vistas']) : null,
            ],
            'conversion'     => $embudo['entraron'] > 0 ? $embudo['pedidos'] / $embudo['entraron'] * 100 : null,
            'embudo'         => $embudo,
            'grafico'        => $grafico,
            'graficoTitulo'  => $graficoTitulo,
            'topProductos'   => array_slice($topProductos, 0, 10),
            'vistosSinVenta' => array_slice($vistosSinVenta, 0, 5),
            'topCategorias'  => $m->topCategorias($d, $h),
            'topMarcas'      => $m->topMarcas($d, $h),
            'busquedas'      => $m->busquedas($d, $h),
            'sinResultados'  => $m->busquedas($d, $h, true),
        ];
    }

    /**
     * Rango de fechas según el período elegido, y el rango con el que se compara.
     * Rangos semiabiertos: desde <= fecha < hasta.
     */
    private function rango(): array
    {
        $periodo  = isset(self::PERIODOS[$_GET['periodo'] ?? '']) ? $_GET['periodo'] : '30d';
        $comparar = ($_GET['comparar'] ?? '') === 'anio' ? 'anio' : 'anterior';

        $hoy    = new DateTimeImmutable('today');
        $manana = $hoy->modify('+1 day');

        switch ($periodo) {
            case 'hoy':
                [$desde, $hasta] = [$hoy, $manana];
                break;
            case '7d':
                [$desde, $hasta] = [$manana->modify('-7 days'), $manana];
                break;
            case 'anio':
                $desde = $hoy->modify('first day of january this year');
                $hasta = $desde->modify('+1 year');
                break;
            case 'todo':
                [$desde, $hasta] = [new DateTimeImmutable('2000-01-01'), $manana];
                break;
            case 'personalizado':
                $d1 = DateTimeImmutable::createFromFormat('!Y-m-d', $_GET['desde'] ?? '');
                $d2 = DateTimeImmutable::createFromFormat('!Y-m-d', $_GET['hasta'] ?? '');
                if (!$d1 || !$d2) {
                    [$desde, $hasta] = [$manana->modify('-30 days'), $manana];
                    $periodo = '30d';
                } else {
                    if ($d2 < $d1) [$d1, $d2] = [$d2, $d1];
                    [$desde, $hasta] = [$d1, $d2->modify('+1 day')];
                }
                break;
            default: // 30d, mes, mes_pasado
                [$desde, $hasta] = DashboardController::rangos($periodo);
        }

        // Rango de comparación
        if ($periodo === 'todo') {
            [$antDesde, $antHasta] = [null, null];
        } elseif ($comparar === 'anio') {
            [$antDesde, $antHasta] = [$desde->modify('-1 year'), $hasta->modify('-1 year')];
        } else {
            $dias = (int) $desde->diff($hasta)->days;
            [$antDesde, $antHasta] = [$desde->modify("-$dias days"), $desde];
        }

        return [
            'periodo'   => $periodo,
            'comparar'  => $comparar,
            'desde'     => $desde,
            'hasta'     => $hasta,
            'antDesde'  => $antDesde,
            'antHasta'  => $antHasta,
            'etiqueta'  => $desde->format('d/m/Y') . ' al ' . $hasta->modify('-1 day')->format('d/m/Y'),
        ];
    }

    /** Completa todos los meses del rango (los sin datos en 0). */
    private function graficoPorMes(array $porMes, DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        $meses  = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $limite = min($hasta, new DateTimeImmutable('tomorrow'));
        $inicio = max($desde, new DateTimeImmutable('-24 months first day of this month'));   // en "Todo", máximo 2 años
        $datos  = [];

        for ($m = $inicio->modify('first day of this month'); $m < $limite; $m = $m->modify('+1 month')) {
            $clave   = $m->format('Y-m');
            $datos[] = [
                'fecha'   => $clave,
                'label'   => $meses[(int) $m->format('n')] . ' ' . $m->format('y'),
                'total'   => $porMes[$clave]['total'] ?? 0,
                'pedidos' => $porMes[$clave]['pedidos'] ?? 0,
            ];
        }

        return $datos;
    }

    /** Temporal: diagnóstico de visitas y eventos. */
    public function diagnostico()
    {
        $r = (new Visita())->diagnostico();

        $r['Tu navegador'] = $_SERVER['HTTP_USER_AGENT'] ?? '(vacío)';
        $r['Cookie vid']   = $_COOKIE['vid'] ?? '(no tiene)';
        $r['Cookie camp']  = $_COOKIE['camp'] ?? '(no tiene)';

        header('Content-Type: text/html; charset=UTF-8');
        echo '<div style="font-family:system-ui;max-width:900px;margin:30px auto;padding:0 16px">';
        echo '<h2>Diagnóstico de estadísticas</h2><table border="1" cellpadding="8" style="border-collapse:collapse;width:100%">';
        foreach ($r as $clave => $valor) {
            $txt = is_array($valor) ? '<pre>' . htmlspecialchars(print_r($valor, true)) . '</pre>' : htmlspecialchars((string) $valor);
            $mal = !is_array($valor) && (strpos((string) $valor, 'ERROR') === 0 || $valor === 'FALTA');
            echo '<tr' . ($mal ? ' style="background:#fee2e2"' : '') . '><th align="left">' . htmlspecialchars($clave) . '</th><td>' . $txt . '</td></tr>';
        }
        echo '</table></div>';
        exit;
    }
}