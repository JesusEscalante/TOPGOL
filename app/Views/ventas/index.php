<?php
declare(strict_types=1);

/** @var array<int, array<string, mixed>> $ventas */
/** @var array<string, mixed> $resumenHoy */
/** @var string $filtroFecha */
/** @var string $filtroMetodo */
/** @var string $busqueda */

$filtroFecha = $filtroFecha ?? '';
$filtroMetodo = $filtroMetodo ?? '';
$busqueda = $busqueda ?? '';
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<style>
    .f-bar { display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-end; }
    .f-bar .fld { display: flex; flex-direction: column; gap: 4px; }
    .f-bar label { font-size: .68rem; font-weight: 700; color: #7c8aa0; text-transform: uppercase; letter-spacing: .4px; }
    .f-bar .form-control, .f-bar .form-select { font-size: .82rem; border-radius: 9px; }
    .badge-metodo-efectivo { background: #dcfce7; color: #166534; font-weight: 700; border: 1px solid #bbf7d0; }
    .badge-metodo-niubiz { background: #dbeafe; color: #1e40af; font-weight: 700; border: 1px solid #bfdbfe; }
    .badge-metodo-yape { background: #f3e8ff; color: #6b21a8; font-weight: 700; border: 1px solid #e9d5ff; }
</style>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
    <div>
        <h1 class="adm-h1">Historial de Ventas</h1>
        <p class="adm-sub">Registro de ventas en mostrador, bar y cafetería de Top Gol.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('/admin/ventas/pos') ?>" class="btn btn-sm fw-bold shadow-sm" style="background:#15803d;color:#fff;border-radius:9px;padding:9px 18px;">
            <i class="bi bi-cart-check me-1"></i> Terminal POS (Venta Rápida)
        </a>
        <a href="<?= url('/admin/productos') ?>" class="btn btn-sm btn-outline-dark fw-bold" style="border-radius:9px;padding:9px 16px;">
            <i class="bi bi-box me-1"></i> Catálogo
        </a>
        <a href="<?= url('/admin/inventario') ?>" class="btn btn-sm btn-outline-dark fw-bold" style="border-radius:9px;padding:9px 16px;">
            <i class="bi bi-boxes me-1"></i> Kardex / Stock
        </a>
    </div>
</div>

<!-- KPIs de Ventas Hoy -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi">
            <span class="kpi-ico b"><i class="bi bi-receipt"></i></span>
            <div>
                <small class="lbl">Ventas registradas hoy</small>
                <div class="val"><?= (int)($resumenHoy['total_transacciones'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi">
            <span class="kpi-ico g"><i class="bi bi-cash-stack"></i></span>
            <div>
                <small class="lbl">Total ventas de hoy</small>
                <div class="val text-success">S/ <?= number_format((float)($resumenHoy['total_ventas'] ?? $resumenHoy['monto_total'] ?? 0), 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi">
            <span class="kpi-ico g"><i class="bi bi-cash"></i></span>
            <div>
                <small class="lbl">Efectivo recaudado hoy</small>
                <div class="val">S/ <?= number_format((float)($resumenHoy['total_efectivo'] ?? $resumenHoy['monto_efectivo'] ?? 0), 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi">
            <span class="kpi-ico b"><i class="bi bi-credit-card-2-front"></i></span>
            <div>
                <small class="lbl">Niubiz POS hoy</small>
                <div class="val">S/ <?= number_format((float)($resumenHoy['total_niubiz'] ?? $resumenHoy['monto_niubiz'] ?? 0), 2) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros de búsqueda -->
<div class="adm-card mb-4">
    <form method="GET" action="<?= url('/admin/ventas') ?>" class="f-bar">
        <div class="fld" style="min-width: 140px;">
            <label>Fecha</label>
            <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($filtroFecha, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="fld" style="min-width: 150px;">
            <label>Método de Pago</label>
            <select name="metodo" class="form-select">
                <option value="">Todos los métodos</option>
                <option value="efectivo" <?= $filtroMetodo === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                <option value="niubiz" <?= $filtroMetodo === 'niubiz' ? 'selected' : '' ?>>Niubiz POS</option>
            </select>
        </div>
        <div class="fld flex-grow-1" style="min-width: 200px;">
            <label>Buscar Código o Cliente</label>
            <input type="text" name="q" class="form-control" placeholder="Ej: VTA-2026... o nombre del cliente" value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-dark" style="border-radius: 9px; padding: 7px 16px;">
                <i class="bi bi-search me-1"></i> Filtrar
            </button>
            <?php if ($filtroFecha !== '' || $filtroMetodo !== '' || $busqueda !== ''): ?>
                <a href="<?= url('/admin/ventas') ?>" class="btn btn-sm btn-outline-secondary" style="border-radius: 9px; padding: 7px 14px;">
                    Limpiar
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tabla de Ventas -->
<div class="adm-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size: .84rem;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; color: #475569; font-size: .75rem; text-transform: uppercase; letter-spacing: .5px;">
                    <th>Operación</th>
                    <th>Fecha / Hora</th>
                    <th>Cliente</th>
                    <th class="text-center">Artículos</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">Método</th>
                    <th>Atendido por</th>
                    <th class="text-end">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ventas)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-cart-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            No se encontraron registros de ventas con los filtros aplicados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ventas as $v): ?>
                        <tr>
                            <td>
                                <a href="<?= url('/admin/ventas/detalle/' . (int)$v['id']) ?>" class="fw-bold text-decoration-none font-monospace" style="color: #1a7a3a;">
                                    <?= htmlspecialchars((string)$v['codigo_operacion'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td>
                                <div><?= htmlspecialchars(date('d/m/Y', strtotime((string)$v['created_at'])), ENT_QUOTES, 'UTF-8') ?></div>
                                <small class="text-muted"><?= htmlspecialchars(date('H:i', strtotime((string)$v['created_at'])), ENT_QUOTES, 'UTF-8') ?> hrs</small>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">
                                    <?= htmlspecialchars((string)($v['cliente_nombre'] ?? 'Cliente Mostrador'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">
                                    <?= (int)($v['total_items'] ?? 0) ?> unid.
                                </span>
                            </td>
                            <td class="text-end fw-bold text-dark font-monospace fs-6">
                                S/ <?= number_format((float)$v['total'], 2) ?>
                            </td>
                            <td class="text-center">
                                <?php if (($v['estado'] ?? '') === 'anulada'): ?>
                                    <span class="badge bg-danger text-white"><i class="bi bi-x-circle me-1"></i>Anulada</span>
                                <?php else: ?>
                                    <?php
                                    $metodo = (string)$v['metodo_pago'];
                                    $badgeClass = match ($metodo) {
                                        'efectivo' => 'badge-metodo-efectivo',
                                        'niubiz'   => 'badge-metodo-niubiz',
                                        default    => 'bg-secondary text-white',
                                    };
                                    $metodoLabel = match ($metodo) {
                                        'efectivo' => '<i class="bi bi-cash me-1"></i>Efectivo',
                                        'niubiz'   => '<i class="bi bi-credit-card me-1"></i>Niubiz POS',
                                        default    => ucfirst($metodo),
                                    };
                                    ?>
                                    <span class="badge px-2 py-1 <?= $badgeClass ?>">
                                        <?= $metodoLabel ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="text-muted" style="font-size: .8rem;">
                                    <?= htmlspecialchars((string)($v['admin_nombre'] ?? $v['registrado_por_nombre'] ?? 'Administrador'), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('/admin/ventas/detalle/' . (int)$v['id']) ?>" class="btn btn-sm btn-outline-dark" style="border-radius: 7px; padding: 4px 10px; font-size: .78rem;" title="Ver Detalle / Comprobante">
                                    <i class="bi bi-receipt me-1"></i> Detalle
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
