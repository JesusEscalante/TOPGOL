<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * ====================================================================
 * TOP GOL - Modelo Producto
 * ====================================================================
 * Gestiona el inventario de productos (refrescos, bebidas, snacks,
 * hidratantes y otros) del bar/kiosco con categorías dinámicas y kardex.
 */
class Producto extends Model {
    protected string $tabla = 'productos';

    public const ESTADOS = ['disponible', 'agotado', 'descontinuado'];

    /**
     * Obtiene todos los productos con filtros opcionales y datos de categoría
     *
     * @param int|string $categoriaId Filtro por categoría (0 o '' = todas)
     * @param string $busqueda Texto en nombre/descripción
     * @param bool $soloStockBajo Solo productos con stock <= stock_minimo
     * @param bool $soloDisponibles Solo productos disponibles para la venta en POS
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(
        int|string $categoriaId = 0,
        string $busqueda = '',
        bool $soloStockBajo = false,
        bool $soloDisponibles = false
    ): array {
        $sql = "SELECT p.*,
                       c.nombre AS categoria_nombre,
                       c.slug AS categoria_slug,
                       c.icono AS categoria_icono
                FROM {$this->tabla} p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE 1 = 1";
        $params = [];

        if (!empty($categoriaId)) {
            if (is_numeric($categoriaId) && (int)$categoriaId > 0) {
                $sql .= " AND p.categoria_id = ?";
                $params[] = (int)$categoriaId;
            } else {
                $sql .= " AND (p.categoria = ? OR c.slug = ?)";
                $params[] = (string)$categoriaId;
                $params[] = (string)$categoriaId;
            }
        }

        if ($busqueda !== '') {
            $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
            $params[] = '%' . $busqueda . '%';
            $params[] = '%' . $busqueda . '%';
        }

        if ($soloStockBajo) {
            $sql .= " AND p.stock <= p.stock_minimo AND p.estado <> 'descontinuado'";
        }

        if ($soloDisponibles) {
            $sql .= " AND p.estado = 'disponible' AND p.stock > 0";
        }

        $sql .= " ORDER BY COALESCE(c.nombre, 'ZZZ') ASC, p.nombre ASC";
        return $this->select($sql, $params);
    }

    /**
     * Busca un producto por ID con su categoría
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT p.*,
                       c.nombre AS categoria_nombre,
                       c.slug AS categoria_slug,
                       c.icono AS categoria_icono
                FROM {$this->tabla} p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.id = ?
                LIMIT 1";
        return $this->selectOne($sql, [$id]);
    }

    /**
     * Registra un nuevo producto en el catálogo y su saldo de apertura si tiene stock inicial
     *
     * @param array<string, mixed> $datos
     * @param int|null $adminId
     * @return int ID del producto creado
     */
    public function crear(array $datos, ?int $adminId = null): int {
        $categoriaId = (int)($datos['categoria_id'] ?? 5);
        if ($categoriaId <= 0) {
            $categoriaId = 5; // Otros
        }

        // Obtener el slug de la categoría
        $modeloCat = new Categoria();
        $cat = $modeloCat->obtenerPorId($categoriaId);
        $categoriaSlug = $cat ? (string)$cat['slug'] : 'otros';

        $stockInicial = max(0, (int)($datos['stock'] ?? 0));
        $estado = (string)($datos['estado'] ?? ($stockInicial > 0 ? 'disponible' : 'agotado'));
        if (!in_array($estado, self::ESTADOS, true)) {
            $estado = 'disponible';
        }

        $sql = "INSERT INTO {$this->tabla}
                (nombre, descripcion, categoria, categoria_id, precio, stock, stock_minimo, unidad, estado, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $this->query($sql, [
            trim((string)$datos['nombre']),
            trim((string)($datos['descripcion'] ?? '')) ?: null,
            $categoriaSlug,
            $categoriaId,
            max(0.01, (float)$datos['precio']),
            $stockInicial,
            max(0, (int)($datos['stock_minimo'] ?? 5)),
            trim((string)($datos['unidad'] ?? 'unidad')) ?: 'unidad',
            $estado,
        ]);

        $id = $this->lastInsertId();

        // Si se registró con stock inicial > 0, asentar en el kardex como saldo de apertura
        if ($id > 0 && $stockInicial > 0) {
            $modeloMov = new MovimientoInventario();
            $modeloMov->registrar(
                $id,
                'saldo_apertura',
                $stockInicial,
                0,
                $stockInicial,
                $adminId,
                'Saldo inicial al registrar producto en sistema'
            );
        }

        return $id;
    }

    /**
     * Actualiza los datos de un producto
     *
     * @param int $id
     * @param array<string, mixed> $datos
     * @param int|null $adminId
     * @return bool
     */
    public function actualizar(int $id, array $datos, ?int $adminId = null): bool {
        $actual = $this->obtenerPorId($id);
        if (!$actual) {
            return false;
        }

        $categoriaId = (int)($datos['categoria_id'] ?? $actual['categoria_id'] ?? 5);
        if ($categoriaId <= 0) {
            $categoriaId = 5;
        }
        $modeloCat = new Categoria();
        $cat = $modeloCat->obtenerPorId($categoriaId);
        $categoriaSlug = $cat ? (string)$cat['slug'] : 'otros';

        $nuevoStock = isset($datos['stock']) ? max(0, (int)$datos['stock']) : (int)$actual['stock'];
        $estado = (string)($datos['estado'] ?? $actual['estado']);
        if ($nuevoStock <= 0 && $estado === 'disponible') {
            $estado = 'agotado';
        } elseif ($nuevoStock > 0 && $estado === 'agotado') {
            $estado = 'disponible';
        }

        $sql = "UPDATE {$this->tabla} SET
                    nombre = ?, descripcion = ?, categoria = ?, categoria_id = ?,
                    precio = ?, stock = ?, stock_minimo = ?, unidad = ?, estado = ?,
                    updated_at = NOW()
                WHERE id = ?";

        $ok = $this->execute($sql, [
            trim((string)$datos['nombre']),
            trim((string)($datos['descripcion'] ?? '')) ?: null,
            $categoriaSlug,
            $categoriaId,
            max(0.01, (float)$datos['precio']),
            $nuevoStock,
            max(0, (int)($datos['stock_minimo'] ?? 5)),
            trim((string)($datos['unidad'] ?? 'unidad')) ?: 'unidad',
            $estado,
            $id
        ]);

        // Si hubo cambio manual de stock en el formulario de edición, auditar como 'ajuste'
        if ($ok && (int)$actual['stock'] !== $nuevoStock) {
            $diferencia = $nuevoStock - (int)$actual['stock'];
            $modeloMov = new MovimientoInventario();
            $modeloMov->registrar(
                $id,
                'ajuste',
                abs($diferencia),
                (int)$actual['stock'],
                $nuevoStock,
                $adminId,
                "Ajuste manual de stock desde formulario (Diferencia: " . ($diferencia >= 0 ? "+{$diferencia}" : "{$diferencia}") . ")"
            );
        }

        return $ok;
    }

    /**
     * Ajusta el stock mediante una operación formal de entrada o salida (Kardex seguro)
     *
     * @param int $id
     * @param string $tipo 'ingreso' o 'salida'
     * @param int $cantidad
     * @param int|null $adminId
     * @param string $motivo
     * @return array{ok: bool, mensaje?: string, error?: string, stock_nuevo?: int}
     */
    public function ajustarStock(int $id, string $tipo, int $cantidad, ?int $adminId, string $motivo): array {
        if ($cantidad <= 0) {
            return ['ok' => false, 'error' => 'La cantidad debe ser mayor a 0.'];
        }
        if (!in_array($tipo, ['ingreso', 'salida'], true)) {
            return ['ok' => false, 'error' => 'Tipo de movimiento inválido. Debe ser ingreso o salida.'];
        }

        $this->beginTransaction();
        try {
            $stmt = $this->query("SELECT id, nombre, stock, estado FROM {$this->tabla} WHERE id = ? FOR UPDATE", [$id]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$producto) {
                $this->rollBack();
                return ['ok' => false, 'error' => 'Producto no encontrado.'];
            }

            $stockAnterior = (int)$producto['stock'];
            if ($tipo === 'ingreso') {
                $stockNuevo = $stockAnterior + $cantidad;
                $nuevoEstado = ($producto['estado'] === 'agotado') ? 'disponible' : $producto['estado'];
            } else {
                if ($stockAnterior < $cantidad) {
                    $this->rollBack();
                    return [
                        'ok' => false,
                        'error' => "Stock insuficiente para realizar la salida. Stock actual: {$stockAnterior}, solicitado: {$cantidad}."
                    ];
                }
                $stockNuevo = $stockAnterior - $cantidad;
                $nuevoEstado = ($stockNuevo === 0 && $producto['estado'] === 'disponible') ? 'agotado' : $producto['estado'];
            }

            $this->query(
                "UPDATE {$this->tabla} SET stock = ?, estado = ?, updated_at = NOW() WHERE id = ?",
                [$stockNuevo, $nuevoEstado, $id]
            );

            $modeloMov = new MovimientoInventario();
            $modeloMov->registrar(
                $id,
                $tipo,
                $cantidad,
                $stockAnterior,
                $stockNuevo,
                $adminId,
                trim($motivo) !== '' ? trim($motivo) : ($tipo === 'ingreso' ? 'Reabastecimiento' : 'Salida de stock')
            );

            $this->commit();

            return [
                'ok' => true,
                'mensaje' => "Stock actualizado con éxito. Nuevo stock: {$stockNuevo}.",
                'stock_nuevo' => $stockNuevo
            ];
        } catch (\Throwable $e) {
            $this->rollBack();
            return ['ok' => false, 'error' => 'Error de base de datos al ajustar stock: ' . $e->getMessage()];
        }
    }

    /**
     * Intenta eliminar un producto verificando que no tenga ventas ni movimientos históricos
     *
     * @param int $id
     * @return array{ok: bool, mensaje?: string, error?: string}
     */
    public function eliminarSeguro(int $id): array {
        $producto = $this->obtenerPorId($id);
        if (!$producto) {
            return ['ok' => false, 'error' => 'Producto no encontrado.'];
        }

        // Verificar si tiene ventas en venta_detalles
        $ventasCount = (int)$this->query("SELECT COUNT(*) FROM venta_detalles WHERE producto_id = ?", [$id])->fetchColumn();
        if ($ventasCount > 0) {
            return [
                'ok' => false,
                'error' => "No se puede eliminar '{$producto['nombre']}' porque registra {$ventasCount} venta(s) histórica(s). Para ocultarlo de la venta, márcalo como 'Descontinuado'."
            ];
        }

        // Verificar si tiene movimientos en movimientos_inventario
        $movCount = (int)$this->query("SELECT COUNT(*) FROM movimientos_inventario WHERE producto_id = ?", [$id])->fetchColumn();
        if ($movCount > 0) {
            return [
                'ok' => false,
                'error' => "No se puede eliminar '{$producto['nombre']}' porque registra {$movCount} movimiento(s) de inventario auditado(s). Para ocultarlo, márcalo como 'Descontinuado'."
            ];
        }

        try {
            $this->query("DELETE FROM {$this->tabla} WHERE id = ?", [$id]);
            return ['ok' => true, 'mensaje' => "Producto '{$producto['nombre']}' eliminado correctamente."];
        } catch (\PDOException $e) {
            return ['ok' => false, 'error' => 'No se puede eliminar el producto debido a restricciones de integridad: ' . $e->getMessage()];
        }
    }

    /**
     * Descontinúa un producto para que no aparezca en venta pero conserve su histórico
     */
    public function descontinuar(int $id): bool {
        $sql = "UPDATE {$this->tabla} SET estado = 'descontinuado', updated_at = NOW() WHERE id = ?";
        return $this->execute($sql, [$id]);
    }

    /**
     * Cuenta productos con stock bajo (para alertas)
     */
    public function contarStockBajo(): int {
        $sql = "SELECT COUNT(*) AS total FROM {$this->tabla} WHERE stock <= stock_minimo AND estado <> 'descontinuado'";
        $res = $this->selectOne($sql);
        return (int)($res['total'] ?? 0);
    }

    /**
     * Valorización total del inventario (precio x stock)
     */
    public function valorizacion(): float {
        $sql = "SELECT SUM(precio * stock) AS total FROM {$this->tabla} WHERE estado <> 'descontinuado'";
        $res = $this->selectOne($sql);
        return (float)($res['total'] ?? 0.0);
    }

    /**
     * Obtiene la lista de productos con stock crítico (bajo o agotado) para el widget y alertas
     *
     * @param int $limite
     * @return array<int, array<string, mixed>>
     */
    public function obtenerStockCritico(int $limite = 15): array {
        $sql = "SELECT p.*,
                       c.nombre AS categoria_nombre,
                       c.icono AS categoria_icono
                FROM {$this->tabla} p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.stock <= p.stock_minimo AND p.estado <> 'descontinuado'
                ORDER BY p.stock ASC, p.nombre ASC
                LIMIT " . (int)$limite;
        return $this->select($sql);
    }
}
