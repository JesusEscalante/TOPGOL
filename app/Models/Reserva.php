<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * ====================================================================
 * TOP GOL - Modelo Reserva
 * ====================================================================
 * Gestiona el ciclo de vida de las reservas de canchas, relaciones
 * con usuarios, cálculo de totales y estadísticas operativas.
 */
class Reserva extends Model {
    protected string $tabla = 'reservas';

    /**
     * Obtiene todas las reservas con los datos del usuario y la cancha asociada
    /**
     * Obtiene el listado completo de reservas registradas con datos de cliente y pagos
     *
     * @param bool $incluirDemo Si es falso, aísla reservas marcadas como demo
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodas(bool $incluirDemo = true): array {
        $whereDemo = $incluirDemo ? '' : ' WHERE r.es_demo = 0';
        $sql = "SELECT r.*,
                       u.nombre AS usuario_nombre,
                       u.email AS usuario_email,
                       u.telefono AS usuario_telefono,
                       c.nombre AS cancha_nombre,
                       c.tipo AS cancha_tipo,
                       c.precio_hora AS cancha_precio_hora,
                       COALESCE((SELECT SUM(p.monto) FROM pagos p WHERE p.reserva_id = r.id AND p.estado = 'verificado'), 0) AS total_pagado,
                       GREATEST(0, r.total_pago - COALESCE((SELECT SUM(p.monto) FROM pagos p WHERE p.reserva_id = r.id AND p.estado = 'verificado'), 0)) AS saldo_pendiente
                FROM {$this->tabla} r
                INNER JOIN usuarios u ON r.usuario_id = u.id
                INNER JOIN canchas c ON r.cancha_id = c.id{$whereDemo}
                ORDER BY r.fecha DESC, r.hora_inicio DESC";

        return $this->select($sql);
    }

    /**
     * Obtiene una reserva por su ID con datos relacionados y estado de pagos
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT r.*,
                       u.nombre AS usuario_nombre,
                       u.email AS usuario_email,
                       u.telefono AS usuario_telefono,
                       c.nombre AS cancha_nombre,
                       c.tipo AS cancha_tipo,
                       c.precio_hora AS cancha_precio_hora,
                       COALESCE((SELECT SUM(p.monto) FROM pagos p WHERE p.reserva_id = r.id AND p.estado = 'verificado'), 0) AS total_pagado,
                       GREATEST(0, r.total_pago - COALESCE((SELECT SUM(p.monto) FROM pagos p WHERE p.reserva_id = r.id AND p.estado = 'verificado'), 0)) AS saldo_pendiente
                FROM {$this->tabla} r
                INNER JOIN usuarios u ON r.usuario_id = u.id
                INNER JOIN canchas c ON r.cancha_id = c.id
                WHERE r.id = ?
                LIMIT 1";

        return $this->selectOne($sql, [$id]);
    }

    /**
     * Obtiene el listado de reservas correspondientes a un usuario específico
     *
     * @param int $usuarioId
     * @return array<int, array<string, mixed>>
     */
    public function obtenerPorUsuario(int $usuarioId): array {
        $sql = "SELECT r.*,
                       c.nombre AS cancha_nombre,
                       c.tipo AS cancha_tipo,
                       c.techada AS cancha_techada,
                       c.iluminacion AS cancha_iluminacion
                FROM {$this->tabla} r
                INNER JOIN canchas c ON r.cancha_id = c.id
                WHERE r.usuario_id = ?
                ORDER BY r.fecha DESC, r.hora_inicio DESC";

        return $this->select($sql, [$usuarioId]);
    }

