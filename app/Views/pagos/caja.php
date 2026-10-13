<?php
declare(strict_types=1);

/** @var string $fecha */
/** @var array<string, mixed> $resumen */
/** @var array<int, array<string, mixed>> $movimientos */
/** @var array<int, array<string, mixed>> $ventas */
/** @var bool $incluirDemo */

$ventas = $ventas ?? [];
$incluirDemo = $incluirDemo ?? false;

$tsFecha = strtotime($fecha);
$diasLargo = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$mesesLargo = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$fechaTexto = $diasLargo[(int)date('w', $tsFecha)] . ', ' . date('d', $tsFecha) . ' de ' . $mesesLargo[(int)date('n', $tsFecha)] . ' de ' . date('Y', $tsFecha);

$fechaAyer = date('Y-m-d', strtotime($fecha . ' -1 day'));
$fechaManana = date('Y-m-d', strtotime($fecha . ' +1 day'));
$esHoy = ($fecha === date('Y-m-d'));
$demoParam = $incluirDemo ? '&demo=1' : '';
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<style>
    .caja-hero { background: linear-gradient(135deg, #0b1e33 0%, #15325b 100%); color: #fff; border-radius: 14px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 14px rgba(11,30,51,.15); }
    .caja-total { font-size: 2.3rem; font-weight: 800; line-height: 1.1; color: #22c55e; }
    .caja-kpi-sub { font-size: .85rem; color: #94a3b8; margin-top: 4px; }
    .metodo-card { background: #fff; border-radius: 12px; padding: 18px; border: 1px solid #e2e8f0; height: 100%; display: flex; align-items: center; gap: 14px; }
    .metodo-ico { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #fff; flex-shrink: 0; }
    .metodo-ico.efe { background: #16a34a; }
    .metodo-ico.pos { background: #0284c7; }
    .metodo-ico.yap { background: #70248a; }
    .metodo-val { font-size: 1.35rem; font-weight: 800; color: #101c33; line-height: 1.2; }
    .metodo-lbl { font-size: .76rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
    .split-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 18px; height: 100%; }
    .nota-caja { background: #e0f2fe; border-left: 4px solid #0284c7; padding: 12px 16px; border-radius: 0 10px 10px 0; font-size: .82rem; color: #0369a1; }
    .nav-tabs .nav-link { font-weight: 700; font-size: .88rem; color: #64748b; border-radius: 8px 8px 0 0; padding: 10px 18px; }
    .nav-tabs .nav-link.active { color: #101c33; border-color: #e2e8f0 #e2e8f0 #fff; border-bottom: 3px solid #1a7a3a; }
</style>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
    <div>
        <h1 class="adm-h1">Caja Diaria — Arqueo e Ingresos Reales</h1>
        <p class="adm-sub">Separación auditada de ingresos por alquiler de canchas/eventos y ventas de bar/kiosco.</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <a href="<?= url('/admin/ventas/pos') ?>" class="btn btn-sm fw-bold shadow-sm" style="background:#15803d;color:#fff;border-radius:9px;padding:9px 16px;">
            <i class="bi bi-cart-check me-1"></i> Terminal POS
        </a>
        <a href="<?= url('/admin/pagos') ?>" class="btn btn-sm btn-outline-dark fw-bold" style="border-radius:9px;padding:9px 16px;">
            <i class="bi bi-credit-card-2-front me-1"></i> Control de saldos
        </a>
    </div>
</div>

<?php if ($incluirDemo): ?>
    <div class="alert alert-warning border-warning d-flex align-items-center justify-content-between mb-4 shadow-sm" role="alert" style="border-radius:12px;">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-shield-exclamation fs-4 text-warning"></i>
            <div>
                <strong>Modo Demostración Activo:</strong> Se están visualizando movimientos y ventas de prueba/calibración en esta fecha de caja.
            </div>
        </div>
        <a href="<?= url('/admin/caja?fecha=' . urlencode($fecha) . '&demo=0') ?>" class="btn btn-sm btn-dark fw-bold">
            <i class="bi bi-eye-slash me-1"></i> Ocultar datos demo
        </a>
    </div>
<?php endif; ?>

<!-- Selector de Fecha -->
<div class="panel-2 mb-4">
    <form action="<?= url('/admin/caja') ?>" method="GET" class="d-flex align-items-center gap-2 flex-wrap">
        <input type="hidden" name="demo" value="<?= $incluirDemo ? '1' : '0' ?>">
        <label for="fechaCaja" class="fw-bold small text-muted text-uppercase mb-0 me-2" style="font-size:.72rem;">Fecha de caja:</label>

        <a href="<?= url('/admin/caja?fecha=' . $fechaAyer . $demoParam) ?>" class="btn btn-sm btn-outline-secondary" title="Día anterior">
            <i class="bi bi-chevron-left"></i>
        </a>

        <input type="date" class="form-control form-control-sm text-center fw-bold" id="fechaCaja" name="fecha" value="<?= htmlspecialchars($fecha) ?>" style="max-width:160px; font-size:.88rem; border-radius:8px;">

        <button type="submit" class="btn btn-sm btn-dark fw-bold" style="border-radius:8px;">
            Consultar
        </button>

        <a href="<?= url('/admin/caja?fecha=' . $fechaManana . $demoParam) ?>" class="btn btn-sm btn-outline-secondary" title="Día siguiente">
            <i class="bi bi-chevron-right"></i>
        </a>

        <?php if (!$esHoy): ?>
            <a href="<?= url('/admin/caja?fecha=' . date('Y-m-d') . $demoParam) ?>" class="btn btn-sm btn-outline-success fw-bold ms-auto" style="border-radius:8px;">
                <i class="bi bi-calendar-event me-1"></i> Ir a hoy
            </a>
        <?php endif; ?>

        <?php if (!$incluirDemo): ?>
            <a href="<?= url('/admin/caja?fecha=' . urlencode($fecha) . '&demo=1') ?>" class="btn btn-sm btn-outline-warning text-dark fw-semibold ms-auto" style="border-radius:8px;" title="Ver transacciones demo">
                <i class="bi bi-shield-exclamation me-1"></i> Incluir demo
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Banner Hero de la Caja de la Fecha -->
<div class="caja-hero">
    <div class="row align-items-center g-3">
        <div class="col-lg-5">
            <span class="badge mb-2" style="background:rgba(255,255,255,.15);font-size:.75rem;">
                <i class="bi bi-calendar-check me-1"></i> <?= htmlspecialchars($fechaTexto) ?>
                <?= $esHoy ? '· HOY' : '' ?>
            </span>
            <div class="caja-total">S/ <?= number_format((float)($resumen['total_caja'] ?? $resumen['total_ingresos'] ?? 0), 2) ?></div>
            <div class="caja-kpi-sub">Total global recaudado en el establecimiento durante esta fecha</div>
        </div>
        <div class="col-lg-7">
            <div class="row g-2 text-start">
                <div class="col-6">
                    <div class="p-2 rounded" style="background:rgba(255,255,255,0.08);border-left:3px solid #22c55e;">
                        <small class="text-white-50 d-block" style="font-size:.72rem;">ALQUILER DE CANCHAS Y EVENTOS</small>
                        <strong class="fs-5 text-white">S/ <?= number_format((float)($resumen['canchas_total'] ?? 0), 2) ?></strong>
                        <div class="text-white-50" style="font-size:.72rem;"><?= (int)($resumen['canchas_tx'] ?? 0) ?> cobro(s) de reservas</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 rounded" style="background:rgba(255,255,255,0.08);border-left:3px solid #38bdf8;">
                        <small class="text-white-50 d-block" style="font-size:.72rem;">VENTAS BAR, KIOSCO Y CAFETERÍA</small>
                        <strong class="fs-5 text-white">S/ <?= number_format((float)($resumen['productos_total'] ?? 0), 2) ?></strong>
                        <div class="text-white-50" style="font-size:.72rem;"><?= (int)($resumen['productos_tx'] ?? 0) ?> venta(s) mostrador</div>
                    </div>
                </div>
                <div class="col-12 mt-1">
                    <small class="text-white-50" style="font-size:.75rem;">
                        <i class="bi bi-check2-circle me-1"></i> <?= (int)($resumen['total_transacciones'] ?? 0) ?> transacción(es) total(es) auditada(s) sin duplicaciones.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Desglose Global Consolidado por Medio de Pago -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="metodo-card shadow-sm">
            <span class="metodo-ico efe"><i class="bi bi-cash-stack"></i></span>
            <div class="flex-grow-1">
                <div class="metodo-lbl">Efectivo Total</div>
                <div class="metodo-val">S/ <?= number_format((float)($resumen['total_efectivo'] ?? 0), 2) ?></div>
                <div class="text-muted" style="font-size:.72rem;">
                    Canchas: S/ <?= number_format((float)($resumen['canchas_efectivo'] ?? 0), 2) ?> | Bar: S/ <?= number_format((float)($resumen['productos_efectivo'] ?? 0), 2) ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metodo-card shadow-sm">
            <span class="metodo-ico pos"><i class="bi bi-credit-card"></i></span>
            <div class="flex-grow-1">
                <div class="metodo-lbl">Niubiz POS Total</div>
                <div class="metodo-val">S/ <?= number_format((float)($resumen['total_niubiz'] ?? 0), 2) ?></div>
                <div class="text-muted" style="font-size:.72rem;">
                    Canchas: S/ <?= number_format((float)($resumen['canchas_niubiz'] ?? 0), 2) ?> | Bar: S/ <?= number_format((float)($resumen['productos_niubiz'] ?? 0), 2) ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="metodo-card shadow-sm">
            <span class="metodo-ico yap"><i class="bi bi-phone"></i></span>
            <div class="flex-grow-1">
                <div class="metodo-lbl">Yape (Verificados)</div>
                <div class="metodo-val">S/ <?= number_format((float)($resumen['total_yape'] ?? 0), 2) ?></div>
                <div class="text-muted" style="font-size:.72rem;">
                    Adelantos web de reservas deportivas
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Separación Detallada de Flujos: Canchas vs Productos -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="split-card shadow-sm border-start border-4 border-success">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h4 class="h6 fw-bold mb-0 text-success">
                    <i class="bi bi-dribbble me-1"></i> Flujo 1: Canchas y Eventos
                </h4>
                <span class="badge bg-light text-dark border font-monospace">
                    S/ <?= number_format((float)($resumen['canchas_total'] ?? 0), 2) ?>
                </span>
            </div>
            <p class="text-muted small mb-3">Recaudación por alquiler de canchas sintéticas y reservas de eventos.</p>
            <div class="row g-2 small">
                <div class="col-4">
                    <div class="p-2 bg-light rounded text-center">
                        <span class="text-muted d-block" style="font-size:.7rem;">Adelantos Yape</span>
                        <strong class="text-dark">S/ <?= number_format((float)($resumen['total_adelantos'] ?? 0), 2) ?></strong>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 bg-light rounded text-center">
                        <span class="text-muted d-block" style="font-size:.7rem;">Saldos Mostrador</span>
                        <strong class="text-dark">S/ <?= number_format((float)($resumen['total_saldos'] ?? 0), 2) ?></strong>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 bg-light rounded text-center">
                        <span class="text-muted d-block" style="font-size:.7rem;">Pagos Completos</span>
                        <strong class="text-dark">S/ <?= number_format((float)($resumen['total_completos'] ?? 0), 2) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="split-card shadow-sm border-start border-4 border-info">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h4 class="h6 fw-bold mb-0 text-info">
                    <i class="bi bi-cup-straw me-1"></i> Flujo 2: Bar, Kiosco y Cafetería
                </h4>
                <span class="badge bg-light text-dark border font-monospace">
                    S/ <?= number_format((float)($resumen['productos_total'] ?? 0), 2) ?>
                </span>
            </div>
            <p class="text-muted small mb-3">Ventas directas de bebidas, snacks e hidratantes en el terminal POS.</p>
            <div class="row g-2 small">
                <div class="col-6">
                    <div class="p-2 bg-light rounded text-center">
                        <span class="text-muted d-block" style="font-size:.7rem;">Efectivo en Mostrador</span>
                        <strong class="text-dark">S/ <?= number_format((float)($resumen['productos_efectivo'] ?? 0), 2) ?></strong>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 bg-light rounded text-center">
                        <span class="text-muted d-block" style="font-size:.7rem;">Niubiz POS (Tarjetas)</span>
                        <strong class="text-dark">S/ <?= number_format((float)($resumen['productos_niubiz'] ?? 0), 2) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="nota-caja mb-4">
    <i class="bi bi-info-circle-fill me-1"></i>
    <strong>Regla de Integridad Financiera:</strong> Los ingresos por productos de mostrador y los pagos de reservas se procesan en tablas independientes con bloqueos de concurrencia y validación de cobro. No existe doble contabilidad ni alteración del historial financiero previo.
</div>

<!-- Pestañas de Movimientos Auditados -->
<div class="panel-2 mb-4">
    <ul class="nav nav-tabs mb-3" id="cajaTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="canchas-tab" data-bs-toggle="tab" data-bs-target="#canchas-panel" type="button" role="tab">
                <i class="bi bi-dribbble me-1"></i> Alquileres de Canchas (<?= count($movimientos) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="productos-tab" data-bs-toggle="tab" data-bs-target="#productos-panel" type="button" role="tab">
                <i class="bi bi-cart-check me-1"></i> Ventas de Bar / Kiosco (<?= count($ventas) ?>)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="cajaTabsContent">
        <!-- Panel 1: Canchas -->
        <div class="tab-pane fade show active" id="canchas-panel" role="tabpanel">
            <div class="table-responsive">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th># Transacción</th>
                            <th>Reserva</th>
                            <th>Cliente / Cancha</th>
                            <th>Tipo de cobro</th>
                            <th>Medio de pago</th>
                            <th>Monto cobrado</th>
                            <th>Responsable</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimientos)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    No se registraron cobros ni adelantos de reservas en la fecha <strong><?= htmlspecialchars($fecha) ?></strong>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($movimientos as $m): ?>
                                <tr>
                                    <td class="fw-semibold text-dark">
                                        <?= date('H:i:s', strtotime((string)$m['created_at'])) ?>
                                    </td>
                                    <td class="fw-bold">
                                        #<?= $m['id'] ?>
                                        <?php if (!empty($m['es_demo'])): ?>
                                            <span class="badge bg-warning text-dark ms-1">DEMO</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= url('/mis-reservas/' . $m['reserva_id']) ?>" class="fw-bold text-decoration-none" style="color:#1a7a3a;">
                                            #<?= $m['reserva_id'] ?>
                                        </a>
                                        <?php if (!empty($m['evento_grupo'])): ?>
                                            <span class="badge bg-secondary ms-1" style="font-size:.58rem;">Evento</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars((string)($m['cliente_nombre'] ?? '—')) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars((string)($m['cancha_nombre'] ?? '—')) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($m['tipo'] === 'adelanto'): ?>
                                            <span class="badge" style="background:#e0f2fe;color:#0369a1;">Adelanto inicial</span>
                                        <?php elseif ($m['tipo'] === 'saldo'): ?>
                                            <span class="badge" style="background:#fef3c7;color:#92400e;">Saldo en cancha</span>
                                        <?php else: ?>
                                            <span class="badge" style="background:#dcfce7;color:#15803d;">Pago completo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($m['metodo_pago'] === 'yape'): ?>
                                            <span class="badge" style="background:#70248a;color:#fff;">Yape</span>
                                        <?php elseif ($m['metodo_pago'] === 'niubiz'): ?>
                                            <span class="badge" style="background:#0284c7;color:#fff;">Niubiz POS</span>
                                        <?php else: ?>
                                            <span class="badge" style="background:#16a34a;color:#fff;">Efectivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-success" style="font-size:.95rem;">
                                            + S/ <?= number_format((float)$m['monto'], 2) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="bi bi-person-check me-1"></i><?= htmlspecialchars((string)($m['admin_nombre'] ?? 'Administrador')) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars((string)($m['notas'] ?? '—')) ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Panel 2: Productos -->
        <div class="tab-pane fade" id="productos-panel" role="tabpanel">
            <div class="table-responsive">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Operación</th>
                            <th>Cliente</th>
                            <th class="text-center">Artículos</th>
                            <th>Medio de pago</th>
                            <th>Monto cobrado</th>
                            <th>Atendido por</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ventas)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="bi bi-cart-x fs-2 d-block mb-2 text-secondary"></i>
                                    No se registraron ventas de bar o kiosco en la fecha <strong><?= htmlspecialchars($fecha) ?></strong>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ventas as $v): ?>
                                <tr>
                                    <td class="fw-semibold text-dark">
                                        <?= date('H:i:s', strtotime((string)$v['created_at'])) ?>
                                    </td>
                                    <td>
                                        <a href="<?= url('/admin/ventas/detalle/' . (int)$v['id']) ?>" class="fw-bold text-decoration-none font-monospace" style="color:#1a7a3a;">
                                            <?= htmlspecialchars((string)$v['codigo_operacion']) ?>
                                        </a>
                                        <?php if (!empty($v['es_demo'])): ?>
                                            <span class="badge bg-warning text-dark ms-1">DEMO</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars((string)($v['cliente_nombre'] ?? 'Cliente Mostrador')) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">
                                            <?= (int)($v['total_unidades'] ?? $v['total_items'] ?? 1) ?> unid.
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($v['metodo_pago'] === 'niubiz'): ?>
                                            <span class="badge" style="background:#0284c7;color:#fff;"><i class="bi bi-credit-card me-1"></i>Niubiz POS</span>
                                        <?php elseif ($v['metodo_pago'] === 'yape'): ?>
                                            <span class="badge" style="background:#70248a;color:#fff;"><i class="bi bi-qr-code me-1"></i>Yape</span>
                                        <?php else: ?>
                                            <span class="badge" style="background:#16a34a;color:#fff;"><i class="bi bi-cash me-1"></i>Efectivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-success font-monospace" style="font-size:.95rem;">
                                            + S/ <?= number_format((float)$v['total'], 2) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="bi bi-person-check me-1"></i><?= htmlspecialchars((string)($v['admin_nombre'] ?? 'Administrador')) ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= url('/admin/ventas/detalle/' . (int)$v['id']) ?>" class="btn btn-sm btn-outline-dark" style="border-radius:7px;padding:4px 9px;font-size:.78rem;">
                                            <i class="bi bi-receipt me-1"></i> Detalle
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
