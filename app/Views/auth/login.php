<?php
declare(strict_types=1);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow border-0 rounded-4 overflow-hidden">
                <div class="bg-topgol-dark text-center p-4">
                    <div class="brand-ball mb-2">
                        <i class="bi bi-dribbble text-gold fs-1"></i>
                    </div>
                    <h3 class="fw-bold mb-1">TOP <span class="text-gold">GOL</span></h3>
                    <p class="brand-name opacity-75 small mb-0">Ingresa a tu cuenta para gestionar tus reservas</p>
                </div>

                <div class="card-body p-4 p-md-4 bg-white">
                    <form action="<?= url('/login') ?>" method="POST" class="needs-validation" novalidate>
                        <?= csrf_field() ?>

                        <!-- Correo Electrónico -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="ejemplo@correo.com" required autofocus>
                                <div class="invalid-feedback">Ingresa tu correo electrónico registrado.</div>
                            </div>
                        </div>

                        <!-- Contraseña -->
                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Tu contraseña" required>
                                <div class="invalid-feedback">Ingresa tu contraseña.</div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-topgol btn-lg w-100 shadow fw-bold mb-3">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                        </button>

                        <div class="text-center">
                            <span class="text-muted small">¿No tienes cuenta aún?</span>
                            <a href="<?= url('/register') ?>" class="text-decoration-none fw-semibold text-success small ms-1">
                                Regístrate aquí
                            </a>
                        </div>
                    </form>

                    <!-- Separador OR -->
                    <div class="row mb-3">
                        <div class="col-12 text-center">
                            <div class="position-relative">
                                <hr class="border-secondary-subtle">
                                <span class="position-absolute top-50 start-50 translate-middle bg-white px-2 text-muted small fw-semibold">o continúa con</span>
                            </div>
                        </div>
                    </div>

                    <!-- Botón Google OAuth -->
                    <a href="<?= url('/auth/google') ?>" class="btn btn-outline-secondary btn-lg w-100 shadow-sm d-flex align-items-center justify-content-center gap-2">
                        <svg width="20" height="20" viewBox="0 0 24 24" class="me-1">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span class="fw-semibold">Continuar con Google</span>
                    </a>

                </div>
            </div>
        </div>
    </div>
</div>