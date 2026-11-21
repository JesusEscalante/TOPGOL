<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * ====================================================================
 * TOP GOL - Modelo de Mensajes de Contacto (RF02 / RF16)
 * ====================================================================
 * Gestiona la persistencia y consulta de los mensajes enviados desde
 * el formulario público de contacto.
 */
class ContactoMensaje extends Model
{
    protected string $tabla = 'contacto_mensajes';
    private static bool $tablaVerificada = false;

    public function __construct()
    {
        parent::__construct();
        if (!self::$tablaVerificada) {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `contacto_mensajes` (
                  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                  `nombre`      VARCHAR(100) NOT NULL,
                  `email`       VARCHAR(100) NOT NULL,
                  `telefono`    VARCHAR(25) DEFAULT NULL,
                  `asunto`      VARCHAR(100) NOT NULL DEFAULT 'Consulta general',
                  `mensaje`     TEXT NOT NULL,
                  `ip_address`  VARCHAR(45) DEFAULT NULL,
                  `atendido`    TINYINT(1) NOT NULL DEFAULT 0,
                  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_contacto_atendido` (`atendido`),
                  KEY `idx_contacto_fecha`    (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            self::$tablaVerificada = true;
        }
    }

    /**
     * Guarda un nuevo mensaje de contacto en la base de datos.
     *
     * @param array{nombre: string, email: string, telefono?: string, asunto?: string, mensaje: string, ip_address?: string} $datos
     * @return int ID insertado
     */
    public function crear(array $datos): int
    {
        $sql = "INSERT INTO {$this->tabla} (nombre, email, telefono, asunto, mensaje, ip_address, atendido, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 0, NOW())";

        $this->query($sql, [
            mb_substr(trim($datos['nombre']), 0, 100),
            mb_substr(strtolower(trim($datos['email'])), 0, 100),
            !empty($datos['telefono']) ? mb_substr(trim($datos['telefono']), 0, 25) : null,
            !empty($datos['asunto']) ? mb_substr(trim($datos['asunto']), 0, 100) : 'Consulta general',
            trim($datos['mensaje']),
            !empty($datos['ip_address']) ? mb_substr(trim($datos['ip_address']), 0, 45) : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
        ]);

        return $this->lastInsertId();
    }

    /**
     * Obtiene todos los mensajes de contacto ordenados del más reciente al más antiguo.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(int $limite = 200): array
    {
        $limiteSeguro = max(1, min(500, $limite));
        return $this->select("SELECT * FROM {$this->tabla} ORDER BY atendido ASC, id DESC LIMIT {$limiteSeguro}");
    }

    /**
     * Marca un mensaje como atendido o pendiente.
     */
    public function marcarAtendido(int $id, bool $atendido = true): bool
    {
        return $this->execute("UPDATE {$this->tabla} SET atendido = ? WHERE id = ?", [$atendido ? 1 : 0, $id]);
    }
}
