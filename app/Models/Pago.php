<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * ====================================================================
 * TOP GOL - Modelo Pago
 * ====================================================================
 * Gestiona el historial y auditoría de cobros (adelantos, saldos y pagos
 * completos), métodos de pago (Yape, Efectivo, Niubiz), y la caja diaria.
 */
class Pago extends Model {
    protected string $tabla = 'pagos';

    public const METODOS_VALIDOS = ['yape', 'efectivo', 'niubiz'];
    public const TIPOS_VALIDOS = ['adelanto', 'saldo', 'completo'];
    public const ESTADOS_VALIDOS = ['pendiente', 'en_revision', 'verificado', 'rechazado'];

    /**
     * Registra un nuevo movimiento de pago
     *
     * @param array<string, mixed> $datos
     * @return int ID del pago registrado
     */
    public function crear(array $datos): int {
        $metodo = trim((string)($datos['metodo_pago'] ?? ''));
        if (!in_array($metodo, self::METODOS_VALIDOS, true)) {
            $metodo = 'efectivo';
        }

        $tipo = trim((string)($datos['tipo'] ?? 'saldo'));
        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            $tipo = 'saldo';
        }

        $estado = trim((string)($datos['estado'] ?? 'verificado'));
        if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
            $estado = 'verificado';
        }

        $monto = round((float)($datos['monto'] ?? 0.0), 2);
        $reservaId = (int)($datos['reserva_id'] ?? 0);
        $registradoPor = isset($datos['registrado_por']) && (int)$datos['registrado_por'] > 0
            ? (int)$datos['registrado_por']
            : null;
        $notas = isset($datos['notas']) ? trim((string)$datos['notas']) : null;
        $createdAt = !empty($datos['created_at']) ? (string)$datos['created_at'] : null;

        if ($createdAt !== null) {
            $sql = "INSERT INTO {$this->tabla}
                    (reserva_id, tipo, monto, metodo_pago, estado, registrado_por, notas, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $this->query($sql, [$reservaId, $tipo, $monto, $metodo, $estado, $registradoPor, $notas, $createdAt]);
        } else {
            $sql = "INSERT INTO {$this->tabla}
                    (reserva_id, tipo, monto, metodo_pago, estado, registrado_por, notas, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $this->query($sql, [$reservaId, $tipo, $monto, $metodo, $estado, $registradoPor, $notas]);
        }

        return $this->lastInsertId();
    }

    /**
     * Obtiene un pago por su ID con datos del usuario que lo registró
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT p.*,
                       u.nombre AS admin_nombre,
                       u.email AS admin_email
                FROM {$this->tabla} p
                LEFT JOIN usuarios u ON p.registrado_por = u.id
                WHERE p.id = ?
                LIMIT 1";
        return $this->selectOne($sql, [$id]);
    }

    /**
     * Obtiene el historial cronológico de pagos de una reserva
     *
     * @param int $reservaId
     * @return array<int, array<string, mixed>>
     */
    public function obtenerPorReserva(int $reservaId): array {
        $sql = "SELECT p.*,
                       COALESCE(u.nombre, 'Administración') AS admin_nombre,
                       COALESCE(u.nombre, 'Administración') AS operador_nombre
                FROM {$this->tabla} p
                LEFT JOIN usuarios u ON p.registrado_por = u.id
                WHERE p.reserva_id = ?
                ORDER BY p.created_at ASC, p.id ASC";
        return $this->select($sql, [$reservaId]);
    }

    /**
     * Calcula la suma total efectivamente cobrada y verificada de una reserva
     *
     * @param int $reservaId
     * @return float
     */
    public function obtenerTotalPagado(int $reservaId, bool $forUpdate = false): float {
        $lock = $forUpdate ? ' FOR UPDATE' : '';
        $sql = "SELECT COALESCE(SUM(monto), 0) AS total_pagado
                FROM {$this->tabla}
                WHERE reserva_id = ? AND estado = 'verificado'{$lock}";
        $res = $this->selectOne($sql, [$reservaId]);
        return round((float)($res['total_pagado'] ?? 0.0), 2);
    }

    /**
     * Retorna el saldo pendiente real de una reserva
     *
     * @param int $reservaId
     * @param float $totalReserva
     * @return float
     */
    public function obtenerSaldoPendiente(int $reservaId, ?float $totalReserva = null): float {
        if ($totalReserva === null) {
            $stmt = $this->db->prepare("SELECT total_pago FROM reservas WHERE id = ?");
            $stmt->execute([$reservaId]);
            $totalReserva = (float)($stmt->fetchColumn() ?: 0.0);
        }
        $pagado = $this->obtenerTotalPagado($reservaId);
        $saldo = $totalReserva - $pagado;
        return max(0.0, round($saldo, 2));
    }

    /**
     * Actualiza el estado de un registro de pago específico
     *
     * @param int $id
     * @param string $estado
     * @return bool
     */
    public function actualizarEstado(int $id, string $estado): bool {
        if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
            return false;
        }
        $sql = "UPDATE {$this->tabla} SET estado = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->query($sql, [$estado, $id]);
        return $stmt->rowCount() >= 0;
    }

    /**
     * Actualiza el estado de los pagos asociados a una reserva según tipo
     *
     * @param int $reservaId
     * @param string $tipo
     * @param string $estado
     * @param int|null $adminId
     * @return bool
     */
    public function actualizarEstadoPorReservaYTipo(int $reservaId, string $tipo, string $estado, ?int $adminId = null): bool {
        if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
            return false;
        }
        $sql = "UPDATE {$this->tabla} SET estado = ?, registrado_por = COALESCE(?, registrado_por), updated_at = NOW()
                WHERE reserva_id = ? AND tipo = ?";
        $stmt = $this->query($sql, [$estado, $adminId, $reservaId, $tipo]);

        // Si no existía registro de adelanto previo (ej: reservas pendientes históricas), crearlo de forma segura
        if ($stmt->rowCount() === 0 && $tipo === 'adelanto') {
            $stmtRes = $this->db->prepare("SELECT adelanto_monto, metodo_pago, origen, created_at FROM reservas WHERE id = ?");
            $stmtRes->execute([$reservaId]);
            $res = $stmtRes->fetch(PDO::FETCH_ASSOC);
            if ($res && (float)($res['adelanto_monto'] ?? 0.0) > 0.0) {
                $metodo = in_array($res['metodo_pago'] ?? '', self::METODOS_VALIDOS, true) ? $res['metodo_pago'] : 'yape';
                $this->crear([
                    'reserva_id'     => $reservaId,
                    'tipo'           => 'adelanto',
                    'monto'          => (float)$res['adelanto_monto'],
                    'metodo_pago'    => $metodo,
                    'estado'         => $estado,
                    'registrado_por' => $adminId,
                    'notas'          => 'Adelanto sincronizado (' . ($res['origen'] ?? 'web') . ')',
                    'created_at'     => $res['created_at'] ?? null,
                ]);
            }
        }

        return true;
    }

    /**
     * Actualiza el estado de los pagos de todo un grupo de evento
     *
     * @param string $grupo
     * @param string $estado
     * @param int|null $adminId
     * @return bool
     */
    public function actualizarEstadoPorGrupo(string $grupo, string $estado, ?int $adminId = null): bool {
        if (!in_array($estado, self::ESTADOS_VALIDOS, true)) {
            return false;
        }
        $sql = "UPDATE {$this->tabla} p
                INNER JOIN reservas r ON p.reserva_id = r.id
                SET p.estado = ?, p.registrado_por = COALESCE(?, p.registrado_por), p.updated_at = NOW()
                WHERE r.evento_grupo = ?";
        $stmt = $this->query($sql, [$estado, $adminId, $grupo]);
        return $stmt->rowCount() >= 0;
    }

    /**
     * Registra el cobro de saldo para una reserva con todas las validaciones de seguridad:
     * - No importes negativos o cero
     * - No cobros superiores al saldo pendiente
     * - Detección y bloqueo de doble clic (idempotencia en ventana de 5s)
     * - Transacción MySQL
     * - Auditoría del administrador
     *
     * @param int $reservaId
     * @param float $monto
     * @param string $metodo 'efectivo' o 'niubiz'
     * @param int $adminId
     * @param string $notas
     * @return array{ok: bool, error?: string, pago_id?: int, saldo_restante?: float, total_pagado?: float}
     */
    public function registrarCobroSaldo(int $reservaId, float $monto, string $metodo, int $adminId, string $notas = ''): array {
        $monto = round($monto, 2);
        if ($monto <= 0.0) {
            return ['ok' => false, 'error' => 'El monto a cobrar debe ser mayor a S/ 0.00.'];
        }

        if (!in_array($metodo, ['efectivo', 'yape', 'niubiz'], true)) {
            return ['ok' => false, 'error' => 'Método de pago no válido. Selecciona Efectivo, Yape o Niubiz (POS).'];
        }

        // Obtener datos actuales de la reserva
        $sqlReserva = "SELECT id, total_pago, estado, pago_estado, evento_grupo FROM reservas WHERE id = ? FOR UPDATE";

        $this->beginTransaction();
        try {
            $stmt = $this->query($sqlReserva, [$reservaId]);
            $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reserva) {
                $this->rollBack();
                return ['ok' => false, 'error' => 'La reserva indicada no existe.'];
            }

            if ($reserva['estado'] === 'cancelada') {
                $this->rollBack();
                return ['ok' => false, 'error' => 'No se puede registrar pagos en una reserva cancelada.'];
            }

            // Prevención de doble clic con Current Read (FOR UPDATE para evitar lecturas obsoletas del snapshot MVCC)
            $sqlDobleClic = "SELECT id FROM {$this->tabla}
                             WHERE reserva_id = ?
                               AND monto = ?
                               AND metodo_pago = ?
                               AND registrado_por = ?
                               AND created_at >= (NOW() - INTERVAL 5 SECOND)
                             LIMIT 1 FOR UPDATE";
            $stmtDoble = $this->query($sqlDobleClic, [$reservaId, $monto, $metodo, $adminId]);
            if ($stmtDoble->fetch()) {
                $this->rollBack();
                return ['ok' => false, 'error' => 'Este cobro ya fue procesado hace un instante (detección de doble clic).'];
            }

            // Calcular saldo pendiente actual con bloqueo FOR UPDATE para garantizar lectura fresca y atómica
            $totalReserva = (float)$reserva['total_pago'];
            $pagadoActual = $this->obtenerTotalPagado($reservaId, true);
            $saldoActual = max(0.0, round($totalReserva - $pagadoActual, 2));

            if ($saldoActual <= 0.0) {
                $this->rollBack();
                return ['ok' => false, 'error' => 'Esta reserva ya se encuentra completamente pagada (saldo S/ 0.00).'];
            }

            if ($monto > $saldoActual) {
                $this->rollBack();
                return [
                    'ok' => false,
                    'error' => "El monto ingresado (S/ " . number_format($monto, 2) . ") supera el saldo pendiente (S/ " . number_format($saldoActual, 2) . ")."
                ];
            }

            // Determinar tipo de pago: 'saldo' o 'completo' (si es el primer y único pago)
            $tipo = ($pagadoActual > 0.0) ? 'saldo' : (($monto >= $totalReserva) ? 'completo' : 'saldo');

            // Insertar pago
            $sqlInsert = "INSERT INTO {$this->tabla}
                          (reserva_id, tipo, monto, metodo_pago, estado, registrado_por, notas, created_at, updated_at)
                          VALUES (?, ?, ?, ?, 'verificado', ?, ?, NOW(), NOW())";
            $this->query($sqlInsert, [$reservaId, $tipo, $monto, $metodo, $adminId, trim($notas) ?: 'Cobro de saldo en mostrador']);
            $pagoId = $this->lastInsertId();

            // Recalcular saldo restante
            $nuevoTotalPagado = round($pagadoActual + $monto, 2);
            $nuevoSaldo = max(0.0, round($totalReserva - $nuevoTotalPagado, 2));

            // Si el saldo quedó en 0 y la reserva estaba pendiente, confirmarla automáticamente
            if ($nuevoSaldo <= 0.0 && $reserva['estado'] === 'pendiente') {
                $this->query("UPDATE reservas SET estado = 'confirmada', pago_estado = 'verificado', updated_at = NOW() WHERE id = ?", [$reservaId]);
            } elseif ($nuevoTotalPagado > 0.0 && $reserva['pago_estado'] !== 'verificado') {
                // Si ya tiene pagos verificados, asegurar que el estado general de pago sea verificado
                $this->query("UPDATE reservas SET pago_estado = 'verificado', updated_at = NOW() WHERE id = ?", [$reservaId]);
            }

            $this->commit();

            return [
                'ok'             => true,
                'pago_id'        => $pagoId,
                'monto'          => $monto,
                'metodo'         => $metodo,
                'total_pagado'   => $nuevoTotalPagado,
                'saldo_restante' => $nuevoSaldo,
                'total_reserva'  => $totalReserva
            ];
        } catch (\Throwable $e) {
            $this->rollBack();
            return ['ok' => false, 'error' => 'Error en base de datos al procesar el cobro: ' . $e->getMessage()];
        }
    }

    /**
     * Registra el cobro de saldo para un grupo completo de evento (múltiples canchas)
     * distribuyendo el saldo proporcionalmente o liquidando cada cancha sin alterar otras reservas.
     *
     * @param string $grupo
     * @param float $montoTotal
     * @param string $metodo
     * @param int $adminId
     * @param string $notas
     * @return array{ok: bool, error?: string, reservas_afectadas?: int, saldo_restante_grupo?: float}
     */
    public function registrarCobroSaldoGrupo(string $grupo, float $montoTotal, string $metodo, int $adminId, string $notas = ''): array {
        $montoTotal = round($montoTotal, 2);
        if ($montoTotal <= 0.0) {
            return ['ok' => false, 'error' => 'El monto del grupo debe ser mayor a S/ 0.00.'];
        }

        if (!in_array($metodo, ['efectivo', 'yape', 'niubiz'], true)) {
            return ['ok' => false, 'error' => 'Método de pago no válido. Selecciona Efectivo, Yape o Niubiz.'];
        }

        $sql = "SELECT id, total_pago, estado FROM reservas WHERE evento_grupo = ? AND estado != 'cancelada' ORDER BY id ASC FOR UPDATE";

        $this->beginTransaction();
        try {
            $stmt = $this->query($sql, [$grupo]);
            $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($reservas)) {
                $this->rollBack();
                return ['ok' => false, 'error' => 'No se encontraron reservas activas para este evento grupal.'];
            }

            // Detección de doble clic para cobro de eventos grupales (Current Read)
            $sqlDobleGrupo = "SELECT p.id FROM {$this->tabla} p
                              INNER JOIN reservas r ON p.reserva_id = r.id
                              WHERE r.evento_grupo = ?
                                AND p.registrado_por = ?
                                AND p.created_at >= (NOW() - INTERVAL 5 SECOND)
                              LIMIT 1 FOR UPDATE";
            $stmtDobleG = $this->query($sqlDobleGrupo, [$grupo, $adminId]);
            if ($stmtDobleG->fetch()) {
                $this->rollBack();
                return ['ok' => false, 'error' => 'Este cobro de grupo ya fue procesado hace un instante (detección de doble clic).'];
            }

            // Calcular saldo pendiente de cada cancha del grupo con Current Read (FOR UPDATE)
            $saldos = [];
            $saldoTotalGrupo = 0.0;
            foreach ($reservas as $r) {
                $rId = (int)$r['id'];
                $pagado = $this->obtenerTotalPagado($rId, true);
                $s = max(0.0, round((float)$r['total_pago'] - $pagado, 2));
                $saldos[$rId] = [
                    'reserva' => $r,
                    'pagado'  => $pagado,
                    'saldo'   => $s,
                ];
                $saldoTotalGrupo += $s;
            }
            $saldoTotalGrupo = round($saldoTotalGrupo, 2);

            if ($saldoTotalGrupo <= 0.0) {
                $this->rollBack();
                return ['ok' => false, 'error' => 'Todas las canchas de este evento ya están pagadas (saldo S/ 0.00).'];
            }

            if ($montoTotal > $saldoTotalGrupo) {
                $this->rollBack();
                return [
                    'ok' => false,
                    'error' => "El monto ingresado (S/ " . number_format($montoTotal, 2) . ") supera el saldo pendiente del evento (S/ " . number_format($saldoTotalGrupo, 2) . ")."
                ];
            }

            // Distribuir el monto entre las reservas con saldo pendiente
            $restantePorDistribuir = $montoTotal;
            $afectadas = 0;

            foreach ($saldos as $rId => $item) {
                if ($restantePorDistribuir <= 0.0) {
                    break;
                }
                $saldoCancha = $item['saldo'];
                if ($saldoCancha <= 0.0) {
                    continue;
                }

                // Cuánto abonar a esta cancha
                $abono = min($restantePorDistribuir, $saldoCancha);
                $abono = round($abono, 2);

                $tipo = ($item['pagado'] > 0.0) ? 'saldo' : (($abono >= (float)$item['reserva']['total_pago']) ? 'completo' : 'saldo');

                $sqlInsert = "INSERT INTO {$this->tabla}
                              (reserva_id, tipo, monto, metodo_pago, estado, registrado_por, notas, created_at, updated_at)
                              VALUES (?, ?, ?, ?, 'verificado', ?, ?, NOW(), NOW())";
                $this->query($sqlInsert, [
                    $rId,
                    $tipo,
                    $abono,
                    $metodo,
                    $adminId,
                    trim($notas) ?: "Cobro saldo evento {$grupo}"
                ]);

                // Actualizar reserva si queda saldada
                if (round($saldoCancha - $abono, 2) <= 0.0) {
                    $this->query("UPDATE reservas SET estado = 'confirmada', pago_estado = 'verificado', updated_at = NOW() WHERE id = ?", [$rId]);
                } else {
                    $this->query("UPDATE reservas SET pago_estado = 'verificado', updated_at = NOW() WHERE id = ?", [$rId]);
                }

                $restantePorDistribuir = round($restantePorDistribuir - $abono, 2);
                $afectadas++;
            }

            $this->commit();

            return [
                'ok'                   => true,
                'reservas_afectadas'   => $afectadas,
                'monto_cobrado'        => $montoTotal,
                'saldo_restante_grupo' => max(0.0, round($saldoTotalGrupo - $montoTotal, 2))
            ];
        } catch (\Throwable $e) {
            $this->rollBack();
            return ['ok' => false, 'error' => 'Error al registrar cobro de grupo: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene el resumen de Caja Diaria básica para una fecha (ingresos REALES efectivamente cobrados)
     *
     * @param string $fecha Formato YYYY-MM-DD
     * @return array<string, mixed>
     */
    /**
     * Obtiene el resumen de Caja Diaria consolidando ingresos de canchas/eventos y productos por separado
     *
     * @param string $fecha Formato YYYY-MM-DD
     * @param bool $incluirDemo
     * @return array<string, mixed>
     */
    public function obtenerResumenCaja(string $fecha, bool $incluirDemo = false): array {
        $demoPagos = $incluirDemo ? '' : ' AND es_demo = 0';
        $demoVentas = $incluirDemo ? '' : ' AND es_demo = 0';

        // 1. Ingresos por reservas y eventos (tabla pagos)
        $sqlCanchas = "SELECT
                            COALESCE(SUM(monto), 0) AS total_ingresos,
                            COALESCE(SUM(CASE WHEN metodo_pago = 'efectivo' THEN monto ELSE 0 END), 0) AS total_efectivo,
                            COALESCE(SUM(CASE WHEN metodo_pago = 'niubiz' THEN monto ELSE 0 END), 0) AS total_niubiz,
                            COALESCE(SUM(CASE WHEN metodo_pago = 'yape' THEN monto ELSE 0 END), 0) AS total_yape,
                            COALESCE(SUM(CASE WHEN tipo = 'adelanto' THEN monto ELSE 0 END), 0) AS total_adelantos,
                            COALESCE(SUM(CASE WHEN tipo = 'saldo' THEN monto ELSE 0 END), 0) AS total_saldos,
                            COALESCE(SUM(CASE WHEN tipo = 'completo' THEN monto ELSE 0 END), 0) AS total_completos,
                            COUNT(*) AS total_transacciones
                        FROM {$this->tabla}
                        WHERE DATE(created_at) = ? AND estado = 'verificado'{$demoPagos}";

        $resCanchas = $this->selectOne($sqlCanchas, [$fecha]);

        $canchasTotal = round((float)($resCanchas['total_ingresos'] ?? 0.0), 2);
        $canchasEfe   = round((float)($resCanchas['total_efectivo'] ?? 0.0), 2);
        $canchasPos   = round((float)($resCanchas['total_niubiz'] ?? 0.0), 2);
        $canchasYap   = round((float)($resCanchas['total_yape'] ?? 0.0), 2);
        $canchasAde   = round((float)($resCanchas['total_adelantos'] ?? 0.0), 2);
        $canchasSal   = round((float)($resCanchas['total_saldos'] ?? 0.0), 2);
        $canchasCom   = round((float)($resCanchas['total_completos'] ?? 0.0), 2);
        $canchasTx    = (int)($resCanchas['total_transacciones'] ?? 0);

        // 2. Ingresos por ventas de productos del bar (tabla ventas)
        $sqlProds = "SELECT
                        COALESCE(SUM(total), 0) AS total_ventas,
                        COALESCE(SUM(CASE WHEN metodo_pago = 'efectivo' THEN total ELSE 0 END), 0) AS total_efectivo,
                        COALESCE(SUM(CASE WHEN metodo_pago = 'niubiz' THEN total ELSE 0 END), 0) AS total_niubiz,
                        COUNT(*) AS total_transacciones,
                        COALESCE(SUM(CASE WHEN es_demo = 1 THEN total ELSE 0 END), 0) AS total_demo
                    FROM ventas
                    WHERE DATE(created_at) = ? AND estado = 'completada'{$demoVentas}";

        $resProds = $this->selectOne($sqlProds, [$fecha]);

        $prodsTotal = round((float)($resProds['total_ventas'] ?? 0.0), 2);
        $prodsEfe   = round((float)($resProds['total_efectivo'] ?? 0.0), 2);
        $prodsPos   = round((float)($resProds['total_niubiz'] ?? 0.0), 2);
        $prodsTx    = (int)($resProds['total_transacciones'] ?? 0);
        $totalDemo  = round((float)($resProds['total_demo'] ?? 0.0), 2);

        // 3. Totales consolidados generales
        $totalGeneral = round($canchasTotal + $prodsTotal, 2);
        $totalEfeGen  = round($canchasEfe + $prodsEfe, 2);
        $totalPosGen  = round($canchasPos + $prodsPos, 2);
        $totalYapGen  = $canchasYap;
        $totalTxGen   = $canchasTx + $prodsTx;

        return [
            'fecha'               => $fecha,
            'incluir_demo'        => $incluirDemo,
            // Totales generales
            'total_ingresos'      => $totalGeneral,
            'total_caja'          => $totalGeneral,
            'total_efectivo'      => $totalEfeGen,
            'total_niubiz'        => $totalPosGen,
            'total_yape'          => $totalYapGen,
            'total_transacciones' => $totalTxGen,

            // Desglose: Canchas y Eventos
            'canchas_total'       => $canchasTotal,
            'canchas_efectivo'    => $canchasEfe,
            'canchas_niubiz'      => $canchasPos,
            'canchas_yape'        => $canchasYap,
            'total_adelantos'     => $canchasAde,
            'total_saldos'        => $canchasSal,
            'total_completos'     => $canchasCom,
            'canchas_tx'          => $canchasTx,

            // Desglose: Productos (Bar / Kiosco)
            'total_productos'     => $prodsTotal,
            'productos_total'     => $prodsTotal,
            'productos_efectivo'  => $prodsEfe,
            'productos_niubiz'    => $prodsPos,
            'productos_tx'        => $prodsTx,
            'total_demo'          => $totalDemo,
        ];
    }

    /**
     * Obtiene el listado detallado de movimientos de caja verificados de una fecha
     *
     * @param string $fecha Formato YYYY-MM-DD
     * @param bool $incluirDemo Si se incluyen o aíslan pagos marcados como demostración
     * @return array<int, array<string, mixed>>
     */
    public function obtenerMovimientosCaja(string $fecha, bool $incluirDemo = false): array {
        $demoFilter = $incluirDemo ? '' : ' AND p.es_demo = 0';
        $sql = "SELECT p.*,
                       r.fecha AS reserva_fecha,
                       r.hora_inicio AS reserva_hora_inicio,
                       r.hora_fin AS reserva_hora_fin,
                       r.total_pago AS reserva_total,
                       r.origen AS reserva_origen,
                       r.evento_grupo,
                       COALESCE(r.cliente_nombre, u_cli.nombre) AS cliente_nombre,
                       u_cli.email AS cliente_email,
                       c.nombre AS cancha_nombre,
                       u_adm.nombre AS admin_nombre
                FROM {$this->tabla} p
                INNER JOIN reservas r ON p.reserva_id = r.id
                INNER JOIN canchas c ON r.cancha_id = c.id
                INNER JOIN usuarios u_cli ON r.usuario_id = u_cli.id
                LEFT JOIN usuarios u_adm ON p.registrado_por = u_adm.id
                WHERE DATE(p.created_at) = ? AND p.estado = 'verificado'{$demoFilter}
                ORDER BY p.created_at DESC, p.id DESC";

        return $this->select($sql, [$fecha]);
    }

    /**
     * Obtiene el listado completo de historial de pagos con filtros
     *
     * @param array<string, mixed> $filtros
     * @param int $limite
     * @return array<int, array<string, mixed>>
     */
    public function obtenerHistorial(array $filtros = [], int $limite = 100): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($filtros['metodo'])) {
            $where[] = "p.metodo_pago = ?";
            $params[] = $filtros['metodo'];
        }
        if (!empty($filtros['tipo'])) {
            $where[] = "p.tipo = ?";
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['estado'])) {
            $where[] = "p.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['fecha'])) {
            $where[] = "DATE(p.created_at) = ?";
            $params[] = $filtros['fecha'];
        }
        if (!empty($filtros['q'])) {
            $where[] = "(p.reserva_id = ? OR r.cliente_nombre LIKE ? OR u_cli.nombre LIKE ? OR c.nombre LIKE ?)";
            $q = trim((string)$filtros['q']);
            $params[] = is_numeric($q) ? (int)$q : 0;
            $params[] = "%{$q}%";
            $params[] = "%{$q}%";
            $params[] = "%{$q}%";
        }
        if (isset($filtros['demo'])) {
            if (!$filtros['demo']) {
                $where[] = "p.es_demo = 0";
            }
        } elseif (isset($filtros['incluir_demo'])) {
            if (!$filtros['incluir_demo']) {
                $where[] = "p.es_demo = 0";
            }
        } else {
            $where[] = "p.es_demo = 0";
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT p.*,
                       r.fecha AS reserva_fecha,
                       r.hora_inicio AS reserva_hora_inicio,
                       r.hora_fin AS reserva_hora_fin,
                       r.total_pago AS reserva_total,
                       r.origen AS reserva_origen,
                       r.evento_grupo,
                       COALESCE(r.cliente_nombre, u_cli.nombre) AS cliente_nombre,
                       c.nombre AS cancha_nombre,
                       u_adm.nombre AS admin_nombre
                FROM {$this->tabla} p
                INNER JOIN reservas r ON p.reserva_id = r.id
                INNER JOIN canchas c ON r.cancha_id = c.id
                INNER JOIN usuarios u_cli ON r.usuario_id = u_cli.id
                LEFT JOIN usuarios u_adm ON p.registrado_por = u_adm.id
                WHERE {$whereClause}
                ORDER BY p.created_at DESC, p.id DESC
                LIMIT {$limite}";

        return $this->select($sql, $params);
    }

    /**
     * Obtiene el listado de ventas de productos completadas de una fecha para el arqueo de caja
     *
     * @param string $fecha
     * @param bool $incluirDemo
     * @return array<int, array<string, mixed>>
     */
    public function obtenerVentasCaja(string $fecha, bool $incluirDemo = false): array {
        $whereDemo = $incluirDemo ? "" : " AND v.es_demo = 0";
        $sql = "SELECT v.*, u.nombre AS admin_nombre,
                       COUNT(vd.id) AS total_items,
                       COALESCE(SUM(vd.cantidad), 0) AS total_unidades
                FROM ventas v
                LEFT JOIN usuarios u ON v.registrado_por = u.id
                LEFT JOIN venta_detalles vd ON v.id = vd.venta_id
                WHERE DATE(v.created_at) = ? AND v.estado = 'completada'{$whereDemo}
                GROUP BY v.id
                ORDER BY v.created_at DESC, v.id DESC";
        return $this->select($sql, [$fecha]);
    }

    /**
     * Reporte consolidado de ingresos por rango de fechas para la Fase 2F
     *
     * @param string $desde Formato YYYY-MM-DD
     * @param string $hasta Formato YYYY-MM-DD
     * @param bool $incluirDemo Si se computan o aíslan ventas de prueba
     * @return array<string, mixed>
     */
    public function obtenerReporteIngresos(string $desde, string $hasta, bool $incluirDemo = false): array {
        $demoPagos = $incluirDemo ? "" : " AND p.es_demo = 0";
        $demoVentas = $incluirDemo ? "" : " AND v.es_demo = 0";

        // 1. Ingresos por alquileres y eventos (pagos verificados)
        $sqlCanchas = "SELECT
                            COALESCE(SUM(p.monto), 0) AS total_ingresos,
                            COALESCE(SUM(CASE WHEN p.metodo_pago = 'efectivo' THEN p.monto ELSE 0 END), 0) AS total_efectivo,
                            COALESCE(SUM(CASE WHEN p.metodo_pago = 'niubiz' THEN p.monto ELSE 0 END), 0) AS total_niubiz,
                            COALESCE(SUM(CASE WHEN p.metodo_pago = 'yape' THEN p.monto ELSE 0 END), 0) AS total_yape,
                            COALESCE(SUM(CASE WHEN p.tipo = 'adelanto' THEN p.monto ELSE 0 END), 0) AS adelantos_verificados,
                            COALESCE(SUM(CASE WHEN p.tipo = 'saldo' THEN p.monto ELSE 0 END), 0) AS saldos_cobrados,
                            COALESCE(SUM(CASE WHEN p.tipo = 'completo' THEN p.monto ELSE 0 END), 0) AS completos_cobrados,
                            COUNT(p.id) AS total_transacciones
                       FROM {$this->tabla} p
                       WHERE DATE(p.created_at) BETWEEN ? AND ? AND p.estado = 'verificado'{$demoPagos}";

        $resC = $this->selectOne($sqlCanchas, [$desde, $hasta]);
        $cTotal = round((float)($resC['total_ingresos'] ?? 0.0), 2);
        $cEfe   = round((float)($resC['total_efectivo'] ?? 0.0), 2);
        $cPos   = round((float)($resC['total_niubiz'] ?? 0.0), 2);
        $cYap   = round((float)($resC['total_yape'] ?? 0.0), 2);
        $cAde   = round((float)($resC['adelantos_verificados'] ?? 0.0), 2);
        $cSal   = round((float)($resC['saldos_cobrados'] ?? 0.0), 2);
        $cCom   = round((float)($resC['completos_cobrados'] ?? 0.0), 2);
        $cTx    = (int)($resC['total_transacciones'] ?? 0);

        // 2. Ingresos por ventas de productos del bar / mostrador
        $sqlProds = "SELECT
                        COALESCE(SUM(v.total), 0) AS total_ventas,
                        COALESCE(SUM(CASE WHEN v.metodo_pago = 'efectivo' THEN v.total ELSE 0 END), 0) AS total_efectivo,
                        COALESCE(SUM(CASE WHEN v.metodo_pago = 'niubiz' THEN v.total ELSE 0 END), 0) AS total_niubiz,
                        COUNT(v.id) AS total_transacciones
                     FROM ventas v
                     WHERE DATE(v.created_at) BETWEEN ? AND ? AND v.estado = 'completada'{$demoVentas}";

        $resP = $this->selectOne($sqlProds, [$desde, $hasta]);
        $pTotal = round((float)($resP['total_ventas'] ?? 0.0), 2);
        $pEfe   = round((float)($resP['total_efectivo'] ?? 0.0), 2);
        $pPos   = round((float)($resP['total_niubiz'] ?? 0.0), 2);
        $pTx    = (int)($resP['total_transacciones'] ?? 0);

        // 3. Ventas demo aisladas en el rango (para trazabilidad y advertencia transparente)
        $sqlDemo = "SELECT COALESCE(SUM(total), 0) AS demo_total, COUNT(*) AS demo_count FROM ventas WHERE DATE(created_at) BETWEEN ? AND ? AND es_demo = 1";
        $resDemo = $this->selectOne($sqlDemo, [$desde, $hasta]);
        $demoMonto = round((float)($resDemo['demo_total'] ?? 0.0), 2);
        $demoCount = (int)($resDemo['demo_count'] ?? 0);

        // 4. Saldos pendientes de cobro en reservas activas comprendidas en el rango de fecha de reserva
        $demoReservas = $incluirDemo ? "" : " AND r.es_demo = 0";
        $sqlSaldos = "SELECT COALESCE(SUM(GREATEST(0, r.total_pago - COALESCE(p_sub.pagado, 0))), 0) AS total_saldo_pendiente
                      FROM reservas r
                      LEFT JOIN (
                          SELECT reserva_id, SUM(monto) AS pagado FROM pagos WHERE estado = 'verificado' GROUP BY reserva_id
                      ) p_sub ON r.id = p_sub.reserva_id
                      WHERE r.fecha BETWEEN ? AND ? AND r.estado IN ('confirmada', 'pendiente'){$demoReservas}";
        $resSaldos = $this->selectOne($sqlSaldos, [$desde, $hasta]);
        $saldosPendientes = round((float)($resSaldos['total_saldo_pendiente'] ?? 0.0), 2);

        // 5. Desglose cronológico diario unificado
        $sqlDiasCanchas = "SELECT DATE(p.created_at) AS dia,
                                  COALESCE(SUM(p.monto), 0) AS canchas_monto,
                                  COALESCE(SUM(CASE WHEN p.metodo_pago = 'efectivo' THEN p.monto ELSE 0 END), 0) AS canchas_efe,
                                  COALESCE(SUM(CASE WHEN p.metodo_pago = 'niubiz' THEN p.monto ELSE 0 END), 0) AS canchas_pos,
                                  COALESCE(SUM(CASE WHEN p.metodo_pago = 'yape' THEN p.monto ELSE 0 END), 0) AS canchas_yap,
                                  COUNT(p.id) AS canchas_tx
                           FROM {$this->tabla} p
                           WHERE DATE(p.created_at) BETWEEN ? AND ? AND p.estado = 'verificado'{$demoPagos}
                           GROUP BY DATE(p.created_at)";
        $diasCanchas = $this->select($sqlDiasCanchas, [$desde, $hasta]);

        $sqlDiasProds = "SELECT DATE(v.created_at) AS dia,
                                COALESCE(SUM(v.total), 0) AS prods_monto,
                                COALESCE(SUM(CASE WHEN v.metodo_pago = 'efectivo' THEN v.total ELSE 0 END), 0) AS prods_efe,
                                COALESCE(SUM(CASE WHEN v.metodo_pago = 'niubiz' THEN v.total ELSE 0 END), 0) AS prods_pos,
                                COUNT(v.id) AS prods_tx
                         FROM ventas v
                         WHERE DATE(v.created_at) BETWEEN ? AND ? AND v.estado = 'completada'{$demoVentas}
                         GROUP BY DATE(v.created_at)";
        $diasProds = $this->select($sqlDiasProds, [$desde, $hasta]);

        $diasMap = [];
        foreach ($diasCanchas as $dc) {
            $dia = (string)$dc['dia'];
            $diasMap[$dia] = [
                'dia'             => $dia,
                'canchas_monto'   => (float)$dc['canchas_monto'],
                'canchas_efe'     => (float)$dc['canchas_efe'],
                'canchas_pos'     => (float)$dc['canchas_pos'],
                'canchas_yap'     => (float)$dc['canchas_yap'],
                'prods_monto'     => 0.0,
                'prods_efe'       => 0.0,
                'prods_pos'       => 0.0,
                'total_ingresos'  => (float)$dc['canchas_monto'],
                'total_efectivo'  => (float)$dc['canchas_efe'],
                'total_niubiz'    => (float)$dc['canchas_pos'],
                'total_yape'      => (float)$dc['canchas_yap'],
                'transacciones'   => (int)$dc['canchas_tx'],
            ];
        }
        foreach ($diasProds as $dp) {
            $dia = (string)$dp['dia'];
            if (!isset($diasMap[$dia])) {
                $diasMap[$dia] = [
                    'dia'            => $dia,
                    'canchas_monto'  => 0.0,
                    'canchas_efe'    => 0.0,
                    'canchas_pos'    => 0.0,
                    'canchas_yap'    => 0.0,
                    'prods_monto'    => 0.0,
                    'prods_efe'      => 0.0,
                    'prods_pos'      => 0.0,
                    'total_ingresos' => 0.0,
                    'total_efectivo' => 0.0,
                    'total_niubiz'   => 0.0,
                    'total_yape'     => 0.0,
                    'transacciones'  => 0,
                ];
            }
            $diasMap[$dia]['prods_monto']    += (float)$dp['prods_monto'];
            $diasMap[$dia]['prods_efe']      += (float)$dp['prods_efe'];
            $diasMap[$dia]['prods_pos']      += (float)$dp['prods_pos'];
            $diasMap[$dia]['total_ingresos'] += (float)$dp['prods_monto'];
            $diasMap[$dia]['total_efectivo'] += (float)$dp['prods_efe'];
            $diasMap[$dia]['total_niubiz']   += (float)$dp['prods_pos'];
            $diasMap[$dia]['transacciones']  += (int)$dp['prods_tx'];
        }
        krsort($diasMap);

        // 6. Transacciones detalladas para visualización / exportación
        $sqlMovPagos = "SELECT p.id,
                               'cancha' AS rubro,
                               p.created_at,
                               p.tipo,
                               p.metodo_pago,
                               p.monto,
                               r.id AS referencia_id,
                               CONCAT('Reserva #', r.id, ' (', c.nombre, ')') AS concepto,
                               COALESCE(r.cliente_nombre, u_cli.nombre) AS cliente_nombre,
                               u_adm.nombre AS admin_nombre,
                               p.es_demo
                        FROM {$this->tabla} p
                        INNER JOIN reservas r ON p.reserva_id = r.id
                        INNER JOIN canchas c ON r.cancha_id = c.id
                        INNER JOIN usuarios u_cli ON r.usuario_id = u_cli.id
                        LEFT JOIN usuarios u_adm ON p.registrado_por = u_adm.id
                        WHERE DATE(p.created_at) BETWEEN ? AND ? AND p.estado = 'verificado'{$demoPagos}";
        $movPagos = $this->select($sqlMovPagos, [$desde, $hasta]);

        $sqlMovVentas = "SELECT v.id,
                                'producto' AS rubro,
                                v.created_at,
                                'venta_directa' AS tipo,
                                v.metodo_pago,
                                v.total AS monto,
                                v.id AS referencia_id,
                                CONCAT('Venta #', v.codigo_operacion) AS concepto,
                                v.cliente_nombre,
                                u_adm.nombre AS admin_nombre,
                                v.es_demo
                         FROM ventas v
                         LEFT JOIN usuarios u_adm ON v.registrado_por = u_adm.id
                         WHERE DATE(v.created_at) BETWEEN ? AND ? AND v.estado = 'completada'{$demoVentas}";
        $movVentas = $this->select($sqlMovVentas, [$desde, $hasta]);

        $movimientos = array_merge($movPagos, $movVentas);
        usort($movimientos, static fn($a, $b) => strcmp((string)$b['created_at'], (string)$a['created_at']));

        $totalGeneral = round($cTotal + $pTotal, 2);
        $totalEfe     = round($cEfe + $pEfe, 2);
        $totalPos     = round($cPos + $pPos, 2);
        $totalYap     = $cYap;
        $totalTx      = $cTx + $pTx;

        return [
            'desde'                => $desde,
            'hasta'                => $hasta,
            'incluir_demo'         => $incluirDemo,
            'total_ingresos'       => $totalGeneral,
            'total_efectivo'       => $totalEfe,
            'total_niubiz'         => $totalPos,
            'total_yape'           => $totalYap,
            'total_transacciones'  => $totalTx,
            // Desglose Canchas
            'canchas_total'        => $cTotal,
            'canchas_efectivo'     => $cEfe,
            'canchas_niubiz'       => $cPos,
            'canchas_yape'         => $cYap,
            'adelantos_verificados'=> $cAde,
            'saldos_cobrados'      => $cSal,
            'completos_cobrados'   => $cCom,
            'canchas_tx'           => $cTx,
            // Desglose Productos
            'productos_total'      => $pTotal,
            'productos_efectivo'   => $pEfe,
            'productos_niubiz'     => $pPos,
            'productos_tx'         => $pTx,
            // Saldos por cobrar
            'saldos_pendientes'    => $saldosPendientes,
            // Datos demo detectados
            'demo_monto'           => $demoMonto,
            'demo_conteo'          => $demoCount,
            // Cronológico y Detalle
            'dias'                 => array_values($diasMap),
            'transacciones'        => $movimientos,
        ];
    }

    /**
     * Monto de los comprobantes en revisión por reserva (una sola consulta).
     *
     * @return array<int, float> reserva_id => monto
     */
    public function montosEnRevision(): array {
        $filas = $this->select("SELECT reserva_id, SUM(monto) AS monto FROM {$this->tabla} WHERE estado = 'en_revision' GROUP BY reserva_id");
        $out = [];
        foreach ($filas as $f) {
            $out[(int)$f['reserva_id']] = (float)$f['monto'];
        }
        return $out;
    }
}
