<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * ====================================================================
 * TOP GOL - Modelo Venta
 * ====================================================================
 * Gestiona el registro comercial de ventas del bar/kiosco,
 * transacciones atómicas de stock, congelamiento de precios y arqueo de caja.
 */
class Venta extends Model {
    protected string $tabla = 'ventas';

    /**
     * Procesa y registra una venta comercial rápida de forma atómica y segura
     *
     * @param array<string, mixed> $datosVenta ['cliente_nombre', 'metodo_pago', 'notas']
     * @param array<int, array{producto_id: int, cantidad: int}> $items Lista de productos a vender
     * @param int $adminId ID del administrador que registra la venta
     * @param string $idempotencyToken Token único para evitar doble clic / submit duplicado
     * @return array{ok: bool, mensaje?: string, error?: string, venta_id?: int, codigo?: string, total?: float}
     */
    public function crearVenta(
        array $datosVenta,
        array $items,
        int $adminId,
        string $idempotencyToken = ''
    ): array {
        if (empty($items)) {
            return ['ok' => false, 'error' => 'La venta debe contener al menos un producto.'];
        }

        $metodoPago = (string)($datosVenta['metodo_pago'] ?? 'efectivo');
        if (!in_array($metodoPago, ['efectivo', 'niubiz'], true)) {
            return ['ok' => false, 'error' => 'Método de pago inválido. Top Gol admite Efectivo o Niubiz POS para ventas.'];
        }

        $clienteNombre = trim((string)($datosVenta['cliente_nombre'] ?? 'Cliente Mostrador'));
        if ($clienteNombre === '') {
            $clienteNombre = 'Cliente Mostrador';
        }
        $notas = trim((string)($datosVenta['notas'] ?? ''));

        $this->beginTransaction();
        try {
            // 1. Verificación de token único de idempotencia (prevención estricta de doble clic)
            if ($idempotencyToken !== '') {
                $stmtToken = $this->query(
                    "SELECT id, codigo_operacion, total FROM {$this->tabla} WHERE idempotency_token = ? LIMIT 1 FOR UPDATE",
                    [$idempotencyToken]
                );
                $ventaExistente = $stmtToken->fetch(PDO::FETCH_ASSOC);
                if ($ventaExistente) {
                    $this->rollBack();
                    return [
                        'ok' => false,
                        'error' => "Esta operación ya fue registrada previamente ({$ventaExistente['codigo_operacion']}). Evite el doble clic.",
                        'venta_id' => (int)$ventaExistente['id'],
                        'codigo' => (string)$ventaExistente['codigo_operacion'],
                    ];
                }
            }

            // 2. Bloquear y verificar stock de cada producto en la lista
            $totalVenta = 0.0;
            $itemsValidados = [];

            // Consolidar cantidades si un producto viene repetido en el array de items
            $itemsAgrupados = [];
            foreach ($items as $it) {
                $pId = (int)($it['producto_id'] ?? 0);
                $cant = (int)($it['cantidad'] ?? 0);
                if ($pId <= 0 || $cant <= 0) {
                    continue;
                }
                $itemsAgrupados[$pId] = ($itemsAgrupados[$pId] ?? 0) + $cant;
            }

            if (empty($itemsAgrupados)) {
                $this->rollBack();
                return ['ok' => false, 'error' => 'Los productos o cantidades solicitadas son inválidos.'];
            }

            $modeloMov = new MovimientoInventario();

            foreach ($itemsAgrupados as $pId => $cant) {
                $stmtProd = $this->query(
                    "SELECT id, nombre, precio, stock, estado FROM productos WHERE id = ? FOR UPDATE",
                    [$pId]
                );
                $prod = $stmtProd->fetch(PDO::FETCH_ASSOC);

                if (!$prod) {
                    $this->rollBack();
                    return ['ok' => false, 'error' => "El producto #{$pId} no existe en el catálogo."];
                }

                if ($prod['estado'] === 'descontinuado') {
                    $this->rollBack();
                    return ['ok' => false, 'error' => "El producto '{$prod['nombre']}' se encuentra descontinuado y no puede ser vendido."];
                }

                $stockActual = (int)$prod['stock'];
                if ($stockActual < $cant) {
                    $this->rollBack();
                    return [
                        'ok' => false,
                        'error' => "Stock insuficiente para '{$prod['nombre']}'. Disponible en bar: {$stockActual}, solicitado: {$cant}."
                    ];
                }

                $precioUnitario = (float)$prod['precio'];
                $subtotal = round($precioUnitario * $cant, 2);
                $totalVenta += $subtotal;

                $itemsValidados[] = [
                    'producto_id'     => $pId,
                    'nombre'          => $prod['nombre'],
                    'cantidad'        => $cant,
                    'stock_anterior'  => $stockActual,
                    'stock_nuevo'     => $stockActual - $cant,
                    'precio_unitario' => $precioUnitario,
                    'subtotal'        => $subtotal,
                ];
            }

            $totalVenta = round($totalVenta, 2);
            if ($totalVenta <= 0.0) {
                $this->rollBack();
                return ['ok' => false, 'error' => 'El total de la venta debe ser mayor a S/ 0.00.'];
            }

            // 3. Generar código correlativo interno único de venta (ej: VTA-20261011-0001)
            $fechaPrefijo = date('Ymd');
            $stmtCorr = $this->query(
                "SELECT COUNT(*) AS total_hoy FROM {$this->tabla} WHERE DATE(created_at) = CURDATE() FOR UPDATE"
            );
            $totalHoy = (int)$stmtCorr->fetchColumn();
            $correlativo = str_pad((string)($totalHoy + 1), 4, '0', STR_PAD_LEFT);
            $codigoOperacion = "VTA-{$fechaPrefijo}-{$correlativo}";

            // 4. Insertar cabecera de la venta
            $sqlVenta = "INSERT INTO {$this->tabla}
                         (codigo_operacion, idempotency_token, cliente_nombre, total, metodo_pago, estado, registrado_por, notas, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, 'completada', ?, ?, NOW(), NOW())";

            $this->query($sqlVenta, [
                $codigoOperacion,
                $idempotencyToken !== '' ? $idempotencyToken : null,
                $clienteNombre,
                $totalVenta,
                $metodoPago,
                $adminId,
                $notas !== '' ? $notas : null,
            ]);

            $ventaId = (int)$this->lastInsertId();
            if ($ventaId <= 0) {
                $this->rollBack();
                return ['ok' => false, 'error' => 'No se pudo generar el registro de la venta en base de datos.'];
            }

            // 5. Insertar detalle de venta, descontar stock de producto y asentar movimiento de inventario
            $sqlDetalle = "INSERT INTO venta_detalles (venta_id, producto_id, cantidad, precio_unitario, subtotal, created_at)
                           VALUES (?, ?, ?, ?, ?, NOW())";

            foreach ($itemsValidados as $it) {
                // Registrar detalle congelando precio unitario
                $this->query($sqlDetalle, [
                    $ventaId,
                    $it['producto_id'],
                    $it['cantidad'],
                    $it['precio_unitario'],
                    $it['subtotal']
                ]);

                // Descontar stock atómicamente
                $nuevoEstado = ($it['stock_nuevo'] === 0) ? 'agotado' : 'disponible';
                $this->query(
                    "UPDATE productos SET stock = ?, estado = ?, updated_at = NOW() WHERE id = ?",
                    [$it['stock_nuevo'], $nuevoEstado, $it['producto_id']]
                );

                // Asentar en kardex
                $modeloMov->registrar(
                    $it['producto_id'],
                    'venta',
                    $it['cantidad'],
                    $it['stock_anterior'],
                    $it['stock_nuevo'],
                    $adminId,
                    "Venta #{$codigoOperacion} ({$clienteNombre})"
                );
            }

            $this->commit();

            return [
                'ok'       => true,
                'mensaje'  => "Venta {$codigoOperacion} registrada con éxito por S/ " . number_format($totalVenta, 2) . ".",
                'venta_id' => $ventaId,
                'codigo'   => $codigoOperacion,
                'total'    => $totalVenta,
                'metodo'   => $metodoPago
            ];
        } catch (\Throwable $e) {
            $this->rollBack();
            return ['ok' => false, 'error' => 'Error al procesar la venta en base de datos: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene una venta completa por ID con sus productos detallados
     */
    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT v.*, u.nombre AS admin_nombre
                FROM {$this->tabla} v
                LEFT JOIN usuarios u ON v.registrado_por = u.id
                WHERE v.id = ?
                LIMIT 1";
        $venta = $this->selectOne($sql, [$id]);
        if (!$venta) {
            return null;
        }

        $sqlDet = "SELECT vd.*, p.nombre AS producto_nombre, p.unidad, c.nombre AS categoria_nombre
                   FROM venta_detalles vd
                   INNER JOIN productos p ON vd.producto_id = p.id
                   LEFT JOIN categorias c ON p.categoria_id = c.id
                   WHERE vd.venta_id = ?
                   ORDER BY vd.id ASC";
        $items = $this->select($sqlDet, [$id]);
        $venta['items'] = $items;
        $venta['detalles'] = $items;
        return $venta;
    }

    /**
     * Obtiene el listado de ventas para el historial
     */
    public function obtenerHistorial(array $filtros = [], int $limite = 50): array {
        $sql = "SELECT v.*,
                       u.nombre AS admin_nombre,
                       COUNT(vd.id) AS total_items,
                       COALESCE(SUM(vd.cantidad), 0) AS total_unidades
                FROM {$this->tabla} v
                LEFT JOIN usuarios u ON v.registrado_por = u.id
                LEFT JOIN venta_detalles vd ON v.id = vd.venta_id
                WHERE 1 = 1";
        $params = [];

        if (!empty($filtros['fecha'])) {
            $sql .= " AND DATE(v.created_at) = ?";
            $params[] = (string)$filtros['fecha'];
        }
        if (!empty($filtros['metodo_pago'])) {
            $sql .= " AND v.metodo_pago = ?";
            $params[] = (string)$filtros['metodo_pago'];
        }
        if (!empty($filtros['q'])) {
            $sql .= " AND (v.codigo_operacion LIKE ? OR v.cliente_nombre LIKE ?)";
            $params[] = '%' . $filtros['q'] . '%';
            $params[] = '%' . $filtros['q'] . '%';
        }

        $sql .= " GROUP BY v.id ORDER BY v.created_at DESC, v.id DESC LIMIT " . (int)$limite;
        return $this->select($sql, $params);
    }

    /**
     * Resumen de ventas de productos de una fecha para integración con Caja Diaria
     *
     * @param string $fecha Formato YYYY-MM-DD
     * @param bool $incluirDemo
     * @return array<string, mixed>
     */
    public function obtenerResumenVentasDia(string $fecha, bool $incluirDemo = false): array {
        $demoFilter = $incluirDemo ? '' : ' AND es_demo = 0';

        $sql = "SELECT
                    COALESCE(SUM(total), 0) AS total_ventas,
                    COALESCE(SUM(CASE WHEN metodo_pago = 'efectivo' THEN total ELSE 0 END), 0) AS total_efectivo,
                    COALESCE(SUM(CASE WHEN metodo_pago = 'niubiz' THEN total ELSE 0 END), 0) AS total_niubiz,
                    COUNT(*) AS total_transacciones,
                    COALESCE(SUM(CASE WHEN es_demo = 1 THEN total ELSE 0 END), 0) AS total_demo,
                    SUM(CASE WHEN es_demo = 1 THEN 1 ELSE 0 END) AS tx_demo
                FROM {$this->tabla}
                WHERE DATE(created_at) = ? AND estado = 'completada'{$demoFilter}";

        $res = $this->selectOne($sql, [$fecha]);

        $montoTotal = round((float)($res['total_ventas'] ?? 0.0), 2);
        $montoEfe   = round((float)($res['total_efectivo'] ?? 0.0), 2);
        $montoPos   = round((float)($res['total_niubiz'] ?? 0.0), 2);
        $totalTx    = (int)($res['total_transacciones'] ?? 0);

        return [
            'fecha'               => $fecha,
            'incluir_demo'        => $incluirDemo,
            'total_ventas'        => $montoTotal,
            'monto_total'         => $montoTotal,
            'total_efectivo'      => $montoEfe,
            'monto_efectivo'      => $montoEfe,
            'total_niubiz'        => $montoPos,
            'monto_niubiz'        => $montoPos,
            'total_transacciones' => $totalTx,
            'total_demo'          => round((float)($res['total_demo'] ?? 0.0), 2),
            'tx_demo'             => (int)($res['tx_demo'] ?? 0),
        ];
    }

    /**
     * Obtiene los productos más vendidos para el widget del Dashboard o reportes
     *
     * @param int $limite
     * @param string|null $fecha YYYY-MM-DD o null para histórico
     * @param bool $incluirDemo
     * @return array<int, array<string, mixed>>
     */
    public function obtenerProductosMasVendidos(int $limite = 5, ?string $fecha = null, bool $incluirDemo = false): array {
        $demoFilter = $incluirDemo ? '' : ' AND v.es_demo = 0';
        $params = [];
        $whereFecha = '';

        if (!empty($fecha)) {
            $whereFecha = ' AND DATE(v.created_at) = ?';
            $params[] = $fecha;
        }

        $sql = "SELECT
                    p.id,
                    p.nombre,
                    p.unidad,
                    p.stock AS stock_actual,
                    p.precio AS precio_actual,
                    COALESCE(c.nombre, 'General') AS categoria_nombre,
                    COALESCE(c.icono, 'bi-box') AS categoria_icono,
                    COALESCE(SUM(vd.cantidad), 0) AS unidades_vendidas,
                    COALESCE(SUM(vd.subtotal), 0) AS total_ingresos
                FROM venta_detalles vd
                INNER JOIN {$this->tabla} v ON vd.venta_id = v.id
                INNER JOIN productos p ON vd.producto_id = p.id
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE v.estado = 'completada'{$demoFilter}{$whereFecha}
                GROUP BY p.id, p.nombre, p.unidad, p.stock, p.precio, c.nombre, c.icono
                ORDER BY unidades_vendidas DESC, total_ingresos DESC
                LIMIT " . (int)$limite;

        return $this->select($sql, $params);
    }

    /**
     * Genera el reporte consolidado comercial de productos, ventas e inventario
     *
     * @param string $desde YYYY-MM-DD
     * @param string $hasta YYYY-MM-DD
     * @param bool $incluirDemo
     * @return array<string, mixed>
     */
    public function obtenerReporteProductos(string $desde, string $hasta, bool $incluirDemo = false): array {
        $demoFilter = $incluirDemo ? '' : ' AND v.es_demo = 0';

        // 1. Resumen global de ventas de productos en el periodo
        $sqlResumen = "SELECT
                            COALESCE(SUM(vd.cantidad), 0) AS total_unidades,
                            COALESCE(SUM(vd.subtotal), 0) AS total_ingresos,
                            COUNT(DISTINCT v.id) AS total_transacciones,
                            COALESCE(SUM(CASE WHEN v.es_demo = 1 THEN vd.cantidad ELSE 0 END), 0) AS demo_unidades,
                            COALESCE(SUM(CASE WHEN v.es_demo = 1 THEN vd.subtotal ELSE 0 END), 0) AS demo_ingresos
                       FROM venta_detalles vd
                       INNER JOIN {$this->tabla} v ON vd.venta_id = v.id
                       WHERE DATE(v.created_at) BETWEEN ? AND ?
                         AND v.estado = 'completada'{$demoFilter}";
        $res = $this->selectOne($sqlResumen, [$desde, $hasta]);

        $totalUnidades = (int)($res['total_unidades'] ?? 0);
        $totalIngresos = round((float)($res['total_ingresos'] ?? 0.0), 2);
        $totalTx       = (int)($res['total_transacciones'] ?? 0);
        $ticketProm    = $totalTx > 0 ? round($totalIngresos / $totalTx, 2) : 0.0;

        // 2. Ranking completo de productos vendidos en el periodo
        $sqlRanking = "SELECT
                            p.id,
                            p.nombre,
                            p.unidad,
                            p.stock AS stock_actual,
                            p.stock_minimo,
                            p.precio AS precio_pvp,
                            COALESCE(c.nombre, 'Sin categoría') AS categoria_nombre,
                            COALESCE(SUM(vd.cantidad), 0) AS unidades_vendidas,
                            COALESCE(SUM(vd.subtotal), 0) AS total_ingresos
                       FROM venta_detalles vd
                       INNER JOIN {$this->tabla} v ON vd.venta_id = v.id
                       INNER JOIN productos p ON vd.producto_id = p.id
                       LEFT JOIN categorias c ON p.categoria_id = c.id
                       WHERE DATE(v.created_at) BETWEEN ? AND ?
                         AND v.estado = 'completada'{$demoFilter}
                       GROUP BY p.id, p.nombre, p.unidad, p.stock, p.stock_minimo, p.precio, c.nombre
                       ORDER BY unidades_vendidas DESC, total_ingresos DESC";
        $ranking = $this->select($sqlRanking, [$desde, $hasta]);

        // 3. Ventas agrupadas por categoría
        $sqlCat = "SELECT
                        COALESCE(c.nombre, 'Sin categoría') AS categoria_nombre,
                        COALESCE(SUM(vd.cantidad), 0) AS unidades_vendidas,
                        COALESCE(SUM(vd.subtotal), 0) AS total_ingresos
                   FROM venta_detalles vd
                   INNER JOIN {$this->tabla} v ON vd.venta_id = v.id
                   INNER JOIN productos p ON vd.producto_id = p.id
                   LEFT JOIN categorias c ON p.categoria_id = c.id
                   WHERE DATE(v.created_at) BETWEEN ? AND ?
                     AND v.estado = 'completada'{$demoFilter}
                   GROUP BY COALESCE(c.nombre, 'Sin categoría')
                   ORDER BY total_ingresos DESC";
        $porCategoria = $this->select($sqlCat, [$desde, $hasta]);

        // 4. Indicadores de inventario activo (PVP de existencias y alertas de stock bajo)
        $modeloProd = new Producto();
        $valorInventarioPvp = round($modeloProd->valorizacion(), 2);
        $conteoStockBajo = $modeloProd->contarStockBajo();
        $productosStockBajo = $modeloProd->obtenerStockCritico(15);

        // 5. Movimientos de inventario del periodo (Kardex auditado)
        $sqlMov = "SELECT
                        m.id,
                        m.tipo,
                        m.cantidad,
                        m.stock_anterior,
                        m.stock_nuevo,
                        m.motivo,
                        m.created_at,
                        p.nombre AS producto_nombre,
                        p.unidad,
                        u.nombre AS admin_nombre
                   FROM movimientos_inventario m
                   INNER JOIN productos p ON m.producto_id = p.id
                   LEFT JOIN usuarios u ON m.registrado_por = u.id
                   WHERE DATE(m.created_at) BETWEEN ? AND ?
                   ORDER BY m.created_at DESC, m.id DESC
                   LIMIT 50";
        $movimientos = $this->select($sqlMov, [$desde, $hasta]);

        return [
            'desde'                  => $desde,
            'hasta'                  => $hasta,
            'incluir_demo'           => $incluirDemo,
            'total_unidades'         => $totalUnidades,
            'total_ingresos'         => $totalIngresos,
            'total_transacciones'    => $totalTx,
            'ticket_promedio'        => $ticketProm,
            'valor_inventario_pvp'   => $valorInventarioPvp,
            'conteo_stock_bajo'      => $conteoStockBajo,
            'productos_stock_bajo'   => $productosStockBajo,
            'demo_unidades'          => (int)($res['demo_unidades'] ?? 0),
            'demo_ingresos'          => round((float)($res['demo_ingresos'] ?? 0.0), 2),
            'ranking'                => $ranking,
            'por_categoria'          => $porCategoria,
            'movimientos'            => $movimientos,
        ];
    }
}
