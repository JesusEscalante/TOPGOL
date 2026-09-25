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
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodas(): array {
        $sql = "SELECT r.*, 
                       u.nombre AS usuario_nombre, 
                       u.email AS usuario_email, 
                       u.telefono AS usuario_telefono,
                       c.nombre AS cancha_nombre, 
                       c.tipo AS cancha_tipo, 
                       c.precio_hora AS cancha_precio_hora
                FROM {$this->tabla} r
                INNER JOIN usuarios u ON r.usuario_id = u.id
                INNER JOIN canchas c ON r.cancha_id = c.id
                ORDER BY r.fecha DESC, r.hora_inicio DESC";

        return $this->select($sql);
    }

    /**
     * Obtiene una reserva por su ID con datos relacionados
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
                       c.precio_hora AS cancha_precio_hora
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
        if (!in_array($metodoPago, ['yape', 'transferencia_bcp', 'efectivo'], true)) {
            $metodoPago = null;
        }

        $pagoEstado = (string)($datos['pago_estado'] ?? 'pendiente');
        if (!in_array($pagoEstado, ['pendiente', 'en_revision', 'verificado', 'rechazado'], true)) {
            $pagoEstado = 'pendiente';
        }

        $sql = "INSERT INTO {$this->tabla}
                (usuario_id, cancha_id, fecha, hora_inicio, hora_fin, duracion_horas, total_pago, estado, observaciones,
                 metodo_pago, adelanto_monto, cliente_nombre, contacto_telefono, evento_grupo, pago_estado,
                 comprobante_ruta, comprobante_nombre, comprobante_tipo, comprobante_size, comprobante_subido_at,
                 created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $this->query($sql, [
            (int)$datos['usuario_id'],
            (int)$datos['cancha_id'],
            (string)$datos['fecha'],
            (string)$datos['hora_inicio'],
            (string)$datos['hora_fin'],
            (float)$datos['duracion_horas'],
            (float)$datos['total_pago'],
            (string)($datos['estado'] ?? 'pendiente'),
            trim((string)($datos['observaciones'] ?? '')),
            $metodoPago,
            (float)($datos['adelanto_monto'] ?? 20.00),
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

        return $this->lastInsertId();
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
            $sql = "UPDATE {$this->tabla} SET pago_estado = ?, pago_verificado_at = NOW(), pago_verificado_por = ?, updated_at = NOW() WHERE id = ?";
            return $this->execute($sql, [$estado, $verificadoPor, $id]);
        }

        $sql = "UPDATE {$this->tabla} SET pago_estado = ?, updated_at = NOW() WHERE id = ?";
        return $this->execute($sql, [$estado, $id]);
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
        $sql = "UPDATE {$this->tabla} SET estado = ?, updated_at = NOW() WHERE id = ?";
        return $this->execute($sql, [$estado, $id]);
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
            return $this->execute($sql, [$id]);
        }

        $sql = "UPDATE {$this->tabla} SET estado = 'cancelada', updated_at = NOW() 
                WHERE id = ? AND usuario_id = ? AND estado IN ('pendiente', 'confirmada')";
        return $this->execute($sql, [$id, $usuarioId]);
    }

    /**
     * Obtiene métricas y estadísticas para el panel administrativo
     *
     * @return array<string, mixed>
     */
    public function obtenerEstadisticas(): array {
        $sql = "SELECT
                    COUNT(*) AS total_reservas,
                    SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) AS confirmadas,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) AS canceladas,
                    SUM(CASE WHEN estado = 'finalizada' THEN 1 ELSE 0 END) AS finalizadas,
                    SUM(CASE WHEN estado IN ('confirmada', 'finalizada') THEN total_pago ELSE 0 END) AS ingresos_totales,
                    SUM(CASE WHEN pago_estado = 'en_revision' THEN 1 ELSE 0 END) AS pagos_en_revision,
                    SUM(CASE WHEN pago_estado = 'verificado' THEN adelanto_monto ELSE 0 END) AS adelantos_verificados
                FROM {$this->tabla}";

        $res = $this->selectOne($sql);
        return [
            'total_reservas'      => (int)($res['total_reservas'] ?? 0),
            'confirmadas'         => (int)($res['confirmadas'] ?? 0),
            'pendientes'          => (int)($res['pendientes'] ?? 0),
            'canceladas'          => (int)($res['canceladas'] ?? 0),
            'finalizadas'         => (int)($res['finalizadas'] ?? 0),
            'ingresos_totales'    => (float)($res['ingresos_totales'] ?? 0.0),
            'pagos_en_revision'   => (int)($res['pagos_en_revision'] ?? 0),
            'adelantos_verificados' => (float)($res['adelantos_verificados'] ?? 0.0)
        ];
    }

    /**
     * Retorna los bloques horarios ocupados para una cancha en una fecha específica
     *
     * @param int $canchaId
     * @param string $fecha
     * @return array<int, array{hora_inicio: string, hora_fin: string, estado: string}>
     */
    public function obtenerHorariosOcupados(int $canchaId, string $fecha): array {
        $sql = "SELECT hora_inicio, hora_fin, estado
                FROM {$this->tabla}
                WHERE cancha_id = ? AND fecha = ? AND estado IN ('pendiente', 'confirmada')
                ORDER BY hora_inicio ASC";

        return $this->select($sql, [$canchaId, $fecha]);
    }

    /**
     * Retorna los slots horarios ocupados (formato 'HH:00') para una cancha
     * en una fecha, expandiendo las reservas en bloques de 1 hora (07:00-23:00).
     *
     * @param int $canchaId
     * @param string $fecha Formato YYYY-MM-DD
     * @return array<int, string>
     */
    public function obtenerSlotsOcupados(int $canchaId, string $fecha): array {
        $bloques = $this->obtenerHorariosOcupados($canchaId, $fecha);
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
}