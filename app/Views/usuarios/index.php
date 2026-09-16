<?php
declare(strict_types=1);
?>

<div class="container py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
        <div>
            <span class="badge bg-gold text-dark mb-1">Módulo Administrativo</span>
            <h1 class="h2 fw-bold text-dark mb-0">Usuarios Registrados</h1>
        </div>
        <div>
            <a href="<?= url('/admin/dashboard') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver al Dashboard
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Nombre Completo</th>
                        <th>Correo Electrónico</th>
                        <th>Teléfono</th>
                        <th>Rol</th>
                        <th class="text-end pe-4">Fecha de Registro</th>
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
                                <td class="ps-4 fw-bold text-muted">#<?= $u['id'] ?></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($u['nombre']) ?></td>
                                <td>
                                    <a href="mailto:<?= htmlspecialchars($u['email']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($u['email']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($u['telefono'] ?: 'No registrado') ?></td>
                                <td>
                                    <span class="badge <?= $u['rol'] === 'admin' ? 'bg-gold text-dark' : 'bg-secondary' ?> rounded-pill px-3 py-1">
                                        <?= strtoupper($u['rol']) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4 text-muted small">
                                    <?= date('d/m/Y H:i', strtotime($u['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>