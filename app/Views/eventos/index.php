<?php declare(strict_types=1); ?>

<!-- Hero Eventos -->
<section style="background: linear-gradient(100deg, rgba(5,15,10,0.88) 0%, rgba(5,15,10,0.72) 55%, rgba(0,20,10,0.55) 100%), url('https://images.unsplash.com/photo-1489944440615-453fc2b6a9a9?w=1600&q=80&fit=crop') center 40% / cover; padding:64px 0 72px; color:#fff;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="badge" style="background:var(--tg-gold); color:#0f172a; font-weight:800; letter-spacing:.5px; padding:6px 12px; border-radius:20px; font-size:.72rem;">EVENTOS DEPORTIVOS</span>
                <h1 style="font-size:3rem; font-weight:900; line-height:1.05; margin:12px 0 10px;">Tu evento deportivo<br><span style="color:var(--tg-green-bright); font-style:italic;">en TOP GOL</span></h1>
                <p style="color:rgba(255,255,255,.82); font-size:.95rem; line-height:1.65; max-width:520px;">Campeonatos, olimpiadas y encuentros para colegios, instituciones y grupos. Reserva varias canchas el mismo día en un solo evento.</p>
                <a href="<?= url('/evento') ?>" class="btn-search mt-2" style="display:inline-flex; font-size:.9rem; padding:14px 26px;">
                    <i class="bi bi-calendar-plus"></i> Reservar evento
                </a>
                <div style="display:flex; gap:18px; flex-wrap:wrap; margin-top:16px; font-size:.82rem; color:rgba(255,255,255,.85);">
                    <span><i class="bi bi-check-circle-fill" style="color:var(--tg-green-bright);"></i> Adelanto S/ 20 por cancha</span>
                    <span><i class="bi bi-check-circle-fill" style="color:var(--tg-green-bright);"></i> Mismo día, múltiples canchas</span>
                    <span><i class="bi bi-check-circle-fill" style="color:var(--tg-green-bright);"></i> Confirmación rápida</span>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div style="background:rgba(255,255,255,.08); backdrop-filter:blur(10px); border:1px solid rgba(255,255,255,.15); border-radius:20px; padding:22px; max-width:300px; margin-left:auto; text-align:center;">
                    <div style="font-size:1.15rem; font-weight:900; font-style:italic; line-height:1.2;">EVENTOS<br><span style="color:var(--tg-green-bright);">QUE UNEN</span></div>
                    <span class="hero-ball"><img src="assets/img/pelota.png" alt="Ball" class="img-fluid" width="115" height="115"></span>
                    <div style="font-size:.8rem; font-weight:700; letter-spacing:1px; opacity:.85;">TOP GOL · TACNA</div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container" style="margin-top:32px; margin-bottom:56px;">

    <div class="text-center mb-4">
        <span class="sec-label">SOLO DEPORTE</span>
        <h2 class="sec-title">Eventos que organizamos</h2>
        <p class="sec-sub" style="max-width:620px; margin:0 auto;">Dirigidos exclusivamente a la práctica deportiva. Instalaciones y servicios pensados para competir y compartir.</p>
    </div>

    <!-- Galería deportes -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div style="position:relative; height:180px; border-radius:14px; overflow:hidden; background:url('https://images.unsplash.com/photo-1524012435847-659cf8c3d158?w=600&q=80&fit=crop') center/cover;">
                <span style="position:absolute; inset:0; background:linear-gradient(to top, rgba(0,0,0,.65), transparent);"></span>
                <span style="position:absolute; left:14px; bottom:12px; color:#fff; font-weight:800; font-size:1.05rem; text-shadow:0 1px 4px rgba(0,0,0,.6);"><i class="bi bi-dribbble me-1"></i> Fútbol</span>
            </div>
        </div>
        <div class="col-md-4">
            <div style="position:relative; height:180px; border-radius:14px; overflow:hidden; background:url('https://images.unsplash.com/photo-1546519638-68e109498ffc?w=600&q=80&fit=crop') center/cover;">
                <span style="position:absolute; inset:0; background:linear-gradient(to top, rgba(0,0,0,.65), transparent);"></span>
                <span style="position:absolute; left:14px; bottom:12px; color:#fff; font-weight:800; font-size:1.05rem; text-shadow:0 1px 4px rgba(0,0,0,.6);"><i class="bi bi-basket me-1"></i> Básquet</span>
            </div>
        </div>
        <div class="col-md-4">
            <div style="position:relative; height:180px; border-radius:14px; overflow:hidden; background:url('https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?w=600&q=80&fit=crop') center/cover;">
                <span style="position:absolute; inset:0; background:linear-gradient(to top, rgba(0,0,0,.65), transparent);"></span>
                <span style="position:absolute; left:14px; bottom:12px; color:#fff; font-weight:800; font-size:1.05rem; text-shadow:0 1px 4px rgba(0,0,0,.6);"><i class="bi bi-volleyball me-1"></i> Vóley</span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="cancha-card-v2 h-100" style="padding:18px;">
                <div class="f-icon mb-3" style="width:52px; height:52px; font-size:1.4rem;"><i class="bi bi-mortarboard-fill"></i></div>
                <h5 class="fw-bold" style="font-size:1rem;">Colegios</h5>
                <p style="font-size:.82rem; color:var(--tg-gray); line-height:1.6;">Inter-escolares, olimpiadas, clausuras y campeonatos por categorías. Múltiples canchas en paralelo para jornadas completas.</p>
                <ul style="font-size:.78rem; color:#334155; padding-left:18px; margin:0; line-height:1.8;">
                    <li>Fútbol 5, 7 y 11</li>
                    <li>Hasta 22 jugadores por cancha</li>
                    <li>Graderías</li>
                </ul>
            </div>
        </div>
        <div class="col-md-4">
            <div class="cancha-card-v2 h-100" style="padding:18px;">
                <div class="f-icon mb-3" style="width:52px; height:52px; font-size:1.4rem;"><i class="bi bi-building"></i></div>
                <h5 class="fw-bold" style="font-size:1rem;">Instituciones</h5>
                <p style="font-size:.82rem; color:var(--tg-gray); line-height:1.6;">Empresas, universidades, municipios y fuerzas armadas. Torneos inter-áreas y actividades de integración.</p>
                <ul style="font-size:.78rem; color:#334155; padding-left:18px; margin:0; line-height:1.8;">
                    <li>Fechas exclusivas</li>
                    <li>Formatos por grupos y eliminatorias</li>
                    <li>Iluminación LED nocturna</li>
                </ul>
            </div>
        </div>
        <div class="col-md-4">
            <div class="cancha-card-v2 h-100" style="padding:18px;">
                <div class="f-icon mb-3" style="width:52px; height:52px; font-size:1.4rem;"><i class="bi bi-people-fill"></i></div>
                <h5 class="fw-bold" style="font-size:1rem;">Grupos</h5>
                <p style="font-size:.82rem; color:var(--tg-gray); line-height:1.6;">Academias, clubes barriales y grupos de amigos. Entrenamientos, amistosos y ligas internas.</p>
                <ul style="font-size:.78rem; color:#334155; padding-left:18px; margin:0; line-height:1.8;">
                    <li>Reserva por bloques horarios</li>
                    <li>Techada y al aire libre</li>
                    <li>Césped sintético de alta densidad</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="panel" style="background:#fff; border-radius:14px; padding:20px; box-shadow:0 1px 3px rgba(16,28,51,.07); height:100%;">
                <h5 class="fw-bold mb-3">¿Qué incluye tu evento?</h5>
                <div class="row g-3">
                    <div class="col-sm-6"><div class="f-item"><div class="f-icon"><i class="bi bi-grid-3x3-gap-fill"></i></div><div class="f-txt"><strong>Múltiples canchas el mismo día</strong><span>Reserva de 1 a 5 canchas en un solo trámite</span></div></div></div>
                    <div class="col-sm-6"><div class="f-item"><div class="f-icon"><i class="bi bi-clock-fill"></i></div><div class="f-txt"><strong>Horarios a tu medida</strong><span>Elige hora y duración por cada cancha</span></div></div></div>
                    <div class="col-sm-6"><div class="f-item"><div class="f-icon"><i class="bi bi-cash-stack"></i></div><div class="f-txt"><strong>Adelanto por cancha</strong><span>S/ 20 por cancha, saldo en sede</span></div></div></div>
                    <div class="col-sm-6"><div class="f-item"><div class="f-icon"><i class="bi bi-shield-check"></i></div><div class="f-txt"><strong>Confirmación ágil</strong><span>Con comprobante, verificación rápida</span></div></div></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel" style="background:#0b1e33; color:#fff; border-radius:14px; padding:20px; height:100%; display:flex; flex-direction:column; justify-content:center; text-align:center;">
                <h5 class="fw-bold" style="color:var(--tg-gold);">¿Listo para tu campeonato?</h5>
                <p style="font-size:.85rem; color:rgba(255,255,255,.75);">Reserva todas tus canchas en un solo evento y asegura tu fecha.</p>
                <a href="<?= url('/evento') ?>" class="btn-search" style="justify-content:center; width:100%; margin-top:8px;">
                    <i class="bi bi-calendar-plus"></i> Reservar evento
                </a>
                <small style="color:rgba(255,255,255,.55); font-size:.72rem; margin-top:8px; display:block;">Atención: solo eventos deportivos</small>
            </div>
        </div>
    </div>
</div>
