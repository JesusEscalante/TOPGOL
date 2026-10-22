<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Espacio extends Model {
    protected string $tabla = 'solicitudes_espacios';

    public function obtenerTodas(): array {
        $sql = "SELECT s.*, e.nombre as espacio_nombre, u.email as usuario_email
                FROM solicitudes_espacios s
                JOIN espacios_eventos e ON s.espacio_id = e.id
                JOIN usuarios u ON s.usuario_id = u.id
                ORDER BY s.fecha DESC, s.hora_inicio DESC";
        return $this->select($sql);
    }

    /**
     * Guarda la solicitud y sus canchas opcionales en una sola transacción.
     *
     * @param array<string, mixed> $datos Puede incluir 'hora_fin', 'total_estimado' y
     *                                    'canchas' (lista de [cancha_id, precio_hora, subtotal])
     */
    public function guardarSolicitud(array $datos): int {
        $sql = "INSERT INTO solicitudes_espacios (espacio_id, usuario_id, fecha, hora_inicio, hora_fin, duracion_horas, contacto_nombre, contacto_telefono, observaciones, estado, total_estimado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $propia = !$this->inTransaction();
        if ($propia) {
            $this->beginTransaction();
        }
        try {
            $this->execute($sql, [
                $datos['espacio_id'],
                $datos['usuario_id'],
                $datos['fecha'],
                $datos['hora_inicio'],
                $datos['hora_fin'] ?? null,
                $datos['duracion_horas'],
                $datos['contacto_nombre'],
                $datos['contacto_telefono'],
                $datos['observaciones'],
                $datos['estado'],
                $datos['total_estimado'] ?? null,
            ]);
            $id = $this->lastInsertId();
            foreach (($datos['canchas'] ?? []) as $c) {
                $this->execute(
                    "INSERT INTO solicitudes_espacios_canchas (solicitud_id, cancha_id, precio_hora, subtotal) VALUES (?, ?, ?, ?)",
                    [$id, (int)$c['cancha_id'], $c['precio_hora'], $c['subtotal']]
                );
            }
            if ($propia) {
                $this->commit();
            }
            return $id;
        } catch (\Throwable $e) {
            if ($propia && $this->inTransaction()) {
                $this->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Canchas incluidas en las solicitudes indicadas, agrupadas por solicitud.
     *
     * @param array<int, int> $solicitudIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function obtenerCanchasPorSolicitudes(array $solicitudIds): array {
        $solicitudIds = array_values(array_filter(array_map('intval', $solicitudIds)));
        if (empty($solicitudIds)) {
            return [];
        }
        try {
            $in = implode(',', array_fill(0, count($solicitudIds), '?'));
            $filas = $this->select(
                "SELECT sc.solicitud_id, sc.cancha_id, sc.precio_hora, sc.subtotal, c.nombre
                 FROM solicitudes_espacios_canchas sc
                 JOIN canchas c ON c.id = sc.cancha_id
                 WHERE sc.solicitud_id IN ({$in}) ORDER BY c.nombre",
                $solicitudIds
            );
        } catch (\PDOException $e) {
            return [];
        }
        $mapa = [];
        foreach ($filas as $f) {
            $mapa[(int)$f['solicitud_id']][] = $f;
        }
        return $mapa;
    }

    /**
     * Solicitudes activas (no rechazadas ni canceladas) entre dos fechas, para el calendario.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerActivasEntre(string $desde, string $hasta): array {
        return $this->select(
            "SELECT s.*, e.nombre AS espacio_nombre
             FROM solicitudes_espacios s
             JOIN espacios_eventos e ON s.espacio_id = e.id
             WHERE s.fecha BETWEEN ? AND ? AND s.estado IN ('pendiente','cotizado','confirmado')
             ORDER BY s.fecha, s.hora_inicio",
            [$desde, $hasta]
        );
    }

    /** Último motivo de fallo: 'ocupado' | 'cancha_no_disponible' | 'error' | null */
    public ?string $ultimoCodigo = null;
    /** Cancha que provocó el choque (0 = el propio espacio) */
    public ?int $ultimaCancha = null;

    /**
     * ¿El espacio tiene otra solicitud CONFIRMADA que se cruza con el intervalo? (lectura con bloqueo)
     */
    public function hayChoqueEspacio(int $espacioId, string $fecha, string $horaInicio, string $horaFin, ?int $excluirId = null): bool {
        $sql = "SELECT id FROM solicitudes_espacios
                WHERE espacio_id = ? AND fecha = ? AND estado = 'confirmado'
                  AND hora_inicio < ?
                  AND COALESCE(hora_fin, ADDTIME(hora_inicio, SEC_TO_TIME(ROUND(duracion_horas * 3600)))) > ?";
        $params = [$espacioId, $fecha, $horaFin, $horaInicio];
        if ($excluirId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excluirId;
        }
        return !empty($this->select($sql . ' LIMIT 1 FOR UPDATE', $params));
    }

    /**
     * Guarda una solicitud de cumpleaños/reunión de forma atómica: bloquea las canchas agregadas
     * (orden ascendente) y el espacio, vuelve a comprobar los choques dentro de la transacción y
     * guarda la solicitud con sus canchas. Todo o nada.
     *
     * @param array<string, mixed> $datos Los de guardarSolicitud() (incluye 'hora_fin' y 'canchas')
     * @return array{ok: bool, id: int, codigo: ?string, cancha_id: ?int, error: ?string}
     */
    public function guardarSolicitudAtomica(array $datos): array {
        $this->ultimoCodigo = null;
        $this->ultimaCancha = null;
        $fallo = function (string $codigo, ?int $cancha = null, ?string $msg = null): array {
            $this->ultimoCodigo = $codigo;
            $this->ultimaCancha = $cancha;
            return ['ok' => false, 'id' => 0, 'codigo' => $codigo, 'cancha_id' => $cancha, 'error' => $msg];
        };
        if ($this->inTransaction()) {
            return $fallo('error', null, 'Ya hay una transacción abierta.');
        }
        $reserva = new Reserva();
        $cids = array_values(array_unique(array_map(static fn($c) => (int)$c['cancha_id'], $datos['canchas'] ?? [])));
        $ini = (string)$datos['hora_inicio'];
        $fin = (string)$datos['hora_fin'];

        $this->beginTransaction();
        try {
            $bloq = $reserva->bloquearCanchas($cids);
            if (count($bloq) !== count($cids)) {
                $this->rollBack();
                return $fallo('cancha_no_disponible');
            }
            foreach ($bloq as $c) {
                if ($c['estado'] !== 'disponible') {
                    $this->rollBack();
                    return $fallo('cancha_no_disponible', (int)$c['id']);
                }
            }
            $this->select("SELECT id FROM espacios_eventos WHERE id = ? FOR UPDATE", [(int)$datos['espacio_id']]);
            if ($this->hayChoqueEspacio((int)$datos['espacio_id'], (string)$datos['fecha'], $ini, $fin)) {
                $this->rollBack();
                return $fallo('ocupado', 0);
            }
            foreach ($cids as $cid) {
                if ($reserva->hayChoque($cid, (string)$datos['fecha'], $ini, $fin)) {
                    $this->rollBack();
                    return $fallo('ocupado', $cid);
                }
            }
            $id = $this->guardarSolicitud($datos);
            $this->commit();
            return ['ok' => true, 'id' => $id, 'codigo' => null, 'cancha_id' => null, 'error' => null];
        } catch (\Throwable $e) {
            $this->rollBack();
            return $fallo('error', null, $e->getMessage());
        }
    }

    /**
     * Cambia el estado de una solicitud. Si pasa a 'confirmado' vuelve a comprobar, dentro de una
     * transacción con bloqueo, que el espacio y sus canchas sigan libres (es el momento en que
     * la solicitud pasa a ocupar el horario).
     *
     * @return array{ok: bool, codigo: ?string, cancha_id: ?int, error: ?string}
     */
    public function cambiarEstadoAtomico(int $id, string $estado, ?float $precio): array {
        $this->ultimoCodigo = null;
        $this->ultimaCancha = null;
        $fallo = function (string $codigo, ?int $cancha = null, ?string $msg = null): array {
            $this->ultimoCodigo = $codigo;
            $this->ultimaCancha = $cancha;
            return ['ok' => false, 'codigo' => $codigo, 'cancha_id' => $cancha, 'error' => $msg];
        };
        if ($this->inTransaction()) {
            return $fallo('error', null, 'Ya hay una transacción abierta.');
        }
        $reserva = new Reserva();
        $this->beginTransaction();
        try {
            if ($estado === 'confirmado') {
                $sol = $this->selectOne("SELECT * FROM solicitudes_espacios WHERE id = ?", [$id]);
                if (!$sol) {
                    $this->rollBack();
                    return $fallo('error', null, 'La solicitud no existe.');
                }
                $cids = array_map(static fn($f) => (int)$f['cancha_id'], $this->select("SELECT cancha_id FROM solicitudes_espacios_canchas WHERE solicitud_id = ?", [$id]));
                $reserva->bloquearCanchas($cids);
                $this->select("SELECT id FROM espacios_eventos WHERE id = ? FOR UPDATE", [(int)$sol['espacio_id']]);
                $this->select("SELECT id FROM solicitudes_espacios WHERE id = ? FOR UPDATE", [$id]);
                $ini = (string)$sol['hora_inicio'];
                $fin = (string)($sol['hora_fin'] ?: date('H:i:s', strtotime('1970-01-01 ' . $ini) + (int)round((float)$sol['duracion_horas'] * 3600)));
                if ($this->hayChoqueEspacio((int)$sol['espacio_id'], (string)$sol['fecha'], $ini, $fin, $id)) {
                    $this->rollBack();
                    return $fallo('ocupado', 0);
                }
                foreach ($cids as $cid) {
                    if ($reserva->hayChoque($cid, (string)$sol['fecha'], $ini, $fin, null, false, $id)) {
                        $this->rollBack();
                        return $fallo('ocupado', $cid);
                    }
                }
            }
            $this->actualizarEstadoYPrecio($id, $estado, $precio);
            $this->commit();
            return ['ok' => true, 'codigo' => null, 'cancha_id' => null, 'error' => null];
        } catch (\Throwable $e) {
            $this->rollBack();
            return $fallo('error', null, $e->getMessage());
        }
    }

    public function actualizarEstado(int $id, string $estado): bool {
        $sql = "UPDATE solicitudes_espacios SET estado = ?, updated_at = NOW() WHERE id = ?";
        return $this->execute($sql, [$estado, $id]);
    }

    public function actualizarEstadoYPrecio(int $id, string $estado, ?float $precio): bool {
        $sql = "UPDATE solicitudes_espacios SET estado = ?, precio_cotizado = ?, updated_at = NOW() WHERE id = ?";
        return $this->execute($sql, [$estado, $precio, $id]);
    }

    public function obtenerPorUsuario(int $usuarioId): array {
        $sql = "SELECT s.*, e.nombre as espacio_nombre
                FROM solicitudes_espacios s
                JOIN espacios_eventos e ON s.espacio_id = e.id
                WHERE s.usuario_id = ?
                ORDER BY s.fecha DESC, s.hora_inicio DESC";
        return $this->select($sql, [$usuarioId]);
    }

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT s.*, e.nombre as espacio_nombre, u.email as usuario_email
                FROM solicitudes_espacios s
                JOIN espacios_eventos e ON s.espacio_id = e.id
                JOIN usuarios u ON s.usuario_id = u.id
                WHERE s.id = ?";
        $resultado = $this->select($sql, [$id]);
        return !empty($resultado) ? $resultado[0] : null;
    }

    public function estaDisponible(int $espacioId, string $fecha, string $horaInicio, float $duracionHoras, int $ignorarSolicitudId = 0): bool {
        $sql = "SELECT id, hora_inicio, duracion_horas FROM solicitudes_espacios
                WHERE espacio_id = ?
                AND fecha = ?
                AND estado = 'confirmado'
                AND id != ?";
        $confirmadas = $this->select($sql, [$espacioId, $fecha, $ignorarSolicitudId]);

        $tsIniNuevo = strtotime("1970-01-01 {$horaInicio}");
        $tsFinNuevo = $tsIniNuevo + (int)round($duracionHoras * 3600);

        foreach ($confirmadas as $c) {
            $tsIniExistente = strtotime("1970-01-01 {$c['hora_inicio']}");
            $tsFinExistente = $tsIniExistente + (int)round((float)$c['duracion_horas'] * 3600);

            if ($tsIniNuevo < $tsFinExistente && $tsFinNuevo > $tsIniExistente) {
                return false;
            }
        }
        return true;
    }
}
