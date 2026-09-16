<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * ====================================================================
 * TOP GOL - Modelo Producto
 * ====================================================================
 * Gestiona el inventario de productos (refrescos, bebidas alcohólicas,
 * snacks, hidratantes y otros) del bar/kiosco.
 */
class Producto extends Model {
    protected string $tabla = 'productos';

    public const CATEGORIAS = [
        'refrescos'            => 'Refrescos',
        'bebidas_alcoholicas'  => 'Bebidas alcohólicas',
        'snacks'               => 'Snacks',
        'hidratantes'          => 'Hidratantes',
        'otros'                => 'Otros',
    ];

    public const ESTADOS = ['disponible', 'agotado', 'descontinuado'];

    /**
     * Obtiene todos los productos con filtros opcionales
     *
     * @param string $categoria Filtro por categoría ('' = todas)
     * @param string $busqueda Texto en nombre/descripción ('' = sin filtro)
     * @param bool $soloStockBajo Solo productos con stock <= stock_minimo
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(string $categoria = '', string $busqueda = '', bool $soloStockBajo = false): array {
        $sql = "SELECT * FROM {$this->tabla} WHERE 1 = 1";
        $params = [];

        if ($categoria !== '' && isset(self::CATEGORIAS[$categoria])) {
            $sql .= " AND categoria = ?";
            $params[] = $categoria;
        }
        if ($busqueda !== '') {
            $sql .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
            $params[] = '%' . $busqueda . '%';
            $params[] = '%' . $busqueda . '%';
        }
        if ($soloStockBajo) {
            $sql .= " AND stock <= stock_minimo AND estado <> 'descontinuado'";
        }

        $sql .= " ORDER BY categoria ASC, nombre ASC";
        return $this->select($sql, $params);
    }

    /**
     * Busca un producto por ID
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT * FROM {$this->tabla} WHERE id = ? LIMIT 1";
        return $this->selectOne($sql, [$id]);
    }

    /**
     * Registra un nuevo producto
     *
     * @param array<string, mixed> $datos
     * @return int ID del producto creado
     */
    public function crear(array $datos): int {
        $sql = "INSERT INTO {$this->tabla}
                (nombre, descripcion, categoria, precio, stock, stock_minimo, unidad, estado, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $this->query($sql, [
            trim((string)$datos['nombre']),
            trim((string)($datos['descripcion'] ?? '')) ?: null,
            (string)($datos['categoria'] ?? 'otros'),
            max(0, (float)$datos['precio']),
            max(0, (int)($datos['stock'] ?? 0)),
            max(0, (int)($datos['stock_minimo'] ?? 5)),
            trim((string)($datos['unidad'] ?? 'unidad')) ?: 'unidad',
            (string)($datos['estado'] ?? 'disponible'),
        ]);

        return $this->lastInsertId();
    }

    /**
     * Actualiza un producto existente
     *
     * @param int $id
     * @param array<string, mixed> $datos
     * @return bool
     */
    public function actualizar(int $id, array $datos): bool {
        $sql = "UPDATE {$this->tabla} SET
                    nombre = ?, descripcion = ?, categoria = ?, precio = ?,
                    stock = ?, stock_minimo = ?, unidad = ?, estado = ?,
                    updated_at = NOW()
                WHERE id = ?";

        return $this->execute($sql, [
            trim((string)$datos['nombre']),
            trim((string)($datos['descripcion'] ?? '')) ?: null,
            (string)($datos['categoria'] ?? 'otros'),
            max(0, (float)$datos['precio']),
            max(0, (int)($datos['stock'] ?? 0)),
            max(0, (int)($datos['stock_minimo'] ?? 5)),
            trim((string)($datos['unidad'] ?? 'unidad')) ?: 'unidad',
            (string)($datos['estado'] ?? 'disponible'),
            $id
        ]);
    }

    /**
     * Elimina un producto
     */
    public function eliminar(int $id): bool {
        $sql = "DELETE FROM {$this->tabla} WHERE id = ?";
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
}
