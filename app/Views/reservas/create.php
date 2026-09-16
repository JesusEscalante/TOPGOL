<?php
declare(strict_types=1);
$idSeleccionado = (int)($canchaSeleccionada['id'] ?? 0);

// Valores pre-seleccionados desde URL (GET)
$fechaPre = $_GET['fecha'] ?? date('Y-m-d');
$horaPre = $_GET['horario'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaPre)) {
    $fechaPre = date('Y-m-d');
}

// Desde 1 hora más de la actual (ni pasadas ni la actual) si la fecha es hoy (hora de Lima)
$hoyStr = date('Y-m-d');
$esHoy = ($fechaPre === $hoyStr);
$horaMinHoy = max(7, (int)date('H') + 1);

// Cancha inicial para el resumen
$canchaIni = $canchaSeleccionada ?? ($canchas[0] ?? null);
$precioHoraIni = (float)($canchaIni['precio_hora'] ?? 60);
$duracionIni = 1;
$totalIni = $precioHoraIni * $duracionIni;
$adelantoIni = 20;
$saldoIni = max(0, $totalIni - $adelantoIni);

$tipoLabels = ['futbol_5' => 'Fútbol 5', 'futbol_7' => 'Fútbol 7', 'futbol_11' => 'Fútbol 11'];
$tipoIni = $tipoLabels[$canchaIni['tipo'] ?? ''] ?? 'Fútbol 5';
$capacidadIni = (int)($canchaIni['capacidad'] ?? 8);

// Fecha formateada: Sáb, 26 de abril de 2025
$dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$tsFecha = strtotime($fechaPre);
$fechaFormateada = $dias[(int)date('w', $tsFecha)] . ', ' . date('d', $tsFecha) . ' de ' . $meses[(int)date('n', $tsFecha)] . ' de ' . date('Y', $tsFecha);

// Horario inicial
$horaIniStr = $horaPre !== '' ? $horaPre : '10:00';
$horaFinStr = date('H:i', strtotime($horaIniStr . ' +1 hour'));
?>

