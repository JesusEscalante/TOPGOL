<?php
declare(strict_types=1);

/**
 * Detalle de una reserva para el ADMINISTRADOR (se incluye desde detalle.php).
 * El cliente sigue viendo su vista de siempre.
 *
 * Variables que llegan de detalle.php: $reserva, $id, $estado, $total, $totalCobrado, $saldo,
 * $pagoEstado, $metodoPago, $comprobanteRuta, $canchaNombre, $tipo, $fechaLarga, $horaIni, $horaFin,
 * $historialPagos, $fmtFechaHora.
 */
$clienteNombre = (string)(!empty($reserva['cliente_nombre']) ? $reserva['cliente_nombre'] : ($reserva['usuario_nombre'] ?? 'Cliente'));
$telefono = (string)(!empty($reserva['contacto_telefono']) ? $reserva['contacto_telefono'] : ($reserva['usuario_telefono'] ?? ''));
$waCliente = generarEnlaceWhatsApp($reserva, 'consulta'); // WhatsApp AL CLIENTE
$grupo = (string)($reserva['evento_grupo'] ?? '');
$etqPago = etiquetaPagoReserva($total, $totalCobrado, $pagoEstado);
$chipPago = trim(metodoPagoTexto($metodoPago) . ' · ' . $etqPago['texto'], ' ·');
$pasos = seguimientoReserva($reserva, $totalCobrado);
$operativo = calcularEstadoPuntualidad($reserva);
$canchasLista = (new \App\Models\Cancha())->obtenerTodas();

$mins = (int)round((float)$reserva['duracion_horas'] * 60);
$durTxt = formatoDuracion($mins);

$estadoTxt = match ($estado) {
    'confirmada' => 'Reserva confirmada',
    'pendiente'  => 'Reserva pendiente de confirmación',
    'cancelada'  => 'Reserva cancelada',
    'finalizada' => 'Reserva finalizada',
    default      => 'Reserva registrada',
};
$estadoCls = match ($estado) { 'confirmada' => 'ok', 'pendiente' => 'warn', 'cancelada' => 'bad', default => 'fin' };

