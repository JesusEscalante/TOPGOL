<?php
declare(strict_types=1);

/** @var array<int, array<string, mixed>> $reservas */
/** @var array<string, mixed> $estadisticas */

$filtroEstado = $filtroEstado ?? '';
$filtroPago = $filtroPago ?? '';
$filtroAsistencia = $filtroAsistencia ?? '';
$busqueda = $busqueda ?? '';

$estLbl = static fn(string $e): string => match ($e) {
    'confirmada' => 'Confirmada', 'pendiente' => 'Pendiente',
    'cancelada' => 'Cancelada', 'finalizada' => 'Completada', default => ucfirst($e),
};
$estCls = static fn(string $e): string => match ($e) {
    'confirmada' => 'ok', 'pendiente' => 'warn',
    'cancelada' => 'bad', 'finalizada' => 'fin', default => 'fin',
};
$pagoLbl = static fn(string $p): string => match ($p) {
    'verificado' => 'Pagado', 'en_revision' => 'Por verificar',
    'rechazado' => 'Rechazado', default => 'Pendiente',
};
$pagoCls = static fn(string $p): string => match ($p) {
    'verificado' => 'ok', 'en_revision' => 'warn',
    'rechazado' => 'bad', default => 'warn',
};
$asistLbl = static fn(string $a): string => match ($a) {
    'presente' => 'Presente',
    'tardanza' => 'Tardanza',
    'retraso_confirmado' => 'Retraso avisado',
    'no_asistio' => 'No asistió',
    default => 'Sin registrar'
};
$asistCls = static fn(string $a): string => match ($a) {
    'presente' => 'ok',
    'tardanza' => 'warn',
    'retraso_confirmado' => 'info',
    'no_asistio' => 'bad',
    default => 'mut'
};
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<style>
    .f-bar { display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-end; }
    .f-bar .fld { display: flex; flex-direction: column; gap: 4px; }
    .f-bar label { font-size: .68rem; font-weight: 700; color: #7c8aa0; text-transform: uppercase; letter-spacing: .4px; }
    .f-bar .form-control, .f-bar .form-select { font-size: .82rem; border-radius: 9px; }
    .btn-mini { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #101c33; font-size: .85rem; margin-right: 4px; transition: all .12s; text-decoration: none; }
    .btn-mini:hover { border-color: #1a7a3a; color: #1a7a3a; }
    .btn-mini.ok { color: #166534; border-color: #bfe6cc; background: #eef7f0; }
    .btn-mini.ok:hover { background: #1a7a3a; color: #fff; }
    .btn-mini.bad { color: #991b1b; border-color: #f3c2c2; background: #fdf0f0; }
    .btn-mini.bad:hover { background: #dc2626; color: #fff; }
    .btn-mini.wa { color: #166534; border-color: #86efac; background: #f0fdf4; }
    .btn-mini.wa:hover { background: #16a34a; color: #fff; border-color: #16a34a; }
    .btn-mini.asist { color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff; }
    .btn-mini.asist:hover { background: #2563eb; color: #fff; border-color: #2563eb; }
    .est-select { font-size: .72rem; border-radius: 8px; padding: 3px 6px; margin-top: 6px; max-width: 130px; }
    .comp-link { display: inline-flex; align-items: center; gap: 4px; font-size: .72rem; font-weight: 700; color: #1d4ed8; margin-top: 6px; }
    .pill.info { background: #e0f2fe; color: #0284c7; }
    .pill.mut { background: #f1f5f9; color: #64748b; }
</style>

            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
                <div>
                    <h1 class="adm-h1">Reservas</h1>
                    <p class="adm-sub">Gestiona las reservas, reprograma horarios y verifica los comprobantes de pago.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/admin/calendario') ?>" class="btn btn-sm btn-outline-dark fw-bold" style="border-radius:9px;padding:9px 16px;">
                        <i class="bi bi-calendar3 me-1"></i> Calendario de canchas
                    </a>
                    <a href="<?= url('/reserva/crear') ?>" class="btn btn-sm fw-bold" style="background:#1a7a3a;color:#fff;border-radius:9px;padding:9px 16px;">
                        <i class="bi bi-plus-lg me-1"></i> Registrar reserva manual
                    </a>
                </div>
            </div>

            <!-- Métricas -->
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-xl-3">
                    <div class="kpi">
                        <span class="kpi-ico b"><i class="bi bi-calendar-check"></i></span>
                        <div><small class="lbl">Total reservas</small><div class="val"><?= (int)($estadisticas['total_reservas'] ?? 0) ?></div></div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="kpi">
                        <span class="kpi-ico g"><i class="bi bi-check-circle"></i></span>
                        <div><small class="lbl">Confirmadas</small><div class="val"><?= (int)($estadisticas['confirmadas'] ?? 0) ?></div></div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="kpi">
                        <span class="kpi-ico y"><i class="bi bi-clock-history"></i></span>
                        <div><small class="lbl">Pendientes</small><div class="val"><?= (int)($estadisticas['pendientes'] ?? 0) ?></div></div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="kpi">
                        <span class="kpi-ico y"><i class="bi bi-credit-card"></i></span>
                        <div><small class="lbl">Pagos por verificar</small><div class="val"><?= (int)($estadisticas['pagos_en_revision'] ?? 0) ?></div></div>
                        <div class="foot"><a href="<?= url('/admin/pagos?tab=revision') ?>">Ver →</a></div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="panel-2 mb-4">
                <form action="<?= url('/admin/reservas') ?>" method="GET" class="f-bar">
                    <div class="fld" style="flex:1;min-width:180px;">
                        <label for="fq">Buscar</label>
                        <input type="text" class="form-control" id="fq" name="q" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Código, cliente o cancha...">
                    </div>
                    <div class="fld">
                        <label for="festado">Estado</label>
                        <select class="form-select" id="festado" name="estado">
                            <option value="">Todos</option>
                            <?php foreach (['pendiente' => 'Pendiente', 'confirmada' => 'Confirmada', 'finalizada' => 'Completada', 'cancelada' => 'Cancelada'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= $filtroEstado === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fld">
                        <label for="fasist">Asistencia</label>
                        <select class="form-select" id="fasist" name="asistencia">
                            <option value="">Todas</option>
                            <option value="sin_registrar" <?= $filtroAsistencia === 'sin_registrar' ? 'selected' : '' ?>>Sin registrar</option>
                            <option value="presente" <?= $filtroAsistencia === 'presente' ? 'selected' : '' ?>>Presente</option>
                            <option value="tardanza" <?= $filtroAsistencia === 'tardanza' ? 'selected' : '' ?>>Tardanza</option>
                            <option value="retraso_confirmado" <?= $filtroAsistencia === 'retraso_confirmado' ? 'selected' : '' ?>>Retraso avisado</option>
                            <option value="no_asistio" <?= $filtroAsistencia === 'no_asistio' ? 'selected' : '' ?>>No asistió</option>
                        </select>
                    </div>
                    <div class="fld">
                        <label for="fpago">Pago</label>
                        <select class="form-select" id="fpago" name="pago">
                            <option value="">Todos</option>
                            <?php foreach (['pendiente' => 'Pendiente', 'en_revision' => 'Por verificar', 'verificado' => 'Pagado', 'rechazado' => 'Rechazado'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= $filtroPago === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm fw-bold" style="background:#101c33;color:#fff;border-radius:9px;padding:9px 18px;">
                        <i class="bi bi-funnel me-1"></i> Filtrar
                    </button>
                    <a href="<?= url('/admin/reservas') ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:9px;padding:9px 16px;">Limpiar</a>
                </form>

                <div class="d-flex align-items-center justify-content-between my-2 ">
                    <h3>Listado de reservas (<?= count($reservas) ?>)</h3>
                </div>
                <div class="table-responsive">
                <table class="tbl">
                    <thead>
                        <tr><th>#</th><th>Cliente</th><th>Cancha</th><th>Fecha y hora</th><th>Total</th><th>Estado</th><th>Asistencia</th><th>Pago</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservas)): ?>
                            <tr><td colspan="9" class="text-center text-muted py-4">No se encontraron reservas con esos filtros.</td></tr>
                        <?php else: ?>
                        <?php
                        // Agrupar reservas
                        $grupos = [];
                        foreach ($reservas as $r) {
                            $k = !empty($r['evento_grupo']) ? 'evt_'.$r['evento_grupo'] : 'res_'.$r['id'];
                            if (!isset($grupos[$k])) {
                                $grupos[$k] = [
                                    'id' => $r['id'],
                                    'is_grupo' => !empty($r['evento_grupo']),
                                    'evento_grupo' => $r['evento_grupo'],
                                    'usuario_nombre' => !empty($r['cliente_nombre']) ? $r['cliente_nombre'] : $r['usuario_nombre'],
                                    'usuario_email' => $r['usuario_email'],
                                    'contacto_telefono' => !empty($r['contacto_telefono']) ? $r['contacto_telefono'] : ($r['usuario_telefono'] ?? ''),
                                    'origen' => $r['origen'] ?? 'web',
                                    'fecha' => $r['fecha'],
                                    'hora_inicio' => $r['hora_inicio'],
                                    'hora_fin' => $r['hora_fin'],
                                    'estado' => $r['estado'],
                                    'asistencia_estado' => $r['asistencia_estado'] ?? 'sin_registrar',
                                    'asistencia_hora' => $r['asistencia_hora'] ?? null,
                                    'asistencia_notas' => $r['asistencia_notas'] ?? null,
                                    'pago_estado' => $r['pago_estado'],
                                    'comprobante_ruta' => $r['comprobante_ruta'],
                                    'total_pago' => 0.0,
                                    'adelanto_monto' => 0.0,
                                    'total_pagado' => 0.0,
                                    'saldo_pendiente' => 0.0,
                                    'canchas' => []
                                ];
                            }
                            $grupos[$k]['total_pago'] += (float)$r['total_pago'];
                            $grupos[$k]['adelanto_monto'] += (float)$r['adelanto_monto'];
                            $grupos[$k]['total_pagado'] += (float)($r['total_pagado'] ?? 0.0);
                            $grupos[$k]['saldo_pendiente'] += (float)($r['saldo_pendiente'] ?? 0.0);
                            $grupos[$k]['canchas'][] = ['id' => $r['cancha_id'], 'nombre' => $r['cancha_nombre']];
                        }
                        ?>
                        <?php foreach ($grupos as $r): ?>
                            <?php
                                $pe = (string)($r['pago_estado'] ?? 'pendiente');
                                $comp = (string)($r['comprobante_ruta'] ?? '');
                                $mF = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
                                $tsF = strtotime($r['fecha']);
                                $fCorta = date('d', $tsF) . ' ' . $mF[(int)date('n', $tsF)] . ' ' . date('Y', $tsF);
                                $totalG = round((float)$r['total_pago'], 2);
                                $pagadoG = round((float)$r['total_pagado'], 2);
                                $saldo = max(0.0, round($totalG - $pagadoG, 2));
                                $orig = (string)($r['origen'] ?? 'web');
                                $canchaNombreDisplay = !empty($r['canchas']) ? $r['canchas'][0]['nombre'] : 'Cancha';
                                $waLink = generarEnlaceWhatsApp($r);
                            ?>
                            <tr>
                                <td class="fw-semibold">
                                    #<?= $r['id'] ?>
                                    <?php if ($r['is_grupo']): ?><br><span class="badge bg-secondary" style="font-size:0.6rem;">Evento</span><?php endif; ?>
                                    <div class="mt-1">
                                        <?php if ($orig === 'whatsapp'): ?>
                                            <span class="badge" style="background:#dcfce7;color:#15803d;font-size:0.62rem;"><i class="bi bi-whatsapp"></i> WhatsApp</span>
                                        <?php elseif ($orig === 'llamada'): ?>
                                            <span class="badge" style="background:#fef9c3;color:#854d0e;font-size:0.62rem;"><i class="bi bi-telephone"></i> Llamada</span>
                                        <?php elseif ($orig === 'presencial'): ?>
                                            <span class="badge" style="background:#ecfdf5;color:#047857;font-size:0.62rem;"><i class="bi bi-person-badge"></i> Presencial</span>
                                        <?php else: ?>
                                            <span class="badge" style="background:#eff6ff;color:#1d4ed8;font-size:0.62rem;"><i class="bi bi-globe"></i> Web</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($r['usuario_nombre']) ?></div>
                                    <?php if (!empty($r['contacto_telefono'])): ?>
                                        <div style="font-size:0.75rem; color:#166534;"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($r['contacto_telefono']) ?></div>
                                    <?php endif; ?>
                                    <small class="text-muted"><?= htmlspecialchars($r['usuario_email']) ?></small>
                                </td>
                                <td>
                                    <?php if ($r['is_grupo']): ?>
                                        <div style="font-size:0.8rem; font-weight:600;"><?= count($r['canchas']) ?> canchas:</div>
                                        <?php foreach ($r['canchas'] as $c): ?>
                                            <div style="font-size:0.75rem; color:#5b6b82;">- <?= htmlspecialchars($c['nombre']) ?></div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($r['canchas'][0]['nombre'] ?? '') ?>
                                    <?php endif; ?>
                                </td>
                                <td class="fh"><?= $fCorta ?><small><?= date('H:i', strtotime($r['hora_inicio'])) ?> - <?= date('H:i', strtotime($r['hora_fin'])) ?></small></td>
                                <td>
                                    <div class="fw-bold"><?= formatPrice($totalG) ?></div>
                                    <div style="font-size:0.7rem; color:#166534;">Cobrado: <?= formatPrice($pagadoG) ?></div>
                                    <?php if ($saldo > 0 && $r['estado'] !== 'cancelada'): ?>
                                        <div style="font-size:0.7rem; color:#dc2626; font-weight:700;">Saldo: <?= formatPrice($saldo) ?></div>
                                    <?php elseif ($r['estado'] === 'cancelada'): ?>
                                        <div style="font-size:0.7rem; color:#64748b;">Cancelada</div>
                                    <?php else: ?>
                                        <div style="font-size:0.7rem; color:#166534; font-weight:700;">Pagado 100%</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="pill <?= $estCls($r['estado']) ?>"><?= $estLbl($r['estado']) ?></span>
                                    <form action="<?= url('/reserva/estado/' . $r['id']) ?>" method="POST" <?= $r['is_grupo'] ? 'class="evento-form"' : '' ?>>
                                        <?= csrf_field() ?>
                                        <?php if ($r['is_grupo']): ?><input type="hidden" name="evento_grupo" value="<?= htmlspecialchars($r['evento_grupo']) ?>"><?php endif; ?>
                                        <select name="estado" class="form-select est-select" onchange="this.form.submit()" title="Cambiar estado">
                                            <?php foreach (['pendiente' => 'Pendiente', 'confirmada' => 'Confirmada', 'finalizada' => 'Completada', 'cancelada' => 'Cancelada'] as $v => $l): ?>
                                                <option value="<?= $v ?>" <?= $r['estado'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <span class="pill <?= $asistCls($r['asistencia_estado']) ?>"><?= $asistLbl($r['asistencia_estado']) ?></span>
                                    <?php if (!empty($r['asistencia_hora'])): ?>
                                        <div class="small text-muted" style="font-size:0.68rem; margin-top:2px;">
                                            <i class="bi bi-clock me-1"></i><?= substr((string)$r['asistencia_hora'], 0, 5) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($r['asistencia_notas'])): ?>
                                        <div class="small text-muted text-truncate" style="max-width:130px; font-size:0.65rem;" title="<?= htmlspecialchars((string)$r['asistencia_notas']) ?>">
                                            <i class="bi bi-chat-text me-1"></i><?= htmlspecialchars((string)$r['asistencia_notas']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="pill <?= $pagoCls($pe) ?>"><?= $pagoLbl($pe) ?></span>
                                    <?php if ($comp): ?>
                                        <br><a href="<?= url('/' . ltrim($comp, '/')) ?>" target="_blank" rel="noopener" class="comp-link"><i class="bi bi-paperclip"></i> Comprobante</a>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space:nowrap;">
                                    <?php if ($waLink !== '#'): ?>
                                        <a href="<?= $waLink ?>" target="_blank" rel="noopener" class="btn-mini wa" title="Contactar por WhatsApp (+51)"><i class="bi bi-whatsapp"></i></a>
                                    <?php endif; ?>
                                    <button type="button" class="btn-mini asist" onclick="abrirModalAsistencia(<?= htmlspecialchars(json_encode([
                                        'id' => $r['id'],
                                        'cliente' => $r['usuario_nombre'],
                                        'telefono' => $r['contacto_telefono'],
                                        'cancha' => $canchaNombreDisplay,
                                        'fecha' => $r['fecha'],
                                        'hora_inicio' => substr((string)$r['hora_inicio'], 0, 5),
                                        'hora_fin' => substr((string)$r['hora_fin'], 0, 5),
                                        'asistencia_estado' => $r['asistencia_estado'] ?? 'sin_registrar',
                                        'asistencia_hora' => $r['asistencia_hora'] ?? '',
                                        'asistencia_notas' => $r['asistencia_notas'] ?? '',
                                        'estado' => $r['estado']
                                    ]), ENT_QUOTES, 'UTF-8') ?>)" title="Registrar asistencia y puntualidad"><i class="bi bi-person-check"></i></button>
                                    <a href="<?= url('/mis-reservas/' . $r['id']) ?>" class="btn-mini" title="Ver detalle"><i class="bi bi-eye"></i></a>
                                    <a href="<?= url('/admin/reserva/editar/' . $r['id']) ?>" class="btn-mini" title="Editar / Reprogramar"><i class="bi bi-pencil"></i></a>
                                    <?php if ($pe === 'en_revision'): ?>
                                        <form action="<?= url('/reserva/pago/' . $r['id']) ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <?php if ($r['is_grupo']): ?><input type="hidden" name="evento_grupo" value="<?= htmlspecialchars($r['evento_grupo']) ?>"><?php endif; ?>
                                            <input type="hidden" name="pago_estado" value="verificado">
                                            <button type="submit" class="btn-mini ok" title="Verificar pago"><i class="bi bi-check-lg"></i></button>
                                        </form>
                                        <form action="<?= url('/reserva/pago/' . $r['id']) ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <?php if ($r['is_grupo']): ?><input type="hidden" name="evento_grupo" value="<?= htmlspecialchars($r['evento_grupo']) ?>"><?php endif; ?>
                                            <input type="hidden" name="pago_estado" value="rechazado">
                                            <button type="submit" class="btn-mini bad" title="Rechazar pago"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($saldo > 0 && $r['estado'] !== 'cancelada'): ?>
                                        <a href="<?= url('/admin/pagos?tab=pendientes&q=' . $r['id']) ?>" class="btn-mini ok" title="Cobrar saldo pendiente en Caja"><i class="bi bi-cash"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

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
