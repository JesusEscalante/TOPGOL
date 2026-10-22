<?php
$titulo = $titulo ?? 'Solicitar Salón de Cumpleaños / Reunión';
$old = $old ?? [];
$canchas = $canchas ?? [];
$oldCanchas = array_map('intval', (array)($old['canchas_ids'] ?? []));
$oldDur = (string)($old['duracion'] ?? '1');
$oldTipo = (string)($old['tipo_celebracion'] ?? '');
$precioEspacio = espacioPrecioHora();
$provisional = !defined('ESPACIO_PRECIO_PROVISIONAL') || ESPACIO_PRECIO_PROVISIONAL;
$cuTx = eventosTextos()['cumpleanos'] ?? [];
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4 text-center" style="font-weight: 800; color: #101c33;">Solicitar Zona de Cumpleaños / Reunión</h2>

            <div class="alert alert-info shadow-sm" style="border-radius: 12px; border-left: 5px solid #0dcaf0;">
                <strong><i class="bi bi-info-circle me-1"></i> Información importante:</strong>
                <p class="mb-2 mt-1" style="font-size: 0.95rem;"><?= htmlspecialchars((string)($cuTx['descripcion'] ?? '')) ?> <?= htmlspecialchars(implode(' ', array_slice($cuTx['puntos'] ?? [], 0, 3))) ?> No necesitas reservar una cancha para usarla.</p>
                <p class="mb-2 fw-bold" style="font-size: 0.95rem; color:#78350f;"><i class="bi bi-ban me-1"></i> <?= htmlspecialchars((string)($cuTx['regla_alcohol'] ?? '')) ?></p>
                <ul class="mb-0" style="font-size: 0.95rem;">
                    <li>Esto es una <strong>solicitud</strong>: el administrador confirmará la disponibilidad, el precio final y el adelanto (pendiente de definir).</li>
                    <li>Si lo deseas, puedes agregar una o varias canchas para el mismo horario.</li>
                </ul>
            </div>

            <form action="<?= url('/cumpleanos/guardar') ?>" method="POST" class="card p-4 shadow-sm mt-4" style="border-radius: 15px; border: none;" id="formEspacio">
                <?= csrf_field() ?>

                <h5 class="mb-3" style="color: #42506b; font-weight: 700;">1. Fecha y Horario</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label" style="font-weight: 600; color: #5b6b82;">Fecha <span class="text-danger">*</span></label>
                        <input type="date" name="fecha" id="esFecha" class="form-control" required min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars((string)($old['fecha'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-weight: 600; color: #5b6b82;">Hora de Inicio <span class="text-danger">*</span></label>
                        <input type="time" name="hora" id="esHora" class="form-control" required step="<?= reservaPasoMinutos() * 60 ?>" value="<?= htmlspecialchars((string)($old['hora'] ?? '')) ?>">
                        <small class="text-muted d-none" id="esAvisoHoy" style="font-size: 0.75rem; display:block;">Hoy solo desde las <?= htmlspecialchars(horaMinimaReserva(date('Y-m-d'), false)) ?>.</small>
                        <small class="text-muted" style="font-size: 0.75rem;">Pasos de <?= reservaPasoMinutos() ?> min (ej. 15:00, 15:<?= sprintf('%02d', reservaPasoMinutos() % 60) ?>). Atención hasta las 23:00.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" style="font-weight: 600; color: #5b6b82;">Duración <span class="text-danger">*</span></label>
                        <select name="duracion" id="esDuracion" class="form-select" required>
                            <?php foreach (['0.5' => '30 minutos', '1' => '1 hora', '1.5' => '1 hora y media', '2' => '2 horas', '2.5' => '2 horas y media', '3' => '3 horas', '4' => '4 horas'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= (float)$oldDur === (float)$v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <h5 class="mb-3" style="color: #42506b; font-weight: 700;">2. Detalles del Evento</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label" style="font-weight: 600; color: #5b6b82;">Tipo de Celebración <span class="text-danger">*</span></label>
                        <select name="tipo_celebracion" class="form-select" required>
                            <?php foreach (['Cumpleaños Infantil' => 'Cumpleaños Infantil', 'Cumpleaños Adulto' => 'Cumpleaños Adulto', 'Reunión de Amigos / Grupo' => 'Reunión de Amigos / Grupo', 'Reunión Institucional / Empresa' => 'Reunión Institucional / Empresa', 'Otro' => 'Otro tipo de reunión'] as $v => $l): ?>
                                <option value="<?= htmlspecialchars($v) ?>" <?= $oldTipo === $v ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <h5 class="mb-1" style="color: #42506b; font-weight: 700;">3. Agregar canchas <span class="text-muted fw-normal" style="font-size:.85rem;">(opcional)</span></h5>
                <p class="text-muted mb-3" style="font-size: .85rem;">Puedes elegir ninguna, una o varias. Se piden para el mismo horario y cada una suma su precio por hora al total.</p>
                <div class="row g-2 mb-4">
                    <?php foreach ($canchas as $c): ?>
                        <div class="col-6 col-md-4">
                            <label class="d-flex align-items-center gap-2 p-2 border rounded-3" style="cursor:pointer; font-size:.9rem;">
                                <input type="checkbox" class="form-check-input mt-0 es-cancha" name="canchas_ids[]" value="<?= (int)$c['id'] ?>" data-precio="<?= (float)$c['precio_hora'] ?>" <?= in_array((int)$c['id'], $oldCanchas, true) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($c['nombre']) ?> <small class="text-muted">· <?= formatPrice($c['precio_hora']) ?>/h</small></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h5 class="mb-3" style="color: #42506b; font-weight: 700;">4. Datos de Contacto</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label" style="font-weight: 600; color: #5b6b82;">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" name="contacto_nombre" class="form-control" value="<?= htmlspecialchars((string)($old['contacto_nombre'] ?? ($_SESSION['usuario_nombre'] ?? ''))) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-weight: 600; color: #5b6b82;">Teléfono (WhatsApp) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text" style="background: #f8fafc; font-weight: 700; color: #334155;">+51</span>
                            <input type="tel" name="contacto_telefono" class="form-control" placeholder="9XX XXX XXX" value="<?= htmlspecialchars((string)($old['contacto_telefono'] ?? '')) ?>" required>
                        </div>
                    </div>
                </div>

                <h5 class="mb-3" style="color: #42506b; font-weight: 700;">5. Observaciones <span class="text-muted fw-normal" style="font-size:.85rem;">(opcional)</span></h5>
                <div class="mb-4">
                    <textarea name="observaciones" class="form-control" rows="3" placeholder="Cuéntanos qué necesitas para tu evento..."><?= htmlspecialchars((string)($old['observaciones'] ?? '')) ?></textarea>
                </div>

                <div class="p-3 mb-4" style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px;" id="resumenEspacio">
                    <div class="fw-bold mb-2" style="color:#14532d;">Total estimado</div>
                    <div class="d-flex justify-content-between small"><span id="resEspacioLbl">Espacio</span><strong id="resEspacioVal">--</strong></div>
                    <div class="d-flex justify-content-between small"><span id="resCanchasLbl">Canchas</span><strong id="resCanchasVal">S/ 0.00</strong></div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between"><span class="fw-bold">Total</span><strong id="resTotalEspacio" style="font-size:1.2rem; color:#166534;">--</strong></div>
                    <div class="small text-muted mt-2">
                        <?php if ($provisional): ?>Precio <strong>provisional, por confirmar con el encargado</strong> (S/ <?= number_format($precioEspacio, 0) ?> por hora el espacio). <?php endif; ?>
                        El adelanto está pendiente de definir; el administrador te lo indicará al confirmar.
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100" style="border-radius: 10px; font-weight: 800;">
                    <i class="bi bi-send me-2"></i> Enviar Solicitud de Espacio
                </button>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    var PRECIO_ESPACIO = <?= json_encode($precioEspacio) ?>;
    var selDur = document.getElementById('esDuracion');
    var checks = document.querySelectorAll('.es-cancha');
    function money(n) { return 'S/ ' + n.toFixed(2); }
    function calcular() {
        var minutos = Math.round(parseFloat(selDur.value || '0') * 60);
        var espacio = minutos * (PRECIO_ESPACIO / 60);
        var canchas = 0, n = 0;
        checks.forEach(function (c) {
            if (c.checked) { n++; canchas += minutos * (parseFloat(c.getAttribute('data-precio') || '0') / 60); }
        });
        document.getElementById('resEspacioLbl').textContent = 'Espacio (' + minutos + ' min)';
        document.getElementById('resEspacioVal').textContent = money(espacio);
        document.getElementById('resCanchasLbl').textContent = n === 0 ? 'Canchas (ninguna)' : 'Canchas (' + n + ')';
        document.getElementById('resCanchasVal').textContent = money(canchas);
        document.getElementById('resTotalEspacio').textContent = money(espacio + canchas);
    }
    // Hoy no se pueden elegir horas que ya pasaron (hora de Lima calculada en el servidor; el servidor también lo valida)
    var HOY_STR = <?= json_encode(date('Y-m-d')) ?>, MIN_HOY = <?= json_encode(horaMinimaReserva(date('Y-m-d'), false)) ?>;
    var inpFecha = document.getElementById('esFecha'), inpHora = document.getElementById('esHora');
    function limitarHoraHoy() {
        if (!inpFecha || !inpHora) { return; }
        if (inpFecha.value === HOY_STR && MIN_HOY !== '24:00') {
            inpHora.min = MIN_HOY;
            if (inpHora.value && inpHora.value < MIN_HOY) { inpHora.value = ''; }
        } else {
            inpHora.removeAttribute('min');
        }
        var aviso = document.getElementById('esAvisoHoy');
        if (aviso) { aviso.classList.toggle('d-none', !(inpFecha.value === HOY_STR)); }
    }
    if (inpFecha) { inpFecha.addEventListener('change', limitarHoraHoy); limitarHoraHoy(); }
    selDur.addEventListener('change', calcular);
    checks.forEach(function (c) { c.addEventListener('change', calcular); });
    calcular();
})();
</script>
