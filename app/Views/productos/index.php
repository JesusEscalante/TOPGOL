<?php
declare(strict_types=1);

/** @var array<int, array<string, mixed>> $productos */
/** @var array<int, array<string, mixed>> $categorias */

$filtroCat = $filtroCat ?? '';
$busqueda = $busqueda ?? '';
$filtro = $filtro ?? '';

$estCls = static fn(string $e): string => match ($e) {
    'disponible' => 'ok', 'agotado' => 'warn', 'descontinuado' => 'fin', default => 'fin',
};
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<style>
    .f-bar { display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-end; }
    .f-bar .fld { display: flex; flex-direction: column; gap: 4px; }
    .f-bar label { font-size: .68rem; font-weight: 700; color: #7c8aa0; text-transform: uppercase; letter-spacing: .4px; }
    .f-bar .form-control, .f-bar .form-select { font-size: .82rem; border-radius: 9px; }
    .btn-mini { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #101c33; font-size: .85rem; margin-right: 4px; text-decoration: none; }
    .btn-mini:hover { border-color: #1a7a3a; color: #1a7a3a; }
    .btn-mini.bad:hover { border-color: #dc2626; color: #dc2626; }
    .stock-ok { color: #166534; font-weight: 800; }
    .stock-bajo { color: #92400e; font-weight: 800; }
    .stock-cero { color: #991b1b; font-weight: 800; }
</style>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
    <div>
        <h1 class="adm-h1">Catálogo de Productos</h1>
        <p class="adm-sub">Productos del bar, kiosco y cafetería de Top Gol.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('/admin/ventas/pos') ?>" class="btn btn-sm fw-bold shadow-sm" style="background:#15803d;color:#fff;border-radius:9px;padding:9px 18px;">
            <i class="bi bi-cart-check me-1"></i> Venta Rápida (POS)
        </a>
        <a href="<?= url('/admin/categorias') ?>" class="btn btn-sm btn-outline-dark fw-bold" style="border-radius:9px;padding:9px 16px;">
            <i class="bi bi-tags me-1"></i> Categorías
        </a>
        <a href="<?= url('/admin/inventario') ?>" class="btn btn-sm btn-outline-dark fw-bold" style="border-radius:9px;padding:9px 16px;">
            <i class="bi bi-boxes me-1"></i> Kardex / Stock
        </a>
        <a href="<?= url('/admin/productos/crear') ?>" class="btn btn-sm fw-bold" style="background:#1a7a3a;color:#fff;border-radius:9px;padding:9px 16px;">
            <i class="bi bi-plus-lg me-1"></i> Nuevo producto
        </a>
    </div>
</div>

<!-- Métricas de Productos -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="kpi">
            <span class="kpi-ico b"><i class="bi bi-box"></i></span>
            <div><small class="lbl">Productos en catálogo</small><div class="val"><?= count($productos) ?></div></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi">
            <span class="kpi-ico y"><i class="bi bi-exclamation-triangle"></i></span>
            <div><small class="lbl">Stock bajo</small><div class="val"><?= (int)($stockBajo ?? 0) ?></div></div>
            <div class="foot"><a href="<?= url('/admin/productos?filtro=stock_bajo') ?>">Ver crítica →</a></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi">
            <span class="kpi-ico g"><i class="bi bi-cash-stack"></i></span>
            <div><small class="lbl">Valorización existencias (PVP)</small><div class="val">S/ <?= number_format((float)($valorizacion ?? 0), 2) ?></div></div>
            <div class="foot text-muted" style="font-size:.72rem;">Calculado a precio de venta al público (PVP)</div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="panel-2 mb-4">
    <form action="<?= url('/admin/productos') ?>" method="GET" class="f-bar">
        <div class="fld" style="flex:1;min-width:180px;">
            <label for="pq">Buscar</label>
            <input type="text" class="form-control" id="pq" name="q" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Nombre o descripción...">
        </div>
        <div class="fld">
            <label for="pcat">Categoría</label>
            <select class="form-select" id="pcat" name="categoria">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>" <?= ((string)$filtroCat === (string)$cat['id'] || $filtroCat === $cat['slug']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fld">
            <label for="pfiltro">Filtro de stock</label>
            <select class="form-select" id="pfiltro" name="filtro">
                <option value="">Todo el inventario</option>
                <option value="stock_bajo" <?= $filtro === 'stock_bajo' ? 'selected' : '' ?>>Solo stock bajo</option>
            </select>
        </div>
        <button type="submit" class="btn btn-sm fw-bold" style="background:#101c33;color:#fff;border-radius:9px;padding:9px 18px;">
            <i class="bi bi-funnel me-1"></i> Filtrar
        </button>
        <a href="<?= url('/admin/productos') ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:9px;padding:9px 16px;">Limpiar</a>
    </form>

    <br>

    <div class="table-responsive">
        <table class="tbl align-middle">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock actual</th>
                    <th>Estado</th>
                    <th class="text-end" style="min-width:140px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productos)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay productos con los filtros seleccionados.</td></tr>
                <?php else: ?>
                    <?php foreach ($productos as $p): ?>
                        <?php
                            $stock = (int)$p['stock'];
                            $min = (int)$p['stock_minimo'];
                            $stockCls = $stock <= 0 ? 'stock-cero' : ($stock <= $min ? 'stock-bajo' : 'stock-ok');
                            $ico = $p['categoria_icono'] ?: 'bi-box';
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:32px;height:32px;border-radius:8px;background:#f1f5f9;color:#0f172a;display:flex;align-items:center;justify-content:center;font-size:1rem;">
                                        <i class="bi <?= htmlspecialchars($ico) ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($p['nombre']) ?></div>
                                        <?php if (!empty($p['descripcion'])): ?>
                                            <small class="text-muted" style="font-size:.74rem;"><?= htmlspecialchars($p['descripcion']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="pill fin"><?= htmlspecialchars($p['categoria_nombre'] ?? ucfirst((string)$p['categoria'])) ?></span>
                            </td>
                            <td class="fw-bold fs-6">S/ <?= number_format((float)$p['precio'], 2) ?></td>
                            <td>
                                <span class="<?= $stockCls ?>"><?= $stock ?></span>
                                <small class="text-muted">/ mín. <?= $min ?> <?= htmlspecialchars($p['unidad']) ?></small>
                            </td>
                            <td>
                                <span class="pill <?= $estCls($p['estado']) ?>">
                                    <?= ucfirst($p['estado']) ?>
                                </span>
                            </td>
                            <td class="text-end" style="white-space:nowrap;">
                                <!-- Ajustar Stock Rápido (Modal) -->
                                <button type="button" class="btn-mini btn-stock"
                                        data-id="<?= (int)$p['id'] ?>"
                                        data-nombre="<?= htmlspecialchars($p['nombre']) ?>"
                                        data-stock="<?= $stock ?>"
                                        title="Ajustar Stock (Ingreso / Salida)">
                                    <i class="bi bi-arrow-left-right text-primary"></i>
                                </button>

                                <!-- Editar -->
                                <a href="<?= url('/admin/productos/editar/' . $p['id']) ?>" class="btn-mini" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <!-- Descontinuar (si está disponible o agotado) -->
                                <?php if ($p['estado'] !== 'descontinuado'): ?>
                                    <form action="<?= url('/admin/productos/descontinuar/' . $p['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Marcar \'<?= htmlspecialchars(addslashes($p['nombre'])) ?>\' como descontinuado?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn-mini" title="Descontinuar (ocultar de venta)">
                                            <i class="bi bi-archive text-warning"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Eliminar (con protección) -->
                                <form action="<?= url('/admin/productos/eliminar/' . $p['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Intentar eliminar \'<?= htmlspecialchars(addslashes($p['nombre'])) ?>\' del catálogo?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn-mini bad" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ajuste Rápido de Stock -->
<div class="modal fade" id="modalAjusteStock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <form id="formAjusteStock" action="" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-boxes me-2 text-primary"></i>Ajuste de Stock: <span id="modalProdNombre" class="text-dark"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="alert alert-light border small text-muted mb-3 py-2">
                        Stock actual en bar: <strong id="modalStockActual" class="text-dark fs-6">0</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-uppercase">Tipo de movimiento <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo" id="tipoIngreso" value="ingreso" checked>
                                <label class="form-check-label fw-bold text-success" for="tipoIngreso">
                                    <i class="bi bi-box-arrow-in-down me-1"></i> Ingreso (Compra / Reabastecimiento)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo" id="tipoSalida" value="salida">
                                <label class="form-check-label fw-bold text-danger" for="tipoSalida">
                                    <i class="bi bi-box-arrow-up me-1"></i> Salida (Merma / Daño)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="ajuste_cantidad" class="form-label fw-semibold small text-uppercase">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="ajuste_cantidad" name="cantidad" min="1" step="1" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="ajuste_motivo" class="form-label fw-semibold small text-uppercase">Motivo / Observación <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="ajuste_motivo" name="motivo" placeholder="Ej: Compra proveedor Distribuidora Sur / Vencimiento" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm fw-bold" style="background:#101c33;color:#fff;border-radius:8px;">Guardar Movimiento en Kardex</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var stockButtons = document.querySelectorAll('.btn-stock');
    var formAjuste = document.getElementById('formAjusteStock');
    var modalAjuste = new bootstrap.Modal(document.getElementById('modalAjusteStock'));

    stockButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.getAttribute('data-id');
            var nombre = this.getAttribute('data-nombre');
            var stock = this.getAttribute('data-stock');

            formAjuste.action = '<?= url('/admin/productos/stock/') ?>' + id;
            document.getElementById('modalProdNombre').textContent = nombre;
            document.getElementById('modalStockActual').textContent = stock;
            document.getElementById('ajuste_cantidad').value = '1';
            document.getElementById('ajuste_motivo').value = '';

            modalAjuste.show();
        });
    });
});
</script>

<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
