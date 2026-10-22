<?php
declare(strict_types=1);

/**
 * Calendario del administrador (día y semana).
 * Cada reserva es un bloque en su hora exacta. Colores (iguales en la leyenda, el calendario y la lista):
 *   amarillo = por verificar · azul = confirmada, falta pagar · verde = pagada · gris = finalizada · rojo suave = cancelada / no asistió
 *
 * @var string $vista
 * @var string $hoy
 * @var string $horaActual
 * @var array<int, array<string, mixed>> $canchas
 */

$diasN = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
$mesesC = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$mesesL = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$tipoLabels = ['futbol_5' => 'Fútbol 5', 'futbol_7' => 'Fútbol 7', 'futbol_11' => 'Fútbol 11'];

if (!defined('CAL_PX_MIN')) { define('CAL_PX_MIN', 1.0); } // píxeles por minuto (60 px por hora)
$aMin = static fn(string $hms): int => ((int)substr($hms, 0, 2)) * 60 + (int)substr($hms, 3, 2);
$finMin = static function (array $r) use ($aMin): int {
    $f = $aMin((string)$r['hora_fin']);
    return $f === 0 ? 1440 : $f; // 00:00 = medianoche
};
$hhmm = static fn(int $m): string => sprintf('%02d:%02d', intdiv($m, 60), $m % 60);

/** Reservas únicas (por id) de una grilla de celdas de 30 min. */
$unicas = static function (array $celdas): array {
    $out = [];
    foreach ($celdas as $lista) {
        foreach ($lista as $r) { $out[(int)$r['id']] = $r; }
    }
    return array_values($out);
};
/** Reparte en carriles las reservas que se cruzan en el tiempo (para que no se tapen). */
$carriles = static function (array $lista) use ($aMin, $finMin): array {
    usort($lista, static fn($a, $b) => $aMin((string)$a['hora_inicio']) <=> $aMin((string)$b['hora_inicio']));
    $res = []; $grupo = []; $finGrupo = -1; $finCarril = [];
    $cerrar = static function () use (&$res, &$grupo, &$finCarril) {
        $n = max(1, count($finCarril));
        foreach ($grupo as $g) { $g['lanes'] = $n; $res[] = $g; }
        $grupo = []; $finCarril = [];
    };
    foreach ($lista as $r) {
        $ini = $aMin((string)$r['hora_inicio']);
        $fin = $finMin($r);
        if (!empty($grupo) && $ini >= $finGrupo) { $cerrar(); $finGrupo = -1; }
        $lane = 0;
        while (isset($finCarril[$lane]) && $finCarril[$lane] > $ini) { $lane++; }
        $finCarril[$lane] = $fin;
        $finGrupo = max($finGrupo, $fin);
        $grupo[] = ['r' => $r, 'lane' => $lane, 'ini' => $ini, 'fin' => $fin];
    }
    if (!empty($grupo)) { $cerrar(); }
    return $res;
};
/** Rango visible (minutos, alineado a horas): horario de atención + reservas fuera de horario. */
$rangoVisible = static function (array $fechas, array $reservas) use ($aMin, $finMin): array {
    $ini = 1440; $fin = 0;
    foreach ($fechas as $f) {
        $h = getHorarioProvisionalNegocio($f);
        $ini = min($ini, $aMin($h['apertura']));
        $fin = max($fin, $aMin($h['cierre']));
    }
    foreach ($reservas as $r) {
        $ini = min($ini, $aMin((string)$r['hora_inicio']));
        $fin = max($fin, $finMin($r));
    }
    return [intdiv($ini, 60) * 60, (int)(ceil($fin / 60) * 60)];
};

/** Leyenda corta de colores (la misma de la lista de reservas). */
$leyenda = static function (): void { ?>
    <div class="cal-leyenda" aria-label="Colores de las reservas">
        <span class="lg verificar"><i></i> Por verificar</span>
        <span class="lg falta"><i></i> Confirmada · falta pagar</span>
        <span class="lg pagada"><i></i> Pagada</span>
        <span class="lg fin"><i></i> Finalizada</span>
        <span class="lg neg"><i></i> Cancelada / No asistió</span>
    </div>
<?php };

