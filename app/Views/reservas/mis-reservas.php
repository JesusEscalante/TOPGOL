<?php
declare(strict_types=1);
?>

<div class="container py-4 det-page">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
        <div>
            <h1 class="det-h1">Mis reservas</h1>
            <p class="det-sub">Consulta el estado de tus reservas y administra tus próximas canchas.</p>
        </div>
        <div class="text-md-end">
            <a href="<?= url('/canchas') ?>" class="btn-nueva">
                <i class="bi bi-plus-lg me-1"></i> Nueva reserva
            </a>
        </div>
    </div>

    <?php if (empty($reservas)): ?>
        <div class="det-card text-center p-5 my-4">
            <div class="mb-3">
                <i class="bi bi-calendar-x text-muted display-1"></i>
            </div>
            <h4 class="fw-bold text-dark">Aún no tienes reservas registradas</h4>
            <p class="text-muted mb-4">¿Listo para jugar un partido? Elige una de nuestras canchas de fútbol 5, 7 u 11.</p>
            <div>
                <a href="<?= url('/canchas') ?>" class="btn-nueva">
                    <i class="bi bi-dribbble me-1"></i> Explorar canchas disponibles
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="det-card">
            <div class="table-responsive">
                <table class="table det-table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Código</th>
                            <th>Cancha</th>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Duración</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservas as $r): ?>
                            <?php
                                $estadoCls = match($r['estado']) {
                                    'confirmada' => 'ok',
                                    'pendiente'  => 'warn',
                                    'cancelada'  => 'bad',
                                    'finalizada' => 'fin',
                                    default      => 'mut'
                                };
                                $puedeCancelar = in_array($r['estado'], ['pendiente', 'confirmada'], true) && ($r['fecha'] >= date('Y-m-d'));
                            ?>
                            <tr>
                                <td class="ps-3 fw-bold text-dark">#<?= $r['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($r['cancha_nombre']) ?></div>
                                    <small class="text-muted"><?= strtoupper(str_replace('_', ' ', $r['cancha_tipo'])) ?></small>
                                </td>
                                <td>
                                    <i class="bi bi-calendar3 text-secondary me-1"></i>
                                    <?= date('d/m/Y', strtotime($r['fecha'])) ?>
                                </td>
                                <td>
                                    <i class="bi bi-clock text-secondary me-1"></i>
                                    <?= date('h:i A', strtotime($r['hora_inicio'])) ?> - <?= date('h:i A', strtotime($r['hora_fin'])) ?>
                                </td>
                                <td><?= $r['duracion_horas'] ?> <?= $r['duracion_horas'] == 1 ? 'hora' : 'horas' ?></td>
                                <td class="fw-bold text-success"><?= formatPrice($r['total_pago']) ?></td>
                                <td>
                                    <span class="pill <?= $estadoCls ?>">
                                        <?= ucfirst($r['estado']) ?>
                                    </span>
                                </td>
                                <td class="text-end pe-3" style="white-space:nowrap;">
                                    <a href="<?= url('/mis-reservas/' . $r['id']) ?>" class="btn-ver">
                                        <i class="bi bi-eye me-1"></i> Ver detalle
                                    </a>
                                    <?php if ($puedeCancelar): ?>
                                        <a href="<?= url('/reserva/cancelar/' . $r['id']) ?>" class="btn-ver danger btn-confirmar-eliminar" data-confirm="¿Deseas cancelar la reserva #<?= $r['id'] ?> para la <?= htmlspecialchars($r['cancha_nombre']) ?>?">
                                            <i class="bi bi-x-circle me-1"></i> Cancelar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.det-page { background: #f6f9fb; border-radius: 12px; }
.det-h1 { font-size: 1.9rem; font-weight: 800; color: #101c33; margin: 0; }
.det-sub { color: #7c8aa0; margin: 2px 0 0; }
.det-crumb { font-size: .75rem; color: #7c8aa0; margin-bottom: 6px; }
.det-crumb a { color: #101c33; font-weight: 600; }
.det-crumb .current { color: #7c8aa0; }
.btn-nueva { display: inline-flex; align-items: center; background: #1a7a3a; color: #fff; font-weight: 700; font-size: .85rem; border-radius: 9px; padding: 9px 16px; }
.btn-nueva:hover { background: #135c2b; color: #fff; }
.det-card { background: #fff; border: 1px solid #e8eef4; border-radius: 14px; padding: 18px; box-shadow: 0 6px 18px rgba(16,28,51,.06); overflow: hidden; }
.det-table thead th { font-size: .72rem; text-transform: uppercase; letter-spacing: .4px; background: #f4f7fb; color: #5b6b82; border: none; white-space: nowrap; }
.det-table td { font-size: .8rem; border-color: #eef2f7; vertical-align: middle; }
.pill { display: inline-block; font-size: .68rem; font-weight: 700; border-radius: 8px; padding: 3px 10px; }
.pill.ok { background: #d9efe0; color: #166534; }
.pill.warn { background: #fbeecb; color: #92400e; }
.pill.bad { background: #fbdcdc; color: #991b1b; }
.pill.fin { background: #e2e8f0; color: #475569; }
.pill.mut { background: #f1f5f9; color: #64748b; }
.btn-ver { display: inline-block; border: 1px solid #cbd5e1; border-radius: 8px; padding: 5px 12px; font-size: .75rem; font-weight: 600; color: #101c33; margin-right: 4px; }
.btn-ver:hover { border-color: #1a7a3a; color: #1a7a3a; }
.btn-ver.danger { color: #dc2626; border-color: #f3c2c2; }
.btn-ver.danger:hover { border-color: #dc2626; color: #dc2626; }
@media (max-width: 991px) {
    .det-h1 { font-size: 1.5rem; }
}
</style>
