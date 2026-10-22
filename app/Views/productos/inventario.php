<?php
declare(strict_types=1);

/** @var array<int, array<string, mixed>> $movimientos */
/** @var array<int, array<string, mixed>> $productos */
/** @var array<int, array<string, mixed>> $stockBajo */
/** @var int $filtroProd */
$stockBajo = $stockBajo ?? [];
/** @var string $filtroTipo */
/** @var string $filtroFecha */

$tiposLabels = [
    'ingreso'        => ['label' => 'Ingreso', 'class' => 'bg-success text-white', 'ico' => 'bi-box-arrow-in-down'],
    'salida'         => ['label' => 'Salida', 'class' => 'bg-danger text-white', 'ico' => 'bi-box-arrow-up'],
    'venta'          => ['label' => 'Venta', 'class' => 'bg-primary text-white', 'ico' => 'bi-cart-check'],
    'ajuste'         => ['label' => 'Ajuste', 'class' => 'bg-warning text-dark', 'ico' => 'bi-sliders'],
    'saldo_apertura' => ['label' => 'Apertura', 'class' => 'bg-secondary text-white', 'ico' => 'bi-flag'],
];
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
    <div>
        <h1 class="adm-h1">Kardex de Inventario y Movimientos</h1>
        <p class="adm-sub">Auditoría completa de ingresos, salidas, ventas y ajustes de stock en Top Gol.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/admin/productos') ?>" class="btn btn-sm btn-outline-dark fw-bold" style="border-radius:9px;padding:9px 16px;">
            <i class="bi bi-box me-1"></i> Catálogo de productos
        </a>
        <a href="<?= url('/admin/ventas/pos') ?>" class="btn btn-sm fw-bold shadow-sm" style="background:#15803d;color:#fff;border-radius:9px;padding:9px 16px;">
            <i class="bi bi-cart-check me-1"></i> Venta rápida POS
        </a>
    </div>
</div>

