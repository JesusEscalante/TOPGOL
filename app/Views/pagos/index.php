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

$pagoLbl = static fn(string $p): string => match ($p) {
    'verificado' => 'Verificado', 'en_revision' => 'Por verificar',
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
    .badge-count { font-size: .65rem; padding: 2px 7px; border-radius: 12px; font-weight: 800; }
</style>

<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
    <div>
        <h1 class="adm-h1">Pagos y Control de Saldos</h1>
        <p class="adm-sub">Verifica comprobantes Yape, cobra saldos pendientes en Efectivo o Niubiz POS y audita ingresos.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/admin/caja') ?>" class="btn btn-sm fw-bold" style="background:#101c33;color:#fff;border-radius:9px;padding:9px 16px;">
            <i class="bi bi-cash-coin me-1"></i> Caja del día
        </a>
        <a href="<?= url('/reserva/crear') ?>" class="btn btn-sm fw-bold" style="background:#1a7a3a;color:#fff;border-radius:9px;padding:9px 16px;">
            <i class="bi bi-plus-lg me-1"></i> Nueva reserva presencial
        </a>
    </div>
</div>

<!-- Métricas de Pagos -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="kpi">
            <span class="kpi-ico y"><i class="bi bi-hourglass-split"></i></span>
            <div>
                <small class="lbl">Comprobantes por verificar</small>
                <div class="val"><?= $conteoRevision ?></div>
            </div>
            <?php if ($conteoRevision > 0): ?>
                <div class="foot"><a href="<?= url('/admin/pagos?tab=revision') ?>" class="text-danger fw-bold">Revisar →</a></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="kpi">
            <span class="kpi-ico b"><i class="bi bi-clock-history"></i></span>
            <div>
                <small class="lbl">Reservas con saldo pendiente</small>
                <div class="val"><?= $conteoPendientes ?></div>
            </div>
            <div class="foot"><a href="<?= url('/admin/pagos?tab=pendientes') ?>">Cobrar →</a></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="kpi">
            <span class="kpi-ico g"><i class="bi bi-check-all"></i></span>
            <div>
                <small class="lbl">Reservas 100% liquidadas</small>
                <div class="val"><?= $conteoPagadas ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="kpi">
            <span class="kpi-ico g" style="background:#059669;"><i class="bi bi-wallet2"></i></span>
            <div>
                <small class="lbl">Ingresos reales hoy (Caja)</small>
                <div class="val" style="font-size:1.45rem;">S/ <?= number_format((float)$cajaHoy['total_ingresos'], 2) ?></div>
            </div>
            <div class="foot up"><a href="<?= url('/admin/caja') ?>">Detalle →</a></div>
        </div>
    </div>
</div>

<!-- Pestañas de Pagos -->
<div class="nav-tabs-pagos">
    <a href="<?= url('/admin/pagos?tab=todos') ?>" class="tab-btn <?= $tab === 'todos' ? 'active' : '' ?>">
        <i class="bi bi-collection"></i> Todas las reservas
    </a>
    <a href="<?= url('/admin/pagos?tab=revision') ?>" class="tab-btn <?= $tab === 'revision' ? 'active' : '' ?>">
        <i class="bi bi-file-earmark-check"></i> Comprobantes por verificar
        <?php if ($conteoRevision > 0): ?>
            <span class="badge bg-danger badge-count"><?= $conteoRevision ?></span>
        <?php endif; ?>
    </a>
    <a href="<?= url('/admin/pagos?tab=pendientes') ?>" class="tab-btn <?= $tab === 'pendientes' ? 'active' : '' ?>">
        <i class="bi bi-cash-stack"></i> Con saldo pendiente
        <?php if ($conteoPendientes > 0): ?>
            <span class="badge bg-warning text-dark badge-count"><?= $conteoPendientes ?></span>
        <?php endif; ?>
    </a>
    <a href="<?= url('/admin/pagos?tab=pagadas') ?>" class="tab-btn <?= $tab === 'pagadas' ? 'active' : '' ?>">
        <i class="bi bi-check-circle"></i> Pagadas al 100%
        <span class="badge bg-success badge-count"><?= $conteoPagadas ?></span>
    </a>
    <a href="<?= url('/admin/pagos?tab=historial') ?>" class="tab-btn <?= $tab === 'historial' ? 'active' : '' ?>">
        <i class="bi bi-clock-history"></i> Historial de cobros
    </a>
</div>

<!-- Filtros -->
<div class="panel-2 mb-4">
    <form action="<?= url('/admin/pagos') ?>" method="GET" class="f-bar mb-3">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
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
        <a href="<?= url('/admin/pagos?tab=' . $tab) ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:9px;padding:9px 16px;">Limpiar</a>
    </form>

    <?php if ($tab === 'historial'): ?>
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
    <?php else: ?>
        <!-- Pestañas de Reservas (Todos, Revisión, Pendientes, Pagadas) -->
        <div class="d-flex align-items-center justify-content-between my-2">
            <h3 style="font-size:1.02rem; font-weight:800;">Listado de Reservas (<?= count($items) ?>)</h3>
        </div>
        <div class="table-responsive">
            <table class="tbl">
                <thead>
                    <tr>
                        <th># Reserva</th>
                        <th>Cliente</th>
                        <th>Cancha y horario</th>
                        <th>Desglose de montos</th>
                        <th>Estado reserva</th>
                        <th>Comprobante</th>
                        <th>Acciones de cobro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay reservas para mostrar en esta pestaña.</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $r): ?>
                            <?php
                                $pe = (string)($r['pago_estado'] ?? 'pendiente');
                                $comp = (string)($r['comprobante_ruta'] ?? '');
                                $total = (float)$r['total_pago'];
                                $pagado = (float)$r['total_pagado'];
                                $saldo = (float)$r['saldo_pendiente'];
                                $orig = (string)($r['origen'] ?? 'web');
                                $mF = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
                                $tsF = strtotime($r['fecha']);
                                $fCorta = date('d', $tsF) . ' ' . $mF[(int)date('n', $tsF)] . ' ' . date('Y', $tsF);
                            ?>
                            <tr>
                                <td class="fw-bold">
                                    #<?= $r['id'] ?>
                                    <?php if ($r['is_grupo']): ?>
                                        <br><span class="badge bg-secondary" style="font-size:0.6rem;">Evento Grupal</span>
                                    <?php endif; ?>
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
                                    <div class="fw-semibold"><?= htmlspecialchars((string)$r['cliente_nombre']) ?></div>
                                    <?php if (!empty($r['contacto_telefono'])): ?>
                                        <div style="font-size:0.75rem; color:#166534;"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars((string)$r['contacto_telefono']) ?></div>
                                    <?php endif; ?>
                                    <small class="text-muted"><?= htmlspecialchars((string)$r['cliente_email']) ?></small>
                                </td>
                                <td>
                                    <?php if ($r['is_grupo']): ?>
                                        <div style="font-size:0.8rem; font-weight:700;"><?= count($r['canchas']) ?> canchas:</div>
                                        <?php foreach ($r['canchas'] as $c): ?>
                                            <div style="font-size:0.75rem; color:#5b6b82;">• <?= htmlspecialchars($c['nombre']) ?></div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="fw-semibold"><?= htmlspecialchars((string)($r['canchas'][0]['nombre'] ?? 'Cancha')) ?></div>
                                    <?php endif; ?>
                                    <small class="text-muted d-block mt-1">
                                        <?= $fCorta ?> (<?= date('H:i', strtotime($r['hora_inicio'])) ?> - <?= date('H:i', strtotime($r['hora_fin'])) ?>)
                                    </small>
                                </td>
                                <td>
                                    <div style="font-size:.82rem;">Total: <strong>S/ <?= number_format($total, 2) ?></strong></div>
                                    <div style="font-size:.76rem; color:#166534;">Pagado: <strong>S/ <?= number_format($pagado, 2) ?></strong></div>
                                    <?php if ($saldo > 0): ?>
                                        <div style="font-size:.78rem; color:#dc2626; font-weight:700;">
                                            Pendiente: S/ <?= number_format($saldo, 2) ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="font-size:.75rem; color:#166534; font-weight:700;">
                                            <i class="bi bi-check-circle-fill me-1"></i>Saldado 100%
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="pill <?= match ($r['estado']) { 'confirmada' => 'ok', 'pendiente' => 'warn', 'cancelada' => 'bad', default => 'fin' } ?>">
                                        <?= ucfirst((string)$r['estado']) ?>
                                    </span>
                                    <div class="mt-1">
                                        <span class="pill <?= $pagoCls($pe) ?>"><?= $pagoLbl($pe) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($comp): ?>
                                        <button type="button" class="comp-link border-0" onclick="verComprobanteModal('<?= url('/' . ltrim($comp, '/')) ?>', 'Reserva #<?= $r['id'] ?>')">
                                            <i class="bi bi-image"></i> Ver captura
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small" style="font-size:.75rem;">Sin archivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <?php if ($pe === 'en_revision'): ?>
                                            <!-- Botones para verificar comprobante de Yape -->
                                            <form action="<?= url('/admin/pago/verificar/' . $r['id']) ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <?php if ($r['is_grupo']): ?><input type="hidden" name="evento_grupo" value="<?= htmlspecialchars((string)$r['evento_grupo']) ?>"><?php endif; ?>
                                                <button type="submit" class="btn-mini ok" title="Confirmar comprobante e ingreso de dinero">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                            <form action="<?= url('/admin/pago/rechazar/' . $r['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Rechazar comprobante y cancelar la reserva para liberar el horario?');">
                                                <?= csrf_field() ?>
                                                <?php if ($r['is_grupo']): ?><input type="hidden" name="evento_grupo" value="<?= htmlspecialchars((string)$r['evento_grupo']) ?>"><?php endif; ?>
                                                <button type="submit" class="btn-mini bad" title="Rechazar y liberar cancha">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($saldo > 0 && $r['estado'] !== 'cancelada'): ?>
                                            <!-- Botón para cobrar saldo restante -->
                                            <button type="button" class="btn-cobrar"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalCobroSaldo"
                                                    data-id="<?= $r['id'] ?>"
                                                    data-grupo="<?= htmlspecialchars((string)($r['evento_grupo'] ?? '')) ?>"
                                                    data-cliente="<?= htmlspecialchars((string)$r['cliente_nombre']) ?>"
                                                    data-total="<?= number_format($total, 2, '.', '') ?>"
                                                    data-pagado="<?= number_format($pagado, 2, '.', '') ?>"
                                                    data-saldo="<?= number_format($saldo, 2, '.', '') ?>">
                                                <i class="bi bi-cash"></i> Cobrar saldo
                                            </button>
                                        <?php endif; ?>

                                        <a href="<?= url('/mis-reservas/' . $r['id']) ?>" class="btn-mini" title="Ver detalle de la reserva">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL PARA COBRAR SALDO PENDIENTE -->
