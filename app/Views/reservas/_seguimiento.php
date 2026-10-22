<?php
declare(strict_types=1);

/**
 * Línea de seguimiento con datos reales. Requiere $pasos (ver seguimientoReserva() en helpers.php).
 * Estados de cada paso: completo | actual | pendiente | problema.
 */
?>
<ol class="seg-steps">
    <?php foreach ($pasos as $paso): ?>
        <li class="seg-step <?= $paso['estado'] ?>">
            <span class="seg-dot"><i class="bi <?= $paso['estado'] === 'completo' ? 'bi-check-lg' : ($paso['estado'] === 'problema' ? 'bi-x-lg' : 'bi-circle') ?>"></i></span>
            <span class="seg-txt"><strong><?= htmlspecialchars($paso['titulo']) ?></strong><?php if ($paso['detalle'] !== ''): ?><small><?= htmlspecialchars($paso['detalle']) ?></small><?php endif; ?></span>
        </li>
    <?php endforeach; ?>
</ol>
<style>
.seg-steps { list-style: none; display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px; margin: 0; padding: 0; }
.seg-step { position: relative; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; }
.seg-step::before { content: ''; position: absolute; top: 15px; left: -50%; width: 100%; height: 3px; background: #e2e8f0; z-index: 0; }
.seg-step:first-child::before { display: none; }
.seg-step.completo::before, .seg-step.actual::before { background: #22c55e; }
.seg-dot { position: relative; z-index: 1; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #e2e8f0; color: #94a3b8; font-size: .9rem; }
.seg-step.completo .seg-dot { background: #1a7a3a; color: #fff; }
.seg-step.actual .seg-dot { background: #fff; color: #1a7a3a; border: 3px solid #22c55e; box-shadow: 0 0 0 4px rgba(34,197,94,.18); }
.seg-step.problema .seg-dot { background: #dc2626; color: #fff; }
.seg-txt strong { display: block; font-size: .85rem; color: #101c33; line-height: 1.2; }
.seg-step.pendiente .seg-txt strong { color: #94a3b8; }
.seg-txt small { display: block; font-size: .75rem; color: #64748b; margin-top: 2px; }
@media (max-width: 767px) {
    .seg-steps { grid-template-columns: 1fr; gap: 12px; }
    .seg-step { flex-direction: row; text-align: left; gap: 12px; }
    .seg-step::before { display: none; }
}
</style>
