<?php
declare(strict_types=1);

/** @var array<int, array<string, mixed>> $productos */
/** @var array<string, string> $categorias */

$filtroCat = $filtroCat ?? '';
$busqueda = $busqueda ?? '';
$filtro = $filtro ?? '';

$catIco = [
    'refrescos' => '🥤', 'bebidas_alcoholicas' => '🍺', 'snacks' => '🍿',
    'hidratantes' => '💧', 'otros' => '📦',
];
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
                    <h1 class="adm-h1">Productos</h1>
                    <p class="adm-sub">Inventario del bar: refrescos, bebidas, snacks y más.</p>
                </div>
                <a href="<?= url('/admin/productos/crear') ?>" class="btn btn-sm fw-bold" style="background:#1a7a3a;color:#fff;border-radius:9px;padding:9px 16px;">
                    <i class="bi bi-plus-lg me-1"></i> Nuevo producto
                </a>
            </div>

            <!-- Métricas -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="kpi">
                        <span class="kpi-ico b"><i class="bi bi-box"></i></span>
                        <div><small class="lbl">Productos</small><div class="val"><?= count($productos) ?></div></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kpi">
                        <span class="kpi-ico y"><i class="bi bi-exclamation-triangle"></i></span>
                        <div><small class="lbl">Stock bajo</small><div class="val"><?= (int)($stockBajo ?? 0) ?></div></div>
                        <div class="foot"><a href="<?= url('/admin/productos?filtro=stock_bajo') ?>">Ver →</a></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kpi">
                        <span class="kpi-ico g"><i class="bi bi-cash-stack"></i></span>
                        <div><small class="lbl">Valorizado</small><div class="val">S/ <?= number_format((float)($valorizacion ?? 0), 0) ?></div></div>
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
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $v => $l): ?>
                                <option value="<?= $v ?>" <?= $filtroCat === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fld">
                        <label for="pfiltro">Stock</label>
                        <select class="form-select" id="pfiltro" name="filtro">
                            <option value="">Todo</option>
                            <option value="stock_bajo" <?= $filtro === 'stock_bajo' ? 'selected' : '' ?>>Stock bajo</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm fw-bold" style="background:#101c33;color:#fff;border-radius:9px;padding:9px 18px;">
                        <i class="bi bi-funnel me-1"></i> Filtrar
                    </button>
                    <a href="<?= url('/admin/productos') ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:9px;padding:9px 16px;">Limpiar</a>
                </form>

                <br>

                <div class="table-responsive">
                <table class="tbl">
                    <thead>
                        <tr><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Estado</th><th class="text-end">Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($productos)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No hay productos con esos filtros.</td></tr>
                        <?php else: ?>
                        <?php foreach ($productos as $p): ?>
                            <?php
                                $stock = (int)$p['stock'];
                                $min = (int)$p['stock_minimo'];
                                $stockCls = $stock <= 0 ? 'stock-cero' : ($stock <= $min ? 'stock-bajo' : 'stock-ok');
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><span style="font-size:1rem;margin-right:6px;"><?= $catIco[$p['categoria']] ?? '📦' ?></span><?= htmlspecialchars($p['nombre']) ?></div>
                                    <?php if (!empty($p['descripcion'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($p['descripcion']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="pill fin"><?= htmlspecialchars($categorias[$p['categoria']] ?? $p['categoria']) ?></span></td>
                                <td class="fw-bold">S/ <?= number_format((float)$p['precio'], 2) ?></td>
                                <td><span class="<?= $stockCls ?>"><?= $stock ?></span> <small class="text-muted">/ mín. <?= $min ?> <?= htmlspecialchars($p['unidad']) ?></small></td>
                                <td><span class="pill <?= $estCls($p['estado']) ?>"><?= ucfirst($p['estado']) ?></span></td>
                                <td class="text-end" style="white-space:nowrap;">
                                    <a href="<?= url('/admin/productos/editar/' . $p['id']) ?>" class="btn-mini" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <a href="<?= url('/admin/productos/eliminar/' . $p['id']) ?>" class="btn-mini bad" onclick="return confirm('¿Eliminar \'<?= htmlspecialchars(str_replace("'", '', $p['nombre'])) ?>\' del inventario?')" title="Eliminar"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
