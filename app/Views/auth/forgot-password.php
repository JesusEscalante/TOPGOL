<?php
declare(strict_types=1);
$expiraMin = defined('PASSWORD_RESET_EXPIRA_MINUTOS') ? (int)PASSWORD_RESET_EXPIRA_MINUTOS : 30;
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow border-0 rounded-4 overflow-hidden">
                <div class="bg-topgol-dark text-center p-4 pb-2">
                    <div class="brand-ball mb-2">
                        <i class="bi bi-envelope-paper-fill text-success fs-1"></i>
                    </div>
                    <h3 class="fw-bold mb-1">Recuperar <span class="text-success">Contraseña</span></h3>
                    <p class="text-muted small mb-0">Te enviaremos un enlace seguro a tu correo electrónico</p>
                </div>

                <div class="card-body p-4 p-md-4 bg-white">
                    <?php if (!empty($devResetUrl)): ?>
                        <div class="alert alert-warning border-warning small mb-4" role="alert">
                            <div class="fw-bold mb-1">
                                <i class="bi bi-code-slash me-1"></i> Modo Desarrollo (Sin SMTP configurado)
                            </div>
                            <p class="mb-2">El correo también fue registrado en <code>storage/logs/emails.log</code>. Puedes probar el restablecimiento directamente aquí:</p>
                            <a href="<?= e((string)$devResetUrl) ?>" class="btn btn-sm btn-warning fw-semibold w-100">
                                <i class="bi bi-key-fill me-1"></i> Abrir enlace de recuperación
                            </a>
                        </div>
                    <?php endif; ?>

                    <p class="text-muted small mb-4">
                        Ingresa el correo electrónico asociado a tu cuenta de <strong>TOP GOL</strong>. Recibirás un enlace temporal válido por <strong><?= $expiraMin ?> minutos</strong> para crear una nueva contraseña.
                    </p>

                    <form action="<?= url('/forgot-password') ?>" method="POST" class="needs-validation" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-4">
                            <label for="email" class="form-label fw-semibold">Correo Electrónico Registrado</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="ejemplo@correo.com" required autofocus>
                                <div class="invalid-feedback">Ingresa un correo electrónico válido.</div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-topgol btn-lg w-100 shadow fw-bold mb-3">
                            <i class="bi bi-send-fill me-1"></i> Enviar Enlace de Recuperación
                        </button>

                        <div class="text-center">
                            <a href="<?= url('/login') ?>" class="text-decoration-none fw-semibold text-success small">
                                <i class="bi bi-arrow-left me-1"></i> Volver a Iniciar Sesión
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
