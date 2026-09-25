<?php declare(strict_types=1); ?>

<!-- Hero Contacto -->
<section style="background: linear-gradient(100deg, rgba(5,15,10,0.88) 0%, rgba(5,15,10,0.70) 55%, rgba(0,20,10,0.50) 100%), url('https://images.unsplash.com/photo-1522778119026-d647f0596c20?w=1600&q=80&fit=crop') center 35% / cover; padding:56px 0 64px; color:#fff;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="badge" style="background:var(--tg-gold); color:#0f172a; font-weight:800; letter-spacing:.5px; padding:6px 12px; border-radius:20px; font-size:.72rem;">CONTACTO</span>
                <h1 style="font-size:2.6rem; font-weight:900; line-height:1.05; margin:12px 0 10px;">Estamos para<br><span style="color:var(--tg-green-bright); font-style:italic;">ayudarte</span></h1>
                <p style="color:rgba(255,255,255,.82); font-size:.95rem; line-height:1.65; max-width:520px;">¿Dudas sobre reservas, eventos o cotizaciones? Escríbenos y te respondemos el mismo día por WhatsApp o correo.</p>
                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:16px;">
                    <a href="https://wa.me/51987654321?text=Hola%20Top%20Gol" target="_blank" class="btn-search" style="background:#25D366; font-size:.85rem; padding:12px 20px;"><i class="bi bi-whatsapp"></i> WhatsApp</a>
                    <a href="#formContacto" class="btn btn-outline-light" style="border-radius:10px; font-weight:700; padding:12px 20px; font-size:.85rem;"><i class="bi bi-envelope me-1"></i> Enviar mensaje</a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div style="background:rgba(255,255,255,.08); backdrop-filter:blur(10px); border:1px solid rgba(255,255,255,.15); border-radius:20px; padding:20px; max-width:320px; margin-left:auto;">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;"><span style="width:42px; height:42px; border-radius:10px; background:var(--tg-green); display:flex; align-items:center; justify-content:center; color:#fff;"><i class="bi bi-telephone-fill"></i></span><span><strong style="display:block; font-size:.9rem;">+51 987 654 321</strong><small style="color:rgba(255,255,255,.7); font-size:.7rem;">Lun - Dom 07:00 - 00:00</small></span></div>
                    <div style="display:flex; align-items:center; gap:12px;"><span style="width:42px; height:42px; border-radius:10px; background:var(--tg-gold); display:flex; align-items:center; justify-content:center; color:#0f172a;"><i class="bi bi-geo-alt-fill"></i></span><span><strong style="display:block; font-size:.85rem;">Av. Deportiva 1234</strong><small style="color:rgba(255,255,255,.7); font-size:.7rem;">Complejo TOP GOL, Tacna</small></span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container" style="margin-top:32px; margin-bottom:56px;">
    <div class="row g-4">
        <!-- Info -->
        <div class="col-lg-4">
            <div class="panel" style="background:#fff; border-radius:14px; padding:20px; box-shadow:0 1px 3px rgba(16,28,51,.07);">
                <h5 class="fw-bold mb-3">Información</h5>
                <div class="f-item mb-3"><div class="f-icon"><i class="bi bi-geo-alt-fill"></i></div><div class="f-txt"><strong>Dirección</strong><span>Av. Deportiva 1234, Complejo TOP GOL<br>Tacna, Perú</span></div></div>
                <div class="f-item mb-3"><div class="f-icon"><i class="bi bi-telephone-fill"></i></div><div class="f-txt"><strong>Teléfono / WhatsApp</strong><span>+51 987 654 321<br><a href="https://wa.me/51987654321" target="_blank" style="color:var(--tg-green); font-weight:700; font-size:.78rem;">Abrir WhatsApp →</a></span></div></div>
                <div class="f-item mb-3"><div class="f-icon"><i class="bi bi-envelope-fill"></i></div><div class="f-txt"><strong>Correo</strong><span>reservas@topgol.com<br>eventos@topgol.com</span></div></div>
                <div class="f-item"><div class="f-icon"><i class="bi bi-clock-fill"></i></div><div class="f-txt"><strong>Horario</strong><span>Lunes a Domingo<br>07:00 AM - 12:00 AM</span></div></div>
                <hr style="margin:16px 0; border-color:#eef2f7;">
                <div style="display:flex; gap:12px; font-size:1.2rem;">
                    <a href="#" style="color:#94a3b8;"><i class="bi bi-facebook"></i></a>
                    <a href="#" style="color:#94a3b8;"><i class="bi bi-instagram"></i></a>
                    <a href="https://wa.me/51987654321" style="color:#25D366;"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>
            <div class="panel mt-3" style="background:#0b1e33; color:#fff; border-radius:14px; padding:16px; text-align:center;">
                <div style="font-size:1.6rem;">⚽</div>
                <strong style="display:block; font-style:italic;">EL FÚTBOL NOS UNE</strong>
                <small style="color:rgba(255,255,255,.6);">Top Gol · Más que fútbol</small>
            </div>
        </div>

        <!-- Formulario -->
        <div class="col-lg-8" id="formContacto">
            <div class="panel" style="background:#fff; border-radius:14px; padding:20px; box-shadow:0 1px 3px rgba(16,28,51,.07);">
                <h5 class="fw-bold mb-1">Envíanos un mensaje</h5>
                <p class="text-muted" style="font-size:.82rem;">Respuesta en el día por el medio que prefieras.</p>
                <form action="<?= url('/contacto/enviar') ?>" method="POST" class="needs-validation mt-3" novalidate>
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.8rem;">Nombre y apellido <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" value="<?= htmlspecialchars($_SESSION['usuario_nombre'] ?? '') ?>" required minlength="3" placeholder="Ej: Juan Pérez">
                            <div class="invalid-feedback">Mínimo 3 caracteres.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.8rem;">Correo <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($_SESSION['usuario_email'] ?? '') ?>" required placeholder="tu@correo.com">
                            <div class="invalid-feedback">Correo no válido.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.8rem;">Celular / WhatsApp</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#f8fafc; font-weight:700; color:#334155; border-color:#e2e8f0;">+51</span>
                                <input type="tel" class="form-control" name="telefono" value="<?= htmlspecialchars(preg_replace('/^\+51\s*/', '', $_SESSION['usuario_telefono'] ?? '')) ?>" placeholder="987 654 321">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.8rem;">Asunto</label>
                            <select class="form-select" name="asunto">
                                <option>Consulta general</option>
                                <option>Reserva de cancha</option>
                                <option>Evento deportivo</option>
                                <option>Cotización</option>
                                <option>Reclamo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.8rem;">Mensaje <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="mensaje" rows="4" required placeholder="Cuéntanos en qué te ayudamos..."></textarea>
                            <div class="invalid-feedback">Escribe tu mensaje.</div>
                        </div>
                    </div>
                    <button type="submit" class="btn-search mt-3" style="width:100%; justify-content:center; padding:12px;">
                        <i class="bi bi-send"></i> Enviar mensaje
                    </button>
                    <small class="text-muted d-block text-center mt-2" style="font-size:.72rem;"><i class="bi bi-shield-lock me-1"></i> Tus datos están protegidos</small>
                </form>
            </div>

            <!-- Mapa -->
            <div class="panel mt-3" style="background:#fff; border-radius:14px; padding:12px; box-shadow:0 1px 3px rgba(16,28,51,.07);">
                <div style="border-radius:10px; overflow:hidden; height:260px; background:#e2e8f0;">
                    <iframe src="https://www.google.com/maps?q=Top+Gol+Tacna&z=16&output=embed" width="100%" height="100%" style="border:0;" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>
