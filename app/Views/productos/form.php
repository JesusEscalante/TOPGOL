<?php
declare(strict_types=1);

/** @var array<int, array<string, mixed>> $categorias */
/** @var array<string, mixed>|null $producto */
/** @var array<int, array<string, mixed>>|null $movimientos */

$esEdicion = $producto !== null;
$p = $producto ?? [];
$movs = $movimientos ?? [];
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
    <div>
        <h1 class="adm-h1"><?= $esEdicion ? 'Editar producto' : 'Nuevo producto' ?></h1>
        <p class="adm-sub"><?= $esEdicion ? htmlspecialchars((string)($p['nombre'] ?? '')) : 'Registra un nuevo producto en el inventario del bar/kiosco.' ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/admin/productos') ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:9px;padding:9px 16px;">
            <i class="bi bi-arrow-left me-1"></i> Volver a productos
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Formulario Principal -->
    <div class="<?= $esEdicion ? 'col-lg-7' : 'col-lg-8' ?>">
        <div class="panel">
            <form action="<?= $esEdicion ? url('/admin/productos/actualizar/' . $p['id']) : url('/admin/productos/guardar') ?>" method="POST" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="nombre" class="form-label fw-semibold" style="font-size:.8rem;">Nombre del producto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars((string)($p['nombre'] ?? '')) ?>" placeholder="Ej: Gatorade 500ml Berry" required>
                        <div class="invalid-feedback">El nombre es obligatorio.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="categoria_id" class="form-label fw-semibold" style="font-size:.8rem;">Categoría <span class="text-danger">*</span></label>
                        <select class="form-select" id="categoria_id" name="categoria_id" required>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>" <?= ((int)($p['categoria_id'] ?? 5) === (int)$cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="descripcion" class="form-label fw-semibold" style="font-size:.8rem;">Descripción opcional</label>
                        <input type="text" class="form-control" id="descripcion" name="descripcion" value="<?= htmlspecialchars((string)($p['descripcion'] ?? '')) ?>" placeholder="Detalles de presentación, sabor, etc.">
                    </div>
                    <div class="col-md-4">
                        <label for="precio" class="form-label fw-semibold" style="font-size:.8rem;">Precio de Venta (S/) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="precio" name="precio" step="0.50" min="0.50" value="<?= htmlspecialchars((string)($p['precio'] ?? '')) ?>" required>
                        <div class="invalid-feedback">Precio mayor a 0.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="stock" class="form-label fw-semibold" style="font-size:.8rem;">Stock actual</label>
                        <input type="number" class="form-control" id="stock" name="stock" min="0" step="1" value="<?= htmlspecialchars((string)($p['stock'] ?? '0')) ?>">
                        <?php if ($esEdicion): ?>
                            <small class="text-muted" style="font-size:.7rem;">Modificar aquí audita un ajuste manual.</small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label for="stock_minimo" class="form-label fw-semibold" style="font-size:.8rem;">Stock mínimo de alerta</label>
                        <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" min="0" step="1" value="<?= htmlspecialchars((string)($p['stock_minimo'] ?? '5')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="unidad" class="form-label fw-semibold" style="font-size:.8rem;">Unidad de medida</label>
                        <select class="form-select" id="unidad" name="unidad">
                            <?php foreach (['unidad', 'botella', 'lata', 'bolsa', 'caja', 'paquete'] as $u): ?>
                                <option value="<?= $u ?>" <?= ($p['unidad'] ?? 'unidad') === $u ? 'selected' : '' ?>><?= ucfirst($u) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="estado" class="form-label fw-semibold" style="font-size:.8rem;">Estado comercial</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="disponible" <?= ($p['estado'] ?? 'disponible') === 'disponible' ? 'selected' : '' ?>>Disponible (En venta)</option>
                            <option value="agotado" <?= ($p['estado'] ?? '') === 'agotado' ? 'selected' : '' ?>>Agotado (Sin stock)</option>
                            <option value="descontinuado" <?= ($p['estado'] ?? '') === 'descontinuado' ? 'selected' : '' ?>>Descontinuado (Ocultar)</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mt-4">
                    <button type="submit" class="btn btn-sm fw-bold" style="background:#1a7a3a;color:#fff;border-radius:9px;padding:10px 22px;">
                        <i class="bi bi-check-lg me-1"></i> <?= $esEdicion ? 'Guardar cambios' : 'Registrar producto' ?>
                    </button>
                    <?php if ($esEdicion): ?>
                        <a href="<?= url('/admin/productos') ?>" class="btn btn-sm btn-link text-muted">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php if ($esEdicion): ?>
    <!-- Tarjeta Lateral de Kardex y Ajuste Rápido -->
    <div class="col-lg-5">
        <!-- Ajuste Rápido de Stock -->
        <div class="panel mb-4">
            <h3 class="fs-6 fw-bold mb-1"><i class="bi bi-arrow-left-right me-2 text-primary"></i>Ingreso / Salida de Stock</h3>
            <p class="text-muted small mb-3">Registra compras a proveedores o mermas con auditoría.</p>

            <form action="<?= url('/admin/productos/stock/' . $p['id']) ?>" method="POST">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="tipo" id="btnIngreso" value="ingreso" checked>
                        <label class="btn btn-sm btn-outline-success fw-bold" for="btnIngreso">
                            <i class="bi bi-box-arrow-in-down me-1"></i> Ingreso
                        </label>
                        <input type="radio" class="btn-check" name="tipo" id="btnSalida" value="salida">
                        <label class="btn btn-sm btn-outline-danger fw-bold" for="btnSalida">
                            <i class="bi bi-box-arrow-up me-1"></i> Salida
                        </label>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold mb-1">Cantidad:</label>
                    <input type="number" class="form-control form-control-sm" name="cantidad" min="1" step="1" value="1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold mb-1">Motivo / Proveedor:</label>
                    <input type="text" class="form-control form-control-sm" name="motivo" placeholder="Ej: Compra factura 001-2490" required>
                </div>
                <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold">
                    <i class="bi bi-save me-1"></i> Registrar en Kardex
                </button>
            </form>
        </div>

        <!-- Últimos Movimientos -->
        <div class="panel">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="fs-6 fw-bold mb-0"><i class="bi bi-clock-history me-2 text-muted"></i>Kardex Reciente</h3>
                <a href="<?= url('/admin/inventario?producto_id=' . $p['id']) ?>" class="small text-decoration-none">Ver todo →</a>
            </div>

            <?php if (empty($movs)): ?>
                <p class="text-muted small py-3 text-center mb-0">Sin movimientos registrados aún.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless small align-middle mb-0" style="font-size:.78rem;">
                        <thead>
                            <tr class="text-muted border-bottom">
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Cant.</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movs as $m): ?>
                                <tr class="border-bottom">
                                    <td class="text-muted"><?= date('d/m H:i', strtotime($m['created_at'])) ?></td>
                                    <td>
                                        <span class="badge <?= $m['tipo'] === 'ingreso' ? 'bg-success' : ($m['tipo'] === 'venta' ? 'bg-primary' : ($m['tipo'] === 'salida' ? 'bg-danger' : 'bg-secondary')) ?>" style="font-size:.65rem;">
                                            <?= strtoupper($m['tipo']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold"><?= (int)$m['cantidad'] ?></td>
                                    <td><span class="text-muted"><?= $m['stock_anterior'] ?> →</span> <strong><?= $m['stock_nuevo'] ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
