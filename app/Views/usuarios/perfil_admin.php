<?php
declare(strict_types=1);

/** @var array<string, mixed> $usuario */

$tienePassword = !empty($usuario['password']);
$esGoogle = !empty($usuario['google_id']);
$avatar = (string)($usuario['avatar'] ?? '');
$rolLbl = ($usuario['rol'] ?? 'cliente') === 'admin' ? 'Administrador' : 'Cliente';
$desde = isset($usuario['created_at']) ? date('d/m/Y', strtotime((string)$usuario['created_at'])) : '—';
$inicialPerfil = mb_strtoupper(mb_substr((string)$usuario['nombre'], 0, 1));
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<style>
    .pf-avatar { width: 84px; height: 84px; border-radius: 50%; object-fit: cover; border: 3px solid #22c55e; }
    .pf-inicial { width: 84px; height: 84px; border-radius: 50%; background: #eef7f0; color: #1a7a3a; font-size: 2rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
    .pf-head { background: #0b1e33; border-radius: 12px; padding: 22px; color: #fff; text-align: center; margin-bottom: 16px; }
    .pf-head h5 { margin: 8px 0 2px; font-weight: 800; }
    .pf-head .mail { font-size: .8rem; color: #a9bccd; }
    .pf-list { list-style: none; margin: 0; padding: 0; font-size: .82rem; }
    .pf-list li { display: flex; justify-content: space-between; gap: 8px; padding: 10px 2px; border-bottom: 1px solid #eef2f7; }
    .pf-list li:last-child { border-bottom: none; }
    .pf-list .k { color: #7c8aa0; }
</style>

            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
                <div>
                    <h1 class="adm-h1">Mi perfil</h1>
                    <p class="adm-sub">Administra los datos de tu cuenta.</p>
                </div>
            </div>

            <div class="row g-3">
                <!-- Resumen cuenta -->
                <div class="col-lg-4">
                    <div class="panel">
                        <div class="pf-head">
                            <?php if ($avatar): ?>
                                <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar" class="pf-avatar">
                            <?php else: ?>
                                <span class="pf-inicial"><?= htmlspecialchars($inicialPerfil) ?></span>
                            <?php endif; ?>
                            <h5><?= htmlspecialchars((string)$usuario['nombre']) ?></h5>
                            <div class="mail"><?= htmlspecialchars((string)$usuario['email']) ?></div>
                            <span class="pill fin mt-2"><?= $rolLbl ?></span>
                        </div>
                        <ul class="pf-list">
                            <li><span class="k"><i class="bi bi-telephone me-1"></i> Teléfono</span><strong><?= htmlspecialchars((string)($usuario['telefono'] ?? '—') ?: '—') ?></strong></li>
                            <li><span class="k"><i class="bi bi-google me-1"></i> Cuenta Google</span><strong><?= $esGoogle ? 'Vinculada' : 'No vinculada' ?></strong></li>
                            <li><span class="k"><i class="bi bi-calendar-check me-1"></i> Miembro desde</span><strong><?= htmlspecialchars($desde) ?></strong></li>
                        </ul>
                    </div>
                </div>

                <!-- Formularios -->
                <div class="col-lg-8">
                    <div class="panel-2 mb-4">
                        <h3><i class="bi bi-person-gear me-2" style="color:#1a7a3a;"></i>Datos personales</h3>
                        <form action="<?= url('/perfil/actualizar') ?>" method="POST" class="needs-validation mt-3" novalidate>
                            <?= csrf_field() ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nombre" class="form-label fw-semibold" style="font-size:.8rem;">Nombre y apellido <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars((string)$usuario['nombre']) ?>" minlength="3" required>
                                    <div class="invalid-feedback">Mínimo 3 caracteres.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label fw-semibold" style="font-size:.8rem;">Correo electrónico</label>
                                    <input type="email" class="form-control" id="email" value="<?= htmlspecialchars((string)$usuario['email']) ?>" disabled readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="telefono" class="form-label fw-semibold" style="font-size:.8rem;">Número de contacto (celular / WhatsApp)</label>
                                    <div class="input-group">
                                        <span class="input-group-text" style="background:#f8fafc; font-weight:700; color:#334155; border-color:#e2e8f0;">+51</span>
                                        <input type="tel" class="form-control" id="telefono" name="telefono" value="<?= htmlspecialchars(preg_replace('/^\+51\s*/', '', (string)($usuario['telefono'] ?? ''))) ?>" placeholder="987 654 321">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm fw-bold mt-3" style="background:#1a7a3a;color:#fff;border-radius:9px;padding:9px 18px;">
                                <i class="bi bi-check-lg me-1"></i> Guardar cambios
                            </button>
                        </form>
                    </div>

                    <div class="panel-2">
                        <h3><i class="bi bi-shield-lock me-2" style="color:#1a7a3a;"></i>Cambiar contraseña</h3>
                        <p class="psub mb-3">
                            <?= $tienePassword ? 'Ingresa tu contraseña actual para establecer una nueva.' : 'Tu cuenta usa solo Google. Define una contraseña para también ingresar con correo y clave.' ?>
                        </p>
                        <form action="<?= url('/perfil/password') ?>" method="POST" class="needs-validation" novalidate>
                            <?= csrf_field() ?>
                            <div class="row g-3">
                                <?php if ($tienePassword): ?>
                                    <div class="col-md-4">
                                        <label for="password_actual" class="form-label fw-semibold" style="font-size:.8rem;">Contraseña actual <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="password_actual" name="password_actual" required>
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-4">
                                    <label for="password_nueva" class="form-label fw-semibold" style="font-size:.8rem;">Nueva contraseña <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password_nueva" name="password_nueva" minlength="6" required>
                                    <div class="invalid-feedback">Mínimo 6 caracteres.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="password_confirm" class="form-label fw-semibold" style="font-size:.8rem;">Confirmar <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" minlength="6" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm btn-outline-dark fw-bold mt-3" style="border-radius:9px;padding:9px 18px;">
                                <i class="bi bi-key me-1"></i> Actualizar contraseña
                            </button>
                        </form>
                    </div>
                </div>
            </div>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
