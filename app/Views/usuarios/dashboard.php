<?php
declare(strict_types=1);

/** @var array<string, mixed> $estadisticas */
/** @var array<int, array<string, mixed>> $canchas */
/** @var array<int, array<string, mixed>> $recientes */
/** @var array<int, array<string, mixed>> $ocupCanchas */
/** @var array<int, string> $horas */
/** @var array<int, array<string, string>> $ocupacion */
/** @var string $hoy */
/** @var string $hoyReal */
/** @var string $lunes */
/** @var string $domingo */
/** @var bool $incluirDemo */
/** @var int $reservasHoy */
/** @var int $reservasConfirmadas */
/** @var int $reservasPendientes */
/** @var float $horasCanchaHoy */
/** @var int $pctReservas */
/** @var int $pagosVerificar */
/** @var int $pagosVerificarDia */
/** @var float $ingresosHoy */
/** @var int $pctIngresos */
/** @var float $ventasProductos */
/** @var int $pctVentasProductos */
/** @var float $saldoPendiente */
/** @var int $stockBajoConteo */
/** @var array<int, array<string, mixed>> $productosStockCritico */
/** @var float $valorInventarioPvp */
/** @var int $pctOcupacion */
/** @var array<string, mixed> $cajaHoy */
/** @var array<string, mixed> $ventasHoy */
/** @var array<int, array<string, mixed>> $productosTopHoy */
/** @var array<int, array<string, mixed>> $productosTopHistorico */

// Fecha legible
$diasC = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$mesesL = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$tsHoy = strtotime($hoy);
$fechaLarga = $diasC[(int)date('w', $tsHoy)] . ', ' . date('d', $tsHoy) . ' de ' . $mesesL[(int)date('n', $tsHoy)] . ' de ' . date('Y', $tsHoy);

$fechaAyer = date('Y-m-d', strtotime($hoy . ' -1 day'));
$fechaManana = date('Y-m-d', strtotime($hoy . ' +1 day'));
$esHoyReal = ($hoy === $hoyReal);

$totalDia = $ingresosHoy; // Ingresos totales efectivamente cobrados en el día
$canchasMontoHoy = (float)($cajaHoy['canchas_total'] ?? 0.0);
$prodsMontoHoy   = (float)($cajaHoy['total_productos'] ?? $ventasProductos);

$pctCanchasDonut = $totalDia > 0 ? (int)round(($canchasMontoHoy / $totalDia) * 100) : 0;
$pctProdsDonut   = $totalDia > 0 ? (int)round(($prodsMontoHoy / $totalDia) * 100) : 0;

$fmtNum = static fn(float $v): string => number_format($v, 2);
$fmtPct = static function (int $p): string {
    $flecha = $p >= 0 ? '↑' : '↓';
    return $flecha . ' ' . abs($p) . '%';
};

