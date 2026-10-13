<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 text-gray-800">Gestión de Solicitudes de Espacios</h2>
    </div>

    <div class="card shadow-sm border-0" style="border-radius: 12px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Espacio</th>
                            <th>Cliente</th>
                            <th>Fecha y Hora</th>
                            <th>Duración</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($solicitudes)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No hay solicitudes de espacios.</td></tr>
                        <?php else: ?>
                            <?php foreach ($solicitudes as $s): ?>
                            <tr>
                                <td>#<?= $s['id'] ?></td>
                                <td><?= htmlspecialchars($s['espacio_nombre']) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($s['contacto_nombre']) ?></div>
                                    <small class="text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($s['contacto_telefono']) ?></small>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($s['fecha'])) ?>
                                    <br>
                                    <small class="text-muted"><?= date('H:i', strtotime($s['hora_inicio'])) ?></small>
                                </td>
                                <td><?= (float)$s['duracion_horas'] ?> h</td>
                                <td>
                                    <?php
                                        $cls = match($s['estado']) {
                                            'pendiente' => 'bg-warning text-dark',
                                            'cotizado' => 'bg-info text-dark',
                                            'confirmado' => 'bg-success',
                                            'rechazado', 'cancelado' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $cls ?>"><?= ucfirst($s['estado']) ?></span>
                                    <?php if ($s['precio_cotizado']): ?>
                                        <br><small class="text-success fw-bold">S/ <?= number_format((float)$s['precio_cotizado'], 2) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form action="<?= url('/admin/espacios/estado/' . $s['id']) ?>" method="POST" class="d-inline-flex gap-2 align-items-center flex-wrap">
                                        <?= csrf_field() ?>
                                        <select name="estado" class="form-select form-select-sm" style="width: auto;">
                                            <option value="pendiente" <?= $s['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                            <option value="cotizado" <?= $s['estado'] === 'cotizado' ? 'selected' : '' ?>>Cotizado</option>
                                            <option value="confirmado" <?= $s['estado'] === 'confirmado' ? 'selected' : '' ?>>Confirmado</option>
                                            <option value="rechazado" <?= $s['estado'] === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                                            <option value="cancelado" <?= $s['estado'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                        </select>
                                        <input type="number" step="0.01" min="0" name="precio_cotizado" class="form-control form-control-sm" placeholder="Precio (S/)" value="<?= $s['precio_cotizado'] ?? '' ?>" style="width: 90px;" title="Precio acordado">
                                        <button type="submit" class="btn btn-sm btn-primary" title="Guardar cambios">Guardar</button>
                                    </form>
                                    <?php if (!empty($s['observaciones'])): ?>
                                        <button class="btn btn-sm btn-outline-secondary mt-1" onclick="alert('Observaciones:\n<?= htmlspecialchars(addslashes($s['observaciones'])) ?>')" title="Ver observaciones">
                                            <i class="bi bi-chat-text"></i>
                                        </button>
                                    <?php endif; ?>
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
