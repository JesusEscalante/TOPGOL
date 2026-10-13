<?php
declare(strict_types=1);

/** @var string $vista */
/** @var string $hoy */
/** @var string $horaActual */
/** @var array<int, array<string, mixed>> $canchas */
/** @var array<int, string> $horas */

$diasN = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
$mesesC = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$mesesL = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

$tipoLabels = ['futbol_5' => 'Fútbol 5', 'futbol_7' => 'Fútbol 7', 'futbol_11' => 'Fútbol 11'];

$chipCls = static function(array $r, string $hoy, string $horaActual): string {
    $e = (string)($r['estado'] ?? 'pendiente');
    $asist = (string)($r['asistencia_estado'] ?? 'sin_registrar');
    if ($e === 'cancelada') {
        return $asist === 'no_asistio' ? 'noasist' : 'canc';
    }
    if ($asist === 'presente') return 'presente';
    if ($asist === 'tardanza') return 'tard';
    if ($asist === 'retraso_confirmado') return 'retraso';
    if ($asist === 'no_asistio') return 'noasist';
    if ($e === 'finalizada') return 'fin';

    if (($r['fecha'] ?? '') === $hoy) {
        $ini = (string)($r['hora_inicio'] ?? '');
        $fin = (string)($r['hora_fin'] ?? '');
        if ($fin === '00:00:00') $fin = '24:00:00';
        if ($ini <= $horaActual && $fin > $horaActual) {
            $tsIni = strtotime("{$hoy} {$ini}");
            $tsAct = strtotime("{$hoy} {$horaActual}");
            if ($tsAct - $tsIni <= 600) {
                return 'tolerancia'; // dentro de 10 min
            }
            return 'tard_alerta'; // más de 10 min sin registro
        }
        $tsIni = strtotime("{$hoy} {$ini}");
        $tsAct = strtotime("{$hoy} {$horaActual}");
        if ($tsIni > $tsAct && ($tsIni - $tsAct) <= 1800) {
            return 'prox'; // <= 30 min para iniciar
        }
    }
    if ($e === 'confirmada') return 'ok';
    return 'warn';
};