    /**
     * Registra una nueva reserva en la base de datos (con pago y comprobante opcionales)
     *
     * @param array<string, mixed> $datos
     * @return int ID de la nueva reserva
     */
    public function crear(array $datos): int {
        $metodoPago = $datos['metodo_pago'] ?? null;
        if (!in_array($metodoPago, ['yape', 'efectivo', 'niubiz'], true)) {
            $metodoPago = null;
        }

        $pagoEstado = (string)($datos['pago_estado'] ?? 'pendiente');
        if (!in_array($pagoEstado, ['pendiente', 'en_revision', 'verificado', 'rechazado'], true)) {
            $pagoEstado = 'pendiente';
        }

        $origen = (string)($datos['origen'] ?? 'web');
        if (!in_array($origen, ['web', 'presencial', 'llamada', 'whatsapp'], true)) {
            $origen = 'web';
        }

        $sql = "INSERT INTO {$this->tabla}
                (usuario_id, cancha_id, fecha, hora_inicio, hora_fin, duracion_horas, total_pago, estado, observaciones,
                 metodo_pago, origen, adelanto_monto, cliente_nombre, contacto_telefono, evento_grupo, pago_estado,
                 comprobante_ruta, comprobante_nombre, comprobante_tipo, comprobante_size, comprobante_subido_at,
                 created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $duracionHoras = isset($datos['duracion_horas']) ? (float)$datos['duracion_horas'] : (isset($datos['duracion_minutos']) ? round((int)$datos['duracion_minutos'] / 60, 2) : 1.0);
        $totalPago = (float)$datos['total_pago'];
        $adelantoMonto = (float)($datos['adelanto_monto'] ?? 20.00);

        $this->query($sql, [
            (int)$datos['usuario_id'],
            (int)$datos['cancha_id'],
            (string)$datos['fecha'],
            (string)$datos['hora_inicio'],
            (string)$datos['hora_fin'],
            $duracionHoras,
            $totalPago,
            (string)($datos['estado'] ?? 'pendiente'),
            trim((string)($datos['observaciones'] ?? '')),
            $metodoPago,
            $origen,
            $adelantoMonto,
            isset($datos['cliente_nombre']) && trim((string)$datos['cliente_nombre']) !== '' ? trim((string)$datos['cliente_nombre']) : null,
            isset($datos['contacto_telefono']) && trim((string)$datos['contacto_telefono']) !== '' ? trim((string)$datos['contacto_telefono']) : null,
            isset($datos['evento_grupo']) && trim((string)$datos['evento_grupo']) !== '' ? trim((string)$datos['evento_grupo']) : null,
            $pagoEstado,
            isset($datos['comprobante_ruta']) ? (string)$datos['comprobante_ruta'] : null,
            isset($datos['comprobante_nombre']) ? (string)$datos['comprobante_nombre'] : null,
            isset($datos['comprobante_tipo']) ? (string)$datos['comprobante_tipo'] : null,
            isset($datos['comprobante_size']) ? (int)$datos['comprobante_size'] : null,
            isset($datos['comprobante_subido_at']) ? (string)$datos['comprobante_subido_at'] : null,
        ]);

        $idReserva = $this->lastInsertId();

        // Registrar automáticamente el cobro inicial en la tabla de pagos
        if ($idReserva > 0) {
            $modeloPago = new Pago();
            $metodoParaPago = in_array($metodoPago, ['efectivo', 'niubiz'], true) ? $metodoPago : 'yape';
            $usuarioOp = (int)$datos['usuario_id'];

            if ($origen !== 'web' && $adelantoMonto >= $totalPago) {
                // Pago completo presencial
                $modeloPago->crear([
                    'reserva_id'     => $idReserva,
                    'tipo'           => 'completo',
                    'monto'          => $totalPago,
                    'metodo_pago'    => $metodoParaPago,
                    'estado'         => 'verificado',
                    'registrado_por' => $usuarioOp,
                    'notas'          => 'Pago completo presencial registrado en mostrador',
                ]);
            } elseif ($adelantoMonto > 0) {
                // Adelanto inicial
                $modeloPago->crear([
                    'reserva_id'     => $idReserva,
                    'tipo'           => 'adelanto',
                    'monto'          => $adelantoMonto,
                    'metodo_pago'    => $metodoParaPago,
                    'estado'         => $pagoEstado,
                    'registrado_por' => ($pagoEstado === 'verificado') ? $usuarioOp : null,
                    'notas'          => $origen === 'web' ? 'Adelanto web' : "Adelanto presencial ({$origen})",
                ]);
            }
        }

        return $idReserva;
    }

    /**
     * Registra o reemplaza el comprobante de pago de una reserva
     * y la marca como 'en_revision'.
     *
     * @param int $id
     * @param array{ruta: string, nombre: string, tipo: string, size: int} $comprobante
     * @return bool
     */
    public function registrarComprobante(int $id, array $comprobante): bool {
        $sql = "UPDATE {$this->tabla} SET
                    comprobante_ruta = ?,
                    comprobante_nombre = ?,
                    comprobante_tipo = ?,
                    comprobante_size = ?,
                    comprobante_subido_at = NOW(),
                    pago_estado = 'en_revision',
                    updated_at = NOW()
                WHERE id = ?";
        return $this->execute($sql, [
            $comprobante['ruta'],
            $comprobante['nombre'],
            $comprobante['tipo'],
            (int)$comprobante['size'],
            $id
        ]);
    }

    /**
     * Actualiza el estado de verificación del pago (uso administrativo).
     *
     * @param int $id
     * @param string $estado Uno de: pendiente, en_revision, verificado, rechazado
     * @param int|null $verificadoPor ID del admin que verifica (opcional)
     * @return bool
     */
    public function actualizarEstadoPago(int $id, string $estado, ?int $verificadoPor = null): bool {
        if (!in_array($estado, ['pendiente', 'en_revision', 'verificado', 'rechazado'], true)) {
            return false;
        }

        if ($estado === 'verificado') {
            $sql = "UPDATE {$this->tabla} SET pago_estado = ?, estado = IF(estado = 'cancelada', estado, 'confirmada'), pago_verificado_at = NOW(), pago_verificado_por = ?, updated_at = NOW() WHERE id = ?";
            $res = $this->execute($sql, [$estado, $verificadoPor, $id]);
        } elseif ($estado === 'rechazado') {
            $sql = "UPDATE {$this->tabla} SET pago_estado = ?, estado = 'cancelada', updated_at = NOW() WHERE id = ?";
            $res = $this->execute($sql, [$estado, $id]);
        } else {
            $sql = "UPDATE {$this->tabla} SET pago_estado = ?, updated_at = NOW() WHERE id = ?";
            $res = $this->execute($sql, [$estado, $id]);
        }

        // Sincronizar en tabla pagos el estado del adelanto
        $modeloPago = new Pago();
        $modeloPago->actualizarEstadoPorReservaYTipo($id, 'adelanto', $estado, $verificadoPor);

        return $res;
    }

    /**
     * Actualiza el estado de verificación del pago para todo un grupo (evento).
     * @param string $grupo
     * @param string $estado
     * @param int|null $verificadoPor
     * @return bool
     */
    public function actualizarEstadoPagoGrupo(string $grupo, string $estado, ?int $verificadoPor = null): bool {
        if (!in_array($estado, ['pendiente', 'en_revision', 'verificado', 'rechazado'], true)) {
            return false;
        }

        if ($estado === 'verificado') {
            $sql = "UPDATE {$this->tabla} SET pago_estado = ?, estado = IF(estado = 'cancelada', estado, 'confirmada'), pago_verificado_at = NOW(), pago_verificado_por = ?, updated_at = NOW() WHERE evento_grupo = ?";
            $res = $this->execute($sql, [$estado, $verificadoPor, $grupo]);
        } elseif ($estado === 'rechazado') {
            $sql = "UPDATE {$this->tabla} SET pago_estado = ?, estado = 'cancelada', updated_at = NOW() WHERE evento_grupo = ?";
            $res = $this->execute($sql, [$estado, $grupo]);
        } else {
            $sql = "UPDATE {$this->tabla} SET pago_estado = ?, updated_at = NOW() WHERE evento_grupo = ?";
            $res = $this->execute($sql, [$estado, $grupo]);
        }

        // Sincronizar en tabla pagos todos los adelantos del grupo
        $modeloPago = new Pago();
        $modeloPago->actualizarEstadoPorGrupo($grupo, $estado, $verificadoPor);

        return $res;
    }

    /**
     * Actualiza el estado general para todo un grupo (evento).
     * @param string $grupo
     * @param string $estado
     * @return bool
     */
    public function actualizarEstadoGrupo(string $grupo, string $estado): bool {
        if (!in_array($estado, ['pendiente', 'confirmada', 'cancelada', 'finalizada'], true)) {
            return false;
        }
        $this->ultimoCodigo = null;
        if (in_array($estado, ['pendiente', 'confirmada'], true)) {
            $canceladas = $this->select("SELECT id FROM {$this->tabla} WHERE evento_grupo = ? AND estado = 'cancelada'", [$grupo]);
            if (!empty($canceladas)) {
                return $this->reactivarAtomico(array_map(static fn($f) => (int)$f['id'], $canceladas), $estado, $grupo);
            }
        }
        $sql = "UPDATE {$this->tabla} SET estado = ?, updated_at = NOW() WHERE evento_grupo = ?";
        return $this->execute($sql, [$estado, $grupo]);
    }

    /**
     * Obtiene las reservas con comprobante pendiente de verificación.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerPagosEnRevision(): array {
        $sql = "SELECT r.*,
                       u.nombre AS usuario_nombre,
                       u.email AS usuario_email,
                       u.telefono AS usuario_telefono,
                       c.nombre AS cancha_nombre
                FROM {$this->tabla} r
                INNER JOIN usuarios u ON r.usuario_id = u.id
                INNER JOIN canchas c ON r.cancha_id = c.id
                WHERE r.pago_estado = 'en_revision'
                ORDER BY r.comprobante_subido_at ASC";
        return $this->select($sql);
    }

    /**
     * Actualiza el estado de una reserva ('pendiente', 'confirmada', 'cancelada', 'finalizada')
     *
     * @param int $id
     * @param string $estado
     * @return bool
     */
    public function actualizarEstado(int $id, string $estado): bool {
        $this->ultimoCodigo = null;
        // Reactivar una reserva cancelada solo si su horario sigue libre (la cancelada ya liberó la cancha)
        if (in_array($estado, ['pendiente', 'confirmada'], true)) {
            $actual = $this->selectOne("SELECT estado FROM {$this->tabla} WHERE id = ?", [$id]);
            if ($actual && $actual['estado'] === 'cancelada') {
                return $this->reactivarAtomico([$id], $estado);
            }
        }
        $sql = "UPDATE {$this->tabla} SET estado = ?, updated_at = NOW() WHERE id = ?";
        return $this->execute($sql, [$estado, $id]);
    }

    // ------------------------------------------------------------------
    //  RESERVA ATÓMICA: evita que dos clientes tomen la misma cancha a la vez
    // ------------------------------------------------------------------

    /** Último motivo de fallo de una operación atómica: 'ocupado' | 'cancha_no_disponible' | 'error' | null */
    public ?string $ultimoCodigo = null;
    /** Cancha que provocó el choque (si aplica) */
    public ?int $ultimaCancha = null;

    /**
     * Bloquea las canchas indicadas con SELECT ... FOR UPDATE, SIEMPRE en orden ascendente de id
     * (así dos procesos que piden varias canchas nunca se bloquean entre sí en cruz).
     * Debe llamarse dentro de una transacción.
     *
     * @param array<int, int> $ids
     * @return array<int, array<string, mixed>> Filas de las canchas bloqueadas (id, estado)
     */
    public function bloquearCanchas(array $ids): array {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids, SORT_NUMERIC);
        if (empty($ids)) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        return $this->select("SELECT id, estado FROM canchas WHERE id IN ({$in}) ORDER BY id ASC FOR UPDATE", $ids);
    }

