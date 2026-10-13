<?php
declare(strict_types=1);

/** @var string $tab */
/** @var string $periodo */
/** @var string $desde */
/** @var string $hasta */
/** @var string $hoy */
/** @var bool $incluirDemo */
/** @var array<string, mixed> $filtrosReservas */
/** @var array<int, array<string, mixed>> $canchas */
/** @var array<string, mixed> $reporteReservas */
/** @var array<string, mixed> $reporteIngresos */
/** @var array<string, mixed> $reporteProductos */

$fmtSoles = static fn(float $m): string => 'S/ ' . number_format($m, 2);
$fmtNum   = static fn(float $n): string => number_format($n, 2);
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<style>
    .rep-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
    .rep-tabs { display: flex; gap: 6px; border-bottom: 2px solid #e2e8f0; margin-bottom: 20px; overflow-x: auto; }
    .rep-tab-link { padding: 12px 20px; font-weight: 700; font-size: .92rem; color: #64748b; text-decoration: none; border-bottom: 3px solid transparent; margin-bottom: -2px; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; transition: all .15s; }
    .rep-tab-link:hover { color: #101c33; background: #f8fafc; border-radius: 8px 8px 0 0; }
    .rep-tab-link.active { color: #1a7a3a; border-bottom-color: #1a7a3a; font-weight: 800; }
    .rep-filter-box { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 16px 20px; margin-bottom: 24px; box-shadow: 0 1px 4px rgba(16,28,51,.04); }
    .rep-kpi-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 18px 20px; height: 100%; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,.03); }
    .rep-kpi-lbl { font-size: .75rem; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: .4px; margin-bottom: 6px; }
    .rep-kpi-val { font-size: 1.75rem; font-weight: 800; color: #101c33; line-height: 1.15; }
    .rep-kpi-foot { font-size: .75rem; color: #7c8aa0; margin-top: 8px; border-top: 1px solid #f1f5f9; padding-top: 8px; }
    .rep-table-wrap { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,.03); }
    .rep-table-wrap h4 { font-size: 1.1rem; font-weight: 800; color: #101c33; margin-bottom: 4px; }
    .rep-table-sub { font-size: .82rem; color: #64748b; margin-bottom: 16px; }
    .badge-canal { font-size: .72rem; font-weight: 700; padding: 3px 8px; border-radius: 6px; }
    .badge-web { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .badge-presencial { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .badge-llamada { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-whatsapp { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; }
    .banner-nota { background: #f0fdf4; border-left: 4px solid #16a34a; border-radius: 0 8px 8px 0; padding: 12px 16px; font-size: .84rem; color: #166534; margin-bottom: 20px; }
    .banner-aviso { background: #fffbeb; border-left: 4px solid #d97706; border-radius: 0 8px 8px 0; padding: 12px 16px; font-size: .84rem; color: #92400e; margin-bottom: 20px; }
    .bar-hora-track { background: #f1f5f9; border-radius: 6px; height: 18px; width: 100%; overflow: hidden; position: relative; }
    .bar-hora-fill { background: linear-gradient(90deg, #16a34a 0%, #22c55e 100%); height: 100%; border-radius: 6px; transition: width .3s ease; }
</style>

<!-- Encabezado Principal y Botón de Exportación -->
<div class="rep-header">
    <div>
        <h1 class="adm-h1">Reportes y Consolidación del Sistema</h1>
        <p class="adm-sub">Análisis operativo de reservas, control financiero de cobros e inventario comercial.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?= url('/admin/reportes/exportar?tipo=' . urlencode($tab) . '&desde=' . urlencode($desde) . '&hasta=' . urlencode($hasta) . ($incluirDemo ? '&demo=1' : '') . (!empty($filtrosReservas['cancha_id']) ? '&cancha_id=' . $filtrosReservas['cancha_id'] : '') . (!empty($filtrosReservas['origen']) ? '&origen=' . urlencode($filtrosReservas['origen']) : '') . (!empty($filtrosReservas['estado']) ? '&estado=' . urlencode($filtrosReservas['estado']) : '')) ?>"
           class="btn btn-sm btn-success fw-bold shadow-sm" style="border-radius:10px; padding:8px 18px;">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar a Excel (CSV)
        </a>
        <a href="<?= url('/admin/dashboard') ?>" class="btn btn-sm btn-outline-dark fw-bold" style="border-radius:10px; padding:8px 14px;">
            <i class="bi bi-house me-1"></i> Dashboard
        </a>
    </div>
</div>

<!-- Barra de Pestañas Principales -->
<div class="rep-tabs">
    <a href="<?= url('/admin/reportes?tab=reservas&periodo=' . urlencode($periodo) . '&desde=' . urlencode($desde) . '&hasta=' . urlencode($hasta) . ($incluirDemo ? '&demo=1' : '')) ?>"
       class="rep-tab-link <?= $tab === 'reservas' ? 'active' : '' ?>">
        <i class="bi bi-calendar2-range"></i> 1. Reservas y Ocupación
    </a>
    <a href="<?= url('/admin/reportes?tab=ingresos&periodo=' . urlencode($periodo) . '&desde=' . urlencode($desde) . '&hasta=' . urlencode($hasta) . ($incluirDemo ? '&demo=1' : '')) ?>"
       class="rep-tab-link <?= $tab === 'ingresos' ? 'active' : '' ?>">
        <i class="bi bi-cash-stack"></i> 2. Ingresos y Recaudación
    </a>
    <a href="<?= url('/admin/reportes?tab=productos&periodo=' . urlencode($periodo) . '&desde=' . urlencode($desde) . '&hasta=' . urlencode($hasta) . ($incluirDemo ? '&demo=1' : '')) ?>"
       class="rep-tab-link <?= $tab === 'productos' ? 'active' : '' ?>">
        <i class="bi bi-box-seam"></i> 3. Productos e Inventario
    </a>
</div>

<!-- Barra Universal de Filtros por Fecha y Periodo -->
<div class="rep-filter-box">
    <form action="<?= url('/admin/reportes') ?>" method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">

        <!-- Selector de Presets de Periodo -->
        <div class="col-lg-5">
            <label class="form-label small fw-bold text-uppercase text-muted" style="font-size:.72rem;">Periodo de Consulta:</label>
            <div class="btn-group w-100" role="group">
                <a href="<?= url('/admin/reportes?tab=' . $tab . '&periodo=hoy' . ($incluirDemo ? '&demo=1' : '')) ?>"
                   class="btn btn-sm <?= $periodo === 'hoy' ? 'btn-dark' : 'btn-outline-secondary' ?> fw-semibold">Hoy</a>
                <a href="<?= url('/admin/reportes?tab=' . $tab . '&periodo=semana' . ($incluirDemo ? '&demo=1' : '')) ?>"
                   class="btn btn-sm <?= $periodo === 'semana' ? 'btn-dark' : 'btn-outline-secondary' ?> fw-semibold">Esta Semana</a>
                <a href="<?= url('/admin/reportes?tab=' . $tab . '&periodo=mes' . ($incluirDemo ? '&demo=1' : '')) ?>"
                   class="btn btn-sm <?= $periodo === 'mes' ? 'btn-dark' : 'btn-outline-secondary' ?> fw-semibold">Este Mes</a>
                <button type="button" class="btn btn-sm <?= $periodo === 'personalizado' ? 'btn-dark' : 'btn-outline-secondary' ?> fw-semibold"
                        onclick="document.getElementById('personalizadoBox').classList.remove('d-none')">
                    Personalizado
                </button>
            </div>
        </div>

        <!-- Rango de Fechas -->
        <div class="col-sm-6 col-lg-3">
            <label for="fDesde" class="form-label small fw-bold text-uppercase text-muted" style="font-size:.72rem;">Desde:</label>
            <input type="date" id="fDesde" name="desde" value="<?= htmlspecialchars($desde) ?>" class="form-control form-control-sm fw-semibold">
        </div>
        <div class="col-sm-6 col-lg-3">
            <label for="fHasta" class="form-label small fw-bold text-uppercase text-muted" style="font-size:.72rem;">Hasta:</label>
            <input type="date" id="fHasta" name="hasta" value="<?= htmlspecialchars($hasta) ?>" class="form-control form-control-sm fw-semibold">
        </div>

        <div class="col-lg-1">
            <input type="hidden" name="periodo" value="personalizado">
            <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold" style="padding:6px 0;" title="Aplicar filtro">
                <i class="bi bi-funnel"></i>
            </button>
        </div>
    </form>

    <!-- Switch de aislamiento de datos demo -->
    <div class="d-flex align-items-center justify-content-between pt-3 mt-3 border-top flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <small class="text-muted fw-semibold">Rango activo: <strong><?= date('d/m/Y', strtotime($desde)) ?></strong> al <strong><?= date('d/m/Y', strtotime($hasta)) ?></strong></small>
            <?php if ($incluirDemo): ?>
                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle-fill me-1"></i>Modo Auditoría Demo</span>
            <?php else: ?>
                <span class="badge bg-success"><i class="bi bi-shield-check me-1"></i>Solo Operaciones Comerciales Reales</span>
            <?php endif; ?>
        </div>

        <div>
            <?php if ($incluirDemo): ?>
                <a href="<?= url('/admin/reportes?tab=' . $tab . '&periodo=' . $periodo . '&desde=' . $desde . '&hasta=' . $hasta . '&demo=0') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-eye-slash me-1"></i> Ocultar ventas y pagos demo
                </a>
            <?php else: ?>
                <a href="<?= url('/admin/reportes?tab=' . $tab . '&periodo=' . $periodo . '&desde=' . $desde . '&hasta=' . $hasta . '&demo=1') ?>" class="btn btn-sm btn-outline-warning text-dark">
                    <i class="bi bi-shield-exclamation me-1"></i> Incluir transacciones demo
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ====================================================================
     PESTAÑA 1: REPORTES DE RESERVAS Y OCUPACIÓN
     ==================================================================== -->
<?php if ($tab === 'reservas'): ?>
    <?php
    $rTotal = (int)$reporteReservas['total_reservas'];
    $rUnicas = (int)$reporteReservas['solicitudes_unicas'];
    $rHoras = (float)$reporteReservas['horas_cancha'];
    $rContratado = (float)$reporteReservas['total_contratado'];
    $rAdelantos = (float)$reporteReservas['adelantos_verificados'];
    $rSaldo = (float)$reporteReservas['saldo_pendiente_total'];
    $rConf = (int)$reporteReservas['confirmadas'];
    $rPend = (int)$reporteReservas['pendientes'];
    $rCanc = (int)$reporteReservas['canceladas'];
    $rFin = (int)$reporteReservas['finalizadas'];
    ?>

    <!-- Filtros específicos para la tabla de reservas -->
    <div class="card border-0 shadow-sm p-3 mb-4 rounded-3">
        <form action="<?= url('/admin/reportes') ?>" method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="tab" value="reservas">
            <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo) ?>">
            <input type="hidden" name="desde" value="<?= htmlspecialchars($desde) ?>">
            <input type="hidden" name="hasta" value="<?= htmlspecialchars($hasta) ?>">
            <input type="hidden" name="demo" value="<?= $incluirDemo ? '1' : '0' ?>">

            <div class="col-md-3">
                <select name="cancha_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todas las canchas</option>
                    <?php foreach ($canchas as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (!empty($filtrosReservas['cancha_id']) && (int)$filtrosReservas['cancha_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?> (<?= htmlspecialchars($c['tipo']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="origen" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todos los canales de origen</option>
                    <option value="web" <?= ($filtrosReservas['origen'] ?? '') === 'web' ? 'selected' : '' ?>>Web (Portal)</option>
                    <option value="presencial" <?= ($filtrosReservas['origen'] ?? '') === 'presencial' ? 'selected' : '' ?>>Presencial (Mostrador)</option>
                    <option value="whatsapp" <?= ($filtrosReservas['origen'] ?? '') === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                    <option value="llamada" <?= ($filtrosReservas['origen'] ?? '') === 'llamada' ? 'selected' : '' ?>>Llamada telefónica</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <option value="confirmada" <?= ($filtrosReservas['estado'] ?? '') === 'confirmada' ? 'selected' : '' ?>>Confirmadas</option>
                    <option value="pendiente" <?= ($filtrosReservas['estado'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                    <option value="finalizada" <?= ($filtrosReservas['estado'] ?? '') === 'finalizada' ? 'selected' : '' ?>>Finalizadas</option>
                    <option value="cancelada" <?= ($filtrosReservas['estado'] ?? '') === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                </select>
            </div>
            <div class="col-md-3 text-end">
                <?php if (!empty($filtrosReservas)): ?>
                    <a href="<?= url('/admin/reportes?tab=reservas&periodo=' . $periodo . '&desde=' . $desde . '&hasta=' . $hasta . ($incluirDemo ? '&demo=1' : '')) ?>" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-x-circle me-1"></i> Limpiar filtros
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tarjetas KPI de Reservas -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="rep-kpi-card">
                <div>
                    <div class="rep-kpi-lbl">Total Reservas (Slots)</div>
                    <div class="rep-kpi-val"><?= $rTotal ?></div>
                </div>
                <div class="rep-kpi-foot d-flex justify-content-between">
                    <span><?= $rUnicas ?> solicitudes únicas</span>
                    <span class="text-success fw-bold"><?= $rConf ?> conf.</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="rep-kpi-card">
                <div>
                    <div class="rep-kpi-lbl">Horas-Cancha Contratadas</div>
                    <div class="rep-kpi-val text-primary"><?= $fmtNum($rHoras) ?> <small class="fs-6 fw-normal text-muted">hrs</small></div>
                </div>
                <div class="rep-kpi-foot d-flex justify-content-between">
                    <span>Capacidad efectiva</span>
                    <span class="text-muted"><?= $rFin ?> finalizadas</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="rep-kpi-card">
                <div>
                    <div class="rep-kpi-lbl">Monto Total Contratado</div>
                    <div class="rep-kpi-val"><?= $fmtSoles($rContratado) ?></div>
                </div>
                <div class="rep-kpi-foot d-flex justify-content-between">
                    <span>Adelantos: <?= $fmtSoles($rAdelantos) ?></span>
                    <span class="text-danger"><?= $rCanc ?> canc.</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="rep-kpi-card">
                <div>
                    <div class="rep-kpi-lbl">Saldo Pendiente de Cobro</div>
                    <div class="rep-kpi-val <?= $rSaldo > 0 ? 'text-warning' : 'text-success' ?>">
                        <?= $fmtSoles($rSaldo) ?>
                    </div>
                </div>
                <div class="rep-kpi-foot d-flex justify-content-between">
                    <span>Por cobrar en cancha</span>
                    <span class="badge bg-warning text-dark"><?= $rPend ?> pendientes</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Análisis de Ocupación por Cancha y Horarios Pico -->
    <div class="row g-3 mb-4">
        <!-- Ocupación por Cancha -->
        <div class="col-lg-6">
            <div class="rep-table-wrap h-100">
                <h4>Ocupación por Cancha</h4>
                <div class="rep-table-sub">Horas-cancha efectivas y demanda acumulada en el periodo</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Cancha</th>
                                <th>Tipo</th>
                                <th class="text-center">Reservas</th>
                                <th class="text-center">Horas-Cancha</th>
                                <th class="text-end">Monto Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reporteReservas['por_cancha'])): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">Sin actividad en canchas para este periodo.</td></tr>
                            <?php else: ?>
                                <?php foreach ($reporteReservas['por_cancha'] as $pc): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($pc['cancha_nombre']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($pc['cancha_tipo']) ?></span></td>
                                        <td class="text-center fw-semibold"><?= (int)$pc['total_reservas'] ?></td>
                                        <td class="text-center fw-bold text-success"><?= number_format((float)$pc['horas_cancha'], 2) ?> hrs</td>
                                        <td class="text-end fw-semibold">S/ <?= number_format((float)$pc['monto_total'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Horarios de Mayor Demanda (Picos) -->
        <div class="col-lg-6">
            <div class="rep-table-wrap h-100">
                <h4>Horarios de Mayor Demanda</h4>
                <div class="rep-table-sub">Concentración horaria de alquiler de canchas (07:00 a 23:00)</div>
                <div style="max-height: 290px; overflow-y: auto;">
                    <?php
                    $maxReservasSlot = 1;
                    foreach ($reporteReservas['por_horas'] as $slot) {
                        if ($slot['reservas'] > $maxReservasSlot) {
                            $maxReservasSlot = $slot['reservas'];
                        }
                    }
                    ?>
                    <?php if (empty($reporteReservas['por_horas'])): ?>
                        <div class="text-center py-4 text-muted">No se registran horarios ocupados en este periodo.</div>
                    <?php else: ?>
                        <?php foreach ($reporteReservas['por_horas'] as $slot): ?>
                            <?php $pctBar = (int)round(($slot['reservas'] / $maxReservasSlot) * 100); ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between small fw-bold mb-1">
                                    <span><?= htmlspecialchars($slot['hora']) ?></span>
                                    <span><?= $slot['reservas'] ?> reservas (<?= $slot['horas_cancha'] ?> hrs-cancha)</span>
                                </div>
                                <div class="bar-hora-track">
                                    <div class="bar-hora-fill" style="width: <?= max(5, $pctBar) ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Canales de Origen -->
    <div class="rep-table-wrap mb-4">
        <h4>Distribución por Canal de Origen</h4>
        <div class="rep-table-sub">Diferenciación de reservas vía Web, Presencial, Llamada y WhatsApp</div>
        <div class="row g-3">
            <?php foreach ($reporteReservas['por_origen'] as $po): ?>
                <?php
                $canalCls = match ($po['canal']) {
                    'web'        => 'badge-web',
                    'presencial' => 'badge-presencial',
                    'whatsapp'   => 'badge-whatsapp',
                    'llamada'    => 'badge-llamada',
                    default      => 'badge-web'
                };
                ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="p-3 border rounded-3 bg-light">
                        <span class="badge-canal <?= $canalCls ?> mb-2 d-inline-block text-uppercase fw-bold"><?= htmlspecialchars($po['canal']) ?></span>
                        <div class="fs-4 fw-bold text-dark"><?= (int)$po['total_reservas'] ?> reservas</div>
                        <small class="text-muted d-block"><?= (int)$po['solicitudes_unicas'] ?> solicitudes únicas</small>
                        <div class="fw-semibold text-success small mt-1"><?= number_format((float)$po['horas_cancha'], 2) ?> horas-cancha</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tabla Detallada de Reservas -->
    <div class="rep-table-wrap">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div>
                <h4>Detalle de Reservas del Periodo</h4>
                <div class="rep-table-sub">Listado completo con distinción de reservas individuales y eventos agrupados</div>
            </div>
            <span class="badge bg-secondary"><?= count($reporteReservas['lista']) ?> registros</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Fecha y Hora</th>
                        <th>Cancha</th>
                        <th>Cliente</th>
                        <th>Canal</th>
                        <th>Tipo Evento</th>
                        <th>Estado</th>
                        <th>Pago</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Adelanto</th>
                        <th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reporteReservas['lista'])): ?>
                        <tr><td colspan="11" class="text-center text-muted py-4">No se encontraron reservas con los filtros aplicados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reporteReservas['lista'] as $item): ?>
                            <?php
                            $estPill = match ($item['estado']) {
                                'confirmada' => 'bg-success',
                                'pendiente'  => 'bg-warning text-dark',
                                'cancelada'  => 'bg-danger',
                                'finalizada' => 'bg-secondary',
                                default      => 'bg-secondary'
                            };
                            $pagoPill = match ($item['pago_estado']) {
                                'verificado'  => 'bg-success',
                                'en_revision' => 'bg-warning text-dark',
                                'rechazado'   => 'bg-danger',
                                default       => 'bg-secondary'
                            };
                            $saldoItem = (float)$item['saldo_pendiente'];
                            ?>
                            <tr>
                                <td class="fw-bold">#<?= $item['id'] ?></td>
                                <td>
                                    <span class="fw-semibold"><?= date('d/m/Y', strtotime($item['fecha'])) ?></span><br>
                                    <small class="text-muted"><?= date('H:i', strtotime($item['hora_inicio'])) ?> - <?= date('H:i', strtotime($item['hora_fin'])) ?> (<?= $item['duracion_horas'] ?>h)</small>
                                </td>
                                <td><?= htmlspecialchars($item['cancha_nombre']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($item['cliente_nombre']) ?></strong>
                                    <?php if (!empty($item['cliente_telefono'])): ?>
                                        <small class="text-muted d-block"><?= htmlspecialchars($item['cliente_telefono']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge-canal <?= 'badge-' . $item['origen'] ?>"><?= strtoupper((string)$item['origen']) ?></span></td>
                                <td>
                                    <?php if (!empty($item['evento_grupo'])): ?>
                                        <span class="badge bg-primary" title="Evento de múltiples canchas agrupadas">Evento: <?= htmlspecialchars($item['evento_grupo']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border">Individual</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?= $estPill ?>"><?= ucfirst((string)$item['estado']) ?></span></td>
                                <td><span class="badge <?= $pagoPill ?>"><?= ucfirst((string)$item['pago_estado']) ?></span></td>
                                <td class="text-end fw-semibold">S/ <?= number_format((float)$item['total_pago'], 2) ?></td>
                                <td class="text-end text-success fw-bold">S/ <?= number_format((float)$item['pagado_verificado'], 2) ?></td>
                                <td class="text-end fw-bold <?= $saldoItem > 0 ? 'text-warning' : 'text-success' ?>">
                                    S/ <?= number_format($saldoItem, 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- ====================================================================
     PESTAÑA 2: REPORTES DE INGRESOS Y RECAUDACIÓN
     ==================================================================== -->
<?php elseif ($tab === 'ingresos'): ?>
    <?php
    $iTotalCobrado = (float)$reporteIngresos['total_ingresos'];
    $iEfectivo = (float)$reporteIngresos['total_efectivo'];
    $iNiubiz = (float)$reporteIngresos['total_niubiz'];
    $iYape = (float)$reporteIngresos['total_yape'];
    $iCanchas = (float)$reporteIngresos['canchas_total'];
    $iProductos = (float)$reporteIngresos['productos_total'];
    $iSaldosPend = (float)$reporteIngresos['saldos_pendientes'];
    $iAdelantos = (float)$reporteIngresos['adelantos_verificados'];
    $iSaldosCob = (float)$reporteIngresos['saldos_cobrados'];
    ?>

    <div class="banner-nota">
        <i class="bi bi-shield-check me-2"></i>
        <strong>Principio Financiero Auditado:</strong> Este reporte consolida únicamente los importes <strong>efectivamente cobrados y verificados</strong> en la caja/banco durante el periodo. Los montos pendientes de cobro no inflan los ingresos percibidos.
    </div>

    <!-- KPIs Financieros -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="rep-kpi-card" style="border-top: 4px solid #16a34a;">
                <div>
                    <div class="rep-kpi-lbl">Total General Cobrado</div>
                    <div class="rep-kpi-val text-success"><?= $fmtSoles($iTotalCobrado) ?></div>
                </div>
                <div class="rep-kpi-foot d-flex justify-content-between">
                    <span><?= (int)$reporteIngresos['total_transacciones'] ?> operaciones</span>
                    <span>Canchas + Bar</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="rep-kpi-card" style="border-top: 4px solid #2563eb;">
                <div>
                    <div class="rep-kpi-lbl">Alquiler de Canchas / Eventos</div>
                    <div class="rep-kpi-val text-primary"><?= $fmtSoles($iCanchas) ?></div>
                </div>
                <div class="rep-kpi-foot d-flex justify-content-between">
                    <span>Adelantos: <?= $fmtSoles($iAdelantos) ?></span>
                    <span>Saldos: <?= $fmtSoles($iSaldosCob) ?></span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="rep-kpi-card" style="border-top: 4px solid #0284c7;">
                <div>
                    <div class="rep-kpi-lbl">Ventas de Productos (Bar/POS)</div>
                    <div class="rep-kpi-val text-info"><?= $fmtSoles($iProductos) ?></div>
                </div>
                <div class="rep-kpi-foot d-flex justify-content-between">
                    <span><?= (int)$reporteIngresos['productos_tx'] ?> ventas</span>
                    <span>Mostrador Kiosco</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="rep-kpi-card" style="border-top: 4px solid #f59e0b;">
                <div>
                    <div class="rep-kpi-lbl">Saldos por Cobrar (Pendientes)</div>
                    <div class="rep-kpi-val text-warning"><?= $fmtSoles($iSaldosPend) ?></div>
                </div>
                <div class="rep-kpi-foot d-flex justify-content-between">
                    <span>Reservas del periodo</span>
                    <a href="<?= url('/admin/pagos') ?>" class="text-decoration-none fw-bold small">Ver saldos →</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Desglose por Método de Pago -->
    <div class="rep-table-wrap mb-4">
        <h4>Desglose por Método de Pago</h4>
        <div class="rep-table-sub">Distribución del dinero percibido según el canal financiero</div>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="p-3 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted text-uppercase fw-bold d-block" style="font-size:.7rem;">EFECTIVO (CAJA FÍSICA)</small>
                        <strong class="fs-4 text-dark"><?= $fmtSoles($iEfectivo) ?></strong>
                    </div>
                    <div class="rounded-circle p-3 text-white" style="background:#16a34a;"><i class="bi bi-cash fs-4"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted text-uppercase fw-bold d-block" style="font-size:.7rem;">NIUBIZ (TARJETA / POS)</small>
                        <strong class="fs-4 text-dark"><?= $fmtSoles($iNiubiz) ?></strong>
                    </div>
                    <div class="rounded-circle p-3 text-white" style="background:#0284c7;"><i class="bi bi-credit-card-2-front fs-4"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted text-uppercase fw-bold d-block" style="font-size:.7rem;">YAPE (BILLETERA DIGITAL)</small>
                        <strong class="fs-4 text-dark"><?= $fmtSoles($iYape) ?></strong>
                    </div>
                    <div class="rounded-circle p-3 text-white" style="background:#70248a;"><i class="bi bi-phone fs-4"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Consolidado Cronológico por Día -->
    <div class="rep-table-wrap mb-4">
        <h4>Recaudación Cronológica por Día</h4>
        <div class="rep-table-sub">Detalle diario consolidado entre reservas y ventas de productos</div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th class="text-end">Canchas (S/)</th>
                        <th class="text-end">Productos (S/)</th>
                        <th class="text-end">Total Cobrado (S/)</th>
                        <th class="text-end">Efectivo (S/)</th>
                        <th class="text-end">Niubiz (S/)</th>
                        <th class="text-end">Yape (S/)</th>
                        <th class="text-center">Operaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reporteIngresos['dias'])): ?>
                        <tr><td colspan="8" class="text-center text-muted py-3">Sin recaudación registrada en las fechas seleccionadas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reporteIngresos['dias'] as $d): ?>
                            <tr>
                                <td class="fw-bold"><?= date('d/m/Y', strtotime($d['dia'])) ?></td>
                                <td class="text-end"><?= number_format((float)$d['canchas_monto'], 2) ?></td>
                                <td class="text-end"><?= number_format((float)$d['prods_monto'], 2) ?></td>
                                <td class="text-end fw-bold text-success">S/ <?= number_format((float)$d['total_ingresos'], 2) ?></td>
                                <td class="text-end"><?= number_format((float)$d['total_efectivo'], 2) ?></td>
                                <td class="text-end"><?= number_format((float)$d['total_niubiz'], 2) ?></td>
                                <td class="text-end"><?= number_format((float)$d['total_yape'], 2) ?></td>
                                <td class="text-center fw-semibold"><?= (int)$d['transacciones'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Lista Detallada de Transacciones Auditadas -->
    <div class="rep-table-wrap">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div>
                <h4>Movimientos y Cobros Detallados</h4>
                <div class="rep-table-sub">Auditoría individual de cada pago verificado y venta registrada</div>
            </div>
            <span class="badge bg-secondary"><?= count($reporteIngresos['transacciones']) ?> cobros</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Rubro</th>
                        <th>Concepto / Referencia</th>
                        <th>Cliente</th>
                        <th>Método</th>
                        <th class="text-end">Monto</th>
                        <th>Registrado Por</th>
                        <th>Tipo Dato</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reporteIngresos['transacciones'])): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No se registran cobros en el rango consultado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reporteIngresos['transacciones'] as $tx): ?>
                            <?php
                            $metodoPill = match ($tx['metodo_pago']) {
                                'efectivo' => 'bg-success',
                                'niubiz'   => 'bg-info text-dark',
                                'yape'     => 'bg-purple text-white',
                                default    => 'bg-secondary'
                            };
                            ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?></td>
                                <td>
                                    <?php if ($tx['rubro'] === 'cancha'): ?>
                                        <span class="badge bg-success"><i class="bi bi-grid me-1"></i>Canchas</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary"><i class="bi bi-cart me-1"></i>Kiosco</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($tx['concepto']) ?></strong></td>
                                <td><?= htmlspecialchars($tx['cliente_nombre'] ?? 'Cliente Mostrador') ?></td>
                                <td><span class="badge <?= $metodoPill ?>" style="<?= $tx['metodo_pago'] === 'yape' ? 'background:#70248a!important;' : '' ?>"><?= strtoupper((string)$tx['metodo_pago']) ?></span></td>
                                <td class="text-end fw-bold text-success">S/ <?= number_format((float)$tx['monto'], 2) ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($tx['admin_nombre'] ?? 'Sistema') ?></small></td>
                                <td>
                                    <?php if (!empty($tx['es_demo'])): ?>
                                        <span class="badge bg-warning text-dark">DEMO</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">REAL</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- ====================================================================
     PESTAÑA 3: REPORTES DE PRODUCTOS E INVENTARIO
     ==================================================================== -->
<?php elseif ($tab === 'productos'): ?>
    <?php
    $pUnidades = (int)$reporteProductos['total_unidades'];
    $pIngresos = (float)$reporteProductos['total_ingresos'];
    $pTx = (int)$reporteProductos['total_transacciones'];
    $pTicket = (float)$reporteProductos['ticket_promedio'];
    $pPvp = (float)$reporteProductos['valor_inventario_pvp'];
    $pBajoConteo = (int)$reporteProductos['conteo_stock_bajo'];
    ?>

    <?php if ($incluirDemo): ?>
        <div class="alert alert-warning border-warning d-flex align-items-center justify-content-between mb-4 shadow-sm" style="border-radius:12px;">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-shield-exclamation fs-4 text-warning"></i>
                <div>
                    <strong>Modo Demostración Activo:</strong> Se están visualizando ventas y rotación de prueba/calibración en este reporte comercial de productos.
                </div>
            </div>
            <a href="<?= url('/admin/reportes?tab=productos&periodo=' . $periodo . '&desde=' . $desde . '&hasta=' . $hasta . '&demo=0') ?>" class="btn btn-sm btn-dark fw-bold">
                <i class="bi bi-eye-slash me-1"></i> Ocultar ventas demo
            </a>
        </div>
    <?php endif; ?>

    <div class="banner-aviso">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>Aviso Contable:</strong> La valorización de existencias se calcula estrictamente a <strong>Precio de Venta al Público (PVP)</strong> de acuerdo con el catálogo vigente. No representa costo de adquisición de inventario ni margen de ganancia bruta, garantizando transparencia financiera.
    </div>

    <!-- KPIs de Productos -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="rep-kpi-card">
                <div>
                    <div class="rep-kpi-lbl">Unidades Vendidas</div>
                    <div class="rep-kpi-val text-primary"><?= $pUnidades ?></div>
                </div>
                <div class="rep-kpi-foot d-flex justify-content-between">
                    <span><?= $pTx ?> ventas realizadas</span>
                    <span>Bar / Kiosco</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="rep-kpi-card">
                <div>
                    <div class="rep-kpi-lbl">Ingresos por Productos</div>
                    <div class="rep-kpi-val text-success"><?= $fmtSoles($pIngresos) ?></div>
                </div>
                <div class="rep-kpi-foot d-flex justify-content-between">
                    <span>Ticket Promedio</span>
                    <strong><?= $fmtSoles($pTicket) ?></strong>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="rep-kpi-card">
                <div>
                    <div class="rep-kpi-lbl">Valor de Existencias (PVP)</div>
                    <div class="rep-kpi-val text-dark"><?= $fmtSoles($pPvp) ?></div>
                </div>
                <div class="rep-kpi-foot d-flex justify-content-between">
                    <span>PVP Catálogo</span>
                    <a href="<?= url('/admin/productos') ?>" class="small text-decoration-none fw-bold">Ver stock →</a>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="rep-kpi-card">
                <div>
                    <div class="rep-kpi-lbl">Stock Bajo / Agotados</div>
                    <div class="rep-kpi-val <?= $pBajoConteo > 0 ? 'text-danger' : 'text-success' ?>"><?= $pBajoConteo ?></div>
                </div>
                <div class="rep-kpi-foot d-flex justify-content-between">
                    <span>Ítems por reabastecer</span>
                    <a href="<?= url('/admin/inventario') ?>" class="small text-decoration-none fw-bold">Kardex →</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Ranking de Productos Más Vendidos y Categorías -->
    <div class="row g-3 mb-4">
        <!-- Ranking -->
        <div class="col-lg-8">
            <div class="rep-table-wrap h-100">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <h4 class="mb-0">Ranking de Productos Más Vendidos</h4>
                    <?= $incluirDemo ? '<span class="badge bg-warning text-dark"><i class="bi bi-flask me-1"></i>Incluye Demo</span>' : '<span class="badge bg-success"><i class="bi bi-shield-check me-1"></i>Comercial Real</span>' ?>
                </div>
                <div class="rep-table-sub">Productos con mayor rotación e ingresos generados en el periodo</div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th class="text-center">Vendidos</th>
                                <th class="text-end">Ingresos</th>
                                <th class="text-center" title="Existencia física vigente al día de hoy en catálogo">Stock Actual (Hoy) *</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reporteProductos['ranking'])): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">No se registran ventas de productos en este periodo.</td></tr>
                            <?php else: ?>
                                <?php $rk = 0; foreach ($reporteProductos['ranking'] as $prod): $rk++; ?>
                                    <?php
                                    $stk = (int)$prod['stock_actual'];
                                    $min = (int)$prod['stock_minimo'];
                                    ?>
                                    <tr>
                                        <td class="fw-bold text-muted"><?= $rk ?></td>
                                        <td><strong><?= htmlspecialchars($prod['nombre']) ?></strong></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($prod['categoria_nombre']) ?></span></td>
                                        <td class="text-center fw-bold"><?= (int)$prod['unidades_vendidas'] ?> <small class="text-muted"><?= htmlspecialchars($prod['unidad'] ?? 'und') ?></small></td>
                                        <td class="text-end fw-bold text-success">S/ <?= number_format((float)$prod['total_ingresos'], 2) ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $stk === 0 ? 'bg-dark' : ($stk <= $min ? 'bg-danger' : 'bg-secondary') ?>"><?= $stk ?></span>
                                        </td>
                                        <td>
                                            <?php if ($stk === 0): ?>
                                                <span class="badge bg-dark">Agotado</span>
                                            <?php elseif ($stk <= $min): ?>
                                                <span class="badge bg-danger">Stock Bajo</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Óptimo</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-light border small text-muted mt-2 py-2 px-3 d-flex align-items-center gap-2 mb-0" style="font-size:.76rem;">
                    <i class="bi bi-info-circle text-primary"></i>
                    <span><strong>* Nota de inventario:</strong> La columna <em>Stock Actual (Hoy)</em> reporta las existencias físicas vigentes al día de hoy en catálogo, no el saldo que existía en la fecha o periodo consultado. Para verificar saldos remanentes en fechas pasadas, consulte el <a href="<?= url('/admin/inventario') ?>" class="fw-bold text-decoration-none">Kardex de Movimientos</a>.</span>
                </div>
            </div>
        </div>

        <!-- Categorías Más Vendidas -->
        <div class="col-lg-4">
            <div class="rep-table-wrap h-100">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <h4 class="mb-0">Ventas por Categoría</h4>
                    <?= $incluirDemo ? '<span class="badge bg-warning text-dark"><i class="bi bi-flask me-1"></i>Incluye Demo</span>' : '' ?>
                </div>
                <div class="rep-table-sub">Participación de categorías en los ingresos</div>
                <?php if (empty($reporteProductos['por_categoria'])): ?>
                    <div class="text-center text-muted py-4">Sin ventas por categoría.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($reporteProductos['por_categoria'] as $cat): ?>
                            <?php
                            $pctCat = $pIngresos > 0 ? (int)round(((float)$cat['total_ingresos'] / $pIngresos) * 100) : 0;
                            ?>
                            <div class="list-group-item px-0 py-2 border-0">
                                <div class="d-flex justify-content-between fw-bold small mb-1">
                                    <span><?= htmlspecialchars($cat['categoria_nombre']) ?></span>
                                    <span>S/ <?= number_format((float)$cat['total_ingresos'], 2) ?> (<?= $pctCat ?>%)</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pctCat ?>%"></div>
                                </div>
                                <small class="text-muted"><?= (int)$cat['unidades_vendidas'] ?> unidades</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Alertas de Stock Bajo y Movimientos de Inventario -->
    <div class="row g-3">
        <!-- Stock Crítico -->
        <div class="col-lg-4">
            <div class="rep-table-wrap h-100">
                <h4>Alertas de Stock Bajo</h4>
                <div class="rep-table-sub">Productos con existencias en nivel de reposición</div>
                <?php if (empty($reporteProductos['productos_stock_bajo'])): ?>
                    <div class="alert alert-success d-flex align-items-center gap-2 p-2 small m-0">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Todo el inventario supera los stocks mínimos.</span>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush small">
                        <?php foreach ($reporteProductos['productos_stock_bajo'] as $psb): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <div>
                                    <strong><?= htmlspecialchars($psb['nombre']) ?></strong>
                                    <small class="text-muted d-block"><?= htmlspecialchars($psb['categoria_nombre'] ?? 'General') ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge <?= (int)$psb['stock'] === 0 ? 'bg-dark' : 'bg-danger' ?>">
                                        <?= (int)$psb['stock'] === 0 ? 'Agotado' : (int)$psb['stock'] . ' ' . htmlspecialchars($psb['unidad'] ?? 'und') ?>
                                    </span>
                                    <small class="text-muted d-block">Mín: <?= (int)$psb['stock_minimo'] ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Movimientos de Inventario (Kardex) -->
        <div class="col-lg-8">
            <div class="rep-table-wrap h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <h4>Movimientos de Inventario (Kardex)</h4>
                        <div class="rep-table-sub">Últimos movimientos de ingresos, salidas y ventas auditadas</div>
                    </div>
                    <a href="<?= url('/admin/inventario') ?>" class="btn btn-sm btn-outline-dark">Ver Kardex completo</a>
                </div>
                <div class="table-responsive" style="max-height: 290px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-center">Stock</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reporteProductos['movimientos'])): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">Sin movimientos de inventario en las fechas consultadas.</td></tr>
                            <?php else: ?>
                                <?php foreach ($reporteProductos['movimientos'] as $mov): ?>
                                    <?php
                                    $tipoMovCls = match ($mov['tipo']) {
                                        'ingreso' => 'bg-success',
                                        'salida'  => 'bg-danger',
                                        'venta'   => 'bg-primary',
                                        'ajuste'  => 'bg-warning text-dark',
                                        default   => 'bg-secondary'
                                    };
                                    ?>
                                    <tr>
                                        <td><small><?= date('d/m H:i', strtotime($mov['created_at'])) ?></small></td>
                                        <td><strong><?= htmlspecialchars($mov['producto_nombre']) ?></strong></td>
                                        <td><span class="badge <?= $tipoMovCls ?>"><?= strtoupper((string)$mov['tipo']) ?></span></td>
                                        <td class="text-center fw-bold"><?= (int)$mov['cantidad'] ?></td>
                                        <td class="text-center small"><?= (int)$mov['stock_anterior'] ?> → <strong><?= (int)$mov['stock_nuevo'] ?></strong></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($mov['motivo']) ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
