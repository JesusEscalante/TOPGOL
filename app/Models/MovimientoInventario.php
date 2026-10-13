<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * ====================================================================
 * TOP GOL - Modelo MovimientoInventario (Kardex)
 * ====================================================================
 * Registra entradas, salidas, ventas y ajustes de stock con trazabilidad.
 */
class MovimientoInventario extends Model {
    protected string $tabla = 'movimientos_inventario';

    public const TIPOS = [
        'ingreso'        => 'Ingreso (Compra/Reabastecimiento)',
        'salida'         => 'Salida (Merma/Dañado/Consumo)',
        'venta'          => 'Venta en mostrador',
        'ajuste'         => 'Ajuste de inventario',
        'saldo_apertura' => 'Saldo inicial de apertura',
    ];

    /**
     * Registra un movimiento de stock en el kardex
     *
     * @param int $productoId
     * @param string $tipo 'ingreso'|'salida'|'venta'|'ajuste'|'saldo_apertura'
     * @param int $cantidad
     * @param int $stockAnterior
     * @param int $stockNuevo
     * @param int|null $adminId
     * @param string $motivo
     * @return int ID del movimiento
     */
    public function registrar(
        int $productoId,
        string $tipo,
        int $cantidad,
        int $stockAnterior,
        int $stockNuevo,
        ?int $adminId,
        string $motivo = ''
    ): int {
        if (!isset(self::TIPOS[$tipo])) {
            $tipo = 'ajuste';
        }

        $sql = "INSERT INTO {$this->tabla}
                (producto_id, tipo, cantidad, stock_anterior, stock_nuevo, registrado_por, motivo, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $this->query($sql, [
            $productoId,
            $tipo,
            $cantidad,
            $stockAnterior,
            $stockNuevo,
            $adminId,
            trim($motivo) !== '' ? trim($motivo) : self::TIPOS[$tipo],
        ]);

        return $this->lastInsertId();
    }

    /**
     * Obtiene el historial de movimientos de un producto
     */
    public function obtenerPorProducto(int $productoId, int $limite = 50): array {
        $sql = "SELECT m.*, u.nombre AS admin_nombre
                FROM {$this->tabla} m
                LEFT JOIN usuarios u ON m.registrado_por = u.id
                WHERE m.producto_id = ?
                ORDER BY m.created_at DESC, m.id DESC
                LIMIT ?";
        return $this->select($sql, [$productoId, $limite]);
    }

    /**
     * Obtiene los movimientos con filtros generales para el kardex de administración
     */
    public function obtenerHistorial(array $filtros = [], int $limite = 100): array {
        $sql = "SELECT m.*, p.nombre AS producto_nombre, p.unidad, c.nombre AS categoria_nombre, u.nombre AS admin_nombre
                FROM {$this->tabla} m
                INNER JOIN productos p ON m.producto_id = p.id
                LEFT JOIN categorias c ON p.categoria_id = c.id
                LEFT JOIN usuarios u ON m.registrado_por = u.id
                WHERE 1 = 1";
        $params = [];

        if (!empty($filtros['producto_id'])) {
            $sql .= " AND m.producto_id = ?";
            $params[] = (int)$filtros['producto_id'];
        }
        if (!empty($filtros['tipo'])) {
            $sql .= " AND m.tipo = ?";
            $params[] = (string)$filtros['tipo'];
        }
        if (!empty($filtros['fecha'])) {
            $sql .= " AND DATE(m.created_at) = ?";
            $params[] = (string)$filtros['fecha'];
        }

        $sql .= " ORDER BY m.created_at DESC, m.id DESC LIMIT " . (int)$limite;
        return $this->select($sql, $params);
    }
}