    /**
     * ¿Hay choque en esa cancha e intervalo? Usa lecturas actuales (FOR UPDATE), por eso es fiable
     * dentro de la transacción. Una reserva que termina justo cuando empieza otra NO choca.
     * Cuentan las reservas pendientes/confirmadas (las canceladas liberan) y las canchas incluidas
     * en solicitudes de cumpleaños/reunión ya confirmadas.
     */
    public function hayChoque(int $canchaId, string $fecha, string $horaInicio, string $horaFin, ?int $excluirId = null, bool $incluirDemo = false, ?int $excluirSolicitudId = null): bool {
        if (strlen($horaInicio) === 5) { $horaInicio .= ':00'; }
        if (strlen($horaFin) === 5) { $horaFin .= ':00'; }
        if ($horaFin === '00:00:00') { $horaFin = '24:00:00'; }

        $sql = "SELECT id FROM {$this->tabla}
                WHERE cancha_id = ? AND fecha = ?
                  AND estado IN ('pendiente', 'confirmada')
                  AND hora_inicio < ?
                  AND IF(hora_fin = '00:00:00', '24:00:00', hora_fin) > ?";
        $params = [$canchaId, $fecha, $horaFin, $horaInicio];
        if (!$incluirDemo) {
            $sql .= ' AND es_demo = 0';
        }
        if ($excluirId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excluirId;
        }
        $sql .= ' LIMIT 1 FOR UPDATE';
        if (!empty($this->select($sql, $params))) {
            return true;
        }

        try {
            $sqlEsp = "SELECT sc.id FROM solicitudes_espacios_canchas sc
                       JOIN solicitudes_espacios s ON s.id = sc.solicitud_id
                       WHERE sc.cancha_id = ? AND s.fecha = ? AND s.estado = 'confirmado'
                         AND s.hora_inicio < ? AND s.hora_fin > ?";
            $paramsEsp = [$canchaId, $fecha, $horaFin, $horaInicio];
            if ($excluirSolicitudId !== null) {
                $sqlEsp .= ' AND s.id != ?';
                $paramsEsp[] = $excluirSolicitudId;
            }
            return !empty($this->select($sqlEsp . ' LIMIT 1 FOR UPDATE', $paramsEsp));
        } catch (\PDOException $e) {
            return false; // migración 016 aún no aplicada
        }
    }

    /**
     * FUNCIÓN CENTRAL: crea una o varias reservas (y su pago inicial) de forma atómica.
     * Abre transacción, bloquea las canchas (orden ascendente), vuelve a comprobar el solapamiento
     * DENTRO de la transacción, guarda todo y confirma. Si algo falla: rollback completo
     * (en eventos de varias canchas: todo o nada).
     *
     * @param array<int, array<string, mixed>> $lista Datos de cada reserva (los mismos de crear())
     * @return array{ok: bool, ids: array<int, int>, codigo: ?string, cancha_id: ?int, error: ?string}
     */
    public function crearAtomico(array $lista): array {
        $this->ultimoCodigo = null;
        $this->ultimaCancha = null;
        $fallo = function (string $codigo, ?int $cancha, ?string $msg = null): array {
            $this->ultimoCodigo = $codigo;
            $this->ultimaCancha = $cancha;
            return ['ok' => false, 'ids' => [], 'codigo' => $codigo, 'cancha_id' => $cancha, 'error' => $msg];
        };
        if (empty($lista)) {
            return $fallo('error', null, 'No hay reservas para guardar.');
        }
        if ($this->inTransaction()) {
            return $fallo('error', null, 'Ya hay una transacción abierta.');
        }

        $this->beginTransaction();
        try {
            $bloqueadas = $this->bloquearCanchas(array_map(static fn($d) => (int)$d['cancha_id'], $lista));
            $estados = [];
            foreach ($bloqueadas as $c) { $estados[(int)$c['id']] = (string)$c['estado']; }

            foreach ($lista as $d) {
                $cid = (int)$d['cancha_id'];
                if (($estados[$cid] ?? '') !== 'disponible') {
                    $this->rollBack();
                    return $fallo('cancha_no_disponible', $cid);
                }
                if ($this->hayChoque($cid, (string)$d['fecha'], (string)$d['hora_inicio'], (string)$d['hora_fin'])) {
                    $this->rollBack();
                    return $fallo('ocupado', $cid);
                }
            }

            $ids = [];
            foreach ($lista as $d) {
                $id = $this->crear($d);
                if ($id <= 0) {
                    $this->rollBack();
                    return $fallo('error', (int)$d['cancha_id'], 'No se pudo guardar la reserva.');
                }
                $ids[] = $id;
            }
            $this->commit();
            return ['ok' => true, 'ids' => $ids, 'codigo' => null, 'cancha_id' => null, 'error' => null];
        } catch (\Throwable $e) {
            $this->rollBack();
            return $fallo('error', null, $e->getMessage());
        }
    }