$asist = (string)($reserva['asistencia_estado'] ?? 'sin_registrar');
$asistTxt = match ($asist) {
    'presente' => 'Llegó a tiempo', 'tardanza' => 'Llegó tarde', 'retraso_confirmado' => 'Avisó que viene en camino',
    'no_asistio' => 'No asistió', default => 'Sin registrar',
};
$asistCls = match ($asist) { 'presente' => 'ok', 'tardanza' => 'warn', 'retraso_confirmado' => 'info', 'no_asistio' => 'bad', default => 'fin' };
$activa = in_array($estado, ['pendiente', 'confirmada'], true);
$origenTxt = match ((string)($reserva['origen'] ?? 'web')) { 'whatsapp' => 'WhatsApp', 'llamada' => 'Llamada', 'presencial' => 'Presencial', default => 'Web' };
$tipoLabelsAdm = ['futbol_5' => 'Fútbol 5', 'futbol_7' => 'Fútbol 7', 'futbol_11' => 'Fútbol 11'];
$horaActualSel = substr((string)$reserva['hora_inicio'], 0, 5);
?>
<div class="container py-4 da-page">
    <a href="<?= url('/admin/reservas') ?>" class="da-back"><i class="bi bi-arrow-left me-1"></i> Volver a reservas</a>

    <!-- Encabezado -->
    <div class="da-head">
        <div>
            <h1 class="da-h1">Reserva #<?= $id ?> · <?= htmlspecialchars($clienteNombre) ?></h1>
            <div class="da-chips">
                <span class="pill <?= $estadoCls ?>"><?= $estadoTxt ?></span>
                <span class="pill <?= $etqPago['cls'] ?>" title="Etiqueta calculada con los montos verificados"><i class="bi bi-wallet2 me-1"></i><?= htmlspecialchars($chipPago) ?></span>
                <span class="pill <?= $asistCls ?>"><i class="bi bi-person-check me-1"></i><?= $asistTxt ?></span>
                <?php if ($grupo !== ''): ?><span class="pill info"><i class="bi bi-grid me-1"></i>Evento de varias canchas</span><?php endif; ?>
            </div>
        </div>
        <div class="da-contacto">
            <?php if ($telefono !== ''): ?>
                <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $telefono)) ?>" class="da-tel"><i class="bi bi-telephone-fill me-1"></i><?= htmlspecialchars($telefono) ?></a>
            <?php endif; ?>
            <?php if ($waCliente !== '#'): ?>
                <a href="<?= $waCliente ?>" target="_blank" rel="noopener" class="da-btn wa"><i class="bi bi-whatsapp me-1"></i> WhatsApp al cliente</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Seguimiento: datos reales -->
    <div class="da-card">
        <h2 class="da-h2">Seguimiento</h2>
        <?php require __DIR__ . '/_seguimiento.php'; ?>
    </div>

    <div class="row g-3">
        <!-- Datos de la reserva -->
        <div class="col-lg-6">
            <div class="da-card h-100">
                <h2 class="da-h2">Datos de la reserva</h2>
                <div class="da-row"><span>Cancha</span><strong><?= htmlspecialchars($canchaNombre) ?> <small class="text-muted">(<?= htmlspecialchars($tipo) ?>)</small></strong></div>
                <div class="da-row"><span>Fecha</span><strong><?= htmlspecialchars($fechaLarga) ?></strong></div>
                <div class="da-row"><span>Horario</span><strong><?= $horaIni ?> – <?= $horaFin ?> <small class="text-muted">(<?= $durTxt ?>)</small></strong></div>
                <div class="da-row"><span>Origen</span><strong><?= $origenTxt ?></strong></div>
                <form action="<?= url('/admin/reserva/actualizar/' . $id) ?>" method="POST" class="da-once mt-2">
                    <?= csrf_field() ?>
                    <label for="notasReserva" class="form-label small fw-bold mb-1">Notas de la reserva</label>
                    <textarea class="form-control form-control-sm" id="notasReserva" name="observaciones" rows="2" maxlength="500" placeholder="Anotaciones internas (no cambian horario, pago ni datos del cliente)"><?= htmlspecialchars((string)($reserva['observaciones'] ?? '')) ?></textarea>
                    <button type="submit" class="da-btn mt-2"><i class="bi bi-journal-text me-1"></i> Guardar nota</button>
                </form>
                <?php if (!empty($reserva['motivo_reprogramacion'])): ?>
                    <div class="da-row"><span>Última reprogramación</span><strong><?= htmlspecialchars((string)$reserva['motivo_reprogramacion']) ?></strong></div>
                <?php endif; ?>
                <div class="da-row"><span>Correo</span><strong><?= htmlspecialchars((string)($reserva['usuario_email'] ?? '—')) ?></strong></div>
            </div>
        </div>

        <!-- Pago (se gestiona en Pagos) -->
        <div class="col-lg-6">
            <div class="da-card h-100">
                <h2 class="da-h2">Pago</h2>
                <div class="da-montos">
                    <div><small>Total</small><strong><?= formatPrice($total) ?></strong></div>
                    <div><small>Pagado</small><strong class="text-success"><?= formatPrice($totalCobrado) ?></strong></div>
                    <div><small>Saldo</small><strong class="<?= $saldo > 0 ? 'text-danger' : 'text-success' ?>"><?= formatPrice($saldo) ?></strong></div>
                </div>
                <div class="mt-2"><span class="pill <?= $etqPago['cls'] ?>"><?= htmlspecialchars($chipPago) ?></span></div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <?php if ($comprobanteRuta): ?>
                        <a href="<?= url('/' . ltrim($comprobanteRuta, '/')) ?>" target="_blank" rel="noopener" class="da-btn"><i class="bi bi-file-earmark-text me-1"></i> Ver comprobante</a>
                    <?php endif; ?>
                    <?php if ($pagoEstado === 'en_revision'): ?>
                        <a href="<?= url('/admin/pagos?tab=revision&q=' . $id . (!empty($reserva['es_demo']) ? '&demo=1' : '')) ?>" class="da-btn principal"><i class="bi bi-receipt-cutoff me-1"></i> Revisar pago</a>
                    <?php elseif ($saldo > 0 && $estado !== 'cancelada'): ?>
                        <a href="<?= url('/admin/pagos?tab=pendientes&q=' . $id . (!empty($reserva['es_demo']) ? '&demo=1' : '')) ?>" class="da-btn principal"><i class="bi bi-cash-coin me-1"></i> Cobrar saldo en Pagos</a>
                    <?php endif; ?>
                </div>
                <p class="da-nota mb-0">Los pagos se verifican y se cobran solo en la pantalla Pagos. Al verificar, la reserva queda confirmada.</p>
            </div>
        </div>
    </div>

    <!-- Asistencia y puntualidad -->
    <div class="da-card mt-3" id="panel-asistencia">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <h2 class="da-h2 mb-0">Asistencia y puntualidad</h2>
            <span class="pill <?= $asistCls ?>"><?= $asistTxt ?><?= !empty($reserva['asistencia_hora']) ? ' · ' . substr((string)$reserva['asistencia_hora'], 0, 5) : '' ?></span>
        </div>
        <p class="da-nota mt-0">Tolerancia de <?= toleranciaMinutos() ?> minutos desde las <?= $horaIni ?>. El horario termina puntualmente a las <?= $horaFin ?>.
            <?php if (!empty($reserva['asistencia_notas'])): ?><br><strong>Nota:</strong> <?= htmlspecialchars((string)$reserva['asistencia_notas']) ?><?php endif; ?></p>

        <?php if ($activa): ?>
        <div class="da-acciones">
            <form action="<?= url('/admin/reserva/asistencia/' . $id) ?>" method="POST" class="da-once">
                <?= csrf_field() ?>
                <input type="hidden" name="asistencia_estado" value="presente">
                <button type="submit" class="da-btn ok"><i class="bi bi-check-circle me-1"></i> Llegó a tiempo</button>
            </form>
            <form action="<?= url('/admin/reserva/asistencia/' . $id) ?>" method="POST" class="da-once">
                <?= csrf_field() ?>
                <input type="hidden" name="asistencia_estado" value="tardanza">
                <button type="submit" class="da-btn warn"><i class="bi bi-hourglass-split me-1"></i> Llegó tarde</button>
            </form>
            <button type="button" class="da-btn bad" data-bs-toggle="modal" data-bs-target="#modalNoAsistio"><i class="bi bi-person-x me-1"></i> No asistió</button>
            <button type="button" class="da-btn" data-bs-toggle="modal" data-bs-target="#modalReprogramar"><i class="bi bi-arrow-repeat me-1"></i> Reprogramar</button>
            <button type="button" class="da-btn peligro" data-bs-toggle="modal" data-bs-target="#modalCancelar"><i class="bi bi-x-octagon me-1"></i> Cancelar reserva</button>
        </div>
        <?php else: ?>
            <div class="alert alert-light border mb-0" style="font-size:.9rem;">Esta reserva está <strong><?= $estado === 'cancelada' ? 'cancelada' : 'finalizada' ?></strong>: ya no se pueden registrar acciones.</div>
        <?php endif; ?>
    </div>

    <!-- Pagos registrados -->
    <div class="da-card mt-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="da-h2 mb-0">Pagos registrados</h2>
            <span class="pill fin">Pagado <?= formatPrice($totalCobrado) ?> de <?= formatPrice($total) ?></span>
        </div>
        <?php if (empty($historialPagos)): ?>
            <p class="text-muted small mb-0">Todavía no hay pagos registrados para esta reserva.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" style="font-size:.88rem;">
                    <thead class="table-light"><tr><th>Fecha</th><th>Tipo</th><th>Método</th><th>Monto</th><th>Estado</th><th>Registró</th><th>Notas</th></tr></thead>
                    <tbody>
                        <?php foreach ($historialPagos as $p): ?>
                            <?php
                                $tipoP = match ((string)$p['tipo']) { 'adelanto' => 'Adelanto', 'saldo' => 'Saldo', default => 'Pago completo' };
                                $estP = match ((string)$p['estado']) { 'verificado' => ['Pagado', 'ok'], 'en_revision' => ['Por verificar', 'warn'], default => ['Rechazado', 'bad'] };
                            ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime((string)$p['created_at'])) ?></td>
                                <td><?= $tipoP ?></td>
                                <td><?= htmlspecialchars(metodoPagoTexto((string)$p['metodo_pago'])) ?></td>
                                <td class="fw-bold">S/ <?= number_format((float)$p['monto'], 2) ?></td>
                                <td><span class="pill <?= $estP[1] ?>"><?= $estP[0] ?></span></td>
                                <td class="text-muted"><?= htmlspecialchars((string)($p['operador_nombre'] ?? 'Sistema')) ?></td>
                                <td class="text-muted small"><?= htmlspecialchars((string)($p['notas'] ?? '—')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php if ($activa): ?>
<!-- No asistió -->
<div class="modal fade" id="modalNoAsistio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= url('/admin/reserva/asistencia/' . $id) ?>" method="POST" class="modal-content da-once" style="border-radius:14px;">
            <?= csrf_field() ?>
            <input type="hidden" name="asistencia_estado" value="no_asistio">
            <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">¿Marcar como "No asistió"?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body">
                <p class="mb-2">Reserva #<?= $id ?> · <?= htmlspecialchars($clienteNombre) ?> · <?= $horaIni ?>–<?= $horaFin ?></p>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="liberar_cancha" value="1" id="chkLiberar">
                    <label class="form-check-label" for="chkLiberar"><strong>Liberar la cancha</strong> (la reserva se cancela y el horario queda libre)</label>
                </div>
                <label class="form-label small fw-bold" for="notasNoAsistio">Nota (opcional)</label>
                <input type="text" class="form-control" id="notasNoAsistio" name="asistencia_notas" placeholder="Ej: No contestó el teléfono">
            </div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Volver</button><button type="submit" class="btn btn-danger fw-bold">Sí, no asistió</button></div>
        </form>
    </div>
</div>

<!-- Cancelar reserva -->
<div class="modal fade" id="modalCancelar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= url('/reserva/estado/' . $id) ?>" method="POST" class="modal-content da-once" style="border-radius:14px;">
            <?= csrf_field() ?>
            <input type="hidden" name="estado" value="cancelada">
            <?php if ($grupo !== ''): ?><input type="hidden" name="evento_grupo" value="<?= htmlspecialchars($grupo) ?>"><?php endif; ?>
            <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Cancelar la reserva #<?= $id ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body">
                <div class="alert alert-danger py-2" style="font-size:.9rem;">El horario quedará libre para otros clientes.<?= $grupo !== '' ? ' <strong>Se cancelan todas las canchas de este evento.</strong>' : '' ?></div>
                <label class="form-label fw-bold small" for="motivoCancelar">Motivo de la cancelación <span class="text-danger">*</span></label>
                <textarea class="form-control" id="motivoCancelar" name="motivo" rows="2" required maxlength="300" placeholder="Ej: El cliente avisó que no podrá venir"></textarea>
            </div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">No cancelar</button><button type="submit" class="btn btn-danger fw-bold">Sí, cancelar reserva</button></div>
        </form>
    </div>
</div>

<!-- Reprogramar -->
<div class="modal fade" id="modalReprogramar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= url('/admin/reserva/reprogramar/' . $id) ?>" method="POST" class="modal-content da-once" style="border-radius:14px;">
            <?= csrf_field() ?>
            <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Reprogramar la reserva #<?= $id ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label small fw-bold">Cancha</label>
                        <select class="form-select" name="cancha_id" required>
                            <?php foreach ($canchasLista as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === (int)$reserva['cancha_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?> (<?= htmlspecialchars($tipoLabelsAdm[$c['tipo']] ?? $c['tipo']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label small fw-bold">Nueva fecha</label>
                        <input type="date" class="form-control" name="fecha" value="<?= htmlspecialchars((string)$reserva['fecha']) ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-6 col-sm-3">
                        <label class="form-label small fw-bold">Hora</label>
                        <select class="form-select" name="hora_inicio" required>
                            <?php if (!in_array(substr($horaActualSel, 3, 2), reservaMinutosInicio(), true)): ?>
                                <option value="<?= $horaActualSel ?>" selected><?= $horaActualSel ?> (actual)</option>
                            <?php endif; ?>
                            <?php for ($h = 7; $h <= 22; $h++): foreach (reservaMinutosInicio() as $mm): $slot = sprintf('%02d:%s', $h, $mm); ?>
                                <option value="<?= $slot ?>" <?= $horaActualSel === $slot ? 'selected' : '' ?>><?= $slot ?></option>
                            <?php endforeach; endfor; ?>
                        </select>
                    </div>
                    <div class="col-6 col-sm-3">
                        <label class="form-label small fw-bold">Duración</label>
                        <select class="form-select" name="duracion_horas" required>
                            <?php foreach ([0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4] as $d): ?>
                                <option value="<?= $d ?>" <?= (float)$reserva['duracion_horas'] === (float)$d ? 'selected' : '' ?>><?= formatoDuracionHoras((float)$d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Motivo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="motivo" required placeholder="Ej: El cliente pidió cambiar de horario">
                    </div>
                    <?php if (!$operativo['puede_reprogramar_regla']): ?>
                        <div class="col-12">
                            <div class="p-2 rounded border border-warning bg-warning bg-opacity-10 form-check ms-0 ps-4">
                                <input class="form-check-input" type="checkbox" name="forzar_fuera_de_plazo" value="1" id="chkForzar">
                                <label class="form-check-label small fw-bold" for="chkForzar">Autorización excepcional: faltan menos de 1 hora para el inicio</label>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="da-nota mb-0 mt-2">Se aplica la regla de 1 hora de anticipación y se revisa que la cancha esté libre.</p>
            </div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Volver</button><button type="submit" class="btn fw-bold" style="background:#1a7a3a;color:#fff;">Reprogramar</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.da-page { background: #f6f9fb; border-radius: 12px; }
.da-back { display: inline-block; font-size: .85rem; font-weight: 600; color: #475569; text-decoration: none; margin-bottom: 10px; }
.da-head { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 14px; margin-bottom: 16px; }
.da-h1 { font-size: 1.7rem; font-weight: 800; color: #101c33; margin: 0 0 8px; }
.da-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.da-chips .pill { font-size: .8rem; padding: 5px 12px; }
.da-contacto { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.da-tel { font-weight: 700; color: #101c33; text-decoration: none; font-size: 1rem; }
.da-card { background: #fff; border: 1px solid #e8eef4; border-radius: 14px; padding: 18px; box-shadow: 0 1px 3px rgba(16,28,51,.05); margin-bottom: 0; }
.da-h2 { font-size: 1.05rem; font-weight: 800; color: #101c33; margin: 0 0 12px; }
.da-row { display: flex; justify-content: space-between; gap: 14px; padding: 8px 0; border-bottom: 1px solid #eef2f7; font-size: .95rem; }
.da-row span { color: #64748b; }
.da-row strong { text-align: right; }
.da-montos { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.da-montos > div { background: #f4f7fb; border-radius: 10px; padding: 10px; text-align: center; }
.da-montos small { display: block; color: #64748b; font-size: .75rem; }
.da-montos strong { font-size: 1.15rem; }
.da-nota { font-size: .82rem; color: #64748b; margin-top: 12px; }
.da-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 0 16px; border-radius: 10px; font-weight: 700; font-size: .92rem; text-decoration: none; cursor: pointer; border: 1.5px solid #cbd5e1; background: #fff; color: #101c33; }
.da-btn:hover { border-color: #1a7a3a; color: #1a7a3a; }
.da-btn.principal { background: #1a7a3a; border-color: #1a7a3a; color: #fff; }
.da-btn.principal:hover { background: #135c2b; color: #fff; }
.da-btn.wa { background: #16a34a; border-color: #16a34a; color: #fff; }
.da-btn.wa:hover { background: #15803d; color: #fff; }
.da-btn.ok { background: #eef7f0; border-color: #bbf7d0; color: #166534; }
.da-btn.ok:hover { background: #1a7a3a; color: #fff; }
.da-btn.warn { background: #fef3c7; border-color: #fde68a; color: #92400e; }
.da-btn.warn:hover { background: #d97706; border-color: #d97706; color: #fff; }
.da-btn.bad { background: #fdf0f0; border-color: #f3c2c2; color: #991b1b; }
.da-btn.bad:hover { background: #dc2626; border-color: #dc2626; color: #fff; }
.da-btn.peligro { background: #fff; border-color: #dc2626; color: #b91c1c; }
.da-btn.peligro:hover { background: #dc2626; color: #fff; }
.da-acciones { display: flex; flex-wrap: wrap; gap: 8px; }
.da-acciones form { margin: 0; }
@media (max-width: 767px) {
    .da-h1 { font-size: 1.35rem; }
    .da-acciones .da-btn, .da-acciones form { width: 100%; }
    .da-acciones form .da-btn { width: 100%; }
    .da-contacto .da-btn { width: 100%; }
}
</style>
<script>
// Evita el doble clic: al enviar, los formularios de acciones se bloquean
document.querySelectorAll('form.da-once').forEach(function (f) {
    f.addEventListener('submit', function () {
        var b = f.querySelector('button[type=submit]');
        if (b) { setTimeout(function () { b.disabled = true; }, 0); }
    });
});
</script>
