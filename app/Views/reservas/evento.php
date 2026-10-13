<?php
declare(strict_types=1);

$esAdminView = function_exists('isAdmin') && isAdmin();
$hoyStr = date('Y-m-d');
$horaMinHoy = max(7, (int)date('H') + 1);
$tipoLabels = ['futbol_5' => 'Fútbol 5', 'futbol_7' => 'Fútbol 7', 'futbol_11' => 'Fútbol 11'];
?>

<div class="container py-3 pago-page">
    <div class="stepper" aria-label="Progreso de reserva">
        <div class="step done"><span class="dot"><i class="bi bi-check-lg"></i></span><span class="lbl">Selección</span></div>
        <span class="line done" aria-hidden="true"></span>
        <div class="step active" aria-current="step"><span class="dot">2</span><span class="lbl"><span class="lbl-full">2. Evento y<br>pago</span><span class="lbl-short">Evento</span></span></div>
        <span class="line" aria-hidden="true"></span>
        <div class="step"><span class="dot">3</span><span class="lbl"><span class="lbl-full">3. Confirmación</span><span class="lbl-short">Confirmación</span></span></div>
    </div>

    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap mb-3">
        <div>
            <h1 class="pago-h1">Reserva tu evento</h1>
            <p class="pago-sub">Elige un mismo día y reserva varias canchas. El <strong>adelanto es S/ 20 por cancha</strong>.</p>
        </div>
    </div>

    <form action="<?= url('/evento/guardar') ?>" method="POST" enctype="multipart/form-data" class="needs-validation" id="formEvento" novalidate>
        <?= csrf_field() ?>

        <div class="alert alert-danger d-none" id="formAlert" role="alert">
            <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> Revisa lo siguiente:</strong>
            <ul class="mb-0 mt-1" id="formAlertList"></ul>
        </div>

        <!-- Fecha del evento -->
        <div class="pago-card mb-3">
            <div class="pago-card-title">Fecha y horario del evento</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="fecha" class="form-label fw-semibold small">Fecha <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="fecha" name="fecha" min="<?= $hoyStr ?>" value="<?= $hoyStr ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Hora <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <select class="form-select" id="horaVal" style="border-right:none;" required>
                            <option value="">HH</option>
                            <?php for($h=7;$h<=23;$h++): $hs=sprintf('%02d',$h); ?>
                                <option value="<?= $hs ?>"><?= $hs ?></option>
                            <?php endfor; ?>
                        </select>
                        <span class="input-group-text bg-white border-start-0 border-end-0 px-1">:</span>
                        <select class="form-select" id="minVal" style="border-left:none;" required>
                            <option value="00">00</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="30">30</option>
                            <option value="40">40</option>
                            <option value="50">50</option>
                        </select>
                        <input type="hidden" name="hora" id="hora" value="">
                    </div>
                    <small class="text-muted d-none" id="sinHorariosHoy">Por hoy ya no quedan horarios.</small>
                </div>
                <div class="col-md-4">
                    <label for="duracion" class="form-label fw-semibold small">Duración <span class="text-danger">*</span></label>
                    <select class="form-select" id="duracion" name="duracion" required>
                        <?php
                        $durOpcionesEvt = [0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 5, 6];
                        foreach ($durOpcionesEvt as $d):
                            $lbl = $d == 0.5 ? '30 minutos' : ($d == 1 ? '1 hora' : $d . ' horas');
                            if (fmod($d, 1) == 0.5) {
                                $h = (int)floor($d);
                                $lbl = $h . 'h 30m';
                                if ($d == 0.5) $lbl = '30 minutos';
                            }
                        ?>
                            <option value="<?= $d ?>" <?= $d==1?'selected':'' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($esAdminView): ?>
                <div class="col-md-6">
                    <label for="cliente_nombre" class="form-label fw-semibold small">Nombre del cliente <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="cliente_nombre" name="cliente_nombre" placeholder="Ej: Juan Pérez" required>
                    <input type="hidden" id="cliente_id" name="cliente_id" value="0">
                </div>
                                <div class="col-md-6">
                    <label for="contacto_telefono" class="form-label fw-semibold small">Número de contacto (celular / WhatsApp) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:#f8fafc; font-weight:700; color:#334155; border-color:#e2e8f0;">+51</span>
                        <input type="tel" class="form-control" id="contacto_telefono" name="contacto_telefono" placeholder="987 654 321" required>
                    </div>
                    <div class="invalid-feedback">Ingresa tu celular / WhatsApp.</div>
                </div>
                <?php endif; ?>
                <div class="col-12">
                    <label for="observaciones" class="form-label fw-semibold small">Observaciones (opcional)</label>
                    <input type="text" class="form-control" id="observaciones" name="observaciones" placeholder="Ej: Campeonato inter-áreas, traer árbitro...">
                </div>
            </div>
            <small class="text-muted d-block mt-2"><i class="bi bi-info-circle me-1"></i> Mismo horario para todas las canchas seleccionadas.</small>
        </div>

        <!-- Selección de canchas -->
        <div class="pago-card mb-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <h2 class="pago-h2 mb-0">Elige tus canchas</h2>
                <small class="text-muted"><span id="selCount">0</span> seleccionadas</small>
            </div>
            <p class="pago-text">Selecciona varias canchas — todas compartirán el mismo horario del evento.</p>

            <div class="row g-3" id="canchasGrid">
                <?php foreach ($canchas as $c): ?>
                    <?php $tid = (int)$c['id']; ?>
                    <div class="col-md-6">
                        <label class="cancha-evt" style="display:block; border:1.5px solid #e2e8f0; border-radius:12px; padding:12px; cursor:pointer; transition:all .15s;">
                            <span style="display:flex; align-items:center; gap:10px;">
                                <input type="checkbox" name="canchas_ids[]" value="<?= $tid ?>" class="chk-cancha" data-precio="<?= $c['precio_hora'] ?>" data-nombre="<?= htmlspecialchars($c['nombre']) ?>" style="width:18px;height:18px;">
                                <strong style="font-size:.9rem;"><?= htmlspecialchars($c['nombre']) ?></strong>
                                <span class="ms-auto fw-bold" style="color:#1a7a3a; font-size:.85rem;">S/ <?= number_format((float)$c['precio_hora'], 0) ?>/h</span>
                            </span>
                            <span class="text-muted" style="font-size:.72rem; margin-left:28px;"><?= !empty($c['techada']) ? 'Techada' : 'Aire libre' ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Pago -->
        <div class="pago-card mb-3">
            <?php if ($esAdminView): ?>
                <h2 class="pago-h2">Cobro y método de pago</h2>
                <p class="pago-text">Reserva presencial de evento: selecciona modalidad de cobro y método de pago.</p>

                <div class="p-3 mb-3" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;">
                    <label class="form-label fw-bold small text-dark mb-2"><i class="bi bi-cash-coin me-1 text-success"></i> Modalidad de cobro presencial:</label>
                    <div class="d-flex gap-3 flex-wrap">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_cobro" id="tipoCobroEvtCompleto" value="completo" checked onchange="cambiarTipoCobroEvt('completo')">
                            <label class="form-check-label fw-semibold" for="tipoCobroEvtCompleto">
                                Pago completo en mostrador <span class="badge bg-success-subtle text-success ms-1">Saldo S/ 0</span>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_cobro" id="tipoCobroEvtAdelanto" value="adelanto" onchange="cambiarTipoCobroEvt('adelanto')">
                            <label class="form-check-label fw-semibold" for="tipoCobroEvtAdelanto">
                                Adelanto de S/ 20 por cancha <span class="badge bg-warning-subtle text-warning-emphasis ms-1">Deja saldo pendiente</span>
                            </label>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <h2 class="pago-h2">Pago del adelanto</h2>
                <p class="pago-text">Adelanto habitual de <strong>S/ 20 por cada cancha</strong> seleccionada mediante Yape. Luego sube tu comprobante.</p>
            <?php endif; ?>

            <input type="hidden" name="metodo_pago" id="metodo_pago" value="<?= $esAdminView ? 'efectivo' : 'yape' ?>">
            <div class="row g-2 mb-3">
                <?php if ($esAdminView): ?>
                    <div class="col-4"><button type="button" class="metodo-btn active" id="btnEfectivo" onclick="elegirMetodoEvt('efectivo')"><span class="bcp-logo" style="background:#1a7a3a;">S/</span> Efectivo</button></div>
                    <div class="col-4"><button type="button" class="metodo-btn" id="btnNiubiz" onclick="elegirMetodoEvt('niubiz')"><span class="bcp-logo" style="background:#005bb5;">POS</span> Niubiz (POS)</button></div>
                    <div class="col-4"><button type="button" class="metodo-btn" id="btnYape" onclick="elegirMetodoEvt('yape')"><span class="yape-logo">yape</span> Yape</button></div>
                <?php else: ?>
                    <div class="col-12"><button type="button" class="metodo-btn active" id="btnYape" onclick="elegirMetodoEvt('yape')" style="cursor:default;"><span class="yape-logo">yape</span> Yape — Adelanto S/ 20 por cancha</button></div>
                <?php endif; ?>
            </div>

            <?php if ($esAdminView): ?>
                <div class="pago-panel" id="panelEfectivo">
                    <div class="pago-importante" style="background:#eef7f0;">
                        <div class="fw-bold mb-1"><i class="bi bi-cash-stack me-1"></i> Cobro presencial en efectivo</div>
                        <ul class="mb-0">
                            <li>El encargado recibe el dinero en efectivo en el mostrador.</li>
                            <li>Ingreso verificado inmediatamente en caja diaria. Sin comprobante obligatorio.</li>
                        </ul>
                    </div>
                </div>

                <div class="pago-panel d-none" id="panelNiubiz">
                    <div class="pago-importante" style="background:#f0f7ff; color:#1e3a8a;">
                        <div class="fw-bold mb-1"><i class="bi bi-credit-card-2-front me-1" style="color:#005bb5;"></i> Cobro presencial con Niubiz POS</div>
                        <ul class="mb-0">
                            <li>Cobro realizado a través del equipo POS físico de Top Gol.</li>
                            <li>Ingreso registrado bajo método Niubiz para el cuadre de caja de tarjetas.</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <div class="pago-panel <?= $esAdminView ? 'd-none' : '' ?>" id="panelYape">
                <div class="row g-3">
                    <div class="col-md-5 text-center">
                        <div class="qr-title">Escanea el código QR con Yape</div>
                        <div class="qr-wrap">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=YAPE-TOPGOL-TACNA-987654321-EVENTO" alt="QR Yape Top Gol" width="180" height="180">
                            <span class="qr-logo">yape</span>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="pago-numero-box">
                            <div class="fw-semibold small mb-2">También puedes yapear al número:</div>
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
                            <ul class="mb-0">
                                <li>Monto del adelanto: <strong>S/ 20 por cada cancha</strong> reservada.</li>
                                <li>Sube tu constancia de pago de Yape en el formulario de abajo.</li>
                                <li>El encargado verificará el pago para confirmar tu evento.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comprobante -->
        <div class="pago-card mb-3 <?= $esAdminView ? 'd-none' : '' ?>" id="seccionComprobante" style="<?= $esAdminView ? 'display:none;' : '' ?>">
            <h2 class="pago-h2">Sube tu comprobante <span class="text-danger">*</span></h2>
            <p class="pago-text">Un solo comprobante para todo el evento (JPG, PNG o PDF, máx. 5MB).</p>
            <label class="dropzone" id="dropzone" for="comprobante">
                <input type="file" id="comprobante" name="comprobante" accept=".jpg,.jpeg,.png,.pdf" hidden>
                <span class="dz-ico"><i class="bi bi-cloud-upload-fill"></i></span>
                <strong>Haz clic para subir tu comprobante</strong>
                <span class="dz-sub">o arrastra y suelta tu archivo aquí</span>
                <span class="dz-file d-none" id="fileInfo"></span>
            </label>
            <div class="dz-formats">Formatos soportados: JPG, PNG, PDF (Máx. 5MB)</div>
            <div class="invalid-feedback d-none" id="fileError">Archivo no válido.</div>
        </div>

        <!-- Resumen -->
        <div class="pago-card mb-3">
            <h2 class="pago-h2">Resumen del evento</h2>
            <div id="resumenLista" class="small text-muted mb-2">Selecciona al menos una cancha.</div>
            <div class="resumen-pago">
                <div class="rp-title">Detalle de pago</div>
                <div class="rp-row"><span>Total del evento</span><strong id="resTotal">S/ 0</strong></div>
                <div class="rp-row verde"><span id="resAdelantoLbl">Adelanto requerido (hoy)</span><strong id="resAdelanto">S/ 0</strong></div>
                <div class="rp-row saldo"><span>Saldo pendiente</span><strong id="resSaldo">S/ 0</strong></div>
                <div class="rp-row" style="font-size:.7rem; color:#7c8aa0;"><span>Canchas</span><strong id="resCanchas">0</strong></div>
            </div>
            <div class="resumen-alerta mt-3">
                <span class="ra-ico"><i class="bi bi-clock-history"></i></span>
                <span><strong id="resAlertaTitle">Revisión de Pago</strong><small id="resAlertaText">Una vez verificado tu comprobante. Te notificaremos para completar la reserva.</small></span>
            </div>
        </div>

        <button type="submit" class="btn-enviar"> <i class="bi bi-send me-2"></i> Reservar evento</button>
    </form>