/** Dibuja un bloque de reserva: solo hora exacta, cliente y etiqueta corta. El detalle sale al tocarlo. */
$dibujarBloque = static function (array $b, int $rangoIni, string $fechaR, string $canchaNombre) use ($aMin): void {
    $r = $b['r'];
    $total = (float)($r['total_pago'] ?? 0);
    $pagado = (float)($r['total_pagado'] ?? 0);
    $cat = categoriaVisualReserva($r, $pagado);
    $saldo = max(0.0, round($total - $pagado, 2));
    $top = round(($b['ini'] - $rangoIni) * CAL_PX_MIN, 1);
    $alto = max(20.0, round(($b['fin'] - $b['ini']) * CAL_PX_MIN, 1) - 1);
    $ancho = 100 / $b['lanes'];
    $izq = $ancho * $b['lane'];
    $hIni = substr((string)$r['hora_inicio'], 0, 5);
    $hFin = substr((string)$r['hora_fin'], 0, 5);
    $cliente = (string)($r['cliente_nombre'] ?? $r['usuario_nombre'] ?? 'Cliente');
    $wa = generarEnlaceWhatsApp($r, 'consulta');
    $det = [
        'id' => (int)$r['id'], 'cliente' => $cliente,
        'telefono' => (string)(!empty($r['contacto_telefono']) ? $r['contacto_telefono'] : ($r['usuario_telefono'] ?? '')),
        'cancha' => $canchaNombre, 'fecha' => $fechaR, 'hora' => "{$hIni} – {$hFin}",
        'etiqueta' => $cat['texto'], 'clave' => $cat['clave'],
        'saldo' => $saldo > 0 ? formatPrice($saldo) : 'Sin saldo',
        'wa' => $wa !== '#' ? $wa : '', 'url_ver' => url('/mis-reservas/' . $r['id']),
    ];
    ?>
    <button type="button" class="cal-blk c-<?= $cat['clave'] ?><?= $alto >= 46 ? ' dos' : '' ?>" style="top:<?= $top ?>px; height:<?= $alto ?>px; left:calc(<?= round($izq, 2) ?>% + 2px); width:calc(<?= round($ancho, 2) ?>% - 4px);"
            data-det="<?= htmlspecialchars(json_encode($det, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>"
            title="<?= htmlspecialchars("{$hIni}–{$hFin} · {$cliente} · {$cat['texto']}", ENT_QUOTES, 'UTF-8') ?>">
        <span class="cb-main"><?= htmlspecialchars("{$hIni}–{$hFin} · {$cliente}") ?></span>
        <span class="cb-sub"><?= htmlspecialchars($cat['texto']) ?></span>
    </button>
    <?php
};

