<?php
declare(strict_types=1);

/** @var array<string, mixed> $usuario */

$tienePassword = !empty($usuario['password']);
$esGoogle = !empty($usuario['google_id']);
$avatar = (string)($usuario['avatar'] ?? '');
$rolLbl = ($usuario['rol'] ?? 'cliente') === 'admin' ? 'Administrador' : 'Cliente';
$desde = isset($usuario['created_at']) ? date('d/m/Y', strtotime((string)$usuario['created_at'])) : '—';
?>

<div class="container py-4">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
        <div>
            <h1 class="h2 fw-bold text-dark mb-0">Mi perfil</h1>
            <p class="text-muted mb-0">Administra los datos de tu cuenta.</p>
        </div>
        <a href="<?= url('/mis-reservas') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-calendar-event me-1"></i> Mis reservas
        </a>
    </div>

    <div class="row g-4">
        <!-- Resumen cuenta -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden h-100">
                <div class="bg-topgol-dark text-center p-4">
                    <?php if ($avatar): ?>
                        <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" class="rounded-circle mb-2" style="width:84px;height:84px;object-fit:cover;border:3px solid var(--tg-gold);">
                    <?php else: ?>
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light mb-2" style="width:84px;height:84px;font-size:2rem;font-weight:800;color:var(--tg-green);">
                            <?= htmlspecialchars(mb_strtoupper(mb_substr((string)$usuario['nombre'], 0, 1))) ?>
                        </span>
                    <?php endif; ?>
                    <h5 class="fw-bold text-white mb-1"><?= htmlspecialchars((string)$usuario['nombre']) ?></h5>
                    <div class="small text-light opacity-75"><?= htmlspecialchars((string)$usuario['email']) ?></div>
                    <span class="badge mt-2 <?= ($usuario['rol'] ?? '') === 'admin' ? 'text-dark' : 'bg-success' ?>" <?= ($usuario['rol'] ?? '') === 'admin' ? 'style="background:var(--tg-gold);"' : '' ?>><?= $rolLbl ?></span>
                </div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-telephone me-1"></i> Teléfono</span>
                        <strong><?= htmlspecialchars((string)($usuario['telefono'] ?? '—') ?: '—') ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-google me-1"></i> Cuenta Google</span>
                        <strong class="<?= $esGoogle ? 'text-success' : 'text-muted' ?>"><?= $esGoogle ? 'Vinculada' : 'No vinculada' ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-calendar-check me-1"></i> Miembro desde</span>
                        <strong><?= htmlspecialchars($desde) ?></strong>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Formularios -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-person-gear me-2 text-success"></i>Datos personales</h5>
                    <form action="<?= url('/perfil/actualizar') ?>" method="POST" class="needs-validation" novalidate>
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label fw-semibold">Nombre y apellido <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars((string)$usuario['nombre']) ?>" minlength="3" required>
                                <div class="invalid-feedback">Mínimo 3 caracteres.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold">Correo electrónico</label>
                                <input type="email" class="form-control" id="email" value="<?= htmlspecialchars((string)$usuario['email']) ?>" disabled readonly>
                                <div class="form-text">El correo no se puede modificar.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="telefono" class="form-label fw-semibold">Número de contacto (celular / WhatsApp)</label>
                                <div class="input-group">
                                    <span class="input-group-text" style="background:#f8fafc; font-weight:700; color:#334155; border-color:#e2e8f0;">+51</span>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" value="<?= htmlspecialchars(preg_replace('/^\+51\s*/', '', (string)($usuario['telefono'] ?? ''))) ?>" placeholder="987 654 321">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-topgol fw-bold mt-3 px-4">
                            <i class="bi bi-check-lg me-1"></i> Guardar cambios
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-1"><i class="bi bi-shield-lock me-2 text-success"></i>Cambiar contraseña</h5>
                    <p class="text-muted small mb-3">
                        <?= $tienePassword ? 'Ingresa tu contraseña actual para establecer una nueva.' : 'Tu cuenta usa solo Google. Define una contraseña para también ingresar con correo y clave.' ?>
                    </p>
                    <form action="<?= url('/perfil/password') ?>" method="POST" class="needs-validation" novalidate>
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <?php if ($tienePassword): ?>
                                <div class="col-md-4">
                                    <label for="password_actual" class="form-label fw-semibold">Contraseña actual <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password_actual" name="password_actual" required>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-4">
                                <label for="password_nueva" class="form-label fw-semibold">Nueva contraseña <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password_nueva" name="password_nueva" minlength="6" required>
                                <div class="invalid-feedback">Mínimo 6 caracteres.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="password_confirm" class="form-label fw-semibold">Confirmar <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" minlength="6" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-outline-dark fw-bold mt-3 px-4">
                            <i class="bi bi-key me-1"></i> Actualizar contraseña
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
