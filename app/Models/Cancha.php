<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * ====================================================================
 * TOP GOL - Modelo Cancha
 * ====================================================================
 * Gestiona el inventario de canchas deportivas, características físicas,
 * tarifas por hora y verificación de disponibilidad de horarios.
 */
class Cancha extends Model {
    protected string $tabla = 'canchas';

    /**
     * Obtiene todas las canchas registradas
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodas(): array {
        $sql = "SELECT * FROM {$this->tabla} ORDER BY id ASC";
        return $this->select($sql);
    }

    /**
     * Obtiene únicamente las canchas en estado 'disponible' para clientes
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerDisponibles(): array {
        $sql = "SELECT * FROM {$this->tabla} WHERE estado = 'disponible' ORDER BY id ASC";
        return $this->select($sql);
    }

    /**
     * Busca una cancha específica por ID
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT * FROM {$this->tabla} WHERE id = ? LIMIT 1";
        return $this->selectOne($sql, [$id]);
    }

    /**
     * Inserta una nueva cancha
     *
     * @param array<string, mixed> $datos
     * @return int
     */
    public function crear(array $datos): int {
        $sql = "INSERT INTO {$this->tabla} 
                (nombre, descripcion, precio_hora, capacidad, tipo, iluminacion, techada, estado, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $this->query($sql, [
            trim((string)$datos['nombre']),
            trim((string)($datos['descripcion'] ?? '')),
            (float)$datos['precio_hora'],
            (int)$datos['capacidad'],
            (string)$datos['tipo'],
            !empty($datos['iluminacion']) ? 1 : 0,
            !empty($datos['techada']) ? 1 : 0,
            (string)($datos['estado'] ?? 'disponible')
        ]);

        return $this->lastInsertId();
    }

    /**
     * Actualiza los datos de una cancha existente
     *
     * @param int $id
     * @param array<string, mixed> $datos
     * @return bool
     */
    public function actualizar(int $id, array $datos): bool {
        $sql = "UPDATE {$this->tabla} SET 
                nombre = ?, 
                descripcion = ?, 
                precio_hora = ?, 
                capacidad = ?, 
                tipo = ?, 
                iluminacion = ?, 
                techada = ?, 
                estado = ?, 
                updated_at = NOW() 
                WHERE id = ?";

        return $this->execute($sql, [
            trim((string)$datos['nombre']),
            trim((string)($datos['descripcion'] ?? '')),
            (float)$datos['precio_hora'],
            (int)$datos['capacidad'],
            (string)$datos['tipo'],
            !empty($datos['iluminacion']) ? 1 : 0,
            !empty($datos['techada']) ? 1 : 0,
            (string)($datos['estado'] ?? 'disponible'),
            $id
        ]);
    }

    /**
     * Elimina una cancha por su ID
     *
     * @param int $id
     * @return bool
     */
    public function eliminar(int $id): bool {
        $sql = "DELETE FROM {$this->tabla} WHERE id = ?";
        return $this->execute($sql, [$id]);
    }

    /**
     * Verifica si una cancha está libre en un rango de fecha y horas
     * (Excluyendo reservas canceladas y opcionalmente una reserva existente)
     *
     * @param int $canchaId
     * @param string $fecha Formato YYYY-MM-DD
     * @param string $horaInicio Formato HH:MM:SS o HH:MM
     * @param string $horaFin Formato HH:MM:SS o HH:MM
     * @param int|null $reservaIdExcluir ID de reserva a ignorar en caso de edición
     * @return bool True si la cancha está libre para reservar
     */
    public function estaDisponible(int $canchaId, string $fecha, string $horaInicio, string $horaFin, ?int $reservaIdExcluir = null): bool {
        // Verificar primero que la cancha esté en estado disponible
        $cancha = $this->obtenerPorId($canchaId);
        if (!$cancha || $cancha['estado'] !== 'disponible') {
            return false;
        }

        $sql = "SELECT COUNT(*) as traslapes FROM reservas 
                WHERE cancha_id = ? 
                AND fecha = ? 
                AND estado IN ('pendiente', 'confirmada') 
                AND hora_inicio < ? 
                AND hora_fin > ?";

        $params = [$canchaId, $fecha, $horaFin, $horaInicio];

        if ($reservaIdExcluir !== null) {
            $sql .= " AND id != ?";
            $params[] = $reservaIdExcluir;
        }

        $resultado = $this->selectOne($sql, $params);
        $traslapes = (int)($resultado['traslapes'] ?? 0);

        return $traslapes === 0;
    }

    /**
     * Cambia solo el estado de una cancha (disponible / mantenimiento)
     */
    public function cambiarEstado(int $id, string $estado): bool {
        if (!in_array($estado, ['disponible', 'mantenimiento'], true)) {
            return false;
        }
        $sql = "UPDATE {$this->tabla} SET estado = ?, updated_at = NOW() WHERE id = ?";
        return $this->execute($sql, [$estado, $id]);
    }

    /**
     * Retorna el número total de canchas
     */
    public function contarCanchas(): int {
        $sql = "SELECT COUNT(*) as total FROM {$this->tabla}";
        $res = $this->selectOne($sql);
        return (int)($res['total'] ?? 0);
    }

    /**
     * Obtiene canchas filtradas por tipo y disponibilidad en fecha/horario
     *
     * @param string $fecha Formato YYYY-MM-DD
     * @param string $horario Formato HH:MM (opcional)
     * @param string $tipo Tipo de cancha (futbol_5, futbol_7, futbol_11) (opcional)
     * @return array<int, array<string, mixed>>
     */
    public function obtenerConFiltros(string $fecha, string $horario = '', string $tipo = ''): array {
        $sql = "SELECT * FROM {$this->tabla} WHERE estado = 'disponible'";
        $params = [];

        if ($tipo !== '') {
            $sql .= " AND tipo = ?";
            $params[] = $tipo;
        }

        $sql .= " ORDER BY tipo ASC, nombre ASC";

        $todas = $this->select($sql, $params);

        // Si no hay horario, devolver todas las disponibles (filtradas por tipo si aplica)
        if ($horario === '') {
            return $todas;
        }

        // Filtrar por disponibilidad en el horario seleccionado
        // Calcular hora_fin asumiendo 1 hora de duración
        $horaInicio = $horario . ':00';
        $horaFin = date('H:i:s', strtotime($horaInicio . ' +1 hour'));

        $disponibles = [];
        foreach ($todas as $cancha) {
            if ($this->estaDisponible((int)$cancha['id'], $fecha, $horaInicio, $horaFin)) {
                $disponibles[] = $cancha;
            }
        }

        return $disponibles;
    }
}