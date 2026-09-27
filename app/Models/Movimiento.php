<?php

require_once __DIR__ . '/Conexion.php';

class Movimiento extends Conexion
{
    public const TIPOS = [
        'cobro'                 => 'Cobro',
        'devolucion'            => 'Devolución',
        'gasto'                 => 'Gasto',
        'ingreso'               => 'Ingreso',
        'pago_proveedor'        => 'Pago a proveedor',
        'transferencia_salida'  => 'Transferencia (sale)',
        'transferencia_entrada' => 'Transferencia (entra)',
        'ajuste'                => 'Ajuste de saldo',
    ];

    // ─── ALTA ──────────────────────────────────────────────────────────────────

    /** Inserta un movimiento. Uso interno: los métodos de abajo arman los datos. */
    private function insertar(array $d): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO movimientos
             (fecha, id_caja, tipo, monto, comision, neto_caja, id_medio, id_categoria_mov,
              id_pedido, id_relacionado, concepto, comprobante)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "sisdddiiiiss",
            $d['fecha'],
            $d['id_caja'],
            $d['tipo'],
            $d['monto'],
            $d['comision'],
            $d['neto_caja'],
            $d['id_medio'],
            $d['id_categoria_mov'],
            $d['id_pedido'],
            $d['id_relacionado'],
            $d['concepto'],
            $d['comprobante']
        );
        $stmt->execute();

        return (int) $this->db->insert_id;
    }

    private function base(array $d): array
    {
        return array_merge([
            'fecha'            => date('Y-m-d H:i:s'),
            'comision'         => 0,
            'id_medio'         => null,
            'id_categoria_mov' => null,
            'id_pedido'        => null,
            'id_relacionado'   => null,
            'concepto'         => null,
            'comprobante'      => null,
        ], $d);
    }

    public function registrarGasto(int $id_caja, float $monto, int $id_categoria, ?string $concepto, string $fecha, ?string $comprobante = null): int
    {
        return $this->insertar($this->base([
            'fecha'            => $fecha,
            'id_caja'          => $id_caja,
            'tipo'             => 'gasto',
            'monto'            => $monto,
            'neto_caja'        => -$monto,
            'id_categoria_mov' => $id_categoria,
            'concepto'         => $concepto,
            'comprobante'      => $comprobante,
        ]));
    }

    public function registrarIngreso(int $id_caja, float $monto, int $id_categoria, ?string $concepto, string $fecha, ?string $comprobante = null): int
    {
        return $this->insertar($this->base([
            'fecha'            => $fecha,
            'id_caja'          => $id_caja,
            'tipo'             => 'ingreso',
            'monto'            => $monto,
            'neto_caja'        => $monto,
            'id_categoria_mov' => $id_categoria,
            'concepto'         => $concepto,
            'comprobante'      => $comprobante,
        ]));
    }

    /**
     * Ajuste de saldo: $saldoReal es cuánto HAY en la caja.
     * Se registra la diferencia con el saldo que calcula el sistema.
     * Devuelve 0 si no había diferencia.
     */
    public function ajustarSaldo(int $id_caja, float $saldoReal, ?string $concepto, string $fecha): int
    {
        $diferencia = round($saldoReal - $this->saldoCaja($id_caja), 2);

        if ($diferencia == 0) {
            return 0;
        }

        return $this->insertar($this->base([
            'fecha'     => $fecha,
            'id_caja'   => $id_caja,
            'tipo'      => 'ajuste',
            'monto'     => abs($diferencia),
            'neto_caja' => $diferencia,
            'concepto'  => $concepto ?: 'Ajuste de saldo',
        ]));
    }

    /** Pasa plata de una caja a otra: dos movimientos relacionados, en una transacción. */
    public function transferir(int $origen, int $destino, float $monto, ?string $concepto, string $fecha): bool
    {
        if ($origen === $destino || $monto <= 0) {
            return false;
        }

        try {
            $this->db->begin_transaction();

            $idSalida = $this->insertar($this->base([
                'fecha'     => $fecha,
                'id_caja'   => $origen,
                'tipo'      => 'transferencia_salida',
                'monto'     => $monto,
                'neto_caja' => -$monto,
                'concepto'  => $concepto,
            ]));

            $idEntrada = $this->insertar($this->base([
                'fecha'          => $fecha,
                'id_caja'        => $destino,
                'tipo'           => 'transferencia_entrada',
                'monto'          => $monto,
                'neto_caja'      => $monto,
                'id_relacionado' => $idSalida,
                'concepto'       => $concepto,
            ]));

            $stmt = $this->db->prepare("UPDATE movimientos SET id_relacionado = ? WHERE id_movimiento = ?");
            $stmt->bind_param("ii", $idEntrada, $idSalida);
            $stmt->execute();

            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            $this->db->rollback();
            error_log('transferir: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cobro de un pedido (se usa en la etapa 2).
     * La caja y la comisión salen del medio de pago.
     */
    public function registrarCobro(int $id_pedido, array $medio, float $monto, string $fecha, ?string $comprobante = null, ?string $concepto = null): int
    {
        $comision = round($monto * (float) $medio['comision_pct'] / 100, 2);

        return $this->insertar($this->base([
            'fecha'       => $fecha,
            'id_caja'     => (int) $medio['id_caja'],
            'tipo'        => 'cobro',
            'monto'       => $monto,
            'comision'    => $comision,
            'neto_caja'   => $monto - $comision,
            'id_medio'    => (int) $medio['id_medio'],
            'id_pedido'   => $id_pedido,
            'concepto'    => $concepto ?: 'Cobro pedido #' . $id_pedido,
            'comprobante' => $comprobante,
        ]));
    }

    // ─── ANULAR ────────────────────────────────────────────────────────────────

    /** Anula un movimiento (y su contraparte si es una transferencia). No se borra nada. */
    public function anular(int $id): bool
    {
        $mov = $this->buscarPorId($id);

        if (!$mov || $mov['anulado_at'] !== null) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE movimientos SET anulado_at = NOW()
             WHERE (id_movimiento = ? OR id_movimiento = ?) AND anulado_at IS NULL"
        );
        $relacionado = (int) ($mov['id_relacionado'] ?? 0);
        $stmt->bind_param("ii", $id, $relacionado);

        return $stmt->execute();
    }

    // ─── CONSULTAS ─────────────────────────────────────────────────────────────

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM movimientos WHERE id_movimiento = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function saldoCaja(int $id_caja): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(neto_caja), 0) AS saldo FROM movimientos WHERE id_caja = ? AND anulado_at IS NULL"
        );
        $stmt->bind_param("i", $id_caja);
        $stmt->execute();

        return (float) $stmt->get_result()->fetch_assoc()['saldo'];
    }

    /**
     * Filtros: caja (id), tipo, categoria (id), desde / hasta ('Y-m-d H:i:s', rango semiabierto),
     *          anulados (bool: incluir anulados)
     */
    private function filtro(array $f): array
    {
        $where  = [];
        $params = [];
        $types  = '';

        if (empty($f['anulados'])) {
            $where[] = 'm.anulado_at IS NULL';
        }
        if (!empty($f['caja'])) {
            $where[]  = 'm.id_caja = ?';
            $params[] = (int) $f['caja'];
            $types   .= 'i';
        }
        if (!empty($f['tipo']) && isset(self::TIPOS[$f['tipo']])) {
            $where[]  = 'm.tipo = ?';
            $params[] = $f['tipo'];
            $types   .= 's';
        }
        if (!empty($f['categoria'])) {
            $where[]  = 'm.id_categoria_mov = ?';
            $params[] = (int) $f['categoria'];
            $types   .= 'i';
        }
        if (!empty($f['desde'])) {
            $where[]  = 'm.fecha >= ?';
            $params[] = $f['desde'];
            $types   .= 's';
        }
        if (!empty($f['hasta'])) {
            $where[]  = 'm.fecha < ?';
            $params[] = $f['hasta'];
            $types   .= 's';
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params, $types];
    }

    public function listar(array $filtros, int $pagina = 1, int $porPagina = 30): array
    {
        [$where, $params, $types] = $this->filtro($filtros);

        $params[] = $porPagina;
        $params[] = ($pagina - 1) * $porPagina;
        $types   .= 'ii';

        $sql = "SELECT m.*,
                       c.nombre  AS caja,
                       mp.nombre AS medio,
                       cm.nombre AS categoria,
                       cr.nombre AS caja_relacionada
                FROM movimientos m
                INNER JOIN cajas c ON c.id_caja = m.id_caja
                LEFT JOIN medios_pago mp ON mp.id_medio = m.id_medio
                LEFT JOIN categorias_movimiento cm ON cm.id_categoria_mov = m.id_categoria_mov
                LEFT JOIN movimientos mr ON mr.id_movimiento = m.id_relacionado
                LEFT JOIN cajas cr ON cr.id_caja = mr.id_caja
                $where
                ORDER BY m.fecha DESC, m.id_movimiento DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function contar(array $filtros): int
    {
        [$where, $params, $types] = $this->filtro($filtros);

        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM movimientos m $where");
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        return (int) $stmt->get_result()->fetch_assoc()['total'];
    }

    /**
     * Totales de lo filtrado: entró, salió, comisiones.
     * Las transferencias y ajustes no cuentan como entradas/salidas "reales".
     */
    public function totales(array $filtros): array
    {
        $filtros['anulados'] = false;
        [$where, $params, $types] = $this->filtro($filtros);

        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN m.tipo IN ('cobro','ingreso') THEN m.neto_caja END), 0)                       AS entradas,
                    COALESCE(SUM(CASE WHEN m.tipo IN ('gasto','devolucion','pago_proveedor') THEN -m.neto_caja END), 0) AS salidas,
                    COALESCE(SUM(m.comision), 0)                                                                          AS comisiones
                FROM movimientos m
                $where";

        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return array_map('floatval', $row);
    }
}