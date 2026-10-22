<?php
declare(strict_types=1);

/** @var string $tab */
/** @var string $busqueda */
/** @var string $filtroMetodo */
/** @var array<int, array<string, mixed>> $items */
/** @var array<int, array<string, mixed>> $historialTransacciones */
/** @var array<string, mixed> $estadisticas */
/** @var array<string, mixed> $cajaHoy */
/** @var int $conteoRevision */
/** @var int $conteoPendientes */
/** @var int $conteoPagadas */
/** @var string $ver */
/** @var float $montoPendiente */

$pagoLbl = static fn(string $p): string => match ($p) {
    'verificado' => 'Pagado', 'en_revision' => 'Por verificar',
    'rechazado' => 'Rechazado', default => 'Pendiente',
};
$pagoCls = static fn(string $p): string => match ($p) {
    'verificado' => 'ok', 'en_revision' => 'warn',
    'rechazado' => 'bad', default => 'warn',
};
$metodoLbl = static fn(string $m): string => match ($m) {
    'niubiz' => 'Niubiz POS', 'efectivo' => 'Efectivo', 'yape' => 'Yape', default => ucfirst($m ?: '—'),
};
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<style>
    .f-bar { display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-end; }
    .f-bar .fld { display: flex; flex-direction: column; gap: 4px; }
    .f-bar label { font-size: .68rem; font-weight: 700; color: #7c8aa0; text-transform: uppercase; letter-spacing: .4px; }
    .f-bar .form-control, .f-bar .form-select { font-size: .82rem; border-radius: 9px; }
    .btn-mini { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #101c33; font-size: .85rem; margin-right: 4px; text-decoration: none; }
    .btn-mini:hover { border-color: #1a7a3a; color: #1a7a3a; }
    .btn-mini.ok { color: #166534; border-color: #bfe6cc; background: #eef7f0; }
    .btn-mini.ok:hover { background: #1a7a3a; color: #fff; }
    .btn-mini.bad { color: #991b1b; border-color: #f3c2c2; background: #fdf0f0; }
    .btn-mini.bad:hover { background: #dc2626; color: #fff; }
    .btn-cobrar { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; background: #1a7a3a; color: #fff; font-size: .75rem; font-weight: 700; border: none; text-decoration: none; cursor: pointer; transition: all .15s; }
    .btn-cobrar:hover { background: #135c2b; color: #fff; box-shadow: 0 2px 6px rgba(26,122,58,.25); }
    .comp-link { display: inline-flex; align-items: center; gap: 4px; font-size: .72rem; font-weight: 700; color: #1d4ed8; text-decoration: none; padding: 3px 6px; border-radius: 6px; background: #eff6ff; }
    .comp-link:hover { background: #dbeafe; color: #1e40af; }
    .nav-tabs-pagos { border-bottom: 2px solid #e2e8f0; margin-bottom: 20px; display: flex; gap: 4px; flex-wrap: wrap; }
    .nav-tabs-pagos .tab-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 10px 10px 0 0; font-size: .84rem; font-weight: 700; color: #64748b; text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -2px; }
    .nav-tabs-pagos .tab-btn:hover { color: #101c33; background: #f8fafc; }
    .nav-tabs-pagos .tab-btn.active { color: #1a7a3a; border-bottom-color: #1a7a3a; background: #fff; }
    .btn-accion { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 38px; padding: 0 14px; border-radius: 9px; font-size: .85rem; font-weight: 700; text-decoration: none; border: 1.5px solid transparent; cursor: pointer; }
    .btn-accion.principal { background: #1a7a3a; color: #fff; }
    .btn-accion.principal:hover { background: #135c2b; color: #fff; }
    .btn-accion.peligro { background: #fff; color: #b91c1c; border-color: #f3c2c2; }
    .btn-accion.peligro:hover { background: #dc2626; color: #fff; border-color: #dc2626; }
    .btn-accion.suave { background: #fff; color: #101c33; border-color: #cbd5e1; }
    .btn-accion.suave:hover { border-color: #1a7a3a; color: #1a7a3a; }
    .btn-cobrar.btn-accion { padding: 0 14px; font-size: .85rem; }
    .fila-revision { box-shadow: inset 4px 0 0 #f5b301; }
    .acc-nota { font-size: .72rem; color: #6b7a90; margin-top: 6px; max-width: 260px; line-height: 1.35; }
    .cobro-resumen { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .cobro-resumen > div { background: #f4f7fb; border-radius: 10px; padding: 10px; text-align: center; }
    .cobro-resumen small { display: block; color: #64748b; font-size: .75rem; }
    .cobro-resumen strong { font-size: 1.15rem; }
    .cobro-metodos { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .cobro-metodo { position: relative; margin: 0; cursor: pointer; }
    .cobro-metodo input { position: absolute; opacity: 0; }
    .cobro-metodo span { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 2px; padding: 10px 6px; border: 2px solid #e2e8f0; border-radius: 12px; font-weight: 700; font-size: .88rem; color: #101c33; height: 100%; }
    .cobro-metodo span i { font-size: 1.3rem; color: #1a7a3a; }
    .cobro-metodo span small { font-weight: 500; font-size: .68rem; color: #64748b; }
    .cobro-metodo input:checked + span { border-color: #1a7a3a; background: #eef7f0; }
    .cobro-metodo input:focus-visible + span { outline: 3px solid #86efac; }
    @media (max-width: 575px) { .cobro-metodos { grid-template-columns: 1fr; } .cobro-metodo span { flex-direction: row; justify-content: flex-start; gap: 10px; text-align: left; } }
    .badge-count { font-size: .65rem; padding: 2px 7px; border-radius: 12px; font-weight: 800; }
</style>

<?php
// Duración de una fila (para un evento de varias canchas es la del primer horario)
$duracionFila = static function (array $r): string {
    $min = (int)round((strtotime('2000-01-01 ' . $r['hora_fin']) - strtotime('2000-01-01 ' . $r['hora_inicio'])) / 60);
    if ($min <= 0) { $min += 1440; }
    return formatoDuracion($min);
};
$mF = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$fechaCorta = static function (string $f) use ($mF): string {
    $ts = strtotime($f);
    return date('d', $ts) . ' ' . $mF[(int)date('n', $ts)] . ' ' . date('Y', $ts);
};
$titulosTab = [
    'revision'   => ['Por verificar', 'Reservas con comprobante o pago en revisión. Aquí se verifica o se rechaza.'],
    'pendientes' => ['Cobrar saldos', 'Reservas confirmadas (adelanto verificado) que todavía tienen saldo por cobrar.'],
    'historial'  => ['Historial de cobros', 'Cobros registrados y reservas pagadas al 100%.'],
];
?>
<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
    <div>
        <h1 class="adm-h1"><?= htmlspecialchars($titulosTab[$tab][0]) ?></h1>
        <p class="adm-sub"><?= htmlspecialchars($titulosTab[$tab][1]) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/reserva/crear') ?>" class="btn btn-sm fw-bold" style="background:#1a7a3a;color:#fff;border-radius:9px;padding:9px 16px;">
            <i class="bi bi-plus-lg me-1"></i> Nueva reserva presencial
        </a>
    </div>
</div>

<!-- Tarjetas (mismos criterios que las pestañas) -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="kpi">
            <span class="kpi-ico y"><i class="bi bi-hourglass-split"></i></span>
            <div>
                <small class="lbl">Comprobantes por verificar</small>
                <div class="val"><?= (int)$conteoRevision ?></div>
            </div>
            <?php if ($conteoRevision > 0 && $tab !== 'revision'): ?>
                <div class="foot"><a href="<?= url('/admin/pagos?tab=revision') ?>" class="text-danger fw-bold">Revisar →</a></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi">
            <span class="kpi-ico b"><i class="bi bi-cash-stack"></i></span>
            <div>
                <small class="lbl">Saldos por cobrar (reservas confirmadas)</small>
                <div class="val"><?= (int)$conteoPendientes ?> <small style="font-size:.8rem;color:#64748b;font-weight:600;">· S/ <?= number_format((float)$montoPendiente, 2) ?></small></div>
            </div>
            <?php if ($tab !== 'pendientes'): ?>
                <div class="foot"><a href="<?= url('/admin/pagos?tab=pendientes') ?>">Cobrar →</a></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi">
            <span class="kpi-ico g" style="background:#059669;"><i class="bi bi-wallet2"></i></span>
            <div>
                <small class="lbl">Ingresos reales hoy (Caja)</small>
                <div class="val" style="font-size:1.45rem;">S/ <?= number_format((float)$cajaHoy['total_ingresos'], 2) ?></div>
            </div>
            <div class="foot up"><a href="<?= url('/admin/caja') ?>">Caja del día →</a></div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="panel-2 mb-4">
    <form action="<?= url('/admin/pagos') ?>" method="GET" class="f-bar mb-3">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <?php if ($ver !== ''): ?><input type="hidden" name="ver" value="<?= htmlspecialchars($ver) ?>"><?php endif; ?>
        <div class="fld" style="flex:1;min-width:200px;">
            <label for="fq">Buscar</label>
            <input type="text" class="form-control" id="fq" name="q" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Código #, cliente, cancha...">
        </div>
        <div class="fld">
            <label for="fmetodo">Método de pago</label>
            <select class="form-select" id="fmetodo" name="metodo">
                <option value="">Todos los métodos</option>
                <option value="yape" <?= $filtroMetodo === 'yape' ? 'selected' : '' ?>>Yape</option>
                <option value="efectivo" <?= $filtroMetodo === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                <option value="niubiz" <?= $filtroMetodo === 'niubiz' ? 'selected' : '' ?>>Niubiz (POS)</option>
            </select>
        </div>
        <button type="submit" class="btn btn-sm fw-bold" style="background:#101c33;color:#fff;border-radius:9px;padding:9px 18px;">
            <i class="bi bi-funnel me-1"></i> Filtrar
        </button>
        <a href="<?= url('/admin/pagos?tab=' . $tab . ($ver !== '' ? '&ver=' . $ver : '')) ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:9px;padding:9px 16px;">Limpiar</a>
    </form>

    <?php if ($tab === 'historial'): ?>
        <!-- Filtro del historial: cobros registrados | reservas pagadas al 100% -->
        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="<?= url('/admin/pagos?tab=historial') ?>" class="btn-accion <?= $ver === '' ? 'principal' : 'suave' ?>"><i class="bi bi-receipt me-1"></i> Cobros registrados</a>
            <a href="<?= url('/admin/pagos?tab=historial&ver=pagadas') ?>" class="btn-accion <?= $ver === 'pagadas' ? 'principal' : 'suave' ?>"><i class="bi bi-check-circle me-1"></i> Pagadas al 100% (<?= (int)$conteoPagadas ?>)</a>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'historial' && $ver === ''): ?>
        <!-- Pestaña Historial de Transacciones -->
        <div class="d-flex align-items-center justify-content-between my-2">
            <h3 style="font-size:1.02rem; font-weight:800;">Historial de Cobros y Movimientos Recientes (<?= count($historialTransacciones) ?>)</h3>
        </div>
        <div class="table-responsive">
            <table class="tbl">
                <thead>
                    <tr>
                        <th># Cobro</th>
                        <th>Fecha y hora</th>
                        <th>Reserva #</th>
                        <th>Cliente / Cancha</th>
                        <th>Tipo</th>
                        <th>Método</th>
                        <th>Monto cobrado</th>
                        <th>Registrado por</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historialTransacciones)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No se encontraron movimientos con los filtros indicados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($historialTransacciones as $p): ?>
                            <tr>
                                <td class="fw-bold">#<?= $p['id'] ?></td>
                                <td class="fh">
                                    <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                                    <small><?= date('H:i:s', strtotime($p['created_at'])) ?></small>
                                </td>
                                <td>
                                    <a href="<?= url('/mis-reservas/' . $p['reserva_id']) ?>" class="fw-bold text-decoration-none" style="color:#1a7a3a;">
                                        #<?= $p['reserva_id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars((string)($p['cliente_nombre'] ?? '—')) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars((string)($p['cancha_nombre'] ?? '—')) ?></small>
                                </td>
                                <td>
                                    <?php if ($p['tipo'] === 'adelanto'): ?>
                                        <span class="badge" style="background:#e0f2fe;color:#0369a1;">Adelanto</span>
                                    <?php elseif ($p['tipo'] === 'saldo'): ?>
                                        <span class="badge" style="background:#fef3c7;color:#92400e;">Saldo restante</span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#dcfce7;color:#15803d;">Pago completo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p['metodo_pago'] === 'yape'): ?>
                                        <span class="badge" style="background:#70248a;color:#fff;">Yape</span>
                                    <?php elseif ($p['metodo_pago'] === 'niubiz'): ?>
                                        <span class="badge" style="background:#0284c7;color:#fff;">Niubiz POS</span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#16a34a;color:#fff;">Efectivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong class="text-success" style="font-size:.92rem;">+ S/ <?= number_format((float)$p['monto'], 2) ?></strong>
                                </td>
                                <td>
                                    <small class="text-muted"><i class="bi bi-person-check me-1"></i><?= htmlspecialchars((string)($p['admin_nombre'] ?? 'Admin')) ?></small>
                                </td>
                                <td>
                                    <small class="text-muted"><?= htmlspecialchars((string)($p['notas'] ?? '—')) ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($tab === 'historial'): ?>
        <!-- Reservas pagadas al 100% (filtro del historial) -->
        <div class="table-responsive">
            <table class="tbl tbl-cards">
                <thead><tr><th>#</th><th>Cliente</th><th>Cancha y horario</th><th>Total pagado</th><th class="text-end">Acciones</th></tr></thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No hay reservas pagadas al 100%.</td></tr>
                    <?php else: foreach ($items as $r): ?>
                        <tr>
                            <td data-label="Reserva" class="fw-bold">#<?= (int)$r['id'] ?><?= $r['is_grupo'] ? '<br><span class="badge bg-secondary" style="font-size:0.6rem;">Evento</span>' : '' ?></td>
                            <td data-label="Cliente">
                                <div class="fw-semibold"><?= htmlspecialchars((string)$r['cliente_nombre']) ?></div>
                                <?php if (!empty($r['contacto_telefono'])): ?><small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars((string)$r['contacto_telefono']) ?></small><?php endif; ?>
                            </td>
                            <td data-label="Cancha y horario">
                                <div class="fw-semibold"><?= $r['is_grupo'] ? count($r['canchas']) . ' canchas' : htmlspecialchars((string)($r['canchas'][0]['nombre'] ?? 'Cancha')) ?></div>
                                <small class="text-muted"><?= $fechaCorta((string)$r['fecha']) ?> · <?= date('H:i', strtotime($r['hora_inicio'])) ?> - <?= date('H:i', strtotime($r['hora_fin'])) ?> (<?= $duracionFila($r) ?>)</small>
                            </td>
                            <td data-label="Total pagado"><strong class="text-success">S/ <?= number_format((float)$r['total_pagado'], 2) ?></strong></td>
                            <td class="td-acc text-end" data-label="Acciones"><a href="<?= url('/mis-reservas/' . $r['id']) ?>" class="btn-accion suave"><i class="bi bi-eye"></i> Ver detalle</a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($tab === 'revision'): ?>
        <!-- POR VERIFICAR: solo reservas con comprobante o pago en revisión -->
        <div class="alert alert-warning py-2 px-3 small" style="border-radius:10px;"><i class="bi bi-info-circle me-1"></i> Al verificar, la reserva queda confirmada. Al rechazar, se cancela y el horario queda libre.</div>
        <div class="table-responsive">
            <table class="tbl tbl-cards">
                <thead><tr><th>Cliente</th><th>Cancha y horario</th><th>Monto del comprobante</th><th>Comprobante</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No hay comprobantes por verificar.</td></tr>
                    <?php else: foreach ($items as $r): ?>
                        <?php $comp = (string)($r['comprobante_ruta'] ?? ''); $montoRev = (float)($r['monto_revision'] ?? 0); ?>
                        <tr class="fila-revision">
                            <td data-label="Cliente">
                                <div class="fw-semibold"><?= htmlspecialchars((string)$r['cliente_nombre']) ?> <small class="text-muted">#<?= (int)$r['id'] ?></small></div>
                                <?php if (!empty($r['contacto_telefono'])): ?><div style="font-size:.78rem;color:#166534;"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars((string)$r['contacto_telefono']) ?></div><?php endif; ?>
                            </td>
                            <td data-label="Cancha y horario">
                                <?php if ($r['is_grupo']): ?>
                                    <div class="fw-semibold"><?= count($r['canchas']) ?> canchas (evento)</div>
                                    <?php foreach ($r['canchas'] as $c): ?><div style="font-size:.75rem;color:#5b6b82;">• <?= htmlspecialchars($c['nombre']) ?></div><?php endforeach; ?>
                                <?php else: ?>
                                    <div class="fw-semibold"><?= htmlspecialchars((string)($r['canchas'][0]['nombre'] ?? 'Cancha')) ?></div>
                                <?php endif; ?>
                                <small class="text-muted"><?= $fechaCorta((string)$r['fecha']) ?> · <?= date('H:i', strtotime($r['hora_inicio'])) ?> - <?= date('H:i', strtotime($r['hora_fin'])) ?> (<?= $duracionFila($r) ?>)</small>
                            </td>
                            <td data-label="Monto del comprobante"><strong>S/ <?= number_format($montoRev > 0 ? $montoRev : (float)$r['adelanto_monto'], 2) ?></strong></td>
                            <td data-label="Comprobante">
                                <?php if ($comp): ?>
                                    <button type="button" class="comp-link border-0" onclick="verComprobanteModal('<?= url('/' . ltrim($comp, '/')) ?>', 'Reserva #<?= (int)$r['id'] ?>')"><i class="bi bi-image"></i> Ver comprobante</button>
                                <?php else: ?>
                                    <span class="text-muted small">Sin archivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="td-acc" data-label="Acciones">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <form action="<?= url('/admin/pago/verificar/' . $r['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Verificar el comprobante de la reserva #<?= (int)$r['id'] ?>?\n\nAl verificar, la reserva queda confirmada.');">
                                        <?= csrf_field() ?>
                                        <?php if ($r['is_grupo']): ?><input type="hidden" name="evento_grupo" value="<?= htmlspecialchars((string)$r['evento_grupo']) ?>"><?php endif; ?>
                                        <button type="submit" class="btn-accion principal" title="Confirmar el comprobante: la reserva queda confirmada"><i class="bi bi-check-lg"></i> Verificar</button>
                                    </form>
                                    <button type="button" class="btn-accion peligro" data-bs-toggle="modal" data-bs-target="#modalRechazar"
                                            data-id="<?= (int)$r['id'] ?>" data-grupo="<?= htmlspecialchars((string)($r['evento_grupo'] ?? '')) ?>" data-cliente="<?= htmlspecialchars((string)$r['cliente_nombre']) ?>"
                                            title="Rechazar: la reserva se cancela y el horario queda libre"><i class="bi bi-x-lg"></i> Rechazar</button>
                                    <a href="<?= url('/mis-reservas/' . $r['id']) ?>" class="btn-accion suave" title="Ver detalle de la reserva"><i class="bi bi-eye"></i> Ver</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <!-- COBRAR SALDOS: solo reservas confirmadas con saldo mayor que 0 -->
        <div class="table-responsive">
            <table class="tbl tbl-cards">
                <thead><tr><th>Cliente</th><th>Cancha y horario</th><th>Total</th><th>Pagado</th><th>Saldo</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No hay saldos por cobrar.</td></tr>
                    <?php else: foreach ($items as $r): ?>
                        <tr>
                            <td data-label="Cliente">
                                <div class="fw-semibold"><?= htmlspecialchars((string)$r['cliente_nombre']) ?> <small class="text-muted">#<?= (int)$r['id'] ?></small></div>
                                <?php if (!empty($r['contacto_telefono'])): ?><div style="font-size:.78rem;color:#166534;"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars((string)$r['contacto_telefono']) ?></div><?php endif; ?>
                            </td>
                            <td data-label="Cancha y horario">
                                <div class="fw-semibold"><?= $r['is_grupo'] ? count($r['canchas']) . ' canchas (evento)' : htmlspecialchars((string)($r['canchas'][0]['nombre'] ?? 'Cancha')) ?></div>
                                <small class="text-muted"><?= $fechaCorta((string)$r['fecha']) ?> · <?= date('H:i', strtotime($r['hora_inicio'])) ?> - <?= date('H:i', strtotime($r['hora_fin'])) ?> (<?= $duracionFila($r) ?>)</small>
                            </td>
                            <td data-label="Total">S/ <?= number_format((float)$r['total_pago'], 2) ?></td>
                            <td data-label="Pagado" class="text-success">S/ <?= number_format((float)$r['total_pagado'], 2) ?></td>
                            <td data-label="Saldo"><strong class="text-danger">S/ <?= number_format((float)$r['saldo_pendiente'], 2) ?></strong></td>
                            <td class="td-acc" data-label="Acciones">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <button type="button" class="btn-cobrar btn-accion principal"
                                            data-bs-toggle="modal" data-bs-target="#modalCobroSaldo"
                                            data-id="<?= (int)$r['id'] ?>"
                                            data-grupo="<?= htmlspecialchars((string)($r['evento_grupo'] ?? '')) ?>"
                                            data-cliente="<?= htmlspecialchars((string)$r['cliente_nombre']) ?>"
                                            data-total="<?= number_format((float)$r['total_pago'], 2, '.', '') ?>"
                                            data-pagado="<?= number_format((float)$r['total_pagado'], 2, '.', '') ?>"
                                            data-saldo="<?= number_format((float)$r['saldo_pendiente'], 2, '.', '') ?>">
                                        <i class="bi bi-cash"></i> Cobrar saldo
                                    </button>
                                    <a href="<?= url('/mis-reservas/' . $r['id']) ?>" class="btn-accion suave" title="Ver detalle de la reserva"><i class="bi bi-eye"></i> Ver</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL PARA RECHAZAR UN COMPROBANTE (pide el motivo) -->
<div class="modal fade" id="modalRechazar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="" method="POST" id="formRechazar" class="modal-content border-0 shadow-lg" style="border-radius:14px;">
            <?= csrf_field() ?>
            <input type="hidden" name="evento_grupo" id="rechazarGrupo" value="">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Rechazar comprobante</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2" id="rechazarTexto"></p>
                <div class="alert alert-warning py-2 small">La reserva se cancelará y el horario quedará libre.</div>
                <label for="rechazarMotivo" class="form-label fw-bold small">Motivo del rechazo <span class="text-danger">*</span></label>
                <textarea class="form-control" id="rechazarMotivo" name="motivo" rows="2" required minlength="3" maxlength="300" placeholder="Ej: El monto no coincide con el adelanto"></textarea>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">No rechazar</button>
                <button type="submit" class="btn btn-danger fw-bold" id="btnConfirmarRechazo">Sí, rechazar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL PARA COBRAR SALDO PENDIENTE -->
<div class="modal fade" id="modalCobroSaldo" tabindex="-1" aria-labelledby="modalCobroLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px; overflow:hidden;">
            <div class="modal-header text-white" style="background:#1a7a3a;">
                <h5 class="modal-title fs-5 fw-bold" id="modalCobroLabel"><i class="bi bi-cash-coin me-2"></i> Cobrar saldo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="<?= url('/admin/pago/registrar') ?>" method="POST" id="formCobroSaldo">
                <?= csrf_field() ?>
                <input type="hidden" name="reserva_id" id="cobroReservaId" value="">
                <input type="hidden" name="evento_grupo" id="cobroEventoGrupo" value="">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <div class="text-muted small">Cliente</div>
                        <strong id="cobroClienteNombre" class="fs-6 text-dark">—</strong>
                    </div>

                    <div class="cobro-resumen mb-3">
                        <div><small>Total</small><strong id="cobroTotalTexto">S/ 0.00</strong></div>
                        <div><small>Pagado</small><strong id="cobroPagadoTexto" class="text-success">S/ 0.00</strong></div>
                        <div><small>Saldo</small><strong id="cobroSaldoTexto" class="text-danger">S/ 0.00</strong></div>
                    </div>

                    <!-- Paso 1: datos del cobro -->
                    <div id="cobroPaso1">
                        <div class="mb-3">
                            <label for="montoCobrar" class="form-label fw-bold small text-dark">Monto a cobrar (S/) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white fw-bold">S/</span>
                                <input type="number" step="0.01" min="0.01" class="form-control fw-bold text-success" id="montoCobrar" name="monto" value="" required>
                                <button type="button" class="btn btn-outline-secondary" id="btnCobrarTodo" title="Cobrar el saldo pendiente completo">Todo el saldo</button>
                            </div>
                            <div class="form-text" id="montoAyuda">Ya viene el saldo completo; puedes cobrar menos si el cliente paga una parte.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark">¿Cómo pagó? <span class="text-danger">*</span></label>
                            <div class="cobro-metodos">
                                <label class="cobro-metodo"><input type="radio" name="metodo_pago" value="efectivo" checked><span><i class="bi bi-cash-stack"></i> Efectivo<small>En mostrador</small></span></label>
                                <label class="cobro-metodo"><input type="radio" name="metodo_pago" value="yape"><span><i class="bi bi-qr-code"></i> Yape<small>Confirma en tu celular</small></span></label>
                                <label class="cobro-metodo"><input type="radio" name="metodo_pago" value="niubiz"><span><i class="bi bi-credit-card"></i> Niubiz<small>Tarjeta (POS)</small></span></label>
                            </div>
                        </div>

                        <div class="mb-1">
                            <label for="cobroNotas" class="form-label fw-semibold small text-muted">Nota (opcional)</label>
                            <input type="text" class="form-control" id="cobroNotas" name="notas" placeholder="Ej: Pagó al ingresar a la cancha">
                        </div>
                    </div>

                    <!-- Paso 2: confirmación -->
                    <div id="cobroPaso2" class="d-none">
                        <div class="alert alert-success mb-0" style="font-size:1rem;">
                            <i class="bi bi-question-circle me-1"></i>
                            Vas a registrar <strong id="confMonto">S/ 0.00</strong> en <strong id="confMetodo">Efectivo</strong>.
                            <div class="mt-1 small">Saldo restante después del cobro: <strong id="confRestante">S/ 0.00</strong></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" id="btnCancelarCobro">Cancelar</button>
                    <button type="button" class="btn btn-light d-none" id="btnVolverCobro"><i class="bi bi-arrow-left me-1"></i> Volver</button>
                    <button type="button" class="btn fw-bold px-4" id="btnRevisarCobro" style="background:#1a7a3a;color:#fff;">Continuar</button>
                    <button type="submit" class="btn fw-bold px-4 d-none" id="btnSubmitCobro" style="background:#1a7a3a;color:#fff;">
                        <i class="bi bi-check-circle me-1"></i> Sí, registrar cobro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL PREVISUALIZAR COMPROBANTE -->
<div class="modal fade" id="modalVerComprobante" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold" id="tituloModalComp">Comprobante de Pago</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center p-3" style="background:#0b1e33;">
                <div id="wrapCompPreview">
                    <img src="" id="imgCompPreview" alt="Comprobante" class="img-fluid rounded shadow" style="max-height:550px; object-fit:contain;">
                </div>
            </div>
            <div class="modal-footer bg-dark border-0">
                <a href="" id="linkDescargarComp" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Abrir en pestaña nueva
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalCobro = document.getElementById('modalCobroSaldo');
    var inputMonto = document.getElementById('montoCobrar');
    var btnTodo = document.getElementById('btnCobrarTodo');
    var formCobro = document.getElementById('formCobroSaldo');
    var btnSubmit = document.getElementById('btnSubmitCobro');
    var btnRevisar = document.getElementById('btnRevisarCobro');
    var btnVolver = document.getElementById('btnVolverCobro');
    var btnCancelar = document.getElementById('btnCancelarCobro');
    var paso1 = document.getElementById('cobroPaso1');
    var paso2 = document.getElementById('cobroPaso2');
    var saldoMaximo = 0;
    var enviado = false;
    var nombresMetodo = { efectivo: 'Efectivo', yape: 'Yape', niubiz: 'Niubiz (POS)' };

    function mostrarPaso(n) {
        paso1.classList.toggle('d-none', n !== 1);
        paso2.classList.toggle('d-none', n !== 2);
        btnRevisar.classList.toggle('d-none', n !== 1);
        btnCancelar.classList.toggle('d-none', n !== 1);
        btnVolver.classList.toggle('d-none', n !== 2);
        btnSubmit.classList.toggle('d-none', n !== 2);
    }

    if (modalCobro) {
        modalCobro.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            if (!button) return;

            var id = button.getAttribute('data-id');
            var grupo = button.getAttribute('data-grupo');
            var cliente = button.getAttribute('data-cliente');
            var total = parseFloat(button.getAttribute('data-total') || '0');
            var pagado = parseFloat(button.getAttribute('data-pagado') || '0');
            var saldo = parseFloat(button.getAttribute('data-saldo') || '0');
            saldoMaximo = saldo;
            enviado = false;

            document.getElementById('cobroReservaId').value = id;
            document.getElementById('cobroEventoGrupo').value = grupo || '';
            document.getElementById('cobroClienteNombre').textContent = cliente + (grupo ? ' (Evento grupal)' : ' (Reserva #' + id + ')');
            document.getElementById('cobroTotalTexto').textContent = 'S/ ' + total.toFixed(2);
            document.getElementById('cobroPagadoTexto').textContent = 'S/ ' + pagado.toFixed(2);
            document.getElementById('cobroSaldoTexto').textContent = 'S/ ' + saldo.toFixed(2);

            inputMonto.value = saldo.toFixed(2);   // monto sugerido = saldo
            inputMonto.max = saldo.toFixed(2);
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="bi bi-check-circle me-1"></i> Sí, registrar cobro';
            mostrarPaso(1);
        });
    }

    if (btnTodo) {
        btnTodo.addEventListener('click', function() {
            if (saldoMaximo > 0) { inputMonto.value = saldoMaximo.toFixed(2); }
        });
    }

    // Paso 1 -> 2: valida y muestra el resumen para confirmar
    if (btnRevisar) {
        btnRevisar.addEventListener('click', function() {
            var m = parseFloat(inputMonto.value || '0');
            if (!(m > 0)) { alert('El monto a cobrar debe ser mayor a S/ 0.00'); return; }
            if (m > (saldoMaximo + 0.001)) {
                alert('El monto (S/ ' + m.toFixed(2) + ') no puede superar el saldo pendiente (S/ ' + saldoMaximo.toFixed(2) + ').');
                return;
            }
            var metodo = formCobro.querySelector('input[name="metodo_pago"]:checked');
            document.getElementById('confMonto').textContent = 'S/ ' + m.toFixed(2);
            document.getElementById('confMetodo').textContent = nombresMetodo[metodo ? metodo.value : 'efectivo'];
            document.getElementById('confRestante').textContent = 'S/ ' + Math.max(0, saldoMaximo - m).toFixed(2);
            mostrarPaso(2);
        });
    }
    if (btnVolver) { btnVolver.addEventListener('click', function() { mostrarPaso(1); }); }

    // Una sola vez: el segundo envío se bloquea (doble clic o Enter repetido)
    if (formCobro) {
        formCobro.addEventListener('submit', function(e) {
            if (enviado || paso2.classList.contains('d-none')) { e.preventDefault(); return; }
            enviado = true;
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Registrando...';
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    var modalRech = document.getElementById('modalRechazar');
    if (!modalRech) return;
    modalRech.addEventListener('show.bs.modal', function(event) {
        var b = event.relatedTarget;
        if (!b) return;
        document.getElementById('formRechazar').action = '<?= url('/admin/pago/rechazar/') ?>' + b.getAttribute('data-id');
        document.getElementById('rechazarGrupo').value = b.getAttribute('data-grupo') || '';
        document.getElementById('rechazarTexto').textContent = 'Reserva #' + b.getAttribute('data-id') + ' · ' + (b.getAttribute('data-cliente') || '');
        document.getElementById('rechazarMotivo').value = '';
        document.getElementById('btnConfirmarRechazo').disabled = false;
    });
    document.getElementById('formRechazar').addEventListener('submit', function() {
        document.getElementById('btnConfirmarRechazo').disabled = true; // un solo envío
    });
});

function verComprobanteModal(url, titulo) {
    document.getElementById('tituloModalComp').textContent = 'Comprobante de Pago — ' + titulo;
    document.getElementById('imgCompPreview').src = url;
    document.getElementById('linkDescargarComp').href = url;
    var modal = new bootstrap.Modal(document.getElementById('modalVerComprobante'));
    modal.show();
}
</script>

<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
