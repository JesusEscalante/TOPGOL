<?php declare(strict_types=1);
$imgPorTipo = [
    'futbol_5'  => 'https://images.unsplash.com/photo-1553778263-73a83bab9b0c?w=640&h=380&fit=crop&q=80',
    'futbol_7'  => 'https://images.unsplash.com/photo-1543326727-cf6c39e8f84c?w=640&h=380&fit=crop&q=80',
    'futbol_11' => 'https://images.unsplash.com/photo-1487466365202-1afdb86c764e?w=640&h=380&fit=crop&q=80',
];

$filtros = $filtros ?? ['fecha' => date('Y-m-d'), 'horario' => '', 'tipo' => '', 'duracion' => ''];
$diaSemana = (int)date('N', strtotime($filtros['fecha']));
$horaApertura = ($diaSemana >= 6) ? 7 : 9;
$horaCierre = 23;
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

// Obtener filtros activos si existen
$filtros = $filtros ?? ['fecha' => date('Y-m-d'), 'horario' => '', 'tipo' => '', 'duracion' => ''];
$meses = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$fechaObj = new DateTime($filtros['fecha']);
$fechaFormateada = $fechaObj->format('d') . ' de ' . $meses[(int)$fechaObj->format('m')] . ' de ' . $fechaObj->format('Y');
$horarioFormateado = $filtros['horario'] ? date('g:i A', strtotime($filtros['horario'])) : '';
$tipoLabels = ['futbol_5' => 'Fútbol 5', 'futbol_7' => 'Fútbol 7', 'futbol_11' => 'Fútbol 11'];
$tipoFormateado = $tipoLabels[$filtros['tipo']] ?? '';
?>

<!-- Sub-header de pagina -->
<div style="background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:20px 0 0;">
    <div class="container">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-4">
            <div>
                <span class="sec-label">Instalaciones deportivas</span>
                <h1 class="sec-title mb-0">Canchas disponibles</h1>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <?php if (isAdmin()): ?>
                    <a href="<?= url('/cancha/crear') ?>" class="btn btn-sm fw-700" style="background:var(--tg-green);color:#fff;border-radius:8px;font-weight:600;padding:9px 18px;">
                        <i class="bi bi-plus-lg me-1"></i> Nueva Cancha
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filtros activos -->
        <?php if ($filtros['horario'] || $filtros['tipo']): ?>
        <div class="d-flex flex-wrap gap-2 pb-3" id="filtros-activos">
            <span class="text-muted small d-flex align-items-center" style="font-size:0.75rem;">
                <i class="bi bi-funnel me-1"></i> Filtrando por:
            </span>
            <?php if ($filtros['horario']): ?>
                <span class="badge text-bg-light border text-dark small px-3 py-2" style="font-size:0.7rem;">
                    <i class="bi bi-clock me-1"></i> <?= $horarioFormateado ?> <?= $filtros['duracion'] ? '(' . $filtros['duracion'] . ' min)' : '' ?>
                    <a href="<?= url('/canchas?fecha=' . urlencode($filtros['fecha']) . '&tipo=' . urlencode($filtros['tipo'])) ?>" class="text-decoration-none ms-1" style="font-size:0.7rem;">&times;</a>
                </span>
            <?php endif; ?>
            <?php if ($filtros['tipo']): ?>
                <span class="badge text-bg-light border text-dark small px-3 py-2" style="font-size:0.7rem;">
                    <i class="bi bi-people me-1"></i> <?= $tipoFormateado ?>
                    <a href="<?= url('/canchas?fecha=' . urlencode($filtros['fecha']) . '&horario=' . urlencode($filtros['horario']) . '&duracion=' . urlencode($filtros['duracion'])) ?>" class="text-decoration-none ms-1" style="font-size:0.7rem;">&times;</a>
                </span>
            <?php endif; ?>
            <a href="<?= url('/canchas') ?>" class="btn btn-sm btn-outline-secondary" style="font-size:0.7rem;padding:4px 10px;border-radius:20px;">Limpiar</a>
        </div>
        <?php endif; ?>

        <!-- Filtros rápidos por tipo eliminados a petición -->
    </div>
</div>