<div class="modal fade" id="modalCobroSaldo" tabindex="-1" aria-labelledby="modalCobroLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header text-white" style="background:#101c33;">
                <h5 class="modal-title fs-6 fw-bold" id="modalCobroLabel">
                    <i class="bi bi-cash-coin me-1 text-success"></i> Registrar Cobro de Saldo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="<?= url('/admin/pago/registrar') ?>" method="POST" id="formCobroSaldo">
                <?= csrf_field() ?>
                <input type="hidden" name="reserva_id" id="cobroReservaId" value="">
                <input type="hidden" name="evento_grupo" id="cobroEventoGrupo" value="">

                <div class="modal-body p-4">
                    <div class="p-3 mb-3" style="background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0;">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Cliente:</span>
                            <strong id="cobroClienteNombre" class="text-dark">—</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Total reserva:</span>
                            <span id="cobroTotalTexto" class="fw-semibold">S/ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Ya cobrado:</span>
                            <span id="cobroPagadoTexto" class="text-success fw-semibold">S/ 0.00</span>
                        </div>
                        <hr class="my-2" style="border-color:#e2e8f0;">
                        <div class="d-flex justify-content-between fs-6">
                            <strong class="text-dark">Saldo pendiente:</strong>
                            <strong id="cobroSaldoTexto" class="text-danger fw-bold">S/ 0.00</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="montoCobrar" class="form-label fw-bold small text-dark">
                            Monto a cobrar ahora (S/) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white fw-bold">S/</span>
                            <input type="number" step="0.50" min="0.50" class="form-control fw-bold fs-5 text-success"
                                   id="montoCobrar" name="monto" value="" required>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCobrarTodo" title="Cobrar el saldo pendiente completo">
                                Todo el saldo
                            </button>
                        </div>
                        <div class="form-text small" id="montoAyuda">Ingresa el monto exacto recibido del cliente.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Método de cobro <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="d-flex align-items-center gap-2 p-2 border rounded cursor-pointer w-100" style="cursor:pointer;" id="lblEfectivo">
                                    <input type="radio" name="metodo_pago" value="efectivo" checked class="form-check-input mt-0">
                                    <div>
                                        <strong class="d-block text-dark small"><i class="bi bi-cash-stack text-success me-1"></i> Efectivo</strong>
                                        <small class="text-muted" style="font-size:.68rem;">Mostrador</small>
                                    </div>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="d-flex align-items-center gap-2 p-2 border rounded cursor-pointer w-100" style="cursor:pointer;" id="lblNiubiz">
                                    <input type="radio" name="metodo_pago" value="niubiz" class="form-check-input mt-0">
                                    <div>
                                        <strong class="d-block text-dark small"><i class="bi bi-credit-card text-primary me-1"></i> Niubiz POS</strong>
                                        <small class="text-muted" style="font-size:.68rem;">Tarjeta / POS</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="cobroNotas" class="form-label fw-semibold small text-muted">Observación o nota (opcional)</label>
                        <input type="text" class="form-control form-control-sm" id="cobroNotas" name="notas" placeholder="Ej: Cancelado al ingresar a la cancha">
                    </div>
                </div>

                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm fw-bold px-4" id="btnSubmitCobro" style="background:#1a7a3a;color:#fff;">
                        <i class="bi bi-check-circle me-1"></i> Guardar Cobro
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
    var saldoMaximo = 0;

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

            document.getElementById('cobroReservaId').value = id;
            document.getElementById('cobroEventoGrupo').value = grupo || '';
            document.getElementById('cobroClienteNombre').textContent = cliente + (grupo ? ' (Evento grupal)' : ' (#' + id + ')');
            document.getElementById('cobroTotalTexto').textContent = 'S/ ' + total.toFixed(2);
            document.getElementById('cobroPagadoTexto').textContent = 'S/ ' + pagado.toFixed(2);
            document.getElementById('cobroSaldoTexto').textContent = 'S/ ' + saldo.toFixed(2);

            inputMonto.value = saldo.toFixed(2);
            inputMonto.max = saldo.toFixed(2);
        });
    }

    if (btnTodo) {
        btnTodo.addEventListener('click', function() {
            if (saldoMaximo > 0) {
                inputMonto.value = saldoMaximo.toFixed(2);
            }
        });
    }

    // Prevención de doble clic y validación en cliente
    if (formCobro) {
        formCobro.addEventListener('submit', function(e) {
            var m = parseFloat(inputMonto.value || '0');
            if (m <= 0) {
                e.preventDefault();
                alert('El monto a cobrar debe ser mayor a S/ 0.00');
                return;
            }
            if (m > (saldoMaximo + 0.001)) {
                e.preventDefault();
                alert('El monto (S/ ' + m.toFixed(2) + ') no puede superar el saldo pendiente (S/ ' + saldoMaximo.toFixed(2) + ').');
                return;
            }

            // Deshabilitar botón para prevenir doble clic
            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Procesando...';
            }
        });
    }
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
