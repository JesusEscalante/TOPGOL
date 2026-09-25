<?php declare(strict_types=1);
$hoy = date('Y-m-d');
$hoySemana = ['Domingo','Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'][date('w')];
$meses = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$fechaFormateada = $hoySemana . ', ' . date('d') . ' de ' . $meses[(int)date('m')] . ' de ' . date('Y');

// Imagenes de canchas por tipo
$imgPorTipo = [
    'futbol_5'  => 'https://images.unsplash.com/photo-1553778263-73a83bab9b0c?w=640&h=380&fit=crop&q=80',
    'futbol_7'  => 'https://images.unsplash.com/photo-1543326727-cf6c39e8f84c?w=640&h=380&fit=crop&q=80',
    'futbol_11' => 'https://images.unsplash.com/photo-1487466365202-1afdb86c764e?w=640&h=380&fit=crop&q=80',
];

// Horario de atencion: 7:00 - 00:00 (ultimo inicio 23:00)
$horaApertura = 7;
$horaCierre = 24;
$horaActual = (int)date('H');

// Generar 5 slots dinamicos: las 5 horas siguientes a la actual (ej: 13:25 -> 14:00-18:00)
$ultimoSlot = $horaCierre - 1;
$inicio = $horaActual + 1;
if ($inicio < $horaApertura) {
    $inicio = $horaApertura;
}
$fin = $inicio + 4;
if ($fin > $ultimoSlot) {
    $fin = $ultimoSlot;
    $inicio = max($horaApertura, $fin - 4);
}

$horariosSlots = [];
for ($h = $inicio; $h <= $fin; $h++) {
    $horariosSlots[] = sprintf('%02d:00', $h);
}

// Todos los horarios disponibles (para formulario de reserva)
$todosLosHorarios = [];
for ($h = $horaApertura; $h < $horaCierre; $h++) {
    $todosLosHorarios[] = sprintf('%02d:00', $h);
}
?>

<!-- ===== HERO SECTION ===== -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <!-- Texto Principal -->
            <div class="col-lg-7">
                <h1 class="hero-title-main mb-0">Vive el futbol</h1>
                <span class="hero-title-accent">en TOP GOL</span>
                <p class="hero-desc">
                    Reserva tu cancha de manera rapida, facil y segura.<br>
                    Disfruta con tus amigos, tu equipo, tu pasion.
                </p>
                <div class="hero-badges">
                    <div class="hero-badge"><i class="bi bi-calendar-check"></i> Reservas 24/7</div>
                    <div class="hero-badge"><i class="bi bi-shield-check"></i> Pago seguro</div>
                    <div class="hero-badge"><i class="bi bi-people"></i> Canchas de primer nivel</div>
                    <div class="hero-badge"><i class="bi bi-geo-alt"></i> Ubicacion privilegiada</div>
                </div>
            </div>
            <!-- Panel Decorativo Derecha -->
            <div class="col-lg-5 d-none d-lg-block">
                <div class="hero-right-panel ms-auto" style="max-width:260px;">
                    <div class="slogan-top">BUEN<br>FUTBOL</div>
                    <div class="slogan-bottom">MEJORES<br>HISTORIAS</div>
                    <span class="hero-ball"><img src="assets/img/pelota.png" alt="Ball" class="img-fluid" width="115" height="115"></span>
                    <div class="hero-brand-tag">TOP GOL</div>
                    <div class="hero-brand-sub">Mas que futbol</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== BARRA DE BUSQUEDA FLOTANTE ===== -->
