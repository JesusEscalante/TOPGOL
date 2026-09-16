<?php
declare(strict_types=1);

/** @var array<string, string> $categorias */
/** @var array<string, mixed>|null $producto */

$esEdicion = $producto !== null;
$p = $producto ?? [];
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
                <div>
                    <h1 class="adm-h1"><?= $esEdicion ? 'Editar producto' : 'Nuevo producto' ?></h1>
                    <p class="adm-sub"><?= $esEdicion ? htmlspecialchars((string)($p['nombre'] ?? '')) : 'Registra un producto en el inventario.' ?></p>
                </div>
                <a href="<?= url('/admin/productos') ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:9px;padding:9px 16px;">
                    <i class="bi bi-arrow-left me-1"></i> Volver
                </a>
            </div>

            <div class="panel" style="max-width:760px;">
                <form action="<?= $esEdicion ? url('/admin/productos/actualizar/' . $p['id']) : url('/admin/productos/guardar') ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="nombre" class="form-label fw-semibold" style="font-size:.8rem;">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars((string)($p['nombre'] ?? '')) ?>" placeholder="Ej: Gatorade 500ml" required>
                            <div class="invalid-feedback">El nombre es obligatorio.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="categoria" class="form-label fw-semibold" style="font-size:.8rem;">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" id="categoria" name="categoria" required>
                                <?php foreach ($categorias as $v => $l): ?>
                                    <option value="<?= $v ?>" <?= ($p['categoria'] ?? 'otros') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="descripcion" class="form-label fw-semibold" style="font-size:.8rem;">Descripción</label>
                            <input type="text" class="form-control" id="descripcion" name="descripcion" value="<?= htmlspecialchars((string)($p['descripcion'] ?? '')) ?>" placeholder="Detalle opcional del producto">
                        </div>
                        <div class="col-md-4">
                            <label for="precio" class="form-label fw-semibold" style="font-size:.8rem;">Precio (S/) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="precio" name="precio" step="0.50" min="0.50" value="<?= htmlspecialchars((string)($p['precio'] ?? '')) ?>" required>
                            <div class="invalid-feedback">Precio mayor a 0.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="stock" class="form-label fw-semibold" style="font-size:.8rem;">Stock actual</label>
                            <input type="number" class="form-control" id="stock" name="stock" min="0" step="1" value="<?= htmlspecialchars((string)($p['stock'] ?? '0')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="stock_minimo" class="form-label fw-semibold" style="font-size:.8rem;">Stock mínimo</label>
                            <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" min="0" step="1" value="<?= htmlspecialchars((string)($p['stock_minimo'] ?? '5')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="unidad" class="form-label fw-semibold" style="font-size:.8rem;">Unidad</label>
                            <select class="form-select" id="unidad" name="unidad">
                                <?php foreach (['unidad', 'botella', 'lata', 'bolsa', 'caja', 'paquete'] as $u): ?>
                                    <option value="<?= $u ?>" <?= ($p['unidad'] ?? 'unidad') === $u ? 'selected' : '' ?>><?= ucfirst($u) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="estado" class="form-label fw-semibold" style="font-size:.8rem;">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <?php foreach (['disponible' => 'Disponible', 'agotado' => 'Agotado', 'descontinuado' => 'Descontinuado'] as $v => $l): ?>
                                    <option value="<?= $v ?>" <?= ($p['estado'] ?? 'disponible') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm fw-bold mt-4" style="background:#1a7a3a;color:#fff;border-radius:9px;padding:10px 22px;">
                        <i class="bi bi-check-lg me-1"></i> <?= $esEdicion ? 'Guardar cambios' : 'Registrar producto' ?>
                    </button>
                </form>
            </div>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