$chipLbl = static function(array $r, string $hoy, string $horaActual): string {
    $e = (string)($r['estado'] ?? 'pendiente');
    $asist = (string)($r['asistencia_estado'] ?? 'sin_registrar');
    if ($e === 'cancelada') {
        return $asist === 'no_asistio' ? 'No asistió' : 'Cancelada';
    }
    if ($asist === 'presente') return 'Presente';
    if ($asist === 'tardanza') return 'Tardanza';
    if ($asist === 'retraso_confirmado') return 'En camino';
    if ($asist === 'no_asistio') return 'No asistió';
    if ($e === 'finalizada') return 'Completada';

    if (($r['fecha'] ?? '') === $hoy) {
        $ini = (string)($r['hora_inicio'] ?? '');
        $fin = (string)($r['hora_fin'] ?? '');
        if ($fin === '00:00:00') $fin = '24:00:00';
        if ($ini <= $horaActual && $fin > $horaActual) {
            $tsIni = strtotime("{$hoy} {$ini}");
            $tsAct = strtotime("{$hoy} {$horaActual}");
            if ($tsAct - $tsIni <= 600) {
                return 'Tolerancia';
            }
            return 'Tardanza >10m';
        }
        $tsIni = strtotime("{$hoy} {$ini}");
        $tsAct = strtotime("{$hoy} {$horaActual}");
        if ($tsIni > $tsAct && ($tsIni - $tsAct) <= 1800) {
            return 'Próx. <=30m';
        }
    }
    if ($e === 'confirmada') return 'Confirmada';
    return 'Pendiente';
};
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<style>
    .cal-header-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
    .cal-btn { display: inline-flex; align-items: center; gap: 6px; background: #fff; border: 1px solid #cbd5e1; border-radius: 9px; padding: 7px 13px; font-size: .82rem; font-weight: 700; color: #1e293b; text-decoration: none; transition: all .15s ease; }
    .cal-btn:hover { border-color: #1a7a3a; color: #1a7a3a; background: #f8fafc; }
    .cal-btn.active { background: #1a7a3a; color: #fff; border-color: #1a7a3a; }
    .cal-table-dia { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 980px; font-size: .78rem; }
    .cal-table-dia thead th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; border-right: 1px solid #eef2f7; padding: 12px 10px; text-align: center; position: sticky; top: 0; z-index: 5; }
    .cal-table-dia thead th.th-hora { width: 75px; text-align: center; font-weight: 700; color: #64748b; background: #f1f5f9; }
    .cal-table-dia tbody th { background: #f8fafc; border-bottom: 1px solid #eef2f7; border-right: 1px solid #e2e8f0; padding: 6px 8px; text-align: center; color: #64748b; font-weight: 700; vertical-align: middle; }
    .cal-table-dia td { border-bottom: 1px solid #eef2f7; border-right: 1px solid #eef2f7; padding: 4px; vertical-align: top; height: 52px; background: #fff; transition: background .12s; }
    .cal-table-dia td:hover { background: #fcfdfe; }
    .cal-table-dia td.td-mantenimiento { background: repeating-linear-gradient(45deg, #fef2f2, #fef2f2 10px, #fee2e2 10px, #fee2e2 20px); opacity: .7; cursor: not-allowed; }

    /* Tarjetas de reserva dentro de la grilla */
    .r-chip { display: block; border-radius: 8px; padding: 6px 8px; margin-bottom: 3px; font-size: .72rem; text-decoration: none; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,.04); border-left: 4px solid transparent; transition: transform .12s, box-shadow .12s; position: relative; }
    .r-chip:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,.08); color: #0f172a; }
    .r-chip.live { background: #e0f2fe; border-left-color: #0284c7; }
    .r-chip.ok { background: #dcfce7; border-left-color: #16a34a; }
    .r-chip.warn { background: #fef3c7; border-left-color: #d97706; }
    .r-chip.fin { background: #f1f5f9; border-left-color: #64748b; color: #475569; }
    .r-chip.canc { background: #fef2f2; border-left-color: #94a3b8; color: #64748b; opacity: .75; text-decoration: line-through; }
    .r-chip.presente { background: #dcfce7; border-left-color: #16a34a; }
    .r-chip.tard { background: #fef3c7; border-left-color: #d97706; }
    .r-chip.retraso { background: #e0f2fe; border-left-color: #0284c7; }
    .r-chip.noasist { background: #fee2e2; border-left-color: #dc2626; color: #991b1b; }
    .r-chip.prox { background: #eff6ff; border-left-color: #2563eb; }
    .r-chip.tolerancia { background: #fefce8; border-left-color: #ca8a04; }
    .r-chip.tard_alerta { background: #fff1f2; border-left-color: #e11d48; border-width: 2px 2px 2px 4px; border-style: solid; }

    .r-chip .r-head { display: flex; align-items: center; justify-content: space-between; font-weight: 800; font-size: .74rem; gap: 4px; }
    .r-chip .r-badge { font-size: .62rem; padding: 2px 6px; border-radius: 4px; font-weight: 800; text-transform: uppercase; white-space: nowrap; }
    .r-chip.live .r-badge { background: #0284c7; color: #fff; }
    .r-chip.ok .r-badge { background: #16a34a; color: #fff; }
    .r-chip.warn .r-badge { background: #d97706; color: #fff; }
    .r-chip.fin .r-badge { background: #64748b; color: #fff; }
    .r-chip.canc .r-badge { background: #94a3b8; color: #fff; }
    .r-chip.presente .r-badge { background: #16a34a; color: #fff; }
    .r-chip.tard .r-badge { background: #d97706; color: #fff; }
    .r-chip.retraso .r-badge { background: #0284c7; color: #fff; }
    .r-chip.noasist .r-badge { background: #dc2626; color: #fff; }
    .r-chip.prox .r-badge { background: #2563eb; color: #fff; }
    .r-chip.tolerancia .r-badge { background: #ca8a04; color: #fff; }
    .r-chip.tard_alerta .r-badge { background: #e11d48; color: #fff; }

    .r-chip .r-client { font-weight: 600; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .r-chip .r-meta { display: flex; align-items: center; justify-content: space-between; font-size: .66rem; color: #475569; margin-top: 3px; }

    .r-chip-actions { display: flex; align-items: center; justify-content: flex-end; gap: 4px; margin-top: 4px; padding-top: 4px; border-top: 1px dashed rgba(0,0,0,.08); }
    .btn-act-chip { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 5px; border: 1px solid #cbd5e1; background: #fff; font-size: .7rem; color: #334155; text-decoration: none; transition: all .12s ease; }
    .btn-act-chip:hover { transform: scale(1.1); color: #fff; }
    .btn-act-chip.wa { color: #16a34a; border-color: #86efac; }
    .btn-act-chip.wa:hover { background: #16a34a; color: #fff; }
    .btn-act-chip.asist { color: #2563eb; border-color: #bfdbfe; }
    .btn-act-chip.asist:hover { background: #2563eb; color: #fff; }

    /* Botón flotante para reservar celda vacía */
    .btn-slot-add { display: none; width: 100%; height: 100%; min-height: 40px; align-items: center; justify-content: center; border: 1px dashed #cbd5e1; border-radius: 6px; background: transparent; color: #64748b; font-size: .7rem; font-weight: 700; text-decoration: none; }
    .cal-table-dia td:hover .btn-slot-add { display: flex; }
    .btn-slot-add:hover { background: #eef7f0; color: #1a7a3a; border-color: #86efac; }

    /* Tarjetas superiores de resumen de canchas */
    .cancha-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; box-shadow: 0 1px 3px rgba(16,28,51,.04); height: 100%; position: relative; }
    .cancha-card.en-curso { border-color: #38bdf8; background: #f0f9ff; }
    .cancha-card.en-mant { border-color: #fca5a5; background: #fef2f2; }
    .cancha-card .c-title { font-weight: 800; font-size: .95rem; color: #0f172a; display: flex; align-items: center; justify-content: space-between; }
    .cancha-card .c-status { font-size: .7rem; font-weight: 700; margin-top: 6px; }

    /* Leyenda */
    .cal-legend { display: flex; gap: 14px; flex-wrap: wrap; font-size: .76rem; font-weight: 600; color: #475569; margin-top: 14px; padding-top: 14px; border-top: 1px solid #e2e8f0; }
    .cal-legend .dot { display: inline-block; width: 11px; height: 11px; border-radius: 50%; margin-right: 5px; vertical-align: middle; }
</style>

<div class="cal-header-bar">
    <div>
        <h1 class="adm-h1">Calendario Administrativo</h1>
        <p class="adm-sub">Ocupación, disponibilidad y programación en tiempo real de las 6 canchas.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <!-- Selector de vista Día / Semana -->
        <div class="btn-group" role="group">
            <a href="<?= url('/admin/calendario?vista=dia&fecha=' . ($vista === 'dia' ? $fechaDia : date('Y-m-d'))) ?>" class="cal-btn <?= $vista === 'dia' ? 'active' : '' ?>">
                <i class="bi bi-grid-3x3-gap-fill"></i> Las 6 Canchas (Día)
            </a>
            <a href="<?= url('/admin/calendario?vista=semana&semana=' . ($vista === 'dia' ? $fechaDia : $semanaRef)) ?>" class="cal-btn <?= $vista === 'semana' ? 'active' : '' ?>">
                <i class="bi bi-calendar-week"></i> Vista Semanal
            </a>
        </div>

        <a href="<?= url('/reserva/crear') ?>" class="btn btn-sm fw-bold" style="background:#1a7a3a;color:#fff;border-radius:9px;padding:8px 16px;">
            <i class="bi bi-plus-lg me-1"></i> Nueva Reserva Manual
        </a>
    </div>
</div>

<?php if ($vista === 'dia'): ?>
    <?php
        $tsDia = strtotime($fechaDia);
        $diaN = $diasN[(date('N', $tsDia) - 1)];
        $fechaTxt = $diaN . ', ' . date('d', $tsDia) . ' de ' . $mesesL[(int)date('n', $tsDia)] . ' ' . date('Y', $tsDia);
        $diaAnterior = date('Y-m-d', strtotime($fechaDia . ' -1 day'));
        $diaSiguiente = date('Y-m-d', strtotime($fechaDia . ' +1 day'));
    ?>

    <!-- Navegación diaria -->
    <div class="panel-2 mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url('/admin/calendario?vista=dia&fecha=' . $diaAnterior) ?>" class="cal-btn" title="Día anterior">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <a href="<?= url('/admin/calendario?vista=dia&fecha=' . $hoy) ?>" class="cal-btn <?= $fechaDia === $hoy ? 'active' : '' ?>">
                    Hoy
                </a>
                <a href="<?= url('/admin/calendario?vista=dia&fecha=' . $diaSiguiente) ?>" class="cal-btn" title="Día siguiente">
                    <i class="bi bi-chevron-right"></i>
                </a>
                <span class="fw-bold text-dark fs-6 ms-2">
                    <?= htmlspecialchars($fechaTxt) ?>
                    <?php if ($fechaDia === $hoy): ?>
                        <span class="badge bg-success ms-1" style="font-size:0.65rem;">HOY</span>
                    <?php endif; ?>
                </span>
            </div>

            <form action="<?= url('/admin/calendario') ?>" method="GET" class="d-flex align-items-center gap-2">
                <input type="hidden" name="vista" value="dia">
                <label for="fechaPick" class="small fw-bold text-muted mb-0">Ir a fecha:</label>
                <input type="date" id="fechaPick" name="fecha" value="<?= htmlspecialchars($fechaDia) ?>" class="form-control form-control-sm" style="border-radius:8px;font-size:.82rem;width:150px;" onchange="this.form.submit()">
            </form>
        </div>

        <!-- 6 Tarjetas de Estado Rápido de las Canchas -->
        <div class="row g-2 mb-4">
            <?php foreach ($canchas as $c): ?>
                <?php
                    $cid = (int)$c['id'];
                    $info = $resumenCanchas[$cid] ?? [];
                    $enMant = (bool)($info['en_mantenimiento'] ?? false);
                    $enCurso = $info['reserva_en_curso'] ?? null;
                    $proxima = $info['proxima_reserva'] ?? null;
                    $totalRes = (int)($info['total_reservas'] ?? 0);
                ?>
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="cancha-card <?= $enMant ? 'en-mant' : ($enCurso ? 'en-curso' : '') ?>">
                        <div class="c-title">
                            <span><?= htmlspecialchars($c['nombre']) ?></span>
                            <span class="badge bg-secondary" style="font-size:0.58rem;"><?= htmlspecialchars($tipoLabels[$c['tipo']] ?? $c['tipo']) ?></span>
                        </div>
                        <div class="small text-muted" style="font-size:0.7rem;">S/ <?= number_format((float)$c['precio_hora'], 2) ?>/h</div>
                        <div class="c-status">
                            <?php if ($enMant): ?>
                                <span class="text-danger"><i class="bi bi-tools"></i> Mantenimiento</span>
                            <?php elseif ($enCurso): ?>
                                <span class="text-primary"><i class="bi bi-circle-fill" style="font-size:0.5rem;"></i> En curso hasta <?= substr((string)$enCurso['hora_fin'], 0, 5) ?></span>
                            <?php elseif ($proxima && $fechaDia === $hoy): ?>
                                <span class="text-success"><i class="bi bi-clock"></i> Próx: <?= substr((string)$proxima['hora_inicio'], 0, 5) ?></span>
                            <?php else: ?>
                                <span class="text-success"><i class="bi bi-check-circle"></i> <?= $totalRes > 0 ? "{$totalRes} reservas" : "Libre hoy" ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Matriz de Ocupación por Canchas -->
        <div class="table-responsive" style="max-height: 720px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
            <table class="cal-table-dia">
                <thead>
                    <tr>
                        <th class="th-hora">Hora</th>
                        <?php foreach ($canchas as $c): ?>
                            <th>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($c['nombre']) ?></div>
                                <div class="small text-muted" style="font-size:0.68rem;">
                                    <?= htmlspecialchars($tipoLabels[$c['tipo']] ?? $c['tipo']) ?> · S/ <?= number_format((float)$c['precio_hora'], 2) ?>/h
                                    <?php if ($c['estado'] === 'mantenimiento'): ?>
                                        <span class="badge bg-danger ms-1" style="font-size:0.55rem;">Mantenimiento</span>
                                    <?php endif; ?>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($h = 7; $h <= 23; $h++): ?>
                        <?php foreach (['00', '30'] as $mm): ?>
                            <?php
                                $slot = sprintf('%02d:%s', $h, $mm);
                                $slotFin = date('H:i', strtotime($slot . ' +30 minutes'));
                            ?>
                            <tr>
                                <th><?= $slot ?></th>
                                <?php foreach ($canchas as $c): ?>
                                    <?php
                                        $cid = (int)$c['id'];
                                        $esMant = ($c['estado'] === 'mantenimiento');
                                        $celda = $grillaDia[$cid][$slot] ?? [];
                                    ?>
                                    <td class="<?= $esMant ? 'td-mantenimiento' : '' ?>">
                                        <?php if ($esMant): ?>
                                            <div class="text-center text-danger small fw-bold" style="font-size:0.65rem;padding:6px 0;">
                                                <i class="bi bi-slash-circle"></i> Mantenimiento
                                            </div>
                                        <?php elseif (!empty($celda)): ?>
                                            <?php foreach ($celda as $r): ?>
                                                <?php
                                                    $cls = $chipCls($r, $hoy, $horaActual);
                                                    $lbl = $chipLbl($r, $hoy, $horaActual);
                                                    $orig = (string)($r['origen'] ?? 'web');
                                                    $nomCliente = (string)($r['cliente_nombre'] ?? $r['usuario_nombre'] ?? 'Cliente');
                                                    $tel = (string)($r['contacto_telefono'] ?? '');
                                                    $hIni = substr((string)$r['hora_inicio'], 0, 5);
                                                    $hFin = substr((string)$r['hora_fin'], 0, 5);
                                                ?>
                                                <?php $waLink = generarEnlaceWhatsApp($r); ?>
                                                <a href="<?= url('/admin/reserva/editar/' . $r['id']) ?>" class="r-chip <?= $cls ?>" title="Clic para ver o modificar">
                                                    <div class="r-head">
                                                        <span><i class="bi bi-clock me-1"></i><?= $hIni ?> - <?= $hFin ?></span>
                                                        <span class="r-badge"><?= $lbl ?></span>
                                                    </div>
                                                    <div class="r-client">
                                                        <i class="bi bi-person me-1"></i><?= htmlspecialchars($nomCliente) ?>
                                                    </div>
                                                    <div class="r-meta">
                                                        <span>
                                                            <?php if ($orig === 'whatsapp'): ?><i class="bi bi-whatsapp text-success"></i> WhatsApp
                                                            <?php elseif ($orig === 'llamada'): ?><i class="bi bi-telephone text-warning"></i> Llamada
                                                            <?php elseif ($orig === 'presencial'): ?><i class="bi bi-person-badge text-success"></i> Presencial
                                                            <?php else: ?><i class="bi bi-globe text-primary"></i> Web
                                                            <?php endif; ?>
                                                        </span>
                                                        <span>#<?= $r['id'] ?></span>
                                                    </div>
                                                    <div class="r-chip-actions">
                                                        <?php if ($waLink !== '#'): ?>
                                                            <a href="<?= $waLink ?>" target="_blank" rel="noopener" class="btn-act-chip wa" onclick="event.stopPropagation();" title="Contactar por WhatsApp">
                                                                <i class="bi bi-whatsapp"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn-act-chip asist" onclick="event.preventDefault(); event.stopPropagation(); abrirModalAsistencia(<?= htmlspecialchars(json_encode([
                                                            'id' => $r['id'],
                                                            'cliente' => $nomCliente,
                                                            'telefono' => $tel,
                                                            'cancha' => $c['nombre'],
                                                            'fecha' => $fechaDia,
                                                            'hora_inicio' => $hIni,
                                                            'hora_fin' => $hFin,
                                                            'asistencia_estado' => $r['asistencia_estado'] ?? 'sin_registrar',
                                                            'asistencia_hora' => $r['asistencia_hora'] ?? '',
                                                            'asistencia_notas' => $r['asistencia_notas'] ?? '',
                                                            'estado' => $r['estado']
                                                        ]), ENT_QUOTES, 'UTF-8') ?>)" title="Registrar asistencia">
                                                            <i class="bi bi-clock-history"></i>
                                                        </button>
                                                    </div>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <a href="<?= url("/reserva/crear?cancha_id={$cid}&fecha={$fechaDia}&horario={$slot}") ?>" class="btn-slot-add" title="Reservar en este horario">
                                                <i class="bi bi-plus-lg me-1"></i> Reservar
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <div class="cal-legend">
            <span><i class="dot" style="background:#16a34a;"></i> Presente / A tiempo</span>
            <span><i class="dot" style="background:#ca8a04;"></i> En tolerancia (0-10 min)</span>
            <span><i class="dot" style="background:#e11d48;"></i> Tardanza (+10 min)</span>
            <span><i class="dot" style="background:#0284c7;"></i> Retraso avisado (En camino)</span>
            <span><i class="dot" style="background:#2563eb;"></i> Próxima (&le;30 min)</span>
            <span><i class="dot" style="background:#dc2626;"></i> No asistió / Liberada</span>
            <span><i class="dot" style="background:#64748b;"></i> Completada</span>
        </div>
    </div>

<?php else: ?>
    <!-- VISTA SEMANAL -->
    <?php
        $domingo = $dias[6];
        $rango = date('d', strtotime($lunes)) . ' ' . $mesesC[(int)date('n', strtotime($lunes))]
            . ' – ' . date('d', strtotime($domingo)) . ' ' . $mesesC[(int)date('n', strtotime($domingo))] . ' ' . date('Y', strtotime($domingo));
        $urlSemana = static fn(string $fecha): string => url('/admin/calendario?vista=semana&semana=' . $fecha . ($filtroCancha > 0 ? '&cancha=' . $filtroCancha : ''));
    ?>

    <div class="panel-2 mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
            <div class="d-flex align-items-center gap-2">
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

        <div class="table-responsive" style="max-height: 720px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
            <table class="cal-table-dia">
                <thead>
                    <tr>
                        <th class="th-hora">Hora</th>
                        <?php foreach ($dias as $i => $d): ?>
                            <?php $ts = strtotime($d); ?>
                            <th class="<?= $d === $hoy ? 'bg-success-subtle text-success-emphasis' : '' ?>">
                                <div class="fw-bold"><?= $diasN[$i] ?> <?= date('d', $ts) ?></div>
                                <div class="small text-muted" style="font-size:0.68rem;"><?= $mesesC[(int)date('n', $ts)] ?> <?= date('Y', $ts) ?></div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($h = 7; $h <= 23; $h++): ?>
                        <?php foreach (['00', '30'] as $mm): ?>
                            <?php $slot = sprintf('%02d:%s', $h, $mm); ?>
                            <tr>
                                <th><?= $slot ?></th>
                                <?php foreach ($dias as $d): ?>
                                    <?php $celda = $grilla[$d][$slot] ?? []; ?>
                                    <td class="<?= $d === $hoy ? 'bg-success-subtle bg-opacity-25' : ($d < $hoy ? 'bg-light' : '') ?>">
                                        <?php if (!empty($celda)): ?>
                                            <?php foreach ($celda as $r): ?>
                                                <?php
                                                    $cls = $chipCls($r, $hoy, $horaActual);
                                                    $nomCliente = (string)($r['cliente_nombre'] ?? $r['usuario_nombre'] ?? 'Cliente');
                                                    $hIni = substr((string)$r['hora_inicio'], 0, 5);
                                                    $hFin = substr((string)$r['hora_fin'], 0, 5);
                                                ?>
                                                <a href="<?= url('/admin/reserva/editar/' . $r['id']) ?>" class="r-chip <?= $cls ?>" title="Ver / Editar #<?= $r['id'] ?>">
                                                    <div class="r-head">
                                                        <span><?= $hIni ?>-<?= $hFin ?></span>
                                                        <span><?= htmlspecialchars((string)($r['cancha_nombre'] ?? '')) ?></span>
                                                    </div>
                                                    <div class="r-client"><?= htmlspecialchars($nomCliente) ?></div>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>

        <div class="cal-legend">
            <span><i class="dot" style="background:#16a34a;"></i> Presente</span>
            <span><i class="dot" style="background:#ca8a04;"></i> Tolerancia</span>
            <span><i class="dot" style="background:#e11d48;"></i> Tardanza</span>
            <span><i class="dot" style="background:#0284c7;"></i> En camino</span>
            <span><i class="dot" style="background:#dc2626;"></i> No asistió</span>
            <span><i class="dot" style="background:#64748b;"></i> Completada</span>
        </div>
    </div>
<?php endif; ?>

<!-- MODAL DE CONTROL OPERATIVO: ASISTENCIA Y PUNTUALIDAD -->
<div class="modal fade" id="modalControlAsistencia" tabindex="-1" aria-labelledby="modalControlAsistenciaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; border:none; box-shadow:0 10px 30px rgba(0,0,0,.15);">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold text-dark" id="modalControlAsistenciaLabel">
                        <i class="bi bi-person-check-fill text-success me-1"></i> Control de Asistencia y Puntualidad
                    </h5>
                    <p class="text-muted small mb-0" id="modalSubTitulo">Reserva #0</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formAsistencia" method="POST" action="">
                <?= csrf_field() ?>
                <div class="modal-body pt-3">
                    <!-- Resumen de reserva -->
                    <div class="p-3 mb-3" style="background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-dark" id="modalCliente">Cliente</strong>
                            <span class="badge bg-secondary" id="modalCancha">Cancha</span>
                        </div>
                        <div class="small text-muted mb-2">
                            <i class="bi bi-calendar3 me-1"></i><span id="modalFecha">Fecha</span> ·
                            <i class="bi bi-clock me-1"></i><span id="modalHorario" class="fw-bold text-dark">00:00 - 00:00</span>
                        </div>
                        <div class="d-flex gap-2 flex-wrap pt-2" style="border-top:1px dashed #cbd5e1;">
                            <a href="#" id="modalBtnTel" class="btn btn-sm btn-outline-dark" style="font-size:0.75rem; border-radius:7px;">
                                <i class="bi bi-telephone-fill me-1 text-success"></i> Llamar
                            </a>
                            <a href="#" id="modalBtnWa" target="_blank" rel="noopener" class="btn btn-sm btn-success" style="font-size:0.75rem; border-radius:7px; background:#16a34a; border-color:#16a34a;">
                                <i class="bi bi-whatsapp me-1"></i> WhatsApp
                            </a>
                            <a href="#" id="modalBtnReprog" class="btn btn-sm btn-outline-primary" style="font-size:0.75rem; border-radius:7px;">
                                <i class="bi bi-arrow-repeat me-1"></i> Reprogramar (&ge;1h)
                            </a>
                        </div>
                    </div>

                    <!-- Alerta de Política -->
                    <div class="alert alert-warning py-2 px-3 small mb-3 d-flex align-items-start gap-2" style="border-radius:9px; font-size:0.78rem;">
                        <i class="bi bi-shield-exclamation text-warning fs-6"></i>
                        <div>
                            <strong>Regla de Puntualidad:</strong> 10 minutos de tolerancia. El alquiler inicia a la hora programada y culmina estrictamente a su hora pactada. La tardanza no extiende el horario.
                        </div>
                    </div>

                    <!-- Estado de asistencia -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-dark mb-1">Estado de Asistencia <span class="text-danger">*</span></label>
                        <select name="asistencia_estado" id="modalSelectEstado" class="form-select form-select-sm" style="border-radius:8px;" required onchange="toggleOpcionesAsistencia(this.value)">
                            <option value="presente">Llegó puntual / dentro de tolerancia (Presente)</option>
                            <option value="tardanza">Llegó tarde (más de 10 min de retraso)</option>
                            <option value="retraso_confirmado">Cliente avisó retraso (En camino - mantener cancha)</option>
                            <option value="no_asistio">No asistió (Inasistencia)</option>
                            <option value="sin_registrar">Sin registrar (Pendiente de llegada)</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3" id="filaHoraLlegada">
                        <div class="col-sm-6">
                            <label class="form-label small fw-bold text-muted mb-1">Hora de llegada registrada:</label>
                            <input type="time" name="asistencia_hora" id="modalInputHora" class="form-control form-control-sm" style="border-radius:8px;">
                        </div>
                        <div class="col-sm-6 d-flex align-items-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100" style="border-radius:8px; font-size:0.75rem;" onclick="setearHoraActual()">
                                <i class="bi bi-clock me-1"></i> Usar hora actual
                            </button>
                        </div>
                    </div>

                    <!-- Liberar cancha si no asistió -->
                    <div class="mb-3 p-2 bg-light rounded" id="cajaLiberarCancha" style="display:none; border:1px solid #fee2e2;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="liberar_cancha" value="1" id="checkLiberar">
                            <label class="form-check-label small fw-bold text-danger" for="checkLiberar">
                                Liberar cancha para nuevos clientes (marca estado = cancelada)
                            </label>
                        </div>
                        <div class="small text-muted" style="font-size:0.7rem; margin-left:1.5rem;">
                            Los adelantos verificados en Caja permanecen registrados y retenidos según la política de Top Gol.
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-dark mb-1">Notas / Observaciones del Encargado:</label>
                        <textarea name="asistencia_notas" id="modalInputNotas" class="form-control form-control-sm" rows="2" style="border-radius:8px;" placeholder="Ej: Llamó para avisar tráfico; llegó 18:25..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:8px;">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success fw-bold" style="background:#1a7a3a; border-color:#1a7a3a; border-radius:8px; padding:7px 18px;">
                        <i class="bi bi-check-circle me-1"></i> Guardar Asistencia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalAsistencia(data) {
    const form = document.getElementById('formAsistencia');
    form.action = '<?= url("/admin/reserva/asistencia/") ?>' + data.id;

    document.getElementById('modalSubTitulo').textContent = 'Reserva #' + data.id;
    document.getElementById('modalCliente').textContent = data.cliente || 'Cliente';
    document.getElementById('modalCancha').textContent = data.cancha || 'Cancha';
    document.getElementById('modalFecha').textContent = data.fecha || '';
    document.getElementById('modalHorario').textContent = (data.hora_inicio || '') + ' - ' + (data.hora_fin || '');

    // Botones de llamada y WhatsApp
    const telLimpio = (data.telefono || '').replace(/\D+/g, '');
    const btnTel = document.getElementById('modalBtnTel');
    const btnWa = document.getElementById('modalBtnWa');
    if (telLimpio) {
        btnTel.href = 'tel:+51' + telLimpio;
        btnTel.style.display = 'inline-flex';
        const waNum = telLimpio.length === 9 ? '51' + telLimpio : telLimpio;
        const msg = encodeURIComponent('Hola ' + data.cliente + ', te saludamos de TOP GOL respecto a tu reserva #' + data.id + ' de ' + data.hora_inicio + ' a ' + data.hora_fin + '. Recuerda que contamos con 10 min de tolerancia.');
        btnWa.href = 'https://wa.me/' + waNum + '?text=' + msg;
        btnWa.style.display = 'inline-flex';
    } else {
        btnTel.style.display = 'none';
        btnWa.style.display = 'none';
    }

    // Botón reprogramar
    document.getElementById('modalBtnReprog').href = '<?= url("/admin/reserva/editar/") ?>' + data.id + '#seccion-reprogramar';

    // Campos de formulario
    const sel = document.getElementById('modalSelectEstado');
    sel.value = data.asistencia_estado || 'sin_registrar';
    document.getElementById('modalInputHora').value = data.asistencia_hora ? data.asistencia_hora.substring(0, 5) : '';
    document.getElementById('modalInputNotas').value = data.asistencia_notas || '';
    document.getElementById('checkLiberar').checked = false;

    toggleOpcionesAsistencia(sel.value);

    const modalEl = document.getElementById('modalControlAsistencia');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function toggleOpcionesAsistencia(estado) {
    const cajaLiberar = document.getElementById('cajaLiberarCancha');
    if (estado === 'no_asistio') {
        cajaLiberar.style.display = 'block';
    } else {
        cajaLiberar.style.display = 'none';
    }
}

function setearHoraActual() {
    const now = new Date();
    const hh = String(now.getHours()).padStart(2, '0');
    const mm = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('modalInputHora').value = hh + ':' + mm;
}
</script>

<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