<div class="search-bar-outer">
    <div class="container">
        <form action="<?= url('/canchas') ?>" method="GET" class="search-card">
            <!-- Campo Fecha -->
            <div class="s-field">
                <div class="s-field-ico"><i class="bi bi-calendar3"></i></div>
                <div class="s-field-txt">
                    <span class="s-field-lbl">Fecha</span>
                    <input type="date" name="fecha" value="<?= $hoy ?>" class="form-control border-0 p-0 shadow-none" style="font-size:0.875rem;font-weight:600;color:#0f172a;background:transparent;">
                </div>
            </div>
            <div class="s-divider"></div>
            <!-- Campo Horario -->
            <div class="s-field">
                <div class="s-field-ico"><i class="bi bi-clock"></i></div>
                <div class="s-field-txt">
                    <span class="s-field-lbl">Horario</span>
                    <select name="horario" class="s-field-val" style="border:none;background:transparent;font-size:0.875rem;font-weight:600;color:#0f172a;outline:none;width:100%;">
                        <option value="">Cualquier horario</option>
                        <?php for($h=7;$h<=22;$h++): ?>
                            <option value="<?= sprintf('%02d:00',$h) ?>"><?= date('g:i A', strtotime(sprintf('%02d:00:00',$h))) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="s-divider"></div>
            <!-- Campo Tipo/Personas -->
            <div class="s-field">
                <div class="s-field-ico"><i class="bi bi-people"></i></div>
                <div class="s-field-txt">
                    <span class="s-field-lbl">Tipo de cancha</span>
                    <select name="tipo" class="s-field-val" style="border:none;background:transparent;font-size:0.875rem;font-weight:600;color:#0f172a;outline:none;width:100%;">
                        <option value="">Cualquier formato</option>
                        <option value="futbol_5">Futbol 5</option>
                        <option value="futbol_7">Futbol 7</option>
                        <option value="futbol_11">Futbol 11</option>
                    </select>
                </div>
            </div>
            <!-- Boton -->
            <button type="submit" class="btn-search">
                <i class="bi bi-search"></i> Ver canchas disponibles
            </button>
        </form>
    </div>
</div>
<style>
/* Options minimalistas */
.search-card .s-field-val option {
    padding: 8px 12px;
    font-size: .8rem;
    font-weight: 400;
    color: #334155;
    background: #fff;
    border-bottom: 1px solid #f8fafc;
}
.search-card .s-field-val option:hover,
.search-card .s-field-val option:focus {
    background: #f8fafc;
    color: #0f172a;
}
.search-card .s-field-val option:checked {
    background: #f1f5f9;
    color: #0f172a;
    font-weight: 600;
}
.search-card .s-field-val option:first-child {
    color: #94a3b8;
    font-weight: 400;
    border-bottom: 1px solid #e2e8f0;
}
</style>

