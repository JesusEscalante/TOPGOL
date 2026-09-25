<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Notificacion extends Model {
    protected string $tabla = 'notificaciones';

    public function crear(int $usuarioId, string $tipo, string $titulo, string $mensaje, ?string $link = null): int {
        $sql = "INSERT INTO {$this->tabla} (usuario_id, tipo, titulo, mensaje, link, leida, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())";
        $this->query($sql, [$usuarioId, $tipo, $titulo, $mensaje, $link]);
        return $this->lastInsertId();
    }

    public function obtenerNoLeidas(int $usuarioId, int $limite = 10): array {
        $sql = "SELECT * FROM {$this->tabla} WHERE usuario_id = ? AND leida = 0 ORDER BY id DESC LIMIT {$limite}";
        return $this->select($sql, [$usuarioId]);
    }

    public function contarNoLeidas(int $usuarioId): int {
        $sql = "SELECT COUNT(*) as total FROM {$this->tabla} WHERE usuario_id = ? AND leida = 0";
        $res = $this->selectOne($sql, [$usuarioId]);
        return (int)($res['total'] ?? 0);
    }

    public function listar(int $usuarioId, int $limite = 20): array {
        $sql = "SELECT * FROM {$this->tabla} WHERE usuario_id = ? ORDER BY id DESC LIMIT {$limite}";
        return $this->select($sql, [$usuarioId]);
    }

    public function marcarLeida(int $id, int $usuarioId): bool {
        $sql = "UPDATE {$this->tabla} SET leida = 1 WHERE id = ? AND usuario_id = ?";
        return $this->execute($sql, [$id, $usuarioId]);
    }

    public function marcarTodasLeidas(int $usuarioId): bool {
        $sql = "UPDATE {$this->tabla} SET leida = 1 WHERE usuario_id = ? AND leida = 0";
        return $this->execute($sql, [$usuarioId]);
    }

    /** Crea notificación para todos los admins */
    public function notificarAdmins(string $tipo, string $titulo, string $mensaje, ?string $link = null): void {
        $admins = $this->select("SELECT id FROM usuarios WHERE rol = 'admin'");
        foreach ($admins as $a) {
            $this->crear((int)$a['id'], $tipo, $titulo, $mensaje, $link);
        }
    }
}
