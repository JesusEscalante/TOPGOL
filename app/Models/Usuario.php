<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * ====================================================================
 * TOP GOL - Modelo Usuario
 * ====================================================================
 * Gestiona el acceso y operaciones con la tabla 'usuarios'.
 */
class Usuario extends Model {
    protected string $tabla = 'usuarios';

    /**
     * Obtiene todos los usuarios registrados
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(): array {
        $sql = "SELECT id, nombre, email, telefono, rol, created_at FROM {$this->tabla} ORDER BY id DESC";
        return $this->select($sql);
    }

    /**
     * Busca un usuario por su ID primario
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT id, nombre, email, telefono, rol, created_at FROM {$this->tabla} WHERE id = ? LIMIT 1";
        return $this->selectOne($sql, [$id]);
    }

    /**
     * Busca un usuario por su correo electrónico (incluye hash de contraseña para login)
     *
     * @param string $email
     * @return array<string, mixed>|null
     */
    public function obtenerPorEmail(string $email): ?array {
        $sql = "SELECT * FROM {$this->tabla} WHERE email = ? LIMIT 1";
        return $this->selectOne($sql, [strtolower(trim($email))]);
    }

    /**
     * Registra un nuevo usuario en el sistema
     *
     * @param array{nombre: string, email: string, password: string, telefono?: string, rol?: string} $datos
     * @return int ID del usuario recién creado
     */
    public function crear(array $datos): int {
        $passwordHash = password_hash($datos['password'], PASSWORD_BCRYPT);
        $rol = $datos['rol'] ?? 'cliente';
        $telefono = $datos['telefono'] ?? null;

        $sql = "INSERT INTO {$this->tabla} (nombre, email, password, telefono, rol, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";

        $this->query($sql, [
            trim($datos['nombre']),
            strtolower(trim($datos['email'])),
            $passwordHash,
            $telefono,
            $rol
        ]);

        return $this->lastInsertId();
    }

    /**
     * Actualiza el perfil de un usuario
     *
     * @param int $id
     * @param array{nombre: string, telefono?: string, rol?: string} $datos
     * @return bool
     */
    public function actualizar(int $id, array $datos): bool {
        $sql = "UPDATE {$this->tabla} SET nombre = ?, telefono = ?, rol = ?, updated_at = NOW() WHERE id = ?";
        return $this->execute($sql, [
            trim($datos['nombre']),
            $datos['telefono'] ?? null,
            $datos['rol'] ?? 'cliente',
            $id
        ]);
    }

    /**
     * Cambia la contraseña de un usuario
     *
     * @param int $id
     * @param string $nuevaPassword
     * @return bool
     */
    public function cambiarPassword(int $id, string $nuevaPassword): bool {
        $hash = password_hash($nuevaPassword, PASSWORD_BCRYPT);
        $sql = "UPDATE {$this->tabla} SET password = ?, updated_at = NOW() WHERE id = ?";
        return $this->execute($sql, [$hash, $id]);
    }

    /**
     * Cuenta la cantidad total de usuarios en la plataforma
     */
    public function contarUsuarios(): int {
        $sql = "SELECT COUNT(*) as total FROM {$this->tabla}";
        $res = $this->selectOne($sql);
        return (int)($res['total'] ?? 0);
    }

    /**
     * Busca un usuario por su Google ID
     *
     * @param string $googleId
     * @return array<string, mixed>|null
     */
    public function obtenerPorGoogleId(string $googleId): ?array {
        $sql = "SELECT * FROM {$this->tabla} WHERE google_id = ? LIMIT 1";
        return $this->selectOne($sql, [$googleId]);
    }

    /**
     * Registra un nuevo usuario autenticado con Google
     *
     * @param array{nombre: string, email: string, google_id: string, avatar?: string} $datos
     * @return int ID del usuario recién creado
     */
    public function crearConGoogle(array $datos): int {
        $rol = 'cliente';
        $telefono = null;
        $avatar = $datos['avatar'] ?? null;

        $sql = "INSERT INTO {$this->tabla} (nombre, email, google_id, avatar, telefono, rol, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $this->query($sql, [
            trim($datos['nombre']),
            strtolower(trim($datos['email'])),
            $datos['google_id'],
            $avatar,
            $telefono,
            $rol
        ]);

        return $this->lastInsertId();
    }

    /**
     * Vincula una cuenta existente con Google OAuth
     *
     * @param int $id
     * @param string $googleId
     * @param string|null $avatar
     * @return bool
     */
    public function vincularGoogle(int $id, string $googleId, ?string $avatar = null): bool {
        $sql = "UPDATE {$this->tabla} SET google_id = ?, avatar = COALESCE(?, avatar), updated_at = NOW() WHERE id = ?";
        return $this->execute($sql, [$googleId, $avatar, $id]);
    }
}