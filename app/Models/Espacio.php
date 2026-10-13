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

    public function guardarSolicitud(array $datos): int {
        $sql = "INSERT INTO solicitudes_espacios (espacio_id, usuario_id, fecha, hora_inicio, duracion_horas, contacto_nombre, contacto_telefono, observaciones, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->execute($sql, [
            $datos['espacio_id'],
            $datos['usuario_id'],
            $datos['fecha'],
            $datos['hora_inicio'],
            $datos['duracion_horas'],
            $datos['contacto_nombre'],
            $datos['contacto_telefono'],
            $datos['observaciones'],
            $datos['estado']
        ]);
        return $this->lastInsertId();
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