$tipoCorto = ['futbol_5' => 'Fútbol 5', 'futbol_7' => 'Fútbol 7', 'futbol_11' => 'Fútbol 11'];
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<style>
    .kpi-card { background: #fff; border-radius: 14px; padding: 18px 20px; border: 1px solid #e8eef4; box-shadow: 0 2px 6px rgba(16,28,51,.04); transition: transform .15s, box-shadow .15s; height: 100%; display: flex; flex-direction: column; justify-content: space-between; }
    .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(16,28,51,.08); }
    .kpi-card-header { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
    .kpi-card-title { font-size: .8rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #64748b; margin: 0; }
    .kpi-card-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
    .kpi-icon-green { background: #eef7f0; color: #1a7a3a; }
    .kpi-icon-blue { background: #eff6ff; color: #2563eb; }
    .kpi-icon-amber { background: #fef3c7; color: #d97706; }
    .kpi-icon-red { background: #fee2e2; color: #dc2626; }
    .kpi-icon-purple { background: #f3e8ff; color: #7e22ce; }
    .kpi-card-value { font-size: 2rem; font-weight: 800; color: #101c33; line-height: 1.15; }
    .kpi-card-sub { font-size: .86rem; color: #64748b; margin-top: 6px; }
    .badge-pill-soft { font-size: .78rem; font-weight: 700; padding: 3px 8px; border-radius: 20px; }
    .badge-soft-green { background: #eef7f0; color: #1a7a3a; border: 1px solid #bbf7d0; }
    .badge-soft-amber { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-soft-red { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .badge-soft-blue { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .banner-demo { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; border-radius: 12px; padding: 12px 18px; margin-bottom: 20px; font-size: .84rem; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    /* Atención de hoy: lo urgente arriba */
    .aten-box { background: #fff; border: 1px solid #e8eef4; border-radius: 14px; padding: 16px 18px; margin-bottom: 20px; box-shadow: 0 2px 6px rgba(16,28,51,.04); }
    .aten-title { font-size: 1.05rem; font-weight: 800; margin: 0 0 12px; color: #101c33; }
    .aten-item { display: flex; align-items: center; gap: 12px; height: 100%; padding: 12px 14px; border-radius: 12px; text-decoration: none; border: 1px solid transparent; }
    .aten-item .n { font-size: 1.7rem; font-weight: 800; line-height: 1; min-width: 44px; text-align: center; }
    .aten-item .t { font-size: .92rem; font-weight: 700; line-height: 1.25; }
    .aten-item .t small { display: block; font-weight: 500; font-size: .8rem; opacity: .85; }
    .aten-item.warn { background: #fef3c7; color: #92400e; border-color: #fde68a; }
    .aten-item.bad { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
    .aten-item.ok { background: #eef7f0; color: #166534; border-color: #bbf7d0; }
    .aten-item:hover { filter: brightness(.97); }
    .occ-table { font-size: .82rem; }
    .occ-table .cell { height: 16px !important; }
    @media (max-width: 767px) {
        .kpi-card-value { font-size: 1.8rem; }
        .kpi-card { padding: 16px; }
    }
    .date-pill-nav { display: inline-flex; align-items: center; gap: 6px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 4px; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
</style>

<!-- Banner de Demostración si está activo o detectado -->
<?php if ($incluirDemo): ?>
    <div class="banner-demo">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill fs-5 text-warning"></i>
            <div>
                <strong>Modo Auditoría Demo ACTIVO:</strong>
                <span>Los indicadores en pantalla incluyen transacciones de prueba (ventas demo).</span>
            </div>
        </div>
        <a href="<?= url('/admin/dashboard?fecha=' . urlencode($hoy) . '&demo=0') ?>" class="btn btn-sm btn-outline-warning fw-bold text-dark">
            <i class="bi bi-shield-check me-1"></i> Ver solo datos comerciales reales
        </a>
    </div>
<?php elseif (!empty($ventasHoy['total_demo']) && $ventasHoy['total_demo'] > 0): ?>
    <div class="banner-demo" style="background:#f0fdf4;border-color:#bbf7d0;color:#166534;">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-info-circle-fill fs-5 text-success"></i>
            <div>
                <strong>Datos Reales Protegidos:</strong>
                <span>Hay S/ <?= number_format((float)$ventasHoy['total_demo'], 2) ?> en ventas demo aisladas de esta fecha y excluidas del cálculo comercial.</span>
            </div>
        </div>
        <a href="<?= url('/admin/dashboard?fecha=' . urlencode($hoy) . '&demo=1') ?>" class="btn btn-sm btn-outline-success fw-bold">
            <i class="bi bi-eye me-1"></i> Auditar datos demo
        </a>
    </div>
<?php endif; ?>

<!-- Saludo y Filtros de Fecha -->
<div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
    <div>
        <h1 class="adm-h1">¡Hola, <?= htmlspecialchars($nombreAdmin) ?>!</h1>
        <p class="adm-sub">Panel operativo de Top Gol Tacna · Indicadores en tiempo real desde MySQL.</p>
    </div>

    <!-- Navegación y selector de fecha universal -->
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <form action="<?= url('/admin/dashboard') ?>" method="GET" class="d-flex align-items-center gap-2 m-0">
            <input type="hidden" name="demo" value="<?= $incluirDemo ? '1' : '0' ?>">

            <div class="date-pill-nav">
                <a href="<?= url('/admin/dashboard?fecha=' . $fechaAyer . ($incluirDemo ? '&demo=1' : '')) ?>" class="btn btn-sm btn-light p-1 px-2" title="Día anterior">
                    <i class="bi bi-chevron-left"></i>
                </a>

                <input type="date" name="fecha" value="<?= htmlspecialchars($hoy) ?>" class="form-control form-control-sm border-0 fw-bold text-center" style="width:140px; font-size:.85rem;" onchange="this.form.submit()">

                <a href="<?= url('/admin/dashboard?fecha=' . $fechaManana . ($incluirDemo ? '&demo=1' : '')) ?>" class="btn btn-sm btn-light p-1 px-2" title="Día siguiente">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>

            <?php if (!$esHoyReal): ?>
                <a href="<?= url('/admin/dashboard' . ($incluirDemo ? '?demo=1' : '')) ?>" class="btn btn-sm btn-outline-success fw-bold" style="border-radius:10px;">
                    <i class="bi bi-calendar-event me-1"></i> Hoy
                </a>
            <?php endif; ?>
        </form>

        <a href="<?= url('/admin/reportes') ?>" class="btn btn-sm btn-dark fw-bold" style="border-radius:10px; padding:7px 14px;">
            <i class="bi bi-bar-chart-line me-1"></i> Reportes detallados
        </a>
    </div>
</div>

<!-- ====================================================================
     ATENCIÓN DE HOY: lo que necesita acción primero (mismos datos de abajo, sin cálculos nuevos)
     ==================================================================== -->
<?php $hayPendientes = ($pagosVerificar > 0 || $saldoPendiente > 0 || $reservasPendientes > 0 || $stockBajoConteo > 0); ?>
<div class="aten-box">
    <h2 class="aten-title"><i class="bi bi-lightning-charge-fill me-1 text-warning"></i> Atención de hoy <?= $hayPendientes ? '' : '· <span style="color:#166534;">todo al día</span>' ?></h2>
    <div class="row g-2">
        <div class="col-12 col-md-6 col-xl-3">
            <a href="<?= url('/admin/pagos?tab=revision') ?>" class="aten-item <?= $pagosVerificar > 0 ? 'bad' : 'ok' ?>">
                <span class="n"><?= (int)$pagosVerificar ?></span>
                <span class="t">Comprobantes por verificar<small><?= $pagosVerificar > 0 ? 'Toca para revisarlos' : 'Ninguno pendiente' ?></small></span>
            </a>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <a href="<?= url('/admin/reservas?vista=pendientes') ?>" class="aten-item <?= $reservasPendientes > 0 ? 'warn' : 'ok' ?>">
                <span class="n"><?= (int)$reservasPendientes ?></span>
                <span class="t">Reservas pendientes<small><?= $reservasPendientes > 0 ? 'Aún sin confirmar' : 'Todas confirmadas' ?></small></span>
            </a>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <a href="<?= url('/admin/pagos?tab=pendientes') ?>" class="aten-item <?= $saldoPendiente > 0 ? 'warn' : 'ok' ?>">
                <span class="n" style="font-size:1.3rem;">S/ <?= number_format((float)$saldoPendiente, 0) ?></span>
                <span class="t">Saldo por cobrar<small><?= $saldoPendiente > 0 ? 'Se cobra en la cancha' : 'Sin saldos pendientes' ?></small></span>
            </a>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <a href="<?= url('/admin/inventario') ?>" class="aten-item <?= $stockBajoConteo > 0 ? 'bad' : 'ok' ?>">
                <span class="n"><?= (int)$stockBajoConteo ?></span>
                <span class="t">Productos con poco stock<small><?= $stockBajoConteo > 0 ? 'Ver en Inventario y stock' : 'Inventario en orden' ?></small></span>
            </a>
        </div>
    </div>
</div>

<!-- ====================================================================
     LOS 9 INDICADORES PRINCIPALES DEL DASHBOARD
     ==================================================================== -->
<div class="row g-3 mb-4">
    <!-- 1. Reservas del Día -->
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div>
                <div class="kpi-card-header">
                    <span class="kpi-card-title">Reservas del día</span>
                    <span class="kpi-card-icon kpi-icon-green"><i class="bi bi-calendar-check"></i></span>
                </div>
                <div class="kpi-card-value"><?= $reservasHoy ?></div>
            </div>
            <div class="kpi-card-sub d-flex align-items-center justify-content-between pt-2 border-top">
                <span><?= $horasCanchaHoy ?> hrs-cancha</span>
                <span class="badge-pill-soft <?= $pctReservas >= 0 ? 'badge-soft-green' : 'badge-soft-red' ?>">
                    <?= $fmtPct($pctReservas) ?> vs ayer
                </span>
            </div>
        </div>
    </div>

    <!-- 2. Reservas Confirmadas vs Pendientes -->
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div>
                <div class="kpi-card-header">
                    <span class="kpi-card-title">Estado de reservas</span>
                    <span class="kpi-card-icon kpi-icon-blue"><i class="bi bi-ui-checks-grid"></i></span>
                </div>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="kpi-card-value text-success"><?= $reservasConfirmadas ?></span>
                    <small class="text-muted fw-bold">confirmadas</small>
                </div>
            </div>
            <div class="kpi-card-sub d-flex align-items-center justify-content-between pt-2 border-top">
                <span class="text-warning fw-semibold"><i class="bi bi-clock-history me-1"></i><?= $reservasPendientes ?> pendientes</span>
                <a href="<?= url('/admin/reservas?fecha=' . urlencode($hoy)) ?>" class="text-decoration-none small fw-bold">Ver lista →</a>
            </div>
        </div>
    </div>

    <!-- 3. Comprobantes por Verificar -->
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div>
                <div class="kpi-card-header">
                    <span class="kpi-card-title">Comprobantes por verificar</span>
                    <span class="kpi-card-icon <?= $pagosVerificar > 0 ? 'kpi-icon-amber' : 'kpi-icon-green' ?>"><i class="bi bi-receipt-cutoff"></i></span>
                </div>
                <div class="kpi-card-value <?= $pagosVerificar > 0 ? 'text-warning' : 'text-muted' ?>">
                    <?= $pagosVerificar ?>
                </div>
            </div>
            <div class="kpi-card-sub d-flex align-items-center justify-content-between pt-2 border-top">
                <span><?= $pagosVerificarDia ?> de esta fecha</span>
                <?php if ($pagosVerificar > 0): ?>
                    <a href="<?= url('/admin/pagos') ?>" class="badge-pill-soft badge-soft-amber text-decoration-none">Revisar caja →</a>
                <?php else: ?>
                    <span class="badge-pill-soft badge-soft-green">Al día</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 4. Ingresos Realmente Cobrados -->
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div>
                <div class="kpi-card-header">
                    <span class="kpi-card-title">Ingresos cobrados</span>
                    <span class="kpi-card-icon kpi-icon-green"><i class="bi bi-cash-coin"></i></span>
                </div>
                <div class="kpi-card-value text-success">S/ <?= $fmtNum($ingresosHoy) ?></div>
            </div>
            <div class="kpi-card-sub d-flex align-items-center justify-content-between pt-2 border-top">
                <span>Efectivo + POS + Yape</span>
                <span class="badge-pill-soft <?= $pctIngresos >= 0 ? 'badge-soft-green' : 'badge-soft-red' ?>">
                    <?= $fmtPct($pctIngresos) ?> vs ayer
                </span>
            </div>
        </div>
    </div>

    <!-- 5. Ventas de Productos (Kiosco / POS) -->
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div>
                <div class="kpi-card-header">
                    <span class="kpi-card-title">Ventas de productos</span>
                    <span class="kpi-card-icon kpi-icon-blue"><i class="bi bi-cart-check"></i></span>
                </div>
                <div class="kpi-card-value">S/ <?= $fmtNum($ventasProductos) ?></div>
            </div>
            <div class="kpi-card-sub d-flex align-items-center justify-content-between pt-2 border-top">
                <span><?= (int)($ventasHoy['total_transacciones'] ?? 0) ?> ventas registradas</span>
                <a href="<?= url('/admin/ventas/pos') ?>" class="text-decoration-none small fw-bold">POS →</a>
            </div>
        </div>
    </div>

    <!-- 6. Saldo Pendiente de Reservas -->
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div>
                <div class="kpi-card-header">
                    <span class="kpi-card-title">Saldo pendiente reservas</span>
                    <span class="kpi-card-icon <?= $saldoPendiente > 0 ? 'kpi-icon-amber' : 'kpi-icon-green' ?>"><i class="bi bi-wallet2"></i></span>
                </div>
                <div class="kpi-card-value <?= $saldoPendiente > 0 ? 'text-warning' : 'text-muted' ?>">
                    S/ <?= $fmtNum($saldoPendiente) ?>
                </div>
            </div>
            <div class="kpi-card-sub d-flex align-items-center justify-content-between pt-2 border-top">
                <span>Por cobrar en cancha</span>
                <a href="<?= url('/admin/pagos') ?>" class="text-decoration-none small fw-bold">Cobrar →</a>
            </div>
        </div>
    </div>

    <!-- 7. Ocupación de Canchas -->
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div>
                <div class="kpi-card-header">
                    <span class="kpi-card-title">Ocupación de canchas</span>
                    <span class="kpi-card-icon kpi-icon-purple"><i class="bi bi-pie-chart"></i></span>
                </div>
                <div class="kpi-card-value"><?= $pctOcupacion ?>%</div>
            </div>
            <div class="kpi-card-sub d-flex align-items-center justify-content-between pt-2 border-top">
                <span><?= $horasCanchaHoy ?>h ocupadas / <?= $capacidadHorasDia ?? 84 ?>h base</span>
                <span class="badge-pill-soft badge-soft-blue" title="Horario provisional referencial"><?= htmlspecialchars($horarioBase['descripcion'] ?? 'Lun-Vie 09-23h | Sáb-Dom 07-23h') ?></span>
            </div>
        </div>
    </div>

    <!-- 8. Productos con Stock Bajo -->
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card">
            <div>
                <div class="kpi-card-header">
                    <span class="kpi-card-title">Stock bajo / Alertas</span>
                    <span class="kpi-card-icon <?= $stockBajoConteo > 0 ? 'kpi-icon-red' : 'kpi-icon-green' ?>"><i class="bi bi-exclamation-octagon"></i></span>
                </div>
                <div class="kpi-card-value <?= $stockBajoConteo > 0 ? 'text-danger' : 'text-success' ?>">
                    <?= $stockBajoConteo ?>
                </div>
            </div>
            <div class="kpi-card-sub d-flex align-items-center justify-content-between pt-2 border-top">
                <span><?= $stockBajoConteo > 0 ? 'Ítems críticos' : 'Inventario óptimo' ?></span>
                <a href="<?= url('/admin/inventario') ?>" class="text-decoration-none small fw-bold">Kardex →</a>
            </div>
        </div>
    </div>
</div>

<!-- ====================================================================
     SECCIÓN INTERMEDIA: OCUPACIÓN DE CANCHAS Y RESERVAS RECIENTES
     ==================================================================== -->
<div class="row g-3 mb-4">
    <!-- Tabla de Ocupación Horaria (07:00 a 24:00) -->
    <div class="col-xl-6">
        <div class="panel">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div>
                    <h3 class="m-0">Ocupación de canchas · <?= htmlspecialchars($fechaLarga) ?></h3>
                    <div class="psub">Grilla de bloques de 30 min (07:00 a 24:00). Base provisional: <?= htmlspecialchars($horarioBase['descripcion'] ?? '') ?> *</div>
                </div>
                <a href="<?= url('/admin/calendario') ?>" class="btn btn-sm btn-outline-dark fw-bold" style="border-radius:8px;">
                    <i class="bi bi-calendar3 me-1"></i> Calendario
                </a>
            </div>

            <div class="legend mb-2">
                <span><i class="dot" style="background:#22c55e;"></i>Reservada</span>
                <span><i class="dot" style="background:#fff;border:2px solid #cbd5e1;"></i>Disponible</span>
                <span><i class="dot" style="background:#ef4444;"></i>Mantenimiento</span>
            </div>

            <div class="table-responsive" style="max-height: 440px; overflow-y: auto;">
                <table class="occ-table">
                    <thead>
                        <tr>
                            <th style="text-align:left; position:sticky; top:0; background:#f8fafc; z-index:2;">Hora</th>
                            <?php $n = 0; foreach ($ocupCanchas as $c): $n++; ?>
                                <th style="position:sticky; top:0; background:#f8fafc; z-index:2;">
                                    <?= htmlspecialchars($c['nombre']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($tipoCorto[$c['tipo']] ?? $c['tipo']) ?></small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($h = 7; $h <= 23; $h++): $hStr = sprintf('%02d', $h); ?>
                            <tr>
                                <th style="font-size:.8rem;"><?= $hStr ?>:00</th>
                                <?php foreach ($ocupCanchas as $c): ?>
                                    <td>
                                        <span style="display:flex; gap:3px;">
                                            <?php foreach (['00', '30'] as $mm):
                                                $slot = $hStr . ':' . $mm;
                                                $st = $ocupacion[(int)$c['id']][$slot] ?? 'libre';
                                            ?>
                                                <span class="cell <?= $st === 'ocup' ? 'ocup' : ($st === 'mant' ? 'mant' : '') ?>"
                                                      style="flex:1; border-radius:3px;"
                                                      title="<?= $slot ?> - <?= htmlspecialchars($c['nombre']) ?> (<?= $st === 'ocup' ? 'Ocupada' : ($st === 'mant' ? 'Mantenimiento' : 'Libre') ?>)"></span>
                                            <?php endforeach; ?>
                                        </span>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-1" style="font-size:.8rem; color:#64748b;">
                <span>◼ Bloque 1: :00 a :30 &nbsp;|&nbsp; ◼ Bloque 2: :30 a :00</span>
                <span><?= $horasCanchaHoy ?> horas-cancha ocupadas (Capacidad base: <?= $capacidadHorasDia ?? 84 ?>h)</span>
            </div>
            <div class="mt-2 text-muted" style="font-size:.78rem;">
                * <em>Horarios provisionales para cálculo operativo, preparados para confirmación final por la empresa. El encargado puede registrar reservas extraordinarias fuera de este intervalo.</em>
            </div>
        </div>
    </div>

    <!-- Reservas Recientes del Sistema -->
    <div class="col-xl-6">
        <div class="panel-2">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h3 class="m-0">Reservas recientes</h3>
                    <div class="psub">Últimas operaciones ingresadas al sistema</div>
                </div>
                <a href="<?= url('/admin/reservas') ?>" class="link-more">Ver todas <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="table-responsive">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fecha y hora</th>
                            <th>Cliente</th>
                            <th>Cancha</th>
                            <th>Estado</th>
                            <th>Pago</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recientes)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No existen reservas registradas en el sistema.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recientes as $r): ?>
                                <?php
                                    $estLbl = match ($r['estado']) { 'confirmada' => 'Confirmada', 'pendiente' => 'Pendiente', 'cancelada' => 'Cancelada', 'finalizada' => 'Completada', default => ucfirst($r['estado']) };
                                    $estCls = match ($r['estado']) { 'confirmada' => 'ok', 'pendiente' => 'warn', 'cancelada' => 'bad', 'finalizada' => 'fin', default => 'fin' };
                                    $pe = (string)($r['pago_estado'] ?? 'pendiente');
                                    $pagoLbl = match ($pe) { 'verificado' => 'Pagado', 'en_revision' => 'Por verificar', 'rechazado' => 'Rechazado', default => 'Pendiente' };
                                    $pagoCls = match ($pe) { 'verificado' => 'ok', 'en_revision' => 'warn', 'rechazado' => 'bad', default => 'warn' };
                                    $saldoR = (float)($r['saldo_pendiente'] ?? 0.0);
                                ?>
                                <tr>
                                    <td class="fw-semibold">#<?= $r['id'] ?></td>
                                    <td class="fh">
                                        <?= date('d/m/Y', strtotime($r['fecha'])) ?>
                                        <small><?= date('H:i', strtotime($r['hora_inicio'])) ?> - <?= date('H:i', strtotime($r['hora_fin'])) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($r['usuario_nombre']) ?></td>
                                    <td><small class="fw-semibold"><?= htmlspecialchars($r['cancha_nombre']) ?></small></td>
                                    <td><span class="pill <?= $estCls ?>"><?= $estLbl ?></span></td>
                                    <td><span class="pill <?= $pagoCls ?>"><?= $pagoLbl ?></span></td>
                                    <td class="fw-bold <?= $saldoR > 0 ? 'text-warning' : 'text-success' ?>">
                                        S/ <?= number_format($saldoR, 2) ?>
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

<!-- ====================================================================
     SECCIÓN INFERIOR: PRODUCTOS MÁS VENDIDOS Y BALANCE COMERCIAL
     ==================================================================== -->
<div class="row g-3">
    <!-- Ranking de Productos Más Vendidos -->
    <div class="col-lg-7">
        <div class="panel">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h3 class="m-0">Productos más vendidos</h3>
                    <div class="psub">
                        <?php if (!empty($productosTopHoy)): ?>
                            Ventas registradas el <?= htmlspecialchars($fechaLarga) ?>
                        <?php else: ?>
                            Ventas acumuladas históricas en el bar/kiosco
                        <?php endif; ?>
                        <?php if ($incluirDemo): ?>
                            <span class="badge bg-warning text-dark ms-1"><i class="bi bi-flask me-1"></i>Modo Demo</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/admin/ventas/pos') ?>" class="btn btn-sm btn-outline-success fw-bold">
                        <i class="bi bi-cart-plus me-1"></i> Nueva venta
                    </a>
                    <a href="<?= url('/admin/productos') ?>" class="btn btn-sm btn-outline-secondary">
                        Catálogo
                    </a>
                </div>
            </div>

            <?php
            $listaProdsMostrar = !empty($productosTopHoy) ? $productosTopHoy : $productosTopHistorico;
            ?>

            <?php if (empty($listaProdsMostrar)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-cart-x fs-1 d-block mb-2"></i>
                    No se registran ventas comerciales de productos todavía.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Unidades</th>
                                <th>Stock actual</th>
                                <th>Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rk = 0; foreach ($listaProdsMostrar as $p): $rk++; ?>
                                <tr>
                                    <td class="fw-bold text-muted"><?= $rk ?></td>
                                    <td class="fw-semibold">
                                        <i class="bi <?= htmlspecialchars($p['categoria_icono'] ?? 'bi-box') ?> text-success me-1"></i>
                                        <?= htmlspecialchars($p['nombre']) ?>
                                    </td>
                                    <td><small class="badge bg-light text-dark border"><?= htmlspecialchars($p['categoria_nombre']) ?></small></td>
                                    <td class="fw-bold"><?= (int)$p['unidades_vendidas'] ?> <?= htmlspecialchars($p['unidad'] ?? 'und') ?></td>
                                    <td>
                                        <?php $stk = (int)($p['stock_actual'] ?? 0); ?>
                                        <span class="badge <?= $stk <= 5 ? 'bg-danger' : 'bg-secondary' ?>"><?= $stk ?></span>
                                    </td>
                                    <td class="fw-bold text-success">S/ <?= number_format((float)$p['total_ingresos'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resumen Comercial y Alertas Críticas -->
    <div class="col-lg-5">
        <div class="panel mb-3">
            <h3 class="mb-2">Composición de ingresos del día</h3>
            <div class="psub mb-3">Distribución entre alquiler de canchas y ventas de bar</div>

            <?php if ($totalDia <= 0): ?>
                <div class="text-center py-3 text-muted">
                    <i class="bi bi-wallet fs-2 d-block mb-1"></i>
                    Sin ingresos cobrados en esta fecha (S/ 0.00).
                </div>
            <?php else: ?>
                <div class="donut mb-3" style="background: conic-gradient(#1a7a3a 0 <?= $pctCanchasDonut ?>%, #2563eb <?= $pctCanchasDonut ?>% 100%);">
                    <div class="donut-in">
                        <strong>S/ <?= $fmtNum($totalDia) ?></strong>
                        <small>Cobrado en la fecha</small>
                    </div>
                </div>
                <div class="dleg">
                    <div>
                        <i class="dot" style="background:#1a7a3a;"></i>
                        <strong>Alquiler de Canchas:</strong> S/ <?= $fmtNum($canchasMontoHoy) ?> (<?= $pctCanchasDonut ?>%)
                    </div>
                    <div class="mt-1">
                        <i class="dot" style="background:#2563eb;"></i>
                        <strong>Ventas de Productos:</strong> S/ <?= $fmtNum($prodsMontoHoy) ?> (<?= $pctProdsDonut ?>%)
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
