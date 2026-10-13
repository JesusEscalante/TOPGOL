<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * ====================================================================
 * TOP GOL - Modelo Categoria
 * ====================================================================
 * Gestiona las categorías dinámicas de productos del bar/kiosco.
 */
class Categoria extends Model {
    protected string $tabla = 'categorias';

    /**
     * Obtiene todas las categorías
     *
     * @param bool $soloActivas
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodas(bool $soloActivas = false): array {
        $sql = "SELECT c.*, COUNT(p.id) AS total_productos
                FROM {$this->tabla} c
                LEFT JOIN productos p ON c.id = p.categoria_id AND p.estado <> 'descontinuado'
                WHERE 1 = 1";
        if ($soloActivas) {
            $sql .= " AND c.activo = 1";
        }
        $sql .= " GROUP BY c.id ORDER BY c.nombre ASC";
        return $this->select($sql);
    }

    /**
     * Obtiene una categoría por ID
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT * FROM {$this->tabla} WHERE id = ? LIMIT 1";
        return $this->selectOne($sql, [$id]);
    }

    /**
     * Obtiene una categoría por Slug
     */
    public function obtenerPorSlug(string $slug): ?array {
        $sql = "SELECT * FROM {$this->tabla} WHERE slug = ? LIMIT 1";
        return $this->selectOne($sql, [$slug]);
    }

    /**
     * Crea una nueva categoría
     *
     * @param array<string, mixed> $datos
     * @return int ID de la categoría creada
     */
    public function crear(array $datos): int {
        $nombre = trim((string)$datos['nombre']);
        $slug = trim((string)($datos['slug'] ?? ''));
        if ($slug === '') {
            $slug = $this->generarSlug($nombre);
        }

        $sql = "INSERT INTO {$this->tabla} (nombre, slug, descripcion, icono, activo, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";

        $this->query($sql, [
            $nombre,
            $slug,
            trim((string)($datos['descripcion'] ?? '')) ?: null,
            trim((string)($datos['icono'] ?? 'bi-box')) ?: 'bi-box',
            isset($datos['activo']) ? ((int)$datos['activo'] ? 1 : 0) : 1,
        ]);

        return $this->lastInsertId();
    }

    /**
     * Actualiza una categoría existente
     */
    public function actualizar(int $id, array $datos): bool {
        $nombre = trim((string)$datos['nombre']);
        $slug = trim((string)($datos['slug'] ?? ''));
        if ($slug === '') {
            $slug = $this->generarSlug($nombre);
        }

        $sql = "UPDATE {$this->tabla} SET
                    nombre = ?, slug = ?, descripcion = ?, icono = ?, activo = ?, updated_at = NOW()
                WHERE id = ?";

        return $this->execute($sql, [
            $nombre,
            $slug,
            trim((string)($datos['descripcion'] ?? '')) ?: null,
            trim((string)($datos['icono'] ?? 'bi-box')) ?: 'bi-box',
            isset($datos['activo']) ? ((int)$datos['activo'] ? 1 : 0) : 1,
            $id
        ]);
    }

    /**
     * Alterna el estado activo de una categoría
     */
    public function toggleActivo(int $id): bool {
        $sql = "UPDATE {$this->tabla} SET activo = CASE WHEN activo = 1 THEN 0 ELSE 1 END, updated_at = NOW() WHERE id = ?";
        return $this->execute($sql, [$id]);
    }

    /**
     * Cuenta cuántos productos están asignados a esta categoría
     */
    public function contarProductos(int $id): int {
        $sql = "SELECT COUNT(*) AS total FROM productos WHERE categoria_id = ?";
        $res = $this->selectOne($sql, [$id]);
        return (int)($res['total'] ?? 0);
    }

    /**
     * Elimina una categoría solo si no tiene productos asociados
     */
    public function eliminar(int $id): array {
        $productosAsociados = $this->contarProductos($id);
        if ($productosAsociados > 0) {
            return [
                'ok' => false,
                'error' => "No se puede eliminar la categoría porque tiene {$productosAsociados} producto(s) asignado(s). Desactívala o reasigna los productos primero."
            ];
        }

        $sql = "DELETE FROM {$this->tabla} WHERE id = ?";
        $ok = $this->execute($sql, [$id]);
        return [
            'ok' => $ok,
            'mensaje' => $ok ? 'Categoría eliminada con éxito.' : 'No se pudo eliminar la categoría.'
        ];
    }

    /**
     * Genera un slug normalizado
     */
    public function generarSlug(string $texto): string {
        $slug = mb_strtolower(trim($texto), 'UTF-8');
        $slug = preg_replace('/[áàäâ]/u', 'a', $slug);
        $slug = preg_replace('/[éèëê]/u', 'e', $slug);
        $slug = preg_replace('/[íìïî]/u', 'i', $slug);
        $slug = preg_replace('/[óòöô]/u', 'o', $slug);
        $slug = preg_replace('/[úùüû]/u', 'u', $slug);
        $slug = preg_replace('/[ñ]/u', 'n', $slug);
        $slug = preg_replace('/[^a-z0-9_-]/', '_', $slug);
        $slug = preg_replace('/_+/', '_', $slug);
        return trim((string)$slug, '_');
    }
}