/** Estado de una cancha ahora mismo (solo para hoy): Libre ahora / Ocupada hasta HH:MM / Próx: HH:MM. */
$estadoCancha = static function (array $reservas, bool $esHoy, int $ahoraMin) use ($aMin, $finMin, $hhmm): array {
    if (!$esHoy) {
        $n = count($reservas);
        return ['cls' => 'libre', 'txt' => $n > 0 ? $n . ($n === 1 ? ' reserva' : ' reservas') : 'Libre todo el día'];
    }
    $prox = null;
    foreach ($reservas as $r) {
        $i = $aMin((string)$r['hora_inicio']); $f = $finMin($r);
        if ($i <= $ahoraMin && $ahoraMin < $f) {
            return ['cls' => 'ocupada', 'txt' => 'Ocupada hasta ' . $hhmm($f)];
        }
        if ($i > $ahoraMin && ($prox === null || $i < $prox)) { $prox = $i; }
    }
    return $prox !== null ? ['cls' => 'libre', 'txt' => 'Libre ahora', 'sub' => 'Próx: ' . $hhmm($prox)] : ['cls' => 'libre', 'txt' => 'Libre ahora'];
};
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<style>
    .cal-header-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
    .cal-header-bar > div:first-child { flex: 1 1 320px; min-width: 0; }
    .cal-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .cal-actions .cal-seg { display: flex; flex-wrap: wrap; gap: 6px; }
    .cal-btn { display: inline-flex; align-items: center; gap: 6px; background: #fff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 7px 13px; font-size: .84rem; font-weight: 700; color: #1e293b; text-decoration: none; white-space: nowrap; }
    .cal-actions .btn { white-space: nowrap; }
    .cal-btn:hover { border-color: #1a7a3a; color: #1a7a3a; background: #f8fafc; }
    .cal-btn.active { background: #1a7a3a; color: #fff; border-color: #1a7a3a; }
    .cal-nav { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }

    /* Paleta de estados (la misma de las pastillas de la lista de reservas) */
    :root { --c-verificar-bg: #fbeecb; --c-verificar-bd: #d97706; --c-verificar-tx: #78350f;
            --c-falta-bg: #e0f2fe; --c-falta-bd: #0284c7; --c-falta-tx: #075985;
            --c-pagada-bg: #d9efe0; --c-pagada-bd: #16a34a; --c-pagada-tx: #14532d;
            --c-fin-bg: #e2e8f0; --c-fin-bd: #64748b; --c-fin-tx: #334155;
            --c-neg-bg: #fbdcdc; --c-neg-bd: #f87171; --c-neg-tx: #991b1b; }
    .cal-leyenda { display: flex; flex-wrap: wrap; gap: 6px 14px; margin: 0 0 10px; font-size: .8rem; font-weight: 600; color: #475569; }
    .cal-leyenda .lg { display: inline-flex; align-items: center; gap: 6px; }
    .cal-leyenda .lg i { width: 14px; height: 14px; border-radius: 4px; border-left: 4px solid; display: inline-block; }
    .lg.verificar i { background: var(--c-verificar-bg); border-color: var(--c-verificar-bd); }
    .lg.falta i { background: var(--c-falta-bg); border-color: var(--c-falta-bd); }
    .lg.pagada i { background: var(--c-pagada-bg); border-color: var(--c-pagada-bd); }
    .lg.fin i { background: var(--c-fin-bg); border-color: var(--c-fin-bd); }
    .lg.neg i { background: var(--c-neg-bg); border-color: var(--c-neg-bd); }

    /* Tarjetas de cancha */
    .cancha-card { background: #fff; border-radius: 14px; padding: 10px 12px; box-shadow: 0 2px 8px rgba(16,28,51,.07); border-left: 5px solid #22c55e; height: 100%; }
    .cancha-card.ocupada { border-left-color: #0284c7; }
    .cancha-card.mant { border-left-color: #94a3b8; background: #f8fafc; }
    .cancha-card .c-title { font-weight: 800; font-size: .95rem; color: #0f172a; line-height: 1.15; }
    .cancha-card .c-tipo { font-size: .72rem; color: #64748b; }
    .cancha-card .c-status { font-size: .82rem; font-weight: 700; margin-top: 4px; display: flex; align-items: center; gap: 6px; color: #166534; }
    .cancha-card .c-sub { font-size: .76rem; font-weight: 600; color: #64748b; margin: 1px 0 0 14px; }
    .cancha-card.ocupada .c-status { color: #075985; }
    .cancha-card.mant .c-status { color: #64748b; }
    .cancha-card .c-status i { width: 8px; height: 8px; border-radius: 50%; background: currentColor; display: inline-block; flex-shrink: 0; }

    /* Rejilla */
    .cal-wrap { position: relative; overflow: auto; max-height: calc(100vh - 250px); min-height: 420px; border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; box-shadow: 0 2px 10px rgba(16,28,51,.07); }
    .cal-grid { --hh: calc(60px * var(--ppm, 1)); display: grid; grid-template-columns: 52px repeat(var(--cols), minmax(var(--colmin, 128px), 1fr)); min-width: calc(52px + var(--cols) * var(--colmin, 128px)); }
    .cal-corner, .cal-colhead { position: sticky; top: 0; z-index: 30; background: #f1f5f9; border-bottom: 1px solid #dbe3ee; border-right: 1px solid #e2e8f0; padding: 7px 6px; text-align: center; }
    .cal-corner { left: 0; z-index: 40; font-size: .7rem; font-weight: 700; color: #64748b; display: flex; align-items: center; justify-content: center; }
    .cal-colhead .t1 { font-weight: 800; font-size: .88rem; color: #0f172a; line-height: 1.15; }
    .cal-colhead .t2 { font-size: .7rem; color: #64748b; line-height: 1.2; }
    .cal-hours { position: sticky; left: 0; z-index: 25; background: #f8fafc; border-right: 1px solid #e2e8f0; }
    .cal-hours span.h { position: absolute; left: 0; right: 0; text-align: center; font-size: .72rem; font-weight: 700; color: #64748b; transform: translateY(-50%); }
    .cal-hours span.h.med { font-weight: 500; color: #a3aec0; font-size: .64rem; }
    .cal-col { position: relative; border-right: 1px solid #eef2f7;
        background-image: linear-gradient(to bottom, #dbe3ee 1px, transparent 1px), linear-gradient(to bottom, #f1f5f9 1px, transparent 1px);
        background-size: 100% var(--hh), 100% calc(var(--hh) / 2); }
    .cal-col.mant { background-color: #f1f5f9; background-image: repeating-linear-gradient(45deg, #f1f5f9, #f1f5f9 10px, #e8edf3 10px, #e8edf3 20px); }
    .cal-col.mant .mant-lbl { position: sticky; top: 56px; text-align: center; color: #64748b; font-size: .8rem; font-weight: 700; padding: 8px 4px; }
    .cal-closed { position: absolute; left: 0; right: 0; background: repeating-linear-gradient(135deg, #f8fafc, #f8fafc 6px, #eef2f7 6px, #eef2f7 12px); pointer-events: none; }
    .cal-now { position: absolute; left: 0; right: 0; height: 0; border-top: 1px solid rgba(239,68,68,.85); z-index: 12; pointer-events: none; }
    .cal-now-lbl { position: absolute; left: 2px; right: 2px; z-index: 13; transform: translateY(-50%); background: #ef4444; color: #fff; font-size: .62rem; font-weight: 800; text-align: center; border-radius: 6px; padding: 1px 0; line-height: 1.25; pointer-events: none; }
    .cal-add { position: absolute; left: 3px; right: 3px; display: flex; align-items: center; justify-content: center; border: 1px dashed transparent; border-radius: 8px; font-size: .74rem; font-weight: 700; color: transparent; text-decoration: none; z-index: 2; }
    .cal-add:hover { color: #1a7a3a; border-color: #86efac; background: #eef7f0; }

    /* Bloques */
    .cal-blk { position: absolute; z-index: 5; margin: 0; padding: 3px 7px; overflow: hidden; text-align: left; cursor: pointer; border: 0; border-left: 4px solid; border-radius: 9px; line-height: 1.2; font-family: inherit; box-shadow: 0 1px 3px rgba(16,28,51,.12); }
    .cal-blk .cb-main { display: block; font-size: .78rem; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cal-blk .cb-sub { display: none; font-size: .7rem; font-weight: 600; opacity: .85; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cal-blk.dos .cb-sub { display: block; }
    .cal-blk:hover { z-index: 8; box-shadow: 0 5px 14px rgba(16,28,51,.22); }
    .cal-blk:focus-visible { outline: 3px solid #1a7a3a; outline-offset: 1px; }
    .c-verificar { background: var(--c-verificar-bg); border-color: var(--c-verificar-bd); color: var(--c-verificar-tx); }
    .c-falta { background: var(--c-falta-bg); border-color: var(--c-falta-bd); color: var(--c-falta-tx); }
    .c-pagada { background: var(--c-pagada-bg); border-color: var(--c-pagada-bd); color: var(--c-pagada-tx); }
    .c-fin { background: var(--c-fin-bg); border-color: var(--c-fin-bd); color: var(--c-fin-tx); }
    .c-neg { background: var(--c-neg-bg); border-color: var(--c-neg-bd); color: var(--c-neg-tx); }

    /* Detalle al tocar */
    .cal-det-row { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid #eef2f7; font-size: .95rem; }
    .cal-det-row span:first-child { color: #64748b; }
    .cal-det-row strong { text-align: right; }
    .cal-det-tag { display: inline-block; font-size: .78rem; font-weight: 800; padding: 3px 10px; border-radius: 8px; border-left: 4px solid; }

    .cal-hint { display: none; font-size: .8rem; color: #475569; background: #f1f5f9; border-radius: 10px; padding: 6px 10px; margin-bottom: 8px; }
    @media (hover: none) { .cal-add { color: #94a3b8; border-color: #e2e8f0; } }
    @media (max-width: 767px) {
        .cal-hint { display: block; }
        .cal-wrap { max-height: calc(100vh - 190px); }
        .cal-blk .cb-main { font-size: .82rem; }
        .cal-btn { padding: 10px 14px; font-size: .9rem; }
        .col-6:has(> .cancha-card) { width: 33.3333%; }
        .cancha-card { padding: 7px 8px; }
        .cancha-card .c-title { font-size: .82rem; }
        .cancha-card .c-tipo { display: none; }
        .cancha-card .c-status { font-size: .7rem; align-items: flex-start; }
        .cancha-card .c-status i { margin-top: 4px; }
        .cal-leyenda { font-size: .78rem; }
    }
    @media (max-width: 575px) {
        .cal-actions { width: 100%; }
        .cal-actions .cal-seg { width: 100%; }
        .cal-actions .cal-seg .cal-btn, .cal-actions > .btn { flex: 1 1 auto; justify-content: center; text-align: center; }
    }
</style>

<div class="cal-header-bar">
    <div>
        <h1 class="adm-h1">Calendario Administrativo</h1>
        <p class="adm-sub">Ocupación, disponibilidad y programación de las canchas.</p>
    </div>
    <div class="cal-actions">
        <!-- Selector de vista Día / Semana -->
        <div class="cal-seg" role="group">
            <a href="<?= url('/admin/calendario?vista=dia&fecha=' . ($vista === 'dia' ? $fechaDia : date('Y-m-d'))) ?>" class="cal-btn <?= $vista === 'dia' ? 'active' : '' ?>">
                <i class="bi bi-grid-3x3-gap-fill"></i> Las 6 Canchas (Día)
            </a>
            <a href="<?= url('/admin/calendario?vista=semana&semana=' . ($vista === 'dia' ? $fechaDia : $semanaRef)) ?>" class="cal-btn <?= $vista === 'semana' ? 'active' : '' ?>">
                <i class="bi bi-calendar-week"></i> Vista Semanal
            </a>
        </div>

        <a href="<?= url('/reserva/crear') ?>" class="btn btn-sm fw-bold" style="background:#1a7a3a;color:#fff;border-radius:10px;padding:8px 16px;">
            <i class="bi bi-plus-lg me-1"></i> Nueva Reserva Manual
        </a>
    </div>
</div>

<?php
/** @var array<int, array<string, mixed>> $solicitudesEspacio */
$solicitudesEspacio = $solicitudesEspacio ?? [];
$canchasPorSolicitud = $canchasPorSolicitud ?? [];
$renderSolicitudesEspacio = static function (array $lista, array $canchasMap, bool $conFecha): void {
    if (empty($lista)) { return; }
    ?>
    <div class="panel-2 mb-3" style="border-left:4px solid #8b5cf6;">
        <div class="fw-bold mb-2"><i class="bi bi-balloon-heart me-1" style="color:#8b5cf6;"></i> Cumpleaños y reuniones (espacio del medio)
            <a href="<?= url('/admin/espacios') ?>" class="small ms-2">Ver solicitudes</a>
        </div>
        <?php foreach ($lista as $sol): ?>
            <?php
                $ini = substr((string)$sol['hora_inicio'], 0, 5);
                $fin = !empty($sol['hora_fin']) ? substr((string)$sol['hora_fin'], 0, 5) : '';
                $nombresC = array_column($canchasMap[(int)$sol['id']] ?? [], 'nombre');
                $badge = $sol['estado'] === 'confirmado' ? 'bg-success' : 'bg-warning text-dark';
            ?>
            <div class="d-flex flex-wrap align-items-center gap-2 py-1 small border-top">
                <span class="badge <?= $badge ?>"><?= ucfirst((string)$sol['estado']) ?></span>
                <strong><?= $conFecha ? date('d/m', strtotime((string)$sol['fecha'])) . ' · ' : '' ?><?= $ini ?><?= $fin !== '' ? ' - ' . $fin : '' ?></strong>
                <span><?= htmlspecialchars((string)$sol['contacto_nombre']) ?> · <?= htmlspecialchars((string)$sol['contacto_telefono']) ?></span>
                <?php if ($nombresC): ?><span class="text-muted">+ <?= htmlspecialchars(implode(', ', $nombresC)) ?></span><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
};
?>
<?php if ($vista === 'dia'): ?>
    <?php
        $tsDia = strtotime($fechaDia);
        $diaN = $diasN[(date('N', $tsDia) - 1)];
        $fechaTxt = $diaN . ', ' . date('d', $tsDia) . ' de ' . $mesesL[(int)date('n', $tsDia)] . ' ' . date('Y', $tsDia);
        $diaAnterior = date('Y-m-d', strtotime($fechaDia . ' -1 day'));
        $diaSiguiente = date('Y-m-d', strtotime($fechaDia . ' +1 day'));

        $esHoy = ($fechaDia === $hoy);
        $ahoraMin = $aMin(substr($horaActual, 0, 5));
        $todasDia = [];
        $porCancha = [];
        foreach ($canchas as $c) {
            $cid = (int)$c['id'];
            $porCancha[$cid] = $unicas($grillaDia[$cid] ?? []);
            $todasDia = array_merge($todasDia, $porCancha[$cid]);
        }
        [$rIni, $rFin] = $rangoVisible([$fechaDia], $todasDia);
        $altoGrid = (int)round(($rFin - $rIni) * CAL_PX_MIN);
        $negocioDia = getHorarioProvisionalNegocio($fechaDia);
        $abreMin = $aMin($negocioDia['apertura']);
        $cierraMin = $aMin($negocioDia['cierre']);
        $scrollA = $esHoy ? max(0, (int)round(($ahoraMin - 60 - $rIni) * CAL_PX_MIN)) : 0;
        $lineaAhora = $esHoy && $ahoraMin >= $rIni && $ahoraMin <= $rFin;
    ?>

    <!-- Navegación diaria -->
    <div class="panel-2 mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
            <div class="cal-nav">
                <a href="<?= url('/admin/calendario?vista=dia&fecha=' . $diaAnterior) ?>" class="cal-btn" title="Día anterior"><i class="bi bi-chevron-left"></i></a>
                <a href="<?= url('/admin/calendario?vista=dia&fecha=' . $hoy) ?>" class="cal-btn <?= $esHoy ? 'active' : '' ?>">Hoy</a>
                <a href="<?= url('/admin/calendario?vista=dia&fecha=' . $diaSiguiente) ?>" class="cal-btn" title="Día siguiente"><i class="bi bi-chevron-right"></i></a>
                <span class="fw-bold text-dark fs-6 ms-2">
                    <?= htmlspecialchars($fechaTxt) ?>
                    <?php if ($esHoy): ?><span class="badge bg-success ms-1" style="font-size:0.65rem;">HOY</span><?php endif; ?>
                </span>
            </div>

            <form action="<?= url('/admin/calendario') ?>" method="GET" class="d-flex align-items-center gap-2">
                <input type="hidden" name="vista" value="dia">
                <label for="fechaPick" class="small fw-bold text-muted mb-0">Ir a fecha:</label>
                <input type="date" id="fechaPick" name="fecha" value="<?= htmlspecialchars($fechaDia) ?>" class="form-control form-control-sm" style="border-radius:8px;font-size:.82rem;width:150px;" onchange="this.form.submit()">
            </form>
        </div>

        <?php $renderSolicitudesEspacio($solicitudesEspacio, $canchasPorSolicitud, false); ?>

        <!-- Estado de cada cancha -->
        <div class="row g-2 mb-3">
            <?php foreach ($canchas as $c): ?>
                <?php
                    $cid = (int)$c['id'];
                    $enMant = ($c['estado'] === 'mantenimiento');
                    $est = $enMant ? ['cls' => 'mant', 'txt' => 'En mantenimiento'] : $estadoCancha($porCancha[$cid], $esHoy, $ahoraMin);
                ?>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="cancha-card <?= $est['cls'] ?>">
                        <div class="c-title"><?= htmlspecialchars($c['nombre']) ?></div>
                        <div class="c-tipo"><?= htmlspecialchars($tipoLabels[$c['tipo']] ?? $c['tipo']) ?></div>
                        <div class="c-status"><i></i><span><?= htmlspecialchars($est['txt']) ?></span></div>
                        <?php if (!empty($est['sub'])): ?><div class="c-sub"><?= htmlspecialchars($est['sub']) ?></div><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php $leyenda(); ?>
        <div class="cal-hint"><i class="bi bi-arrows-expand-vertical me-1" style="transform:rotate(90deg);display:inline-block;"></i> Desliza para ver todas las canchas. Toca una reserva para ver el detalle o un espacio libre para reservar.</div>

        <div class="cal-wrap" id="calScroll" data-scroll="<?= $scrollA ?>">
            <div class="cal-grid" style="--cols: <?= count($canchas) ?>; --ppm: <?= CAL_PX_MIN ?>;">
                <div class="cal-corner">Hora</div>
                <?php foreach ($canchas as $c): ?>
                    <div class="cal-colhead">
                        <div class="t1"><?= htmlspecialchars($c['nombre']) ?></div>
                        <div class="t2"><?= htmlspecialchars($tipoLabels[$c['tipo']] ?? $c['tipo']) ?></div>
                    </div>
                <?php endforeach; ?>

                <div class="cal-hours" style="height: <?= $altoGrid ?>px;">
                    <?php for ($m = $rIni; $m < $rFin; $m += 30): ?>
                        <span class="h <?= $m % 60 === 0 ? '' : 'med' ?>" style="top: <?= round(($m - $rIni) * CAL_PX_MIN) + ($m === $rIni ? 8 : 0) ?>px;"><?= $hhmm($m) ?></span>
                    <?php endfor; ?>
                    <?php if ($lineaAhora): ?>
                        <div class="cal-now-lbl" style="top: <?= round(($ahoraMin - $rIni) * CAL_PX_MIN) ?>px;">Ahora <?= $hhmm($ahoraMin) ?></div>
                    <?php endif; ?>
                </div>

                <?php foreach ($canchas as $c): ?>
                    <?php $cid = (int)$c['id']; $esMant = ($c['estado'] === 'mantenimiento'); $lista = $carriles($porCancha[$cid]); ?>
                    <div class="cal-col <?= $esMant ? 'mant' : '' ?>" style="height: <?= $altoGrid ?>px;">
                        <?php if ($abreMin > $rIni): ?><div class="cal-closed" style="top:0; height: <?= round(($abreMin - $rIni) * CAL_PX_MIN) ?>px;"></div><?php endif; ?>
                        <?php if ($cierraMin < $rFin): ?><div class="cal-closed" style="top: <?= round(($cierraMin - $rIni) * CAL_PX_MIN) ?>px; height: <?= round(($rFin - $cierraMin) * CAL_PX_MIN) ?>px;"></div><?php endif; ?>
                        <?php if ($esMant): ?>
                            <div class="mant-lbl"><i class="bi bi-slash-circle"></i> Mantenimiento</div>
                        <?php else: ?>
                            <?php for ($m = max($rIni, $abreMin); $m < min($rFin, $cierraMin); $m += 30): ?>
                                <?php
                                    $ocupado = false;
                                    foreach ($lista as $bq) { if ($bq['ini'] < $m + 30 && $bq['fin'] > $m) { $ocupado = true; break; } }
                                    if ($ocupado) { continue; }
                                ?>
                                <a href="<?= url("/reserva/crear?cancha_id={$cid}&fecha={$fechaDia}&horario=" . $hhmm($m)) ?>" class="cal-add" style="top: <?= round(($m - $rIni) * CAL_PX_MIN) ?>px; height: <?= round(30 * CAL_PX_MIN) - 1 ?>px;" title="Reservar a las <?= $hhmm($m) ?>"><i class="bi bi-plus-lg me-1"></i> Reservar</a>
                            <?php endfor; ?>
                            <?php foreach ($lista as $bq) { $dibujarBloque($bq, $rIni, $fechaDia, (string)$c['nombre']); } ?>
                        <?php endif; ?>
                        <?php if ($lineaAhora): ?><div class="cal-now" style="top: <?= round(($ahoraMin - $rIni) * CAL_PX_MIN) ?>px;"></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- VISTA SEMANAL -->
    <?php
        $domingo = $dias[6];
        $rango = date('d', strtotime($lunes)) . ' ' . $mesesC[(int)date('n', strtotime($lunes))]
            . ' – ' . date('d', strtotime($domingo)) . ' ' . $mesesC[(int)date('n', strtotime($domingo))] . ' ' . date('Y', strtotime($domingo));
        $urlSemana = static fn(string $fecha): string => url('/admin/calendario?vista=semana&semana=' . $fecha . ($filtroCancha > 0 ? '&cancha=' . $filtroCancha : ''));

        $ahoraMin = $aMin(substr($horaActual, 0, 5));
        $porDia = [];
        $todasSemana = [];
        foreach ($dias as $d) {
            $porDia[$d] = $unicas($grilla[$d] ?? []);
            $todasSemana = array_merge($todasSemana, $porDia[$d]);
        }
        [$rIni, $rFin] = $rangoVisible($dias, $todasSemana);
        $altoGrid = (int)round(($rFin - $rIni) * CAL_PX_MIN);
        $hoyEnSemana = in_array($hoy, $dias, true);
        $scrollA = $hoyEnSemana ? max(0, (int)round(($ahoraMin - 60 - $rIni) * CAL_PX_MIN)) : 0;
        $lineaAhora = $hoyEnSemana && $ahoraMin >= $rIni && $ahoraMin <= $rFin;
    ?>

    <div class="panel-2 mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
            <div class="cal-nav">
                <a href="<?= $urlSemana(date('Y-m-d', strtotime($lunes . ' -7 days'))) ?>" class="cal-btn"><i class="bi bi-chevron-left"></i> Semana Anterior</a>
                <a href="<?= $urlSemana(date('Y-m-d')) ?>" class="cal-btn">Semana Actual</a>
                <a href="<?= $urlSemana(date('Y-m-d', strtotime($lunes . ' +7 days'))) ?>" class="cal-btn">Siguiente <i class="bi bi-chevron-right"></i></a>
                <span class="fw-bold ms-2 fs-6"><?= htmlspecialchars($rango) ?></span>
            </div>

            <form action="<?= url('/admin/calendario') ?>" method="GET" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="vista" value="semana">
                <input type="hidden" name="semana" value="<?= htmlspecialchars($semanaRef) ?>">
                <label for="canchaFiltro" class="small fw-bold text-muted mb-0">Filtrar cancha:</label>
                <select id="canchaFiltro" name="cancha" class="form-select form-select-sm" style="border-radius:9px;font-size:.82rem;width:180px;" onchange="this.form.submit()">
                    <option value="0">Todas las canchas</option>
                    <?php foreach ($canchas as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filtroCancha === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?> (<?= htmlspecialchars($tipoLabels[$c['tipo']] ?? $c['tipo']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php $renderSolicitudesEspacio($solicitudesEspacio, $canchasPorSolicitud, true); ?>
        <?php $leyenda(); ?>
        <div class="cal-hint"><i class="bi bi-arrows-expand-vertical me-1" style="transform:rotate(90deg);display:inline-block;"></i> Desliza para ver toda la semana. Toca una reserva para ver el detalle.</div>

        <div class="cal-wrap" id="calScroll" data-scroll="<?= $scrollA ?>">
            <div class="cal-grid" style="--cols: 7; --colmin: 150px; --ppm: <?= CAL_PX_MIN ?>;">
                <div class="cal-corner">Hora</div>
                <?php foreach ($dias as $i => $d): ?>
                    <?php $ts = strtotime($d); ?>
                    <div class="cal-colhead" style="<?= $d === $hoy ? 'background:#dcfce7;' : '' ?>">
                        <div class="t1"><?= $diasN[$i] ?> <?= date('d', $ts) ?></div>
                        <div class="t2"><?= $mesesC[(int)date('n', $ts)] ?> <?= date('Y', $ts) ?></div>
                    </div>
                <?php endforeach; ?>

                <div class="cal-hours" style="height: <?= $altoGrid ?>px;">
                    <?php for ($m = $rIni; $m < $rFin; $m += 30): ?>
                        <span class="h <?= $m % 60 === 0 ? '' : 'med' ?>" style="top: <?= round(($m - $rIni) * CAL_PX_MIN) + ($m === $rIni ? 8 : 0) ?>px;"><?= $hhmm($m) ?></span>
                    <?php endfor; ?>
                    <?php if ($lineaAhora): ?>
                        <div class="cal-now-lbl" style="top: <?= round(($ahoraMin - $rIni) * CAL_PX_MIN) ?>px;">Ahora <?= $hhmm($ahoraMin) ?></div>
                    <?php endif; ?>
                </div>

                <?php foreach ($dias as $d): ?>
                    <?php
                        $negD = getHorarioProvisionalNegocio($d);
                        $abreD = $aMin($negD['apertura']);
                        $cierraD = $aMin($negD['cierre']);
                        $lista = $carriles($porDia[$d]);
                    ?>
                    <div class="cal-col" style="height: <?= $altoGrid ?>px; <?= $d === $hoy ? 'background-color:#f0fdf4;' : ($d < $hoy ? 'background-color:#f8fafc;' : '') ?>">
                        <?php if ($abreD > $rIni): ?><div class="cal-closed" style="top:0; height: <?= round(($abreD - $rIni) * CAL_PX_MIN) ?>px;"></div><?php endif; ?>
                        <?php if ($cierraD < $rFin): ?><div class="cal-closed" style="top: <?= round(($cierraD - $rIni) * CAL_PX_MIN) ?>px; height: <?= round(($rFin - $cierraD) * CAL_PX_MIN) ?>px;"></div><?php endif; ?>
                        <?php foreach ($lista as $bq) { $dibujarBloque($bq, $rIni, $d, (string)($bq['r']['cancha_nombre'] ?? '')); } ?>
                        <?php if ($d === $hoy && $lineaAhora): ?><div class="cal-now" style="top: <?= round(($ahoraMin - $rIni) * CAL_PX_MIN) ?>px;"></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- DETALLE DE UNA RESERVA (al tocar un bloque) -->
<div class="modal fade" id="modalDetalleReserva" tabindex="-1" aria-labelledby="modalDetalleTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px; border:none; box-shadow:0 12px 32px rgba(0,0,0,.18);">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="modalDetalleTitulo">Reserva</h5>
                    <span class="cal-det-tag" id="detEtiqueta">Estado</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="cal-det-row"><span>Hora</span><strong id="detHora"></strong></div>
                <div class="cal-det-row"><span>Cliente</span><strong id="detCliente"></strong></div>
                <div class="cal-det-row"><span>Teléfono</span><strong id="detTelefono"></strong></div>
                <div class="cal-det-row"><span>Cancha</span><strong id="detCancha"></strong></div>
                <div class="cal-det-row"><span>Saldo</span><strong id="detSaldo"></strong></div>
            </div>
            <div class="modal-footer border-0 pt-0 flex-wrap gap-2 justify-content-start">
                <a href="#" id="detVer" class="btn fw-bold px-4" style="background:#1a7a3a;color:#fff;"><i class="bi bi-eye me-1"></i> Ver detalle</a>
                <a href="#" id="detWa" target="_blank" rel="noopener" class="btn btn-outline-success fw-bold"><i class="bi bi-whatsapp me-1"></i> WhatsApp</a>
            </div>
        </div>
    </div>
</div>

<script>
// Ir a la hora actual al abrir hoy, y detalle al tocar un bloque
(function () {
    var cont = document.getElementById('calScroll');
    if (cont) {
        var y = parseInt(cont.getAttribute('data-scroll') || '0', 10);
        if (y > 0) { cont.scrollTop = y; }
    }
    var modalEl = document.getElementById('modalDetalleReserva');
    if (!modalEl) { return; }
    var colores = {
        verificar: ['#fbeecb', '#d97706', '#78350f'], falta: ['#e0f2fe', '#0284c7', '#075985'],
        pagada: ['#d9efe0', '#16a34a', '#14532d'], fin: ['#e2e8f0', '#64748b', '#334155'], neg: ['#fbdcdc', '#f87171', '#991b1b']
    };
    function set(id, txt) { var e = document.getElementById(id); if (e) { e.textContent = txt || '—'; } }
    document.addEventListener('click', function (ev) {
        var blk = ev.target.closest ? ev.target.closest('.cal-blk') : null;
        if (!blk) { return; }
        var d;
        try { d = JSON.parse(blk.getAttribute('data-det')); } catch (e) { return; }
        document.getElementById('modalDetalleTitulo').textContent = 'Reserva #' + d.id;
        var tag = document.getElementById('detEtiqueta');
        var c = colores[d.clave] || colores.fin;
        tag.textContent = d.etiqueta; tag.style.background = c[0]; tag.style.borderColor = c[1]; tag.style.color = c[2];
        set('detHora', d.hora); set('detCliente', d.cliente); set('detTelefono', d.telefono);
        set('detCancha', d.cancha); set('detSaldo', d.saldo);
        document.getElementById('detVer').href = d.url_ver;
        var wa = document.getElementById('detWa');
        if (d.wa) { wa.href = d.wa; wa.style.display = ''; } else { wa.style.display = 'none'; }
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });
})();
</script>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
