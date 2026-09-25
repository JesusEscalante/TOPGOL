<?php
declare(strict_types=1);

/** @var array<string, mixed> $estadisticas */
/** @var array<int, array<string, mixed>> $recientes */
/** @var array<int, array<string, mixed>> $ocupCanchas */
/** @var array<int, string> $horas */
/** @var array<int, array<string, string>> $ocupacion */

// Nota: $nombreAdmin y $adminMenuActivo los define layout/admin_top.php.

// Fecha larga: Sáb, 26 de abril de 2025
$diasC = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$mesesL = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$tsHoy = strtotime($hoy);
$fechaLarga = $diasC[(int)date('w', $tsHoy)] . ', ' . date('d', $tsHoy) . ' de ' . $mesesL[(int)date('n', $tsHoy)] . ' de ' . date('Y', $tsHoy);

$reservasHoy = (int)($reservasHoy ?? 0);
$pctReservas = (int)($pctReservas ?? 0);
$ingresosHoy = (float)($ingresosHoy ?? 0);
$pctIngresos = (int)($pctIngresos ?? 0);
$pagosVerificar = (int)($pagosVerificar ?? 0);
$ventasProductos = (float)($ventasProductos ?? 340);
$totalDia = $ingresosHoy + $ventasProductos;
$pctReservasDonut = $totalDia > 0 ? (int)round($ingresosHoy / $totalDia * 100) : 0;
$pctProductosDonut = 100 - $pctReservasDonut;

$fmtNum = static fn(float $v): string => number_format($v, 0);
$fmtPct = static function (int $p): string {
    $flecha = $p >= 0 ? '↑' : '↓';
    return $flecha . ' ' . abs($p) . '%';
};

$tipoCorto = ['futbol_5' => 'Fútbol 5', 'futbol_7' => 'Fútbol 7', 'futbol_11' => 'Fútbol 11'];

