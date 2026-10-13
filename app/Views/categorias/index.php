<?php
declare(strict_types=1);

/** @var array<int, array<string, mixed>> $categorias */
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
    <div>
        <h1 class="adm-h1">Categorías de Productos</h1>
        <p class="adm-sub">Gestión dinámica de categorías del bar, kiosco y cafetería de Top Gol.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/admin/productos') ?>" class="btn btn-sm btn-outline-dark fw-bold" style="border-radius:9px;padding:9px 16px;">
            <i class="bi bi-box me-1"></i> Catálogo de productos
        </a>
        <button type="button" class="btn btn-sm fw-bold" style="background:#1a7a3a;color:#fff;border-radius:9px;padding:9px 16px;" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria">
            <i class="bi bi-plus-lg me-1"></i> Nueva categoría
        </button>
    </div>
</div>

<div class="panel-2 mb-4">
    <div class="table-responsive">
        <table class="tbl align-middle">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Categoría</th>
                    <th>Slug</th>
                    <th>Descripción</th>
                    <th>Productos asociados</th>
                    <th>Estado</th>
                    <th class="text-end" style="width:140px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categorias)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay categorías registradas.</td></tr>
                <?php else: ?>
                    <?php foreach ($categorias as $c): ?>
                        <tr>
                            <td class="text-muted fw-bold">#<?= (int)$c['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:34px;height:34px;border-radius:8px;background:#eef7f0;color:#1a7a3a;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
                                        <i class="bi <?= htmlspecialchars($c['icono'] ?: 'bi-box') ?>"></i>
                                    </div>
                                    <strong class="text-dark"><?= htmlspecialchars($c['nombre']) ?></strong>
                                </div>
                            </td>
                            <td><code><?= htmlspecialchars($c['slug']) ?></code></td>
                            <td class="small text-muted"><?= htmlspecialchars($c['descripcion'] ?? '—') ?></td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-box me-1 text-success"></i> <?= (int)($c['total_productos'] ?? 0) ?> producto(s)
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($c['activo'])): ?>
                                    <span class="pill ok"><i class="bi bi-check-circle me-1"></i>Activa</span>
                                <?php else: ?>
                                    <span class="pill fin"><i class="bi bi-dash-circle me-1"></i>Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end" style="white-space:nowrap;">
                                <!-- Toggle Activo -->
                                <form action="<?= url('/admin/categorias/toggle/' . $c['id']) ?>" method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn-mini" title="<?= !empty($c['activo']) ? 'Desactivar' : 'Activar' ?>">
                                        <i class="bi <?= !empty($c['activo']) ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted' ?>" style="font-size:1.1rem;"></i>
                                    </button>
                                </form>

                                <!-- Editar (abre modal) -->
                                <button type="button" class="btn-mini btn-editar-cat"
                                        data-id="<?= (int)$c['id'] ?>"
                                        data-nombre="<?= htmlspecialchars($c['nombre']) ?>"
                                        data-slug="<?= htmlspecialchars($c['slug']) ?>"
                                        data-icono="<?= htmlspecialchars($c['icono'] ?: 'bi-box') ?>"
                                        data-descripcion="<?= htmlspecialchars($c['descripcion'] ?? '') ?>"
                                        data-activo="<?= (int)$c['activo'] ?>"
                                        title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <!-- Eliminar (con confirmación) -->
                                <form action="<?= url('/admin/categorias/eliminar/' . $c['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar la categoría \'<?= htmlspecialchars(addslashes($c['nombre'])) ?>\'?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn-mini bad" title="Eliminar" <?= (int)($c['total_productos'] ?? 0) > 0 ? 'disabled style="opacity:.4;" title="No se puede eliminar porque tiene productos asociados"' : '' ?>>
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

