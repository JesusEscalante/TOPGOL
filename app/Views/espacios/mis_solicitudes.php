<?php
declare(strict_types=1);
$titulo = $titulo ?? 'Mis Solicitudes de Espacios';
?>
<div class="container py-5">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="mb-0" style="font-weight: 800; color: #101c33;">Mis Solicitudes de Espacios</h2>
            <p class="text-muted mb-0">Revisa el estado de tus solicitudes de cumpleaños y reuniones.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="<?= url('/cumpleanos/solicitar') ?>" class="btn btn-success" style="border-radius: 10px; font-weight: 700;">
                <i class="bi bi-plus-circle me-1"></i> Nueva Solicitud
            </a>
        </div>
    </div>

    <?php if (empty($solicitudes)): ?>
        <div class="alert alert-light text-center py-5 shadow-sm" style="border-radius: 15px; border: 1px dashed #c4cfdb;">
            <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
            <h5 class="text-muted">No tienes solicitudes registradas</h5>
            <p class="text-muted mb-0">Cuando solicites un espacio para cumpleaños o reunión, aparecerá aquí.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($solicitudes as $s): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0" style="border-radius: 15px; transition: transform 0.2s, box-shadow 0.2s; cursor: default;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 15px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 1px 3px rgba(16,28,51,.07)';">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-light text-dark border mb-2">#<?= $s['id'] ?></span>
                                    <h5 class="card-title mb-0 fw-bold" style="color: #101c33;"><?= htmlspecialchars($s['espacio_nombre']) ?></h5>
                                </div>
                                <?php
                                    $cls = match($s['estado']) {
                                        'pendiente' => 'bg-warning text-dark',
                                        'cotizado' => 'bg-info text-dark',
                                        'confirmado' => 'bg-success',
                                        'rechazado', 'cancelado' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                ?>
                                <span class="badge <?= $cls ?> p-2" style="border-radius: 8px;"><?= ucfirst($s['estado']) ?></span>
                            </div>

                            <div class="mb-3" style="font-size: 0.9rem; color: #5b6b82;">
                                <div class="mb-1"><i class="bi bi-calendar-event me-2 text-muted"></i> <strong>Fecha:</strong> <?= date('d/m/Y', strtotime($s['fecha'])) ?></div>
                                <div class="mb-1"><i class="bi bi-clock me-2 text-muted"></i> <strong>Horario:</strong> <?= date('H:i', strtotime($s['hora_inicio'])) ?> (<?= (float)$s['duracion_horas'] ?> h)</div>
                                <div class="mb-1"><i class="bi bi-person me-2 text-muted"></i> <strong>Contacto:</strong> <?= htmlspecialchars($s['contacto_nombre']) ?></div>
                            </div>

                            <?php if ($s['precio_cotizado']): ?>
                            <div class="alert alert-success p-2 mb-0 text-center" style="border-radius: 8px; font-weight: 700;">
                                Precio Cotizado: S/ <?= number_format((float)$s['precio_cotizado'], 2) ?>
                            </div>
                            <?php endif; ?>

                            <?php if ($s['estado'] === 'pendiente'): ?>
                            <div class="text-center mt-3">
                                <small class="text-muted"><i class="bi bi-hourglass-split"></i> En revisión por el administrador</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
