<?php
declare(strict_types=1);

/** @var array<string, mixed> $reserva */
/** @var array<int, array<string, mixed>> $canchas */

$id = (int)$reserva['id'];
$canchaIdActual = (int)$reserva['cancha_id'];
$fechaActual = (string)$reserva['fecha'];
$horaIniActual = substr((string)$reserva['hora_inicio'], 0, 5);
$duracionActual = (float)$reserva['duracion_horas'];
$estadoActual = (string)$reserva['estado'];
$origenActual = (string)($reserva['origen'] ?? 'web');
$clienteActual = (string)($reserva['cliente_nombre'] ?? $reserva['usuario_nombre'] ?? '');
$telefonoActual = (string)($reserva['contacto_telefono'] ?? $reserva['usuario_telefono'] ?? '');
$adelantoActual = (float)($reserva['adelanto_monto'] ?? 0.0);
$obsActual = (string)($reserva['observaciones'] ?? '');
$totalActual = (float)$reserva['total_pago'];

$operativo = calcularEstadoPuntualidad($reserva);
$waLink = generarEnlaceWhatsApp($reserva);
$telDigitos = preg_replace('/\D+/', '', $telefonoActual);

$tipoLabels = ['futbol_5' => 'Fútbol 5', 'futbol_7' => 'Fútbol 7', 'futbol_11' => 'Fútbol 11'];
?>
<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_top.php'; ?>

