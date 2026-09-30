<?php

require_once __DIR__ . '/Conexion.php';

/**
 * Etapa 2: estados, stock, cobros, cancelación, saldo a favor e historial de un pedido.
 * Todo lo que mueve plata o stock va en una transacción y con esta misma conexión.
 */
class GestionPedido extends Conexion
{
    /** clave => [texto, clases del badge] */
    public const ESTADOS = [
        'pendiente_contacto' => ['Pendiente de contacto', 'bg-gray-100 text-gray-700'],
        'contactado'         => ['Contactado',            'bg-blue-100 text-blue-800'],
        'confirmado'         => ['Confirmado',            'bg-indigo-100 text-indigo-800'],
        'listo'              => ['Listo para entregar',   'bg-amber-100 text-amber-800'],
        'entregado'          => ['Entregado',             'bg-green-100 text-green-800'],
        'cancelado'          => ['Cancelado',             'bg-red-100 text-red-700'],
    ];

    /** Estados que tienen el stock reservado */
    public const RESERVAN = ['confirmado', 'listo'];

    /** A qué estados se puede pasar desde cada uno (cancelar va aparte) */
    public const TRANSICIONES = [
        'pendiente_contacto' => ['contactado', 'confirmado', 'entregado'],
        'contactado'         => ['pendiente_contacto', 'confirmado', 'entregado'],
        'confirmado'         => ['contactado', 'listo', 'entregado'],
        'listo'              => ['confirmado', 'entregado'],
        'entregado'          => [],
        'cancelado'          => [],
    ];

    public const ESTADOS_PAGO = [
        'sin_pagar' => ['Sin pagar',      'bg-red-50 text-red-700'],
        'senado'    => ['Señado',         'bg-yellow-100 text-yellow-800'],
        'pagado'    => ['Pagado',         'bg-green-100 text-green-800'],
        'de_mas'    => ['Pagado de más',  'bg-purple-100 text-purple-800'],
    ];

    public static function etiqueta(string $estado): string
    {
        return self::ESTADOS[$estado][0] ?? $estado;
    }

    public static function clase(string $estado): string
    {
        return self::ESTADOS[$estado][1] ?? 'bg-gray-100 text-gray-700';
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CONSULTAS
    // ═══════════════════════════════════════════════════════════════════════

    public function pedido(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, c.nombre, c.apellido, c.telefono, c.email, c.direccion, c.localidad,
                    mp.nombre AS medio_acordado
             FROM pedidos p
             LEFT JOIN clientes c     ON c.id_cliente = p.id_cliente
             LEFT JOIN medios_pago mp ON mp.id_medio  = p.id_medio_acordado
             WHERE p.id_pedido = ?
             LIMIT 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function items(int $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT pi.*, pv.id_producto
             FROM pedido_items pi
             LEFT JOIN producto_variantes pv ON pv.id_variante = pi.id_variante
             WHERE pi.id_pedido = ?
             ORDER BY pi.id_item ASC"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function historial(int $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM pedido_historial WHERE id_pedido = ? ORDER BY fecha DESC, id_historial DESC"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Cobros y devoluciones del pedido (incluye anulados, marcados). */
    public function cobros(int $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.*, mp.nombre AS medio, c.nombre AS caja
             FROM movimientos m
             LEFT JOIN medios_pago mp ON mp.id_medio = m.id_medio
             LEFT JOIN cajas c        ON c.id_caja   = m.id_caja
             WHERE m.id_pedido = ? AND m.tipo IN ('cobro', 'devolucion')
             ORDER BY m.fecha ASC, m.id_movimiento ASC"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Saldo a favor que se usó para pagar este pedido. */
    public function saldoAplicado(int $id): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(-SUM(monto), 0) AS s FROM saldos_favor WHERE id_pedido = ? AND monto < 0"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return (float) $stmt->get_result()->fetch_assoc()['s'];
    }

    /** Lo que pagó el cliente: cobros − devoluciones (sin anulados), en la caja. */
    public function cobradoEnCaja(int $id): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN tipo = 'cobro' THEN monto ELSE -monto END), 0) AS c
             FROM movimientos
             WHERE id_pedido = ? AND tipo IN ('cobro', 'devolucion') AND anulado_at IS NULL"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return (float) $stmt->get_result()->fetch_assoc()['c'];
    }

