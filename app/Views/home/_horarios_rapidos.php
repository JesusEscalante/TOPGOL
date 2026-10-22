<?php
declare(strict_types=1);

/**
 * Horarios libres de hoy (o mañana) de una cancha, como botones pequeños. Solo lectura.
 * Variables: $cancha (fila), $horariosLibres (de Reserva::proximosHorariosLibres), $reservaBaseHorarios (ruta base).
 */
$hl = $horariosLibres[(int)$cancha['id']] ?? ['hoy' => [], 'manana' => [], 'fecha_hoy' => date('Y-m-d'), 'fecha_manana' => date('Y-m-d', strtotime('+1 day'))];
$hayHoy = !empty($hl['hoy']);
$lista = $hayHoy ? $hl['hoy'] : $hl['manana'];
$fechaLista = $hayHoy ? $hl['fecha_hoy'] : $hl['fecha_manana'];
$urlBaseH = url($reservaBaseHorarios . $cancha['id']);
?>
<div class="horarios-rapidos">
    <?php if ($hayHoy): ?>
        <div class="hr-titulo">Hoy</div>
    <?php else: ?>
        <div class="hr-titulo"><span class="hr-sin">Sin horarios hoy</span><?= $lista ? ' · Mañana' : '' ?></div>
    <?php endif; ?>
    <?php if ($lista): ?>
        <div class="hr-lista">
            <?php foreach ($lista as $hhmm): ?>
                <a class="hr-btn" href="<?= $urlBaseH . '?fecha=' . urlencode($fechaLista) . '&horario=' . urlencode($hhmm) ?>"><?= htmlspecialchars($hhmm) ?></a>
            <?php endforeach; ?>
        </div>
    <?php elseif (!$hayHoy): ?>
        <div class="hr-vacio">Sin horarios libres mañana tampoco.</div>
    <?php endif; ?>
    <a class="hr-otros" href="<?= $urlBaseH ?>">Ver otros días <i class="bi bi-arrow-right"></i></a>
</div>
<style>
    .horarios-rapidos { margin-bottom: 14px; }
    .horarios-rapidos .hr-titulo { font-size: .78rem; font-weight: 800; color: #334155; margin-bottom: 6px; }
    .horarios-rapidos .hr-sin { color: #b45309; }
    .horarios-rapidos .hr-lista { display: flex; flex-wrap: wrap; gap: 6px; }
    .horarios-rapidos .hr-btn { display: inline-block; min-width: 56px; text-align: center; padding: 7px 10px; border-radius: 8px; border: 1px solid #1a7a3a; color: #1a7a3a; background: #f0fdf4; font-size: .82rem; font-weight: 700; text-decoration: none; }
    .horarios-rapidos .hr-btn:hover { background: #1a7a3a; color: #fff; }
    .horarios-rapidos .hr-vacio { font-size: .78rem; color: #64748b; }
    .horarios-rapidos .hr-otros { display: inline-block; margin-top: 8px; font-size: .78rem; font-weight: 700; color: #1a7a3a; text-decoration: none; }
    .horarios-rapidos .hr-otros:hover { text-decoration: underline; }
</style>