<style>
    .edit-card { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 6px rgba(16,28,51,.04); }
    .edit-h1 { font-size: 1.5rem; font-weight: 800; color: #101c33; margin: 0; }
    .edit-sub { font-size: .88rem; color: #64748b; margin-top: 4px; }
    .form-label { font-size: .8rem; font-weight: 700; color: #334155; margin-bottom: 5px; }
    .form-control, .form-select { border-radius: 9px; font-size: .88rem; border-color: #cbd5e1; }
    .form-control:focus, .form-select:focus { border-color: #1a7a3a; box-shadow: 0 0 0 3px rgba(26,122,58,.15); }
    .pill-origin { display: inline-flex; align-items: center; gap: 5px; font-size: .75rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; }
    .pill-web { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .pill-presencial { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .pill-llamada { background: #fefce8; color: #854d0e; border: 1px solid #fef08a; }
    .pill-whatsapp { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; }
</style>

<div class="mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h1 class="edit-h1">Reserva #<?= $id ?></h1>
            <span class="pill-origin pill-<?= htmlspecialchars($origenActual) ?>">
                <?php if ($origenActual === 'whatsapp'): ?><i class="bi bi-whatsapp"></i> WhatsApp
                <?php elseif ($origenActual === 'llamada'): ?><i class="bi bi-telephone"></i> Llamada
                <?php elseif ($origenActual === 'presencial'): ?><i class="bi bi-person-badge"></i> Presencial
                <?php else: ?><i class="bi bi-globe"></i> Web
                <?php endif; ?>
            </span>
            <span class="badge bg-<?= $operativo['badge'] ?> ms-1" style="font-size:0.75rem;">
                <?= $operativo['label'] ?>
            </span>
        </div>
        <p class="edit-sub">Gestión integral de edición, control de puntualidad, asistencia y reprogramación de cancha.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/mis-reservas/' . $id) ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:9px;padding:8px 14px;">
            <i class="bi bi-eye me-1"></i> Ver comprobante / detalle
        </a>
        <a href="<?= url('/admin/reservas') ?>" class="btn btn-sm btn-outline-dark" style="border-radius:9px;padding:8px 14px;">
            <i class="bi bi-arrow-left me-1"></i> Volver a Reservas
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- FORMULARIO PRINCIPAL DE EDICIÓN -->
        <div class="edit-card mb-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-pencil-square text-success me-1"></i> Datos Generales de la Reserva</h5>
            <form action="<?= url('/admin/reserva/actualizar/' . $id) ?>" method="POST" id="formEditReserva">
                <?= csrf_field() ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="cliente_nombre" class="form-label">Cliente (Nombre completo) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cliente_nombre" name="cliente_nombre" value="<?= htmlspecialchars($clienteActual) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="contacto_telefono" class="form-label">Teléfono / WhatsApp <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:#f8fafc; font-weight:700; font-size:.85rem; color:#475569;">+51</span>
                            <input type="tel" class="form-control" id="contacto_telefono" name="contacto_telefono" value="<?= htmlspecialchars(preg_replace('/^\+51\s*/', '', $telefonoActual)) ?>" required>
                        </div>
                    </div>
                </div>

                <hr class="my-3" style="border-color:#f1f5f9;">

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="cancha_id" class="form-label">Cancha <span class="text-danger">*</span></label>
                        <select class="form-select" id="cancha_id" name="cancha_id" required>
                            <?php foreach ($canchas as $c): ?>
                                <option value="<?= $c['id'] ?>"
                                    data-precio="<?= $c['precio_hora'] ?>"
                                    data-estado="<?= $c['estado'] ?>"
                                    <?= (int)$c['id'] === $canchaIdActual ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre']) ?> (<?= htmlspecialchars($tipoLabels[$c['tipo']] ?? $c['tipo']) ?>) - S/ <?= number_format((float)$c['precio_hora'], 2) ?>/h
                                    <?= $c['estado'] === 'mantenimiento' ? ' [Mantenimiento]' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="fecha" class="form-label">Fecha de Reserva <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="fecha" name="fecha" value="<?= htmlspecialchars($fechaActual) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label for="hora_inicio" class="form-label">Hora de Inicio <span class="text-danger">*</span></label>
                        <select class="form-select" id="hora_inicio" name="hora_inicio" required>
                            <?php
                                // Una reserva antigua con minutos distintos (por ejemplo 13:20) conserva su hora en la lista
                                $minutosPermitidos = reservaMinutosInicio();
                                $horaActualRara = !in_array(substr($horaIniActual, 3, 2), $minutosPermitidos, true);
                            ?>
                            <?php if ($horaActualRara): ?>
                                <option value="<?= htmlspecialchars($horaIniActual) ?>" selected><?= date('g:i A', strtotime($horaIniActual)) ?> (<?= htmlspecialchars($horaIniActual) ?>) · hora actual, se conserva</option>
                            <?php endif; ?>
                            <?php for ($h = 7; $h <= 23; $h++): ?>
                                <?php foreach ($minutosPermitidos as $mm): ?>
                                    <?php $slot = sprintf('%02d:%s', $h, $mm); ?>
                                    <option value="<?= $slot ?>" <?= $horaIniActual === $slot ? 'selected' : '' ?>>
                                        <?= date('g:i A', strtotime($slot)) ?> (<?= $slot ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="duracion_horas" class="form-label">Duración <span class="text-danger">*</span></label>
                        <select class="form-select" id="duracion_horas" name="duracion_horas" required>
                            <?php foreach ([0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 5, 6] as $d): ?>
                                <?php
                                    $lbl = formatoDuracionHoras((float)$d);
                                ?>
                                <option value="<?= $d ?>" <?= (float)$duracionActual === (float)$d ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="origen" class="form-label">Origen de la Reserva <span class="text-danger">*</span></label>
                        <select class="form-select" id="origen" name="origen" required>
                            <option value="presencial" <?= $origenActual === 'presencial' ? 'selected' : '' ?>>Presencial (Mostrador)</option>
                            <option value="llamada" <?= $origenActual === 'llamada' ? 'selected' : '' ?>>Llamada telefónica</option>
                            <option value="whatsapp" <?= $origenActual === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                            <option value="web" <?= $origenActual === 'web' ? 'selected' : '' ?>>Web (Portal)</option>
                        </select>
                    </div>
                </div>

                <hr class="my-3" style="border-color:#f1f5f9;">

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="estado" class="form-label">Estado de la Reserva <span class="text-danger">*</span></label>
                        <?php $pagoPendienteWeb = (($reserva['origen'] ?? 'web') === 'web' && ($reserva['pago_estado'] ?? 'pendiente') === 'en_revision'); ?>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="pendiente" <?= $estadoActual === 'pendiente' ? 'selected' : '' ?>>Pendiente de revisión</option>
                            <option value="confirmada" <?= $estadoActual === 'confirmada' ? 'selected' : '' ?> <?= $pagoPendienteWeb ? 'disabled' : '' ?>>
                                Confirmada <?= $pagoPendienteWeb ? '(Requiere verificar comprobante)' : '' ?>
                            </option>
                            <option value="finalizada" <?= $estadoActual === 'finalizada' ? 'selected' : '' ?>>Completada / Finalizada</option>
                            <option value="cancelada" <?= $estadoActual === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="adelanto_monto" class="form-label">Adelanto / Monto Cobrado (S/)</label>
                        <input type="number" step="0.50" min="0" class="form-control" id="adelanto_monto" name="adelanto_monto" value="<?= htmlspecialchars((string)$adelantoActual) ?>">
                    </div>

                    <div class="col-12">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="2" placeholder="Notas internas o requerimientos del cliente..."><?= htmlspecialchars($obsActual) ?></textarea>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between pt-3" style="border-top:1px solid #f1f5f9;">
                    <a href="<?= url('/admin/reservas') ?>" class="btn btn-outline-secondary" style="border-radius:9px;padding:9px 18px;">Cancelar</a>
                    <button type="submit" class="btn btn-success fw-bold" style="background:#1a7a3a;border-color:#1a7a3a;border-radius:9px;padding:9px 24px;">
                        <i class="bi bi-check-circle me-1"></i> Guardar Modificaciones
                    </button>
                </div>
            </form>
        </div>

        <!-- MÓDULO FORMAL DE REPROGRAMACIÓN DE RESERVA -->
        <div class="edit-card mb-4" id="seccion-reprogramar" style="border-left:4px solid #0284c7;">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-arrow-repeat text-primary me-1"></i> Reprogramación Formal de Cancha</h5>
                <?php if ($operativo['puede_reprogramar_regla']): ?>
                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Plazo válido (&ge; 1 hora de anticipación)</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i> Fuera de plazo estándar (&lt; 1 hora)</span>
                <?php endif; ?>
            </div>
            <p class="small text-muted mb-3">
                <strong>Política Top Gol:</strong> Un cliente puede solicitar cambio de fecha, hora o cancha si avisa al menos una hora antes del comienzo. El sistema valida disponibilidad estricta sin desplazar otras reservas confirmadas.
            </p>

            <?php if (!empty($reserva['reprogramado_at'])): ?>
                <div class="alert alert-info py-2 px-3 small mb-3" style="border-radius:8px;">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    <strong>Historial de Trazabilidad:</strong> Esta reserva ya fue reprogramada el <strong><?= htmlspecialchars((string)$reserva['reprogramado_at']) ?></strong>.
                    Anteriormente estuvo programada en Cancha #<?= (int)$reserva['reprogramado_de_cancha_id'] ?>, fecha <?= htmlspecialchars((string)$reserva['reprogramado_de_fecha']) ?> a las <?= substr((string)$reserva['reprogramado_de_hora'], 0, 5) ?>.
                    <?php if (!empty($reserva['motivo_reprogramacion'])): ?>
                        <br><em>Motivo registrado: "<?= htmlspecialchars((string)$reserva['motivo_reprogramacion']) ?>"</em>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form action="<?= url('/admin/reserva/reprogramar/' . $id) ?>" method="POST" id="formReprogramar">
                <?= csrf_field() ?>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nueva Cancha <span class="text-danger">*</span></label>
                        <select class="form-select" name="cancha_id" required>
                            <?php foreach ($canchas as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= (int)$c['id'] === $canchaIdActual ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre']) ?> (<?= htmlspecialchars($tipoLabels[$c['tipo']] ?? $c['tipo']) ?>) - S/ <?= number_format((float)$c['precio_hora'], 2) ?>/h
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nueva Fecha <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="fecha" value="<?= htmlspecialchars($fechaActual) ?>" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nueva Hora de Inicio <span class="text-danger">*</span></label>
                        <select class="form-select" name="hora_inicio" required>
                            <?php for ($h = 7; $h <= 23; $h++): ?>
                                <?php foreach (['00', '30'] as $mm): ?>
                                    <?php $slot = sprintf('%02d:%s', $h, $mm); ?>
                                    <option value="<?= $slot ?>" <?= $horaIniActual === $slot ? 'selected' : '' ?>>
                                        <?= date('g:i A', strtotime($slot)) ?> (<?= $slot ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Duración <span class="text-danger">*</span></label>
                        <select class="form-select" name="duracion_horas" required>
                            <?php foreach ([0.5, 1, 1.5, 2, 2.5, 3] as $d): ?>
                                <option value="<?= $d ?>" <?= (float)$duracionActual === (float)$d ? 'selected' : '' ?>>
                                    <?= formatoDuracionHoras((float)$d) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Motivo de la Reprogramación <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="motivo" required placeholder="Ej: Solicitud de cliente por lluvia, fuerza mayor, cambio de horario laboral..." value="<?= htmlspecialchars((string)($reserva['motivo_reprogramacion'] ?? '')) ?>">
                    </div>
                    <?php if (!$operativo['puede_reprogramar_regla']): ?>
                        <div class="col-12">
                            <div class="p-2 rounded bg-warning bg-opacity-10 border border-warning">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="forzar_fuera_de_plazo" value="1" id="checkForzarFueraPlazo">
                                    <label class="form-check-label small fw-bold text-dark" for="checkForzarFueraPlazo">
                                        Autorización administrativa excepcional (solicitada fuera de la regla de 1 hora)
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary fw-bold" style="border-radius:9px; padding:8px 20px;">
                        <i class="bi bi-arrow-repeat me-1"></i> Procesar Reprogramación
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- PANEL LATERAL: CONTROL OPERATIVO, ASISTENCIA Y CONTACTO -->
    <div class="col-lg-4">
        <!-- Tarjeta de Asistencia y Puntualidad -->
        <div class="edit-card mb-3" style="border-top:4px solid #16a34a;">
            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-person-check-fill text-success me-1"></i> Control de Asistencia</h6>

            <div class="p-2 mb-3 rounded" style="background:#f8fafc; border:1px solid #e2e8f0; font-size:0.78rem;">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Horario acordado:</span>
                    <strong class="text-dark"><?= $horaIniActual ?> - <?= substr((string)$reserva['hora_fin'], 0, 5) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Estado actual:</span>
                    <span class="badge bg-<?= $operativo['badge'] ?>"><?= $operativo['label'] ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Tolerancia:</span>
                    <strong class="text-success"><?= toleranciaMinutos() ?> minutos máx.</strong>
                </div>
            </div>

            <!-- Regla estricta -->
            <div class="alert alert-warning p-2 small mb-3" style="font-size:0.72rem; border-radius:8px;">
                <i class="bi bi-shield-exclamation me-1"></i>
                <strong>Aviso:</strong> El alquiler corre desde las <?= $horaIniActual ?> y culmina a las <?= substr((string)$reserva['hora_fin'], 0, 5) ?>. No se extiende por tardanzas.
            </div>

            <form action="<?= url('/admin/reserva/asistencia/' . $id) ?>" method="POST" id="formAsistenciaLateral">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label small mb-1">Marcar asistencia:</label>
                    <select name="asistencia_estado" id="selAsistenciaLateral" class="form-select form-select-sm" required onchange="toggleLateralLiberar(this.value)">
                        <option value="sin_registrar" <?= ($reserva['asistencia_estado'] ?? '') === 'sin_registrar' ? 'selected' : '' ?>>Sin registrar</option>
                        <option value="presente" <?= ($reserva['asistencia_estado'] ?? '') === 'presente' ? 'selected' : '' ?>>Presente (Puntual / Tolerancia)</option>
                        <option value="tardanza" <?= ($reserva['asistencia_estado'] ?? '') === 'tardanza' ? 'selected' : '' ?>>Tardanza (&gt;<?= toleranciaMinutos() ?> min)</option>
                        <option value="retraso_confirmado" <?= ($reserva['asistencia_estado'] ?? '') === 'retraso_confirmado' ? 'selected' : '' ?>>Retraso notificado (En camino)</option>
                        <option value="no_asistio" <?= ($reserva['asistencia_estado'] ?? '') === 'no_asistio' ? 'selected' : '' ?>>No asistió</option>
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label small mb-1">Hora de ingreso:</label>
                    <div class="input-group input-group-sm">
                        <input type="time" name="asistencia_hora" class="form-control" value="<?= !empty($reserva['asistencia_hora']) ? substr((string)$reserva['asistencia_hora'], 0, 5) : '' ?>">
                        <button class="btn btn-outline-secondary" type="button" onclick="this.previousElementSibling.value = new Date().toTimeString().substring(0,5);">Hora actual</button>
                    </div>
                </div>

                <div class="mb-2 p-2 bg-light rounded" id="cajaLiberarLateral" style="display:<?= ($reserva['asistencia_estado'] ?? '') === 'no_asistio' ? 'block' : 'none' ?>; border:1px solid #fee2e2;">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="liberar_cancha" value="1" id="checkLiberarLateral">
                        <label class="form-check-label small fw-bold text-danger" for="checkLiberarLateral" style="font-size:0.75rem;">
                            Liberar cancha inmediatamente (estado cancelada)
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small mb-1">Notas operativas:</label>
                    <input type="text" name="asistencia_notas" class="form-control form-control-sm" placeholder="Ej: Llegó 18:12, avisó tráfico..." value="<?= htmlspecialchars((string)($reserva['asistencia_notas'] ?? '')) ?>">
                </div>

                <button type="submit" class="btn btn-sm btn-success w-100 fw-bold" style="background:#1a7a3a; border-radius:8px;">
                    <i class="bi bi-check2-circle me-1"></i> Actualizar Asistencia
                </button>
            </form>
        </div>

        <!-- Tarjeta de Comunicación y Aviso Previo (~30 min) -->
        <div class="edit-card mb-3">
            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-chat-dots-fill text-primary me-1"></i> Contacto y Aviso Previo</h6>
            <p class="small text-muted mb-2" style="font-size:0.75rem;">
                Recordatorio ~30 min antes. Comunícate directamente con el cliente y registra el resultado:
            </p>

            <div class="d-grid gap-2 mb-3">
                <?php if ($waLink !== '#'): ?>
                    <a href="<?= $waLink ?>" target="_blank" rel="noopener" class="btn btn-sm btn-success" style="background:#16a34a; border-radius:8px; font-weight:700;">
                        <i class="bi bi-whatsapp me-1"></i> Escribir por WhatsApp
                    </a>
                <?php endif; ?>
                <?php if ($telDigitos): ?>
                    <a href="tel:+51<?= $telDigitos ?>" class="btn btn-sm btn-outline-dark" style="border-radius:8px; font-weight:700;">
                        <i class="bi bi-telephone-fill me-1 text-success"></i> Llamar al Cliente (+51 <?= $telDigitos ?>)
                    </a>
                <?php endif; ?>
            </div>

            <!-- Formulario de Registro de Contacto -->
            <form action="<?= url('/admin/reserva/contacto/' . $id) ?>" method="POST">
                <?= csrf_field() ?>
                <label class="form-label small mb-1">Registrar resultado de llamada / WhatsApp:</label>
                <div class="input-group input-group-sm mb-2">
                    <select name="contacto_resultado" class="form-select" required>
                        <option value="confirmada">Confirmó que asistirá a tiempo</option>
                        <option value="retraso">Avisó que llegará con retraso</option>
                        <option value="no_contesta">No contesta llamada / mensajes</option>
                        <option value="solicita_reprogramacion">Solicita reprogramación</option>
                    </select>
                    <button class="btn btn-outline-primary" type="submit">Guardar</button>
                </div>
            </form>

            <?php if (!empty($reserva['contacto_realizado_at'])): ?>
                <div class="small text-muted" style="font-size:0.7rem; border-top:1px dashed #e2e8f0; padding-top:6px;">
                    <i class="bi bi-clock-history me-1"></i> Último contacto: <?= htmlspecialchars((string)$reserva['contacto_realizado_at']) ?> (<?= htmlspecialchars((string)($reserva['contacto_resultado'] ?? 'realizado')) ?>)
                </div>
            <?php endif; ?>
        </div>

        <!-- Resumen Financiero -->
        <div class="edit-card">
            <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-receipt me-1"></i> Resumen de Pago</h6>
            <div class="d-flex justify-content-between mb-2 small">
                <span class="text-muted">Total calculado:</span>
                <strong class="text-dark" id="calcTotal">S/ <?= number_format($totalActual, 2) ?></strong>
            </div>
            <div class="d-flex justify-content-between mb-2 small">
                <span class="text-muted">Adelanto registrado:</span>
                <span class="text-success fw-semibold" id="calcAdelanto">S/ <?= number_format($adelantoActual, 2) ?></span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between small">
                <span class="fw-bold text-dark">Saldo pendiente:</span>
                <strong class="text-danger" id="calcSaldo">S/ <?= number_format(max(0, $totalActual - $adelantoActual), 2) ?></strong>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const canchaSelect = document.getElementById('cancha_id');
        const duracionSelect = document.getElementById('duracion_horas');
        const adelantoInput = document.getElementById('adelanto_monto');
        const calcTotal = document.getElementById('calcTotal');
        const calcAdelanto = document.getElementById('calcAdelanto');
        const calcSaldo = document.getElementById('calcSaldo');

        function recalcular() {
            if (!canchaSelect) return;
            const opt = canchaSelect.options[canchaSelect.selectedIndex];
            const precioHora = opt ? parseFloat(opt.dataset.precio || 60) : 60;
            const dur = parseFloat(duracionSelect.value || 1);
            const total = dur * precioHora;
            const adelanto = parseFloat(adelantoInput.value || 0);
            const saldo = Math.max(0, total - adelanto);

            calcTotal.textContent = 'S/ ' + total.toFixed(2);
            calcAdelanto.textContent = 'S/ ' + adelanto.toFixed(2);
            calcSaldo.textContent = 'S/ ' + saldo.toFixed(2);
        }

        if (canchaSelect) canchaSelect.addEventListener('change', recalcular);
        if (duracionSelect) duracionSelect.addEventListener('change', recalcular);
        if (adelantoInput) adelantoInput.addEventListener('input', recalcular);
    });

    function toggleLateralLiberar(val) {
        const caja = document.getElementById('cajaLiberarLateral');
        if (caja) {
            caja.style.display = (val === 'no_asistio') ? 'block' : 'none';
        }
    }
</script>

<?php require VIEWS_PATH . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'admin_bottom.php'; ?>