    /**
     * Cambia cancha / fecha / hora de una reserva existente sin chocar con otras (excluyendo la propia).
     * Misma lógica atómica: transacción + bloqueo de la cancha destino + nueva comprobación.
     *
     * @param array<string, mixed> $datos Los mismos campos de actualizarReserva()
     * @return array{ok: bool, codigo: ?string, error: ?string}
     */
    public function actualizarReservaAtomica(int $id, array $datos, bool $revisarChoque = true): array {
        $this->ultimoCodigo = null;
        if ($this->inTransaction()) {
            return ['ok' => false, 'codigo' => 'error', 'error' => 'Ya hay una transacción abierta.'];
        }
        $this->beginTransaction();
        try {
            $this->bloquearCanchas([(int)$datos['cancha_id']]);
            $this->select("SELECT id FROM {$this->tabla} WHERE id = ? FOR UPDATE", [$id]);
            if ($revisarChoque && $this->hayChoque((int)$datos['cancha_id'], (string)$datos['fecha'], (string)$datos['hora_inicio'], (string)$datos['hora_fin'], $id)) {
                $this->rollBack();
                $this->ultimoCodigo = 'ocupado';
                $this->ultimaCancha = (int)$datos['cancha_id'];
                return ['ok' => false, 'codigo' => 'ocupado', 'error' => null];
            }
            $this->actualizarReserva($id, $datos); // devuelve false si no cambió ningún valor: no es un error
            $this->commit();
            return ['ok' => true, 'codigo' => null, 'error' => null];
        } catch (\Throwable $e) {
            $this->rollBack();
            $this->ultimoCodigo = 'error';
            return ['ok' => false, 'codigo' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Reactiva reservas canceladas (pendiente/confirmada) solo si su horario sigue libre.
     * Se usa en actualizarEstado() y actualizarEstadoGrupo(): una cancelada ya liberó la cancha.
     *
     * @param array<int, int> $revivir IDs de reservas canceladas que se quieren reactivar
     */
    private function reactivarAtomico(array $revivir, string $estado, ?string $grupo = null): bool {
        $this->ultimoCodigo = null;
        if ($this->inTransaction()) {
            return false;
        }
        $this->beginTransaction();
        try {
            $in = implode(',', array_fill(0, count($revivir), '?'));
            $filas = $this->select("SELECT id, cancha_id, fecha, hora_inicio, hora_fin FROM {$this->tabla} WHERE id IN ({$in})", $revivir);
            $this->bloquearCanchas(array_map(static fn($f) => (int)$f['cancha_id'], $filas));
            foreach ($filas as $f) {
                if ($this->hayChoque((int)$f['cancha_id'], (string)$f['fecha'], (string)$f['hora_inicio'], (string)$f['hora_fin'], (int)$f['id'])) {
                    $this->rollBack();
                    $this->ultimoCodigo = 'ocupado';
                    $this->ultimaCancha = (int)$f['cancha_id'];
                    return false;
                }
            }
            if (!empty($grupo)) {
                $ok = $this->execute("UPDATE {$this->tabla} SET estado = ?, updated_at = NOW() WHERE evento_grupo = ?", [$estado, $grupo]);
            } else {
                $ok = $this->execute("UPDATE {$this->tabla} SET estado = ?, updated_at = NOW() WHERE id = ?", [$estado, $revivir[0]]);
            }
            $this->commit();
            return $ok;
        } catch (\Throwable $e) {
            $this->rollBack();
            $this->ultimoCodigo = 'error';
            return false;
        }
    }

    /**
     * Guarda el motivo de una cancelación hecha por el administrador (en la nota de asistencia).
     * Si recibe un grupo (evento), lo guarda en todas sus reservas.
     */
    public function guardarMotivoCancelacion(int $id, ?string $grupo, string $motivo, ?int $adminId = null): bool {
        $nota = 'Cancelada: ' . mb_substr(trim($motivo), 0, 300);
        if (!empty($grupo)) {
            $sql = "UPDATE {$this->tabla} SET asistencia_notas = ?, asistencia_registrada_por = ?, asistencia_updated_at = NOW(), updated_at = NOW() WHERE evento_grupo = ?";
            return $this->execute($sql, [$nota, $adminId, $grupo]);
        }
        $sql = "UPDATE {$this->tabla} SET asistencia_notas = ?, asistencia_registrada_por = ?, asistencia_updated_at = NOW(), updated_at = NOW() WHERE id = ?";
        return $this->execute($sql, [$nota, $adminId, $id]);
    }

    /**
     * Regla de cierre: una reserva CONFIRMADA pasa a FINALIZADA cuando el cliente asistió
     * (presente o tardanza), el saldo está en cero y ya pasó la hora de fin.
     * Se revisa al registrar asistencia, al cobrar saldo y al abrir el detalle del administrador.
     *
     * @return bool True si la reserva quedó finalizada en esta llamada
     */
    public function sincronizarFinalizacion(int $id): bool {
        $r = $this->obtenerPorId($id);
        if (!$r || $r['estado'] !== 'confirmada') {
            return false;
        }
        if (!in_array((string)($r['asistencia_estado'] ?? 'sin_registrar'), ['presente', 'tardanza'], true)) {
            return false;
        }
        $tz = new \DateTimeZone('America/Lima');
        $ini = new \DateTime((string)$r['fecha'] . ' ' . (string)$r['hora_inicio'], $tz);
        $fin = new \DateTime((string)$r['fecha'] . ' ' . (string)$r['hora_fin'], $tz);
        if ($fin <= $ini) {
            $fin->modify('+1 day');
        }
        if (new \DateTime('now', $tz) < $fin) {
            return false;
        }
        $pagado = (new Pago())->obtenerTotalPagado($id);
        if (round((float)$r['total_pago'] - $pagado, 2) > 0.0) {
            return false;
        }
        return $this->actualizarEstado($id, 'finalizada');
    }

    /**
     * Aplica sincronizarFinalizacion() a una reserva o a todas las de un grupo (evento).
     */
    public function sincronizarFinalizacionGrupoOId(int $id, ?string $grupo = null): void {
        if (!empty($grupo)) {
            foreach ($this->select("SELECT id FROM {$this->tabla} WHERE evento_grupo = ?", [$grupo]) as $fila) {
                $this->sincronizarFinalizacion((int)$fila['id']);
            }
            return;
        }
        $this->sincronizarFinalizacion($id);
    }

    /**
     * Actualiza los datos operativos de una reserva (Uso Administrativo)
     *
     * @param int $id
     * @param array<string, mixed> $datos
     * @return bool
     */
    public function actualizarReserva(int $id, array $datos): bool {
        $sql = "UPDATE {$this->tabla} SET
                    cancha_id = ?,
                    fecha = ?,
                    hora_inicio = ?,
                    hora_fin = ?,
                    duracion_horas = ?,
                    total_pago = ?,
                    estado = ?,
                    observaciones = ?,
                    origen = ?,
                    cliente_nombre = ?,
                    contacto_telefono = ?,
                    adelanto_monto = ?,
                    updated_at = NOW()
                WHERE id = ?";
        $duracionHoras = isset($datos['duracion_horas']) ? (float)$datos['duracion_horas'] : (isset($datos['duracion_minutos']) ? round((int)$datos['duracion_minutos'] / 60, 2) : 1.0);

        return $this->execute($sql, [
            (int)$datos['cancha_id'],
            (string)$datos['fecha'],
            (string)$datos['hora_inicio'],
            (string)$datos['hora_fin'],
            $duracionHoras,
            (float)$datos['total_pago'],
            (string)$datos['estado'],
            (string)($datos['observaciones'] ?? ''),
            (string)($datos['origen'] ?? 'web'),
            !empty($datos['cliente_nombre']) ? trim((string)$datos['cliente_nombre']) : null,
            !empty($datos['contacto_telefono']) ? trim((string)$datos['contacto_telefono']) : null,
            (float)($datos['adelanto_monto'] ?? 0.0),
            $id
        ]);
    }

    /**
     * Permite cancelar una reserva si pertenece al usuario o si es un administrador
     *
     * @param int $id
     * @param int $usuarioId
     * @param bool $esAdmin
     * @return bool
     */
    public function cancelar(int $id, int $usuarioId, bool $esAdmin = false): bool {
        if ($esAdmin) {
            $sql = "UPDATE {$this->tabla} SET estado = 'cancelada', updated_at = NOW() WHERE id = ?";
            $params = [$id];
        } else {
            $sql = "UPDATE {$this->tabla} SET estado = 'cancelada', updated_at = NOW()
                    WHERE id = ? AND usuario_id = ? AND estado IN ('pendiente', 'confirmada')";
            $params = [$id, $usuarioId];
        }

        // Cancelar libera la cancha y NO toca los pagos. Se hace con la cancha bloqueada (misma
        // lógica de la reserva atómica) para que no se cruce con una reserva nueva que se esté guardando.
        $fila = $this->selectOne("SELECT cancha_id FROM {$this->tabla} WHERE id = ?", [$id]);
        if (!$fila || $this->inTransaction()) {
            return $this->execute($sql, $params);
        }
        $this->beginTransaction();
        try {
            $this->bloquearCanchas([(int)$fila['cancha_id']]);
            $ok = $this->execute($sql, $params);
            $this->commit();
            return $ok;
        } catch (\Throwable $e) {
            $this->rollBack();
            return false;
        }
    }

    /**
     * Obtiene métricas y estadísticas para el panel administrativo
     *
     * @return array<string, mixed>
     */
    public function obtenerEstadisticas(bool $incluirDemo = true): array {
        $whereDemo = $incluirDemo ? '' : ' WHERE es_demo = 0';
        $whereDemoPagos = $incluirDemo ? '' : ' AND es_demo = 0';
        $sql = "SELECT
                    COUNT(*) AS total_reservas,
                    SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) AS confirmadas,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) AS canceladas,
                    SUM(CASE WHEN estado = 'finalizada' THEN 1 ELSE 0 END) AS finalizadas,
                    SUM(CASE WHEN pago_estado = 'en_revision' THEN 1 ELSE 0 END) AS pagos_en_revision
                FROM {$this->tabla}{$whereDemo}";

        $res = $this->selectOne($sql);

        // Dinero efectivamente cobrado y verificado desde la tabla pagos (sin inflar con valores totales)
        $sqlPagos = "SELECT
                        COALESCE(SUM(monto), 0) AS ingresos_totales,
                        COALESCE(SUM(CASE WHEN tipo = 'adelanto' THEN monto ELSE 0 END), 0) AS adelantos_verificados,
                        COALESCE(SUM(CASE WHEN tipo IN ('saldo', 'completo') THEN monto ELSE 0 END), 0) AS saldos_cobrados
                     FROM pagos
                     WHERE estado = 'verificado'{$whereDemoPagos}";
        $resPagos = $this->selectOne($sqlPagos);

        return [
            'total_reservas'        => (int)($res['total_reservas'] ?? 0),
            'confirmadas'           => (int)($res['confirmadas'] ?? 0),
            'pendientes'            => (int)($res['pendientes'] ?? 0),
            'canceladas'            => (int)($res['canceladas'] ?? 0),
            'finalizadas'           => (int)($res['finalizadas'] ?? 0),
            'pagos_en_revision'     => (int)($res['pagos_en_revision'] ?? 0),
            'ingresos_totales'      => round((float)($resPagos['ingresos_totales'] ?? 0.0), 2),
            'adelantos_verificados' => round((float)($resPagos['adelantos_verificados'] ?? 0.0), 2),
            'saldos_cobrados'       => round((float)($resPagos['saldos_cobrados'] ?? 0.0), 2)
        ];
    }

    /**
     * Obtiene una reserva con su historial completo de pagos asociados
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function obtenerConPagos(int $id): ?array {
        $reserva = $this->obtenerPorId($id);
        if (!$reserva) {
            return null;
        }

        $modeloPago = new Pago();
        $reserva['pagos'] = $modeloPago->obtenerPorReserva($id);
        $reserva['total_pagado'] = $modeloPago->obtenerTotalPagado($id);
        $reserva['saldo_pendiente'] = max(0.0, round((float)$reserva['total_pago'] - (float)$reserva['total_pagado'], 2));

        return $reserva;
    }

    /**
     * Retorna los bloques horarios ocupados para una cancha en una fecha específica
     *
     * @param int $canchaId
     * @param string $fecha
     * @return array<int, array{hora_inicio: string, hora_fin: string, estado: string}>
     */
    public function obtenerHorariosOcupados(int $canchaId, string $fecha, bool $incluirDemo = true): array {
        $whereDemo = $incluirDemo ? '' : ' AND es_demo = 0';
        $sql = "SELECT hora_inicio, hora_fin, estado
                FROM {$this->tabla}
                WHERE cancha_id = ? AND fecha = ? AND estado IN ('pendiente', 'confirmada'){$whereDemo}
                ORDER BY hora_inicio ASC";

        return $this->select($sql, [$canchaId, $fecha]);
    }

    /**
     * Cantidad de reservas/eventos (un evento cuenta una vez) con pago por verificar. Mismo criterio que pagoEsPorVerificar().
     */
    public function contarPagosPorVerificar(): int {
        $res = $this->selectOne("SELECT COUNT(DISTINCT IF(evento_grupo IS NULL OR evento_grupo = '', CONCAT('r', id), CONCAT('e', evento_grupo))) AS total
                                 FROM {$this->tabla}
                                 WHERE pago_estado = 'en_revision' AND estado <> 'cancelada' AND es_demo = 0");
        return (int)($res['total'] ?? 0);
    }

    /**
     * Agrega una nota al final de las observaciones de una reserva o de todo un evento (por ejemplo, el motivo de un rechazo).
     */
    public function agregarObservacion(int $id, string $grupo, string $texto): void {
        $texto = mb_substr(trim($texto), 0, 300);
        if ($texto === '') {
            return;
        }
        $set = "observaciones = CONCAT(IFNULL(observaciones, ''), IF(observaciones IS NULL OR observaciones = '', '', ' | '), ?), updated_at = NOW()";
        if ($grupo !== '') {
            $this->execute("UPDATE {$this->tabla} SET {$set} WHERE evento_grupo = ?", [$texto, $grupo]);
        } else {
            $this->execute("UPDATE {$this->tabla} SET {$set} WHERE id = ?", [$texto, $id]);
        }
    }

    /**
     * Intervalos ocupados (en minutos desde 00:00) de una cancha entre dos fechas. Solo lectura, una consulta.
     * Mismo criterio que hayChoque: es_demo=0, 00:00 = 24:00 y espacios confirmados con sus canchas.
     * Es solo ayuda visual para el formulario: el servidor vuelve a validar al reservar.
     *
     * @return array<string, array<int, array{0: int, 1: int}>> fecha => [[inicio, fin], ...]
     */
    public function ocupacionCancha(int $canchaId, string $desde, string $hasta): array {
        $sqlRes = "SELECT fecha, hora_inicio, hora_fin FROM {$this->tabla}
                   WHERE cancha_id = ? AND fecha BETWEEN ? AND ?
                     AND estado IN ('pendiente', 'confirmada') AND es_demo = 0";
        $sqlEsp = "SELECT s.fecha, s.hora_inicio, s.hora_fin FROM solicitudes_espacios_canchas sc
                   JOIN solicitudes_espacios s ON s.id = sc.solicitud_id
                   WHERE sc.cancha_id = ? AND s.fecha BETWEEN ? AND ? AND s.estado = 'confirmado'";
        $params = [$canchaId, $desde, $hasta];
        try {
            $filas = $this->select($sqlRes . ' UNION ALL ' . $sqlEsp, array_merge($params, $params));
        } catch (\PDOException $e) {
            $filas = $this->select($sqlRes, $params); // migración 016 aún no aplicada
        }
        $aMin = static fn(string $h): int => ((int)substr($h, 0, 2)) * 60 + (int)substr($h, 3, 2);
        $ocupado = [];
        foreach ($filas as $f) {
            $fin = $aMin((string)$f['hora_fin']);
            $ocupado[(string)$f['fecha']][] = [$aMin((string)$f['hora_inicio']), $fin === 0 ? 1440 : $fin];
        }
        return $ocupado;
    }

    /**
     * Próximos horarios libres (bloques de 30 min) de varias canchas para HOY y MAÑANA. Solo lectura.
     * Hace UNA sola consulta para todas las canchas y fechas; el cálculo de "libre" usa las mismas reglas
     * del formulario (validarHorarioReserva: horario del negocio, paso de 30 min, no pasado) y el mismo
     * criterio de choque que hayChoque (es_demo=0, 00:00 = 24:00, espacios confirmados con sus canchas).
     *
     * @param array<int, int> $canchaIds
     * @return array<int, array{hoy: array<int, string>, manana: array<int, string>, fecha_hoy: string, fecha_manana: string}>
     */
    public function proximosHorariosLibres(array $canchaIds, int $max = 6): array {
        $canchaIds = array_values(array_unique(array_map('intval', $canchaIds)));
        $tz = new \DateTimeZone('America/Lima');
        $ahora = new \DateTimeImmutable('now', $tz);
        $fechas = ['hoy' => $ahora->format('Y-m-d'), 'manana' => $ahora->modify('+1 day')->format('Y-m-d')];

        $resultado = [];
        foreach ($canchaIds as $id) {
            $resultado[$id] = ['hoy' => [], 'manana' => [], 'fecha_hoy' => $fechas['hoy'], 'fecha_manana' => $fechas['manana']];
        }
        if (!$canchaIds) {
            return $resultado;
        }

        $inC = implode(',', array_fill(0, count($canchaIds), '?'));
        $sqlRes = "SELECT cancha_id, fecha, hora_inicio, hora_fin FROM {$this->tabla}
                   WHERE cancha_id IN ({$inC}) AND fecha IN (?, ?)
                     AND estado IN ('pendiente', 'confirmada') AND es_demo = 0";
        $sqlEsp = "SELECT sc.cancha_id, s.fecha, s.hora_inicio, s.hora_fin FROM solicitudes_espacios_canchas sc
                   JOIN solicitudes_espacios s ON s.id = sc.solicitud_id
                   WHERE sc.cancha_id IN ({$inC}) AND s.fecha IN (?, ?) AND s.estado = 'confirmado'";
        $params = array_merge($canchaIds, array_values($fechas));
        try {
            $filas = $this->select($sqlRes . ' UNION ALL ' . $sqlEsp, array_merge($params, $params));
        } catch (\PDOException $e) {
            $filas = $this->select($sqlRes, $params); // migración 016 aún no aplicada
        }

        $aMin = static function (string $h): int {
            return ((int)substr($h, 0, 2)) * 60 + (int)substr($h, 3, 2);
        };
        $ocupado = [];
        foreach ($filas as $f) {
            $fin = $aMin((string)$f['hora_fin']);
            $ocupado[(int)$f['cancha_id']][(string)$f['fecha']][] = [$aMin((string)$f['hora_inicio']), $fin === 0 ? 1440 : $fin];
        }

        foreach ($canchaIds as $id) {
            foreach ($fechas as $clave => $fecha) {
                $primera = horaMinimaReserva($fecha, false);
                if ($primera === '24:00') {
                    continue;
                }
                $bloques = $ocupado[$id][$fecha] ?? [];
                for ($m = $aMin($primera); $m < 1440 && count($resultado[$id][$clave]) < $max; $m += 30) {
                    $hhmm = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
                    if (!validarHorarioReserva($fecha, $hhmm, 0.5)['ok']) {
                        continue;
                    }
                    $libre = true;
                    foreach ($bloques as [$ini, $fin]) {
                        if ($ini < $m + 30 && $fin > $m) { $libre = false; break; }
                    }
                    if ($libre) {
                        $resultado[$id][$clave][] = $hhmm;
                    }
                }
            }
        }
        return $resultado;
    }

    /**
     * Retorna los slots horarios ocupados (formato 'HH:00') para una cancha
     * en una fecha, expandiendo las reservas en bloques de 1 hora (07:00-23:00).
     *
     * @param int $canchaId
     * @param string $fecha Formato YYYY-MM-DD
     * @param bool $incluirDemo
     * @return array<int, string>
     */
    public function obtenerSlotsOcupados(int $canchaId, string $fecha, bool $incluirDemo = false): array {
        $bloques = $this->obtenerHorariosOcupados($canchaId, $fecha, $incluirDemo);
        $ocupados = [];
        for ($h = 7; $h <= 23; $h++) {
            $ini = sprintf('%02d:00:00', $h);
            $fin = $h === 23 ? '24:00:00' : sprintf('%02d:00:00', $h + 1);
            foreach ($bloques as $b) {
                $bFin = $b['hora_fin'] === '00:00:00' ? '24:00:00' : $b['hora_fin'];
                if ($b['hora_inicio'] < $fin && $bFin > $ini) {
                    $ocupados[] = sprintf('%02d:00', $h);
                    break;
                }
            }
        }
        return $ocupados;
    }

    /**
     * Registra el estado de asistencia y puntualidad de una reserva
     *
     * @param int $id
     * @param string $asistenciaEstado 'sin_registrar'|'presente'|'tardanza'|'retraso_confirmado'|'no_asistio'
     * @param string|null $hora Formato HH:MM:SS o HH:MM
     * @param string|null $notas
     * @param int|null $adminId
     * @param bool $liberarCancha Si es true y el estado es no_asistio, libera la cancha (estado='cancelada')
     * @return array{ok: bool, mensaje?: string, error?: string}
     */
    public function registrarAsistencia(int $id, string $asistenciaEstado, ?string $hora = null, ?string $notas = null, ?int $adminId = null, bool $liberarCancha = false): array {
        $validos = ['sin_registrar', 'presente', 'tardanza', 'retraso_confirmado', 'no_asistio'];
        if (!in_array($asistenciaEstado, $validos, true)) {
            return ['ok' => false, 'error' => 'Estado de asistencia inválido.'];
        }

        $reserva = $this->obtenerPorId($id);
        if (!$reserva) {
            return ['ok' => false, 'error' => 'Reserva no encontrada.'];
        }

        // Si se provee hora pero no segundos, normalizar
        if ($hora !== null && strlen($hora) === 5) {
            $hora .= ':00';
        }

        // Si el estado es presente o tardanza y no se especificó hora, registrar la hora actual
        if (in_array($asistenciaEstado, ['presente', 'tardanza'], true) && $hora === null) {
            $hora = date('H:i:s');
        }

        // Si llega y se marca presente pero su llegada supera la tolerancia (TOLERANCIA_MINUTOS en config), registrar como tardanza
        if ($asistenciaEstado === 'presente' && $hora !== null) {
            $hIniTs = strtotime("{$reserva['fecha']} {$reserva['hora_inicio']}");
            $hLlegadaTs = strtotime("{$reserva['fecha']} {$hora}");
            if ($hLlegadaTs > $hIniTs + toleranciaMinutos() * 60) { // más de la tolerancia (config)
                $asistenciaEstado = 'tardanza';
                if ($notas === null || $notas === '') {
                    $minTardanza = (int)floor(($hLlegadaTs - $hIniTs) / 60);
                    $notas = "Ingresó con {$minTardanza} min de tardanza (tolerancia superada).";
                }
            }
        }

        $nuevoEstadoReserva = $reserva['estado'];
        if ($asistenciaEstado === 'no_asistio' && $liberarCancha) {
            $nuevoEstadoReserva = 'cancelada';
            if ($notas === null || $notas === '') {
                $notas = 'Cancha liberada administrativamente por inasistencia.';
            }
        }

        $sql = "UPDATE {$this->tabla} SET
                    asistencia_estado = ?,
                    asistencia_hora = ?,
                    asistencia_notas = ?,
                    asistencia_registrada_por = ?,
                    asistencia_updated_at = NOW(),
                    estado = ?,
                    updated_at = NOW()
                WHERE id = ?";

        try {
            $stmt = $this->query($sql, [
                $asistenciaEstado,
                $hora,
                $notas,
                $adminId,
                $nuevoEstadoReserva,
                $id
            ]);
        } catch (\PDOException $e) {
            return ['ok' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()];
        }

        return [
            'ok' => true,
            'mensaje' => 'Asistencia registrada correctamente.',
            'asistencia_estado' => $asistenciaEstado,
            'estado_reserva' => $nuevoEstadoReserva
        ];
    }

    /**
     * Reprograma una reserva a una nueva fecha, hora o cancha cumpliendo la política de anticipación
     *
     * @param int $id
     * @param int $nuevaCanchaId
     * @param string $nuevaFecha
     * @param string $nuevaHoraInicio
     * @param float $duracionHoras
     * @param string $motivo
     * @param int $adminId
     * @param bool $forzarFueraDePlazo
     * @return array{ok: bool, mensaje?: string, error?: string}
     */
    public function reprogramar(
        int $id,
        int $nuevaCanchaId,
        string $nuevaFecha,
        string $nuevaHoraInicio,
        float $duracionHoras,
        string $motivo,
        int $adminId,
        bool $forzarFueraDePlazo = false
    ): array {
        $reserva = $this->obtenerPorId($id);
        if (!$reserva) {
            return ['ok' => false, 'error' => 'Reserva no encontrada.'];
        }

        if (in_array($reserva['estado'], ['cancelada', 'finalizada'], true)) {
            return ['ok' => false, 'error' => 'No se puede reprogramar una reserva cancelada o finalizada.'];
        }

        // 1. Validar política de 1 hora de anticipación respecto al inicio programado
        $tz = new \DateTimeZone('America/Lima');
        $ahora = new \DateTime('now', $tz);
        $ahoraTs = $ahora->getTimestamp();

        $inicioActualDt = \DateTime::createFromFormat('Y-m-d H:i:s', "{$reserva['fecha']} {$reserva['hora_inicio']}", $tz)
            ?: \DateTime::createFromFormat('Y-m-d H:i', "{$reserva['fecha']} {$reserva['hora_inicio']}", $tz)
            ?: new \DateTime("{$reserva['fecha']} {$reserva['hora_inicio']}", $tz);
        $inicioActualTs = $inicioActualDt->getTimestamp();

        $segundosRestantes = $inicioActualTs - $ahoraTs;
        if (!$forzarFueraDePlazo && $segundosRestantes < 3600) {
            $minutosFaltantes = max(0, (int)floor($segundosRestantes / 60));
            return [
                'ok' => false,
                'error' => "La política de Top Gol requiere solicitar la reprogramación con al menos 1 hora de anticipación. Faltan {$minutosFaltantes} minutos para el inicio."
            ];
        }

        // 2. Calcular nueva hora fin
        if (strlen($nuevaHoraInicio) === 5) {
            $nuevaHoraInicio .= ':00';
        }
        $minutosDuracion = (int)round($duracionHoras * 60);
        $nuevaInicioDt = \DateTime::createFromFormat('H:i:s', $nuevaHoraInicio, $tz)
            ?: \DateTime::createFromFormat('H:i', $nuevaHoraInicio, $tz);
        if (!$nuevaInicioDt) {
            return ['ok' => false, 'error' => 'Hora de inicio inválida.'];
        }
        $nuevaFinDt = clone $nuevaInicioDt;
        $nuevaFinDt->modify("+{$minutosDuracion} minutes");
        $nuevaHoraFin = $nuevaFinDt->format('H:i:s');

        // El NUEVO horario no puede estar en el pasado y debe respetar el paso (:00/:30).
        // Si no cambian fecha ni hora (solo cancha o duración), un horario antiguo con minutos raros se conserva.
        $mismoInicio = ($nuevaFecha === (string)$reserva['fecha'] && substr($nuevaHoraInicio, 0, 5) === substr((string)$reserva['hora_inicio'], 0, 5));
        if (!$mismoInicio) {
            $v = validarHorarioReserva($nuevaFecha, $nuevaHoraInicio, $duracionHoras);
            if (!$v['ok']) {
                return ['ok' => false, 'error' => (string)$v['error']];
            }
        }

        // 3-5. Comprobar disponibilidad (excluyendo la propia reserva), recalcular precio y guardar:
        //      todo dentro de una transacción con la cancha de destino bloqueada (evita doble reserva)
        $canchaModel = new Cancha();
        $canchaDestino = $canchaModel->obtenerPorId($nuevaCanchaId);
        if (!$canchaDestino) {
            return ['ok' => false, 'error' => 'La cancha seleccionada no existe.'];
        }
        $precioHora = (float)$canchaDestino['precio_hora'];
        $nuevoTotal = round($duracionHoras * $precioHora, 2);

        $sql = "UPDATE {$this->tabla} SET
                    reprogramado_de_cancha_id = ?,
                    reprogramado_de_fecha = ?,
                    reprogramado_de_hora = ?,
                    reprogramado_por = ?,
                    reprogramado_at = NOW(),
                    motivo_reprogramacion = ?,
                    cancha_id = ?,
                    fecha = ?,
                    hora_inicio = ?,
                    hora_fin = ?,
                    duracion_horas = ?,
                    total_pago = ?,
                    asistencia_estado = 'sin_registrar',
                    asistencia_hora = NULL,
                    asistencia_notas = NULL,
                    updated_at = NOW()
                WHERE id = ?";

        if ($this->inTransaction()) {
            return ['ok' => false, 'error' => 'Ya hay una operación en curso. Intenta de nuevo.'];
        }
        $this->beginTransaction();
        try {
            $bloq = $this->bloquearCanchas([$nuevaCanchaId]);
            $this->select("SELECT id FROM {$this->tabla} WHERE id = ? FOR UPDATE", [$id]);
            if (empty($bloq) || $bloq[0]['estado'] !== 'disponible' || $this->hayChoque($nuevaCanchaId, $nuevaFecha, $nuevaHoraInicio, $nuevaHoraFin, $id)) {
                $this->rollBack();
                return [
                    'ok' => false,
                    'error' => 'La cancha y horario seleccionados no están disponibles o colisionan con otra reserva confirmada.'
                ];
            }
            $this->query($sql, [
                (int)$reserva['cancha_id'],
                (string)$reserva['fecha'],
                (string)$reserva['hora_inicio'],
                $adminId,
                trim($motivo) !== '' ? trim($motivo) : 'Reprogramación solicitada por cliente',
                $nuevaCanchaId,
                $nuevaFecha,
                $nuevaHoraInicio,
                $nuevaHoraFin,
                $duracionHoras,
                $nuevoTotal,
                $id
            ]);
            $this->commit();
        } catch (\PDOException $e) {
            $this->rollBack();
            return ['ok' => false, 'error' => 'Error de base de datos al reprogramar: ' . $e->getMessage()];
        }

        return [
            'ok' => true,
            'mensaje' => 'Reserva reprogramada exitosamente.',
            'nuevo_total' => $nuevoTotal
        ];
    }

    /**
     * Registra un intento de contacto previo o resultado de comunicación con el cliente
     *
     * @param int $id
     * @param string $resultado
     * @param int|null $adminId
     * @return array{ok: bool, mensaje?: string}
     */
    public function registrarContacto(int $id, string $resultado, ?int $adminId = null): array {
        $sql = "UPDATE {$this->tabla} SET
                    contacto_realizado_at = NOW(),
                    contacto_resultado = ?,
                    updated_at = NOW()
                WHERE id = ?";
        try {
            $this->query($sql, [$resultado, $id]);
            return ['ok' => true, 'mensaje' => 'Contacto registrado con éxito.'];
        } catch (\PDOException $e) {
            return ['ok' => false, 'error' => 'Error al registrar contacto: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene métricas operativas directas de MySQL para una fecha específica
     *
     * @param string $fecha YYYY-MM-DD
     * @param bool $incluirDemo
     * @return array<string, mixed>
     */
    public function obtenerMetricasDia(string $fecha, bool $incluirDemo = false): array {
        $demoFilter = $incluirDemo ? '' : ' AND es_demo = 0';

        $sql = "SELECT
                    COUNT(*) AS total_dia,
                    SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) AS confirmadas,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) AS canceladas,
                    SUM(CASE WHEN estado = 'finalizada' THEN 1 ELSE 0 END) AS finalizadas,
                    COALESCE(SUM(CASE WHEN estado <> 'cancelada' THEN duracion_horas ELSE 0 END), 0) AS horas_cancha,
                    COALESCE(SUM(CASE WHEN estado <> 'cancelada' THEN total_pago ELSE 0 END), 0) AS total_contratado,
                    COUNT(DISTINCT CASE WHEN estado <> 'cancelada' THEN COALESCE(evento_grupo, CONCAT('ind_', id)) END) AS solicitudes_unicas
                FROM {$this->tabla}
                WHERE fecha = ?{$demoFilter}";
        $res = $this->selectOne($sql, [$fecha]);

        // Comprobantes por verificar globales y de la fecha
        $sqlRevGlobal = "SELECT COUNT(*) AS total FROM {$this->tabla} WHERE pago_estado = 'en_revision'{$demoFilter}";
        $resRevGlobal = $this->selectOne($sqlRevGlobal);

        $sqlRevDia = "SELECT COUNT(*) AS total FROM {$this->tabla} WHERE fecha = ? AND pago_estado = 'en_revision'{$demoFilter}";
        $resRevDia = $this->selectOne($sqlRevDia, [$fecha]);

        // Saldo pendiente de las reservas activas en esa fecha
        $sqlSaldo = "SELECT r.id, r.total_pago,
                            COALESCE((SELECT SUM(p.monto) FROM pagos p WHERE p.reserva_id = r.id AND p.estado = 'verificado'), 0) AS pagado
                     FROM {$this->tabla} r
                     WHERE r.fecha = ? AND r.estado IN ('pendiente', 'confirmada'){$demoFilter}";
        $filas = $this->select($sqlSaldo, [$fecha]);
        $saldoPendiente = 0.0;
        foreach ($filas as $f) {
            $saldoPendiente += max(0.0, (float)$f['total_pago'] - (float)$f['pagado']);
        }

        return [
            'fecha'                 => $fecha,
            'total_reservas'        => (int)($res['total_dia'] ?? 0),
            'confirmadas'           => (int)($res['confirmadas'] ?? 0),
            'pendientes'            => (int)($res['pendientes'] ?? 0),
            'canceladas'            => (int)($res['canceladas'] ?? 0),
            'finalizadas'           => (int)($res['finalizadas'] ?? 0),
            'horas_cancha'          => round((float)($res['horas_cancha'] ?? 0.0), 2),
            'total_contratado'      => round((float)($res['total_contratado'] ?? 0.0), 2),
            'solicitudes_unicas'    => (int)($res['solicitudes_unicas'] ?? 0),
            'comprobantes_revision' => (int)($resRevGlobal['total'] ?? 0),
            'comprobantes_dia'      => (int)($resRevDia['total'] ?? 0),
            'saldo_pendiente'       => round($saldoPendiente, 2),
        ];
    }

    /**
     * Reporte consolidado de reservas por periodo con distinción de eventos y horas-cancha
     *
     * @param string $desde
     * @param string $hasta
     * @param array<string, mixed> $filtros
     * @param bool $incluirDemo
     * @return array<string, mixed>
     */
    public function obtenerReporteReservas(
        string $desde,
        string $hasta,
        array $filtros = [],
        bool $incluirDemo = false
    ): array {
        $demoFilter = $incluirDemo ? '' : ' AND r.es_demo = 0';
        $params = [$desde, $hasta];
        $whereExtra = '';

        if (!empty($filtros['cancha_id'])) {
            $whereExtra .= ' AND r.cancha_id = ?';
            $params[] = (int)$filtros['cancha_id'];
        }
        if (!empty($filtros['origen'])) {
            $whereExtra .= ' AND r.origen = ?';
            $params[] = (string)$filtros['origen'];
        }
        if (!empty($filtros['estado'])) {
            $whereExtra .= ' AND r.estado = ?';
            $params[] = (string)$filtros['estado'];
        }

        // 1. Resumen global del periodo
        $sqlResumen = "SELECT
                            COUNT(*) AS total_reservas,
                            COUNT(DISTINCT COALESCE(r.evento_grupo, CONCAT('ind_', r.id))) AS solicitudes_unicas,
                            SUM(CASE WHEN r.evento_grupo IS NOT NULL THEN 1 ELSE 0 END) AS slots_eventos,
                            SUM(CASE WHEN r.estado = 'confirmada' THEN 1 ELSE 0 END) AS confirmadas,
                            SUM(CASE WHEN r.estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
                            SUM(CASE WHEN r.estado = 'cancelada' THEN 1 ELSE 0 END) AS canceladas,
                            SUM(CASE WHEN r.estado = 'finalizada' THEN 1 ELSE 0 END) AS finalizadas,
                            COALESCE(SUM(CASE WHEN r.estado <> 'cancelada' THEN r.duracion_horas ELSE 0 END), 0) AS horas_cancha,
                            COALESCE(SUM(CASE WHEN r.estado <> 'cancelada' THEN r.total_pago ELSE 0 END), 0) AS total_contratado
                       FROM {$this->tabla} r
                       WHERE r.fecha BETWEEN ? AND ?{$demoFilter}{$whereExtra}";
        $resumen = $this->selectOne($sqlResumen, $params);

        // 2. Desglose por Cancha (ocupación en horas-cancha y demanda)
        $sqlCanchas = "SELECT
                            c.id AS cancha_id,
                            c.nombre AS cancha_nombre,
                            c.tipo AS cancha_tipo,
                            COUNT(r.id) AS total_reservas,
                            COALESCE(SUM(CASE WHEN r.estado <> 'cancelada' THEN r.duracion_horas ELSE 0 END), 0) AS horas_cancha,
                            COALESCE(SUM(CASE WHEN r.estado <> 'cancelada' THEN r.total_pago ELSE 0 END), 0) AS monto_total,
                            SUM(CASE WHEN r.estado = 'confirmada' THEN 1 ELSE 0 END) AS confirmadas,
                            SUM(CASE WHEN r.estado = 'cancelada' THEN 1 ELSE 0 END) AS canceladas
                       FROM canchas c
                       LEFT JOIN {$this->tabla} r ON c.id = r.cancha_id
                            AND r.fecha BETWEEN ? AND ?{$demoFilter}{$whereExtra}
                       WHERE c.estado <> 'mantenimiento'
                       GROUP BY c.id, c.nombre, c.tipo
                       ORDER BY horas_cancha DESC, c.id ASC";
        $porCancha = $this->select($sqlCanchas, $params);

        // 3. Desglose por Canal de Origen (web, presencial, llamada, whatsapp)
        $sqlOrigen = "SELECT
                            COALESCE(r.origen, 'web') AS canal,
                            COUNT(r.id) AS total_reservas,
                            COUNT(DISTINCT COALESCE(r.evento_grupo, CONCAT('ind_', r.id))) AS solicitudes_unicas,
                            COALESCE(SUM(CASE WHEN r.estado <> 'cancelada' THEN r.duracion_horas ELSE 0 END), 0) AS horas_cancha,
                            COALESCE(SUM(CASE WHEN r.estado <> 'cancelada' THEN r.total_pago ELSE 0 END), 0) AS monto_total
                      FROM {$this->tabla} r
                      WHERE r.fecha BETWEEN ? AND ?{$demoFilter}{$whereExtra}
                      GROUP BY COALESCE(r.origen, 'web')
                      ORDER BY total_reservas DESC";
        $porOrigen = $this->select($sqlOrigen, $params);

        // 4. Demanda por franja horaria (picos de demanda 07:00 a 23:00)
        $sqlHoras = "SELECT
                        SUBSTRING(r.hora_inicio, 1, 2) AS hora_slot,
                        COUNT(r.id) AS total_reservas,
                        COALESCE(SUM(CASE WHEN r.estado <> 'cancelada' THEN r.duracion_horas ELSE 0 END), 0) AS horas_cancha
                     FROM {$this->tabla} r
                     WHERE r.fecha BETWEEN ? AND ? AND r.estado <> 'cancelada'{$demoFilter}{$whereExtra}
                     GROUP BY SUBSTRING(r.hora_inicio, 1, 2)
                     ORDER BY hora_slot ASC";
        $porHoras = $this->select($sqlHoras, $params);
        $mapHoras = [];
        foreach ($porHoras as $h) {
            $slot = sprintf('%02d:00', (int)$h['hora_slot']);
            $mapHoras[$slot] = [
                'hora'         => $slot,
                'reservas'     => (int)$h['total_reservas'],
                'horas_cancha' => round((float)$h['horas_cancha'], 2),
            ];
        }

        // 5. Lista detallada de reservas para la tabla y exportación
        $sqlDetalle = "SELECT
                            r.id,
                            r.fecha,
                            r.hora_inicio,
                            r.hora_fin,
                            r.duracion_horas,
                            r.total_pago,
                            r.estado,
                            r.origen,
                            r.evento_grupo,
                            r.pago_estado,
                            r.es_demo,
                            COALESCE(r.cliente_nombre, u.nombre) AS cliente_nombre,
                            COALESCE(r.contacto_telefono, u.telefono) AS cliente_telefono,
                            u.email AS cliente_email,
                            c.nombre AS cancha_nombre,
                            c.tipo AS cancha_tipo,
                            COALESCE((SELECT SUM(p.monto) FROM pagos p WHERE p.reserva_id = r.id AND p.estado = 'verificado'), 0) AS pagado_verificado,
                            GREATEST(0, r.total_pago - COALESCE((SELECT SUM(p.monto) FROM pagos p WHERE p.reserva_id = r.id AND p.estado = 'verificado'), 0)) AS saldo_pendiente
                       FROM {$this->tabla} r
                       INNER JOIN canchas c ON r.cancha_id = c.id
                       INNER JOIN usuarios u ON r.usuario_id = u.id
                       WHERE r.fecha BETWEEN ? AND ?{$demoFilter}{$whereExtra}
                       ORDER BY r.fecha DESC, r.hora_inicio DESC, r.id DESC";
        $lista = $this->select($sqlDetalle, $params);

        // Calcular saldo total acumulado de las reservas en la lista
        $saldoTotalPendiente = 0.0;
        $adelantosCobrados = 0.0;
        foreach ($lista as $item) {
            if ($item['estado'] !== 'cancelada') {
                $saldoTotalPendiente += (float)$item['saldo_pendiente'];
                $adelantosCobrados += (float)$item['pagado_verificado'];
            }
        }

        return [
            'desde'                  => $desde,
            'hasta'                  => $hasta,
            'filtros'                => $filtros,
            'incluir_demo'           => $incluirDemo,
            'total_reservas'         => (int)($resumen['total_reservas'] ?? 0),
            'solicitudes_unicas'     => (int)($resumen['solicitudes_unicas'] ?? 0),
            'slots_eventos'          => (int)($resumen['slots_eventos'] ?? 0),
            'confirmadas'            => (int)($resumen['confirmadas'] ?? 0),
            'pendientes'             => (int)($resumen['pendientes'] ?? 0),
            'canceladas'             => (int)($resumen['canceladas'] ?? 0),
            'finalizadas'            => (int)($resumen['finalizadas'] ?? 0),
            'horas_cancha'           => round((float)($resumen['horas_cancha'] ?? 0.0), 2),
            'total_contratado'       => round((float)($resumen['total_contratado'] ?? 0.0), 2),
            'adelantos_verificados'  => round($adelantosCobrados, 2),
            'saldo_pendiente_total'  => round($saldoTotalPendiente, 2),
            'por_cancha'             => $porCancha,
            'por_origen'             => $porOrigen,
            'por_horas'              => $mapHoras,
            'lista'                  => $lista,
        ];
    }
}