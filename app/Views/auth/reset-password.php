<?php
declare(strict_types=1);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow border-0 rounded-4 overflow-hidden">
                <div class="bg-topgol-dark text-center p-4 pb-2">
                    <div class="brand-ball mb-2">
                        <i class="bi bi-shield-lock-fill text-success fs-1"></i>
                    </div>
                    <h3 class="fw-bold mb-1">Nueva <span class="text-success">Contraseña</span></h3>
                    <p class="text-muted small mb-0">Restablece el acceso para <strong><?= e((string)($email ?? '')) ?></strong></p>
                </div>

                <div class="card-body p-4 p-md-4 bg-white">
                    <form action="<?= url('/reset-password') ?>" method="POST" class="needs-validation" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="token" value="<?= e((string)($token ?? '')) ?>">

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Nueva Contraseña <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" minlength="6" placeholder="Mínimo 6 caracteres" required autofocus>
                                <div class="invalid-feedback">Ingresa una contraseña de al menos 6 caracteres.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirm" class="form-label fw-semibold">Confirmar Nueva Contraseña <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" minlength="6" placeholder="Repite tu nueva contraseña" required>
                                <div class="invalid-feedback">Confirma tu nueva contraseña.</div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-topgol btn-lg w-100 shadow fw-bold mb-3">
                            <i class="bi bi-check2-circle me-1"></i> Guardar Nueva Contraseña
                        </button>

                        <div class="text-center">
                            <a href="<?= url('/login') ?>" class="text-decoration-none fw-semibold text-muted small">
                                <i class="bi bi-x-circle me-1"></i> Cancelar e ir al Login
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