<!-- Modal Nueva Categoría -->
<div class="modal fade" id="modalNuevaCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <form action="<?= url('/admin/categorias/guardar') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-tag-fill me-2 text-success"></i>Nueva Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label for="cat_nombre" class="form-label fw-semibold small text-uppercase">Nombre de la categoría <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cat_nombre" name="nombre" placeholder="Ej: Bebidas energéticas" required>
                    </div>
                    <div class="mb-3">
                        <label for="cat_slug" class="form-label fw-semibold small text-uppercase">Slug (identificador único)</label>
                        <input type="text" class="form-control" id="cat_slug" name="slug" placeholder="Opcional (se genera automáticamente)">
                    </div>
                    <div class="mb-3">
                        <label for="cat_icono" class="form-label fw-semibold small text-uppercase">Ícono Bootstrap</label>
                        <select class="form-select" id="cat_icono" name="icono">
                            <option value="bi-cup-straw">🥤 Refrescos (bi-cup-straw)</option>
                            <option value="bi-cup-hot">🍺 Bebidas (bi-cup-hot)</option>
                            <option value="bi-basket">🍿 Snacks (bi-basket)</option>
                            <option value="bi-droplet">💧 Hidratantes (bi-droplet)</option>
                            <option value="bi-lightning">⚡ Energizantes (bi-lightning)</option>
                            <option value="bi-cookie">🍪 Galletas (bi-cookie)</option>
                            <option value="bi-box" selected>📦 Artículos / Otros (bi-box)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cat_desc" class="form-label fw-semibold small text-uppercase">Descripción</label>
                        <textarea class="form-control" id="cat_desc" name="descripcion" rows="2" placeholder="Detalle informativo opcional"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="cat_activo" name="activo" value="1" checked>
                        <label class="form-check-label small fw-semibold" for="cat_activo">Categoría activa en catálogo y mostrador</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm fw-bold" style="background:#1a7a3a;color:#fff;border-radius:8px;">Guardar Categoría</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Categoría -->
<div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius:14px;">
            <form id="formEditarCat" action="" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Editar Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="mb-3">
                        <label for="edit_cat_nombre" class="form-label fw-semibold small text-uppercase">Nombre de la categoría <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_cat_nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_cat_slug" class="form-label fw-semibold small text-uppercase">Slug</label>
                        <input type="text" class="form-control" id="edit_cat_slug" name="slug">
                    </div>
                    <div class="mb-3">
                        <label for="edit_cat_icono" class="form-label fw-semibold small text-uppercase">Ícono</label>
                        <select class="form-select" id="edit_cat_icono" name="icono">
                            <option value="bi-cup-straw">🥤 Refrescos (bi-cup-straw)</option>
                            <option value="bi-cup-hot">🍺 Bebidas (bi-cup-hot)</option>
                            <option value="bi-basket">🍿 Snacks (bi-basket)</option>
                            <option value="bi-droplet">💧 Hidratantes (bi-droplet)</option>
                            <option value="bi-lightning">⚡ Energizantes (bi-lightning)</option>
                            <option value="bi-cookie">🍪 Galletas (bi-cookie)</option>
                            <option value="bi-box">📦 Artículos / Otros (bi-box)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_cat_desc" class="form-label fw-semibold small text-uppercase">Descripción</label>
                        <textarea class="form-control" id="edit_cat_desc" name="descripcion" rows="2"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="edit_cat_activo" name="activo" value="1">
                        <label class="form-check-label small fw-semibold" for="edit_cat_activo">Categoría activa</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-bold" style="border-radius:8px;">Actualizar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var editButtons = document.querySelectorAll('.btn-editar-cat');
    var formEditar = document.getElementById('formEditarCat');
    var modalEditar = new bootstrap.Modal(document.getElementById('modalEditarCategoria'));

    editButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.getAttribute('data-id');
            var nombre = this.getAttribute('data-nombre');
            var slug = this.getAttribute('data-slug');
            var icono = this.getAttribute('data-icono');
            var desc = this.getAttribute('data-descripcion');
            var activo = this.getAttribute('data-activo') === '1';

            formEditar.action = '<?= url('/admin/categorias/actualizar/') ?>' + id;
            document.getElementById('edit_cat_nombre').value = nombre;
            document.getElementById('edit_cat_slug').value = slug;
            document.getElementById('edit_cat_icono').value = icono;
            document.getElementById('edit_cat_desc').value = desc;
            document.getElementById('edit_cat_activo').checked = activo;

            modalEditar.show();
        });
    });
});
</script>

<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
