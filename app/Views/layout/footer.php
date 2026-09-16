</main>

<!-- ===== FEATURES STRIP ===== -->
<div class="features-strip">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="f-item">
                    <div class="f-icon"><i class="bi bi-trophy-fill"></i></div>
                    <div class="f-txt">
                        <strong>Futbol todo el año</strong>
                        <span>Haz realidad tu pasion</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="f-item">
                    <div class="f-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="f-txt">
                        <strong>Reune a tu equipo</strong>
                        <span>Vive grandes momentos</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="f-item">
                    <div class="f-icon"><i class="bi bi-geo-alt-fill"></i></div>
                    <div class="f-txt">
                        <strong>Instalaciones de calidad</strong>
                        <span>Canchas siempre listas, Facil acceso y seguridad</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-12">
                <div class="f-cta-box">
                    <div class="f-cta-ico"><i class="bi bi-dribbble"></i></div>
                    <div>
                        <div class="f-cta-txt">El futbol nos une</div>
                        <div class="f-cta-sub">Top Gol</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== FOOTER ===== -->
<footer class="tg-footer">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="brand-logo-wrap" style="width:36px;height:36px;font-size:1.1rem;">
                        <i class="bi bi-dribbble"></i>
                    </div>
                    <span class="foot-brand-name">TOP <span>GOL</span></span>
                </div>
                <p style="font-size:0.82rem; color:rgba(255,255,255,0.5); line-height:1.7; max-width:280px;">
                    El mejor centro deportivo para el alquiler de canchas sinteticas de futbol. Cesped de primera, iluminacion LED y las mejores instalaciones.
                </p>
                <div class="d-flex gap-3 mt-3" style="font-size:1.2rem;">
                    <a href="#" style="color:rgba(255,255,255,0.4); transition:color 0.2s;" onmouseover="this.style.color='#22c55e'" onmouseout="this.style.color='rgba(255,255,255,0.4)'"><i class="bi bi-facebook"></i></a>
                    <a href="#" style="color:rgba(255,255,255,0.4); transition:color 0.2s;" onmouseover="this.style.color='#22c55e'" onmouseout="this.style.color='rgba(255,255,255,0.4)'"><i class="bi bi-instagram"></i></a>
                    <a href="#" style="color:rgba(255,255,255,0.4); transition:color 0.2s;" onmouseover="this.style.color='#22c55e'" onmouseout="this.style.color='rgba(255,255,255,0.4)'"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5>Navegacion</h5>
                <a href="<?= url('/') ?>"><i class="bi bi-chevron-right me-1" style="color:#22c55e;font-size:0.65rem;"></i>Inicio</a>
                <a href="<?= url('/canchas') ?>"><i class="bi bi-chevron-right me-1" style="color:#22c55e;font-size:0.65rem;"></i>Nuestras Canchas</a>
                <a href="<?= url('/reserva/crear') ?>"><i class="bi bi-chevron-right me-1" style="color:#22c55e;font-size:0.65rem;"></i>Reservar Cancha</a>
                <a href="<?= url('/login') ?>"><i class="bi bi-chevron-right me-1" style="color:#22c55e;font-size:0.65rem;"></i>Acceso Clientes</a>
            </div>
            <div class="col-lg-5 col-md-12">
                <h5>Horario y Contacto</h5>
                <div style="font-size:0.82rem; color:rgba(255,255,255,0.55); display:flex; flex-direction:column; gap:7px;">
                    <div><i class="bi bi-geo-alt-fill me-2" style="color:#22c55e;"></i>Av. Deportiva 1234, Complejo TOP GOL</div>
                    <div><i class="bi bi-clock-fill me-2" style="color:#22c55e;"></i>Lunes a Domingo: 07:00 AM - 11:00 PM</div>
                    <div><i class="bi bi-telephone-fill me-2" style="color:#22c55e;"></i>+51 987 654 321</div>
                    <div><i class="bi bi-envelope-fill me-2" style="color:#22c55e;"></i>reservas@topgol.com</div>
                </div>
            </div>
        </div>
        <div class="border-top pt-3 d-flex flex-wrap justify-content-between align-items-center" style="border-color:rgba(255,255,255,0.07) !important;">
            <small>&copy; <?= date('Y') ?> <strong style="color:#fff;">TOP GOL</strong>. Todos los derechos reservados.</small>
            <small>PHP 8+ MVC &mdash; Sistema de Reservas de Canchas</small>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>