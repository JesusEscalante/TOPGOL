<?php
declare(strict_types=1);
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="adm-h1"><i class="bi bi-chat-dots text-success me-2"></i>Mensajes de Contacto</h1>
        <p class="adm-sub">Consultas, cotizaciones y mensajes enviados por visitantes y clientes desde el formulario de contacto.</p>
    </div>
    <div>
        <a href="<?= url('/admin/dashboard') ?>" class="btn btn-sm btn-outline-secondary fw-bold" style="border-radius:9px; padding:8px 16px;">
            <i class="bi bi-arrow-left me-1"></i> Volver al Inicio
        </a>
    </div>
</div>

<div class="panel-2">
    <div class="table-responsive">
        <table class="tbl">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Remitente</th>
                    <th>Contacto</th>
                    <th>Asunto</th>
                    <th>Mensaje Completo</th>
                    <th>Estado</th>
                    <th class="text-end">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mensajes)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">No hay mensajes de contacto registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($mensajes as $m): ?>
                        <?php
                        $telDigits = preg_replace('/\D+/', '', (string)($m['telefono'] ?? ''));
                        if (strlen($telDigits) === 9) {
                            $telDigits = '51' . $telDigits;
                        }
                        $waUrl = strlen($telDigits) >= 11
                            ? 'https://wa.me/' . $telDigits . '?text=' . urlencode("Hola {$m['nombre']}, te escribimos de TOP GOL en respuesta a tu mensaje sobre '{$m['asunto']}'.")
                            : '';
                        ?>
                        <tr>
                            <td class="fw-bold text-muted">#<?= (int)$m['id'] ?></td>
                            <td class="fh">
                                <strong><?= date('d/m/Y', strtotime((string)$m['created_at'])) ?></strong>
                                <small><?= date('H:i', strtotime((string)$m['created_at'])) ?></small>
                            </td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars((string)$m['nombre']) ?></td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars((string)$m['email']) ?>" class="d-block small text-decoration-none">
                                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars((string)$m['email']) ?>
                                </a>
                                <?php if (!empty($m['telefono'])): ?>
                                    <span class="small text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars((string)$m['telefono']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="pill info"><?= htmlspecialchars((string)$m['asunto']) ?></span></td>
                            <td style="max-width:340px; white-space:pre-wrap;"><?= htmlspecialchars((string)$m['mensaje']) ?></td>
                            <td>
                                <?php if (!empty($m['atendido'])): ?>
                                    <span class="pill ok">Atendido</span>
                                <?php else: ?>
                                    <span class="pill warn">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <?php if ($waUrl !== ''): ?>
                                        <a href="<?= htmlspecialchars($waUrl) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success" title="Responder por WhatsApp">
                                            <i class="bi bi-whatsapp"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (empty($m['atendido'])): ?>
                                        <form action="<?= url('/admin/contactos/atender/' . (int)$m['id']) ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-success fw-semibold" title="Marcar como atendido">
                                                <i class="bi bi-check2"></i> Atendido
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