</div>

<style>
.pago-page { background:#f6f9fb; border-radius:12px; }
.pago-h1 { font-size:1.8rem; font-weight:800; color:#101c33; margin:0; }
.pago-sub { color:#7c8aa0; margin:4px 0 0; font-size:.85rem; }
.stepper { display:flex; align-items:flex-start; gap:8px; margin:6px 0 18px; }
.step { display:flex; align-items:center; gap:8px; }
.step .dot { width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; background:#e5e9ef; color:#8a97a8; flex-shrink:0; }
.step .lbl { font-size:.75rem; color:#8a97a8; line-height:1.2; }
.step.done .dot { background:#1a7a3a; color:#fff; }
.step.done .lbl { color:#101c33; font-weight:600; }
.step.active .dot { background:#22c55e; color:#fff; box-shadow:0 0 0 5px rgba(34,197,94,.18); }
.step.active .lbl { color:#101c33; font-weight:600; }
.stepper .line { flex:1; height:3px; border-radius:3px; background:#e5e9ef; margin-top:16px; min-width:30px; }
.stepper .line.done { background:#22c55e; }
.pago-card { background:#fff; border:1px solid #e8eef4; border-radius:12px; padding:18px; box-shadow:0 1px 2px rgba(16,28,51,.04); }
.pago-h2 { font-size:1.05rem; font-weight:800; color:#101c33; margin-bottom:2px; }
.pago-text { color:#7c8aa0; font-size:.85rem; margin-bottom:12px; }
.metodo-btn { width:100%; display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; border:1.5px solid #e2e8f0; background:#fff; font-weight:700; color:#101c33; transition:all .15s; }
.metodo-btn.active { background:#effaf1; border-color:#22c55e; }
.yape-logo { background:#742284; color:#fff; font-style:italic; font-weight:800; border-radius:8px; padding:6px 10px; font-size:.85rem; }
.bcp-logo { background:#002a8d; color:#fff; font-weight:800; border-radius:8px; padding:6px 10px; font-size:.8rem; }
.cancha-evt:has(.chk-cancha:checked) { border-color:#22c55e !important; background:#effaf1; }
.pago-panel { border:1px solid #e8eef4; border-radius:10px; padding:14px; }
.qr-title { font-size:.8rem; font-weight:600; margin-bottom:8px; }
.qr-wrap { position:relative; display:inline-block; }
.qr-wrap img { border-radius:8px; display:block; }
.qr-logo { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:#742284; color:#fff; font-style:italic; font-weight:800; border-radius:12px; padding:8px 12px; border:3px solid #fff; }
.pago-numero-box { background:#f4f7fb; border-radius:10px; padding:12px; margin-bottom:12px; color:#101c33; }
.tel-num { font-size:1.1rem; letter-spacing:.5px; }
.btn-copiar { border:none; background:transparent; color:#1a7a3a; font-weight:700; font-size:.85rem; }
.pago-importante { background:#e9f7ee; border-radius:10px; padding:12px 14px; font-size:.8rem; color:#23402e; }
.pago-importante ul { margin:6px 0 0; padding-left:18px; }
.info-ico { display:inline-flex; width:22px; height:22px; border-radius:50%; background:#1a7a3a; color:#fff; align-items:center; justify-content:center; font-size:.8rem; margin-right:4px; }
.dropzone { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px; border:1.5px dashed #c4cfdb; background:#f7fafc; border-radius:10px; padding:28px 16px; cursor:pointer; text-align:center; transition:all .15s; }
.dropzone:hover, .dropzone.over { border-color:#22c55e; background:#effaf1; }
.dz-ico { font-size:2rem; color:#42506b; }
.dz-sub { color:#7c8aa0; font-size:.85rem; }
.dz-formats { text-align:center; color:#7c8aa0; font-size:.72rem; margin-top:6px; }
.dz-file { margin-top:8px; font-size:.8rem; background:#e9f7ee; color:#166534; border-radius:8px; padding:6px 10px; }
.btn-enviar { width:100%; background:#0d7a33; color:#fff; border:none; border-radius:10px; padding:13px; font-weight:800; font-size:1rem; }
.btn-enviar:hover { background:#0a6129; color:#fff; }
.resumen-pago { background:#f4f7fb; border-radius:10px; margin:8px 0; padding:12px 14px; font-size:.85rem; }
.rp-title { font-weight:800; margin-bottom:6px; color:#101c33; }
.rp-row { display:flex; justify-content:space-between; padding:3px 0; color:#33415c; }
.rp-row.verde { color:#1a7a3a; font-weight:700; }
.rp-row.saldo { background:#e9f7ee; color:#166534; font-weight:800; border-radius:8px; padding:8px 10px; margin-top:6px; }
.resumen-alerta { display:flex; gap:10px; background:#fef6e0; border-radius:10px; padding:12px 14px; }
.ra-ico { color:#d97706; font-size:1.4rem; }
.resumen-alerta strong { display:block; color:#b45309; font-size:.9rem; }
.resumen-alerta small { color:#7c6a3a; font-size:.78rem; }
</style>

var ES_ADMIN_EVT = <?= $esAdminView ? 'true' : 'false' ?>;
var tipoCobroEvtActual = 'completo';

function cambiarTipoCobroEvt(tipo) {
    tipoCobroEvtActual = tipo;
    if (typeof window.actualizarEvtResumen === 'function') window.actualizarEvtResumen();
}

function elegirMetodoEvt(m){
    var esEfe = m === 'efectivo', esNiu = m === 'niubiz', esYape = m === 'yape';
    var bE = document.getElementById('btnEfectivo'), bN = document.getElementById('btnNiubiz'), bY = document.getElementById('btnYape');
    var pE = document.getElementById('panelEfectivo'), pN = document.getElementById('panelNiubiz'), pY = document.getElementById('panelYape');
    var sec = document.getElementById('seccionComprobante');

    if(bE) bE.classList.toggle('active', esEfe);
    if(bN) bN.classList.toggle('active', esNiu);
    if(bY) bY.classList.toggle('active', esYape);

    if(pE) pE.classList.toggle('d-none', !esEfe);
    if(pN) pN.classList.toggle('d-none', !esNiu);
    if(pY) pY.classList.toggle('d-none', !esYape);

    document.getElementById('metodo_pago').value = m;

    if(ES_ADMIN_EVT && sec){
        var ocultar = esEfe || esNiu;
        sec.classList.toggle('d-none', ocultar);
        sec.style.display = ocultar ? 'none' : '';
    }
    if (typeof window.actualizarEvtResumen === 'function') window.actualizarEvtResumen();
}
function copiarNumero(num, btn){
    var done=function(){ var o=btn.innerHTML; btn.innerHTML='<i class="bi bi-check-lg"></i> Copiado'; setTimeout(function(){ btn.innerHTML=o; },1500); };
    if(navigator.clipboard && navigator.clipboard.writeText){ navigator.clipboard.writeText(num).then(done).catch(done); }
    else { var t=document.createElement('textarea'); t.value=num; document.body.appendChild(t); t.select(); try{document.execCommand('copy');}catch(e){} document.body.removeChild(t); done(); }
}
(function(){
    var fecha=document.getElementById('fecha');
    var hoyStr=<?= json_encode($hoyStr) ?>;
    var horaMinHoy=<?= (int)$horaMinHoy ?>;
    var form=document.getElementById('formEvento');
    var alertBox=document.getElementById('formAlert'), alertList=document.getElementById('formAlertList');
    var dz=document.getElementById('dropzone'), input=document.getElementById('comprobante'), info=document.getElementById('fileInfo'), err=document.getElementById('fileError');
    var validTypes=['image/jpeg','image/png','application/pdf'], maxSize=5*1024*1024;
    function validar(f){ if(!f) return true; var okT=validTypes.includes(f.type) || /\.(jpe?g|png|pdf)$/i.test(f.name); var okS=f.size<=maxSize; err.classList.toggle('d-none', okT && okS); return okT && okS; }
    function mostrar(f){ if(!f) return; info.textContent='📎 '+f.name+' ('+(f.size/1024).toFixed(0)+' KB)'; info.classList.remove('d-none'); }
    if(input){
        input.addEventListener('change', function(){ var f=input.files[0]; if(f && validar(f)) mostrar(f); else if(f){ input.value=''; info.classList.add('d-none'); } });
        if(dz){
            ['dragenter','dragover'].forEach(function(ev){ dz.addEventListener(ev,function(e){ e.preventDefault(); dz.classList.add('over'); }); });
            ['dragleave','drop'].forEach(function(ev){ dz.addEventListener(ev,function(e){ e.preventDefault(); dz.classList.remove('over'); }); });
            dz.addEventListener('drop', function(e){
                var f=e.dataTransfer.files && e.dataTransfer.files[0]; if(!f) return;
                if(!validar(f)){ input.value=''; info.classList.add('d-none'); return; }
                var dt=new DataTransfer(); dt.items.add(f); input.files=dt.files; mostrar(f);
            });
        }
    }

    // Actualizar resumen al seleccionar canchas
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('chk-cancha')) {
            window.actualizarEvtResumen();
        }
    });

    // Filtrar horas pasadas si fecha es hoy (único horario)
    var selHoraValEvt=document.getElementById('horaVal');
    var selMinValEvt=document.getElementById('minVal');
    var selHoraEvt=document.getElementById('hora'); // Hidden input
    var selDurEvt=document.getElementById('duracion');

    function actualizarInputHora() {
        if (selHoraValEvt && selMinValEvt && selHoraEvt) {
            if (selHoraValEvt.value && selMinValEvt.value) {
                selHoraEvt.value = selHoraValEvt.value + ':' + selMinValEvt.value;
            } else {
                selHoraEvt.value = '';
            }
        }
    }

    function filtrarHorasEvt(){
        if(!selHoraValEvt) return;
        var esHoy = fecha.value === hoyStr;
        var visibles=0;
        Array.from(selHoraValEvt.options).forEach(function(o){
            if(!o.value) return;
            var h=parseInt(o.value,10);
            var oculta = esHoy && h < horaMinHoy;
            o.hidden=oculta; o.disabled=oculta;
            if(!oculta) visibles++;
            if(oculta && o.selected) selHoraValEvt.value='';
        });
        var aviso=document.getElementById('sinHorariosHoy');
        if(aviso) aviso.classList.toggle('d-none', !(esHoy && visibles===0));
        actualizarInputHora();
    }

    fecha.addEventListener('change', function(){ filtrarHorasEvt(); window.actualizarEvtResumen(); });
    if(selHoraValEvt) selHoraValEvt.addEventListener('change', function(){ actualizarInputHora(); window.actualizarEvtResumen(); });
    if(selMinValEvt) selMinValEvt.addEventListener('change', function(){ actualizarInputHora(); window.actualizarEvtResumen(); });
    if(selDurEvt) selDurEvt.addEventListener('change', window.actualizarEvtResumen);
    filtrarHorasEvt();

    window.actualizarEvtResumen = function(){
        var sel=document.querySelectorAll('.chk-cancha:checked');
        document.getElementById('selCount').textContent = sel.length;
        var total=0;
        var listaHtml='';
        var horaVal = selHoraEvt ? selHoraEvt.value : '';
        var durVal = selDurEvt ? parseFloat(selDurEvt.value||'1') : 1;
        if(!horaVal) horaVal='--:--';
        sel.forEach(function(chk){
            var precio=parseFloat(chk.getAttribute('data-precio')||'0');
            var nombre=chk.getAttribute('data-nombre')||'';
            var sub=precio*durVal;
            total+=sub;
            var dLbl2 = durVal==0.5 ? '30m' : (durVal==1 ? '1h' : (durVal==1.5 ? '1h 30m' : durVal+'h'));
            listaHtml += '<div style="display:flex; justify-content:space-between; padding:4px 0; border-bottom:1px solid #eef2f7;"><span>'+nombre+' ('+horaVal+' · '+dLbl2+')</span><strong>S/ '+sub.toFixed(0)+'</strong></div>';
        });
        if(sel.length===0) listaHtml='<span class="text-muted">Selecciona al menos una cancha.</span>';
        document.getElementById('resumenLista').innerHTML = listaHtml;
        var esCompletoAdmin = ES_ADMIN_EVT && tipoCobroEvtActual === 'completo';
        var adelanto = esCompletoAdmin ? total : (sel.length * 20);
        var saldo = Math.max(0, total - adelanto);
        document.getElementById('resTotal').textContent='S/ '+total.toFixed(0);
        document.getElementById('resAdelanto').textContent='S/ '+adelanto.toFixed(0);
        document.getElementById('resSaldo').textContent='S/ '+saldo.toFixed(0);
        document.getElementById('resCanchas').textContent=String(sel.length);
        document.getElementById('resAdelantoLbl').textContent = esCompletoAdmin ? 'Cobro completo en mostrador' : 'Adelanto requerido (hoy) · S/ 20 x cancha';
        document.getElementById('resAlertaTitle').textContent = esCompletoAdmin ? 'Cobro presencial en mostrador' : 'Pago del evento';
        document.getElementById('resAlertaText').textContent = esCompletoAdmin ? 'Reserva verificada al registrarse. Cobro completo en sitio.' : 'Adelanto de S/ 20 por cada cancha. Saldo a cancelar al llegar.';
    };
    // init
    window.actualizarEvtResumen();
    var contactoEvtForAlert2 = document.getElementById('contacto_telefono');
    if(contactoEvtForAlert2) contactoEvtForAlert2.addEventListener('input', function(){ alertBox.classList.add('d-none'); });

    // Validación
    form.addEventListener('submit', function(e){
        var falt=[];
        var sel=document.querySelectorAll('.chk-cancha:checked');
        if(sel.length===0) falt.push('Selecciona al menos una cancha para tu evento.');
        var horaVal2 = selHoraEvt ? selHoraEvt.value : '';
        var durVal2 = selDurEvt ? selDurEvt.value : '';
        if(!horaVal2) falt.push('Elige el horario del evento.');
        if(!durVal2) falt.push('Elige la duración del evento.');
        if(!fecha.value) falt.push('Elige la fecha del evento.');
        var contactoEvt=document.getElementById('contacto_telefono');
        if(!contactoEvt || !contactoEvt.value.trim()) falt.push('Ingresa un número de contacto (celular / WhatsApp).');
        <?php if ($esAdminView): ?>if(!document.getElementById('cliente_nombre').value.trim()) falt.push('Ingresa el nombre del cliente.');<?php endif; ?>
        var metodo=document.getElementById('metodo_pago').value;
        var requiereComp = !ES_ADMIN_EVT;
        var f=input ? input.files[0] : null;
        if(requiereComp && !f){ falt.push('Sube tu comprobante de pago (JPG, PNG o PDF, máx. 5MB).'); if(err){ err.textContent='Falta tu comprobante.'; err.classList.remove('d-none'); } }
        else if(f && !validar(f)) falt.push('Comprobante no válido. Usa JPG, PNG o PDF de máximo 5MB.');
        if(falt.length>0 || !form.checkValidity()){
            e.preventDefault(); e.stopPropagation();
            alertList.innerHTML = falt.map(function(t){ return '<li>'+t+'</li>'; }).join('');
            alertBox.classList.remove('d-none');
            alertBox.scrollIntoView({behavior:'smooth', block:'center'});
        } else { alertBox.classList.add('d-none'); }
        form.classList.add('was-validated');
    });
})();
</script>
