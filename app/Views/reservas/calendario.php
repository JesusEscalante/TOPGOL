<?php
declare(strict_types=1);

/** @var array<int, string> $dias */
/** @var array<int, string> $horas */
/** @var array<string, array<string, array<int, array<string, mixed>>>> $grilla */
/** @var array<int, array<string, mixed>> $canchas */

$diasN = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
$mesesC = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$hoy = $hoy ?? date('Y-m-d');
$filtroCancha = (int)($filtroCancha ?? 0);

$domingo = $dias[6];
$rango = date('d', strtotime($lunes)) . ' ' . $mesesC[(int)date('n', strtotime($lunes))]
    . ' – ' . date('d', strtotime($domingo)) . ' ' . $mesesC[(int)date('n', strtotime($domingo))] . ' ' . date('Y', strtotime($domingo));

$baseFiltro = $filtroCancha > 0 ? '&cancha=' . $filtroCancha : '';
$urlSemana = static fn(string $fecha): string => url('/admin/calendario?semana=' . $fecha . ($filtroCancha > 0 ? '&cancha=' . $filtroCancha : ''));

$chipCls = static fn(string $e): string => match ($e) {
    'confirmada' => 'ok', 'pendiente' => 'warn',
    'finalizada' => 'fin', default => 'fin',
};
$canchaCorta = static function (string $nombre): string {
    if (preg_match('/cancha\s*\d+/i', $nombre, $m)) {
        return ucwords(strtolower($m[0]));
    }
    return mb_strimwidth($nombre, 0, 14, '…');
};
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<style>
    .cal-nav { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .cal-btn { display: inline-flex; align-items: center; gap: 6px; background: #fff; border: 1px solid #e2e8f0; border-radius: 9px; padding: 8px 14px; font-size: .8rem; font-weight: 700; color: #101c33; text-decoration: none; }
    .cal-btn:hover { border-color: #1a7a3a; color: #1a7a3a; }
    .cal-rango { font-size: .85rem; font-weight: 700; color: #101c33; min-width: 170px; text-align: center; }
    .cal-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .72rem; min-width: 860px; }
    .cal-table thead th { background: #f4f7fb; padding: 8px 6px; text-align: center; border: none; position: sticky; top: 0; }
    .cal-table thead th small { display: block; font-weight: 400; color: #7c8aa0; font-size: .68rem; }
    .cal-table thead th.hoy { background: #d9efe0; color: #166534; }
    .cal-table thead th.hoy small { color: #166534; }
    .cal-table tbody th { text-align: right; color: #7c8aa0; font-weight: 600; white-space: nowrap; padding: 4px 8px 4px 2px; vertical-align: top; width: 52px; }
    .cal-table td { border: 1px solid #eef2f7; padding: 3px; vertical-align: top; min-width: 120px; }
    .cal-table td.hoy { background: #f2faf4; }
    .cal-table td.pasado { background: #f8fafc; }
    .chip { display: block; border-radius: 7px; padding: 4px 6px; margin-bottom: 3px; font-size: .66rem; line-height: 1.3; text-decoration: none; color: #101c33; }
    .chip strong { display: block; font-size: .68rem; }
    .chip small { display: block; color: #5b6b82; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .chip.ok { background: #d9efe0; border-left: 3px solid #1a7a3a; }
    .chip.warn { background: #fbeecb; border-left: 3px solid #d97706; }
    .chip.fin { background: #e2e8f0; border-left: 3px solid #64748b; }
    .chip:hover { filter: brightness(.95); color: #101c33; }
    .mas { font-size: .64rem; color: #7c8aa0; font-weight: 700; padding: 0 4px; }
    .cal-legend { display: flex; gap: 14px; flex-wrap: wrap; font-size: .72rem; color: #7c8aa0; margin: 12px 0 0; }
    .cal-legend i.dot { display: inline-block; width: 11px; height: 11px; border-radius: 50%; margin-right: 5px; }
</style>

            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
                <div>
                    <h1 class="adm-h1">Calendario de reservas</h1>
                    <p class="adm-sub">Ocupación semanal por día y horario.</p>
                </div>
                
            </div>

            <div class="panel-2 mb-4">
                <div class="cal-nav mb-3">
                    <a href="<?= $urlSemana(date('Y-m-d', strtotime($lunes . ' -7 days'))) ?>" class="cal-btn"><i class="bi bi-chevron-left"></i> Anterior</a>
                    <a href="<?= $urlSemana(date('Y-m-d')) ?>" class="cal-btn">Hoy</a>
                    <a href="<?= $urlSemana(date('Y-m-d', strtotime($lunes . ' +7 days'))) ?>" class="cal-btn">Siguiente <i class="bi bi-chevron-right"></i></a>
                    <span class="cal-rango"><?= htmlspecialchars($rango) ?></span>
                    <form action="<?= url('/admin/calendario') ?>" method="GET" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="semana" value="<?= htmlspecialchars($semanaRef) ?>">
                    <select name="cancha" class="form-select form-select-sm" style="border-radius:9px;font-size:.8rem;" onchange="this.form.submit()">
                        <option value="0">Todas las canchas</option>
                        <?php foreach ($canchas as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $filtroCancha === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                </div>
                <div class="table-responsive">
                <table class="cal-table">
                    <thead>
                        <tr>
                            <th style="width:52px;">Hora</th>
                            <?php foreach ($dias as $i => $d): ?>
                                <?php $ts = strtotime($d); ?>
                                <th class="<?= $d === $hoy ? 'hoy' : '' ?>">
                                    <?= $diasN[$i] ?> <?= date('d', $ts) ?>
                                    <small><?= $mesesC[(int)date('n', $ts)] ?> <?= date('Y', $ts) ?></small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for($h=7;$h<=23;$h++): $hStr=sprintf('%02d',$h); ?>
                            <tr>
                                <th><?= $hStr ?>:00</th>
                                <?php foreach ($dias as $d): ?>
                                    <td class="<?= $d === $hoy ? 'hoy' : ($d < $hoy ? 'pasado' : '') ?>">
                                        <div style="display:flex; flex-direction:column; gap:3px;">
                                        <?php foreach(['00','30'] as $mm): $slot=$hStr.':'.$mm; $celda=$grilla[$d][$slot] ?? []; if(empty($celda)) continue; $mostrar=array_slice($celda,0,2); foreach($mostrar as $r): ?>
                                            <a class="chip <?= $chipCls($r['estado']) ?>" href="<?= url('/mis-reservas/' . $r['id']) ?>" title="#<?= $r['id'] ?> · <?= htmlspecialchars($r['usuario_nombre']) ?> · <?= date('H:i', strtotime($r['hora_inicio'])) ?>-<?= date('H:i', strtotime($r['hora_fin'])) ?>">
                                                <strong><?= date('H:i', strtotime($r['hora_inicio'])) ?> <?= htmlspecialchars($canchaCorta((string)$r['cancha_nombre'])) ?></strong>
                                                <small><?= htmlspecialchars($r['usuario_nombre']) ?></small>
                                            </a>
                                        <?php endforeach; if(count($celda)>2): ?><span class="mas">+<?= count($celda)-2 ?> más</span><?php endif; endforeach; ?>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
                </div>
                <div class="cal-legend">
                    <span><i class="dot" style="background:#1a7a3a;"></i>Confirmada</span>
                    <span><i class="dot" style="background:#d97706;"></i>Pendiente</span>
                    <span><i class="dot" style="background:#64748b;"></i>Finalizada</span>
                </div>
            </div>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