<!-- Grid de Canchas -->
<div class="container py-5">
    
    <?php if (empty($canchas)): ?>
        <div class="text-center py-5">
            <i class="bi bi-dribbble text-muted" style="font-size:3rem;"></i>
            <?php if ($filtros['horario'] || $filtros['tipo']): ?>
                <p class="text-muted mt-3 fw-500">No hay canchas disponibles para los filtros seleccionados.</p>
                <p class="text-muted small">Intenta cambiar la fecha, horario o tipo de cancha.</p>
                <a href="<?= url('/canchas') ?>" class="btn btn-sm btn-outline-secondary mt-2">
                    <i class="bi bi-x-circle me-1"></i> Limpiar filtros
                </a>
            <?php else: ?>
                <p class="text-muted mt-3 fw-500">No hay canchas registradas en el sistema.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row g-4" id="canchas-grid">
            <?php foreach ($canchas as $idx => $cancha): ?>
                <?php
                    $tipo = $cancha['tipo'];
                    $imgUrl = $imgPorTipo[$tipo] ?? $imgPorTipo['futbol_5'];
                    $disponible = $cancha['estado'] === 'disponible';
                    $numero = $idx + 1;
                ?>
                <div class="col-lg-4 col-md-6 cancha-item" data-tipo="<?= $tipo ?>">
                    <div class="cancha-card-v2">
                        <!-- Imagen -->
                        <div class="cancha-img-wrap">
                            <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($cancha['nombre']) ?>"
                                 onerror="this.style.display='none';this.parentNode.querySelector('.pitch-visual').style.display='flex'">
                            <div class="pitch-visual" style="position:absolute;top:0;left:0;right:0;bottom:0;display:none;">
                                <div class="pitch-night-lights"></div>
                                <div class="pitch-lines-v2"><div class="pitch-center-v2"></div></div>
                            </div>

                            <?php if ($disponible): ?>
                                <div class="badge-disp">Cancha habilitada</div>
                            <?php else: ?>
                                <div class="badge-ocup">En mantenimiento</div>
                            <?php endif; ?>

                            <!-- Precio badge -->
                            <div style="position:absolute;top:10px;right:10px;background:rgba(15,23,42,0.8);color:var(--tg-gold);font-weight:800;font-size:0.8rem;padding:3px 10px;border-radius:6px;backdrop-filter:blur(4px);">
                                <?= formatPrice($cancha['precio_hora']) ?>/h
                            </div>

                            <div class="cancha-img-overlay">
                                <span class="ci-name"><?= htmlspecialchars($cancha['nombre']) ?></span>
                                <span class="ci-type">Características por confirmar</span>
                            </div>
                        </div>

                        <!-- Cuerpo -->
                        <div class="cancha-card-body">
                            <div class="c-features">
                                <div class="c-feat"><i class="bi bi-info-circle-fill"></i> Detalles de la instalación por confirmar</div>
                            </div>

                            <div class="horarios-lbl">Disponibilidad
                                <?php if ($filtros['horario'] && $filtros['duracion']): ?>
                                    <span class="badge text-bg-success ms-2" style="font-size:0.65rem;">
                                        <i class="bi bi-check-circle me-1"></i> Libre <?= $horarioFormateado ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <?php if ($filtros['horario'] && $filtros['duracion']): ?>
                                    <span class="badge text-bg-light border text-dark w-100 py-2 d-flex align-items-center justify-content-center" style="font-size:0.75rem;font-weight:500;">
                                        Disponible para tu reserva de <?= $filtros['duracion'] ?> min.
                                    </span>
                                <?php else: ?>
                                    <span class="badge text-bg-light border text-dark w-100 py-2 d-flex align-items-center justify-content-center" style="font-size:0.75rem;font-weight:500;">
                                        <i class="bi bi-calendar-check me-2 text-success"></i>
                                        Consulta fecha, hora y duración exactas para validar.
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Acciones -->
                            <div class="d-grid gap-2">
                                <?php if ($disponible): ?>
                                    <?php $reservaBase = isAdmin() ? '/reserva/crear/' : '/reserva/formulario/'; ?>
                                    <a href="<?= url($reservaBase . $cancha['id']) . ($filtros['fecha'] ? '?fecha=' . urlencode($filtros['fecha']) . ($filtros['horario'] ? '&horario=' . urlencode($filtros['horario']) . '&duracion=' . urlencode($filtros['duracion']) : '') : '') ?>" class="btn-reservar-v2">
                                        Reservar <?= htmlspecialchars($cancha['nombre']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="btn-detalles-v2" style="opacity:0.6;cursor:default;">En mantenimiento</span>
                                <?php endif; ?>

                                <?php if (isAdmin()): ?>
                                    <div class="row g-2 mt-1">
                                        <div class="col-md-6 d-flex gap-2 mt-1">
                                            <a href="<?= url('/cancha/editar/' . $cancha['id']) ?>" class="btn btn-sm btn-outline-primary flex-grow-1" style="border-radius:7px;font-size:0.75rem;font-weight:600;">
                                                <i class="bi bi-pencil me-1"></i>Editar
                                            </a>
                                        </div>
                                        <div class="col-md-6 d-flex gap-2 mt-1">
                                            <a href="<?= url('/cancha/eliminar/' . $cancha['id']) ?>" class="btn btn-sm btn-outline-danger w-100 btn-confirmar-eliminar" style="border-radius:7px;font-size:0.75rem;font-weight:600;" data-confirm="Eliminar '<?= htmlspecialchars($cancha['nombre']) ?>'?">
                                                <i class="bi bi-trash me-1"></i>Eliminar
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function filtrar(tipo, btn) {
    document.querySelectorAll('#filtros-tipo button').forEach(b => {
        b.style.background = '#fff';
        b.style.color = '#0f172a';
        b.style.border = '1.5px solid #e2e8f0';
    });
    btn.style.background = 'var(--tg-green)';
    btn.style.color = '#fff';
    btn.style.border = 'none';

    document.querySelectorAll('.cancha-item').forEach(item => {
        if (tipo === 'todas' || item.getAttribute('data-tipo') === tipo) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>