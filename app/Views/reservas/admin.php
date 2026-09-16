<?php
declare(strict_types=1);

/** @var array<int, array<string, mixed>> $reservas */
/** @var array<string, mixed> $estadisticas */

$filtroEstado = $filtroEstado ?? '';
$filtroPago = $filtroPago ?? '';
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
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<style>
    .f-bar { display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-end; }
    .f-bar .fld { display: flex; flex-direction: column; gap: 4px; }
    .f-bar label { font-size: .68rem; font-weight: 700; color: #7c8aa0; text-transform: uppercase; letter-spacing: .4px; }
    .f-bar .form-control, .f-bar .form-select { font-size: .82rem; border-radius: 9px; }
    .btn-mini { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff; color: #101c33; font-size: .85rem; margin-right: 4px; }
    .btn-mini:hover { border-color: #1a7a3a; color: #1a7a3a; }
    .btn-mini.ok { color: #166534; border-color: #bfe6cc; background: #eef7f0; }
    .btn-mini.ok:hover { background: #1a7a3a; color: #fff; }
    .btn-mini.bad { color: #991b1b; border-color: #f3c2c2; background: #fdf0f0; }
    .btn-mini.bad:hover { background: #dc2626; color: #fff; }
    .est-select { font-size: .72rem; border-radius: 8px; padding: 3px 6px; margin-top: 6px; max-width: 130px; }
    .comp-link { display: inline-flex; align-items: center; gap: 4px; font-size: .72rem; font-weight: 700; color: #1d4ed8; margin-top: 6px; }
</style>

            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-4">
                <div>
                    <h1 class="adm-h1">Reservas</h1>
                    <p class="adm-sub">Gestiona las reservas y verifica los comprobantes de pago.</p>
                </div>
                <a href="<?= url('/reserva/crear') ?>" class="btn btn-sm fw-bold" style="background:#1a7a3a;color:#fff;border-radius:9px;padding:9px 16px;">
                    <i class="bi bi-plus-lg me-1"></i> Registrar reserva
                </a>
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
                        <div class="foot"><a href="<?= url('/admin/reservas?pago=en_revision') ?>">Ver →</a></div>
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
                        <tr><th>#</th><th>Cliente</th><th>Cancha</th><th>Fecha y hora</th><th>Total</th><th>Estado</th><th>Pago</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservas)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No se encontraron reservas con esos filtros.</td></tr>
                        <?php else: ?>
                        <?php foreach ($reservas as $r): ?>
                            <?php
                                $pe = (string)($r['pago_estado'] ?? 'pendiente');
                                $comp = (string)($r['comprobante_ruta'] ?? '');
                                $mF = ['', 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
                                $tsF = strtotime($r['fecha']);
                                $fCorta = date('d', $tsF) . ' ' . $mF[(int)date('n', $tsF)] . ' ' . date('Y', $tsF);
                            ?>
                            <tr>
                                <td class="fw-semibold">#<?= $r['id'] ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($r['usuario_nombre']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($r['usuario_email']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($r['cancha_nombre']) ?></td>
                                <td class="fh"><?= $fCorta ?><small><?= date('H:i', strtotime($r['hora_inicio'])) ?> - <?= date('H:i', strtotime($r['hora_fin'])) ?></small></td>
                                <td class="fw-bold"><?= formatPrice($r['total_pago']) ?></td>
                                <td>
                                    <span class="pill <?= $estCls($r['estado']) ?>"><?= $estLbl($r['estado']) ?></span>
                                    <form action="<?= url('/reserva/estado/' . $r['id']) ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <select name="estado" class="form-select est-select" onchange="this.form.submit()" title="Cambiar estado">
                                            <?php foreach (['pendiente' => 'Pendiente', 'confirmada' => 'Confirmada', 'finalizada' => 'Completada', 'cancelada' => 'Cancelada'] as $v => $l): ?>
                                                <option value="<?= $v ?>" <?= $r['estado'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <span class="pill <?= $pagoCls($pe) ?>"><?= $pagoLbl($pe) ?></span>
                                    <?php if ($comp): ?>
                                        <br><a href="<?= url('/' . ltrim($comp, '/')) ?>" target="_blank" rel="noopener" class="comp-link"><i class="bi bi-paperclip"></i> Comprobante</a>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space:nowrap;">
                                    <a href="<?= url('/mis-reservas/' . $r['id']) ?>" class="btn-mini" title="Ver detalle"><i class="bi bi-eye"></i></a>
                                    <?php if ($pe === 'en_revision'): ?>
                                        <form action="<?= url('/reserva/pago/' . $r['id']) ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="pago_estado" value="verificado">
                                            <button type="submit" class="btn-mini ok" title="Verificar pago"><i class="bi bi-check-lg"></i></button>
                                        </form>
                                        <form action="<?= url('/reserva/pago/' . $r['id']) ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="pago_estado" value="rechazado">
                                            <button type="submit" class="btn-mini bad" title="Rechazar pago"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