    /** Total pagado: en caja + saldo a favor usado. */
    public function cobrado(int $id): float
    {
        return round($this->cobradoEnCaja($id) + $this->saldoAplicado($id), 2);
    }

    public static function estadoPago(float $total, float $cobrado): string
    {
        if ($cobrado <= 0.009) return 'sin_pagar';
        if ($cobrado < $total - 0.009) return 'senado';
        if ($cobrado > $total + 0.009) return 'de_mas';
        return 'pagado';
    }

    public function saldoCliente(?int $id_cliente): float
    {
        if (!$id_cliente) {
            return 0;
        }
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(monto), 0) AS s FROM saldos_favor WHERE id_cliente = ?");
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();

        return round((float) $stmt->get_result()->fetch_assoc()['s'], 2);
    }

    /**
     * Todo lo que necesita la pantalla del pedido, calculado.
     */
    public function resumen(int $id): ?array
    {
        $p = $this->pedido($id);
        if (!$p) {
            return null;
        }

        $items   = $this->items($id);
        $cobros  = $this->cobros($id);
        $cobrado = $this->cobrado($id);
        $total   = (float) $p['total'];

        // Ganancia
        $costo      = 0;
        $sinCosto   = 0;
        foreach ($items as $it) {
            if ($it['costo_unitario'] !== null) {
                $costo += (float) $it['costo_unitario'] * (int) $it['cantidad'];
            } else {
                $sinCosto += (int) $it['cantidad'];
            }
        }
        $comisiones = 0;
        foreach ($cobros as $c) {
            if ($c['anulado_at'] === null && $c['tipo'] === 'cobro') {
                $comisiones += (float) $c['comision'];
            }
        }
        $productos = (float) $p['subtotal'] - (float) $p['descuento'] + (float) $p['ajuste_monto'];
        $envio     = (float) $p['envio_cobrado'] - (float) $p['envio_costo'];

        return [
            'pedido'         => $p,
            'items'          => $items,
            'cobros'         => $cobros,
            'historial'      => $this->historial($id),
            'cobrado'        => $cobrado,
            'saldo_aplicado' => $this->saldoAplicado($id),
            'pendiente'      => round(max(0, $total - $cobrado), 2),
            'estado_pago'    => self::estadoPago($total, $cobrado),
            'saldo_cliente'  => $this->saldoCliente($p['id_cliente'] ? (int) $p['id_cliente'] : null),
            'transiciones'   => self::TRANSICIONES[$p['estado']] ?? [],
            'ganancia'       => [
                'productos'  => $productos,
                'costo'      => $costo,
                'sin_costo'  => $sinCosto,
                'comisiones' => $comisiones,
                'envio'      => $envio,
                'resultado'  => round($productos - $costo - $comisiones + $envio, 2),
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ESTADOS Y STOCK
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Cambia el estado moviendo el stock que corresponda.
     * Devuelve null si salió bien, o el texto del error.
     */
    public function cambiarEstado(int $id, string $nuevo): ?string
    {
        try {
            $this->db->begin_transaction();

            $p = $this->pedidoParaActualizar($id);
            if (!$p) {
                throw new RuntimeException('El pedido no existe.');
            }

            $error = $this->aplicarEstado($p, $nuevo);
            if ($error) {
                $this->db->rollback();
                return $error;
            }

            $this->db->commit();
            return null;

        } catch (Throwable $e) {
            $this->db->rollback();
            error_log('cambiarEstado: ' . $e->getMessage());
            return $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo cambiar el estado.';
        }
    }

    /** Lee el pedido bloqueándolo hasta el fin de la transacción (evita dos cambios a la vez). */
    private function pedidoParaActualizar(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM pedidos WHERE id_pedido = ? FOR UPDATE");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    /**
     * Aplica el cambio de estado dentro de una transacción ya abierta.
     * Devuelve null si salió bien, o el texto del error.
     */
    private function aplicarEstado(array $p, string $nuevo): ?string
    {
        $actual = $p['estado'];
        $id     = (int) $p['id_pedido'];

        if ($actual === $nuevo) {
            return null;
        }
        if (!isset(self::ESTADOS[$nuevo]) || $nuevo === 'cancelado') {
            return 'Estado no válido.';
        }
        if (!in_array($nuevo, self::TRANSICIONES[$actual] ?? [], true)) {
            return 'No se puede pasar de "' . self::etiqueta($actual) . '" a "' . self::etiqueta($nuevo) . '".';
        }

        $reservaba = in_array($actual, self::RESERVAN, true);
        $reserva   = in_array($nuevo, self::RESERVAN, true);
        $items     = array_filter($this->items($id), fn($it) => !empty($it['id_variante']));

        if ($nuevo === 'entregado') {
            foreach ($items as $it) {
                $cant = (int) $it['cantidad'];
                $idv  = (int) $it['id_variante'];

                if ($reservaba) {
                    $stmt = $this->db->prepare(
                        "UPDATE producto_variantes
                         SET stock = GREATEST(stock - ?, 0), stock_reservado = GREATEST(stock_reservado - ?, 0)
                         WHERE id_variante = ?"
                    );
                    $stmt->bind_param("iii", $cant, $cant, $idv);
                    $stmt->execute();
                } else {
                    $stmt = $this->db->prepare(
                        "UPDATE producto_variantes SET stock = stock - ?
                         WHERE id_variante = ? AND (stock - stock_reservado) >= ?"
                    );
                    $stmt->bind_param("iii", $cant, $idv, $cant);
                    $stmt->execute();
                    if ($stmt->affected_rows === 0) {
                        return 'No hay stock suficiente de ' . $this->nombreItem($it) . ' para entregar.';
                    }
                }
            }
        } elseif (!$reservaba && $reserva) {
            foreach ($items as $it) {
                $cant = (int) $it['cantidad'];
                $idv  = (int) $it['id_variante'];

                $stmt = $this->db->prepare(
                    "UPDATE producto_variantes SET stock_reservado = stock_reservado + ?
                     WHERE id_variante = ? AND (stock - stock_reservado) >= ?"
                );
                $stmt->bind_param("iii", $cant, $idv, $cant);
                $stmt->execute();
                if ($stmt->affected_rows === 0) {
                    return 'No hay stock suficiente de ' . $this->nombreItem($it) . '. Revisá el stock antes de confirmar.';
                }
            }
        } elseif ($reservaba && !$reserva) {
            $this->liberarStock($items);
        }

        $stmt = $this->db->prepare("UPDATE pedidos SET estado = ? WHERE id_pedido = ?");
        $stmt->bind_param("si", $nuevo, $id);
        $stmt->execute();

        $this->anotar($id, 'estado', self::etiqueta($actual) . ' → ' . self::etiqueta($nuevo));
        return null;
    }

    private function liberarStock(array $items): void
    {
        $stmt = $this->db->prepare(
            "UPDATE producto_variantes SET stock_reservado = GREATEST(stock_reservado - ?, 0) WHERE id_variante = ?"
        );
        foreach ($items as $it) {
            if (empty($it['id_variante'])) continue;
            $cant = (int) $it['cantidad'];
            $idv  = (int) $it['id_variante'];
            $stmt->bind_param("ii", $cant, $idv);
            $stmt->execute();
        }
    }

    private function nombreItem(array $it): string
    {
        $var = variante_texto($it['talle'] ?? null, $it['color'] ?? null);
        return '"' . $it['producto'] . ($var ? ' · ' . $var : '') . '"';
    }

    // ═══════════════════════════════════════════════════════════════════════
    // COBROS Y SALDO A FAVOR
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Registra un cobro en la caja del medio. Si el pedido estaba pendiente o contactado,
     * pasa a Confirmado. Devuelve ['ok' => bool, 'mensaje' => string].
     */
    public function registrarCobro(int $id, int $id_medio, float $monto, string $fecha, ?string $comprobante = null): array
    {
        $monto = round($monto, 2);
        if ($monto <= 0) {
            return ['ok' => false, 'mensaje' => 'El monto tiene que ser mayor a 0.'];
        }

        $medio = $this->medio($id_medio);
        if (!$medio) {
            return ['ok' => false, 'mensaje' => 'Elegí un medio de pago.'];
        }

        try {
            $this->db->begin_transaction();

            $p = $this->pedidoParaActualizar($id);
            if (!$p || $p['estado'] === 'cancelado') {
                $this->db->rollback();
                return ['ok' => false, 'mensaje' => 'No se pueden registrar cobros en un pedido cancelado.'];
            }

            $comision = round($monto * (float) $medio['comision_pct'] / 100, 2);

            $this->insertarMovimiento([
                'fecha'       => $fecha,
                'id_caja'     => (int) $medio['id_caja'],
                'tipo'        => 'cobro',
                'monto'       => $monto,
                'comision'    => $comision,
                'neto_caja'   => $monto - $comision,
                'id_medio'    => (int) $medio['id_medio'],
                'id_pedido'   => $id,
                'concepto'    => 'Cobro pedido #' . $id,
                'comprobante' => $comprobante,
            ]);

            $this->anotar($id, 'cobro', 'Cobro ' . self::pesos($monto) . ' por ' . $medio['nombre']
                . ($comision > 0 ? ' (comisión ' . self::pesos($comision) . ')' : ''));

            // Si pagó algo, el pedido va: se confirma solo
            $aviso = '';
            if (in_array($p['estado'], ['pendiente_contacto', 'contactado'], true)) {
                $error = $this->aplicarEstado($p, 'confirmado');
                if ($error) {
                    $aviso = ' Ojo: no se pudo pasar a Confirmado. ' . $error;
                }
            }

            $this->db->commit();
            return ['ok' => true, 'mensaje' => 'Cobro registrado.' . $aviso];

        } catch (Throwable $e) {
            $this->db->rollback();
            error_log('registrarCobro: ' . $e->getMessage());
            return ['ok' => false, 'mensaje' => 'No se pudo registrar el cobro.'];
        }
    }

    /** Anula un cobro o devolución del pedido (no se borra: queda tachado). */
    public function anularCobro(int $id, int $id_movimiento): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE movimientos SET anulado_at = NOW()
             WHERE id_movimiento = ? AND id_pedido = ? AND tipo IN ('cobro', 'devolucion') AND anulado_at IS NULL"
        );
        $stmt->bind_param("ii", $id_movimiento, $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $this->anotar($id, 'cobro', 'Se anuló un movimiento (#' . $id_movimiento . ')');
            return true;
        }
        return false;
    }

    /** Usa saldo a favor del cliente para pagar este pedido. No suma a la caja. */
    public function usarSaldo(int $id, float $monto): array
    {
        $monto = round($monto, 2);
        $p     = $this->pedido($id);

        if (!$p || !$p['id_cliente'] || $p['estado'] === 'cancelado') {
            return ['ok' => false, 'mensaje' => 'No se puede usar saldo en este pedido.'];
        }

        $saldo     = $this->saldoCliente((int) $p['id_cliente']);
        $pendiente = round((float) $p['total'] - $this->cobrado($id), 2);
        $monto     = min($monto, $saldo, $pendiente);

        if ($monto <= 0) {
            return ['ok' => false, 'mensaje' => 'No hay saldo a favor disponible o el pedido ya está pagado.'];
        }

        $id_cliente = (int) $p['id_cliente'];
        $negativo   = -$monto;
        $concepto   = 'Usado en pedido #' . $id;

        $stmt = $this->db->prepare("INSERT INTO saldos_favor (id_cliente, monto, concepto, id_pedido) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idsi", $id_cliente, $negativo, $concepto, $id);
        $stmt->execute();

        $this->anotar($id, 'cobro', 'Se usaron ' . self::pesos($monto) . ' de saldo a favor');

        return ['ok' => true, 'mensaje' => 'Saldo a favor aplicado: ' . self::pesos($monto) . '.'];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CANCELAR
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Cancela el pedido. Libera el stock y resuelve lo que había pagado:
     *   'devolver' → sale de la caja del medio elegido
     *   'saldo'    → queda como saldo a favor del cliente
     *   'nada'     → no se devuelve (ej: seña que se pierde)
     */
    public function cancelar(int $id, string $destino, ?int $id_medio_devolucion, ?string $motivo): ?string
    {
        try {
            $this->db->begin_transaction();

            $p = $this->pedidoParaActualizar($id);
            if (!$p) {
                throw new RuntimeException('El pedido no existe.');
            }
            if (in_array($p['estado'], ['entregado', 'cancelado'], true)) {
                throw new RuntimeException('Un pedido ' . strtolower(self::etiqueta($p['estado'])) . ' no se puede cancelar.');
            }

            // 1. Stock
            if (in_array($p['estado'], self::RESERVAN, true)) {
                $this->liberarStock($this->items($id));
            }

            // 2. Plata
            $enCaja    = $this->cobradoEnCaja($id);
            $conSaldo  = $this->saldoAplicado($id);
            $detalle   = 'Pedido cancelado' . ($motivo ? ': ' . $motivo : '');
            $idCliente = $p['id_cliente'] ? (int) $p['id_cliente'] : null;

            // El saldo a favor que había usado siempre vuelve a su saldo
            if ($conSaldo > 0 && $idCliente) {
                $this->sumarSaldo($idCliente, $conSaldo, 'Devuelto por cancelación del pedido #' . $id, $id);
            }

            if ($enCaja > 0) {
                if ($destino === 'devolver') {
                    $medio = $this->medio((int) $id_medio_devolucion);
                    if (!$medio) {
                        throw new RuntimeException('Elegí de dónde sale la devolución.');
                    }
                    $this->insertarMovimiento([
                        'fecha'       => date('Y-m-d H:i:s'),
                        'id_caja'     => (int) $medio['id_caja'],
                        'tipo'        => 'devolucion',
                        'monto'       => $enCaja,
                        'comision'    => 0,
                        'neto_caja'   => -$enCaja,
                        'id_medio'    => (int) $medio['id_medio'],
                        'id_pedido'   => $id,
                        'concepto'    => 'Devolución pedido #' . $id,
                        'comprobante' => null,
                    ]);
                    $detalle .= ' · Se devolvieron ' . self::pesos($enCaja) . ' por ' . $medio['nombre'];

                } elseif ($destino === 'saldo') {
                    if (!$idCliente) {
                        throw new RuntimeException('El pedido no tiene cliente: no se puede dejar saldo a favor.');
                    }
                    $this->sumarSaldo($idCliente, $enCaja, 'Saldo del pedido #' . $id . ' cancelado', $id);
                    $detalle .= ' · ' . self::pesos($enCaja) . ' quedaron como saldo a favor';

                } else {
                    $detalle .= ' · No se devolvieron los ' . self::pesos($enCaja) . ' pagados';
                }
            }

            // 3. Estado
            $stmt = $this->db->prepare("UPDATE pedidos SET estado = 'cancelado', cancelado_at = NOW() WHERE id_pedido = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $this->anotar($id, 'cancelacion', $detalle);

            $this->db->commit();
            return null;

        } catch (Throwable $e) {
            $this->db->rollback();
            error_log('cancelar: ' . $e->getMessage());
            return $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo cancelar el pedido.';
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CONDICIONES: DESCUENTO, MEDIO ACORDADO, ENVÍO, ENTREGA
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * $d: descuento, motivo_descuento, id_medio_acordado (null), envio_cobrado, envio_costo,
     *     entrega ('retiro' | 'envio' | null), seguimiento
     */
    public function actualizarCondiciones(int $id, array $d): ?string
    {
        $p = $this->pedido($id);
        if (!$p) {
            return 'El pedido no existe.';
        }
        if (in_array($p['estado'], ['entregado', 'cancelado'], true)) {
            return 'Un pedido ' . strtolower(self::etiqueta($p['estado'])) . ' ya no se puede modificar.';
        }

        $subtotal  = (float) $p['subtotal'];
        $descuento = min(max(0, round((float) $d['descuento'], 2)), $subtotal);
        $medio     = $d['id_medio_acordado'] ? $this->medio((int) $d['id_medio_acordado']) : null;
        $idMedio   = $medio ? (int) $medio['id_medio'] : null;
        $ajustePct = $medio ? (float) $medio['ajuste_pct'] : 0;
        $ajuste    = round(($subtotal - $descuento) * $ajustePct / 100, 2);
        $envioCob  = max(0, round((float) $d['envio_cobrado'], 2));
        $envioCost = max(0, round((float) $d['envio_costo'], 2));
        $entrega   = in_array($d['entrega'], ['retiro', 'envio'], true) ? $d['entrega'] : null;
        $motivo    = $d['motivo_descuento'] !== '' ? mb_substr($d['motivo_descuento'], 0, 150) : null;
        $seguim    = $d['seguimiento'] !== '' ? mb_substr($d['seguimiento'], 0, 100) : null;
        $total     = round($subtotal - $descuento + $ajuste + $envioCob, 2);

        $stmt = $this->db->prepare(
            "UPDATE pedidos SET
                descuento = ?, motivo_descuento = ?, id_medio_acordado = ?, ajuste_pct = ?, ajuste_monto = ?,
                envio_cobrado = ?, envio_costo = ?, entrega = ?, seguimiento = ?, total = ?
             WHERE id_pedido = ?"
        );
        $stmt->bind_param("dsidddsssdi",
            $descuento, $motivo, $idMedio, $ajustePct, $ajuste,
            $envioCob, $envioCost, $entrega, $seguim, $total, $id
        );
        $stmt->execute();

        // Historial: solo lo que cambió
        $cambios = [];
        if (abs($descuento - (float) $p['descuento']) > 0.009) {
            $cambios[] = 'Descuento ' . self::pesos($descuento) . ($motivo ? ' (' . $motivo . ')' : '');
        }
        if ($idMedio !== ($p['id_medio_acordado'] !== null ? (int) $p['id_medio_acordado'] : null)) {
            $cambios[] = 'Medio acordado: ' . ($medio ? $medio['nombre'] : 'sin definir')
                       . ($ajuste != 0 ? ' (' . ($ajuste > 0 ? 'recargo ' : 'descuento ') . self::pesos(abs($ajuste)) . ')' : '');
        }
        if (abs($envioCob - (float) $p['envio_cobrado']) > 0.009 || abs($envioCost - (float) $p['envio_costo']) > 0.009) {
            $cambios[] = 'Envío: cobra ' . self::pesos($envioCob) . ' / cuesta ' . self::pesos($envioCost);
        }
        if ($entrega !== $p['entrega']) {
            $cambios[] = 'Entrega: ' . ($entrega === 'retiro' ? 'retira en el local' : ($entrega === 'envio' ? 'envío' : 'sin definir'));
        }
        if ($seguim !== $p['seguimiento'] && $seguim) {
            $cambios[] = 'Seguimiento: ' . $seguim;
        }
        if ($cambios) {
            $this->anotar($id, 'condiciones', implode(' · ', $cambios) . ' · Total ' . self::pesos($total));
        }

        return null;
    }

    public function guardarNota(int $id, string $nota): void
    {
        $nota = trim($nota) !== '' ? mb_substr(trim($nota), 0, 2000) : null;
        $stmt = $this->db->prepare("UPDATE pedidos SET notas_internas = ? WHERE id_pedido = ?");
        $stmt->bind_param("si", $nota, $id);
        $stmt->execute();
    }

    /** Tocó "WhatsApp al cliente": si estaba pendiente, pasa a Contactado. */
    public function marcarContactado(int $id): void
    {
        $p = $this->pedido($id);
        if (!$p) {
            return;
        }

        // No repetir la anotación si ya se hizo en los últimos 10 minutos
        $stmt = $this->db->prepare(
            "SELECT 1 FROM pedido_historial
             WHERE id_pedido = ? AND tipo = 'contacto' AND fecha > NOW() - INTERVAL 10 MINUTE LIMIT 1"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return;
        }

        if ($p['estado'] === 'pendiente_contacto') {
            $stmt = $this->db->prepare("UPDATE pedidos SET estado = 'contactado' WHERE id_pedido = ? AND estado = 'pendiente_contacto'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $this->anotar($id, 'contacto', 'Se le escribió por WhatsApp → Contactado');
        } else {
            $this->anotar($id, 'contacto', 'Se le escribió por WhatsApp');
        }
    }

    /** Al crear el pedido desde la web: subtotal, origen e historial inicial. */
    public function inicializar(int $id, string $origen = 'web'): void
    {
        $stmt = $this->db->prepare("UPDATE pedidos SET subtotal = total, origen = ? WHERE id_pedido = ?");
        $stmt->bind_param("si", $origen, $id);
        $stmt->execute();

        $this->anotar($id, 'estado', 'Pedido recibido desde la tienda', 'cliente');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // AUXILIARES
    // ═══════════════════════════════════════════════════════════════════════

    public function anotar(int $id, string $tipo, string $detalle, ?string $usuario = null): void
    {
        $usuario = $usuario ?? ($_SESSION['admin']['nombre'] ?? 'sistema');
        $detalle = mb_substr($detalle, 0, 255);

        $stmt = $this->db->prepare("INSERT INTO pedido_historial (id_pedido, tipo, detalle, usuario) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $id, $tipo, $detalle, $usuario);
        $stmt->execute();
    }

    private function medio(int $id_medio): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT mp.* FROM medios_pago mp
             INNER JOIN cajas c ON c.id_caja = mp.id_caja
             WHERE mp.id_medio = ? AND mp.activo = 1 AND c.activo = 1
             LIMIT 1"
        );
        $stmt->bind_param("i", $id_medio);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    private function sumarSaldo(int $id_cliente, float $monto, string $concepto, int $id_pedido): void
    {
        $stmt = $this->db->prepare("INSERT INTO saldos_favor (id_cliente, monto, concepto, id_pedido) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idsi", $id_cliente, $monto, $concepto, $id_pedido);
        $stmt->execute();
    }

    /** Mismo formato que Movimiento::insertar, pero con esta conexión (para la transacción). */
    private function insertarMovimiento(array $d): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO movimientos (fecha, id_caja, tipo, monto, comision, neto_caja, id_medio, id_pedido, concepto, comprobante)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sisdddiiss",
            $d['fecha'], $d['id_caja'], $d['tipo'], $d['monto'], $d['comision'], $d['neto_caja'],
            $d['id_medio'], $d['id_pedido'], $d['concepto'], $d['comprobante']
        );
        $stmt->execute();
    }

    public static function pesos(float $n): string
    {
        return '$' . number_format($n, 0, ',', '.');
    }
}