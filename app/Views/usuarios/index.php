<?php
declare(strict_types=1);
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="adm-h1">Usuarios Registrados</h1>
        <p class="adm-sub">Listado de clientes y administradores registrados en el sistema (<?= count($usuarios ?? []) ?> cuentas).</p>
    </div>
    <div>
        <a href="<?= url('/admin/dashboard') ?>" class="btn btn-sm btn-outline-secondary fw-bold" style="border-radius:9px; padding:8px 16px;">
            <i class="bi bi-arrow-left me-1"></i> Volver al Inicio
        </a>
    </div>
</div>

<div class="panel-2">
    <div class="table-responsive">
        <table class="tbl">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre Completo</th>
                    <th>Correo Electrónico</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th class="text-end">Fecha de Registro</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No hay usuarios registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td class="fw-bold text-muted">#<?= $u['id'] ?></td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($u['nombre']) ?></td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($u['email']) ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($u['email']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($u['telefono'] ?: 'No registrado') ?></td>
                            <td>
                                <span class="pill <?= $u['rol'] === 'admin' ? 'ok' : 'fin' ?>">
                                    <?= strtoupper((string)$u['rol']) ?>
                                </span>
                            </td>
                            <td class="text-end text-muted small">
                                <?= date('d/m/Y H:i', strtotime((string)$u['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>