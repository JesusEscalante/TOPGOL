<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * ====================================================================
 * TOP GOL - Modelo de Auditoría y Bitácora Administrativa (RF32)
 * ====================================================================
 * Registra de forma inmutable las operaciones sensibles realizadas
 * por los administradores y permite consultarlas con filtros avanzados.
 */
class Auditoria extends Model
{
    protected string $tabla = 'auditoria_logs';

    /**
     * Registra un evento en la bitácora de auditoría administrativa.
     * Nunca lanza excepciones para no interrumpir el flujo principal.
     *
     * @param string $modulo Módulo funcional (ej. 'reservas', 'pagos', 'canchas', 'productos', 'inventario')
     * @param string $accion Acción ejecutada (ej. 'crear', 'actualizar', 'eliminar', 'verificar_pago')
     * @param string $descripcion Resumen legible de la operación
     * @param string|null $entidad Tipo de entidad afectada (ej. 'reserva', 'cancha', 'producto')
     * @param string|int|null $entidadId Identificador de la entidad afectada
     * @param array<string, mixed>|null $datos Contexto adicional serializable en JSON
     * @return bool
     */
    public function registrar(
        string $modulo,
        string $accion,
        string $descripcion,
        ?string $entidad = null,
        string|int|null $entidadId = null,
        ?array $datos = null
    ): bool {
        try {
            $usuarioId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
            $usuarioNombre = isset($_SESSION['usuario_nombre']) && trim((string)$_SESSION['usuario_nombre']) !== ''
                ? trim((string)$_SESSION['usuario_nombre'])
                : 'Sistema / Administrador';

            $ip = $this->obtenerIpCliente();
            $ua = substr(trim((string)($_SERVER['HTTP_USER_AGENT'] ?? 'CLI / Desconocido')), 0, 255);
            $datosJson = ($datos !== null && !empty($datos))
                ? json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null;

            $sql = "INSERT INTO {$this->tabla}
                    (usuario_id, usuario_nombre, modulo, accion, entidad, entidad_id, descripcion, datos_json, ip_address, user_agent, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            return $this->execute($sql, [
                $usuarioId,
                mb_substr($usuarioNombre, 0, 100),
                mb_substr(strtolower(trim($modulo)), 0, 50),
                mb_substr(strtolower(trim($accion)), 0, 50),
                $entidad !== null ? mb_substr(strtolower(trim($entidad)), 0, 50) : null,
                $entidadId !== null ? mb_substr((string)$entidadId, 0, 64) : null,
                mb_substr(trim($descripcion), 0, 255),
                $datosJson,
                $ip,
                $ua,
            ]);
        } catch (\Throwable $e) {
            error_log('[TOP GOL][Auditoria] Error al registrar log: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el listado de registros de auditoría aplicando filtros opcionales.
     *
     * @param array<string, mixed> $filtros
     * @param int $limite
     * @return array<int, array<string, mixed>>
     */
    public function obtenerListado(array $filtros = [], int $limite = 250): array
    {
        $where = [];
        $params = [];

        if (!empty($filtros['modulo'])) {
            $where[] = 'a.modulo = ?';
            $params[] = strtolower(trim((string)$filtros['modulo']));
        }

        if (!empty($filtros['accion'])) {
            $where[] = 'a.accion = ?';
            $params[] = strtolower(trim((string)$filtros['accion']));
        }

        if (!empty($filtros['usuario_id'])) {
            $where[] = 'a.usuario_id = ?';
            $params[] = (int)$filtros['usuario_id'];
        }

        if (!empty($filtros['desde']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$filtros['desde'])) {
            $where[] = 'DATE(a.created_at) >= ?';
            $params[] = (string)$filtros['desde'];
        }

        if (!empty($filtros['hasta']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$filtros['hasta'])) {
            $where[] = 'DATE(a.created_at) <= ?';
            $params[] = (string)$filtros['hasta'];
        }

        if (!empty($filtros['q'])) {
            $q = '%' . trim((string)$filtros['q']) . '%';
            $where[] = '(a.descripcion LIKE ? OR a.usuario_nombre LIKE ? OR a.entidad_id LIKE ? OR a.ip_address LIKE ?)';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        $sqlWhere = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $limiteSeguro = max(1, min(1000, $limite));

        $sql = "SELECT a.*, u.email AS usuario_email, u.rol AS usuario_rol
                FROM {$this->tabla} a
                LEFT JOIN usuarios u ON a.usuario_id = u.id
                {$sqlWhere}
                ORDER BY a.id DESC
                LIMIT {$limiteSeguro}";

        return $this->select($sql, $params);
    }

    /**
     * Obtiene indicadores estadísticos de la bitácora de auditoría.
     *
     * @return array{total: int, hoy: int, semana: int, modulos: int}
     */
    public function obtenerEstadisticas(): array
    {
        $row = $this->selectOne("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS hoy,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS semana,
                COUNT(DISTINCT modulo) AS modulos
            FROM {$this->tabla}
        ");

        return [
            'total'   => (int)($row['total'] ?? 0),
            'hoy'     => (int)($row['hoy'] ?? 0),
            'semana'  => (int)($row['semana'] ?? 0),
            'modulos' => (int)($row['modulos'] ?? 0),
        ];
    }

    /**
     * Obtiene la lista de administradores que han registrado eventos en la bitácora.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerUsuariosConActividad(): array
    {
        return $this->select("
            SELECT DISTINCT usuario_id, usuario_nombre
            FROM {$this->tabla}
            WHERE usuario_id IS NOT NULL
            ORDER BY usuario_nombre ASC
        ");
    }

    /**
     * Detecta la dirección IP real del cliente o administrador conectado.
     * Prioriza REMOTE_ADDR para evitar suplantación mediante cabeceras HTTP_X_FORWARDED_FOR falsas.
     */
    private function obtenerIpCliente(): string
    {
        $remote = trim((string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
        $proxiesLocales = ['127.0.0.1', '::1'];

        if (in_array($remote, $proxiesLocales, true) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '127.0.0.1';
    }
}