// Productos demo (sin módulo propio aún)
$productosDemo = [
    ['n' => 'Gatorade',      'ico' => '🥤', 'v' => 12, 's' => 120],
    ['n' => 'Agua San Luis', 'ico' => '🧴', 'v' => 10, 's' => 30],
    ['n' => 'Balón Top Gol', 'ico' => '⚽', 'v' => 4,  's' => 160],
    ['n' => 'Chompitas',     'ico' => '🦺', 'v' => 3,  's' => 90],
    ['n' => 'Guantes',       'ico' => '🧤', 'v' => 2,  's' => 80],
];
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>
            <!-- Saludo -->
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
                <div>
                    <h1 class="adm-h1">¡Hola, <?= htmlspecialchars($nombreAdmin) ?>!</h1>
                    <p class="adm-sub">Aquí tienes un resumen de la operación de Top Gol en Tacna.</p>
                </div>
                <div class="adm-date" id="admDateWrap" style="cursor:pointer;">
                    <span class="cal-ico"><i class="bi bi-calendar3"></i></span>
                    <button type="button" class="adm-date-btn" id="admDateBtn" aria-haspopup="listbox" aria-expanded="false">
                        <span id="admDateLabel"><?= htmlspecialchars($fechaLarga) ?></span>
                        <i class="bi bi-chevron-down chev"></i>
                    </button>
                    <div class="adm-date-dropdown d-none" id="admDateDropdown" role="listbox">
                        <?php
                        $d = $lunes;
                        for($i=0; $i<7; $i++):
                            $ts = strtotime($d);
                            $lbl = $diasC[(int)date('w', $ts)] . ', ' . date('d', $ts) . ' de ' . $mesesL[(int)date('n', $ts)];
                            $isActive = $d === $hoy;
                            $isToday = $d === $hoyReal;
                        ?>
                            <div class="adm-opt <?= $isActive ? 'active' : '' ?> <?= $isToday && !$isActive ? 'today' : '' ?>" data-value="<?= $d ?>" role="option" aria-selected="<?= $isActive ? 'true' : 'false' ?>">
                                <span><span style="font-weight:700;"><?= $diasC[(int)date('w', $ts)] ?></span> <span class="mut"><?= date('d', $ts) ?> <?= $mesesL[(int)date('n', $ts)] ?></span> <?= $isToday ? '<span class="badge ms-2" style="font-size:.62rem; background:#eef7f0; color:#1a7a3a; border:1px solid #bbf7d0; padding:2px 6px; border-radius:20px;">Hoy</span>' : '' ?></span>
                                <?php if($isActive): ?><i class="bi bi-check-lg check"></i><?php endif; ?>
                            </div>
                        <?php $d = date('Y-m-d', strtotime($d . ' +1 day')); endfor; ?>
                    </div>
                </div>
                <script>
                (function(){
                    var wrap=document.getElementById('admDateWrap');
                    var btn=document.getElementById('admDateBtn');
                    var dd=document.getElementById('admDateDropdown');
                    if(!wrap || !btn || !dd) return;
                    function toggle(open){
                        var willOpen = typeof open === 'boolean' ? open : dd.classList.contains('d-none');
                        dd.classList.toggle('d-none', !willOpen);
                        wrap.classList.toggle('open', willOpen);
                        btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                    }
                    btn.addEventListener('click', function(e){ e.stopPropagation(); toggle(); });
                    wrap.addEventListener('click', function(e){
                        if(e.target.closest('#admDateBtn')) return;
                        // si clic en el pill fuera del botón, también abre
                        if(e.target.closest('.adm-date') && !dd.contains(e.target)) toggle();
                    });
                    dd.querySelectorAll('.adm-opt').forEach(function(opt){
                        opt.addEventListener('click', function(){
                            var v=this.getAttribute('data-value');
                            if(!v) return;
                            var url=new URL(window.location.href);
                            url.searchParams.set('fecha', v);
                            window.location.href=url.toString();
                        });
                    });
                    document.addEventListener('click', function(e){
                        if(!wrap.contains(e.target)) toggle(false);
                    });
                    document.addEventListener('keydown', function(e){
                        if(e.key === 'Escape') toggle(false);
                    });
                })();
                </script>
            </div>

            <?php $esHoyReal = ($hoy === $hoyReal); $lblHoy = $esHoyReal ? 'de hoy' : 'del ' . $diasC[(int)date('w', $tsHoy)] . ' ' . date('d', $tsHoy); ?>
            <!-- KPIs -->
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-xl-3">
                    <div class="kpi">
                        <span class="kpi-ico g"><i class="bi bi-calendar-check"></i></span>
                        <div><small class="lbl">Reservas <?= $lblHoy ?></small><div class="val"><?= $reservasHoy ?></div></div>
                        <div class="foot up"><?= $fmtPct($pctReservas) ?><br><span style="color:#7c8aa0;font-weight:400;">vs. ayer</span></div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="kpi">
                        <span class="kpi-ico y"><i class="bi bi-credit-card"></i></span>
                        <div><small class="lbl">Pagos por verificar</small><div class="val"><?= $pagosVerificar ?></div></div>
                        <div class="foot"><a href="<?= url('/reservas') ?>">Requieren revisión →</a></div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="kpi">
                        <span class="kpi-ico g"><i class="bi bi-stack"></i></span>
                        <div><small class="lbl">Ingresos <?= $lblHoy ?></small><div class="val">S/ <?= $fmtNum($ingresosHoy) ?></div></div>
                        <div class="foot up"><?= $fmtPct($pctIngresos) ?><br><span style="color:#7c8aa0;font-weight:400;">vs. ayer</span></div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="kpi">
                        <span class="kpi-ico b"><i class="bi bi-cart-fill"></i></span>
                        <div><small class="lbl">Ventas (productos)</small><div class="val">S/ <?= $fmtNum($ventasProductos) ?></div></div>
                        <div class="foot up">↗ 7%<br><span style="color:#7c8aa0;font-weight:400;">vs. ayer</span></div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <!-- Ocupación -->
                <div class="col-md-6">
                    <div class="panel">
                        <h3>Ocupación de canchas - <?= $esHoyReal ? 'Hoy' : $diasC[(int)date('w', $tsHoy)] . ' ' . date('d', $tsHoy) ?></h3>
                        <div class="psub">Vista rápida de la disponibilidad por horario <?= $esHoyReal ? '' : '· ' . $fechaLarga ?></div>
                        <div class="legend">
                            <span><i class="dot" style="background:#22c55e;"></i>Reservada</span>
                            <span><i class="dot" style="background:#fff;border:2px solid #cbd5e1;"></i>Disponible</span>
                            <span><i class="dot" style="background:#ef4444;"></i>Mantenimiento</span>
                        </div>
                        <div class="table-responsive">
                        <table class="occ-table">
                            <thead>
                                <tr>
                                    <th style="text-align:left;">Hora</th>
                                    <?php $n = 0; foreach ($ocupCanchas as $c): $n++; ?>
                                        <th>Cancha <?= $n ?><br><small><?= htmlspecialchars($tipoCorto[$c['tipo']] ?? $c['tipo']) ?></small></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for($h=7;$h<=23;$h++): $hStr=sprintf('%02d',$h); ?>
                                    <tr>
                                        <th style="font-size:.68rem;"><?= $hStr ?>:00</th>
                                        <?php foreach ($ocupCanchas as $c): ?>
                                            <td>
                                                <span style="display:flex; gap:3px;">
                                                    <?php foreach(['00','30'] as $mm): $slot=$hStr.':'.$mm; $st=$ocupacion[(int)$c['id']][$slot] ?? 'libre'; ?>
                                                        <span class="cell <?= $st==='ocup'?'ocup':($st==='mant'?'mant':'') ?>" style="flex:1; height:12px;" title="<?= $slot ?> - <?= htmlspecialchars($c['nombre']) ?> (<?= $st ?>)"></span>
                                                    <?php endforeach; ?>
                                                </span>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                        </div>
                        <div style="display:flex; gap:12px; font-size:.65rem; color:#7c8aa0; margin-top:8px;">
                            <span>◼ 00-30</span><span>◼ 30-00</span>
                        </div>
                        <a href="<?= url('/admin/calendario') ?>" class="btn-cal"><i class="bi bi-calendar3"></i> Ver calendario completo</a>
                    </div>
                </div>

                <div class="col-lg-6">
                    <!-- Recientes -->
                    <div class="panel-2">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h3>Reservas recientes</h3>
                            <a href="<?= url('/admin/reservas') ?>" class="link-more">Ver todas <i class="bi bi-arrow-right"></i></a>
                        </div>
                        <div class="table-responsive">
                        <table class="tbl">
                            <thead>
                                <tr><th>#</th><th>Fecha y hora</th><th>Cliente</th><th>Cancha</th><th>Estado</th><th>Pago</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recientes)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-3">Sin reservas registradas.</td></tr>
                                <?php else: ?>
                                <?php foreach ($recientes as $r): ?>
                                    <?php
                                        $estLbl = match ($r['estado']) { 'confirmada' => 'Confirmada', 'pendiente' => 'Pendiente', 'cancelada' => 'Cancelada', 'finalizada' => 'Completada', default => ucfirst($r['estado']) };
                                        $estCls = match ($r['estado']) { 'confirmada' => 'ok', 'pendiente' => 'warn', 'cancelada' => 'bad', 'finalizada' => 'fin', default => 'fin' };
                                        $pe = (string)($r['pago_estado'] ?? 'pendiente');
                                        $pagoLbl = match ($pe) { 'verificado' => 'Pagado', 'en_revision' => 'Por verificar', 'rechazado' => 'Rechazado', default => 'Pendiente' };
                                        $pagoCls = match ($pe) { 'verificado' => 'ok', 'en_revision' => 'warn', 'rechazado' => 'bad', default => 'warn' };
                                        $corto = $r['cancha_nombre'];
                                        if (preg_match('/cancha\s*\d+/i', (string)$r['cancha_nombre'], $m)) { $corto = ucwords(strtolower($m[0])); }
                                        $mF = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
                                        $tsF = strtotime($r['fecha']);
                                        $fCorta = date('d', $tsF) . ' ' . $mF[(int)date('n', $tsF)] . ' ' . date('Y', $tsF);
                                    ?>
                                    <tr>
                                        <td class="fw-semibold">#<?= $r['id'] ?></td>
                                        <td class="fh"><?= $fCorta ?><small><?= date('H:i', strtotime($r['hora_inicio'])) ?> - <?= date('H:i', strtotime($r['hora_fin'])) ?></small></td>
                                        <td><?= htmlspecialchars($r['usuario_nombre']) ?></td>
                                        <td><?= htmlspecialchars($corto) ?></td>
                                        <td><span class="pill <?= $estCls ?>"><?= $estLbl ?></span></td>
                                        <td><span class="pill <?= $pagoCls ?>"><?= $pagoLbl ?></span></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="dots-btn" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-three-dots-vertical"></i></button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                    <li><a class="dropdown-item" href="<?= url('/mis-reservas/' . $r['id']) ?>"><i class="bi bi-eye me-2"></i>Ver detalle</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>

                    <div class="row g-3 mt-3">
                        <!-- Productos -->
                        <div class="col-lg-7">
                            <div class="panel">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h3>Productos más vendidos <small style="font-weight:400;color:#7c8aa0;font-size:.75rem;">(hoy)</small></h3>
                                    <a href="#" class="link-more">Ver todas <i class="bi bi-arrow-right"></i></a>
                                </div>
                                <table class="tbl">
                                    <thead><tr><th>#</th><th>Producto</th><th>Ventas</th><th>Ingresos</th></tr></thead>
                                    <tbody>
                                        <?php $i = 0; foreach ($productosDemo as $p): $i++; ?>
                                            <tr>
                                                <td><?= $i ?></td>
                                                <td><span style="font-size:1rem;margin-right:6px;"><?= $p['ico'] ?></span><?= $p['n'] ?></td>
                                                <td><?= $p['v'] ?></td>
                                                <td>S/ <?= $p['s'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Donut -->
                        <div class="col-lg-5">
                            <div class="panel">
                                <h3>Resumen de ventas por tipo</h3>
                                <div class="donut" style="background: conic-gradient(#1a7a3a 0 <?= $pctReservasDonut ?>%, #3b82f6 <?= $pctReservasDonut ?>% 100%);">
                                    <div class="donut-in">
                                        <strong>S/ <?= $fmtNum($totalDia) ?></strong>
                                        <small>Total del día</small>
                                    </div>
                                </div>
                                <div class="dleg">
                                    <div><i class="dot" style="background:#1a7a3a;"></i>Reservas de canchas<small>S/ <?= $fmtNum($ingresosHoy) ?> (<?= $pctReservasDonut ?>%)</small></div>
                                    <div class="mt-1"><i class="dot" style="background:#3b82f6;"></i>Productos<small>S/ <?= $fmtNum($ventasProductos) ?> (<?= $pctProductosDonut ?>%)</small></div>
                                </div>
                                <div class="crec-box">
                                    <i class="bi bi-arrow-up-right" style="color:#1a7a3a;font-size:1.2rem;"></i>
                                    <span><strong>+<?= abs($pctIngresos) ?>%</strong><small>en comparación con ayer</small></span>
                                    <i class="bi bi-bar-chart-fill ms-auto" style="color:#1a7a3a;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