<!-- ===== CANCHAS DISPONIBLES ===== -->
<div class="container" style="margin-top: <?= (isAdmin() && isset($estadisticas)) ? '40px' : '48px' ?>; margin-bottom: 60px;">

    <!-- Encabezado de seccion -->
    <div class="d-flex align-items-start justify-content-between mb-4">
        <div>
            <span class="sec-label">NUESTRAS CANCHAS</span>
            <h2 class="sec-title">Canchas disponibles en TOP GOL</h2>
            <p class="sec-sub">Selecciona una cancha, elige tu horario y vive la experiencia Top Gol.</p>
        </div>
        <div class="d-none d-md-block pt-3">
            <a href="<?= url('/canchas') ?>" class="link-ver-todas">
                Ver todas las canchas <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- Cuadricula de Canchas -->
    <?php if (empty($canchas)): ?>
        <div class="text-center py-5">
            <i class="bi bi-dribbble text-muted" style="font-size:3rem;"></i>
            <p class="text-muted mt-3">No hay canchas disponibles en este momento.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach (array_slice($canchas, 0, 3) as $idx => $cancha): ?>
                <?php
                    $tipo = $cancha['tipo'];
                    $imgUrl = $imgPorTipo[$tipo] ?? $imgPorTipo['futbol_5'];
                    $tipoLabel = strtoupper(str_replace(['_','futbol '], [' ','Futbol '], $tipo));
                    $disponible = $cancha['estado'] === 'disponible';
                    $numero = $idx + 1;
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="cancha-card-v2">
                        <!-- Imagen con Overlay -->
                        <div class="cancha-img-wrap">
                            <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($cancha['nombre']) ?>"
                                 onerror="this.style.display='none';this.parentNode.querySelector('.pitch-visual').style.display='flex'">
                            <div class="pitch-visual" style="position:absolute;top:0;left:0;display:none;">
                                <div class="pitch-night-lights"></div>
                                <div class="pitch-lines-v2">
                                    <div class="pitch-center-v2"></div>
                                    <div class="pitch-center-spot"></div>
                                </div>
                            </div>

                            <!-- Badge de estado -->
                            <?php if ($disponible): ?>
                                <div class="badge-disp">Disponible ahora</div>
                            <?php else: ?>
                                <div class="badge-ocup">Ocupada ahora</div>
                            <?php endif; ?>

                            <!-- Info sobre la imagen -->
                            <div class="cancha-img-overlay">
                                <span class="ci-name"><?= $cancha['nombre'] ?></span>
                                <span class="ci-type"><?= $tipoLabel ?> &bull; Cesped sintetico</span>
                            </div>
                        </div>

                        <!-- Cuerpo de la tarjeta -->
                        <div class="cancha-card-body">
                            <!-- Caracteristicas -->
                            <div class="c-features">
                                <div class="c-feat"><i class="bi bi-people-fill"></i> Hasta <?= $cancha['capacidad'] ?> personas</div>
                                <?php if (!empty($cancha['iluminacion'])): ?>
                                    <div class="c-feat"><i class="bi bi-lightbulb-fill"></i> Iluminacion LED</div>
                                <?php endif; ?>
                                <?php if (!empty($cancha['techada'])): ?>
                                    <div class="c-feat"><i class="bi bi-shield-shaded"></i> Techada</div>
                                <?php endif; ?>
                            </div>

                            <!-- Horarios disponibles -->
                            <div class="horarios-lbl">Horarios disponibles</div>
                            <div class="horarios-row">
                                <?php $ocupados = $slotsOcupados[(int)$cancha['id']] ?? []; ?>
                                <?php foreach ($horariosSlots as $slot): ?>
                                    <?php
                                        $horaInt = (int)explode(':', $slot)[0];
                                        $horaActual = (int)date('H');
                                        if (in_array($slot, $ocupados, true)) {
                                            $cls = 'ocupada';
                                        } elseif ($horaInt < $horaActual) {
                                            $cls = 'pasada';
                                        } else {
                                            $cls = 'libre';
                                        }
                                    ?>
                                    <span class="hora-slot <?= $cls ?>"><?= $slot ?></span>
                                <?php endforeach; ?>
                            </div>

                            <!-- Precio -->
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span style="font-size:0.72rem;color:#94a3b8;">Precio por hora</span>
                                <span style="font-size:1rem;font-weight:800;color:#1a7a3a;"><?= formatPrice($cancha['precio_hora']) ?></span>
                            </div>

                            <!-- Boton CTA -->
                            <?php if ($disponible): ?>
                                <?php $reservaBaseHome = isAdmin() ? '/reserva/crear/' : '/reserva/formulario/'; ?>
                                <a href="<?= url($reservaBaseHome . $cancha['id']) ?>" class="btn-reservar-v2">
                                    Reservar cancha <?= $numero ?>
                                </a>
                            <?php else: ?>
                                <a href="<?= url('/cancha/ver/' . $cancha['id']) ?>" class="btn-detalles-v2">
                                    Ver detalles
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($canchas) > 3): ?>
            <div class="text-center mt-4 d-md-none">
                <a href="<?= url('/canchas') ?>" class="link-ver-todas">
                    Ver todas las canchas <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
        <div class="mt-4 pt-2 border-top d-flex justify-content-end">
            <a href="<?= url('/cancha/crear') ?>" class="btn btn-sm" style="background:var(--tg-green);color:#fff;border-radius:8px;font-weight:600;">
                <i class="bi bi-plus-lg me-1"></i> Registrar nueva cancha
            </a>
        </div>
    <?php endif; ?>
</div>