<!-- Stock bajo: el aviso corto está en el Inicio (Atención de hoy); aquí va el detalle -->
<div class="panel-2 mb-4" id="stock-bajo">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h3 class="m-0" style="font-size:1.02rem;font-weight:800;">Stock bajo</h3>
        <span class="badge <?= $stockBajo ? 'bg-danger' : 'bg-success' ?>"><?= count($stockBajo) ?> producto(s)</span>
    </div>
    <?php if (empty($stockBajo)): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 m-0 p-2 small">
            <i class="bi bi-check-circle-fill"></i> Todos los productos están por encima de su stock mínimo.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="tbl tbl-cards align-middle">
                <thead><tr><th>Producto</th><th>Estado</th><th>Stock actual</th><th>Mínimo</th></tr></thead>
                <tbody>
                    <?php foreach ($stockBajo as $sb): ?>
                        <?php $agotado = (int)$sb['stock'] === 0; ?>
                        <tr>
                            <td data-label="Producto"><strong><?= htmlspecialchars((string)$sb['nombre']) ?></strong><small class="text-muted d-block"><?= htmlspecialchars((string)($sb['categoria_nombre'] ?? 'General')) ?></small></td>
                            <td data-label="Estado"><span class="badge <?= $agotado ? 'bg-dark' : 'bg-danger' ?>"><?= $agotado ? 'Agotado' : 'Stock bajo' ?></span></td>
                            <td data-label="Stock actual"><?= (int)$sb['stock'] ?> <?= htmlspecialchars((string)($sb['unidad'] ?? 'und')) ?></td>
                            <td data-label="Mínimo"><?= (int)$sb['stock_minimo'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Filtros de Kardex -->
<div class="panel-2 mb-4">
    <form action="<?= url('/admin/inventario') ?>" method="GET" class="f-bar">
        <div class="fld" style="flex:1;min-width:200px;">
            <label for="f_prod">Producto</label>
            <select class="form-select" id="f_prod" name="producto_id">
                <option value="">Todos los productos</option>
                <?php foreach ($productos as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= $filtroProd === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nombre']) ?> (Stock: <?= $p['stock'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fld">
            <label for="f_tipo">Tipo de Movimiento</label>
            <select class="form-select" id="f_tipo" name="tipo">
                <option value="">Todos los tipos</option>
                <option value="ingreso" <?= $filtroTipo === 'ingreso' ? 'selected' : '' ?>>Ingresos (Compras)</option>
                <option value="salida" <?= $filtroTipo === 'salida' ? 'selected' : '' ?>>Salidas (Mermas)</option>
                <option value="venta" <?= $filtroTipo === 'venta' ? 'selected' : '' ?>>Ventas Mostrador</option>
                <option value="ajuste" <?= $filtroTipo === 'ajuste' ? 'selected' : '' ?>>Ajustes Manuales</option>
                <option value="saldo_apertura" <?= $filtroTipo === 'saldo_apertura' ? 'selected' : '' ?>>Saldos de Apertura</option>
            </select>
        </div>
        <div class="fld">
            <label for="f_fecha">Fecha</label>
            <input type="date" class="form-control" id="f_fecha" name="fecha" value="<?= htmlspecialchars($filtroFecha) ?>">
        </div>
        <button type="submit" class="btn btn-sm fw-bold" style="background:#101c33;color:#fff;border-radius:9px;padding:9px 18px;">
            <i class="bi bi-funnel me-1"></i> Filtrar Kardex
        </button>
        <a href="<?= url('/admin/inventario') ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:9px;padding:9px 16px;">Limpiar</a>
    </form>

    <br>

    <div class="table-responsive">
        <table class="tbl align-middle">
            <thead>
                <tr>
                    <th style="width:130px;">Fecha y Hora</th>
                    <th>Producto</th>
                    <th>Tipo</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-center">Stock Anterior</th>
                    <th class="text-center">Stock Resultante</th>
                    <th>Responsable</th>
                    <th>Motivo / Trazabilidad</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movimientos)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No hay movimientos registrados con los filtros seleccionados.</td></tr>
                <?php else: ?>
                    <?php foreach ($movimientos as $m): ?>
                        <?php
                            $tipoInfo = $tiposLabels[$m['tipo']] ?? ['label' => strtoupper((string)$m['tipo']), 'class' => 'bg-secondary text-white', 'ico' => 'bi-dot'];
                            $esPositivo = in_array($m['tipo'], ['ingreso', 'saldo_apertura'], true);
                            $esVenta = ($m['tipo'] === 'venta');
                        ?>
                        <tr>
                            <td class="small text-muted" style="white-space:nowrap;">
                                <i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?>
                            </td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($m['producto_nombre']) ?></strong>
                                <small class="text-muted d-block" style="font-size:.72rem;"><?= htmlspecialchars($m['categoria_nombre'] ?? '') ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $tipoInfo['class'] ?>" style="font-size:.72rem;padding:4px 8px;border-radius:6px;">
                                    <i class="bi <?= $tipoInfo['ico'] ?> me-1"></i> <?= $tipoInfo['label'] ?>
                                </span>
                            </td>
                            <td class="text-center fw-bold fs-6 <?= $esPositivo ? 'text-success' : ($esVenta ? 'text-primary' : 'text-danger') ?>">
                                <?= $esPositivo ? '+' : '-' ?><?= (int)$m['cantidad'] ?>
                            </td>
                            <td class="text-center text-muted fw-semibold">
                                <?= (int)$m['stock_anterior'] ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border fw-bold fs-6" style="padding:4px 10px;">
                                    <?= (int)$m['stock_nuevo'] ?> <?= htmlspecialchars($m['unidad'] ?? '') ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-dark fw-semibold"><?= htmlspecialchars($m['admin_nombre'] ?? 'Sistema / Encargado') ?></small>
                            </td>
                            <td>
                                <span class="small text-muted"><?= htmlspecialchars($m['motivo']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
