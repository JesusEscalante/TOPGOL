<?php
declare(strict_types=1);

namespace App\Core;

use App\Config\Database;
use PDO;
use PDOStatement;

/**
 * ====================================================================
 * TOP GOL - Modelo Base
 * ====================================================================
 * Proporciona métodos de abstracción y acceso seguro a la base de datos
 * utilizando PDO y consultas preparadas (Prepared Statements).
 */
abstract class Model {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Ejecuta una consulta SQL preparada con parámetros vinculados
     *
     * @param string $sql Sentencia SQL con marcadores de posición (?)
     * @param array<int|string, mixed> $params Parámetros a vincular
     * @return PDOStatement
     */
    protected function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Ejecuta una consulta y retorna todos los registros como array asociativo
     *
     * @param string $sql
     * @param array<int|string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    protected function select(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Ejecuta una consulta y retorna un único registro o null si no se encuentra
     *
     * @param string $sql
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    protected function selectOne(string $sql, array $params = []): ?array {
        $stmt = $this->query($sql, $params);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado !== false ? $resultado : null;
    }

    /**
     * Ejecuta una sentencia INSERT, UPDATE o DELETE
     *
     * @param string $sql
     * @param array<int|string, mixed> $params
     * @return bool True si tuvo éxito
     */
    protected function execute(string $sql, array $params = []): bool {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Retorna el último ID autonumérico insertado en la base de datos
     */
    protected function lastInsertId(): int {
        return (int)$this->db->lastInsertId();
    }

    /**
     * Inicia una transacción en la base de datos si no hay una activa
     */
    public function beginTransaction(): bool {
        return !$this->db->inTransaction() ? $this->db->beginTransaction() : true;
    }

    /**
     * Confirma la transacción activa
     */
    public function commit(): bool {
        return $this->db->inTransaction() ? $this->db->commit() : true;
    }

    /**
     * Revierte la transacción activa
     */
    public function rollBack(): bool {
        return $this->db->inTransaction() ? $this->db->rollBack() : true;
    }

    /**
     * Verifica si hay una transacción activa
     */
    public function inTransaction(): bool {
        return $this->db->inTransaction();
    }
}