<div class="container py-3 pago-page">
    <!-- Stepper -->
    <div class="stepper" aria-label="Progreso de reserva">
        <div class="step done">
            <span class="dot"><i class="bi bi-check-lg"></i></span>
            <span class="lbl"><span class="lbl-full">1. Selección de cancha</span><span class="lbl-short">Selección</span></span>
        </div>
        <span class="line done" aria-hidden="true"></span>
        <div class="step active" aria-current="step">
            <span class="dot">2</span>
            <span class="lbl"><span class="lbl-full">2. Pago y comprobante</span><span class="lbl-short">Pago</span></span>
        </div>
        <span class="line" aria-hidden="true"></span>
        <div class="step">
            <span class="dot">3</span>
            <span class="lbl"><span class="lbl-full">3. Confirmación</span><span class="lbl-short">Confirmación</span></span>
        </div>
    </div>

    <div class="row g-4">
        <!-- Columna izquierda -->
        <div class="col-lg-7">

            <form action="<?= url('/reserva/guardar') ?>" method="POST" enctype="multipart/form-data" class="needs-validation" id="formPago" novalidate>
                <?= csrf_field() ?>

                <div class="alert alert-danger d-none" id="formAlert" role="alert">
                    <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> Revisa lo siguiente antes de realizar tu reserva:</strong>
                    <ul class="mb-0 mt-1" id="formAlertList"></ul>
                </div>

                <!-- Datos de la reserva (editables, compactos) -->
                <div class="pago-card mb-3">
                    <div class="pago-card-title">Datos de tu reserva</div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="cancha_id" class="form-label fw-semibold small">Cancha <span class="text-danger">*</span></label>
                            <select class="form-select" id="cancha_id" name="cancha_id" required>
                                <option value="" disabled <?= $idSeleccionado === 0 && empty($canchas) ? 'selected' : '' ?>>-- Elige tu cancha --</option>
                                <?php foreach ($canchas as $c): ?>
                                    <option value="<?= $c['id'] ?>"
                                        data-precio="<?= $c['precio_hora'] ?>"
                                        data-nombre="<?= htmlspecialchars($c['nombre']) ?>"
                                        data-tipo="<?= htmlspecialchars($tipoLabels[$c['tipo']] ?? $c['tipo']) ?>"
                                        data-capacidad="<?= (int)$c['capacidad'] ?>"
                                        <?= (int)$c['id'] === $idSeleccionado ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nombre']) ?> (<?= htmlspecialchars($tipoLabels[$c['tipo']] ?? $c['tipo']) ?>) - <?= formatPrice($c['precio_hora']) ?>/h
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="fecha" class="form-label fw-semibold small">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha" name="fecha" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($fechaPre) ?>" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="hora_inicio" class="form-label fw-semibold small">Hora <span class="text-danger">*</span></label>
                            <select class="form-select" id="hora_inicio" name="hora_inicio" required>
                                <option value="" disabled <?= $horaPre === '' ? 'selected' : '' ?>>--:--</option>
                                <?php for ($h = 7; $h <= 23; $h++): ?>
                                    <?php if ($esHoy && $h < $horaMinHoy) continue; ?>
                                    <?php $horaStr = sprintf('%02d:00', $h); ?>
                                    <option value="<?= $horaStr ?>" <?= $horaPre === $horaStr ? 'selected' : '' ?>><?= $horaStr ?></option>
                                <?php endfor; ?>
                            </select>
                            <small class="text-muted <?= ($esHoy && $horaMinHoy > 23) ? '' : 'd-none' ?>" id="sinHorariosHoy">Por hoy ya no quedan horarios, elige otra fecha.</small>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="duracion_horas" class="form-label fw-semibold small">Duración <span class="text-danger">*</span></label>
                            <select class="form-select" id="duracion_horas" name="duracion_horas" required>
                                <?php for ($d = 1; $d <= 4; $d++): ?>
                                    <option value="<?= $d ?>" <?= $d === $duracionIni ? 'selected' : '' ?>><?= $d ?> hora<?= $d > 1 ? 's' : '' ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-9">
                            <label for="observaciones" class="form-label fw-semibold small">Observaciones (opcional)</label>
                            <input type="text" class="form-control" id="observaciones" name="observaciones" placeholder="Ej: Necesitamos chalecos...">
                        </div>
                    </div>
                </div>

                <!-- Sección 1 -->
                <div class="pago-card mb-3">
                    <h2 class="pago-h2"><span class="pago-num">1.</span> Realiza el pago del adelanto</h2>
                    <p class="pago-text">Elige un método de pago y realiza el adelanto de S/ 20. Luego sube tu comprobante.</p>

                    <input type="hidden" name="metodo_pago" id="metodo_pago" value="yape">
                    <div class="row g-2 mb-3" role="group" aria-label="Método de pago">
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <button type="button" class="metodo-btn active" id="btnYape" onclick="elegirMetodo('yape')">
                                <span class="yape-logo">yape</span>
                                <span>Yape</span>
                            </button>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <button type="button" class="metodo-btn" id="btnBcp" onclick="elegirMetodo('bcp')">
                                <span class="bcp-logo">›BCP›</span>
                                <span>Transferencia BCP</span>
                            </button>
                        </div>
                    </div>

                    <!-- Panel Yape -->
                    <div class="pago-panel" id="panelYape">
                        <div class="row g-3">
                            <div class="col-md-5 text-center">
                                <div class="qr-title">Escanea el código QR con Yape</div>
                                <div class="qr-wrap">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=YAPE-TOPGOL-TACNA-987654321-20" alt="QR Yape Top Gol" width="180" height="180">
                                    <span class="qr-logo">yape</span>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="pago-numero-box">
                                    <div class="fw-semibold small mb-2">También puedes pagar al número:</div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="tel-ico"><i class="bi bi-telephone-fill"></i></span>
                                        <strong class="tel-num">987 654 321</strong>
                                        <button type="button" class="btn-copiar" onclick="copiarNumero('987654321', this)">
                                            <i class="bi bi-copy"></i> Copiar
                                        </button>
                                    </div>
                                    <div class="small mt-2">Titular: <strong>Top Gol Tacna S.A.C.</strong></div>
                                </div>
                                <div class="pago-importante">
                                    <div class="fw-bold mb-1"><span class="info-ico"><i class="bi bi-info-lg"></i></span> Importante</div>
                                    <ul>
                                        <li>Realiza el pago por el monto del adelanto: <strong>S/ 20</strong></li>
                                        <li>Incluye tu nombre en la descripción (opcional)</li>
                                        <li>Luego sube tu comprobante en el formulario de abajo</li>
                                        <li>Tu reserva se confirmará una vez verificado el pago</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel BCP -->
                    <div class="pago-panel d-none" id="panelBcp">
                        <div class="pago-numero-box mb-3">
                            <div class="fw-semibold small mb-2">Transfiere a nuestra cuenta BCP:</div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="tel-ico"><i class="bi bi-bank"></i></span>
                                <strong class="tel-num" style="font-size:1rem;">CCI: 002-123-456789-12</strong>
                                <button type="button" class="btn-copiar" onclick="copiarNumero('00212345678912', this)">
                                    <i class="bi bi-copy"></i> Copiar
                                </button>
                            </div>
                            <div class="small mt-2">Titular: <strong>Top Gol Tacna S.A.C.</strong></div>
                        </div>
                        <div class="pago-importante">
                            <div class="fw-bold mb-1"><span class="info-ico"><i class="bi bi-info-lg"></i></span> Importante</div>
                            <ul>
                                <li>Realiza la transferencia por el monto del adelanto: <strong>S/ 20</strong></li>
                                <li>Guarda la constancia de la operación</li>
                                <li>Luego sube tu comprobante en el formulario de abajo</li>
                                <li>Tu reserva se confirmará una vez verificado el pago</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Sección 2 -->
                <div class="pago-card mb-3">
                    <h2 class="pago-h2"><span class="pago-num">2.</span> Sube tu comprobante de pago <span class="text-danger">*</span></h2>
                    <p class="pago-text">Adjunta una imagen o captura de tu comprobante de pago <strong>(obligatorio)</strong> (formato JPG, PNG o PDF. Máx. 5MB).</p>

                    <label class="dropzone" id="dropzone" for="comprobante">
                        <input type="file" id="comprobante" name="comprobante" accept=".jpg,.jpeg,.png,.pdf" hidden>
                        <span class="dz-ico"><i class="bi bi-cloud-upload-fill"></i></span>
                        <strong>Haz clic para subir tu comprobante</strong>
                        <span class="dz-sub">o arrastra y suelta tu archivo aquí</span>
                        <span class="dz-file d-none" id="fileInfo"></span>
                    </label>
                    <div class="dz-formats">Formatos soportados: JPG, PNG, PDF (Máx. 5MB)</div>
                    <div class="invalid-feedback d-none" id="fileError">Archivo no válido. Usa JPG, PNG o PDF de máximo 5MB.</div>

                    <button type="submit" class="btn-enviar mt-3">
                        <i class="bi bi-send me-2"></i> Relizar Reserva
                    </button>
                </div>
            </form>
        </div>

        <!-- Columna derecha: resumen -->
        <div class="col-lg-5">
            <h2 class="resumen-h2">Resumen de tu reserva</h2>
            <div class="resumen-card">
                <div class="resumen-img">
                    <img src="https://images.unsplash.com/photo-1522778119026-d647f0596c20?w=800&q=80&fit=crop" alt="Cancha Top Gol de noche">
                    <span class="resumen-badge"><i class="bi bi-shield-fill-check me-1"></i> Cancha reservada</span>
                    <div class="resumen-img-txt">
                        <strong id="resCanchaNombre"><?= htmlspecialchars(mb_strtoupper($canchaIni['nombre'] ?? 'CANCHA 1')) ?></strong>
                        <span id="resCanchaTipo"><?= htmlspecialchars($tipoIni) ?> - Césped sintético</span>
                    </div>
                </div>

                <ul class="resumen-list">
                    <li>
                        <span class="rl-ico"><i class="bi bi-calendar3"></i></span>
                        <span><small>Fecha</small><strong id="resFecha"><?= htmlspecialchars($fechaFormateada) ?></strong></span>
                    </li>
                    <li>
                        <span class="rl-ico"><i class="bi bi-clock"></i></span>
                        <span><small>Horario</small><strong id="resHorario"><?= htmlspecialchars($horaIniStr) ?> - <?= htmlspecialchars($horaFinStr) ?> (<?= $duracionIni ?> hora)</strong></span>
                    </li>
                    <li>
                        <span class="rl-ico"><i class="bi bi-people-fill"></i></span>
                        <span><small>Número de personas</small><strong id="resPersonas">Hasta <?= $capacidadIni ?> personas</strong></span>
                    </li>
                    <li>
                        <span class="rl-ico"><i class="bi bi-geo-alt-fill"></i></span>
                        <span><small>Ubicación</small><strong>Top Gol Tacna</strong></span>
                    </li>
                </ul>

                <div class="resumen-pago">
                    <div class="rp-title">Detalle de pago</div>
                    <div class="rp-row"><span>Precio por hora</span><strong id="resPrecioHora">S/ <?= number_format($precioHoraIni, 0) ?></strong></div>
                    <div class="rp-row"><span>Total de la reserva</span><strong id="resTotal">S/ <?= number_format($totalIni, 0) ?></strong></div>
                    <div class="rp-row verde"><span>Adelanto requerido (hoy)</span><strong>S/ 20</strong></div>
                    <div class="rp-row saldo"><span>Saldo pendiente</span><strong id="resSaldo">S/ <?= number_format($saldoIni, 0) ?></strong></div>
                </div>

                <div class="resumen-alerta">
                    <span class="ra-ico"><i class="bi bi-clock-history"></i></span>
                    <span>
                        <strong>Pago en revisión</strong>
                        <small>Hemos recibido tu comprobante. Te notificaremos por WhatsApp y correo cuando se confirme tu pago.</small>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.pago-page { background: #f6f9fb; border-radius: 12px; }
.pago-breadcrumb { font-size: .8rem; color: #64748b; margin-bottom: 2px; }
.pago-breadcrumb a { color: #64748b; }
.pago-breadcrumb .sep { margin: 0 6px; }
.pago-breadcrumb .current { color: #1a7a3a; font-weight: 600; }
.pago-h1 { font-size: 2rem; font-weight: 800; color: #101c33; margin: 0; }
.pago-sub { color: #7c8aa0; margin: 2px 0 12px; }
.pago-seguro { align-items: center; gap: 8px; color: #101c33; }
.pago-seguro-ico { color: #1a7a3a; font-size: 1.6rem; }
.pago-seguro strong { display: block; font-size: .85rem; }
.pago-seguro small { color: #7c8aa0; font-size: .72rem; }
/* Stepper */
.stepper { display: flex; align-items: flex-start; gap: 8px; margin: 6px 0 18px; }
.step { display: flex; align-items: center; gap: 8px; }
.step .dot { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; background: #e5e9ef; color: #8a97a8; flex-shrink: 0; }
.step .lbl { font-size: .75rem; color: #8a97a8; line-height: 1.2; }
.step.done .dot { background: #1a7a3a; color: #fff; }
.step.done .lbl { color: #101c33; font-weight: 600; }
.step.active .dot { background: #22c55e; color: #fff; box-shadow: 0 0 0 5px rgba(34,197,94,.18); }
.step.active .lbl { color: #101c33; font-weight: 600; }
.stepper .line { flex: 1; height: 3px; border-radius: 3px; background: #e5e9ef; margin-top: 16px; min-width: 30px; }
.stepper .line.done { background: #22c55e; }
.step .lbl-short { display: none; }
@media (max-width: 991px) { .pago-h1 { font-size: 1.6rem; } }
@media (max-width: 576px) {
    .stepper { gap: 6px; margin: 2px 0 14px; }
    .step { gap: 6px; min-width: 0; }
    .step .dot { width: 28px; height: 28px; font-size: .8rem; }
    .step.active .dot { box-shadow: 0 0 0 4px rgba(34,197,94,.18); }
    .step .lbl { font-size: .68rem; line-height: 1.15; }
    .step .lbl-full { display: none; }
    .step .lbl-short { display: inline; }
    .stepper .line { min-width: 12px; margin-top: 13px; }
}
@media (max-width: 360px) {
    .step .lbl { font-size: .62rem; }
    .stepper { gap: 4px; }
}
/* Cards */
.pago-card { background: #fff; border: 1px solid #e8eef4; border-radius: 12px; padding: 18px; box-shadow: 0 1px 2px rgba(16,28,51,.04); }
.pago-card-title { font-weight: 800; color: #101c33; margin-bottom: 10px; }
.pago-h2 { font-size: 1.05rem; font-weight: 800; color: #101c33; margin-bottom: 2px; }
.pago-num { font-weight: 900; }
.pago-text { color: #7c8aa0; font-size: .85rem; margin-bottom: 12px; }
/* Métodos */
.metodo-btn { width: 100%; display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #fff; font-weight: 700; color: #101c33; transition: all .15s; }
.metodo-btn.active { background: #effaf1; border-color: #22c55e; }
.yape-logo { background: #742284; color: #fff; font-style: italic; font-weight: 800; border-radius: 8px; padding: 6px 10px; font-size: .85rem; }
.bcp-logo { background: #002a8d; color: #fff; font-weight: 800; border-radius: 8px; padding: 6px 10px; font-size: .8rem; letter-spacing: .5px; }
/* Panel */
.pago-panel { border: 1px solid #e8eef4; border-radius: 10px; padding: 14px; }
.qr-title { font-size: .8rem; font-weight: 600; margin-bottom: 8px; }
.qr-wrap { position: relative; display: inline-block; }
.qr-wrap img { border-radius: 8px; display: block; }
.qr-logo { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); background: #742284; color: #fff; font-style: italic; font-weight: 800; border-radius: 12px; padding: 10px 16px; border: 3px solid #fff; }
.pago-numero-box { background: #f4f7fb; border-radius: 10px; padding: 12px; margin-bottom: 12px; color: #101c33; }
.tel-ico { width: 32px; height: 32px; border-radius: 50%; background: #fff; display: inline-flex; align-items: center; justify-content: center; color: #101c33; }
.tel-num { font-size: 1.15rem; letter-spacing: .5px; }
.btn-copiar { border: none; background: transparent; color: #1a7a3a; font-weight: 700; font-size: .85rem; }
.pago-importante { background: #e9f7ee; border-radius: 10px; padding: 12px 14px; font-size: .8rem; color: #23402e; }
.pago-importante ul { margin: 6px 0 0; padding-left: 18px; }
.info-ico { display: inline-flex; width: 22px; height: 22px; border-radius: 50%; background: #1a7a3a; color: #fff; align-items: center; justify-content: center; font-size: .8rem; margin-right: 4px; }
/* Dropzone */
.dropzone { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; border: 1.5px dashed #c4cfdb; background: #f7fafc; border-radius: 10px; padding: 28px 16px; cursor: pointer; text-align: center; transition: all .15s; }
.dropzone:hover, .dropzone.over { border-color: #22c55e; background: #effaf1; }
.dz-ico { font-size: 2rem; color: #42506b; }
.dz-sub { color: #7c8aa0; font-size: .85rem; }
.dz-formats { text-align: center; color: #7c8aa0; font-size: .72rem; margin-top: 6px; }
.dz-file { margin-top: 8px; font-size: .8rem; background: #e9f7ee; color: #166534; border-radius: 8px; padding: 6px 10px; }
.btn-enviar { width: 100%; background: #0d7a33; color: #fff; border: none; border-radius: 10px; padding: 13px; font-weight: 800; font-size: 1rem; transition: all .15s; }
.btn-enviar:hover { background: #0a6129; color: #fff; }
/* Resumen */
.resumen-h2 { font-size: 1.2rem; font-weight: 800; color: #101c33; margin-bottom: 10px; }
.resumen-card { background: #fff; border: 1px solid #e8eef4; border-radius: 14px; overflow: hidden; box-shadow: 0 6px 18px rgba(16,28,51,.06); }
.resumen-img { position: relative; height: 190px; }
.resumen-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.resumen-badge { position: absolute; top: 12px; left: 12px; background: #b8e6c3; color: #14532d; font-size: .72rem; font-weight: 700; border-radius: 8px; padding: 5px 10px; }
.resumen-img-txt { position: absolute; left: 14px; bottom: 12px; color: #fff; text-shadow: 0 1px 4px rgba(0,0,0,.6); }
.resumen-img-txt strong { display: block; font-size: 1rem; letter-spacing: .5px; }
.resumen-img-txt span { font-size: .8rem; opacity: .9; }
.resumen-list { list-style: none; margin: 0; padding: 12px 16px 4px; }
.resumen-list li { display: flex; gap: 10px; padding: 7px 0; color: #101c33; }
.rl-ico { color: #101c33; font-size: 1.2rem; width: 26px; text-align: center; }
.resumen-list small { display: block; color: #7c8aa0; font-size: .72rem; }
.resumen-list strong { font-size: .85rem; }
.resumen-pago { background: #f4f7fb; border-radius: 10px; margin: 8px 14px; padding: 12px 14px; font-size: .85rem; }
.rp-title { font-weight: 800; margin-bottom: 6px; color: #101c33; }
.rp-row { display: flex; justify-content: space-between; padding: 3px 0; color: #33415c; }
.rp-row.verde { color: #1a7a3a; font-weight: 700; }
.rp-row.saldo { background: #e9f7ee; color: #166534; font-weight: 800; border-radius: 8px; padding: 8px 10px; margin-top: 6px; }
.resumen-alerta { display: flex; gap: 10px; background: #fef6e0; border-radius: 10px; margin: 12px 14px 16px; padding: 12px 14px; }
.ra-ico { color: #d97706; font-size: 1.4rem; }
.resumen-alerta strong { display: block; color: #b45309; font-size: .9rem; }
.resumen-alerta small { color: #7c6a3a; font-size: .78rem; }
</style>

<script>
function elegirMetodo(m) {
    const yape = m === 'yape';
    document.getElementById('btnYape').classList.toggle('active', yape);
    document.getElementById('btnBcp').classList.toggle('active', !yape);
    document.getElementById('panelYape').classList.toggle('d-none', !yape);
    document.getElementById('panelBcp').classList.toggle('d-none', yape);
    document.getElementById('metodo_pago').value = yape ? 'yape' : 'transferencia_bcp';
}

function copiarNumero(num, btn) {
    const done = () => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Copiado';
        setTimeout(() => { btn.innerHTML = orig; }, 1500);
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(num).then(done).catch(done);
    } else {
        const t = document.createElement('textarea');
        t.value = num;
        document.body.appendChild(t);
        t.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(t);
        done();
    }
}

(function () {
    const dz = document.getElementById('dropzone');
    const input = document.getElementById('comprobante');
    const info = document.getElementById('fileInfo');
    const err = document.getElementById('fileError');
    const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    const maxSize = 5 * 1024 * 1024;

    function validar(file) {
        if (!file) return true;
        const okType = validTypes.includes(file.type) || /\.(jpe?g|png|pdf)$/i.test(file.name);
        const okSize = file.size <= maxSize;
        err.classList.toggle('d-none', okType && okSize);
        return okType && okSize;
    }

    function mostrar(file) {
        if (!file) return;
        const kb = (file.size / 1024).toFixed(0);
        info.textContent = '📎 ' + file.name + ' (' + kb + ' KB)';
        info.classList.remove('d-none');
    }

    input.addEventListener('change', () => {
        const f = input.files[0];
        if (f && validar(f)) mostrar(f);
        else if (f) { input.value = ''; info.classList.add('d-none'); }
    });

    ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.add('over'); }));
    ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.remove('over'); }));
    dz.addEventListener('drop', e => {
        const f = e.dataTransfer.files && e.dataTransfer.files[0];
        if (!f) return;
        if (!validar(f)) { input.value = ''; info.classList.add('d-none'); return; }
        const dt = new DataTransfer();
        dt.items.add(f);
        input.files = dt.files;
        mostrar(f);
    });

    // Resumen dinámico
    const selCancha = document.getElementById('cancha_id');
    const inpFecha = document.getElementById('fecha');
    const selHora = document.getElementById('hora_inicio');
    const selDur = document.getElementById('duracion_horas');
    const dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    const meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    function actualizarResumen() {
        const opt = selCancha.options[selCancha.selectedIndex];
        const precio = parseFloat(opt ? opt.dataset.precio || '0' : '0');
        const nombre = opt ? (opt.dataset.nombre || '') : '';
        const tipo = opt ? (opt.dataset.tipo || '') : '';
        const cap = opt ? (opt.dataset.capacidad || '') : '';
        const dur = parseInt(selDur.value || '1', 10);
        const total = precio * dur;
        const saldo = Math.max(0, total - 20);

        if (nombre) document.getElementById('resCanchaNombre').textContent = nombre.toUpperCase();
        if (tipo) document.getElementById('resCanchaTipo').textContent = tipo + ' - Césped sintético';
        if (cap) document.getElementById('resPersonas').textContent = 'Hasta ' + cap + ' personas';

        const f = new Date(inpFecha.value + 'T12:00:00');
        if (!isNaN(f)) {
            document.getElementById('resFecha').textContent =
                dias[f.getDay()] + ', ' + String(f.getDate()).padStart(2, '0') + ' de ' + meses[f.getMonth() + 1] + ' de ' + f.getFullYear();
        }

        if (selHora.value) {
            const ini = selHora.value;
            const finDate = new Date('2000-01-01T' + ini + ':00');
            finDate.setHours(finDate.getHours() + dur);
            const fin = String(finDate.getHours()).padStart(2, '0') + ':' + String(finDate.getMinutes()).padStart(2, '0');
            document.getElementById('resHorario').textContent = ini + ' - ' + fin + ' (' + dur + ' hora' + (dur > 1 ? 's' : '') + ')';
        }

        document.getElementById('resPrecioHora').textContent = 'S/ ' + precio.toFixed(0);
        document.getElementById('resTotal').textContent = 'S/ ' + total.toFixed(0);
        document.getElementById('resSaldo').textContent = 'S/ ' + saldo.toFixed(0);
    }

    // Oculta las horas que ya pasaron cuando la fecha elegida es hoy (hora de Lima)
    const HOY_STR = <?= json_encode($hoyStr) ?>;
    const HORA_MIN_HOY = <?= (int)$horaMinHoy ?>;
    function filtrarHoras() {
        const esHoySel = (inpFecha.value === HOY_STR);
        let seleccionValida = true;
        let visibles = 0;
        Array.from(selHora.options).forEach(o => {
            if (!o.value) return;
            const h = parseInt(o.value.slice(0, 2), 10);
            const pasada = esHoySel && h < HORA_MIN_HOY;
            o.hidden = pasada;
            o.disabled = pasada;
            if (!pasada) visibles++;
            if (pasada && o.selected) seleccionValida = false;
        });
        if (!seleccionValida) selHora.value = '';
        const aviso = document.getElementById('sinHorariosHoy');
        if (aviso) aviso.classList.toggle('d-none', !(esHoySel && visibles === 0));
    }

    [selCancha, inpFecha, selHora, selDur].forEach(el => el && el.addEventListener('change', actualizarResumen));
    inpFecha.addEventListener('change', filtrarHoras);
    filtrarHoras();
    actualizarResumen();

    // Validación previa: avisa qué falta antes de reservar (comprobante obligatorio)
    const form = document.getElementById('formPago');
    const formAlert = document.getElementById('formAlert');
    const formAlertList = document.getElementById('formAlertList');

    form.addEventListener('submit', e => {
        const faltantes = [];
        if (!selCancha.value) faltantes.push('Selecciona una cancha.');
        if (!inpFecha.value) faltantes.push('Elige la fecha del partido.');
        if (!selHora.value) faltantes.push('Elige la hora de inicio.');
        if (!selDur.value) faltantes.push('Elige la duración del partido.');
        const f = input.files[0];
        if (!f) {
            faltantes.push('Sube tu comprobante de pago (JPG, PNG o PDF, máx. 5MB).');
            err.textContent = 'Falta tu comprobante de pago. Súbelo para continuar.';
            err.classList.remove('d-none');
            dz.style.borderColor = '#dc2626';
        } else if (!validar(f)) {
            faltantes.push('El comprobante no es válido. Usa JPG, PNG o PDF de máximo 5MB.');
        }

        if (faltantes.length > 0 || !form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            formAlertList.innerHTML = faltantes.map(t => '<li>' + t + '</li>').join('');
            formAlert.classList.remove('d-none');
            formAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            formAlert.classList.add('d-none');
        }
        form.classList.add('was-validated');
    });

    // Oculta el aviso y errores al corregir
    [selCancha, inpFecha, selHora, selDur].forEach(el => el && el.addEventListener('change', () => {
        formAlert.classList.add('d-none');
    }));
    input.addEventListener('change', () => {
        dz.style.borderColor = '';
    });
})();
</script>
